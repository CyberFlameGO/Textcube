<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'POST' => array(
		'targets' => array('list', 'default' => '')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireModel("blog.comment");
requireStrictRoute();

$isAjaxRequest = checkAjaxRequest();
if(isset($suri['id'])) {
	if (deleteCommentNotifiedInOwner($blogid, $suri['id']) === true)
		$isAjaxRequest ? respondResultPage(0) : header("Location: ".$_SERVER['HTTP_REFERER']);
	else
		$isAjaxRequest ? respondResultPage(-1) : header("Location: ".$_SERVER['HTTP_REFERER']);
} else {
	foreach(explode(',', $_POST['targets']) as $target)
		deleteCommentNotifiedInOwner($blogid, $target, false);
	respondResultPage(0);
}
?>
