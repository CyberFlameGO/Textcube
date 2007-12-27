<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'REQUEST' => array(
		'sidebarNumber' => array('int'),
		'modulePos' => array('int'),
		'targetSidebarNumber' => array('int'),
		'targetPos' => array('int'),
		'viewMode' => array('string', 'default' => '')
	)
);

require ROOT . '/lib/includeForBlogOwner.php';
requireLibrary('blog.skin');
requireModel("blog.sidebar");
requireStrictRoute();

$skin = new Skin($skinSetting['skin']);
$sidebarCount = count($skin->sidebarBasicModules);
$sidebarOrder = getSidebarModuleOrderData($sidebarCount);

if ($_REQUEST['targetPos'] < 0 || $_REQUEST['targetPos'] > count($sidebarOrder[$_REQUEST['sidebarNumber']]) || $_REQUEST['targetSidebarNumber'] < 0 || $_REQUEST['targetSidebarNumber'] >= count($sidebarOrder)) {
	if ($_SERVER['REQUEST_METHOD'] != 'POST')
		header('Location: '. $blogURL . '/owner/skin/sidebar' . $_REQUEST['viewMode']);
	else
		respondResultPage(-1);
} else {
	if (($_REQUEST['sidebarNumber'] == $_REQUEST['targetSidebarNumber'])
		&& ($_REQUEST['modulePos'] < $_REQUEST['targetPos'])) 
	{
		$_REQUEST['targetPos']--;
	}
	$temp = array_splice($sidebarOrder[$_REQUEST['sidebarNumber']], $_REQUEST['modulePos'], 1);
	array_splice($sidebarOrder[$_REQUEST['targetSidebarNumber']], $_REQUEST['targetPos'], 0, $temp);
	
	setBlogSetting("sidebarOrder", serialize($sidebarOrder));
}

if ($_REQUEST['viewMode'] != '') $_REQUEST['viewMode'] = '?' . $_REQUEST['viewMode'];

if ($_SERVER['REQUEST_METHOD'] != 'POST')
	header('Location: '. $blogURL . '/owner/skin/sidebar' . $_REQUEST['viewMode']);
else
	respondResultPage(0);
?>
