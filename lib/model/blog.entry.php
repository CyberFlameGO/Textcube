<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

function getEntriesTotalCount($blogid) {
	global $database;
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0'.getPrivateCategoryExclusionQuery($blogid);
	return DBQuery::queryCell("SELECT COUNT(*) 
		FROM {$database['prefix']}Entries e
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0");
}

function getNoticesTotalCount($blogid) {
	global $database;
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0';
	return DBQuery::queryCell("SELECT COUNT(*) 
		FROM {$database['prefix']}Entries e
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category = -2");
}

function getEntries($blogid, $attributes = '*', $condition = false, $order = 'published DESC') {
	global $database;
	if (!empty($condition))
		$condition = 'AND ' . $condition;
	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 0';
	return DBQuery::queryAll("SELECT $attributes FROM {$database['prefix']}Entries WHERE blogid = $blogid AND draft = 0 $visibility $condition ORDER BY $order");
}


function getTemplates($blogid, $attributes = '*', $condition = false, $order = 'published DESC') {
	global $database;
	if (!empty($condition))
		$condition = 'AND ' . $condition;
	return DBQuery::queryAll("SELECT $attributes 
			FROM {$database['prefix']}Entries 
			WHERE blogid = $blogid 
				AND draft = 0 AND category = -4 $condition 
				ORDER BY $order");
}

function getEntry($blogid, $id, $draft = false) {
	global $database;
	requireModel('blog.attachment');
	if($id == 0) {
		if (!doesHaveOwnership())
			return;
		deleteAttachments($blogid, 0);
		return array('id'    => 0,
				'userid'     => 0,
				'draft'      => 0,
				'visibility' => 0,
				'category'   => 0,
				'location'   => '',
				'title'      => '',
				'content'    => '',
				'contentFormatter' => getDefaultFormatter(),
				'contentEditor'    => getDefaultEditor(),
				'acceptComment'    => 1,
				'acceptTrackback'  => 1,
				'published'  => time(),
				'slogan'     => '');
	}
	if ($draft) {
		$entry = DBQuery::queryRow("SELECT * FROM {$database['prefix']}Entries 
				WHERE blogid = $blogid 
					AND id = $id 
					AND draft = 1");
		if (!$entry)
			return;
		if ($entry['published'] == 1)
			$entry['republish'] = true;
		else if ($entry['published'] != 0)
			$entry['appointed'] = $entry['published'];
		if ($id != 0)
			$entry['published'] = DBQuery::queryCell("SELECT published 
					FROM {$database['prefix']}Entries 
					WHERE blogid = $blogid AND id = $id AND draft = 0");
		return $entry;
	} else {
		$visibility = doesHaveOwnership() ? '' : 'AND visibility > 0';
		$entry = DBQuery::queryRow("SELECT * 
				FROM {$database['prefix']}Entries 
				WHERE blogid = $blogid AND id = $id AND draft = 0 $visibility");
		if (!$entry)
			return;
		if ($entry['visibility'] < 0)
			$entry['appointed'] = $entry['published'];
		return $entry;
	}
}

function getUserIdOfEntry($blogid, $id, $draft = false) {
	global $database;
	$result = DBQuery::queryCell("SELECT userid 
		FROM {$database['prefix']}Entries
		WHERE 
			blogid = $blogid AND id = $id");
	if(!empty($result)) return $result;
	else return null;
}

function getEntryAttributes($blogid, $id, $attributeNames) {
	global $database;
	
	if (stristr($attributeNames, "from") != false) // security check!
		return null;
	
	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 0';
	$attributes = DBQuery::queryRow("SELECT $attributeNames FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $id AND draft = 0 $visibility");
	return $attributes;
}

function getEntryListWithPagingByCategory($blogid, $category, $page, $count) {
	global $database, $suri, $folderURL;
	if ($category === null)
		return array();
	if (!doesHaveOwnership() && getCategoryVisibility($blogid, $category) < 2 && $category != 0)
		return array();
	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 1';
	if ($category > 0) {
		$categories = DBQuery::queryColumn("SELECT id FROM {$database['prefix']}Categories WHERE blogid = $blogid AND parent = $category $visibility");
		array_push($categories, $category);
		$cond = 'AND e.category IN (' . implode(', ', $categories) . ')';
		$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0';
	} else {
		$cond = 'AND e.category >= 0';
		$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0'.getPrivateCategoryExclusionQuery($blogid);
	}

	$sql = "SELECT e.blogid,e.userid,e.id,e.title,e.comments,e.slogan,e.published
			FROM {$database['prefix']}Entries e 
			WHERE e.blogid = $blogid AND e.draft = 0 $visibility $cond 
			ORDER BY e.published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}");
}

function getEntryListWithPagingByTag($blogid, $tag, $page, $count) {
	global $database, $suri, $folderURL;
	if ($tag === null)
		return array(array(), array('url'=>'','prefix'=>'','postfix'=>''));	
	$tag = tc_escape_string($tag);
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0'.getPrivateCategoryExclusionQuery($blogid);
	$sql = "SELECT e.blogid, e.userid, e.id, e.title, e.comments, e.slogan, e.published
		FROM {$database['prefix']}Entries e 
		LEFT JOIN {$database['prefix']}TagRelations t ON e.id = t.entry AND e.blogid = t.blogid 
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0 AND t.tag = '$tag' 
		ORDER BY published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}");
}

function getEntryListWithPagingByPeriod($blogid, $period, $page, $count) {
	global $database, $suri, $folderURL;
	$cond = "AND e.published >= " . getTimeFromPeriod($period) . " AND e.published < " . getTimeFromPeriod(addPeriod($period));
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0'.getPrivateCategoryExclusionQuery($blogid);
	$sql = "SELECT e.blogid, e.userid, e.id, e.title, e.comments, e.slogan, e.published
		FROM {$database['prefix']}Entries e
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0 $cond 
		ORDER BY e.published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}");
}

function getEntryListWithPagingBySearch($blogid, $search, $page, $count) {
	global $database, $suri, $folderURL;
	$search = escapeSearchString($search);
	$cond = strlen($search) == 0 ? 'AND 0' : "AND (title LIKE '%$search%' OR content LIKE '%$search%')";
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 1'.getPrivateCategoryExclusionQuery($blogid);
	$sql = "SELECT e.blogid, e.userid, e.id, e.title, e.comments, e.slogan, e.published
		FROM {$database['prefix']}Entries e
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0 $cond 
		ORDER BY e.published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}");
}

function getEntriesWithPaging($blogid, $page, $count) {
	global $database;
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0 AND (c.visibility > 1 OR e.category = 0)';
	$sql = "SELECT e.*, c.label categoryLabel 
		FROM {$database['prefix']}Entries e 
		LEFT JOIN {$database['prefix']}Categories c ON e.blogid = c.blogid AND e.category = c.id 
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0 
		ORDER BY e.published DESC";
	return fetchWithPaging($sql, $page, $count);
}

function getEntriesWithPagingByCategory($blogid, $category, $page, $count, $countItem) {
	global $database, $folderURL, $suri;
	if ($category === null)
		return fetchWithPaging(null, $page, $count, "$folderURL/{$suri['value']}");
	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 1';
	if ($category > 0) {
		$categories = DBQuery::queryColumn("SELECT id FROM {$database['prefix']}Categories WHERE blogid = $blogid AND parent = $category $visibility");
		array_push($categories, $category);
		$cond = 'AND e.category IN (' . implode(', ', $categories) . ')';
		$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0';
	} else {
		$cond = 'AND e.category >= 0';
		$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0 AND (c.visibility > 1 OR e.category = 0)';
	}
	$sql = "SELECT e.*, c.label AS categoryLabel 
		FROM {$database['prefix']}Entries AS e 
		LEFT JOIN {$database['prefix']}Categories c ON e.category = c.id AND e.blogid = c.blogid 
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility $cond 
		ORDER BY e.published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}","?page=",$countItem);
}

function getEntriesWithPagingByTag($blogid, $tag, $page, $count, $countItem = null) {
	global $database, $folderURL, $suri;
	if ($tag === null)
		return fetchWithPaging(null, $page, $count, "$folderURL/{$suri['value']}");
	$tag = tc_escape_string($tag);
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0'.getPrivateCategoryExclusionQuery($blogid);
	$sql = "SELECT e.*, c.label categoryLabel 
		FROM {$database['prefix']}Entries e
		LEFT JOIN {$database['prefix']}Categories c ON e.blogid = c.blogid AND e.category = c.id 
		LEFT JOIN {$database['prefix']}TagRelations t ON e.id = t.entry AND e.blogid = t.blogid 
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0 AND t.tag = '$tag' 
		ORDER BY e.published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}","?page=", $countItem);
}

function getEntriesWithPagingByNotice($blogid, $page, $count, $countItem = null) {
	global $database, $folderURL, $suri;
	$visibility = doesHaveOwnership() ? '' : 'AND visibility = 2';
	$sql = "SELECT * 
		FROM {$database['prefix']}Entries 
		WHERE blogid = $blogid $visibility AND category = -2 
		ORDER BY published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}","?page=", $countItem);
}

function getEntriesWithPagingByPeriod($blogid, $period, $page, $count) {
	global $database, $folderURL, $suri;
	$cond = "AND published >= " . getTimeFromPeriod($period) . " AND published < " . getTimeFromPeriod(addPeriod($period));
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0 AND (c.visibility > 1 OR e.category = 0)';
	$sql = "SELECT e.*, c.label categoryLabel 
		FROM {$database['prefix']}Entries e 
		LEFT JOIN {$database['prefix']}Categories c ON e.blogid = c.blogid AND e.category = c.id 
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0 $cond 
		ORDER BY e.published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}");
}

function getEntriesWithPagingBySearch($blogid, $search, $page, $count, $countItem) {
	global $database, $folderURL, $suri;
	$search = escapeSearchString($search);
	$cond = strlen($search) == 0 ? 'AND 0' : "AND (e.title LIKE '%$search%' OR e.content LIKE '%$search%')";
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 1 AND (c.visibility > 1 OR e.category = 0)';
	$sql = "SELECT e.*, c.label categoryLabel 
		FROM {$database['prefix']}Entries e 
		LEFT JOIN {$database['prefix']}Categories c ON e.blogid = c.blogid AND e.category = c.id 
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0 $cond 
		ORDER BY e.published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}","?page=", $countItem);
}

function getEntriesWithPagingForOwner($blogid, $category, $search, $page, $count, $visibility = null) {
	global $database, $suri;
	requireComponent('Eolin.PHP.Core');
	
	$teamMemberFilter = "";
	if( ! Acl::check("group.editors", "entry.list") ) {
		$teamMemberFilter = " AND e.userid = ".getUserId();
	}
	
	$sql = "SELECT e.*, c.label categoryLabel, d.id draft 
		FROM {$database['prefix']}Entries e 
		LEFT JOIN {$database['prefix']}Categories c ON e.category = c.id AND e.blogid = c.blogid 
		LEFT JOIN {$database['prefix']}Entries d ON e.blogid = d.blogid AND e.id = d.id AND d.draft = 1 
		WHERE e.blogid = $blogid AND e.draft = 0" . $teamMemberFilter;
	if ($category > 0) {
		$categories = DBQuery::queryColumn("SELECT id FROM {$database['prefix']}Categories WHERE blogid = $blogid AND parent = $category");
		array_push($categories, $category);
		$sql .= ' AND e.category IN (' . implode(', ', $categories) . ')';
	} else if ($category == -3) {
		$sql .= ' AND e.category = 0';
	} else if ($category == -5) {
		$sql .= ' AND e.category >= -2';
	} else if ($category == 0) {
		$sql .= ' AND e.category >= 0';
	} else {
		$sql .= ' AND e.category = '.$category;
	}
	if(isset($visibility)) {
		if(Validator::isInteger($visibility,0,3)) {
			$sql .= ' AND e.visibility = '.$visibility;
		} else {
			$sql .= ' AND e.visibility '.$visibility;
		}
	}
	if (!empty($search)) {
		$search = escapeSearchString($search);
		$sql .= " AND (e.title LIKE '%$search%' OR e.content LIKE '%$search%')";
	}
	$sql .= ' ORDER BY e.published DESC';
	return fetchWithPaging($sql, $page, $count);
}

function getEntryWithPaging($blogid, $id, $isNotice = false) {
	global $database;
	global $folderURL;
	$entries = array();
	$paging = initPaging($folderURL, '/');
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0';
	$visibility .= ($isNotice || doesHaveOwnership())  ? '' : ' AND (c.visibility > 1 OR e.category = 0)';
	$category = $isNotice ? 'e.category = -2' : 'e.category >= 0';
	$currentEntry = DBQuery::queryRow("SELECT e.*, c.label categoryLabel 
		FROM {$database['prefix']}Entries e 
		LEFT JOIN {$database['prefix']}Categories c ON e.blogid = c.blogid AND e.category = c.id 
		WHERE e.blogid = $blogid 
			AND e.id = $id 
			AND e.draft = 0 $visibility AND $category");
	$result = DBQuery::query("SELECT e.id 
		FROM {$database['prefix']}Entries e 
		LEFT JOIN {$database['prefix']}Categories c ON e.blogid = c.blogid AND e.category = c.id 
		WHERE e.blogid = $blogid 
			AND e.draft = 0 $visibility AND $category 
		ORDER BY e.published DESC");
	if (!$result || !$currentEntry)
		return array($entries, $paging);
	$paging['pages'] = ($isNotice) ? getNoticesTotalCount($blogid) : getEntriesTotalCount($blogid);

	for ($i = 1; $entry = mysql_fetch_array($result); $i++) {
		if ($entry['id'] != $id) {
			if (array_push($paging['before'], $entry['id']) > 4) {
				if ($i == 5) 
					$paging['first'] = array_shift($paging['before']);
				else 
					array_shift($paging['before']);
			}
			continue;
		}
		$paging['page'] = $i;
		array_push($entries, $currentEntry);
		$paging['after'] = array();
		for ($i++; (count($paging['after']) < 4) && ($entry = mysql_fetch_array($result)); $i++)
			array_push($paging['after'], $entry['id']);
		if ($i < $paging['pages']) {
			while ($entry = mysql_fetch_array($result))
				$paging['last'] = $entry['id'];
		}
		if (count($paging['before']) > 0)
			$paging['prev'] = $paging['before'][count($paging['before']) - 1];
		if (isset($paging['after'][0]))
			$paging['next'] = $paging['after'][0];
		return array($entries, $paging);
	}
	$paging['page'] = $paging['pages'] + 1;
	return array($entries, $paging);
}

function getEntryWithPagingBySlogan($blogid, $slogan, $isNotice = false) {
	global $database;
	global $blogURL;
	$entries = array();
	$paging = initPaging("$blogURL/entry", '/');
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0';
	$visibility .= ($isNotice || doesHaveOwnership()) ? '' : getPrivateCategoryExclusionQuery($blogid);
	$category = $isNotice ? 'e.category = -2' : 'e.category >= 0';

	$currentEntry = DBQuery::queryRow("SELECT e.*, c.label categoryLabel 
		FROM {$database['prefix']}Entries e 
		LEFT JOIN {$database['prefix']}Categories c ON e.blogid = c.blogid AND e.category = c.id 
		WHERE e.blogid = $blogid 
			AND e.slogan = '".tc_escape_string($slogan)."' 
			AND e.draft = 0 $visibility AND $category");

	$result = DBQuery::query("SELECT e.id, e.slogan 
		FROM {$database['prefix']}Entries e 
		LEFT JOIN {$database['prefix']}Categories c ON e.blogid = c.blogid AND e.category = c.id 
		WHERE e.blogid = $blogid 
			AND e.draft = 0 $visibility AND $category 
		ORDER BY e.published DESC");
	if (!$result || !$currentEntry)
		return array($entries, $paging);
	$paging['pages'] = ($isNotice) ? getNoticesTotalCount($blogid) : getEntriesTotalCount($blogid);
	for ($i = 1; $entry = mysql_fetch_array($result); $i++) {
		if ($entry['slogan'] != $slogan) {
			if (array_push($paging['before'], $entry['slogan']) > 4) if ($i == 5)
				$paging['first'] = array_shift($paging['before']);
			else
				array_shift($paging['before']);
			continue;
		}
		$paging['page'] = $i;
		array_push($entries, $currentEntry);
		$paging['after'] = array();
		for ($i++; (count($paging['after']) < 4) && ($entry = mysql_fetch_array($result)); $i++)
			array_push($paging['after'], $entry['slogan']);
		if ($i < $paging['pages']) {
			while ($entry = mysql_fetch_array($result))
				$paging['last'] = $entry['slogan'];
		}
		if (count($paging['before']) > 0)
			$paging['prev'] = $paging['before'][count($paging['before']) - 1];
		if (isset($paging['after'][0]))
			$paging['next'] = $paging['after'][0];
		return array($entries, $paging);
	}
	$paging['page'] = $paging['pages'] + 1;
	return array($entries, $paging);
}

function getSlogan($slogan) {
	$slogan = preg_replace('/-+/', ' ', $slogan);
	$slogan = preg_replace('@[!-/:-\@\[-\^`{-~]+@', '', $slogan);
	$slogan = preg_replace('/\s+/', '-', $slogan);
	$slogan = trim($slogan, '-');
	return strlen($slogan) > 0 ? $slogan : 'XFile';
}

function getRecentEntries($blogid) {
	global $database, $skinSetting;
	$entries = array();
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0'.getPrivateCategoryExclusionQuery($blogid);
	$result = DBQuery::query("SELECT e.id, e.userid, e.title, e.slogan, e.comments 
		FROM {$database['prefix']}Entries e
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0 
		ORDER BY published DESC LIMIT {$skinSetting['entriesOnRecent']}");
	while ($entry = mysql_fetch_array($result)) {
		array_push($entries, $entry);
	}
	return $entries;
}

function addEntry($blogid, $entry) {
	global $database, $blog;
	requireModel("blog.attachment");
	requireModel("blog.rss");
	requireModel("blog.category");
	requireModel("blog.tag");
	requireModel("blog.locative");

	$entry['userid'] = getUserId();
	$entry['title'] = UTF8::lessenAsEncoding(trim($entry['title']), 255);
	$entry['location'] = UTF8::lessenAsEncoding(trim($entry['location']), 255);
	$entry['slogan'] = array_key_exists('slogan', $entry) ? trim($entry['slogan']) : '';
	
	if((empty($entry['slogan']))||($entry['category'] == -1)) {
		$slogan = $slogan0 = getSlogan($entry['title']);
	} else {
		$slogan = $slogan0 = getSlogan($entry['slogan']);
	}

	$slogan = tc_escape_string(UTF8::lessenAsEncoding($slogan, 255));
	$title = tc_escape_string($entry['title']);

	if($entry['category'] == -1) {
		if($entry['visibility'] == 1 || $entry['visibility'] == 3)
			return false;
		if(DBQuery::queryCell("SELECT count(*) FROM {$database['prefix']}Entries WHERE blogid = $blogid AND draft = 0 AND title = '$title' AND category = -1") > 0)
			return false;
	}
	
	if ($entry['category'] < 0) {
		if ($entry['visibility'] == 1) $entry['visibility'] = 0;
		if ($entry['visibility'] == 3) $entry['visibility'] = 2;
	}
	if ($entry['category'] == -4) {
		$entry['visibility'] = 0;
	}

	$result = DBQuery::queryCount("SELECT slogan FROM {$database['prefix']}Entries WHERE blogid = $blogid AND slogan = '$slogan' AND draft = 0 LIMIT 1");
	for ($i = 1; $result > 0; $i++) {
		if ($i > 1000)
			return false;
		$slogan = tc_escape_string(UTF8::lessenAsEncoding($slogan0, 245) . '-' . $i);
		$result = DBQuery::queryCount("SELECT slogan FROM {$database['prefix']}Entries WHERE blogid = $blogid AND slogan = '$slogan' AND draft = 0 LIMIT 1");
	}
	$userid = $entry['userid'];
	$content = tc_escape_string($entry['content']);
	$contentFormatter = tc_escape_string($entry['contentFormatter']);
	$contentEditor = tc_escape_string($entry['contentEditor']);
	$password = tc_escape_string(generatePassword());
	$location = tc_escape_string($entry['location']);
	if (isset($entry['published']) && is_numeric($entry['published']) && ($entry['published'] >= 2)) {
		$published = $entry['published'];
		$entry['visibility'] = 0 - $entry['visibility'];
	} else {
		$published = 'UNIX_TIMESTAMP()';
	}
	
	$currentMaxId = DBQuery::queryCell("SELECT MAX(id) FROM {$database['prefix']}Entries WHERE blogid = $blogid AND draft = 0");
	if(!empty($currentMaxId) && $currentMaxId > 0) {
		$id = $currentMaxId + 1;
	} else {
		$id = 1;
	}
	$result = DBQuery::query("INSERT INTO {$database['prefix']}Entries 
			(blogid, userid, id, draft, visibility, category, title, slogan, content, contentFormatter,
			 contentEditor, location, password, acceptComment, acceptTrackback, published, created, modified,
			 comments, trackbacks) 
			VALUES (
			$blogid,
			$userid,
			$id,
			0,
			{$entry['visibility']},
			{$entry['category']},
			'$title',
			'$slogan',
			'$content',
			'$contentFormatter',
			'$contentEditor',
			'$location',
			'$password',
			{$entry['acceptComment']},
			{$entry['acceptTrackback']},
			$published,
			UNIX_TIMESTAMP(),
			UNIX_TIMESTAMP(),
			0,
			0)");
	if (!$result)
		return false;
	DBQuery::query("UPDATE {$database['prefix']}Attachments SET parent = $id WHERE blogid = $blogid AND parent = 0");
	DBQuery::query("DELETE FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $id AND draft = 1");
	updateEntriesOfCategory($blogid, $entry['category']);
	if ($entry['visibility'] == 3)
		syndicateEntry($id, 'create');
	if ($entry['visibility'] >= 2)
		clearRSS();
	if (!empty($entry['tag'])) {
		$tags = getTagsWithEntryString($entry['tag']);
		addTagsWithEntryId($blogid, $id, $tags);
	}
	return $id;
}

function updateEntry($blogid, $entry, $updateDraft = 0) {
	global $database;
	global $blog;
	requireModel('blog.tag');
	requireModel('blog.locative');
	requireModel('blog.attachment');
	requireModel('blog.category');
	requireModel('blog.rss');

	if($entry['id'] == 0) return false;
	
	if(empty($entry['userid'])) $entry['userid'] = getUserId(); 
	$entry['title'] = UTF8::lessenAsEncoding(trim($entry['title']));
	$entry['location'] = UTF8::lessenAsEncoding(trim($entry['location']));
	$entry['slogan'] = array_key_exists('slogan', $entry) ? trim($entry['slogan']) : '';
	if(empty($entry['slogan'])) {
		$slogan = $slogan0 = getSlogan($entry['title']);
	} else {
		$slogan = $slogan0 = getSlogan($entry['slogan']);
	}
	$slogan = tc_escape_string(UTF8::lessenAsEncoding($slogan, 255));
	$title = tc_escape_string($entry['title']);

	if($entry['category'] == -1) {
		if($entry['visibility'] == 1 || $entry['visibility'] == 3)
			return false;
		if(DBQuery::queryCell("SELECT count(*) 
			FROM {$database['prefix']}Entries 
			WHERE blogid = $blogid 
				AND id <> {$entry['id']} 
				AND draft = 0 
				AND title = '$title' 
				AND category = -1") > 0)
			return false;
	}

	if ($entry['category'] < 0) {
		if ($entry['visibility'] == 1) $entry['visibility'] = 0;
		if ($entry['visibility'] == 3) $entry['visibility'] = 2;
	}
	if ($entry['category'] == -4) {
		$entry['visibility'] = 0;
	}
	
	$result = DBQuery::queryCount("SELECT slogan 
		FROM {$database['prefix']}Entries 
		WHERE blogid = $blogid 
		AND slogan = '$slogan' 
		AND id = {$entry['id']} 
		AND draft = 0
		LIMIT 1");
	if ($result == 0) { // if changed
		$result = DBQuery::queryCount("SELECT slogan FROM {$database['prefix']}Entries WHERE blogid = $blogid AND slogan = '$slogan' AND draft = 0 LIMIT 1");
		for ($i = 1; $result > 0; $i++) {
			if ($i > 1000)
				return false;
			$slogan = tc_escape_string(UTF8::lessenAsEncoding($slogan0, 245) . '-' . $i);
			$result = DBQuery::queryCount("SELECT slogan FROM {$database['prefix']}Entries WHERE blogid = $blogid AND slogan = '$slogan' AND draft = 0 LIMIT 1");
		}
	}
	$tags = getTagsWithEntryString($entry['tag']);
	modifyTagsWithEntryId($blogid, $entry['id'], $tags);
	
	$location = tc_escape_string($entry['location']);
	$content = tc_escape_string($entry['content']);
	$contentFormatter = tc_escape_string($entry['contentFormatter']);
	$contentEditor = tc_escape_string($entry['contentEditor']);
	switch ($entry['published']) {
		case 0:
			$published = 'published';
			break;
		case 1:
			$published = 'UNIX_TIMESTAMP()';
			break;
		default:
			$published = $entry['published'];
			$entry['visibility'] = 0 - $entry['visibility'];
			break;
	}

	$result = DBQuery::query("UPDATE {$database['prefix']}Entries
			SET
				userid             = {$entry['userid']},
				visibility         = {$entry['visibility']},
				category           = {$entry['category']},
				draft              = 0,
				location           = '$location',
				title              = '$title',
				content            = '$content',
				contentFormatter   = '$contentFormatter',
				contentEditor      = '$contentEditor',
				slogan             = '$slogan',
				acceptComment      = {$entry['acceptComment']},
				acceptTrackback    = {$entry['acceptTrackback']},
				published          = $published,
				modified           = UNIX_TIMESTAMP()
			WHERE blogid = $blogid AND id = {$entry['id']} AND draft = $updateDraft");
	if ($result)
		@DBQuery::query("DELETE FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = {$entry['id']} AND draft = 1");
	updateEntriesOfCategory($blogid, $entry['category']);
	if ($entry['visibility'] == 3)
		syndicateEntry($entry['id'], 'modify');
	DBQuery::query("UPDATE {$database['prefix']}Attachments SET parent = {$entry['id']} WHERE blogid = $blogid AND parent = 0");
	if ($entry['visibility'] >= 2)
		clearRSS();
	return $result ? $entry['id'] : false;
}

function saveDraftEntry($blogid, $entry) {
	global $database, $blog;
	requireModel('blog.tag');
	requireModel('blog.locative');
	requireModel('blog.attachment');
	requireModel('blog.category');
	requireModel('blog.rss');

	if($entry['id'] == 0) return -11;

	$draftCount = DBQuery::queryCell("SELECT count(*) FROM {$database['prefix']}Entries
		WHERE blogid = $blogid
			AND id = ".$entry['id']."
			AND draft = 1");

	if($draftCount > 0) { // draft가 없으면 insert를, 있으면 update를.
		$doUpdate = true;
	} else {
		$doUpdate = false;
	}
	// 원 글을 읽어서 몇가지 정보를 보존한다. 원래 글이 없는 경우 draft는 저장될 수 없다.
	$origEntry = DBQuery::queryRow("SELECT created, comments, trackbacks, password
		FROM {$database['prefix']}Entries
		WHERE blogid = $blogid
			AND id = ".$entry['id']."
			AND draft = 0");
	if(empty($origEntry)) return -12;
	
	$created = $origEntry['created'];
	$comments = $origEntry['comments'];
	$trackbacks = $origEntry['trackbacks'];
	$password = $origEntry['password'];
	
	if(empty($entry['userid'])) $entry['userid'] = getUserId(); 
	$entry['title'] = UTF8::lessenAsEncoding(trim($entry['title']));
	$entry['location'] = UTF8::lessenAsEncoding(trim($entry['location']));
	$entry['slogan'] = array_key_exists('slogan', $entry) ? trim($entry['slogan']) : '';
	if(empty($entry['slogan'])) {
		$slogan = $slogan0 = getSlogan($entry['title']);
	} else {
		$slogan = $slogan0 = getSlogan($entry['slogan']);
	}
	$slogan = tc_escape_string(UTF8::lessenAsEncoding($slogan, 255));
	$title = tc_escape_string($entry['title']);

	if($entry['category'] == -1) {
		if($entry['visibility'] == 1 || $entry['visibility'] == 3)
			return false;
		if(DBQuery::queryCell("SELECT count(*) 
			FROM {$database['prefix']}Entries 
			WHERE blogid = $blogid 
				AND id <> {$entry['id']} 
				AND draft = 0 
				AND title = '$title' 
				AND category = -1") > 0)
			return -13;
	}

	if ($entry['category'] < 0) {
		if ($entry['visibility'] == 1) $entry['visibility'] = 0;
		if ($entry['visibility'] == 3) $entry['visibility'] = 2;
	}
	if ($entry['category'] == -4) {
		$entry['visibility'] = 0;
	}
	
	$result = DBQuery::queryCount("SELECT slogan 
		FROM {$database['prefix']}Entries 
		WHERE blogid = $blogid 
		AND slogan = '$slogan' 
		AND id = {$entry['id']} 
		AND draft = 0 LIMIT 1");
	if ($result == 0) { // if changed
		$result = DBQuery::queryExistence("SELECT slogan FROM {$database['prefix']}Entries WHERE blogid = $blogid AND slogan = '$slogan' AND draft = 0 LIMIT 1");
		for ($i = 1; $result != false; $i++) {
			if ($i > 1000)
				return false;
			$slogan = tc_escape_string(UTF8::lessenAsEncoding($slogan0, 245) . '-' . $i);
			$result = DBQuery::queryExistence("SELECT slogan FROM {$database['prefix']}Entries WHERE blogid = $blogid AND slogan = '$slogan' AND draft = 0 LIMIT 1");
		}
	}
	$tags = getTagsWithEntryString($entry['tag']);
	modifyTagsWithEntryId($blogid, $entry['id'], $tags);
	
	$location = tc_escape_string($entry['location']);
	$content = tc_escape_string($entry['content']);
	$contentFormatter = tc_escape_string($entry['contentFormatter']);
	$contentEditor = tc_escape_string($entry['contentEditor']);
	switch ($entry['published']) {
		case 0:
			$published = 'published';
			break;
		case 1:
			$published = 'UNIX_TIMESTAMP()';
			break;
		default:
			$published = $entry['published'];
			$entry['visibility'] = 0 - $entry['visibility'];
			break;
	}

	if($doUpdate) {
		$result = DBQuery::query("UPDATE {$database['prefix']}Entries
			SET
				userid             = {$entry['userid']},
				visibility         = {$entry['visibility']},
				category           = {$entry['category']},
				draft              = 1,
				location           = '$location',
				title              = '$title',
				content            = '$content',
				contentFormatter   = '$contentFormatter',
				contentEditor      = '$contentEditor',
				slogan             = '$slogan',
				acceptComment      = {$entry['acceptComment']},
				acceptTrackback    = {$entry['acceptTrackback']},
				published          = $published,
				modified           = UNIX_TIMESTAMP()
			WHERE blogid = $blogid AND id = {$entry['id']} AND draft = 1");
	} else {
		$result = DBQuery::query("INSERT INTO {$database['prefix']}Entries 
			(blogid, userid, id, draft, visibility, category, title, slogan, content, contentFormatter,
			 contentEditor, location, password, acceptComment, acceptTrackback, published, created, modified,
			 comments, trackbacks) 
			VALUES (
			$blogid,
			{$entry['userid']},
			{$entry['id']},
			1,
			{$entry['visibility']},
			{$entry['category']},
			'$title',
			'$slogan',
			'$content',
			'$contentFormatter',
			'$contentEditor',
			'$location',
			'$password',
			{$entry['acceptComment']},
			{$entry['acceptTrackback']},
			$published,
			$created,
			UNIX_TIMESTAMP(),
			$comments,
			$trackbacks)");
	}
	return $result ? $entry['id'] : false;
}

function updateTrackbacksOfEntry($blogid, $id) {
	global $database;
	$trackbacks = DBQuery::queryCell("SELECT COUNT(*) FROM {$database['prefix']}Trackbacks WHERE blogid = $blogid AND entry = $id AND isFiltered = 0");
	if ($trackbacks === null)
		return false;
	return DBQuery::execute("UPDATE {$database['prefix']}Entries SET trackbacks = $trackbacks WHERE blogid = $blogid AND id = $id");
}

function deleteEntry($blogid, $id) {
	global $database, $blog;
	requireModel("blog.rss");
	requireModel("blog.category");
	requireModel("blog.attachment");
	requireModel("blog.tag");

	$target = getEntry($blogid, $id);
	if (DBQuery::queryCell("SELECT visibility FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $id") == 3)
		syndicateEntry($id, 'delete');
	$result = DBQuery::query("DELETE FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $id");
	if (mysql_affected_rows() > 0) {
		$result = DBQuery::query("DELETE FROM {$database['prefix']}Comments WHERE blogid = $blogid AND entry = $id");
		$result = DBQuery::query("DELETE FROM {$database['prefix']}Trackbacks WHERE blogid = $blogid AND entry = $id");
		$result = DBQuery::query("DELETE FROM {$database['prefix']}TrackbackLogs WHERE blogid = $blogid AND entry = $id");

		updateEntriesOfCategory($blogid, $target['category']);
		deleteAttachments($blogid, $id);
		
		deleteTagsWithEntryId($blogid, $id);
		
		clearRSS();
		return true;
	}
	return false;
}

function changeCategoryOfEntries($blogid, $entries, $category) {
	global $database;
	requireModel("blog.category");
	requireModel("blog.rss");

	$targets = array_unique(preg_split('/,/', $entries, -1, PREG_SPLIT_NO_EMPTY));
	if ( count($targets)<1 || !is_numeric($category) ) 
		return false;
		
	if ($category == -1) { // Check Keyword duplication
		foreach($targets as $entryId) {
			$title = DBQuery::queryCell("SELECT title FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $entryId AND draft = 0");
			if (is_null($title)) return false;
			if (DBQuery::queryExistence("SELECT id FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id <> $entryId AND draft = 0 AND title = '$title' AND category = -1") == true) return false;
		}
	}
	
	foreach($targets as $entryId) {
		$oldVisibility = DBQuery::queryCell("SELECT visibility FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $entryId AND draft = 0");
		$visibility = 	$oldVisibility;
		if ($category < 0) {
			if ($visibility == 1) $visibility = 0;
			if ($visibility == 3) $visibility = 2;
		}
		
		if (($oldVisibility == 3) && ($visibility != 3))
			syndicateEntry($entryId, 'delete');
			
		DBQuery::execute("UPDATE {$database['prefix']}Entries SET category = $category , visibility = $visibility WHERE blogid = $blogid AND id = $entryId");
	}	

	if(updateEntriesOfCategory($blogid)) {
		clearRSS();
		return true;	
	}
	return false;
}

function setEntryVisibility($id, $visibility) {
	global $database;
	requireModel("blog.rss");
	requireModel("blog.category");
	requireComponent('Needlworks.Cache.PageCache');
	$blogid = getBlogId();
	if (($visibility < 0) || ($visibility > 3))
		return false;
	list($oldVisibility, $category) = DBQuery::queryRow("SELECT visibility, category FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $id AND draft = 0");

	if ($category < 0) {
		if ($visibility == 1) $visibility = 0;
		if ($visibility == 3) $visibility = 2;
	}
	
	if ($oldVisibility === null)
		return false;
	if ($visibility == $oldVisibility)
		return true;

	CacheControl::flushCategory($category);
	if ($oldVisibility == 3)
		syndicateEntry($id, 'delete');
	else if ($visibility == 3) {
		if (!syndicateEntry($id, 'create')) {
			DBQuery::query("UPDATE {$database['prefix']}Entries 
				SET visibility = $oldVisibility, 
					modified = UNIX_TIMESTAMP() 
				WHERE blogid = $blogid AND id = $id");
			return false;
		}
	}

	$result = DBQuery::query("UPDATE {$database['prefix']}Entries 
		SET visibility = $visibility, 
			modified = UNIX_TIMESTAMP() 
		WHERE blogid = $blogid AND id = $id");
	if (!$result)
		return false;
	if (mysql_affected_rows() == 0)
		return true;

	if ($category >= 0) {
		if ((($oldVisibility >= 2) && ($visibility < 2)) || (($oldVisibility < 2) && ($visibility >= 2)))
			clearRSS();
		if ((($oldVisibility == 3) && ($visibility <= 2)) || (($oldVisibility <= 2) && ($visibility == 3)))
			clearRSS();
		if ($category > 0)
			updateEntriesOfCategory($blogid, $category);
	}
	return true;
}

function protectEntry($id, $password) {
	global $database;
	$password = tc_escape_string($password);
	$result = DBQuery::query("UPDATE {$database['prefix']}Entries SET password = '$password', modified = UNIX_TIMESTAMP() WHERE blogid = ".getBlogId()." AND id = $id AND visibility = 1");
	return ($result && (mysql_affected_rows() > 0));
}

function syndicateEntry($id, $mode) {
	global $database, $blog, $defaultURL;
	requireComponent('Eolin.PHP.Core');
	requireComponent('Eolin.PHP.XMLRPC');
	$blogid = getBlogId();
	$rpc = new XMLRPC();
	$rpc->url = TEXTCUBE_SYNC_URL;
	$summary = array('blogURL' => $defaultURL, 'syncURL' => "$defaultURL/sync/$id");
	if($mode == 'create') {
		$entry = getEntry($blogid, $id);
		$summary['blogTitle'] = $blog['title'];
		$summary['language'] = $blog['language'];
		$summary['permalink'] = "$defaultURL/".($blog['useSlogan'] ? "entry/{$entry['slogan']}": $entry['id']);
		$summary['title'] = $entry['title'];
		$summary['content'] = UTF8::lessenAsByte(stripHTML(getEntryContentView($blogid, $entry['id'], $entry['content'], $entry['contentFormatter'])), 1023, '');
		$summary['author'] = DBQuery::queryCell("SELECT name FROM {$database['prefix']}Users WHERE userid = {$entry['userid']}");
		$summary['tags'] = array();
		foreach(DBQuery::queryAll("SELECT DISTINCT name FROM {$database['prefix']}Tags, {$database['prefix']}TagRelations WHERE id = tag AND blogid = $blogid AND entry = $id ORDER BY name") as $tag)
			array_push($summary['tags'], $tag['name']);
		$summary['location'] = $entry['location'];
		$summary['written'] = Timestamp::getRFC1123($entry['published']);
	}
	if(!$rpc->call("sync.$mode", $summary))
		return false;
	if($rpc->fault)
		return false;
	return true;
}

function publishEntries() {
	global $database;
	$blogid = getBlogId();
	$entries = DBQuery::queryAll("SELECT id, visibility 
			FROM {$database['prefix']}Entries 
			WHERE blogid = $blogid AND draft = 0 AND visibility < 0 AND published < UNIX_TIMESTAMP()");
	if (count($entries) == 0)
		return;
	foreach ($entries as $i => $entry) {
		$result = DBQuery::query("UPDATE {$database['prefix']}Entries 
				SET visibility = 0 
				WHERE blogid = $blogid AND id = {$entry['id']} AND draft = 0");
		if ($entry['visibility'] == -3) {
			if ($result && setEntryVisibility($entry['id'], 2)) {
					$updatedEntry = getEntry($blogid, $entry['id']);
					fireEvent('UpdatePost', $entry['id'], $updatedEntry);
					setEntryVisibility($entry['id'], 3);
			}
		}
		else {
			if ($result) {
				setEntryVisibility($entry['id'], abs($entry['visibility']));
				$updatedEntry = getEntry($blogid, $entry['id']);
				fireEvent('UpdatePost', $entry['id'], $updatedEntry);
			}
		}
	}
}

function getTagsWithEntryString($entryTag) 
{
	global $database;
	$tags = explode(',', $entryTag);
	
	$ret = array();
	
	foreach ($tags as $tag) {
		$tag = UTF8::lessenAsEncoding($tag, 255, '');
		$tag = str_replace('&quot;', '"', $tag);
		$tag = str_replace('&#39;', '\'', $tag);
		$tag = preg_replace('/ +/', ' ', $tag);
		$tag = preg_replace('/[\x00-\x1f]|[\x7f]/', '', $tag);
		$tag = preg_replace('/^(-|\s)+/', '', $tag);
		$tag = preg_replace('/(-|\s)+$/', '', $tag);
		$tag = trim($tag);
		
		array_push($ret, $tag);
	}
	
	return $ret;
}

function getEntryVisibilityName($visibility) {
	switch (abs($visibility)) {
		case 0:
			return _text('비공개');
		case 1:
			return _text('보호');
		case 2:
			return _text('공개');
		case 3:default:
			return _text('발행');
	}
}

function getSloganById($blogid, $id) {
	global $database;
	$result = DBQuery::queryCell("SELECT slogan FROM {$database['prefix']}Entries WHERE blogid = $blogid AND id = $id");
	if (is_null($result))
		return false;
	else
		return $result;
}

function getEntryIdBySlogan($blogid, $slogan) {
	global $database;
	$result = DBQuery::queryCell("SELECT id
		FROM {$database['prefix']}Entries 
		WHERE blogid = $blogid 
			AND slogan = '".tc_escape_string($slogan)."'");
	if(!$result) return false;
	else return $result;
}
?>
