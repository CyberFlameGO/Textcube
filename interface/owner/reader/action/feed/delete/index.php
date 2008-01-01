<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'POST' => array(
		'id' => array('id'),
		'group' => array('int')
	)
);
require ROOT . '/lib/includeForReader.php';
requireStrictRoute();
$result = array('error' => deleteFeed($blogid, $_POST['id']));
ob_start();
printFeeds($blogid, $_POST['group']);
$result['view'] = escapeCData(ob_get_contents());
ob_end_clean();
printRespond($result);
?>
