<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'POST' => array(
'userid'=>array('id')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();

if (deleteTeamblogUser($_POST['userid'])) {
	respondResultPage(0);
}
respondResultPage(-1);
?>
