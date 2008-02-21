<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
require ROOT . '/lib/includeForBlogOwner.php';

$IV = array(
	'GET' => array(
		'userid' => array('id')
	) 
);
requireStrictRoute();

$result = User::deleteUser($_GET['userid']);
if ($result===true) {
	respond::PrintResult(array('error' => 0));
}
else {
	respond::PrintResult(array('error' => -1 , 'result' =>$result));
}
?>
