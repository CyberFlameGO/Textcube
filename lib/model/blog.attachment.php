<?php 
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

global $__gCacheAttachment;
$__gCacheAttachment = array();

function getAttachments($blogid, $parent, $orderBy = null, $sort='ASC') {
	global $database, $__gCacheAttachment;
	if(isset($__gCacheAttachment) && !empty($__gCacheAttachment)) {
		if($result = getAttachmentsFromCache($blogid, $parent, 'parent')) {
			return $result;
		}
	}
	$attachments = array();
	if ($result = DBQuery::queryAll("SELECT * 
		FROM {$database['prefix']}Attachments 
		WHERE blogid = $blogid AND parent = $parent ".( is_null($orderBy ) ? '' : "ORDER BY $orderBy $sort"))) {
		foreach($result as $attachment) {
			array_push($attachments, $attachment);
			array_push($__gCacheAttachment, $attachment);
		}
	}
	return $attachments;
}

function getAttachmentsFromCache($blogid, $value, $filter = 'parent') {
	global $__gCacheAttachment;
	$result = array();
	foreach($__gCacheAttachment as $id => $info) {
		$row = array_search($value, $info);
		if($row) array_push($result,$__gCacheAttachment[$id]);
	}
	return $result;
}

function getAttachmentFromCache($blogid, $value, $filter = 'name') {
	global $__gCacheAttachment;
	foreach($__gCacheAttachment as $id => $info) {
		$row = array_search($value, $info);
		//if($row && $row == $filter) return $__gCacheAttachment[$id];
		if($row) return $__gCacheAttachment[$id];
	}
	return false;
}

function getAttachmentByName($blogid, $parent, $name) {
	global $database, $__gCacheAttachment;
	if(!isset($__gCacheAttachment))
		getAttachments($blogid, $parent);
	if($result = getAttachmentFromCache($blogid, $name, 'name') && $result['parent'] == $parent) {
		return $result;
	}
	return false;
}

function getAttachmentByOnlyName($blogid, $name) {
	global $database, $__gCacheAttachment;
	if(!empty($__gCacheAttachment) && $result = getAttachmentFromCache($blogid, $name, 'name')) {
		return $result;
	} else {
		$newAttachment = DBQuery::queryRow("SELECT * FROM {$database['prefix']}Attachments 
			WHERE blogid = $blogid AND name = '".tc_escape_string($name)."'");
		array_push($__gCacheAttachment,$newAttachment);
		return $newAttachment;
	}
}

function getAttachmentByLabel($blogid, $parent, $label) {
	global $database;
	if ($parent === false)
		$parent = 0;
	$label = tc_escape_string($label);
	return DBQuery::queryRow("SELECT * FROM {$database['prefix']}Attachments WHERE blogid = $blogid AND parent = $parent AND label = '$label'");
}

function getAttachmentSize($blogid=null, $parent = null) {
	global $database;	
	$blogidStr = '';
	$parentStr = '';	

	if (!empty($blogid))
		$blogidStr = "blogid = $blogid ";
	if ($parent == 0 || !empty($parent))
		$parentStr = "AND parent = $parent";
	return DBQuery::queryCell("SELECT sum(size) FROM {$database['prefix']}Attachments WHERE $blogidStr $parentStr");
}

function getAttachmentSizeLabel($blogid=null, $parent = null) {
	//return number_format(ceil(getAttachmentSize($blogid,$parent)/1024)).' / '.number_format(ceil(getAttachmentSize($blogid)/1024)).' (KByte)';
	return number_format(ceil(getAttachmentSize($blogid,$parent)/1024)).' (KByte)';
}

function addAttachment($blogid, $parent, $file) {
	global $database;	
	if (empty($file['name']) || ($file['error'] != 0))
		return false;
	$filename = tc_escape_string($file['name']);
	if (DBQuery::queryCell("SELECT count(*) 
		FROM {$database['prefix']}Attachments 
		WHERE blogid=$blogid 
			AND parent=$parent 
			AND label='$filename'")>0) {
		return false;
	}
	$attachment = array();
	$attachment['parent'] = $parent ? $parent : 0;
	$attachment['label'] = Path::getBaseName($file['name']);
	$attachment['size'] = $file['size'];
	$extension = getFileExtension($attachment['label']);
	switch (strtolower($extension)) {
		case 'exe':case 'php':case 'sh':case 'com':case 'bat':
			$extension = 'xxx';
			break;
	}
	if ((strlen($extension) > 6) || ($extension == '')) {
		$extension = 'xxx';
	}
	$path = ROOT . "/attach/$blogid";
	if (!is_dir($path)) {
		mkdir($path);
		if (!is_dir($path))
			return false;
		@chmod($path, 0777);
	}
	do {
		$attachment['name'] = rand(1000000000, 9999999999) . ".$extension";
		$attachment['path'] = "$path/{$attachment['name']}";
	} while (file_exists($attachment['path']));
	if ($imageAttributes = @getimagesize($file['tmp_name'])) {
		$attachment['mime'] = $imageAttributes['mime'];
		$attachment['width'] = $imageAttributes[0];
		$attachment['height'] = $imageAttributes[1];
	} else {
		$attachment['mime'] = getMIMEType($extension);
		$attachment['width'] = 0;
		$attachment['height'] = 0;
	}
	if (!move_uploaded_file($file['tmp_name'], $attachment['path']))
		return false;
	@chmod($attachment['path'], 0666);
	$name = tc_escape_string($attachment['name']);
	$label = tc_escape_string(UTF8::lessenAsEncoding($attachment['label'], 64));
	$attachment['mime'] = UTF8::lessenAsEncoding($attachment['mime'], 32);
	
	$result = DBQuery::execute("insert into {$database['prefix']}Attachments values ($blogid, {$attachment['parent']}, '$name', '$label', '{$attachment['mime']}', {$attachment['size']}, {$attachment['width']}, {$attachment['height']}, UNIX_TIMESTAMP(), 0,0)");
	if (!$result) {
		@unlink($attachment['path']);
		return false;
	}
	return $attachment;
}

function deleteAttachment($blogid, $parent, $name) {
	global $database;
	requireModel('blog.rss');
	if (!Validator::filename($name)) 
		return false;
	$origname = $name;
	$name = tc_escape_string($name);
	if (DBQuery::execute("DELETE FROM {$database['prefix']}Attachments WHERE blogid = $blogid AND name = '$name'") && (mysql_affected_rows() == 1)) {
		@unlink(ROOT . "/attach/$blogid/$origname");
		clearRSS();
		return true;
	}
	return false;
}

function copyAttachments($blogid, $originalEntryId, $targetEntryId) {
	global $database;
	$path = ROOT . "/attach/$blogid";
	$attachments = getAttachments($blogid, $originalEntryId);
	if(empty($attachments)) return true;
	if(!DBQuery::queryCell("SELECT id 
		FROM {$database['prefix']}Entries
		WHERE blogid = $blogid
			AND id = $originalEntryId")) return 2; // original entry does not exists;
	if(!DBQuery::queryCell("SELECT id 
		FROM {$database['prefix']}Entries
		WHERE blogid = $blogid
			AND id = $targetEntryId")) return 3; // target entry does not exists;

	foreach($attachments as $attachment) {
		$extension = getFileExtension($attachment['label']);
		$originalPath = "$path/{$attachment['name']}";
		do {
			$attachment['name'] = rand(1000000000, 9999999999) . ".$extension";
			$attachment['path'] = "$path/{$attachment['name']}";
		} while (file_exists($attachment['path']));
		if(!copy($originalPath, $attachment['path'])) return 4; // copy failed.
		if(!DBQuery::execute("insert into {$database['prefix']}Attachments 
			values ($blogid, 
				$targetEntryId,
				'{$attachment['name']}',
				'{$attachment['label']}',
				'{$attachment['mime']}',
				{$attachment['size']},
				{$attachment['width']},
				{$attachment['height']}, 
				UNIX_TIMESTAMP(), 
				0,
				0)"))
			return false;
	}
	return true;
}
function deleteTotalAttachment($blogid) {
	requireModel('blog.rss');
	$d = dir(ROOT."/attach/$blogid");
	while($file = $d->read()) {
		if(is_file(ROOT."/attach/$blogid/$file"))
			unlink(ROOT."/attach/$blogid/$file");
	}
	rmdir(ROOT."/attach/$blogid/");
	clearRSS();
	return true;
}

function deleteAttachmentMulti($blogid, $parent, $names) {
	global $database;
	requireModel('blog.rss');
	$files = explode('!^|', $names);
	foreach ($files as $name) {
		if ($name == '')
			continue;
		if (!Validator::filename($name)) 
			continue;
		$origname = $name;
		$name = tc_escape_string($name);
		if (DBQuery::execute("DELETE FROM {$database['prefix']}Attachments WHERE blogid = $blogid AND parent = $parent AND name = '$name'") && (mysql_affected_rows() == 1)) {
			unlink(ROOT . "/attach/$blogid/$origname");
		} else {
		}
	}
	clearRSS();
	return true;
}



function deleteAttachments($blogid, $parent) {
	$attachments = getAttachments($blogid, $parent);
	foreach ($attachments as $attachment)
		deleteAttachment($blogid, $parent, $attachment['name']);
}

function downloadAttachment($name) {
	requireModel('blog.rss');
	global $database;
	$name = tc_escape_string($name);
	DBQuery::query("UPDATE {$database['prefix']}Attachments SET downloads = downloads + 1 WHERE blogid = ".getBlogId()." AND name = '$name'");
}

function setEnclosure($name, $order) {
	global $database;
	requireModel('blog.rss');
	requireModel('blog.attachment');
	$name = tc_escape_string($name);
	if (($parent = DBQuery::queryCell("SELECT parent FROM {$database['prefix']}Attachments WHERE blogid = ".getBlogId()." AND name = '$name'")) !== null) {
		DBQuery::execute("UPDATE {$database['prefix']}Attachments SET enclosure = 0 WHERE parent = $parent AND blogid = ".getBlogId());
		if ($order) {
			clearRSS();
			return DBQuery::execute("UPDATE {$database['prefix']}Attachments SET enclosure = 1 WHERE blogid = ".getBlogId()." AND name = '$name'") ? 1 : 2;
		} else
			return 0;
	} else
		return 3;
}

function getEnclosure($entry) {
	global $database;
	if ($entry < 0)
		return null;
	return DBQuery::queryCell("SELECT name FROM {$database['prefix']}Attachments WHERE parent = $entry AND enclosure = 1 AND blogid = ".getBlogId());
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val{strlen($val)-1});
    switch($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}
?>
