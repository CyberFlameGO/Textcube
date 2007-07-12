<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../../..');
$IV = array(
	'POST' => array(
		'names' => array('string', 'default' => null)
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireModel("blog.attachment");
requireStrictRoute();
if (!empty($_POST['names']) && deleteAttachmentMulti($blogid, $suri['id'], $_POST['names']))
	respondResultPage(0);
else
	respondResultPage( - 1);
?>