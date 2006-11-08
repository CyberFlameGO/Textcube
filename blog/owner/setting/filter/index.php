<?php
/// Copyright (c) 2004-2006, Tatter & Company / Tatter & Friends.
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../..');
$IV = array(
	'POST' => array(
		'mode' => array( array( 'ip','content' , 'url', 'name') ,'default'=>null),
		'contentValue' => array('string' , 'default' => null),
		'ipValue' => array('ip' , 'default' => null),
		'urlValue' => array('url' , 'default' => null),
		'nameValue' => array('string' , 'default' => null)
	),
	//'GET' => array(
	//	'history' => array( 'string' , 'default' => null )
	//)
);
require ROOT . '/lib/includeForOwner.php';
requireComponent('Tattertools.Data.Filter');
if (isset($_POST['ipValue'])) {
	$_POST['mode'] = "ip";
} else if (isset($_POST['urlValue'])) {
	$_POST['mode'] = "url";
} else if (isset($_POST['contentValue'])) {
	$_POST['mode'] = "content";
} else if (isset($_POST['nameValue'])) {
	$_POST['mode'] = "name";
}
if (!empty($_POST['mode'])) {
	$filter = new Filter();
	$filter->type = $_POST['mode'];
	$filter->pattern = $_POST[($_POST['mode'] . 'Value')];
	$filter->add();
	//$history = $_POST['mode'];
}
//if (!empty($_GET['history'])) {
//	$history = $_GET['history'];
//}
require ROOT . '/lib/piece/owner/header5.php';
require ROOT . '/lib/piece/owner/contentMenu53.php';

function printFilterBox($mode, $title) {
	global $service;
	$filter = new Filter();
	$filtersList = array();
	if ($filter->open($mode)) {
		do {
			$filtersList[] = array(0 => $filter->id, 1 => $filter->pattern);
		} while ($filter->shift());
		$filter->close();
	}
?>
									<h3><?php echo $title;?></h3>
									
									<div class="filtering-words">
										<table cellpadding="0" cellspacing="0">
											<tbody>
<?php
	if ($filtersList) {
		$id = 0;
		$count = 0;
		foreach ($filtersList as $key => $value) {
			$entity = $value[1];
			
			$className = ($count % 2) == 1 ? 'even-line' : 'odd-line';
			$className .= ($id == sizeof($filtersList) - 1) ? ' last-line' : '';
?>
												<tr class="<?php echo $className;?> inactive-class" onmouseover="rolloverClass(this, 'over')" onmouseout="rolloverClass(this, 'out')">
			<td class="content"><span title="<?php echo escapeJSInAttribute($entity);?>"><?php echo htmlspecialchars(UTF8::lessenAsEm($entity, 30));?></span></td>
													<td class="delete"><a class="delete-button button" href="#void" onclick="deleteFilter(parentNode.parentNode,'<?php echo $mode;?>', '<?php echo urlencode($entity);?>',<?php echo $value[0];?>); return false;" title="<?php echo _t('이 필터링을 제거합니다.');?>"><span class="text"><?php echo _t('삭제');?></span></a></td>
												</tr>
<?php
			$id++;
			$count++;
		}
	} else {
?>
												<tr class="odd-line inactive-class" onmouseover="rolloverClass(this, 'over')" onmouseout="rolloverClass(this, 'out')">
													<td class="empty"><?php echo _t('등록된 내용이 없습니다.');?></td>
												</tr>
<?php
	}
?>
											</tbody>
										</table>
									</div>
									
									<div class="input-field">
										<input type="text" class="input-text" name="<?php echo $mode;?>Value" onkeyup="if(event.keyCode=='13') {add('<?php echo $mode;?>')}" />
									</div>
									
									<div class="button-box">
										<input type="submit" class="add-button input-button" value="<?php echo _t('추가하기');?>" onclick="add('<?php echo $mode;?>'); return false;" />
									</div>
<?php
}
?>
						<script type="text/javascript">
							//<![CDATA[
								
								function changeColor(caller, color) {
									var target 	= document.getElementById(field) ;
									target.style.backgroundColor=color;
								}
								
								function deleteFilter(caller, mode, value, id) {
									if (!confirm('<?php echo _t('선택된 목록을 필터링에서 제외합니다. 계속 하시겠습니까?');?>')) return false;
									var execute = 'close';
									
									param  = '?mode=' + mode;
									param += '&value=' + value;
									param += '&command=unblock';
									param += '&id=' + id;
									
									var request = new HTTPRequest("GET", "<?php echo $blogURL;?>/owner/setting/filter/change/" + param);
									request.onSuccess = function() {
										var parent = caller.parentNode;
										parent.removeChild(caller);
										
										if(parent.rows.length == 0) {	
											var tr = document.createElement("tr");
											tr.className = "odd-line inactive-class";
											tr.setAttribute("onmouseover", "rolloverClass(this, 'over')");
											tr.setAttribute("onmouseout", "rolloverClass(this, 'out')");
											var td = document.createElement("td");
											td.className = "empty";
											td.appendChild(document.createTextNode("<?php echo _t('등록된 내용이 없습니다.');?>"));
											tr.appendChild(td);
											parent.appendChild(tr);
										}
									}
									request.onError = function() {
										alert("<?php echo _t('필터링을 삭제하지 못했습니다.');?>");
									}
									request.send();
								}
								
								function add(mode) {
									switch (mode) {
										case 'ip':
											target 	= document.getElementById('ipSection').ipValue;
											break;
										case 'url':
											target 	= document.getElementById('urlSection').urlValue;
											break;
										case 'content':
											target 	= document.getElementById('contentSection').contentValue;
											break;
										case 'name':
											target 	= document.getElementById('nameSection').nameValue;
											break;
									}
									
									if(target.value=="") {
										alert("<?php echo _t('내용을 입력해 주십시오.');?>");
										return false;
									}

									if(mode == 'url') {
										var reg = new RegExp('^http://', "gi");
										target.value = target.value.replace(reg,'');
									}
									
									if(mode == 'ip') {
										reg = /\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/;
										if(!reg.test(target.value)) {
											alert("<?php echo _t('잘못된 IP 주소입니다.');?>");
											return;
										};
									}
									
									switch (mode) {
										case 'ip':
											document.getElementById('ipSection').submit();
											break;
										case 'url':
											document.getElementById('urlSection').submit();
											break;
										case 'content':
											document.getElementById('contentSection').submit();
											break;
										case 'name':
											document.getElementById('nameSection').submit();
											break;
									}
								}
								
							//]]>
						</script>
						
						<div id="part-setting-filter" class="part">
							<h2 class="caption"><span class="main-text"><?php echo _t('필터를 설정합니다');?></span></h2>
							
							<div class="main-explain-box">
								<p class="explain"><?php echo _t('댓글, 글걸기, 리퍼러가 입력될 때 아래의 단어가 포함되어 있으면 알림창을 띄우고 휴지통으로 보냅니다.');?></p>
							</div>
							
							<div class="data-inbox">
								<form id="ipSection" class="section" method="post" action="<?php echo $blogURL;?>/owner/setting/filter">
<?php echo printFilterBox('ip', _t('IP 필터링'));?>
								</form>
										
								<hr class="hidden" />
										
								<form id="urlSection" class="section" method="post" action="<?php echo $blogURL;?>/owner/setting/filter">
<?php echo printFilterBox('url', _t('홈페이지 필터링'));?>
								</form>
								
								<hr class="hidden" />
								
								<form id="contentSection" class="section" method="post" action="<?php echo $blogURL;?>/owner/setting/filter">
<?php echo printFilterBox('content', _t('본문 필터링'));?>
								</form>
								
								<hr class="hidden" />
								
								<form id="nameSection" class="section" method="post" action="<?php echo $blogURL;?>/owner/setting/filter">
<?php echo printFilterBox('name', _t('이름 필터링'));?>
								</form>
							</div>
						</div>
<?php
require ROOT . '/lib/piece/owner/footer1.php';
?>
