<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../..');

require ROOT . '/lib/includeForBlogOwner.php';
require ROOT . '/lib/piece/owner/header.php';
require ROOT . '/lib/piece/owner/contentMenu.php';
requireModel('common.plugin');

// set the selected tab.
if (isset($_GET['visibility'])) {
	$_POST['visibility'] = $_GET['visibility'];
} else if (!isset($_POST['visibility'])) {
	$_POST['visibility'] = 'blog';
}

switch ($_POST['visibility']) {
	case 'center':
		define('__TAB_CENTER__', true);
		$memberScopes = 'center';
		break;
	case 'coverpage':
		define('__TAB_COVERPAGE__', true);
		$memberScopes = 'coverpage'; // 임시.
		break;
	case 'blog':
	default:
		define('__TAB_BLOG__', true);
		$memberScopes = 'global|blog|sidebar|admin|editor|formatter|none';
		$_POST['visibility'] = 'blog';
		break;
}

$tabsClass = array();
$tabsClass[$_POST['visibility']] = true;

// get and set align type, scope type and status type.
$selectedSort = getBlogSetting('pluginListSortType', 'ascend');
$selectedScopes = explode('|', getBlogSetting("pluginListScopeType_{$_POST['visibility']}", $memberScopes));
$selectedStatus = explode('|', getBlogSetting("pluginListStatusType_{$_POST['visibility']}", 'activated|deactivated'));

// get all plugin list.
$plugins = array();
$pluginAttrs = array();

$dir = dir(ROOT . '/plugins/');
while (false !== ($plugin = $dir->read())) { // 이게 php.net에서 권장하는 올바른 디렉토리 읽는 법.
	$plugin = trim($plugin);
	$pluginInfo = getPluginInformation($plugin);
	if(empty($pluginInfo)) continue;
	if($pluginInfo['privilege'] == 'administrator' && getBlogId() != 1) continue;

	$acceptedPathCount = 0;
	$tempXMLPathCount = 0;

	if(empty($pluginInfo['scope'])) continue;
	
	foreach($pluginInfo['scope'] as $pluginScope) {
		if (in_array($pluginScope, $selectedScopes)) {
			$acceptedPathCount++;
		}
	}
	if($acceptedPathCount == 0) continue;

	$pluginDir = $pluginInfo['directory'];
	$pluginAttrs[$pluginDir] = $pluginInfo;
	$plugins[$pluginDir] = $pluginAttrs[$pluginDir]['title'];
}

// sort as value of $selectedSort.
if ($selectedSort == 'ascend') {
	asort($plugins);
} else {
	arsort($plugins);
}

$pluginKeys = array_keys($plugins);
?>
						<script type="text/javascript">
							//<![CDATA[
								var pluginInfo = new Array();
								
<?php
for ($i=0; $i<count($pluginKeys); $i++) {
	$pluginDir = $pluginKeys[$i];
	
	$width = $pluginAttrs[$pluginDir]['width']?$pluginAttrs[$pluginDir]['width']:500;
	$height = $pluginAttrs[$pluginDir]['height']?$pluginAttrs[$pluginDir]['height']:525;
?>
								pluginInfo['<?php echo $pluginDir;?>'] = new Array();
								pluginInfo['<?php echo $pluginDir;?>']['width'] = <?php echo $width;?>;
								pluginInfo['<?php echo $pluginDir;?>']['height'] = <?php echo $height;?>;
<?php
}
?>
								
								function togglePlugin(plugin, num, width, height, obj, force) {
									var currentIcon = document.getElementById('pluginIcon'+num);
									var currentSettingButton = document.getElementById('pluginSettingButton'+num);
									
									if (force == 'activate') {
										command = true;
									} else if (force == 'deactivate') {
										command = false;
									} else if (document.getElementById('pluginStatus'+num).value == 0) {
										command = true;
									} else {
										command = false;
									}
									
									if (command) {
										var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/plugin/activate");
										request.onSuccess = function() {												
											currentIcon.setAttribute('alt', '<?php echo _t('켜짐');?>');
											currentIcon.setAttribute('title', '<?php echo _t('이 플러그인은 사용중입니다. 클릭하시면 사용을 중지합니다.');?>');
											document.getElementById('pluginStatus'+num).value = 1;
											objLI = getParentByTagName("LI", obj);
											
											if (document.getElementById('activated-plugin').checked == false) {
												objLI.parentNode.removeChild(objLI);
											} else {
												objLI.className = objLI.className.replace('inactive', 'active');
											
												var icon = new Image();
												if (currentIcon.style.backgroundImage == "url(<?php echo $serviceURL . $adminSkinSetting['skin'];?>/image/icon_plugin_off.png)") {
													icon.src = '<?php echo $serviceURL . $adminSkinSetting['skin'];?>/image/icon_plugin_on.png';
												} else {
													icon.src = '<?php echo $serviceURL;?>/plugins/' + plugin + '/images/icon_plugin_on.png';
												}
												currentIcon.style.backgroundImage = "url('"+icon.src+"')";
											
												if (currentSettingButton.className == 'dimmed') {
													if (STD.isIE) {
														// tempLink.onclick, tempLink.setAttribute('onclick', ...)은 브라우저 호환성에 문제 있음. 따라서 브라우저를 detect하여 처리함.
														tempLink = document.createElement('<A onclick="getCurrentSetting(\''+plugin+'\', \'Y\', '+width+', '+height+', \'setting\'); return false;">');
													} else {
														tempLink = document.createElement('A');
														tempLink.setAttribute('onclick', "getCurrentSetting('"+plugin+"', 'Y', "+width+", "+height+", 'setting'); return false;");
													}
													tempLink.setAttribute('href', '#void');
													tempLink.innerHTML = '<?php echo _t('환경설정');?>';
													
													currentSettingButton.innerHTML = '';
													currentSettingButton.className = 'enabled';
													currentSettingButton.appendChild(tempLink);
												}
											}
										}
										request.onError = function() {
											alert("<?php echo _t('플러그인을 활성화하는데 실패했습니다.');?>");
										}
										request.send("name=" + plugin);
									} else {
										var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/plugin/deactivate");
										request.onSuccess = function() {
											currentIcon.setAttribute('alt', '<?php echo _t('꺼짐');?>');
											currentIcon.setAttribute('title', '<?php echo _t('이 플러그인은 사용중지 상태입니다. 클릭하시면 사용을 시작합니다.');?>');
											document.getElementById('pluginStatus'+num).value = 0;
											objLI = getParentByTagName("LI", obj);
											
											if (document.getElementById('deactivated-plugin').checked == false) {
												objLI.parentNode.removeChild(objLI);
											} else {
												objLI.className = objLI.className.replace('active', 'inactive');
											
												var icon = new Image();
												if (currentIcon.style.backgroundImage == "url(<?php echo $serviceURL . $adminSkinSetting['skin'];?>/image/icon_plugin_on.png)") {
													icon.src = '<?php echo $serviceURL . $adminSkinSetting['skin'];?>/image/icon_plugin_off.png';
												} else {
													icon.src = '<?php echo $serviceURL;?>/plugins/' + plugin + '/images/icon_plugin_off.png';
												}
												currentIcon.style.backgroundImage = "url('"+icon.src+"')";
											
												if (currentSettingButton.className == 'enabled') {
													currentSettingButton.innerHTML = '<?php echo _t('환경설정');?>';
													currentSettingButton.className = 'dimmed';
												}
											}
										}
										request.onError = function() {
											switch(parseInt(this.getText("/response/error"))) {
												case 1:
													alert("<?php echo _t('플러그인을 비활성화하지 못하였습니다.');?>");
													break;
												case 2:
													alert("<?php echo _t('모듈을 비활성화하지 못하였습니다.');?>\n\n<?php echo _t('텍스트큐브의 동작을 위하여 모듈이 필요한 부분마다 최소 하나 이상이 필요합니다.\n현재 해당 모듈이 하나밖에 존재하지 않기 때문에 이 모듈의 동작을 중지시킬 수 없습니다.');?>");
													break;
											}
										}
										request.send("name=" + plugin);
									}
								}
								
								function changeList(obj) {
									var currentTab = getObject('currentTab');
									
									var scope = new Array();
									var status = new Array();
									
									var scopeCount = 0;
									var statusCount = 0;
									
									for (var i=0; getObject('part-plugin-list').elements[i]; i++) {
										oElement = getObject('part-plugin-list').elements[i];
										if (oElement.name == 'scopeType' && oElement.checked == true) {
											scope[scopeCount] = oElement.value;
											scopeCount++;
										} else if (oElement.name == 'pluginStatus' && oElement.checked == true) {
											status[statusCount] = oElement.value;
											statusCount++;
										} else if (oElement.name == 'sortType' && oElement.checked == true) {
											var sort = oElement.value;
										}
									}
									
									if (scope.length == 0 || status.length == 0) {
										obj.checked = true;
										return false;
									}
									
									var request = new HTTPRequest("POST", "<?php echo $blogURL;?>/owner/plugin/saveScope");
									request.onSuccess = function() {
										window.location.reload(true);
									}
									
									request.onError = function() {
										alert("<?php echo _t('선택하신 조건을 적용할 수 없었습니다.');?>");
									}
									
									request.send("visibility=" + currentTab.value + "&scope=" + scope.join('|') + "&status=" + status.join('|') + "&sort=" + sort);
								}
								
								window.addEventListener("load", execLoadFunction, false);
								
								function execLoadFunction() {
									for (var i=0; getObject('part-plugin-list').elements[i]; i++) {
										oElement = getObject('part-plugin-list').elements[i];
										if ((oElement.name == 'plugin'))
											oElement.style.display = 'none';
									}
								}
								
								var currentSetting='';
								function getCurrentSetting( plugin, setYN, width, height, tab) {
									//if( "N" == setYN ) return ;
									if( '' != currentSetting ) currentSetting.close();
									window.open('<?php echo $blogURL;?>/owner/plugin/currentSetting/?Name='+plugin+'&Tab='+tab, 'CurrentSetting', 'width='+width+', height='+height+', scrollbars=1, status=0, resizable=1');	
									return;
								}								
							//]]>
						</script>
						
						<form id="part-plugin-list" class="part" method="post" action="<?php echo $blogURL."/owner/plugin";?>">
							<h2 class="caption"><span class="main-text"><?php echo _t('설치된 플러그인 목록입니다');?></span></h2>
							
							<div class="main-explain-box">
								<p class="explain"><?php echo _t('플러그인은 텍스트큐브의 기능을 확장합니다. 이 곳에서 설치된 플러그인의 사용 여부를 결정할 수 있습니다.');?></p>
							</div>
							
							<ul id="plugin-tabs-box" class="tabs-box">
								<li<?php echo isset($tabsClass['blog']) ? ' class="selected"' : NULL;?>><a href="<?php echo $blogURL;?>/owner/plugin"><?php echo _t('블로그/관리자 플러그인');?></a></li>
								<li<?php echo isset($tabsClass['center']) ? ' class="selected"' : NULL;?>><a href="<?php echo $blogURL;?>/owner/plugin?visibility=center"><?php echo _t('센터 플러그인');?></a></li>
								<li<?php echo isset($tabsClass['coverpage']) ? ' class="selected"' : NULL;?>><a href="<?php echo $blogURL;?>/owner/plugin?visibility=coverpage"><?php echo _t('표지 플러그인');?></a></li>
							</ul>
							
							<fieldset id="plugin-display-box">
								<legend><?php echo _t('표시할 플러그인의 종류를 선택하세요.');?></legend>
								
								<dl id="scope-line" class="line">
									<dt><?php echo _t('기능');?></dt>
									<dd id="scope-line-plugin">
										<ul>
<?php
if (defined('__TAB_BLOG__')) {
?>
											<li><input type="checkbox" class="checkbox" id="blog-scope" name="scopeType" value="blog" onclick="changeList(this)"<?php echo in_array('blog', $selectedScopes) ? ' checked="checked"' : '';?> /><label id="blog-scope-label" for="blog-scope"<?php echo in_array('blog', $selectedScopes) ? ' class="selected"' : '';?>><?php echo _t('블로그 플러그인');?></label></li>
											<li><input type="checkbox" class="checkbox" id="sidebar-scope" name="scopeType" value="sidebar" onclick="changeList(this)"<?php echo in_array('sidebar', $selectedScopes) ? ' checked="checked"' : '';?> /><label id="sidebar-scope-label" for="sidebar-scope"<?php echo in_array('sidebar', $selectedScopes) ? ' class="selected"' : '';?>><?php echo _t('사이드바 플러그인');?></label></li>
											<li><input type="checkbox" class="checkbox" id="admin-scope" name="scopeType" value="admin" onclick="changeList(this)"<?php echo in_array('admin', $selectedScopes) ? ' checked="checked"' : '';?> /><label id="admin-scope-label" for="admin-scope"<?php echo in_array('admin', $selectedScopes) ? ' class="selected"' : '';?>><?php echo _t('관리자 플러그인');?></label></li>
											<li><input type="checkbox" class="checkbox" id="common-scope" name="scopeType" value="global" onclick="changeList(this)"<?php echo in_array('global', $selectedScopes) ? ' checked="checked"' : '';?> /><label id="common-scope-label" for="common-scope"<?php echo in_array('global', $selectedScopes) ? ' class="selected"' : '';?>><?php echo _t('기타 플러그인');?></label></li>
											<li><input type="checkbox" class="checkbox" id="none-scope" name="scopeType" value="none" onclick="changeList(this)"<?php echo in_array('none', $selectedScopes) ? ' checked="checked"' : '';?> /><label id="none-scope-label" for="none-scope"<?php echo in_array('none', $selectedScopes) ? ' class="selected"' : '';?>><?php echo _t('분류 없음');?></label></li>
										</ul>
									</dd>
								</dl>
								<dl id="module-line" class="line">
									<dt><?php echo _t('모듈');?></dt>
									<dd>
										<ul>
											<li><input type="checkbox" class="checkbox" id="editor-scope" name="scopeType" value="editor" onclick="changeList(this)"<?php echo in_array('editor', $selectedScopes) ? ' checked="checked"' : '';?> /><label id="editor-scope-label" for="editor-scope"<?php echo in_array('editor', $selectedScopes) ? ' class="selected"' : '';?>><?php echo _t('에디터 모듈');?></label></li>
											<li><input type="checkbox" class="checkbox" id="formatter-scope" name="scopeType" value="formatter" onclick="changeList(this)"<?php echo in_array('formatter', $selectedScopes) ? ' checked="checked"' : '';?> /><label id="formatter-scope-label" for="formatter-scope"<?php echo in_array('formatter', $selectedScopes) ? ' class="selected"' : '';?>><?php echo _t('포매터 모듈');?></label></li>
<?php
} else if (defined('__TAB_CENTER__')) {
?>
											<li><input type="checkbox" class="checkbox" id="center-scope" name="scopeType" value="center" onclick="this.checked=true;" checked="checked" /><label id="center-scope-label" for="center-scope"<?php echo in_array('center', $selectedScopes) ? ' class="selected"' : '';?>><?php echo _t('센터 플러그인');?></label></li>
<?php
} else if (defined('__TAB_COVERPAGE__')) {
?>
											<li><input type="checkbox" class="checkbox" id="coverpage-scope" name="scopeType" value="coverpage" onclick="this.checked=true;" checked="checked" /><label id="coverpage-scope-label" for="coverpage-scope"<?php echo in_array('coverpage', $selectedScopes) ? ' class="selected"' : '';?>><?php echo _t('표지 플러그인');?></label></li>
<?php
}
?>
										</ul>
									</dd>
								</dl>
								
								<dl id="status-line" class="line">
									<dt><?php echo _t('상태');?></dt>
									<dd id="sorting-line-status">
										<label for="activated-plugin"><input type="checkbox" class="checkbox" id="activated-plugin" name="pluginStatus" value="activated" onclick="changeList(this)"<?php echo in_array('activated', $selectedStatus) ? ' checked="checked"' : '';?> /><?php echo defined('__TAB_ETC__') ? _t('사용중인 플러그인/모듈') : _t('사용중인 플러그인');?></label>
										<label for="deactivated-plugin"><input type="checkbox" class="checkbox" id="deactivated-plugin" name="pluginStatus" value="deactivated" onclick="changeList(this)"<?php echo in_array('deactivated', $selectedStatus) ? ' checked="checked"' : '';?> /><?php echo defined('__TAB_ETC__') ? _t('사용중이 아닌 플러그인/모듈') : _t('사용하지 않는 플러그인');?></label>
									</dd>
								</dl>
								
								<dl id="sorting-line" class="line">
									<dt class="hidden"><?php echo _t('정렬');?></dt>
									<dd id="sorting-line-align">
										<input type="radio" class="radio" id="ascend-sorting" name="sortType" value="ascend" onclick="changeList(this)"<?php echo $selectedSort == 'ascend' ? ' checked="checked"' : '';?> /><label for="ascend-sorting"><?php echo _t('오름차순');?></label>
										<input type="radio" class="radio" id="descend-sorting" name="sortType" value="descend" onclick="changeList(this)"<?php echo $selectedSort == 'descend' ? ' checked="checked"' : '';?> /><label for="descend-sorting"><?php echo _t('내림차순');?></label>
									</dd>
								</dl>
								
<?php
if (defined('__TAB_CENTER__') || defined('__TAB_COVERPAGE__')) {
	$text = defined('__TAB_CENTER__') ? _t('센터로 바로 가기') : _t('표지 설정으로 바로가기');
	$link = defined('__TAB_CENTER__') ? $blogURL . '/owner/center/dashboard' : $blogURL . '/owner/center/coverpage';
} else {
	$text = _t('사이드바로 바로 가기');
	$link = $blogURL . '/owner/skin/sidebar';
}
?>
								<dl id="direct-link-line" class="line">
									<dt><?php echo _t('메인 화면으로 이동');?></dt>
									<dd>
										<a class="button" href="<?php echo $link;?>"><?php echo $text;?></a>
									</dd>
								</dl>
							</fieldset>
							
							<div id="temp-box">
								<ul class="data-inbox">
<?php
list($currentTextcubeVersion) = explode(' ', TEXTCUBE_VERSION, 2);

for ($i=0; $i<count($pluginKeys); $i++) {
	$pluginDir = $pluginKeys[$i];
	
	$link = $pluginAttrs[$pluginDir]['link'];
	$title = $pluginAttrs[$pluginDir]['title'];
	$version = $pluginAttrs[$pluginDir]['version'];
	$requirements = $currentTextcubeVersion >= $pluginAttrs[$pluginDir]['requirements'] ? true : false;
	$description = $pluginAttrs[$pluginDir]['description'];
	$authorLink = $pluginAttrs[$pluginDir]['authorLink'];
	$author = $pluginAttrs[$pluginDir]['author'];
	$config = $pluginAttrs[$pluginDir]['config'] ? 'Y':'N';
	$width = $pluginAttrs[$pluginDir]['width'] ? $pluginAttrs[$pluginDir]['width'] : 500;
	$height = $pluginAttrs[$pluginDir]['height'] ? $pluginAttrs[$pluginDir]['height'] : 525;
	$active = in_array($pluginDir, $activePlugins);
	
	if ($active == true && !in_array('activated', $selectedStatus))
		continue;
	else if ($active == false && !in_array('deactivated', $selectedStatus))
		continue;
	
	$className = $active ? 'active-class' : 'inactive-class';
	$className .= $requirements ? NULL : ' disabled-class';
	$className .= $i == (count($pluginKeys) - 1) ? ' last-item' : NULL;
?>
									<li class="<?php echo $className;?>">
<?php
	if ($requirements == false) {
?>
										<div class="plugin-box">
											<div class="plugin-icon plugin-disabled-icon" style="background-image: url('<?php 
		echo $serviceURL . 
			(file_exists(ROOT . "/plugins/{$pluginDir}/images/icon_plugin_off.png") ?
				"/plugins/{$pluginDir}/images/icon_plugin_off.png" :
				$adminSkinSetting['skin'] . "/image/icon_plugin_off.png")	;?>');">
												<img id="pluginStatusIcon<?php echo $i;?>" src="<?php echo $serviceURL . $adminSkinSetting['skin'] . "/image/spacer.gif";?>" width="28" height="29" alt="<?php echo _t('꺼짐');?>" title="<?php echo _t('이 플러그인은 현재 텍스트큐브와 호환되지 않습니다. 플러그인의 업데이트가 필요합니다.');?>" />
											</div>
<?php
	} else if ($active) {
?>
										<div class="plugin-box">
											<div id="pluginIcon<?php echo $i;?>" class="plugin-icon" style="background-image: url('<?php 
		echo $serviceURL . 
			(file_exists(ROOT . "/plugins/{$pluginDir}/images/icon_plugin_on.png") ? 
				"/plugins/{$pluginDir}/images/icon_plugin_on.png" :
				$adminSkinSetting['skin'] . "/image/icon_plugin_on.png");?>');" onclick="togglePlugin('<?php echo $pluginDir;?>',<?php echo $i;?>,'<?php echo $width;?>','<?php echo $height;?>', this, null); return false;">
												<img id="pluginStatusIcon<?php echo $i;?>" src="<?php echo $serviceURL . $adminSkinSetting['skin'] . "/image/spacer.gif";?>" width="28" height="29" alt="<?php echo _t('켜짐');?>" title="<?php echo _t('이 플러그인은 사용중입니다. 클릭하시면 사용을 중지합니다.');?>" />
											</div>
											<input type="hidden" id="pluginStatus<?php echo $i;?>" value="1" />
<?php
	} else {
?>
										<div class="plugin-box">
										<div id="pluginIcon<?php echo $i;?>" class="plugin-icon" style="background-image: url('<?php 
		echo $serviceURL . 
			(file_exists(ROOT . "/plugins/{$pluginDir}/images/icon_plugin_off.png") ? 
				"/plugins/{$pluginDir}/images/icon_plugin_off.png" : 
				$adminSkinSetting['skin'] . "/image/icon_plugin_off.png");?>');" onclick="togglePlugin('<?php echo $pluginDir;?>',<?php echo $i;?>,'<?php echo $width;?>','<?php echo $height;?>', this, null); return false;">
												<img id="pluginStatusIcon<?php echo $i;?>" src="<?php echo $serviceURL . $adminSkinSetting['skin'] . "/image/spacer.gif";?>" width="28" height="29" alt="<?php echo _t('꺼짐');?>" title="<?php echo _t('이 플러그인은 사용 중지 상태입니다. 클릭하시면 사용을 시작합니다.');?>" />
											</div>
											<input type="hidden" id="pluginStatus<?php echo $i;?>" value="0" />
<?php
	}
?>
										</div>
										<div class="summary">
											<div class="plugin-title">
<?php
	if ($requirements == false) {
?>
												<input type="checkbox" class="input-checkbox" name="plugin" title="<?php echo _t('이 플러그인은 현재 텍스트큐브와 호환되지 않습니다. 플러그인의 업데이트가 필요합니다.');?>" disabled="disabled" />
<?php
	} else if ($active) {
?>
												<input type="checkbox" class="input-checkbox" name="plugin" value="<?php echo $pluginDir;?>" title="<?php echo _t('이 플러그인은 사용중입니다. 클릭하시면 사용을 중지합니다.');?>" checked="checked" />
<?php
	} else {
?>
												<input type="checkbox" class="input-checkbox" name="plugin" value="<?php echo $pluginDir;?>" title="<?php echo _t('이 플러그인은 사용 중지 상태입니다. 클릭하시면 사용을 시작합니다.');?>" />
<?php
	}
?>
												<?php echo ($link ? "<a href=\"" . htmlspecialchars($link) . "\" title=\"".htmlspecialchars($title)." - " . _t('판번호') . " {$version}\">" . htmlspecialchars(UTF8::lessenAsEm($title, 20)) . '</a>' : "<span title=\"".htmlspecialchars($title)." - " . _t('판번호') . " {$version}\">" . htmlspecialchars(UTF8::lessenAsEm($title, 20)) . '</span>');?>
											</div>
											<div class="plugin-buttons">
<?php
	if ($requirements == false) {
?>
												<a href="#void" onclick="getCurrentSetting('<?php echo $pluginDir;?>','<?php echo $config;?>','<?php echo $width;?>','<?php echo $height;?>', 'about'); return false;"><?php echo _t('사용 불가능한 판번호');?></a>
<?php
	} else {
?>
												<a href="#void" onclick="getCurrentSetting('<?php echo $pluginDir;?>','<?php echo $config;?>','<?php echo $width;?>','<?php echo $height;?>', 'about'); return false;"><?php echo _t('자세히 보기');?></a> <span class="divider">|</span>
<?php
		if ($config=='Y') {
			if ($active) {
?>
												<span id="pluginSettingButton<?php echo $i;?>" class="enabled"><a href="#void" onclick="getCurrentSetting('<?php echo $pluginDir;?>','<?php echo $config;?>','<?php echo $width;?>','<?php echo $height;?>', 'setting'); return false;"><?php echo _t('환경설정');?></a></span>
<?php
			} else {
?>
												<span id="pluginSettingButton<?php echo $i;?>" class="dimmed"><?php echo _t('환경설정');?></span>
<?php
			}
		} else {
?>
												<span id="pluginSettingButton<?php echo $i;?>" class="disabled"><?php echo _t('환경설정');?></span>
<?php
		}
	}
?>
											</div>
										</div>
									</li>
<?php
}
?>
								</ul>
								
								<div class="clear"></div>
							</div>
							
							<input type="hidden" id="currentTab" name="currentTab" value="<?php echo $_POST['visibility'];?>" />
						</form>
						
						<hr class="hidden" />
						
						<div id="part-plugin-more" class="part">
							<h2 class="caption"><span class="main-text"><?php echo _t('플러그인을 구하려면');?></span></h2>
							
<?php
$linkString = '<a href="http://www.textcube.org/plugin" onclick="window.open(this.href); return false;" title="' . _t('플러그인 업로드 게시판으로 연결합니다.') . '">' . _t('플러그인 업로드 게시판'). '</a>';
$tempString = _f('텍스트큐브 홈페이지의 %1을 방문하시면 다양한 플러그인을 받을 수 있습니다. 받은 플러그인 파일을 텍스트큐브의 plugin 디렉토리(폴더)로 업로드하면 설치가 완료됩니다. 업로드 후, 이 페이지에서 해당 플러그인을 사용중으로 전환하여 사용을 시작할 수 있습니다.',$linkString);
?>
							<div class="main-explain-box">
								<p class="explain"><?php echo $tempString;?></p>
							</div>
						</div>
<?php
require ROOT . '/lib/piece/owner/footer.php';
?>
