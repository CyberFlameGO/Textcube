<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'GET' => array(
		'blogid'=>array('id'),
		'acltype'=>array('string'),
		'userid'=>array('int'),
		'switch'=>array('int')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();
if (changeACLonBlog($_GET['blogid'],$_GET['acltype'],$_GET['userid'],$_GET['switch'])) {
	return respond::ResultPage(true);
}
respond::ResultPage(false);
?>
