<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

function printFormatterSelectScript() {
?>
<script type="text/javascript">
//<![CDATA[
	function getEditorsForFormatter(key) {
		switch (key) {
<?php
	foreach (getAllFormatters() as $id => $formatter) {
		echo "\t\tcase '".addslashes($id)."': return [";
		$delim = '';
		foreach ($formatter['editors'] as $key => $value) {
			echo $delim."'".addslashes($key)."'";
			$delim = ', ';
		}
		echo "];\n";
	}
?>
		}
		return [];
	}

	function setFormatter(key, editorselect, correct) {
		var editoroptions = editorselect.options;
		var editorsusedfor = getEditorsForFormatter(key);
		var editormap = {};
		for (var i = 0; i < editorsusedfor.length; ++i) {
			editormap[editorsusedfor[i]] = true;
		}
		var firsteditor = -1;
		for (var i = 0; i < editoroptions.length; ++i) {
			if (editormap[editoroptions[i].value]) {
				editoroptions[i].disabled = false;
				editoroptions[i].style.color = '';
				if (firsteditor < 0) firsteditor = i;
			} else {
				editoroptions[i].disabled = true;
				editoroptions[i].style.color = 'GrayText';
			}
		}
		if (correct && !editormap[editorselect.value] && firsteditor >= 0) {
			editorselect.selectedIndex = firsteditor;
			if (typeof correct == 'function') correct(editorselect.value);
		}
		return true;
	}

	function saveEditor(select) {
		select.prevSelectedIndex = select.selectedIndex;
		return true;
	}

	function setEditor(select) {
		if (select.options[select.selectedIndex].disabled) {
			select.selectedIndex = select.prevSelectedIndex;
			return false;
		}
		select.prevSelectedIndex = select.selectedIndex;
		return true;
	}
//]]>
</script>
<?php
}

function printOwnerEditorScript($entryId = false) {
	global $database, $skin, $hostURL, $blogURL, $service;
	$blogid = getBlogId();

	$contentWidth = 500;
	
	if($skin = DBQuery::queryCell("SELECT skin FROM {$database['prefix']}SkinSettings WHERE blogid = $blogid")) {
		if($xml = @file_get_contents(ROOT."/skin/$skin/index.xml")) {
			$xmls = new XMLStruct();
			$xmls->open($xml, $service['encoding']);
			if($xmls->getValue('/skin/default/contentWidth')) {
				$contentWidth = $xmls->getValue('/skin/default/contentWidth');
			}
		}
	}

?>
<script type="text/javascript">
//<![CDATA[
	var entryId = <?php echo $entryId ? $entryId : 0;?>; 
	var skinContentWidth = <?php echo $contentWidth;?>;

	function savePosition(oTextarea) {
		if (oTextarea.createTextRange)
			oTextarea.currentPos = document.selection.createRange().duplicate();
	}

	function insertTag(oTextarea, prefix, postfix) {
		if(isSafari) 
			var selection = window.getSelection;
		else
			var selection = document.selection;
		
		if (selection) {			
			if (oTextarea.createTextRange && oTextarea.currentPos) {				
				oTextarea.currentPos.text = prefix + oTextarea.currentPos.text + postfix;
				oTextarea.focus();
				savePosition(oTextarea);
			}
			else
				oTextarea.value = oTextarea.value + prefix + postfix;
		}
		else if (oTextarea.selectionStart != null && oTextarea.selectionEnd != null) {
			var s1 = oTextarea.value.substring(0, oTextarea.selectionStart);
			var s2 = oTextarea.value.substring(oTextarea.selectionStart, oTextarea.selectionEnd);
			var s3 = oTextarea.value.substring(oTextarea.selectionEnd);
			oTextarea.value = s1 + prefix + s2 + postfix + s3;
		}
		else
			oTextarea.value += prefix + postfix;
			
		return true;	
	}

	function editorChanged() {
		if ((entryManager != undefined) && (entryManager.saveAuto != undefined))
			entryManager.saveAuto();
	}

	function getEditor(key) {
		switch (key) {
<?php
	foreach (getAllEditors() as $id => $editor) {
		getEditorInfo($id); // explicitly loads plugin code
		if (isset($editor['initfunc']) && function_exists($editor['initfunc'])) {
			echo "\t\tcase '".addslashes($id)."': {\n".call_user_func($editor['initfunc'], $editor)."\t\t}\n";
		}
	}
?>
		}
		return new TTDefaultEditor();
	}

	var editor = null;
	function setCurrentEditor(key) {
		var neweditor = getEditor(key);
		if (neweditor == null) {
			if (editor == null) {
				// this indicates currently selected editor is unavailable;
				// we fallback into the default editor.
				neweditor = new TTDefaultEditor();
			} else {
				return false;
			}
		}
		if (editor != null) {
			try { editor.syncTextarea(); } catch(e) {}
			editor.finalize();
		}
		editor = neweditor;
		editor.initialize(document.getElementById("editWindow"));
		return true;
	}
//]]>
</script>
<?php

	printFormatterSelectScript();
}

function printEntryFileList($attachments, $param) {
	global $service, $blogURL, $adminSkinSetting;

	$blogid = getBlogId();
	if(empty($attachments) || (
	strpos($attachments[0]['name'] ,'.gif') === false &&
	strpos($attachments[0]['name'] ,'.jpg') === false &&
	strpos($attachments[0]['name'] ,'.png') === false)) {
		$fileName =  "{$service['path']}{$adminSkinSetting['skin']}/image/spacer.gif";
	} else {
		$fileName = "{$service['path']}/attach/$blogid/{$attachments[0]['name']}";
	}
?>
											<div id="previewSelected" style="width: 120px; height: 90px;"><span class="text"><?php echo _t('미리보기');?></span></div>
											
											<div id="attachManagerSelectNest">				
												<span id="attachManagerSelect">
													<select id="fileList" name="fileList" multiple="multiple" size="8" onchange="selectAttachment();">
<?php 
	$initialFileListForFlash = '';
	$enclosureFileName = '';
	foreach ($attachments as $i => $attachment) {
		
		if (strpos ($attachment['mime'], 'application') !== false ) {
			$class = 'class="MimeApplication"';
		} else if (strpos ($attachment['mime'], 'audio') !== false ) {
			$class = 'class="MimeAudio"';
		} else if (strpos ($attachment['mime'], 'image') !== false ) {
			$class = 'class="MimeImage"';
		} else if (strpos ($attachment['mime'], 'message') !== false ) {
			$class = 'class="MimeMessage"';
		} else if (strpos ($attachment['mime'], 'model') !== false ) {
			$class = 'class="MimeModel"';
		} else if (strpos ($attachment['mime'], 'multipart') !== false ) {
			$class = 'class="MimeMultipart"';
		}  else if (strpos ($attachment['mime'], 'text') !== false ) {
			$class = 'class="MimeText"';
		}  else if (strpos ($attachment['mime'], 'video') !== false ) {
			$class = 'class="MimeVideo"';
		} else {
			$class = '';
		}
		if (!empty($attachment['enclosure']) && $attachment['enclosure'] == 1) {
			$style = 'style="background-color:#c6a6e7; color:#000000"';		
			$enclosureFileName = $attachment['name'];
		} else {
			$style = '';
		}
		
		$value = htmlspecialchars(getAttachmentValue($attachment));
		$label = htmlspecialchars(getPrettyAttachmentLabel($attachment));
		
		$initialFileListForFlash .= escapeJSInAttribute($value.'(_!'.$label.'!^|');
?>
		<option  <?php echo $style;?> value="<?php echo $value;?>"><?php echo $label;?></option>
<?php
	}
?>
													</select>
												</span>
											</div>
											
											<script type="text/javascript">
												//<![CDATA[
													function addAttachment() {
														var attachHidden = document.getElementById('attachHiddenNest');
														attachHidden.contentDocument.forms[0].action = "<?php echo $param['singleUploadPath'];?>"+entryManager.entryId;
														attachHidden.contentDocument.forms[0].attachment.click();
													}
													
													function deleteAttachment() {
														var fileList = document.getElementById('fileList');		
														
														if (fileList.selectedIndex < 0) {
															alert("<?php echo _t('삭제할 파일을 선택해 주십시오\t');?>");
															return false;
														}
														
														try {
															
															var targetStr = '';
															deleteFileList = new Array();
															for(var i=0; i<fileList.length; i++) {
																if(fileList[i].selected) {
																	var name = fileList[i].value.split("|")[0];
																	targetStr += name+'!^|';
																	deleteFileList.push(i);
																}
															}
														} catch(e) {
															alert("<?php echo _t('파일을 삭제하지 못했습니다');?> ::"+e.message);
														}
												
														var request = new HTTPRequest("POST", "<?php echo $param['deletePath'];?>"+entryManager.entryId);
														request.onVerify = function () { 
															return true 
														}
												
														request.onSuccess = function() {				
															for(var i=deleteFileList.length-1; i>=0; i--) {
																fileList.remove(deleteFileList[i]);	
															}
															
															if (fileList.options.length == 0)
																document.getElementById('previewSelected').innerHTML = '';
															else {
																fileList.selectedIndex = 0;
																selectAttachment();
															}
															refreshAttachFormSize();
															refreshFileSize();
														}
														
														request.onError = function() {
															alert("<?php echo _t('파일을 삭제하지 못했습니다');?>");
														}
														request.send("names="+targetStr);
													}

													function selectAttachment() {
														try {
														width = document.getElementById('previewSelected').clientWidth;
														height = document.getElementById('previewSelected').clientHeight;
														var code = '';
														var fileList = document.getElementById('fileList');
														if (fileList.selectedIndex < 0)
															return false;
														var fileName = fileList.value.split("|")[0];
														
														if((new RegExp("\\.(gif|jpe?g|png)$", "gi").exec(fileName))) {
															try {
																var width = new RegExp('width="(\\d+)').exec(fileList.value);
																width = width[1];
																var height = new RegExp('height="(\\d+)').exec(fileList.value);
																height = height[1];
																if(width > 120) {
																	height = 120 / width * height;
																	width = 120;
																}
																if(height > 90) {
																	width = 90 / height * width;
																	height = 90;
																}
																document.getElementById('previewSelected').innerHTML = '<img src="<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/'+fileName+'?randseed='+Math.random()+'" width="' + parseInt(width) + '" height="' + parseInt(height) + '" alt="" style="margin-top: ' + ((90-height)/2) + 'px" onerror="this.src=\'<?php echo $service['path'] . $adminSkinSetting['skin'];?>/image/spacer.gif\'"/>';																
															}
															catch(e) { }
															return false;
														}
														
														if((new RegExp("\\.(mp3)$", "gi").exec(fileName))) {
															var str = getEmbedCode("<?php echo $service['path'];?>/script/jukebox/flash/mini.swf","100%","100%", "jukeBox0Flash","#FFFFFF", "sounds=<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/"+fileName+"&autoplay=false", "false");
															writeCode(str, 'previewSelected');
															return false;
														}
														
														if((new RegExp("\\.(swf)$", "gi").exec(fileName))) {			
															
															code = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="100%" height="100%"><param name="movie" value="<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/'+fileName+'"/><param name="allowScriptAccess" value="sameDomain" /><param name="menu" value="false" /><param name="quality" value="high" /><param name="bgcolor" value="#FFFFFF"/>';
															code += '<!--[if !IE]> <--><object type="application/x-shockwave-flash" data="<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/'+fileName+'" width="100%" height="100%"><param name="allowScriptAccess" value="sameDomain" /><param name="menu" value="false" /><param name="quality" value="high" /><param name="bgcolor" value="#FFFFFF"/><\/object><!--> <![endif]--><\/object>';
															
															writeCode(code,'previewSelected');
															return false;
														}
														
														if((new RegExp("\\.(mov)$", "gi").exec(fileName))) {			
															code = '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" width="'+width+'" height="'+height+'"><param name="src" value="<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/'+fileName+'"/><param name="controller" value="true"><param name="autoplay" value="false"><param name="scale" value="Aspect">';
															code += '<!--[if !IE]> <--><object type="video/quicktime" data="<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/'+fileName+'" width="'+width+'" height="'+height+'" showcontrols="true" TYPE="video/quicktime" scale="Aspect" nomenu="true"><param name="showcontrols" value="true"><param name="autoplay" value="false"><param name="scale" value="ToFit"><\/object><!--> <![endif]--><\/object>';
															writeCode(code,'previewSelected');
															return false;
														}
													
														if((new RegExp("\\.(mp2|wma|mid|midi|mpg|wav|avi|mp4)$", "gi").exec(fileName))) {
															code ='<object width="'+width+'" height="'+height+'" classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95" codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701" standby="Loading for you" type="application/x-oleobject" align="middle">';		
															code +='<param name="FileName" value="<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/'+fileName+'">';
															code +='<param name="ShowStatusBar" value="False">';
															code +='<param name="DefaultFrame" value="mainFrame">';
															code +='<param name="autoplay" value="false">';
															code +='<param name="showControls" value="true">';
															code +='<embed type="application/x-mplayer2" pluginspage = "http://www.microsoft.com/Windows/MediaPlayer/" src="<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/'+fileName+'" align="middle" width="'+width+'" height="'+height+'" showControls="true" defaultframe="mainFrame" showstatusbar="false" autoplay="false"><\/embed>';
															code +='<\/object>';
															
															writeCode(code,'previewSelected');
															
															return false;
														}
														
														if((new RegExp("\\.(rm|ram)$", "gi").exec(fileName))) {		
														/*
															code = '<object classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" width="'+width+'" height="'+height+'"><param name="src" value="<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/'+fileName+'"/><param name="CONTROLS" value="imagewindow"><param name="AUTOGOTOURL" value="FALSE"><param name="CONSOLE" value="radio"><param name="AUTOSTART" value="TRUE">';
															code += '<!--[if !IE]> <--><object type="audio/x-pn-realaudio-plugin" data="<?php echo $service['path'];?>/attach/<?php echo $blogid;?>/'+fileName+'" width="'+width+'" height="'+height+'" ><param name="CONTROLS" value="imagewindow"><param name="AUTOGOTOURL" value="FALSE"><param name="CONSOLE" value="radio"><param name="AUTOSTART" value="TRUE"><\/object><!--> <![endif]--><\/object>';			
														*/
														}
														
														if (code == undefined || code == '') {
															document.getElementById('previewSelected').innerHTML = "<table width=\"100%\" height=\"100%\"><tr><td valign=\"middle\" align=\"center\"><?php echo _t('미리보기');?><\/td><\/tr><\/table>";
															return true;
														}
														
																
														
														return false;
														} catch (e) {
															document.getElementById('previewSelected').innerHTML = "<table width=\"100%\" height=\"100%\"><tr><td valign=\"middle\" align=\"center\"><?php echo _t('미리보기');?><\/td><\/tr><\/table>";	
															alert(e.message);
															return true;
														}
													}				

													function downloadAttachment() {
														try {
															var fileList = document.getElementById('fileList');
															if (fileList.selectedIndex < 0) {
																return false;
															}
															for(var i=0; fileList.length; i++) {
																if (fileList[i].selected) {
																	var fileName = fileList[i].value.split("|")[0];
																	if(STD.isIE) {
																		document.getElementById('fileDownload').innerHTML='<iframe style="display:none;" src="'+blogURL+'\/attachment\/'+fileName+'"><\/iframe>';
																		
																	} else {
																		window.location = blogURL+'/attachment/'+fileName;
																	}
																	break;
																}
															}
														} catch(e) {
															alert(e.message);
														}
													}

													function disablePageManager() {
														try {
															entryManager.pageHolder.isHolding = function () {
																return false;
															}
														} catch(e) {
														}
														return true;
													}
													
													STD.addEventListener(window);													
													window.addEventListener("beforeunload", PageMaster.prototype._onBeforeUnload, false);				
													
													function enablePageManager() {
														try {
															entryManager.pageHolder.isHolding = entryManager.isContentSaved;
														} catch(e) {
														}
														return true;
													}
													
													function stripLabelToValue(fileLabel) {
														var pos = fileLabel.lastIndexOf('(');
														return fileLabel.substring(0,pos-1);	
													}
													
													function refreshAttachFormSize() {
														fileListObj = document.getElementById('fileList');
														fileListObj.setAttribute('size',Math.max(8,Math.min(fileListObj.length,30)));
													}
													
													function refreshAttachList() {
														var request = new HTTPRequest("POST", "<?php echo $param['refreshPath'];?>"+entryManager.entryId);
														request.onVerify = function () { 	
															return true 
														}
														request.onSuccess = function() {
															var fileListObj = document.getElementById("attachManagerSelect");
															fileListObj.innerHTML = this.getText();
															refreshAttachFormSize();
															//getUploadObj().setAttribute('width',1)
															//getUploadObj().setAttribute('height',1)
															
															if (isIE || isMoz) {
																document.getElementById('uploadBtn').style.display  = 'inline';
																document.getElementById('stopUploadBtn').style.display  = 'none';			
															} else {
																document.getElementById('uploadBtn').disabled = false;					
															}
															
															document.getElementById('uploaderNest').innerHTML = uploaderStr;
															refreshFileSize();						
															setTimeout("enablePageManager()", 2000);
															entryManager.delay     = true;
															entryManager.nowsaving = false;
														}
														request.onError = function() {
															setTimeout("enablePageManager()", 2000);
															entryManager.delay     = true;
															entryManager.nowsaving = false;
														
														}
														request.send();					
													}
													
													function uploadProgress(target,loaded, total) {
														loaded = Number(loaded);
														total = Number(total);
														var fileListObj = document.getElementById("fileList");					
														for(var i=0; i<fileListObj.length; i++) {
															if (fileListObj[i].getAttribute("value") == target) {
																fileListObj[i].innerHTML = target+" "+(Math.ceil(100*loaded/total))+"%";
																break;
															}
														}
													}
													
													function uploadComplete(target,size) {
														loaded = Number(loaded);
														total = Number(total);
														var fileListObj = document.getElementById("fileList");
														for(var i=0; i<fileListObj.length; i++) {
															if (fileListObj[i].getAttribute("value") == target) {
																fileListObj[i].innerHTML = target+" "+(Math.ceil(100*loaded/total))+"%";
																break;
															}
														}
													}
													
													/**
													*
													*  Base64 encode / decode
													*  http://www.webtoolkit.info/
													*
													**/
													
													var Base64 = {
													
														// private property
														_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
													
														// public method for encoding
														encode : function (input) {
															var output = "";
															var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
															var i = 0;
													
															input = Base64._utf8_encode(input);
													
															while (i < input.length) {
													
																chr1 = input.charCodeAt(i++);
																chr2 = input.charCodeAt(i++);
																chr3 = input.charCodeAt(i++);
													
																enc1 = chr1 >> 2;
																enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
																enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
																enc4 = chr3 & 63;
													
																if (isNaN(chr2)) {
																	enc3 = enc4 = 64;
																} else if (isNaN(chr3)) {
																	enc4 = 64;
																}
													
																output = output +
																this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
																this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
													
															}
													
															return output;
														},
													
														// public method for decoding
														decode : function (input) {
															var output = "";
															var chr1, chr2, chr3;
															var enc1, enc2, enc3, enc4;
															var i = 0;
													
															input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
													
															while (i < input.length) {
													
																enc1 = this._keyStr.indexOf(input.charAt(i++));
																enc2 = this._keyStr.indexOf(input.charAt(i++));
																enc3 = this._keyStr.indexOf(input.charAt(i++));
																enc4 = this._keyStr.indexOf(input.charAt(i++));
													
																chr1 = (enc1 << 2) | (enc2 >> 4);
																chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
																chr3 = ((enc3 & 3) << 6) | enc4;
													
																output = output + String.fromCharCode(chr1);
													
																if (enc3 != 64) {
																	output = output + String.fromCharCode(chr2);
																}
																if (enc4 != 64) {
																	output = output + String.fromCharCode(chr3);
																}
													
															}
													
															output = Base64._utf8_decode(output);
													
															return output;
													
														},
													
														// private method for UTF-8 encoding
														_utf8_encode : function (string) {
															string = string.replace(/\r\n/g,"\n");
															var utftext = "";
													
															for (var n = 0; n < string.length; n++) {
													
																var c = string.charCodeAt(n);
													
																if (c < 128) {
																	utftext += String.fromCharCode(c);
																}
																else if((c > 127) && (c < 2048)) {
																	utftext += String.fromCharCode((c >> 6) | 192);
																	utftext += String.fromCharCode((c & 63) | 128);
																}
																else {
																	utftext += String.fromCharCode((c >> 12) | 224);
																	utftext += String.fromCharCode(((c >> 6) & 63) | 128);
																	utftext += String.fromCharCode((c & 63) | 128);
																}
													
															}
													
															return utftext;
														},
													
														// private method for UTF-8 decoding
														_utf8_decode : function (utftext) {
															var string = "";
															var i = 0;
															var c = c1 = c2 = 0;
													
															while ( i < utftext.length ) {
													
																c = utftext.charCodeAt(i);
													
																if (c < 128) {
																	string += String.fromCharCode(c);
																	i++;
																}
																else if((c > 191) && (c < 224)) {
																	c2 = utftext.charCodeAt(i+1);
																	string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
																	i += 2;
																}
																else {
																	c2 = utftext.charCodeAt(i+1);
																	c3 = utftext.charCodeAt(i+2);
																	string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
																	i += 3;
																}
													
															}
													
															return string;
														}
													
													}


													
													function addFileList(list) {
														var list = Base64.decode(list);														
														var fileListObj = document.getElementById("fileList");
														var listTemp = list.split("!^|");
														var fileLabel = listTemp[0];
														var fileValue = listTemp[1];
														var fileListObj = document.getElementById("fileList");
														for(var i=0; i<fileListObj.length; i++) {
															if (stripLabelToValue(fileLabel).indexOf(fileListObj[i].getAttribute("value")) != -1) {
																var oOption = document.createElement("option");
																oOption.innerHTML= fileLabel;
																oOption.setAttribute("value",fileValue);
																fileListObj.replaceChild(oOption,fileListObj[i]);
																break;
															}
														}
													}
													
													function newLoadItem(fileValue) {
														var fileListObj = document.getElementById("fileList");
														var fileListObj = document.getElementById("fileList");
														for(var i=0; i<fileListObj.length; i++) {
															if (fileValue.indexOf(fileListObj[i].getAttribute("value")) != -1) {
																fileListObj[i].style.backgroundColor="#C8DAF3";
																break;
															}
														}	
													}
													
													function setFileList() {
														try {
															list = getUploadObj().GetVariable("/:listStr");						
														} catch(e) {
															alert(e.message);
														}
														var fileListObj = document.getElementById("fileList");										
														var listTemp = list.split("!^|");					
														for(var i=0; i<listTemp.length; i++) {						
															temp = listTemp[i].split('(_!');
															var fileName = temp[0];
															var fileSize = temp[1];
															if(fileName == undefined || fileSize == undefined) 
																continue;							
															var oOption = document.createElement("option");
															oOption.innerHTML= fileName+' ('+Math.ceil((fileSize/1024))+'KB) <?php echo _t('대기 중..');?>';
															oOption.setAttribute("value",fileName);
															oOption.style.backgroundColor="#A4C3F0";
															fileListObj.insertBefore(oOption,fileListObj[i]);
															if(i == 0) {
																newLoadItem(fileName);
															}
														}
														fileListObj.setAttribute('size',Math.max(8,Math.min(fileListObj.length,30)));
														getUploadObj().setAttribute('width',416);
														getUploadObj().setAttribute('height',25);
														//document.getElementById('uploadBtn').disabled=true;		
														if(isIE || isMoz) {
															document.getElementById('uploadBtn').style.display  = 'none';
															document.getElementById('stopUploadBtn').style.display  = 'inline';
														} else {
															document.getElementById('uploadBtn').disabled = true;
														}
													}
													
													function selectFileList(value) {
														selectedFiles = value.split("!^|");
														var fileListObj = document.getElementById("fileList");
														for(var i=0; i<fileListObj.length; i++) {
															for(var j=0; j<selectedFiles.length; j++) {
																if (fileListObj[i].getAttribute("value") == selectedFiles[j]) {
																	fileListObj[i].setAttribute("selected","selected");
																	selectAttachment();
																	break;
																}
																
																fileListObj[i].setAttribute("selected","");							
															}
														}
														refreshAttachFormSize();
													}
													
													function disabledDeleteBtn() {
														if(document.getElementById('fileList').length>0) {
															document.getElementById('deleteBtn').disabled = false;
														} else {
															document.getElementById('deleteBtn').disabled = true;
														}
													}
													
													function removeUploadList(list) {
														selectedFiles = list.split("!^|");
														var fileListObj = document.getElementById("fileList");
														for(var j=0; j<selectedFiles.length; j++) {
															for(var i=0; i<fileListObj.length; i++) {						
																if(selectedFiles[j] == undefined) 
																	continue;
																if (fileListObj[i].getAttribute("value") == selectedFiles[j]) {								
																	fileListObj.remove(i);
																	break;
																}
															}
														}
														refreshAttachFormSize();
													}
													
													function browser() {														
														entryManager.delay     = true;
														entryManager.nowsaving = true;
														getUploadObj().SetVariable('/:openBrowser','true');
													}
													
													function stopUpload() {
														getUploadObj().SetVariable('/:stopUpload','true');
													}
													
													function refreshFileSize() {
														try {
															var request = new HTTPRequest("POST", "<?php echo $param['fileSizePath'];?>"+entryManager.entryId);
															request.onVerify = function () {
																return true;
															}
															
															request.onSuccess = function() {
																try {
																	var result = this.getText("/response/result");
																	document.getElementById('fileSize').innerHTML = result;
																} catch(e) {
																
																}
															}
															
															request.onError = function() {
															}															
															request.send();
															
														} catch(e) {
															alert(e.message);
														}
													}
 
													function getUploadObj() {
														try {		
															var result;			
															if(isIE) 
																result = document.getElementById("uploader");
															else
																result = document.getElementById("uploader2");
															if (result == null)
																return false;
															else
																return result;
														} catch(e) {
															return false;
														}
													}
													refreshAttachFormSize();
												//]]>
											</script>
											
<?php
	require_once ROOT.'/script/detectFlash.inc';
	$maxSize = min( return_bytes(ini_get('upload_max_filesize')) , return_bytes(ini_get('post_max_size')) );
?>

												<script type="text/javascript">
													//<![CDATA[
													var uploaderStr = '';
													function reloadUploader() { 
														var requiredMajorVersion = 8;
														var requiredMinorVersion = 0;
														var requiredRevision = 0;
														var jsVersion = 1.0;
														var hasRightVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);
														uploaderStr = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" id="uploader"'
															+ 'width="0" height="0"'
															+ 'codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab">'
															+ '<param name="movie" value="<?php echo $service['path'];?>/script/uploader/uploader.swf" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" /><param name="scale" value="noScale" /><param name="wmode" value="transparent" /><param name="FlashVars" value="uploadPath=<?php echo $param['uploadPath'];?>'
															+ entryManager.entryId
															+ '&labelingPath=<?php echo $param['labelingPath'];?>'
															+ entryManager.entryId
															+ '&maxSize=<?php echo $maxSize;?>&sessionName=TSSESSION&sessionValue=<?php echo $_COOKIE['TSSESSION'];?>" />'
															+ '<embed id="uploader2" src="<?php echo $service['path'];?>/script/uploader/uploader.swf" flashvars="uploadPath=<?php echo $param['uploadPath'];?>'
															+ entryManager.entryId
															+ '&labelingPath=<?php echo $param['labelingPath'];?>'
															+ entryManager.entryId
															+ '&maxSize=<?php echo $maxSize;?>&sessionName=TSSESSION&sessionValue=<?php echo $_COOKIE['TSSESSION'];?>" width="1" height="1" align="middle" wmode="transparent" quality="high" bgcolor="#ffffff" scale="noScale" allowscriptaccess="always" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /><\/embed><\/object>';
														if (hasRightVersion && (isMoz || isIE)) {
															if(<?php echo (isset($service['flashuploader']) && $service['flashuploader'] === false) ? 'false' : 'true';?>) { writeCode(uploaderStr,'uploaderNest'); }
														}
														window.uploader= document.getElementById('uploader');
														refreshUploadButton();
													}
													//]]>
												</script>
											<div id="uploaderNest">
											</div>
<?php
}

function printEntryFileUploadButton($entryId) {
	global $service;

	$blogid = getBlogId();
?>

											<script type="text/javascript">
												//<![CDATA[

													var fileUploadNestOriginal = false;													

													function makeCrossDamainSubmit(uri,userAgent) {
														var property =new Array();
														property['ie'] = new Array();
														property['ie']['width'] = '225px';
														property['ie']['height'] = '25px';		
														
														property['moz'] = new Array();
														property['moz']['width'] = '215px';
														property['moz']['height'] = '22px';		
														
														property['etc'] = new Array();
														property['etc']['width'] = '240px';
														property['etc']['height'] = '22px';
														
																
														var str = '<iframe id="attachHiddenNest" src="' + uri + '" style="display: block; height: ' + property[userAgent]['height']+'; width: ' + property[userAgent]['width'] + ';" frameborder="no" scrolling="no"><\/iframe>';						
														document.getElementById('fileUploadNest').innerHTML = str + fileUploadNestOriginal;
														/*if (document.getElementById('attachHiddenNest_' + (attachId - 1))) {
															document.getElementById('attachHiddenNest_' + (attachId - 1)).style.display = "none";
															document.getElementById('attachHiddenNest_' + (attachId - 1)).style.width = 0;
															document.getElementById('attachHiddenNest_' + (attachId - 1)).style.height = 0;
														}
														attachId++;*/
													}

													function refreshUploadButton() {
														if (getUploadObj()) {
															try {
																if(fileUploadNestOriginal == false) {
																	fileUploadNestOriginal = document.getElementById('fileUploadNest').innerHTML;
																}
																var str1 = '<input type="button" id="uploadBtn" class="upload-button input-button" value="<?php echo _t('파일 업로드');?>" onclick="browser(); return false;" />';
																var str2 = '<input type="button" id="stopUploadBtn" class="stop-button input-button" value="<?php echo _t('업로드 중지');?>" onclick="stopUpload(); return false;" style="display: none;" />';
																document.getElementById('fileUploadNest').innerHTML = str1 + str2 + fileUploadNestOriginal;
															} catch(e) {
																
															}								
														} else {
															if(isIE) {
																makeCrossDamainSubmit(blogURL + "/owner/entry/attach/" + entryManager.entryId,"ie");
															} else if(isMoz) {
																makeCrossDamainSubmit(blogURL + "/owner/entry/attach/" + entryManager.entryId,"moz");
															} else {
																makeCrossDamainSubmit(blogURL + "/owner/entry/attach/" + entryManager.entryId,"etc");
															}
														}
													}
												//]]>
											</script>
										<div id="fileUploadNest" class="container">												
											<input type="button" id="deleteBtn" class="input-button" value="<?php echo _t('삭제하기');?>" onclick="deleteAttachment();" />
											<div id="fileSize">
<?php 
echo getAttachmentSizeLabel($blogid, $entryId);											
?>
											</div>
											<div id="fileDownload" class="system-message" style="display: none;"></div>
										</div>
<?php
}

function getAttachmentValue($attachment) {
	global $g_attachmentFolderPath;
	if (strpos($attachment['mime'], 'image') === 0) {
		if (getBlogSetting("waterMarkDefault") == "yes")
			$classString = 'class="tt-watermark" ';
		else if (getBlogSetting("resamplingDefault") == "yes")
			$classString = 'class="tt-resampling" ';
		else
			$classString = "";
		
		return "{$attachment['name']}|{$classString}width=\"{$attachment['width']}\" height=\"{$attachment['height']}\" alt=\"" . _text('사용자 삽입 이미지') . "\"";		
	} else {
		return "{$attachment['name']}|";
	}
}

function getPrettyAttachmentLabel($attachment) {
	if (strpos($attachment['mime'], 'image') === 0)
		return "{$attachment['label']} ({$attachment['width']}x{$attachment['height']} / ".getSizeHumanReadable($attachment['size']).')';
	else if(strpos($attachment['mime'], 'audio') !== 0 && strpos($attachment['mime'], 'video') !== 0) {
		if ($attachment['downloads']>0)
			return "{$attachment['label']} (".getSizeHumanReadable($attachment['size']).' / '._t('다운로드').':'.$attachment['downloads'].')';		
	}
	return "{$attachment['label']} (".getSizeHumanReadable($attachment['size']).')';
}
?>
