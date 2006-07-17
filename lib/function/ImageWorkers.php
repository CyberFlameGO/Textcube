<?php
// ������ �ʴ� �Լ��̳� ������ ���� ���ܵ�.
function isLarge($target, $maxX, $maxY) {
	$size = getImageSize($target);
	$sx = $size[0];
	$sy = $size[1];
	if (strpos($maxX, "%") && strpos($maxY, "%")) {
		return false;
	}
	if (($sx > $maxX) || ($sy > $maxY)) {
		return true;
	} else {
		return false;
	}
}

// ������ �ʴ� �Լ��̳� ������ ���� ���ܵ�.
function resizing($maxX, $maxY, $src_file, $tag_file) {
	list($width, $height, $type, $attr) = getimagesize($src_file);
	if ($type == 1) {
		$src_img = imagecreatefromgif($src_file);
	} else if ($type == 2) {
		$src_img = imagecreatefromjpeg($src_file);
	} else if ($type == 3) {
		$src_img = imagecreatefrompng($src_file);
	}
	$sx = imagesx($src_img);
	$sy = imagesy($src_img);
	$xratio = $sx / $maxX;
	$yratio = $sy / $maxY;
	$ratio = max($xratio, $yratio);
	$targ_Y = $sy / $ratio;
	$targ_X = $sx / $ratio;
	$dst_img = ImageCreateTrueColor($targ_X, $targ_Y);
	$colorSetting = ImageColorAllocate($dst_img, 255, 255, 255);
	ImageFilledRectangle($dst_img, 0, 0, $maxX, $maxY, $colorSetting);
	ImageCopyResampled($dst_img, $src_img, 0, 0, 0, 0, $targ_X, $targ_Y, $sx, $sy);
	if ($type == 1) {
		imagegif($dst_img, $tag_file, 100);
	} else if ($type == 2) {
		imagejpeg($dst_img, $tag_file, 100);
	} else if ($type == 3) {
		imagepng($dst_img, $tag_file, 100);
	}
	ImageDestroy($dst_img);
	ImageDestroy($src_img);
	return true;
}

// img�� width/height�� ���� �̹����� �����ø��ϴ� �Լ�. ����� �Լ��� �ƴ�! ����.
function makeThumbnail($imgString, $originSrc) {
	global $database, $owner, $blogURL;
	
	if (!extension_loaded('gd')) {
		return $imgString;
	}
	
	if (!is_dir(ROOT."/cache/thumbnail/$owner")) { 
		@mkdir(ROOT."/cache/thumbnail");
		@chmod(ROOT."/cache/thumbnail", 0777);
		@mkdir(ROOT."/cache/thumbnail/$owner");
		@chmod(ROOT."/cache/thumbnail/$owner", 0777);
	}
	
	// ���� ��ũ ������ �ִ� ��.
	if (file_exists(ROOT."/attach/$owner/watermark.gif")) {
		$waterMarkPath = ROOT."/attach/$owner/watermark.gif";
	} else {
		$waterMarkPath = NULL;
	}
	
	// ���� ��ũ�� �� ����� x, y ��ǥ.
	// - x�� "left, center, right, ����", y�� "top, middle, bottom, ����" �� �Է��� �� �ֽ��ϴ�.
	// - ���ڷ� ��ġ�� �����Ͻ� ��� ����� ���� ���� ��� �𼭸���, ������ ���� ���� �ϴ� �𼭸��� �����Դϴ�.
	$waterMarkPosition = DBQuery::queryCell("SELECT `value` FROM `{$database['prefix']}UserSettings` WHERE `user` = $owner AND `name` = 'waterMarkPosition'");
	if ($waterMarkPosition == false) {
		$waterMarkPosition = "left=10|bottom=10";
	}
	
	list($horizontalPos, $verticalPos) = explode("|", $waterMarkPosition);
	$horizontalPos = explode("=", $horizontalPos);
	$verticalPos = explode("=", $verticalPos);
	
	if ($horizontalPos[0] == "left") {
		if ($horizontalPos[0] > 0) {
			$horizontalValue = $horizontalPos[1];
		} else {
			$horizontalValue = "left";
		}
	} else if ($horizontalPos[0] == "center") {
		$horizontalValue = "center";
	} else if ($horizontalPos[0] == "right") {
		if ($horizontalPos[0] > 0) {
			$horizontalValue = $horizontalPos[1] - $horizontalPos[1] * 2;
		} else {
			$horizontalValue = "right";
		}
	}
	if ($verticalPos[0] == "top") {
		if ($verticalPos[0] > 0) {
			$verticalValue = $verticalPos[1];
		} else {
			$verticalValue = "top";
		}
	} else if ($verticalPos[0] == "middle") {
		$verticalValue = "middle";
	} else if ($verticalPos[0] == "bottom") {
		if ($verticalPos[0] > 0) {
			$verticalValue = $verticalPos[1] - $verticalPos[1] * 2;
		} else {
			$verticalValue = "bottom";
		}
	}
	
	$waterMarkPosition = "$horizontalValue $verticalValue";
	
	// ���� ��ũ�� ����.
	// - 100�̸� ����������.
	// - 0�̸� ��������.(��, ���͸�ũ�� ������� ���� �Ͱ� ��������.)
	$gammaForWaterMark = DBQuery::queryCell("SELECT `value` FROM `{$database['prefix']}UserSettings` WHERE `user` = $owner AND `name` = 'gammaForWaterMark'");
	if ($gammaForWaterMark == false) {
		$gammaForWaterMark = 100;
	} else {
		$gammaForWaterMark = intval($gammaForWaterMark);
	}
	
	// ������ ũ��.
	$thumbnailPadding = DBQuery::queryCell("SELECT `value` FROM `{$database['prefix']}UserSettings` WHERE `user` = $owner AND `name` = 'thumbnailPadding'");
	if ($thumbnailPadding == false) {
		$padding = array("top" => 25, "right" => 25, "bottom" => 25, "left" => 25);
	} else {
		$tempArray = explode("|", $thumbnailPadding);
		$padding = array("top" => intval($tempArray[0]), "right" => intval($tempArray[1]), "bottom" => intval($tempArray[2]), "left" => intval($tempArray[3]));
	}
	
	// ������ ����.
	// ������ transparent�� ����ϵ��� ®���� IE ������(!!) ���ҽ��ϴ�. ���� ���� ſ�ϻ�.
	$bgColorForPadding = DBQuery::queryCell("SELECT `value` FROM `{$database['prefix']}UserSettings` WHERE `user` = $owner AND `name` = 'thumbnailPaddingColor'");
	if ($bgColorForPadding == false) {
		$bgColorForPadding = "FFFFFF"; 
	}
	
	$waterMarkArray = array();
	$waterMarkArray['path'] = $waterMarkPath;
	$waterMarkArray['position'] = $waterMarkPosition;
	$waterMarkArray['gamma'] = $gammaForWaterMark;
	
	$paddingArray = array();
	$paddingArray['top'] = $padding['top'];
	$paddingArray['right'] = $padding['right'];
	$paddingArray['bottom'] = $padding['bottom'];
	$paddingArray['left'] = $padding['left'];
	$paddingArray['bgColor'] = $bgColorForPadding;
	
	if (eregi('class="tt-thumbnail"', $imgString, $extra)) {
		$originFileName = basename($originSrc);
		
		// ����� �Ѿ���� ���� �̹� getAttachmentBinder() �Լ����� ���������� ��ȯ�� ���̹Ƿ� % ���� ����� �ʿ� ����. 
		if (ereg('width="([1-9][0-9]*)"', $imgString, $temp)) {
			$tempWidth = $temp[1];
		}
		
		// ����� �Ѿ���� ���� �̹� getAttachmentBinder() �Լ����� ���������� ��ȯ�� ���̹Ƿ� % ���� ����� �ʿ� ����. 
		if (ereg('height="([1-9][0-9]*)"', $imgString, $temp)) {
			$tempHeight = $temp[1];
		}
		
		$newTempFileName = eregi_replace("\.([[:alnum:]]+)$", ".thumbnail.\\1", $originFileName);
		$tempSrc = ROOT."/cache/thumbnail/$owner/$newTempFileName";
		// ���Ȼ� cache ���丮�� �������� �ʵ��� ���ܳ��´�.
		$tempURL = $blogURL."/thumbnail/$owner/$newTempFileName";
		
		if (!file_exists($tempSrc)) {
			$originImageInfo = getimagesize($originSrc);
			
			// ��ҵ� �������� �̹����� ��������.
			if ($originImageInfo[0] > $tempWidth || $originImageInfo[1] > $tempHeight) {
				// �� ����� ����.
				@copy(ROOT."/attach/$owner/$originFileName", $tempSrc);
				if (resampleImage($tempWidth, $tempHeight, $tempSrc, "reduce", "file", $paddingArray, $waterMarkArray)) {
					$tempImageInfo = getImagesize($tempSrc);
					$imgString = eregi_replace('src="([^"]+)"', 'src="'.$tempURL.'"', $imgString);
					$imgString = eregi_replace('width="([^"]+)"', 'width="'.$tempImageInfo[0].'"', $imgString);
					$imgString = eregi_replace('height="([^"]+)"', 'height="'.$tempImageInfo[1].'"', $imgString);
				} else {
					@unlink($tempSrc);
				}
			// ���� ������ �״���̰ų� Ȯ�� �̹�������, ���͸�ũ�� ������ �����ϸ� ����� ����.
			} else if (($originImageInfo[0] <= $tempWidth || $originImageInfo[1] <= $tempHeight) && (file_exists($waterMarkPath) || !empty($padding))) {
				// �� ����� ����.
				@copy(ROOT."/attach/$owner/$originFileName", $tempSrc);
				if (resampleImage($tempWidth, $tempHeight, $tempSrc, "reduce", "file", $paddingArray, $waterMarkArray)) {
					$tempImageInfo = getImagesize($tempSrc);
					$imgString = eregi_replace('src="([^"]+)"', 'src="'.$tempURL.'"', $imgString);
					$imgString = eregi_replace('width="([^"]+)"', 'width="'.$tempImageInfo[0].'"', $imgString);
					$imgString = eregi_replace('height="([^"]+)"', 'height="'.$tempImageInfo[1].'"', $imgString);
				} else {
					@unlink($tempSrc);
				}
			}
		} else {
			$thumbnailImageInfo = getimagesize($tempSrc);
			$resizedWidth = $tempWidth - $paddingArray['left'] - $paddingArray['right'];
			$resizedHeight = ceil($tempHeight * $resizedWidth / $tempWidth) + $paddingArray['top'] + $paddingArray['bottom'];
			
			// ��ҵ� �������� �̹����� ��������.
			if ($thumbnailImageInfo[0] > $tempWidth || $thumbnailImageInfo[1] > $resizedHeight) {
				// �� ���ϰ� ���õ� ���� ������ �����.
				deleteFilesByRegExp(ROOT."/cache/thumbnail/$owner/", "^".eregi_replace("\.([[:alnum:]]+)$", "\.", $originFileName));
				
				// �� ����� ����.
				@copy(ROOT."/attach/$owner/$originFileName", $tempSrc);
				if (resampleImage($tempWidth, $tempHeight, $tempSrc, "reduce", "file", $paddingArray, $waterMarkArray)) {
					$tempImageInfo = getImagesize($tempSrc);
					$imgString = eregi_replace('src="([^"]+)"', 'src="'.$tempURL.'"', $imgString);
					$imgString = eregi_replace('width="([^"]+)"', 'width="'.$tempImageInfo[0].'"', $imgString);
					$imgString = eregi_replace('height="([^"]+)"', 'height="'.$tempImageInfo[1].'"', $imgString);
				} else {
					@unlink($tempSrc);
				}
			} else {
				// ��������� ������ �̹� �����ϹǷ� ���.
				$tempImageInfo = getImagesize($tempSrc);
				$imgString = eregi_replace('src="([^"]+)"', 'src="'.$tempURL.'"', $imgString);
				$imgString = eregi_replace('width="([^"]+)"', 'width="'.$tempImageInfo[0].'"', $imgString);
				$imgString = eregi_replace('height="([^"]+)"', 'height="'.$tempImageInfo[1].'"', $imgString);
			}
		}
	} else {
		// ����.
	}

	return $imgString;
}

function resampleImage($width=NULL, $height=NULL, $fileName=NULL, $resizeFlag=NULL, $outputType="file", $padding=NULL, $waterMark=NULL)
{
	$path = eregi("/$", dirname($fileName), $temp) ? dirname($fileName) : dirname($fileName).'/';
	$fileName = basename($fileName);
	
	// ���ϴ� ũ�Ⱑ �������� �ʾ����� �׳� ������.
	if (empty($width) && empty($height)) {
		return true;
	}
	
	// ���������� �����ϴ°�.
	if ($tempInfo = getimagesize($path.$fileName)) {
		$originWidth = $tempInfo[0];
		$originHeight = $tempInfo[1];
	} else {
		return false;
	}
	
	// ������¡ ��Ÿ���� 'both'�� ����Ʈ.
	if (empty($resizeFlag) || ($resizeFlag != "enlarge" && $resizeFlag != "reduce" && $resizeFlag != "both")) {
		$resizeFlag = "both";
	}
	
	// ��¹�� ��ȿ�� �˻�.
	if ($outputType != "file" && $outputType != "browser") {
		$outputType = "file";
	}
	
	// ���� ��ȿ�� �˻�.
	if (!is_null($padding)) {
		if (!isset($padding['top']) || !is_int($padding['top'])) {
			$padding['top'] = 0;
		}
		if (!isset($padding['right']) || !is_int($padding['right'])) {
			$padding['right'] = 0;
		}
		if (!isset($padding['bottom']) || !is_int($padding['bottom'])) {
			$padding['bottom'] = 0;
		}
		if (!isset($padding['left']) || !is_int($padding['left'])) {
			$padding['left'] = 0;
		}
	}
	
	// bgColor ��ȿ�� �˻�.
	if (!eregi("^[0-9A-F]{3,6}$", $padding['bgColor'], $temp)) {
	//if (!eregi("^[0-9A-F]{3,6}$", $padding['bgColor'], $temp) && $padding['bgColor'] != "transparent") {
		$padding['bgColor'] = "FFFFFF";
	}
	
	// ���� ���� �����Ѵٸ� $width, $height�� ���� ���鰪�� ������ ũ���̴�. ���� ���� ���� ����.
	// |--------------------- $width ---------------------|
	// |-- ���� ���� --||-- $new_width --||-- ���� ���� --|
	$imgWidth = $width - $padding['left'] - $padding['right'];
	($imgWidth < 0) ? $imgWidth = 0 : NULL;
	$imgHeight = ceil($height * $imgWidth / $width);
	($imgHeight < 0) ? $imgHeight = 0 : NULL;
	
	// ������ ����(Ȯ���ڰ� �ƴ�)�� �ش��ϴ� �̹��� ����.
	switch (getImageType($path.$fileName)) {
		case "gif":
			if (imagetypes() & IMG_GIF) {
				$tempSource = imagecreatefromgif($path.$fileName);
			} else {
				return false;
			}
			break;
		case "jpg":
			if (imagetypes() & IMG_JPG) {
				$tempSource = imagecreatefromjpeg($path.$fileName);
			} else {
				return false;
			}
			break;
		case "png":
			if (imagetypes() & IMG_PNG) {
				$tempSource = imagecreatefrompng($path.$fileName);
			} else {
				return false;
			}
			break;
		case "wbmp":
			if (imagetypes() & IMG_WBMP) {
				$tempSource = imagecreatefromwbmp($path.$fileName);
			} else {
				return false;
			}
			break;
		case "xpm":
			if (imagetypes() & IMG_XPM) {
				$tempSource = imagecreatefromxpm($path.$fileName);
			} else {
				return false;
			}
			break;
		default:
			return false;
			break;
	}
	
	// �ӽ� �̹��� ���ϸ� ����.
	srand((double) microtime()*1000000);
	$tempImage = rand(0, 100000);
	
	// ���ο� Ʈ��Ÿ�� �̹��� ����̽��� ����.
	if (getFileExtension($fileName) == "gif") {
		$tempResultImage = imagecreate($imgWidth + $padding['left'] + $padding['right'], $imgHeight + $padding['top'] + $padding['bottom']);
	} else {
		$tempResultImage = imagecreatetruecolor($imgWidth + $padding['left'] + $padding['right'], $imgHeight + $padding['top'] + $padding['bottom']);
	}
	
	// �̹��� ����̽��� ���� ������ ä���.
	if ($padding['bgColor'] == "transparent") {
		$bgColorBy16 = hexRGB("FF0000");
		$temp = imagecolorallocate($tempResultImage, $bgColorBy16['R'], $bgColorBy16['G'], $bgColorBy16['B']);
		imagefilledrectangle($tempResultImage, 0, 0, $imgWidth + $padding['left'] + $padding['right'], $imgHeight + $padding['top'] + $padding['bottom'], $temp);
		imagecolortransparent($tempResultImage, $temp);
	} else {
		//imagealphablending($tempResultImage, 0); //bgColor�� ��� alpha blending�� ������.
		$bgColorBy16 = hexRGB($padding['bgColor']);
		$temp = imagecolorallocate($tempResultImage, $bgColorBy16['R'], $bgColorBy16['G'], $bgColorBy16['B']);
		imagefilledrectangle($tempResultImage, 0, 0, $imgWidth + $padding['left'] + $padding['right'], $imgHeight + $padding['top'] + $padding['bottom'], $temp);
	}
	
	// �̹��� ����̽��� ũ�Ⱑ ������ ���� �̹����� ������ �����Ͽ� ���δ�.
	imagecopyresampled($tempResultImage, $tempSource, $padding['left'], $padding['top'], 0, 0, $imgWidth, $imgHeight, imagesx($tempSource), imagesy($tempSource));
	
	// ���� ��ũ ���̱�.
	if ($waterMarkInfo = getimagesize($waterMark['path'])) {
		$waterMarkWidth = $waterMarkInfo[0];
		$waterMarkHeight = $waterMarkInfo[1];
		
		// ���� ��ũ �̹��� ����̽� ����.
		if ($waterMarkInfo[2] == 1) {
			$tempWaterMarkSource = imagecreatefromgif($waterMark['path']);
		} else if ($waterMarkInfo[2] == 2) {
			$tempWaterMarkSource = imagecreatefromjpeg($waterMark['path']);
		} else if ($waterMarkInfo[2] == 3) {
			$tempWaterMarkSource = imagecreatefrompng($waterMark['path']);
		}
		
		// ���� ��ũ ������.
		if (eregi("^(\-?[0-9A-Z]+) (\-?[0-9A-Z]+)$", $waterMark['position'], $temp)) {
			$extraPadding = 0;
			switch ($temp[1]) {
				case "left":
					$xPosition = $extraPadding;
					break;
				case "center":
					$xPosition = ($imgWidth + $padding['left'] + $padding['right']) / 2 - $waterMarkInfo[0] / 2;
					break;
				case "right":
					$xPosition = $imgWidth + $padding['left'] + $padding['right'] - $waterMarkWidth - $extraPadding;
					break;
				default:
					// ����� ���, ���ʺ��� x��ǥ ���� ����Ѵ�.
					if (eregi("^([1-9][0-9]*)$", $temp[1], $extra)) {
						if ($extra[1] > $imgWidth + $padding['left'] + $padding['right'] - $waterMarkWidth) {
							$xPosition = $imgWidth + $padding['left'] + $padding['right'] - $waterMarkWidth;
						} else {
							$xPosition = $extra[1];
						}
					// ������ ���, �����ʺ��� x��ǥ ���� ����Ѵ�.
					} else if (eregi("^(\-?[1-9][0-9]*)$", $temp[1], $extra)) {
						if ($imgWidth + $padding['left'] + $padding['right'] - $waterMarkWidth + $extra[1] < 0) {
							$xPosition = 0;
						} else {
							$xPosition = $imgWidth + $padding['left'] + $padding['right'] - $waterMarkWidth + $extra[1];
						}
					// 0�� ���.
					} else if (eregi("^0$", $temp[1], $extra)) {
						$xPosition = 0;
					// ������ ���� ���� �������� ������ ���δ�.
					} else {
						$xPosition = $imgWidth + $padding['left'] + $padding['right'] - $waterMarkWidth - $extraPadding;
					}
			}
			
			switch ($temp[2]) {
				case "top":
					$yPosition = $extraPadding;
					break;
				case "middle":
					$yPosition = $imgHeight + $padding['top'] + $padding['bottom'] / 2 - $waterMarkInfo[1] / 2;
					break;
				case "bottom":
					$yPosition = $imgHeight + $padding['top'] + $padding['bottom'] - $waterMarkHeight - $extraPadding;
					break;
				default:
					// ����� ���, ������ y��ǥ ���� ����Ѵ�.
					if (eregi("^([1-9][0-9]*)$", $temp[2], $extra)) {
						if ($extra[1] > $imgHeight + $padding['top'] + $padding['bottom'] - $waterMarkHeight) {
							$yPosition = $imgHeight + $padding['top'] + $padding['bottom'] - $waterMarkHeight;
						} else {
							$yPosition = $extra[1];
						}
					// ������ ���, �Ʒ����� y��ǥ ���� ����Ѵ�.
					} else if (eregi("^(\-?[1-9][0-9]*)$", $temp[2], $extra)) {
						if ($imgHeight + $padding['top'] + $padding['bottom'] - $waterMarkHeight + $extra[1] < 0) {
							$yPosition = 0;
						} else {
							$yPosition = $imgHeight + $padding['top'] + $padding['bottom'] - $waterMarkHeight + $extra[1];
						}
					// 0�� ���.
					} else if (eregi("^0$", $temp[1], $extra)) {
						$yPosition = 0;
					// ������ ���� ���� �������� �Ʒ��� ���δ�.
					} else {
						$yPosition = $imgHeight + $padding['top'] + $padding['bottom'] - $waterMarkHeight - $extraPadding;
					}
			}
		} else {
			$xPosition = $imgWidth + $padding['left'] + $padding['right'] - $waterMarkWidth - $extraPadding;
			$yPosition = $imgHeight + $padding['top'] + $padding['bottom'] - $waterMarkHeight - $extraPadding;
		}
		
		// ������ ��ȿ�� �˻�.
		if (!is_int($waterMark['gamma'])) {
			$waterMark['gamma'] = 100;
		} else if ($waterMark['gamma'] < 0) {
			$waterMark['gamma'] = 0;
		} else if ($waterMark['gamma'] > 100) {
			$waterMark['gamma'] = 100;
		}
		
		if (function_exists("imagecopymerge")) {
			imagecopymerge($tempResultImage, $tempWaterMarkSource, $xPosition, $yPosition, 0, 0, imagesx($tempWaterMarkSource), imagesy($tempWaterMarkSource), $waterMark['gamma']);
		} else {
			imagecopy($tempResultImage, $tempWaterMarkSource, $xPosition, $yPosition, 0, 0, imagesx($tempWaterMarkSource), imagesy($tempWaterMarkSource));
		}
	}
	
	// �˸´� �������� ����.
	if ($outputType == "file") {
		if (getFileExtension($fileName) == "gif") {
			imageinterlace($tempResultImage);
			imagegif($tempResultImage, $path.$tempImage);
		} else if (getFileExtension($fileName) == "jpg" || getFileExtension($fileName) == "jpeg") {
			imageinterlace($tempResultImage);
			imagejpeg($tempResultImage, $path.$tempImage);
		} else if (getFileExtension($fileName) == "png") {
			imagepng($tempResultImage, $path.$tempImage);
		} else if (getFileExtension($fileName) == "wbmp") {
			imagewbmp($tempResultImage, $path.$tempImage);
		}
		
		// �ӽ� �̹��� ����.
		imagedestroy($tempResultImage);
		imagedestroy($tempSource);
		if (file_exists($waterMark['path'])) {
			imagedestroy($tempWaterMarkSource);
		}
		
		if (file_exists($path.$fileName)) {
			unlink($path.$fileName);
		}
		
		// ���� �̹��� ��Ī���� ������.
		rename($path.$tempImage, $path.$fileName);
		return true;
	// �������� ����.
	} else {
		header("Content-type: image/jpeg");
		imagejpeg($tempResultImage);
		return $bResult;
		
		/*header("Content-type: image/".(getFileExtension($fileName) == "jpg" ? "jpeg" : getFileExtension($fileName)));
		// getFileExtension()�� getImageType()�� ���̿� ������ ��.
		
		$bResult = false;
		switch (getFileExtension($fileName)) {
			case "gif":
				imageinterlace($tempResultImage);
				imagegif($tempResultImage);
				$bResult = true;
				break;
			case "jpg":
			case "jpeg":
				imageinterlace($tempResultImage);
				imagejpeg($tempResultImage);
				$bResult = true;
				break;
			case "png":
				imagepng($tempResultImage);
				$bResult = true;
				break;
			case "wbmp":
				imagewbmp($tempResultImage);
				$bResult = true;
				break;
		}
		return $bResult;*/
	}
}

function hexRGB($hexstr)
{
	$int = hexdec($hexstr);
	return array('R' => 0xFF & ($int >> 0x10), 'G' => 0xFF & ($int >> 0x8), 'B' => 0xFF & $int);
}

function getImageType($filename)
{
	if (file_exists($filename)) {
		if (function_exists("exif_imagetype")) {
			$imageType = exif_imagetype($filename);
		} else {
			$tempInfo = getimagesize($filename);
			$imageType = $tempInfo[2];
		}
		
		switch ($imageType) {
			// ����� ����ϸ� ����? Ȯ�� ����.
			case IMAGETYPE_GIF:
				$extension = 'gif';
				break;
			case IMAGETYPE_JPEG:
				$extension = 'jpg';
				break;
			case IMAGETYPE_PNG:
				$extension = 'png';
				break;
			case IMAGETYPE_SWF:
				$extension = 'swf';
				break;
			case IMAGETYPE_PSD:
				$extension = 'psd';
				break;
			case IMAGETYPE_BMP:
				$extension = 'bmp';
				break;
			case IMAGETYPE_TIFF_II:
			case IMAGETYPE_TIFF_MM:
				$extension = 'tiff';
				break;
			case IMAGETYPE_JPC:
				$extension = 'jpc';
				break;
			case IMAGETYPE_JP2:
				$extension = 'jp2';
				break;
			case IMAGETYPE_JPX:
				$extension = 'jpx';
				break;
			case IMAGETYPE_JB2:
				$extension = 'jb2';
				break;
			case IMAGETYPE_SWC:
				$extension = 'swc';
				break;
			case IMAGETYPE_IFF:
				$extension = 'aiff';
				break;
			case IMAGETYPE_WBMP:
				$extension = 'wbmp';
				break;
			case IMAGETYPE_XBM:
				$extension = 'xbm';
				break;
			default:
				$extension = false;
		}
	} else {
		$extension = false;
	}
	
	return $extension;
}

function deleteAllThumbnails($path) {
	deleteFilesByRegExp($path, "*");
	return true;
}
?>
