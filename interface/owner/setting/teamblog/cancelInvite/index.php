<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'POST' => array(
		'userid'=>array('id')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();

if (cancelTeamblogInvite($_POST['userid'])) {
	respond::ResultPage(0);
}
respond::ResultPage(-1);
?>
