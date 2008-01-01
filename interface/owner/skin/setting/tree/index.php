<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'POST' => array(
		'tree' => array('string', 'default' => 'base'),
		'colorOnTree' => array('string', 'default' => '000000'),
		'bgColorOnTree' => array('string', 'default' => ''),
		'activeColorOnTree' => array('string', 'default' => '000000'),
		'activeBgColorOnTree' => array('string', 'default' => ''),
		'labelLengthOnTree' => array('int', 'default' => 30),
		'showValueOnTree' => array('string', 'mandatory' => false)
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();
if(isset($suri['id'])) {
	$categories = getCategories($blogid);
	printRespond(array('code' => urlencode(getCategoriesViewInSkinSetting(getEntriesTotalCount($blogid), getCategories($blogid), $suri['id']))));
	exit;
} else {
	if (setTreeSetting($blogid, $_POST)) {
		header("Location: $blogURL/owner/skin/setting");
	} else {
	}
}
?>
