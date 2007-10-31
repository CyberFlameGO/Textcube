<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

/***** Modules *****/

/* Editor */
function getDefaultEditor() {
	global $editorMapping;
	reset($editorMapping);
	return getBlogSetting('defaultEditor', key($editorMapping));
}

function& getAllEditors() { global $editorMapping; return $editorMapping; }

function getEditorInfo($editor) {
	global $editorMapping;
	if (!isset($editorMapping[$editor])) {
		reset($editorMapping);
		$editor = key($editorMapping); // gives first declared (thought to be default) editor
	}
	if (isset($editorMapping[$editor]['plugin'])) {
		include_once ROOT . "/plugins/{$editorMapping[$editor]['plugin']}/index.php";
	}
	return $editorMapping[$editor];
}


/* Formatter */
// default formatter functions.
function getDefaultFormatter() {
	global $formatterMapping;
	reset($formatterMapping);
	return getBlogSetting('defaultFormatter', key($formatterMapping));
}

function& getAllFormatters() { global $formatterMapping; return $formatterMapping; }
function getFormatterInfo($formatter) {
	global $formatterMapping;
	if (!isset($formatterMapping[$formatter])) {
		reset($formatterMapping);
		$formatter = key($formatterMapping); // gives first declared (thought to be default) formatter
	}
	if (isset($formatterMapping[$formatter]['plugin'])) {
		include_once ROOT . "/plugins/{$formatterMapping[$formatter]['plugin']}/index.php";
	}
	return $formatterMapping[$formatter];
}

function formatContent($blogid, $id, $content, $formatter, $keywords = array(), $useAbsolutePath = false) {
	$info = getFormatterInfo($formatter);
	$func = (isset($info['formatfunc']) ? $info['formatfunc'] : 'FM_default_format');
	return $func($blogid, $id, $content, $keywords, $useAbsolutePath);
}

function summarizeContent($blogid, $id, $content, $formatter, $keywords = array(), $useAbsolutePath = false) {
	global $blog;
	$info = getFormatterInfo($formatter);
	$func = (isset($info['summaryfunc']) ? $info['summaryfunc'] : 'FM_default_summary');
	// summary function is responsible for shortening the content if needed
	return $func($blogid, $id, $content, $keywords, $useAbsolutePath);
}

function FM_default_format($blogid, $id, $content, $keywords = array(), $useAbsolutePath = false) {
	global $service, $hostURL;
	$basepath = ($useAbsolutePath ? $hostURL : '');
	return str_replace('[##_ATTACH_PATH_##]', "$basepath{$service['path']}/attach/$blogid", $content);
}

function FM_default_summary($blogid, $id, $content, $keywords = array(), $useAbsolutePath = false) {
	global $blog;
	if (!$blog['publishWholeOnRSS']) $content = UTF8::lessen(removeAllTags(stripHTML($content)), 255);
	return $content;
}
?>
