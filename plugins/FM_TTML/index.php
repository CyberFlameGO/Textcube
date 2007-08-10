<?php
function FM_TTML_format($blogid, $id, $content, $keywords = array(), $useAbsolutePath = false, $bRssMode = false) {
	global $service;
	$path = ROOT . "/attach/$blogid";
	$url = "{$service['path']}/attach/$blogid";
	$view = FM_TTML_bindAttachments($id, $path, $url, $content, $useAbsolutePath, $bRssMode);
	if (is_array($keywords)) $view = FM_TTML_bindKeywords($keywords, $view);
	$view = FM_TTML_bindTags($id, $view);
	return $view;
}

function FM_TTML_summary($blogid, $id, $content, $keywords = array(), $useAbsolutePath = false) {
	global $blog;
	$view = FM_TTML_format($blogid, $id, $content, $keywords, $useAbsolutePath, true);
	if (!$blog['publishWholeOnRSS']) $view = UTF8::lessen(removeAllTags(stripHTML($view)), 255);
	return $view;
}

////////////////////////////////////////////////////////////////////////////////

function FM_TTML_bindTags($id, $content) {
	for ($no = 0; (($start = strpos($content, '[#M_')) !== false) && (($end = strpos($content, '_M#]', $start + 4)) !== false); $no++) {
		$prefix = substr($content, 0, $start);
		list($more, $less, $full) = explode('|', substr($content, $start + 4, $end - $start - 4), 3);
		if (strlen($more) == 0) $more = 'more..';
		if (strlen($less) == 0) $less = 'less..';
		$more2 = htmlspecialchars(str_replace("\"", "&quot;", str_replace("'", "&#39;", $more)));
		$less2 = htmlspecialchars(str_replace("\"", "&quot;", str_replace("'", "&#39;", $less)));
		$postfix = substr($content, $end + 4);
		$content = $prefix;
		if (defined('__TEXTCUBE_MOBILE__')) {
			$content .= "<div>[$more | $less]<br />$full</div>";
		} else {
			$content .= "<p id=\"more{$id}_$no\" class=\"moreless_fold\"><span style=\"cursor: pointer;\" onclick=\"toggleMoreLess(this, '{$id}_$no','$more2','$less2'); return false;\">$more</span></p><div id=\"content{$id}_$no\" class=\"moreless_content\" style=\"display: none;\">$full</div>";
		}
		$content .= $postfix;
	}
	return $content;
}

function FM_TTML_bindKeywords($keywords, $content) {
	if(empty($keywords)) return $content;

	// split all HTML/TTML tags and CDATAs
	$result = preg_split('@(
		# <ns:elem or </ns:elem
		</?([A-Za-z0-9-:]+)
		# whitespaces preceding attributes
		(?:\s+
			(?:
				# quotations (e.g. ="blah" or =`blah`)
				=\s*([\'"`]).*?\3
			|
				# =nospacehere or raw character (e.g. ! or =blah)
				[^>]+
			)*
		)?
		# end of element
		>
		# redundant closure need to keep num of capturing patterns to 4
		()
	|
		# TTML pattern
		\[\#\#_.*?_\#\#]
	)@x', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

	$pattern = array();
	foreach ($keywords as $keyword)
		$pattern[] = preg_quote($keyword, '/');
	$pattern = '/(?<![a-zA-Z\x80-\xff])(?:'.implode('|',$pattern).')/e'; // 대소문자 구별 및 키워드의 단어 첫머리 처리

	// list of unbindable & (always) singleton elements
	$unbindables = array('a', 'object', 'applet', 'select', 'option', 'optgroup', 'textarea',
		'button', 'isindex', 'title', 'meta', 'base', 'link', 'style', 'head', 'script', 'embed',
		'address', 'pre', 'param');
	$singletons = array('br', 'hr', 'img', 'input');

	$stack = array(); // outer element first, inner element last
	$buf = '';
	$i = 0;
	$bindable = true;
	while (true) {
		if ($bindable) {
			$buf .= preg_replace($pattern, "fireEvent('BindKeyword', '\\0')", $result[$i]);
		} else {
			$buf .= $result[$i];
		}

		if (++$i >= count($result)) break;
		if ($result[$i]{0} == '<') {
			// now we have delimeter pattern from $result[$i] to $result[$i+3]
			$tagname = strtolower($result[$i+1]);
			if ($result[$i]{1} == '/') {
				// closing tag
				$index = array_search($tagname, $stack);
				if ($index === false) {
					// if there is no opening tag
					//$stack = array();
				} else {
					// if there is any opening tag, close it and pops them from the stack
					array_splice($stack, 0, $index + 1);
					$bindable = (count(array_intersect($stack, $unbindables)) > 0 ? false : true);
				}
			} else {
				// opening tag or empty element (singleton) tag
				// note: empty element tag always endswith '/>', without whitespace between '/' and '>' (XML spec 3.1)
				if (substr($result[$i], -2) != '/>' && !in_array($tagname, $singletons)) {
					// ... is not singleton tag.
					array_unshift($stack, $tagname);
					$bindable = ($bindable && !in_array($tagname, $unbindables));
				}
			}
			$buf .= $result[$i];
			$i += 4;
		} else {
			// TTML pattern
			$buf .= $result[$i++];
		}
	}

	return $buf;
}

function FM_TTML_bindAttachments($entryId, $folderPath, $folderURL, $content, $useAbsolutePath = false, $bRssMode = false) {
	global $service, $hostURL, $blogURL;
	$blogid = getBlogId();
	$view = str_replace('[##_ATTACH_PATH_##]', ($useAbsolutePath ? "$hostURL{$service['path']}/attach/$blogid" : $folderURL), $content);
	$view = str_replace('http://tt_attach_path/', ($useAbsolutePath ? "$hostURL{$service['path']}/attach/$blogid/" : ($folderURL . '/')), $view);
	$count = 0;
	$bWritedGalleryJS = false;
	
	while ((($start = strpos($view, '[##_')) !== false) && (($end = strpos($view, '_##]', $start + 4)) !== false)) {
		$count++;
		$attributes = explode('|', substr($view, $start + 4, $end - $start - 4));
		$prefix = '';
		$buf = '';
		if ($attributes[0] == 'Gallery') {
			if (count($attributes) % 2 == 1)
				array_pop($attributes);
			if (defined('__TEXTCUBE_MOBILE__')) {
				$images = array_slice($attributes, 1, count($attributes) - 2);
				for ($i = 0; $i < count($images); $i++) {
					if (!empty($images[$i])) {
						if ($i % 2 == 0)
							$buf .= '<div align="center">' . FM_TTML_getAttachmentBinder($images[$i], '', $folderPath, $folderURL, 1, $useAbsolutePath, $bRssMode) . '</div>';
						else if (strlen($images[$i]) > 0)
							$buf .= "<div align=\"center\">$images[$i]</div>";
					}
				}
			} else if ($bRssMode == true) {
				$items = array();
				for ($i = 1; $i < sizeof($attributes) - 2; $i += 2)
					array_push($items, array($attributes[$i], $attributes[$i + 1]));
				$galleryAttributes = getAttributesFromString($attributes[sizeof($attributes) - 1]);
				
				$images = array_slice($attributes, 1, count($attributes) - 2);
				for ($i = 0; $i < count($images); $i++) {
					if (!empty($images[$i])) {
						if ($i % 2 == 0) {
							$setWidth = $setHeight = 0;
							if (list($width, $height) = @getimagesize("$folderPath/{$images[$i]}")) {
								
								$setWidth = $width;
								$setHeight = $height;
								if (isset($galleryAttributes['width']) && $galleryAttributes['width'] < $setWidth) {
									$setHeight = $setHeight * $galleryAttributes['width'] / $setWidth;
									$setWidth = $galleryAttributes['width'];
								}
								if (isset($galleryAttributes['height']) && $galleryAttributes['height'] < $setHeight) {
									$setWidth = $setWidth * $galleryAttributes['height'] / $setHeight;
									$setHeight = $galleryAttributes['height'];
								}
								
								if (intval($setWidth > 0) && intval($setHeight) > 0)
									$tempProperty = 'width="' . intval($setWidth) . '" height="' . intval($setHeight) . '"';
								else
									$tempProperty = '';
								
								$buf .= '<div align="center">' . FM_TTML_getAttachmentBinder($images[$i], $tempProperty, $folderPath, $folderURL, 1, $useAbsolutePath, $bRssMode) . '</div>';
							}
						} else if (strlen($images[$i]) > 0) {
							$buf .= "<div align=\"center\">{$images[$i]}</div>";
						}
					}
				}
			} else {
				$id = "gallery$entryId$count";
				$cssId = "tt-gallery-$entryId-$count";
				$contentWidth = getContentWidth();
				
				$items = array();
				for ($i = 1; $i < sizeof($attributes) - 2; $i += 2)
					array_push($items, array($attributes[$i], $attributes[$i + 1]));
				$galleryAttributes = getAttributesFromString($attributes[sizeof($attributes) - 1]);
				
				if (!isset($galleryAttributes['width']))
					$galleryAttributes['width'] = $contentWidth;
				if (!isset($galleryAttributes['height']))
					$galleryAttributes['height'] = 3/4 * $galleryAttributes['width'];
				
				if ($galleryAttributes['width'] > $contentWidth) {
					$galleryAttributes['height'] = $galleryAttributes['height'] * $contentWidth / $galleryAttributes['width'];
					$galleryAttributes['width'] = $contentWidth;
				}
				
				if (($useAbsolutePath == true) && ($bWritedGalleryJS == false)) {
					$bWritedGalleryJS = true;
					$buf .= printScript('gallery.js');
				}
				$buf .= CRLF . '<div id="' . $cssId . '" class="tt-gallery-box">' . CRLF;
				$buf .= '	<script type="text/javascript">' . CRLF;
				$buf .= '		//<![CDATA[' . CRLF;
				$buf .= "			var {$id} = new TTGallery(\"{$cssId}\");" . CRLF;
				$buf .= "			{$id}.prevText = \"" . _text('이전 이미지 보기') . "\"; " . CRLF;
				$buf .= "			{$id}.nextText = \"" . _text('다음 이미지 보기') . "\"; " . CRLF;
				$buf .= "			{$id}.enlargeText = \"" . _text('원본 크기로 보기') . "\"; " . CRLF;
				$buf .= "			{$id}.altText = \"" . _text('갤러리 이미지') . "\"; " . CRLF;

				foreach ($items as $item) {
					$setWidth = $setHeight = 0;
					if (list($width, $height) = @getimagesize("$folderPath/$item[0]")) {
						$setWidth = $width;
						$setHeight = $height;
						if (isset($galleryAttributes['width']) && $galleryAttributes['width'] < $setWidth) {
							$setHeight = $setHeight * $galleryAttributes['width'] / $setWidth;
							$setWidth = $galleryAttributes['width'];
						}
						if (isset($galleryAttributes['height']) && $galleryAttributes['height'] < $setHeight) {
							$setWidth = $setWidth * $galleryAttributes['height'] / $setHeight;
							$setHeight = $galleryAttributes['height'];
						}
						$item[1] = str_replace("'", '&#39;', $item[1]);
						$buf .= $id . '.appendImage("' . ($useAbsolutePath ? "$hostURL{$service['path']}/attach/$blogid/$item[0]" : "$folderURL/$item[0]") . '", "' . htmlspecialchars($item[1]) . '", ' . intval($setWidth) . ', ' . intval($setHeight) . ");";
					}
				}
				$buf .= "			{$id}.show();" . CRLF;
				$buf .= "		//]]>" . CRLF;
				$buf .= '	</script>' . CRLF;
				$buf .= '	<noscript>' . CRLF;
				foreach ($items as $item) {
				$setWidth = $setHeight = 0;
					if (list($width, $height) = @getimagesize("$folderPath/$item[0]")) {
						$setWidth = $width;
						$setHeight = $height;
						if (isset($galleryAttributes['width']) && $galleryAttributes['width'] < $setWidth) {
							$setHeight = $setHeight * $galleryAttributes['width'] / $setWidth;
							$setWidth = $galleryAttributes['width'];
						}
						if (isset($galleryAttributes['height']) && $galleryAttributes['height'] < $setHeight) {
							$setWidth = $setWidth * $galleryAttributes['height'] / $setHeight;
							$setHeight = $galleryAttributes['height'];
						}
					
						$buf .= '<div class="imageblock center" style="text-align: center; clear: both;">';
						if ($useAbsolutePath)
							$buf .= '		<img src="' . $hostURL . $service['path'] . "/attach/" . $blogid . "/" . $item[0] . '" width="' . intval($setWidth) . '" height="' . intval($setHeight) . '" alt="' . _text('사용자 삽입 이미지') . '" />' . CRLF;
						else
							$buf .= '		<img src="' . $folderURL . "/" . $item[0] . '" width="' . intval($setWidth) . '" height="' . intval($setHeight) . '" alt="' . _text('사용자 삽입 이미지') . '" />' . CRLF;
						if(!empty($item[1]))
							$buf .= '		<p class="cap1">'. $item[1] .'</p>' . CRLF;
						$buf .= '</div>';
					}
				}
				$buf .= '	</noscript>' . CRLF;
				$buf .= '</div>' . CRLF;
			}
		} else if ($attributes[0] == 'iMazing') {
			if (defined('__TEXTCUBE_MOBILE__')  || ($bRssMode == true)) {
				$images = array_slice($attributes, 1, count($attributes) - 3);
				for ($i = 0; $i < count($images); $i += 2) {
					if (!empty($images[$i]))
						$buf .= '<div>' . FM_TTML_getAttachmentBinder($images[$i], '', $folderPath, $folderURL, 1, $useAbsolutePath) . '</div>';
				}
				$buf .= $attributes[count($attributes) - 1];
			} else {
				$params = getAttributesFromString($attributes[sizeof($attributes) - 2]);
				$id = $entryId . $count;
				$imgs = array_slice($attributes, 1, count($attributes) - 3);
				$imgStr = '';
				for ($i = 0; $i < count($imgs); $i += 2) {
					if ($imgs[$i] != '') {
						$imgStr .= $service['path'] . '/attach/' . $blogid . '/' . $imgs[$i];
						if ($i < (count($imgs) - 2))
							$imgStr .= '*!';
					}
				}
				if (!empty($attributes[count($attributes) - 1])) {
					$caption = '<p class="cap1">' . $attributes[count($attributes) - 1] . '</p>';
				} else {
					$caption = '';
				}
				$buf .= '<div style="clear: both; text-align: center"><img src="' . ($useAbsolutePath ? $hostURL : $service['path']) . '/image/gallery/gallery_enlarge.gif" alt="' . _text('확대') . '" style="cursor:pointer" onclick="openFullScreen(\'' . $service['path'] . '/script/gallery/iMazing/embed.php?d=' . urlencode($id) . '&f=' . urlencode($params['frame']) . '&t=' . urlencode($params['transition']) . '&n=' . urlencode($params['navigation']) . '&si=' . urlencode($params['slideshowInterval']) . '&p=' . urlencode($params['page']) . '&a=' . urlencode($params['align']) . '&o=' . $blogid . '&i=' . $imgStr . '\',\'' . htmlspecialchars(str_replace("'", "&#39;", $attributes[count($attributes) - 1])) . '\',\'' . $service['path'] . '\')" />';
				$buf .= '<table>';
				$buf .= '<tr>';
				$buf .= '<td width="' . $params['width'] . '" height="' . $params['height'] . '">';
				$buf .= '<div id="iMazingContainer'.$id.'" class="iMazingContainer" style="width:'.$params['width'].'px; height:'.$params['height'].'px;"></div><script type="text/javascript">iMazing' . $id . 'Str = getEmbedCode(\'' . $service['path'] . '/script/gallery/iMazing/main.swf\',\'100%\',\'100%\',\'iMazing' . $id . '\',\'#FFFFFF\',"image=' . $imgStr . '&amp;frame=' . $params['frame'] . '&amp;transition=' . $params['transition'] . '&amp;navigation=' . $params['navigation'] . '&amp;slideshowInterval=' . $params['slideshowInterval'] . '&amp;page=' . $params['page'] . '&amp;align=' . $params['align'] . '&amp;skinPath=' . $service['path'] . '/script/gallery/iMazing/&amp;","false"); writeCode(iMazing' . $id . 'Str, "iMazingContainer'.$id.'");</script><noscript>';
				for ($i = 0; $i < count($imgs); $i += 2)
				    $buf .= '<img src="'.($useAbsolutePath ? $hostURL : $service['path']).'/attach/'.$blogid.'/'.$imgs[$i].'" alt="" />';
				$buf .= '</noscript>';
				$buf .= '</td>';
				$buf .= '</tr>';
				$buf .= '</table>' . $caption . '</div>';
			}
		} else if ($attributes[0] == 'Jukebox') {
			if (defined('__TEXTCUBE_MOBILE__')) {
				$sounds = array_slice($attributes, 1, count($attributes) - 3);
				for ($i = 0; $i < count($sounds); $i += 2) {
					if (!empty($sounds[$i]))
						echo "<a href=\"$folderURL/$sounds[$i]\">$sounds[$i]</a><br />";
				}
			} else {
				$params = getAttributesFromString($attributes[sizeof($attributes) - 2]);
				foreach ($params as $key => $value) {
					if ($key == 'autoPlay') {
						unset($params['autoplay']);
						$params['autoplay'] = $value;
					}
				}
				if ($params['visible'] == 1) {
					$width = '250px';
					$height = '27px';
				} else {
					$width = '0px';
					$height = '0px';
				}
				$id = $entryId . $count;
				$imgs = array_slice($attributes, 1, count($attributes) - 3);
				$imgStr = '';
				for ($i = 0; $i < count($imgs); $i++) {
					if ($imgs[$i] == '')
						continue;
					if ($i % 2 == 1) {
						$imgStr .= urlencode($imgs[$i]) . '_*';
						continue;
					} else {
						if ($i < (count($imgs) - 1))
							$imgStr .= "{$service['path']}/attach/$blogid/" . urlencode($imgs[$i]) . '*!';
					}
				}
				if (!empty($attributes[count($attributes) - 1])) {
					$caption = '<div class="cap1" style="text-align: center">' . $attributes[count($attributes) - 1] . '</div>';
				} else {
					$caption = '';
				}
				$buf = '<center>';
				$buf .= '<div id="jukeBox' . $id . 'Div" style="width:' . $width . '; height:' . $height . ';"><div id="jukeBoxContainer'.$id.'" style="width:' . $width . '; height:' . $height . ';"></div>';
				$buf .= '<script type="text/javascript">writeCode(getEmbedCode(\'' . $service['path'] . '/script/jukebox/flash/main.swf\',\'100%\',\'100%\',\'jukeBox' . $id . 'Flash\',\'#FFFFFF\',"sounds=' . $imgStr . '&amp;autoplay=' . $params['autoplay'] . '&amp;visible=' . $params['visible'] . '&amp;id=' . $id . '","false"), "jukeBoxContainer'.$id.'")</script><noscript>';
				for ($i = 0; $i < count($imgs); $i++) {
					if ($i % 2 == 0)
						$buf .= '<a href="'.($useAbsolutePath ? $hostURL : $service['path']).'/attach/'.$blogid.'/'.$imgs[$i].'">';
					else
						$buf .= htmlspecialchars($imgs[$i]).'</a><br/>';
				}
				$buf .= '</noscript>';
				$buf .= '</div>' . $caption . '</center>';
			}
		} else {
			$contentWidth = getContentWidth();

			switch (count($attributes)) {
				case 4:
					list($newProperty, $onclickFlag) = FM_TTML_createNewProperty($attributes[1], $contentWidth, $attributes[2]);

					if (defined('__TEXTCUBE_MOBILE__')) {
						$buf = '<div>' . FM_TTML_getAttachmentBinder($attributes[1], $newProperty, $folderPath, $folderURL, 1, $useAbsolutePath) . "</div><div>$attributes[3]</div>";
					} else {
						if (trim($attributes[3]) == '') {
							$caption = '';
						} else {
							$caption = '<p class="cap1">' . $attributes[3] . '</p>';
						}
						switch ($attributes[0]) {
							case '1L':
								$prefix = '<div class="imageblock left" style="float: left; margin-right: 10px;">';
								break;
							case '1R':
								$prefix = '<div class="imageblock right" style="float: right; margin-left: 10px;">';
								break;
							case '1C':
							default:
								$prefix = '<div class="imageblock center" style="text-align: center; clear: both;">';
								break;
						}
						$buf = $prefix . FM_TTML_getAttachmentBinder($attributes[1], $newProperty, $folderPath, $folderURL, 1, $useAbsolutePath, $bRssMode, $onclickFlag) . $caption . '</div>';
					}
					break;
				case 7:
					$eachImageWidth = floor(($contentWidth - 5 * 3) / 2);
					list($newProperty1, $onclickFlag1) = FM_TTML_createNewProperty($attributes[1], $eachImageWidth, $attributes[2]);
					list($newProperty2, $onclickFlag2) = FM_TTML_createNewProperty($attributes[4], $eachImageWidth, $attributes[5]);
					if (defined('__TEXTCUBE_MOBILE__')) {
						$buf = '<div>' . FM_TTML_getAttachmentBinder($attributes[1], $newProperty1, $folderPath, $folderURL, 1, $useAbsolutePath, $bRssMode) . "</div><div>$attributes[3]</div>";
						$buf .= '<div>' . FM_TTML_getAttachmentBinder($attributes[4], $newProperty2, $folderPath, $folderURL, 1, $useAbsolutePath, $bRssMode) . "</div><div>$attributes[6]</div>";
					} else {
						$cap1 = strlen(trim($attributes[3])) > 0 ? '<p class="cap1">' . $attributes[3] . '</p>' : '';
						$cap2 = strlen(trim($attributes[6])) > 0 ? '<p class="cap1">' . $attributes[6] . '</p>' : '';
						$buf = '<div class="imageblock dual" style="text-align: center;"><table cellspacing="5" cellpadding="0" border="0" style="margin: 0 auto;"><tr><td>'
							. FM_TTML_getAttachmentBinder($attributes[1], $newProperty1, $folderPath, $folderURL, 2, $useAbsolutePath, $bRssMode, $onclickFlag1) . $cap1 . '</td><td>'
							. FM_TTML_getAttachmentBinder($attributes[4], $newProperty2, $folderPath, $folderURL, 2, $useAbsolutePath, $bRssMode, $onclickFlag2) . $cap2 . '</td></tr></table></div>';
					}
					break;
				case 10:
					$eachImageWidth = floor(($contentWidth - 5 * 4) / 3);
					list($newProperty1, $onclickFlag1) = FM_TTML_createNewProperty($attributes[1], $eachImageWidth, $attributes[2]);
					list($newProperty2, $onclickFlag2) = FM_TTML_createNewProperty($attributes[4], $eachImageWidth, $attributes[5]);
					list($newProperty3, $onclickFlag3) = FM_TTML_createNewProperty($attributes[7], $eachImageWidth, $attributes[8]);
					if (defined('__TEXTCUBE_MOBILE__')) {
						$buf = '<div>' . FM_TTML_getAttachmentBinder($attributes[1], $newProperty1, $folderPath, $folderURL, 1, $useAbsolutePath, $bRssMode) . "</div><div>$attributes[3]</div>";
						$buf .= '<div>' . FM_TTML_getAttachmentBinder($attributes[4], $newProperty2, $folderPath, $folderURL, 1, $useAbsolutePath, $bRssMode) . "</div><div>$attributes[6]</div>";
						$buf .= '<div>' . FM_TTML_getAttachmentBinder($attributes[7],$newProperty3, $folderPath, $folderURL, 1, $useAbsolutePath, $bRssMode) . "</div><div>$attributes[9]</div>";
					} else {
						$cap1 = strlen(trim($attributes[3])) > 0 ? '<p class="cap1">' . $attributes[3] . '</p>' : '';
						$cap2 = strlen(trim($attributes[6])) > 0 ? '<p class="cap1">' . $attributes[6] . '</p>' : '';
						$cap3 = strlen(trim($attributes[9])) > 0 ? '<p class="cap1">' . $attributes[9] . '</p>' : '';
						$buf = '<div class="imageblock triple" style="text-align: center"><table cellspacing="5" cellpadding="0" border="0" style="margin: 0 auto;"><tr><td>'
							. FM_TTML_getAttachmentBinder($attributes[1], $newProperty1, $folderPath, $folderURL, 3, $useAbsolutePath, $bRssMode, $onclickFlag1) . $cap1 . '</td><td>'
							. FM_TTML_getAttachmentBinder($attributes[4], $newProperty2, $folderPath, $folderURL, 3, $useAbsolutePath, $bRssMode, $onclickFlag2) . $cap2 . '</td><td>'
							. FM_TTML_getAttachmentBinder($attributes[7], $newProperty3, $folderPath, $folderURL, 3, $useAbsolutePath, $bRssMode, $onclickFlag3) . $cap3 . '</td></tr></table></div>';
					}
					break;
				// 어디에도 해당되지 않을 경우 임시 태그를 되살림.
				default:
					$buf = '[###_###_###_' . implode('|', $attributes) . '_###_###_###]';
					break;
			}
		}
		$view = substr($view, 0, $start) . $buf . substr($view, $end + 4);
	}
	
	$view = preg_replace(array("@\[###_###_###_@", "@_###_###_###\]@"), array('[##_', '_##]'), $view);
	return $view;
}

function FM_TTML_getAttachmentBinder($filename, $property, $folderPath, $folderURL, $imageBlocks = 1, $useAbsolutePath = false, $bRssMode = false, $onclickFlag=false) {
	global $database, $skinSetting, $service, $blogURL, $hostURL, $serviceURL;
	$blogid = getBlogId();
	$path = "$folderPath/$filename";
	if ($useAbsolutePath)
		$url = "$serviceURL/attach/$blogid/$filename";
	else
		$url = "$folderURL/$filename";
	$fileInfo = getAttachmentByOnlyName($blogid, $filename);
	switch (getFileExtension($filename)) {
		case 'jpg':case 'jpeg':case 'gif':case 'png':case 'bmp':
			$bPassing = false;
			if (defined('__TEXTCUBE_MOBILE__')) {
				if (!is_null(getBlogSetting("resamplingDefault"))) {
					$waterMarkOn = getBlogSetting("waterMarkDefault", "no");
					$exist = preg_match('/class="tt-watermark"/i', $property);
					if (($waterMarkOn == 'yes') && ($exist == 1)) $bPassing = true;
				}

				if ($bPassing == false)
					return fireEvent('ViewAttachedImageMobile', "<img src=\"$blogURL/imageResizer/?f=" . urlencode($filename) . "\" alt=\"\" />", $path);
			}
			/*if ($bRssMode == true) {
				$property = str_replace('&quot;', '"', $property);
				return fireEvent('ViewAttachedImage', "<img src=\"$url\" $property/>", $path);
			} else {*/
			{
				if (($onclickFlag == true) && ($bRssMode == false) && ($bPassing == false)) {
					$imageStr = '<img src="'.$url.'" '.$property.' style="cursor: pointer;" onclick="open_img(\''.$url.'\')" />';
				} else {
					$imageStr = '<img src="'.$url.'" '.$property.' />';
				}

				return fireEvent('ViewAttachedImage', $imageStr, $path);
			}
			break;
		case 'swf':
			$id = md5($url) . rand(1, 10000);
			if (($useAbsolutePath) && (strncasecmp($url, 'http://', 7) == 0)) $url = substr($url, 7);
			return "<span id=\"$id\"><script type=\"text/javascript\">writeCode(getEmbedCode('$url','300','400','$id','#FFFFFF',''), \"$id\");</script></span>";
			break;
		case 'wmv':case 'avi':case 'asf':case 'mpg':case 'mpeg':
			$id = md5($url) . rand(1, 10000);
			if (($useAbsolutePath) && (strncasecmp($url, 'http://', 7) == 0)) $url = substr($url, 7);
			return "<span id=\"$id\"><script type=\"text/javascript\">writeCode('<embed $property autostart=\"0\" src=\"$url\"></embed>', \"$id\")</script></span>";
			break;
		case 'mp3':case 'mp2':case 'wma':case 'wav':case 'mid':case 'midi':
			$id = md5($url) . rand(1, 10000);
			if (($useAbsolutePath) && (strncasecmp($url, 'http://', 7) == 0)) $url = substr($url, 7);
			return "<span id=\"$id\"><script type=\"text/javascript\">writeCode('<embed $property autostart=\"0\" height=\"45\" src=\"$url\"></embed>', \"$id\")</script></span>";
			break;
		case 'mov':
			$id = md5($url) . rand(1, 10000);
			return "<span id=\"$id\"><script type=\"text/javascript\">writeCode(" . '\'<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" width="400px" height="300px"><param name="src" value="' . $url . '" /><param name="controller" value="true" /><param name="pluginspage" value="http://www.apple.com/QuickTime/download/" /><!--[if !IE]> <--><object type="video/quicktime" data="' . $url . '" width="400px" height="300px" class="mov"><param name="controller" value="true" /><param name="pluginspage" value="http://www.apple.com/QuickTime/download/" /></object><!--> <![endif]--></object>\'' . ", \"$id\")</script></span>";
			break;
		default:
			if (file_exists(ROOT . '/image/extension/' . getFileExtension($fileInfo['label']) . '.gif')) {
				return '<a class="extensionIcon" href="' . ($useAbsolutePath ? $hostURL : '') . $blogURL . '/attachment/' . $filename . '">' . fireEvent('ViewAttachedFileExtension', '<img src="' . ($useAbsolutePath ? $hostURL : '') . $service['path'] . '/image/extension/' . getFileExtension($fileInfo['label']) . '.gif" />') . ' ' . htmlspecialchars($fileInfo['label']) . '</a>';
			} else {
				return '<a class="extensionIcon" href="' . ($useAbsolutePath ? $hostURL : '') . $blogURL . '/attachment/' . $filename . '">' . fireEvent('ViewAttachedFileExtension', '<img src="' . ($useAbsolutePath ? $hostURL : '') . $service['path'] . '/image/extension/unknown.gif" />') . ' ' . htmlspecialchars($fileInfo['label']) . '</a>';
			}
			break;
	}
}

function FM_TTML_createNewProperty($filename, $imageWidth, $property) {
	$blogid = getBlogId();
	requireComponent('Textcube.Function.Image');
	if (in_array(Image::getImageType(ROOT."/attach/$blogid/$filename"), array('gif', 'png', 'jpg', 'bmp')))
		return Image::resizeImageToContent($property, ROOT."/attach/$blogid/$filename", $imageWidth);
	else
		return array($property, false);
}
?>
