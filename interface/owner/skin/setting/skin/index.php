<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

$IV = array(
	'POST' => array(
		'entriesOnPage' => array('int'),
		'entriesOnList' => array('int'),
		'entriesOnRecent' => array('int'),
		'commentsOnRecent' => array('int'),
		'commentsOnGuestbook' => array('int'),
		'archivesOnPage' => array('int'),
		'tagboxAlign' => array('int'),
		'tagsOnTagbox' => array('int'),
		'trackbacksOnRecent' => array('int'),
		'showListOnCategory' => array('int'),
		'showListOnArchive' => array('int'),
		'showListOnTag' => array('int'),
		'showListOnAuthor' => array('int'),
		'showListOnSearch' => array('int'),
		'expandComment' => array('int'),
		'expandTrackback' => array('int'),
		'recentNoticeLength' => array('int'),
		'recentEntryLength' => array('int'),
		'recentCommentLength' => array('int'),
		'recentTrackbackLength' => array('int'),
		'linkLength' => array('int'),
		'useMicroformat' => array('int'),
		'useFOAF' => array('int')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireComponent('Textcube.Function.Respond');
requireStrictRoute();

if (setSkinSetting($blogid, $_POST)) {
	respond::PrintResult(array('error' => 0));
} else {
	respond::PrintResult(array('error' => 1, 'msg' => mysql_error()));
}
?>