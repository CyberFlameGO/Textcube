<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../..');
if (isset($_POST['page']))
	$_GET['page'] = $_POST['page'];
if(count($_POST) > 0) {
	$IV = array(
		'GET' => array(
			'name' => array('string', 'mandatory' => false),
			'ip' => array('ip', 'mandatory' => false),
			'page' => array('int', 1, 'default' => 1)
		),
		'POST' => array(
			'category' => array('int', 'default' => 0),
			'name' => array('string', 'mandatory' => false),
			'ip' => array('ip', 'mandatory' => false),
			'withSearch' => array(array('on'), 'mandatory' => false),
			'search' => array('string', 'default' => ''),
			'perPage' => array('int', 1, 'mandatory' => false)
		)
	);
}
require ROOT . '/lib/includeForBlogOwner.php';
$categoryId = empty($_POST['category']) ? 0 : $_POST['category'];
$name = isset($_GET['name']) && !empty($_GET['name']) ? $_GET['name'] : '';
$name = isset($_POST['name']) && !empty($_POST['name']) ? $_POST['name'] : $name;
$ip = isset($_GET['ip']) && !empty($_GET['ip']) ? $_GET['ip'] : '';
$ip = isset($_POST['ip']) && !empty($_POST['ip']) ? $_POST['ip'] : $ip;
$search = empty($_POST['withSearch']) || empty($_POST['search']) ? '' : trim($_POST['search']);
$perPage = getUserSetting('rowsPerPage', 10); 
if (isset($_POST['perPage']) && is_numeric($_POST['perPage'])) {
	$perPage = $_POST['perPage'];
	setUserSetting('rowsPerPage', $_POST['perPage']);
}
list($comments, $paging) = getCommentsNotifiedWithPagingForOwner($owner, '', $name, '', $search, $suri['page'], $perPage);
require ROOT . '/lib/piece/owner/header.php';
require ROOT . '/lib/piece/owner/contentMenu.php';
?>
						<script type="text/javascript">
							//<![CDATA[
								function deleteComment(id) {
									if (!confirm("<?php echo _t('선택된 댓글을 삭제합니다. 계속 하시겠습니까?');?>"))
										return;
									var request = new HTTPRequest("GET", "<?php echo $blogURL;?>/owner/entry/notify/delete/" + id);
									request.onSuccess = function () {
										document.getElementById('list-form').submit();
									}
									request.send();
								}
								
								function deleteComments() {	
									if (!confirm("<?php echo _t('선택된 댓글을 삭제합니다. 계속 하시겠습니까?');?>"))
										return false;
									var oElement;
									var targets = new Array();
									for (i = 0; document.getElementById('list-form').elements[i]; i ++) {
										oElement = document.getElementById('list-form').elements[i];
										if ((oElement.name == "entry") && oElement.checked)
											targets[targets.length] = oElement.value;
									}
									var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/entry/notify/delete");
									request.onSuccess = function() {
										document.getElementById('list-form').submit();
									}
									request.send("targets=" + targets.join(','));
								}
								
								function checkAll(checked) {
									for (i = 0; document.getElementById('list-form').elements[i]; i++) {
										if (document.getElementById('list-form').elements[i].name == "entry") {
											if (document.getElementById('list-form').elements[i].checked != checked) {
												document.getElementById('list-form').elements[i].checked = checked;
												toggleThisTr(document.getElementById('list-form').elements[i]);
											}
										}
									}
								}
								
								function changeState(caller, value, mode) {
									try {			
										if (caller.className == 'block-icon bullet') {
											var command 	= 'unblock';
										} else {
											var command 	= 'block';
										}
										var name 		= caller.id.replace(/\-[0-9]+$/, '');
										param  	=  '?value='	+ encodeURIComponent(value);
										param 	+= '&mode=' 	+ mode;
										param 	+= '&command=' 	+ command;
										
										var request = new HTTPRequest("GET", "<?php echo $blogURL;?>/owner/setting/filter/change/" + param);
										var iconList = document.getElementsByTagName("a");	
										for (var i = 0; i < iconList.length; i++) {
											icon = iconList[i];
											if(icon.id == null || icon.id.replace(/\-[0-9]+$/, '') != name) {
												continue;
											} else {
												if (command == 'block') {
													icon.className = 'block-icon bullet';
													icon.innerHTML = '<span class="text"><?php echo _t('[차단됨]');?><\/span>';
													icon.setAttribute('title', "<?php echo _t('이 이름은 차단되었습니다. 클릭하시면 차단을 해제합니다.');?>");
												} else {
													icon.className = 'unblock-icon bullet';
													icon.innerHTML = '<span class="text"><?php echo _t('[허용됨]');?><\/span>';
													icon.setAttribute('title', "<?php echo _t('이 이름은 차단되지 않았습니다. 클릭하시면 차단합니다.');?>");
												}
											}
										}
										request.send();
									} catch(e) {
										alert(e.message);
									}
								}
								
								window.addEventListener("load", execLoadFunction, false);
								function execLoadFunction() {
									document.getElementById('allChecked').disabled = false;
								}
								
								function toggleThisTr(obj) {
									objTR = getParentByTagName("TR", obj);
									
									if (objTR.className.match('inactive')) {
										objTR.className = objTR.className.replace('inactive', 'active');
									} else {
										objTR.className = objTR.className.replace('active', 'inactive');
									}
								}
							//]]>
						</script>
									
						<div id="part-post-notify" class="part">
							<h2 class="caption">
								<span class="main-text"><?php echo _t('댓글 알리미입니다');?></span>
<?php
if (strlen($name) > 0 || strlen($ip) > 0) {
	if (strlen($name) > 0) {
?>
								<span class="filter-condition"><?php echo htmlspecialchars($name);?></span>
<?php
	}
	
	if (strlen($ip) > 0) {
?>
								<span class="filter-condition"><?php echo htmlspecialchars($ip);?></span>
<?php
	}
}
?>
							</h2>
							
							<div class="main-explain-box">
								<p class="explain"><?php echo _f('다른 사람의 블로그에 단 댓글에 대한 댓글이 등록되면 알려줍니다. 알리미가 동작하기 위해서는 댓글 작성시 홈페이지 기입란에 자신의 블로그 주소(<samp>%1</samp>)를 입력하셔야 합니다.',$user['homepage']);?></p>
							</div>
							
							<form id="list-form" method="post" action="<?php echo $blogURL;?>/owner/entry/notify">
								<table class="data-inbox" cellspacing="0" cellpadding="0">
									<thead>
										<tr>
											<th class="selection"><input type="checkbox" id="allChecked" class="checkbox" onclick="checkAll(this.checked);" disabled="disabled" /></th>
											<th class="date"><span class="text"><?php echo _t('등록일자');?></span></th>
											<th class="site"><span class="text"><?php echo _t('사이트명');?></span></th>
											<th class="name"><span class="text"><?php echo _t('이름');?></span></th>
											<th class="content"><span class="text"><?php echo _t('내용');?></span></th>
											<th class="delete"><span class="text"><?php echo _t('삭제');?></span></th>
										</tr>
									</thead>
									<tbody>
<?php
$more = false;
$mergedComments = array();
$lastVisitNotifiedPage = getUserSetting('lastVisitNotifiedPage', null);
setUserSetting('lastVisitNotifiedPage', time());
for ($i = 0; $i < count($comments); $i++) {
	array_push($mergedComments, $comments[$i]);
	$result = getCommentCommentsNotified($comments[$i]['id']);
	for ($j = 0; $j < count($result); $j++) {
		array_push($mergedComments, $result[$j]);
	}
}

$nameNumber = array();
for ($i=0; $i<sizeof($mergedComments); $i++) {
	$comment = $mergedComments[$i];
	
	requireComponent('Textcube.Data.Filter');
	if (Filter::isFiltered('name', $comment['name']))
		$isNameFiltered = true;
	else
		$isNameFiltered = false;
	
	if (!isset($nameNumber[$comment['name']])) {
		$nameNumber[$comment['name']] = $i;
		$currentNumber = $i;
	} else {
		$currentNumber = $nameNumber[$comment['name']];
	}
	
	$className = ($i % 2) == 1 ? 'even-line' : 'odd-line';
	$className .= $comment['parent'] ? ' reply-line' : null;
	$className .= ($i == sizeof($mergedComments) - 1) ? ' last-line' : '';
?>
										<tr class="<?php echo $className;?> inactive-class" onmouseover="rolloverClass(this, 'over')" onmouseout="rolloverClass(this, 'out')">
											<td class="selection"><input type="checkbox" class="checkbox" name="entry" value="<?php echo $comment['id'];?>" onclick="document.getElementById('allChecked').checked=false; toggleThisTr(this);" /></td>
											<td class="date"><?php echo Timestamp::formatDate($comment['written']);?></td>
											<td class="site"><a href="<?php echo $comment['siteUrl'];?>" onclick="window.open(this.href); return false;" title="<?php echo _t('사이트를 연결합니다.');?>"><?php echo htmlspecialchars($comment['siteTitle']);?></a></td>
											<td class="name">
<?php
	if ($isNameFiltered) {
?>
												<a id="nameFilter<?php echo $currentNumber;?>-<?php echo $i;?>" class="block-icon bullet" href="<?php echo $blogURL;?>/owner/setting/filter/change/?value=<?php echo urlencode(escapeJSInAttribute($comment['name']));?>&amp;mode=name&amp;command=unblock" onclick="changeState(this,'<?php echo escapeJSInAttribute($comment['name']);?>', 'name'); return false;" title="<?php echo _t('이 이름은 차단되었습니다. 클릭하시면 차단을 해제합니다.');?>"><span class="text"><?php echo _t('[차단됨]');?></span></a>
<?php
	} else {
?>
												<a id="nameFilter<?php echo $currentNumber;?>-<?php echo $i;?>" class="unblock-icon bullet" href="<?php echo $blogURL;?>/owner/setting/filter/change/?value=<?php echo urlencode(escapeJSInAttribute($comment['name']));?>&amp;mode=name&amp;command=block" onclick="changeState(this,'<?php echo escapeJSInAttribute($comment['name']);?>', 'name'); return false;" title="<?php echo _t('이 이름은 차단되지 않았습니다. 클릭하시면 차단합니다.');?>"><span class="text"><?php echo _t('[허용됨]');?></span></a>
<?php
	}
?>
												<a href="?name=<?php echo urlencode(escapeJSInAttribute($comment['name']));?>" title="<?php echo _t('이 이름으로 등록된 댓글 목록을 보여줍니다.');?>"><?php echo htmlspecialchars($comment['name']);?></a>
											</td>
											<td class="content">
<?php
	if ($comment['parent']) {
		if ($lastVisitNotifiedPage > time() - 86400) {
?>
												<span class="new-icon bullet" title="<?php echo _t('새로 등록된 댓글입니다.');?>"><span class="text">[<?php echo _t('새 댓글');?>]</span></span>
<?php
		}
	} else {										
		echo '<a class="entryURL" href="'.$comment['entryUrl'].'" onclick="window.open(this.href); return false;" title="'._t('댓글이 작성된 포스트로 직접 이동합니다.').'">';
		echo '<span class="entry-title">'. htmlspecialchars($comment['entryTitle']) .'</span>';
		
		if ($comment['entryTitle'] != '' && $comment['parent'] != '') {
			echo '<span class="divider"> | </span>';
		}
		
		echo empty($comment['parent']) ? '' : "<a href=\"" . $comment['parentUrl'] . "\" onclick=\"window.open(this.href); return false;\">" . _f('%1 님의 댓글에 대한 댓글',$comment['parentName']) . "</a>";
		echo "</a>";
		echo !empty($comment['title']) || !empty($comment['parent']) ? '<br />' : '';
	}
?>
												<a class="commentURL" href="<?php echo $comment['url'];?>" onclick="window.open(this.href); return false;" title="<?php echo _t('댓글이 작성된 위치로 직접 이동합니다.');?>"><?php echo htmlspecialchars($comment['comment']);?></a>
											</td>
											<td class="delete">
												<a class="delete-button button" href="<?php echo $blogURL;?>/owner/entry/notify/delete/<?php echo $comment['id'];?>" onclick="deleteComment(<?php echo $comment['id'];?>); return false;" title="<?php echo _t('이 댓글을 삭제합니다.');?>"><span class="text"><?php echo _t('삭제');?></span></a>
											</td>
										</tr>
<?php
}
?>
									</tbody>
								</table>
								
								<hr class="hidden" />
								
								<div class="data-subbox">
									<input type="hidden" name="page" value="<?php echo $suri['page'];?>" />
									<input type="hidden" name="name" value="" />
									
									<div id="delete-section" class="section">
										<span class="label"><?php echo _t('선택한 알림을');?></span>
										<input type="button" class="delete-button input-button" value="<?php echo _t('삭제');?>" onclick="deleteComments();" />
									</div>
									<div id="page-section" class="section">
										<div id="page-navigation">
											<span id="page-list">
<?php
//$paging['url'] = 'document.getElementById('list-form').page.value=';
//$paging['prefix'] = '';
//$paging['postfix'] = '; document.getElementById('list-form').submit()';
$pagingTemplate = '[##_paging_rep_##]';
$pagingItemTemplate = '<a [##_paging_rep_link_##]>[[##_paging_rep_link_num_##]]</a>';
print getPagingView($paging, $pagingTemplate, $pagingItemTemplate);
?>
											</span>
											<span id="total-count"><?php echo _f('총 %1건', empty($paging['total']) ? "0" : $paging['total']);?></span>
										</div>
										<div class="page-count">
											<?php echo getArrayValue(explode('%1', _t('한 페이지에 글 %1건 표시')), 0);?>
											
											<select name="perPage" onchange="document.getElementById('list-form').page.value=1; document.getElementById('list-form').submit()">
	<?php
	for ($i = 10; $i <= 30; $i += 5) {
	if ($i == $perPage) {
	?>
												<option value="<?php echo $i;?>" selected="selected"><?php echo $i;?></option>
	<?php
	} else {
	?>
												<option value="<?php echo $i;?>"><?php echo $i;?></option>
	<?php
	}
	}
	?>
											</select>
											<?php echo getArrayValue(explode('%1', _t('한 페이지에 글 %1건 표시')), 1).CRLF;?>
										</div>
									</div>
								</div>
							</form>
							
							<hr class="hidden" />
							
							<form id="search-form" class="data-subbox" method="post" action="<?php echo $blogURL;?>/owner/entry/notify">
								<h2><?php echo _t('검색');?></h2>
								
								<div class="section">
									<label for="search"><?php echo _t('제목');?>, <?php echo _t('사이트명');?>, <?php echo _t('내용');?></label>
									<input type="text" id="search" class="input-text" name="search" value="<?php echo htmlspecialchars($search);?>" onkeydown="if (event.keyCode == '13') { document.getElementById('search-form').withSearch.value = 'on'; document.getElementById('search-form').submit(); }" />
									<input type="hidden" name="withSearch" value="" />
									<input type="submit" class="search-button input-button" value="<?php echo _t('검색');?>" onclick="document.getElementById('search-form').withSearch.value = 'on'; document.getElementById('search-form').submit();" />
								</div>
							</form>
						</div>
<?php
require ROOT . '/lib/piece/owner/footer.php';
?>
