<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

// 사용되지 않는 함수이나 만약을 위해 남겨둠.
function isLarge($target, $maxX, $maxY) {
	if (!file_exists($target)) return false;
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

// 사용되지 않는 함수이나 만약을 위해 남겨둠.
function resizing($maxX, $maxY, $src_file, $tag_file) {
	if (!file_exists($src_file)) return false;
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

function deleteAllThumbnails($path) {
	deleteFilesByRegExp($path, "*");
	return true;
}

function getWaterMarkPosition() {
	$waterMarkPosition = getUserSetting("waterMarkPosition", "left=10|bottom=10");

	list($horizontalPos, $verticalPos) = explode("|", $waterMarkPosition);
	$horizontalPos = explode("=", $horizontalPos);
	$verticalPos = explode("=", $verticalPos);

	if ($horizontalPos[0] == "left") {
		if ($horizontalPos[1] > 0) {
			$horizontalValue = $horizontalPos[1];
		} else {
			$horizontalValue = "left";
		}
	} else if ($horizontalPos[0] == "center") {
		$horizontalValue = "center";
	} else if ($horizontalPos[0] == "right") {
		if ($horizontalPos[1] > 0) {
			$horizontalValue = $horizontalPos[1] - $horizontalPos[1] * 2;
		} else {
			$horizontalValue = "right";
		}
	}
	if ($verticalPos[0] == "top") {
		if ($verticalPos[1] > 0) {
			$verticalValue = $verticalPos[1];
		} else {
			$verticalValue = "top";
		}
	} else if ($verticalPos[0] == "middle") {
		$verticalValue = "middle";
	} else if ($verticalPos[0] == "bottom") {
		if ($verticalPos[1] > 0) {
			$verticalValue = $verticalPos[1] - $verticalPos[1] * 2;
		} else {
			$verticalValue = "bottom";
		}
	}

	return "$horizontalValue $verticalValue";
}

function getWaterMarkGamma() {
	return 100;//intval(getUserSetting("gammaForWaterMark", "100"));
}

function getThumbnailPadding() {
	$thumbnailPadding = getUserSetting("thumbnailPadding", false);
	if ($thumbnailPadding == false) {
		return array("top" => 0, "right" => 0, "bottom" => 0, "left" => 0);
	} else {
		$tempArray = explode("|", $thumbnailPadding);
		return array("top" => intval($tempArray[0]), "right" => intval($tempArray[1]), "bottom" => intval($tempArray[2]), "left" => intval($tempArray[3]));
	}
}

function getThumbnailPaddingColor() {
	return getUserSetting("thumbnailPaddingColor", "FFFFFF");
}

// img의 width/height에 맞춰 이미지를 리샘플링하는 함수. 썸네일 함수가 아님! 주의.
function resampleImage($imgString, $originSrc, $useAbsolutePath) {
	global $database, $serviceURL, $pathURL;
	
	if (!extension_loaded('gd')) {
		return $imgString;
	}
	
	requireComponent('Textcube.Function.Image');
	if (!is_dir(ROOT."/cache/thumbnail")) {
		@mkdir(ROOT."/cache/thumbnail");
		@chmod(ROOT."/cache/thumbnail", 0777);
	}

	if (!is_dir(ROOT."/cache/thumbnail/".getBlogId())) {
		@mkdir(ROOT."/cache/thumbnail/".getBlogId());
		@chmod(ROOT."/cache/thumbnail/".getBlogId(), 0777);
	}

	$originFileName = basename($originSrc);

	// 여기로 넘어오는 값은 이미 getAttachmentBinder() 함수에서 고정값으로 변환된 값이므로 % 값은 고려할 필요 없음.
	if (preg_match('/width="([1-9][0-9]*)"/i', $imgString, $temp)) {
		$tempWidth = $temp[1];
	}

	if (preg_match('/height="([1-9][0-9]*)"/i', $imgString, $temp)) {
		$tempHeight = $temp[1];
	}

	$newTempFileName = preg_replace("/\.([[:alnum:]]+)$/i", ".w{$tempWidth}-h{$tempHeight}.\\1", $originFileName);
	$tempSrc = ROOT."/cache/thumbnail/".getBlogId()."/".$newTempFileName;

	$tempURL = $pathURL."/thumbnail/".getBlogId()."/".$newTempFileName;
	if ($useAbsolutePath == true) {
		$tempURL = "$serviceURL/thumbnail/".getBlogId()."/".$newTempFileName;
	}

	if (file_exists($tempSrc)) {
		$imgString = preg_replace('/src="([^"]+)"/i', 'src="'.$tempURL.'"', $imgString);
		$imgString = preg_replace('/width="([^"]+)"/i', 'width="'.$tempWidth.'"', $imgString);
		$imgString = preg_replace('/height="([^"]+)"/i', 'height="'.$tempHeight.'"', $imgString);
		$imgString = preg_replace('/onclick="open_img\(\'([^\']+)\'\)"/', "onclick=\"open_img('$serviceURL/attach/".getBlogId()."/".$originFileName."')\"", $imgString);
	} else {
		$AttachedImage = new Image();
		$AttachedImage->imageFile = $originSrc;

		// 리샘플링 시작.
		if ($AttachedImage->resample($tempWidth, $tempHeight)) {
			// 리샘플링된 파일 저장.
			$AttachedImage->createThumbnailIntoFile($tempSrc);
			$imgString = preg_replace('/src="([^"]+)"/i', 'src="'.$tempURL.'"', $imgString);
			$imgString = preg_replace('/width="([^"]+)"/i', 'width="'.$tempWidth.'"', $imgString);
			$imgString = preg_replace('/height="([^"]+)"/i', 'height="'.$tempHeight.'"', $imgString);
			$imgString = preg_replace('/onclick="open_img\(\'([^\']+)\'\)"/', "onclick=\"open_img('$serviceURL/attach/".getBlogId()."/".$originFileName."')\"", $imgString);
		}

		unset($AttachedImage);
	}

	return $imgString;
}
?>
