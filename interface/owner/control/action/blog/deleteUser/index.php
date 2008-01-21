<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'GET' => array(
		'userid'=>array('id'),
		'blogid'=>array('id')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();

if (deleteTeamblogUser($_GET['userid'],$_GET['blogid'])) {
	respond::ResultPage(0);
}
respond::ResultPage(-1);
?>
