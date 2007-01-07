<?php
/// Copyright (c) 2004-2007, Tatter & Company / Tatter & Friends.
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

function escapeJSInAttribute($str) {
	return htmlspecialchars(str_replace(array('\\', '\r', '\n', '\''), array('\\\\', '\\r', '\\n', '\\\''), $str));
}

function escapeJSInCData($str) {
	return preg_replace(array('/</', '/>/', '/\r*\n|\r/'), array('\x3C', '\x3E', '\\\\$0'), addslashes($str));
}

function escapeCData($str) {
	return str_replace(']]>', ']]&gt;', $str);
}

function filterJavaScript($str, $removeScript = true) {
	if ($removeScript) {
		preg_match_all('/<.*?>/s', $str, $matches);
		foreach ($matches[0] as $tag) {
			$strippedTag = $tag;
			preg_match_all('/\s+on\w+?\s*?=\s*?("|\').*?\1/s', $strippedTag, $subMatches);
			foreach ($subMatches[0] as $attribute)
				$strippedTag = str_replace($attribute, '', $strippedTag);
			preg_match_all('/\s+on\w+?\s*?=\s*?[^\s>]*/s', $tag, $subMatches);
			foreach ($subMatches[0] as $attribute)
				$strippedTag = str_replace($attribute, '', $strippedTag);
			$str = str_replace($tag, $strippedTag, $str);
		}
		$str = preg_replace('/<\/?iframe.*?>/si', '', $str);
		$str = preg_replace('/<script.*?<\/script>/si', '', $str);
		$str = preg_replace('/<object.*?type=["\']?text\/x-scriptlet["\']?.*?>(.*?<\/object>)?/si', '', $str);
		$str = preg_replace('/j\s*?a\s*?v\s*?a\s*?s\s*?c\s*?r\s*?i\s*?p\s*?t\s*?:/si', '', $str);
	} else
		$str = str_replace('<script', '<script defer="defer"', $str);
	return $str;
}

function checkAjaxRequest() {
	//if (preg_match("/__T__=[0-9]{13}/", $_SERVER['QUERY_STRING']))
		return true;
	//else
	//	return false;
}	
?>
