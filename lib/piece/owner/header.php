<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

// Automatic menu location routine.
$blogMenu = array();
$urlFragments = preg_split('/\//',ltrim($suri['directive'],'/'));
if(isset($urlFragments[1])) $blogMenu['topMenu'] = $urlFragments[1];
if(isset($urlFragments[2])) $blogMenu['contentMenu'] = $urlFragments[2];
else $blogMenu['contentMenu'] = $urlFragments[1];
if(isset($urlFragments[3])) $blogMenu['contentMenu'] .= $urlFragments[3];

// If admin.panel plugin, set the menu location again.
if(isset($urlFragments[2])&&strncmp($urlFragments[2],'adminMenu',9) == 0) {
	$plugin = isset($_GET['name']) ? $_GET['name'] : '';
	$pluginDir = strtok($plugin,'/');
	$blogMenu['topMenu'] = $adminMenuMappings[$plugin]['topMenu'];
}

if(Acl::check('group.administrators')) {
	$blogTopMenuItem = array(
		array('menu'=>'center','title'=>_t('센터'),'link'=>'/owner/center/dashboard'),
		array('menu'=>'entry','title'=>_t('글'),'link'=>'/owner/entry'),
		array('menu'=>'communication','title'=>_t('소통'),'link'=>'/owner/communication/comment'),
//		array('menu'=>'reader','title'=>_t('리더'),'link'=>'/owner/reader'),
		array('menu'=>'skin','title'=>_t('꾸미기'),'link'=>'/owner/skin'),
		array('menu'=>'plugin','title'=>_t('플러그인'),'link'=>'/owner/plugin'),	
		array('menu'=>'setting','title'=>_t('설정'),'link'=>'/owner/setting/blog')
		);
} else {
	$blogTopMenuItem = array(
		array('menu'=>'center','title'=>_t('센터'),'link'=>'/owner/center/dashboard'),
		array('menu'=>'entry','title'=>_t('글'),'link'=>'/owner/entry'),
//		array('menu'=>'reader','title'=>_t('리더'),'link'=>'/owner/reader'),
		array('menu'=>'setting','title'=>_t('설정'),'link'=>'/owner/setting/account')
		);
}
//if($service['reader'] === false) {
//	if(Acl::check('group.administrators')) array_splice($blogTopMenuItem,3,1);
//	else array_splice($blogTopMenuItem,2,1);
//}

if(Acl::check('group.creators')) { 
	array_push($blogTopMenuItem, array('menu'=>'control','title'=>_t('서비스관리'),'link'=>'/owner/control/blog'));
}
switch($blogMenu['topMenu']) {
	case 'center':
		$blogMenu['title'] = _t('센터');
		$blogMenu['loadCSS'] = array('center');
		$blogMenu['loadCSSIE6'] = array('center');
		$blogMenu['loadCSSIE7'] = array('center');
		break;
	case 'entry':
		$blogMenu['title'] = _t('글');
		if ($blogMenu['contentMenu'] == 'post' || $blogMenu['contentMenu'] == 'edit') {
			$blogMenu['loadCSS'] = array('post','editor');
			$blogMenu['loadCSSIE6'] = array('post','editor');
			$blogMenu['loadCSSIE7'] = array('post','editor');
		} else {
			$blogMenu['loadCSS'] = array('post');
			$blogMenu['loadCSSIE6'] = array('post');
			$blogMenu['loadCSSIE7'] = array('post');
		}
		
		break;
	case 'communication':
		$blogMenu['title'] = _t('소통');
		$blogMenu['loadCSS'] = array('communication');
		$blogMenu['loadCSSIE6'] = array('communication');
		$blogMenu['loadCSSIE7'] = array('communication');
		break;
	case 'skin':
		$blogMenu['title'] = _t('꾸미기');
		$blogMenu['loadCSS'] = array('skin');
		$blogMenu['loadCSSIE6'] = array('skin');
		$blogMenu['loadCSSIE7'] = array('skin');
		break;
	case 'plugin':
		$blogMenu['title'] = _t('플러그인');
		$blogMenu['loadCSS'] = array('plugin');
		$blogMenu['loadCSSIE6'] = array('plugin');
		$blogMenu['loadCSSIE7'] = array('plugin');
		break;
	case 'setting':
	case 'data':
		$blogMenu['title'] = _t('설정');
		$blogMenu['loadCSS'] = array('setting');
		$blogMenu['loadCSSIE6'] = array('setting');
		$blogMenu['loadCSSIE7'] = array('setting');
		break;
	case 'reader':
		$blogMenu['title'] = _t('리더');
		$blogMenu['loadCSS'] = array('reader');
		$blogMenu['loadCSSIE6'] = array('reader');
		$blogMenu['loadCSSIE7'] = array('reader');
		break;
	case 'control':
		$blogMenu['title'] = _t('서비스');
		$blogMenu['loadCSS'] = array('control');
		break;
}
// exception for reader CSS. RSS reader will keep as an independent module.
if(defined('__TEXTCUBE_READER_SUBMENU__') && $blogMenu['contentMenu'] == 'reader') {
	$blogMenu['topMenu'] = 'communication';
	$blogMenu['title'] = _t('소통');
	$blogMenu['loadCSS'] = array('reader');
	$blogMenu['loadCSSIE6'] = array('reader');
	$blogMenu['loadCSSIE7'] = array('reader');
}
// mapping data management to setting
if(isset($blogMenu['topMenu']) && $blogMenu['topMenu']=='data') $blogMenu['topMenu'] = 'setting';
$pluginListForCSS = array();
if ($blogMenu['topMenu'] == 'center' && $blogMenu['contentMenu'] == 'dashboard') {
	if (isset($eventMappings['AddPostEditorToolbox'])) {
		foreach ($centerMappings as $tempPlugin) {
			array_push($pluginListForCSS, $tempPlugin['plugin']);
		}
	}
} else if ($blogMenu['topMenu'] == 'entry' && ($blogMenu['contentMenu'] == 'post' || $blogMenu['contentMenu'] == 'edit')) {
	if (isset($eventMappings['AddPostEditorToolbox'])) {
		foreach ($eventMappings['AddPostEditorToolbox'] as $tempPlugin) {
			array_push($pluginListForCSS, $tempPlugin['plugin']);
		}
	}
} else if (isset($pluginDir)) {
	array_push($pluginListForCSS, $pluginDir);
}
unset($tempPlugin);
/***** submenu generation part. *****/
if(isset($blogMenu['topMenu'])) {
	if(Acl::check('group.administrators')) {
		$blogContentMenuItem['center'] = array(
			array('menu'=>'dashboard','title'=>_t('조각보'),'link'=>'/owner/center/dashboard'),
		);
	} else{
		$blogContentMenuItem['center'] = array(
			array('menu'=>'dashboard','title'=>_t('조각보'),'link'=>'/owner/center/dashboard')
		);
	}
	if(Acl::check('group.editors')) {
		$blogContentMenuItem['entry'] = array(
			array('menu'=>'post','title'=>_t('글쓰기'),'link'=>'/owner/entry/post'),
			array('menu'=>'entry','title'=>_t('글 목록'),'link'=>'/owner/entry'),
			array('menu'=>'category','title'=>_t('분류 관리'),'link'=>'/owner/entry/category')
		);
	} else {
		$blogContentMenuItem['entry'] = array(
			array('menu'=>'post','title'=>_t('글쓰기'),'link'=>'/owner/entry/post'),
			array('menu'=>'entry','title'=>_t('글 목록'),'link'=>'/owner/entry')
		);
	}
	if(Acl::check('group.administrators')) {
		$blogContentMenuItem['communication'] = array(
			array('menu'=>'comment','title'=>_t('소통 기록'),'link'=>'/owner/communication/comment'),
			array('menu'=>'openid','title'=>_t('오픈아이디 목록'),'link'=>'/owner/communication/openid'),
			array('menu'=>'link','title'=>_t('링크'),'link'=>'/owner/communication/link')
		);
		if($service['reader'] == true) array_push($blogContentMenuItem['communication'],array('menu'=>'reader','title'=>_t('바깥 글 읽기'),'link'=>'/owner/communication/reader'));
	} else {
		$blogContentMenuItem['communication'] = array(
			array('menu'=>'comment','title'=>_t('소통 기록'),'link'=>'/owner/communication/comment'),
			array('menu'=>'trash','title'=>_t('휴지통'),'link'=>'/owner/communication/trash/comment')
		);
		if($service['reader'] == true) array_push($blogContentMenuItem,array('menu'=>'reader','title'=>_t('바깥 글 읽기'),'link'=>'/owner/communication/reader'));
	}
	if(Acl::check('group.administrators')) {
		$blogContentMenuItem['skin'] = array(
			array('menu'=>'skin','title'=>_t('스킨 선택'),'link'=>'/owner/skin'),
			array('menu'=>'edit','title'=>_t('스킨 편집'),'link'=>'/owner/skin/edit'),
			array('menu'=>'setting','title'=>_t('스킨 상세 설정'),'link'=>'/owner/skin/setting'),
			array('menu'=>'widget','title'=>_t('위젯'),'link'=>'/owner/skin/sidebar'),
			array('menu'=>'adminSkin','title'=>_t('관리자 패널 스킨 선택'),'link'=>'/owner/skin/adminSkin')
		);
	}
	if(Acl::check('group.administrators')) {
		$blogContentMenuItem['plugin'] = array(
			array('menu'=>'plugin','title'=>_t('플러그인 목록'),'link'=>'/owner/plugin')
		);
		if(Acl::check('group.creators')) array_push($blogContentMenuItem, array('menu'=>'tableSetting','title'=>_t('플러그인 데이터 관리'),'link'=>'/owner/plugin/tableSetting'));
	}
	if(Acl::check('group.administrators')) {
		$blogContentMenuItem['setting'] = array(
			array('menu'=>'blog','title'=>_t('블로그'),'link'=>'/owner/setting/blog'),
			array('menu'=>'entry','title'=>_t('글 작성'),'link'=>'/owner/setting/entry'),
			array('menu'=>'account','title'=>_t('개인 정보'),'link'=>'/owner/setting/account'),
			array('menu'=>'teamblog','title'=>_t('필진 목록'),'link'=>'/owner/setting/teamblog'),
			array('menu'=>'filter','title'=>_t('스팸 필터'),'link'=>'/owner/setting/filter'),
			array('menu'=>'data','title'=>_t('데이터 관리'),'link'=>'/owner/data')
		);
	} else {
		$blogContentMenuItem['setting'] = array(
			array('menu'=>'account','title'=>_t('개인 정보'),'link'=>'/owner/setting/account')
		);
	}
	if(Acl::check('group.creators')) {
		$blogContentMenuItem['control'] = array(
			array('menu'=>'blog','title'=>_t('블로그'),'link'=>'/owner/control/blog'),
			array('menu'=>'user','title'=>_t('사용자'),'link'=>'/owner/control/user'),
			array('menu'=>'server','title'=>_t('서버'),'link'=>'/owner/control/server'),
			array('menu'=>'system','title'=>_t('시스템 정보'),'link'=>'/owner/control/system')
		);
	}
}

if( empty($blogContentMenuItem) ) {
	echo _t('접근권한이 없습니다');
	exit;
}

foreach($adminMenuMappings as $path => $pluginAdminMenuitem) {
	if(count($blogContentMenuItem[$pluginAdminMenuitem['topMenu']]) < $pluginAdminMenuitem['contentMenuOrder'] 
	  || $pluginAdminMenuitem['contentMenuOrder'] < 1)
		$pluginAdminMenuitem['contentMenuOrder'] = count($blogContentMenuItem[$pluginAdminMenuitem['topMenu']]);
	array_splice($blogContentMenuItem[$pluginAdminMenuitem['topMenu']], $pluginAdminMenuitem['contentMenuOrder'], 0, 
		array(array('menu'=>'adminMenu?name='.$path,
		'title'=>$pluginAdminMenuitem['title'],
		'link'=>'/owner/plugin/adminMenu?name='.$path))
	);
}
$blogContentMenuItem['center'] = array_merge($blogContentMenuItem['center'] , array(array('menu'=>'about','title'=>_t('텍스트큐브는'),'link'=>'/owner/center/about')));
// Adds 'about' panel at the last part of center panel.
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo (isset($blog['language']) ? $blog['language'] : "ko");?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo htmlspecialchars($blog['title']);?> &gt; <?php echo $blogMenu['title'];?></title>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $service['path'].$adminSkinSetting['skin'];?>/basic.css" />
<?php
// common CSS.
foreach($blogMenu['loadCSS'] as $loadCSS) {
?>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $service['path'].$adminSkinSetting['skin'];?>/<?php echo $loadCSS;?>.css" />
<?php
}

foreach ($pluginListForCSS as $tempPluginDir) {
	if (isset($tempPluginDir) && file_exists(ROOT . "/plugins/$tempPluginDir/plugin-main.css")) {
?>
	<link rel="stylesheet" type="text/css" href="<?php echo $service['path'];?>/plugins/<?php echo $tempPluginDir;?>/plugin-main.css" />
<?php
	}
}
?>
	<!--[if lte IE 6]>
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $service['path'].$adminSkinSetting['skin'];?>/basic.ie.css" />
<?php
// CSS for Internet Explorer 6
foreach($blogMenu['loadCSSIE6'] as $loadCSS) {
?>
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $service['path'].$adminSkinSetting['skin'];?>/<?php echo $loadCSS;?>.ie.css" />
<?php
}

foreach ($pluginListForCSS as $tempPluginDir) {
	if (isset($tempPluginDir) && file_exists(ROOT . "/plugins/$tempPluginDir/plugin-main.ie.css")) {
?>
	<link rel="stylesheet" type="text/css" href="<?php echo $service['path'];?>/plugins/<?php echo $tempPluginDir;?>/plugin-main.ie.css" />
<?php
	}
}
?>	
	<![endif]-->
	<!--[if IE 7]>
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $service['path'].$adminSkinSetting['skin'];?>/basic.ie7.css" />
<?php
// CSS for Internet Explorer 7
foreach($blogMenu['loadCSSIE7'] as $loadCSS) {
?>
		<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $service['path'].$adminSkinSetting['skin'];?>/<?php echo $loadCSS;?>.ie7.css" />
<?php
}

foreach ($pluginListForCSS as $tempPluginDir) {
	if (isset($tempPluginDir) && file_exists(ROOT . "/plugins/$tempPluginDir/plugin-main.ie7.css")) {
?>
	<link rel="stylesheet" type="text/css" href="<?php echo $service['path'];?>/plugins/<?php echo $tempPluginDir;?>/plugin-main.ie7.css" />
<?php
	}
}

unset($pluginListForCSS);
unset($tempPluginDir);
?>
	<![endif]-->
	<script type="text/javascript">
		//<![CDATA[
			var servicePath = "<?php echo $service['path'];?>";
			var blogURL = "<?php echo $blogURL;?>";
			var adminSkin = "<?php echo $adminSkinSetting['skin'];?>";
<?php
if (file_exists(ROOT.$adminSkinSetting['editorTemplate'])) {
?>
			var editorCSS = "<?php echo $adminSkinSetting['editorTemplate'];?>";
<?php
} else {
?>
			var editorCSS = "/style/default-wysiwyg.css";
<?php
}

include ROOT . '/language/messages.php';
?>
		//]]>
	</script>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/byTextcube.js"></script>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/EAF4.js"></script>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/common2.js"></script>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/owner.js"></script>
<?php
if(!in_array($blogMenu['contentMenu'],array('post','edit'))) {
?>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/mootools10.js"></script>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/moodalbox/moodalbox.js"></script>
	<link rel="stylesheet" href="<?php echo $service['path'];?>/style/helper/moodalbox.css" type="text/css" media="screen" />
<?php
}
?>
<?php
/*if($service['helperPanel'] == true) {
?>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/dojo/dojo.js"></script>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/helpdialog.js"></script>
<?php
}*/
if(($service['interface'] == 'simple') || ($service['effect'])) {
	if(!in_array($blogMenu['contentMenu'],array('post','edit'))) {
?>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/mootools.js"></script>
<?php
	}
}
if( isset($service['admin_script']) ) {
	if( is_array($service['admin_script']) ) {
		foreach( $service['admin_script'] as $src ) {
?>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/<?php echo $src;?>"></script>
<?php
		}
	} else {
?>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/<?php echo $service['admin_script'];?>"></script>
<?php
	}
}
if($blogMenu['topMenu']=='entry' && in_array($blogMenu['contentMenu'],array('post','edit'))) {
?>
	<script type="text/javascript" src="<?php echo $service['path'];?>/script/editor3.js"></script>
<?php
}
echo fireEvent('ShowAdminHeader', '');
?>
</head>
<body id="body-<?php echo $blogMenu['topMenu'];?>">
	<div id="temp-wrap">
		<div id="all-wrap">
			<div id="layout-header">
				<h1><?php echo _t('텍스트큐브 관리 페이지');?></h1>
				
				<div id="main-description-box">
					<ul id="main-description">
<?php
$writer = POD::queryCell("SELECT name FROM {$database['prefix']}Users WHERE userid = ".getUserId());
requireComponent('Textcube.Core');
?>
						<li id="description-blogger"><span class="text"><?php echo _f('환영합니다. <em>%1</em>님.', htmlspecialchars($writer));?></span></li><?php
						if ( 'single' != $service['type'] ) {
							?>
						<li id="description-teamblog"><label for="teamblog"><?php echo _t('현재 블로그');?></label>
<?php echo User::changeBlog();?>
						</li>
<?php } ?>
						<li id="description-blog"><a href="<?php echo $blogURL;?>/" title="<?php echo _t('블로그 메인으로 이동합니다.');?>"><span class="text"><?php echo _t('블로그로 이동');?></span></a></li>
						<li id="description-logout"><a href="<?php echo $blogURL;?>/logout" title="<?php echo _t('로그아웃하고 블로그 메인으로 이동합니다.');?>"><span class="text"><?php echo _t('로그아웃');?></span></a></li>
					</ul>
				</div>
				
				<hr class="hidden" />
				
				<h2><?php echo _t('메인메뉴');?></h2>
				
				<div id="main-menu-box">
					<ul id="main-menu">
						<li id="menu-textcube"><a href="<?php echo TEXTCUBE_HOMEPAGE;?>" onclick="window.open(this.href); return false;" title="<?php echo _t('텍스트큐브 홈페이지로 이동합니다.');?>"><span class="text"><?php echo _t('텍스트큐브 홈페이지');?></span></a></li>
						
<?php
foreach($blogTopMenuItem as $menuItem) {
?>
						<li id="menu-<?php echo $menuItem['menu'];?>"<?php echo $menuItem['menu']==$blogMenu['topMenu'] ? ' class="selected"' : '';?> onmouseover="previewSubmenu('<?php echo $menuItem['menu'];?>')">
							<a href="<?php echo $blogURL.$menuItem['link'];?>"><span><?php echo $menuItem['title'];?></span></a>
							<ul id="submenu-<?php echo $menuItem['menu'];?>" class="sub-menu">
<?php
	$firstChildClass = ' firstChild';
	if (isset($_POST['category'])) $currentCategory = $_POST['category'];
	else if (isset($_GET['category'])) $currentCategory = $_GET['category'];
	else $currentCategory = null;
	if(in_array($blogMenu['contentMenu'],array('notify','trackback','trashcomment','trashtrackback')))
		$blogMenu['contentMenu'] = 'comment';
	else if(in_array($blogMenu['contentMenu'],array('linkadd','linkedit','linkcategoryEdit','xfn')))
		$blogMenu['contentMenu'] = 'link';
	else if(in_array($blogMenu['contentMenu'],array('coverpage','sidebar')))
		$blogMenu['contentMenu'] = 'widget';
		
	foreach($blogContentMenuItem[$menuItem['menu']] as $contentMenuItem) { 
		$PostIdStr = null;
		if(strstr($contentMenuItem['menu'], 'adminMenu?name=') !== false) {
			$pluginMenuValue = explode('/',substr($contentMenuItem['menu'], 15));
			$PostIdStr = $pluginMenuValue[0];
		} else {
			$PostIdStr = $contentMenuItem['menu'];
		}
?>
								<li id="sub-menu-<?php echo $PostIdStr;?>"<?php echo 
	(($blogMenu['contentMenu'] == $contentMenuItem['menu'] || 
	(isset($_GET['name']) && ('adminMenu?name='.$_GET['name'] == $contentMenuItem['menu'])) ||
	($contentMenuItem['menu'] == 'add' && strpos($blogMenu['contentMenu'],'add') !== false) ||
	($contentMenuItem['menu'] == 'blog' && strpos($blogMenu['contentMenu'],'blog') !== false && strpos($blogMenu['contentMenu'],'teamblog') === false) ||
	($contentMenuItem['menu'] == 'user' && strpos($blogMenu['contentMenu'],'user') !== false) ||
	($blogMenu['contentMenu'] == 'edit' && $contentMenuItem['menu'] == 'post')) ? 
		" class=\"selected{$firstChildClass}\"" : ($firstChildClass ? " class=\"$firstChildClass\"" : ''));?>><a href="<?php 
						echo $blogURL.
							$contentMenuItem['link'].
							($contentMenuItem['menu'] == 'post' && isset($currentCategory) ? '?category='.$currentCategory : '');
						?>"><span class="text"><?php echo $contentMenuItem['title'];?></span></a></li>
<?php
		$firstChildClass = null;
	}
?>
							</ul>
						</li>
<?php
}
?>
					</ul>
				</div>
			</div>
			
			<hr class="hidden" />
<?php
/********** Submenu part. ***********/
if(!defined('__TEXTCUBE_READER_SUBMENU__')) {
?>
			<div id="layout-body">
<?php
}
?>
				<h2><?php echo isset($blogMenu['title']) ? _f('서브메뉴 : %1', $blogMenu['title']) : _t('서브메뉴');?></h2>

<?php
if(isset($blogContentMenuItem[$blogMenu['topMenu']])) {
?>
				<div id="sub-menu-box">
					<ul id="sub-menu">
<?php
	$firstChildClass = ' firstChild';
	$submenuURL = null;
	foreach($blogContentMenuItem[$blogMenu['topMenu']] as $contentMenuItem) { 
		$PostIdStr = null;
		if(strstr($contentMenuItem['menu'], 'adminMenu?name=') !== false) {
			$pluginMenuValue = explode('/',substr($contentMenuItem['menu'], 15));
			$PostIdStr = $pluginMenuValue[0];
			if(($blogMenu['contentMenu'] == $contentMenuItem['menu'] || (isset($_GET['name']) && ('adminMenu?name='.$_GET['name'] == $contentMenuItem['menu'])) || ($contentMenuItem['menu'] == 'trash' && strpos($blogMenu['contentMenu'],'trash') !== false))) {
				$submenuURL = $pluginMenuValue[0];
			}
		} else {
			$PostIdStr = $contentMenuItem['menu'];
			if(($blogMenu['contentMenu'] == $contentMenuItem['menu'] 
				|| (isset($_GET['name']) && ('adminMenu?name='.$_GET['name'] == $contentMenuItem['menu'])) 
				|| (in_array($contentMenuItem['menu'],array('blog','user')) && strpos($blogMenu['contentMenu'],'detail') !== false)
				)) {
				$submenuURL = $blogMenu['contentMenu'];
			}
		}
?>
						<li id="sub-menu-<?php echo $PostIdStr;?>"<?php echo 
						(($blogMenu['contentMenu'] == $contentMenuItem['menu'] || 
							(isset($_GET['name']) && ('adminMenu?name='.$_GET['name'] == $contentMenuItem['menu'])) ||
							($contentMenuItem['menu'] == 'add' && strpos($blogMenu['contentMenu'],'add') !== false) ||
							($contentMenuItem['menu'] == 'blog' && strpos($blogMenu['contentMenu'],'blog') !== false && strpos($blogMenu['contentMenu'],'teamblog') === false) ||
							($contentMenuItem['menu'] == 'user' && strpos($blogMenu['contentMenu'],'user') !== false) ||
							($blogMenu['contentMenu'] == 'edit' && $contentMenuItem['menu'] == 'post')) ? " class=\"selected{$firstChildClass}\"" : ($firstChildClass ? " class=\"$firstChildClass\"" : ''));?>><a href="<?php 
						echo $blogURL.
							$contentMenuItem['link'].
							($contentMenuItem['menu'] == 'post' && isset($currentCategory) ? '?category='.$currentCategory : '');
						?>"><span class="text"><?php echo $contentMenuItem['title'];?></span></a></li>
<?php
		$firstChildClass = null;
	}
	
	$helpURL = $blogMenu['topMenu'].(isset($blogMenu['contentMenu']) ? '/'.$submenuURL : '');
?>
					</ul>
					<ul id="helper">
						<li id="sub-menu-helper"><a href="<?php echo getHelpURL($helpURL);?>" onclick="window.open(this.href); return false;"><span class="text"><?php echo _t('도우미');?></span></a></li>
					</ul>
				</div>
<?php
}
if(!defined('__TEXTCUBE_READER_SUBMENU__')) {
?>
				<hr class="hidden" />
				
				<div id="pseudo-box" onmouseover="revertSubmenu();">
					<div id="data-outbox">
<?php
}
?>
