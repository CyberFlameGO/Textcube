<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'POST' => array(
		'visibility' => array('int', 0, 3),
		'category' => array('int', 'default' => 0),
		'title' => array('string'),
		'content' => array('string'),
		'contentFormatter' => array('string'),
		'contentEditor' => array('string'),
		'permalink' => array('string', 'default' => ''),
		'location' => array('string', 'default' => '/'),
		'tag' => array('string', 'default' => ''),
		'acceptComment' => array(array('0', '1'), 'default' => '0'),
		'acceptTrackback' => array(array('0', '1'), 'default' => '0'),
		'published' => array('int', 0, 'default' => 0)
	)
);
require ROOT . '/lib/includeForBlogOwner.php';


requireModel('blog.entry');

requireStrictRoute();
if(empty($suri['id'])) respond::ResultPage(1);
$entry['id'] = $suri['id'];
$entry['draft'] = 1;
$entry['visibility'] = $_POST['visibility'];
$entry['category'] = $_POST['category'];
$entry['title'] = $_POST['title'];
$entry['content'] = $_POST['content'];
$entry['contentFormatter'] = $_POST['contentFormatter'];
$entry['contentEditor'] = $_POST['contentEditor'];
$entry['location'] = empty($_POST['location']) ? '/' : $_POST['location'];
$entry['tag'] = empty($_POST['tag']) ? '' : $_POST['tag'];
$entry['slogan'] = $_POST['permalink'];
$entry['acceptComment'] = empty($_POST['acceptComment']) ? 0 : 1;
$entry['acceptTrackback'] = empty($_POST['acceptTrackback']) ? 0 : 1;
$entry['published'] = empty($_POST['published']) ? 0 : $_POST['published'];
$id = saveDraftEntry($blogid, $entry);
if ($id > 0) {
	setBlogSetting('LatestEditedEntry_user'.getUserId(),$id);
	$result = array();
	$result['error'] = ($id > 0) ? 0 : 1;
	$result['entryId'] = $id;
	$result['suriid'] = $suri['id'];
	respond::PrintResult($result);
} else {
	respond::ResultPage(1);
}
?>
