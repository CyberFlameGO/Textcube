// 입력창을 10ms마다 체크하면서 값이 변했으면 request를 보낸다.
// 파이어폭스에서는 한글을 입력할때 keydown 이벤트가 발생하지 않기 때문에
// 값이 변하는지 계속 보고있어야 한다.
function eolinTagFunction_WatchInputBox(id)
{
	try
	{
		var instance = document.getElementById(id).instance;
		var input = instance.getInput();

		// 값이 달라졌는지 체크
		if(input.value != instance.typingText)
		{
			instance.typingText = input.value;
			instance.requestSuggestion();
		}
	}
	catch(e) { }
}

// 서버에서 보내오는 필터로 로컬 tag suggestion
function eolinTagFunction_showLocalSuggestion(id, cursor, filter)
{
	// Container의 ID를 통해 instance를 가져온다
	try { var instance = document.getElementById(id).instance; }
	catch(e) { return; }

	// 보내온 cursor와 현재 cursor가 같지 않으면 필요없는 데이터이므로 버린다
	// 텍스트 박스를 벗어난 후에 도착한 데이터도 버린다
	if(instance.cursor != cursor || !instance.isTyping)
		return;

	var input = instance.getInput();

	// 편집중인 내용이 빈 상태면 suggestion 윈도우 감추고 리턴
	if(input.value.trim() == "")
	{
		instance.hideSuggestion();
		return;
	}

	var xmlhttp = createHttp();

	if(xmlhttp)
	{
		xmlhttp.open("GET", blogURL + "/suggest/?id=" + id + "&cursor=" + cursor + "&filter=" + encodeURIComponent(filter), true);
		xmlhttp.onreadystatechange = function()
		{
			if(xmlhttp.readyState == 4)
			{
				try {
					var doc = xmlhttp.responseXML;
					var root = doc.getElementsByTagName("response")[0];
					var id = root.getAttribute("id");
					var cursor = root.getAttribute("cursor");

					var instance = document.getElementById(id).instance;

					if(instance.cursor == cursor)
					{
						var tags = new Array();

						var tagItems = root.getElementsByTagName("tag");

						for(var i=0; i<tagItems.length; i++)
							tags[tags.length] = tagItems[i].firstChild.nodeValue;

						// 중복될 항목들을 미리 제거
						for(var i=0; i<tags.length; i++)
						{
							for(var j=0; j<instance.suggestion.childNodes.length; j++)
							{
								if(tags[i] == instance.suggestion.childNodes[j].innerHTML.replace(new RegExp("</?strong>", "gi"), ""))
								{
									instance.suggestion.removeChild(instance.suggestion.childNodes[j]);
									break;
								}
							}
						}

						var htmlText = new StringBuffer();

						for(var i=0; i<tags.length; i++)
						{
							htmlText.append("<li onmouseover=\"this.className='hover'\" onmouseout=\"this.className=''\" onmousedown=\"this.parentNode.instance.suggestionMouseClick(this)\" style=\"background-color: #ccb\"><strong>");
							htmlText.append(tags[i].substring(0, input.value.length).htmlspecialchars().replaceAll("&amp;", "&"));
							htmlText.append("</strong>");
							htmlText.append(tags[i].substring(input.value.length).htmlspecialchars().replaceAll("&amp;", "&"));
							htmlText.append("</li>");
						}

						htmlText.append(instance.suggestion.innerHTML);

						instance.suggestion.innerHTML = htmlText.toString();

						while(instance.suggestion.childNodes.length > 10)
							instance.suggestion.removeChild(instance.suggestion.childNodes[instance.suggestion.childNodes.length-1]);
					}
				} catch(e) { }
			}
		}
		xmlhttp.send(null);
		delete xmlhttp;
	}
}

// 서버에서 보내오는 내용을 실행하는 함수
function eolinTagFunction_showSuggestion()
{
	// Container의 ID를 통해 instance를 가져온다
	try { var instance = document.getElementById(arguments[0]).instance; }
	catch(e) { return; }

	debug("<span style=\"color: red\">Received " + instance.cursor + "</span>");

	// 보내온 cursor와 현재 cursor가 같지 않으면 필요없는 데이터이므로 버린다
	// 텍스트 박스를 벗어난 후에 도착한 데이터도 버린다
	if(instance.cursor != arguments[1] || !instance.isTyping)
		return;

	var input = instance.getInput();

	// 편집중인 내용이 빈 상태면 suggestion 윈도우 감추고 리턴
	if(input.value.trim() == "")
	{
		instance.hideSuggestion();
		return;
	}

	// input box의 위치를 구해서 suggestion window의 위치를 결정한다
	// TODO : suggestion window가 표시되면서 스크롤바가 생기면 위치를 다시 잡아줘야 한다
	instance.suggestion.style.left = getOffsetLeft(input) + "px";
	instance.suggestion.style.top = getOffsetTop(input) + input.offsetHeight + "px";

	// suggestion window의 깜빡임을 방지하기 위해 dom을 이용한 삭제/삽입 대신 innerHTML을 이용한다
	var htmlText = new StringBuffer();

	// 전송된 결과가 있을때
	if(arguments[2] == 0)
	{
		for(var i=3; i<arguments.length; i++)
		{
			arguments[i] = arguments[i].replaceAll("&quot;", '"');
			htmlText.append("<li onmouseover=\"this.className='hover'\" onmouseout=\"this.className=''\" onmousedown=\"this.parentNode.instance.suggestionMouseClick(this)\"><strong>");
			htmlText.append(arguments[i].substring(0, input.value.length).htmlspecialchars().replace("&amp;", "&"));
			htmlText.append("</strong>");
			htmlText.append(arguments[i].substring(input.value.length).htmlspecialchars().replace("&amp;", "&"));
			htmlText.append("</li>");
		}
	}
	// 빈 값을 전송 받았을 때
	else
	{
		htmlText.append("<li class=\"disabled\">");
		htmlText.append("<strong>");
		htmlText.append(input.value.htmlspecialchars());
		htmlText.append("</strong> - " + arguments[3] + "</li>");
	}

	/* TODO : temporary code */
	try {
		document.getElementById("fileList").style.visibility = "hidden";
	} catch(e) { }

	instance.suggestion.innerHTML = htmlText.toString();
	instance.suggestion.style.display = "block";
	instance.isSuggestionShown = true;

	// 이전에 추가했던 suggestion 노드가 있으면 삭제한다
	try { document.body.removeChild(instance.suggestion) } catch(e) { };
	document.body.appendChild(instance.suggestion);
}

function Tag(container, language)
{
	this.name = "Eolin Tag Object";
	this.copyright = "Tatter & Company";

	this.isSettingValue = false;	// setValue가 짧은 시간에 여러번 실행될때 Safari가 죽어버리는 문제 해결

	this.instance = this;	// requestSuggestion() 함수에서 참조한다
	this.cursor = 0;		// 비동기로 전송되는 스크립트의 짝을 맞추기 위한 커서

	this.isTyping = false;			// input box에 포커스가 있는지 여부
	this.isSuggestionShown = false;	// suggest window가 보여지고 있는지의 여부

	this.typingText = "";			// eolinTagFunction_WatchInputBox에서 input box의 값을 감시하기 위한 변수

	this.inputClassName = "";

	this.language = "ko";
	if(typeof language != "undefined")
		this.language = language;

	this.container = container;		// tag list가 들어갈 container
	this.container.instance = this;

	// suggestion window
	this.suggestion = document.createElement("ul");
	this.suggestion.instance = this;
	this.suggestion.selectedIndex = 0;
	this.suggestion.className = "eolinSuggest";
	this.suggestion.style.margin = "0px";
	this.suggestion.style.padding = "0px";
	this.suggestion.style.listStyleType = "none";
	this.suggestion.style.position = "absolute";
	this.suggestion.style.display = "none";
	this.suggestion.style.zIndex = "999";

	// 10ms마다 input box의 값이 변했는지 체크
	setInterval("eolinTagFunction_WatchInputBox('" + this.container.id + "')", 10);

	// 마지막 노드에 들어가는 input box
	this.inputOnLast = this.createSuggestInput();
	this.inputTemporary = null;

	// tag list
	this.tagList = document.createElement("ul");
	this.tagList.instance = this;

	// tag list first child
	var listItem = document.createElement("li");
	listItem.className = "firstChild"
	listItem.appendChild(this.inputOnLast);

	this.tagList.appendChild(listItem);

	this.container.appendChild(this.tagList);
}

// 마지막노드의 input box를 편집중인지 중간의 list item을 눌러 편집중인지를 리턴
Tag.prototype.isTemporaryEditing = function()
{
	return (this.inputTemporary != null);
}

Tag.prototype.setInputClassName = function(str)
{
	this.inputClassName = str;
	this.inputOnLast.className = str;
	if(this.inputTemporary) this.inputTemporary.className = str;
}

// 현재 편집중인 input box
Tag.prototype.getInput = function()
{
	return (this.inputTemporary == null) ? this.inputOnLast : this.inputTemporary;
}

// cross browser event
Tag.prototype.adjustEventCompatibility = function(event)
{
	if(navigator.appName == "Microsoft Internet Explorer")
	{
		event = window.event;
		event.target = event.srcElement;
	}

	return event;
}

// input box를 생성한다
Tag.prototype.createSuggestInput = function()
{
	var input = document.createElement("input");
	input.instance = this;
	input.className = this.inputClassName;
	input.setAttribute("autocomplete", "off");
	input.onblur = function() {
		var instance = this.instance;

		instance.isTyping = false;
		instance.hideSuggestion();
		instance.setValue(this.value);
	}
	input.onclick = this.requestSuggestion;
	input.onkeydown = function(event) {
		var instance = this.instance;

		instance.isTyping = true;

		event = instance.adjustEventCompatibility(event);

		switch(event.keyCode)
		{
			case 8:		// BackSpace
				if(this.value == "")
					instance.moveBack();
				else
					return event.keyCode;
				break;
			case 13:	// Enter
				instance.setValue(this.value);
				break;
			case 27:	// ESC
				instance.hideSuggestion();
				break;
			case 38:	// Key Up
				instance.moveUp();
				break;
			case 40:	// Key Down
				instance.moveDown();
				break;
			default:
				return event.keyCode;
		}

		event.returnValue = false;
		event.cancelBubble = true;

		try { event.preventDefault(); } catch(e) { }

		return false;
	}

	return input;
}

// suggestion window의 항목을 클릭하면 값을 세팅한다
Tag.prototype.suggestionMouseClick = function(obj)
{
	var input = this.getInput();

	this.hideSuggestion();
	this.setValue(obj.innerHTML.replace(new RegExp("</?strong>", "gi"), "").replaceAll("&amp;", "&"));
}

// script의 src를 변경해 서버로부터 tag 리스트를 전송받는다
Tag.prototype.requestSuggestion = function()
{
	var instance = this.instance;

	instance.isTyping = true;
	instance.cursor++;

	debug("Request " + instance.cursor);

	var script = document.createElement("script");
//	script.setAttribute("id", "eolinTagScript");
	script.setAttribute("src", "http://suggest.eolin.com/tag/tatter/?id=" + instance.container.getAttribute("id") + "&cursor=" + instance.cursor + "&language=" + instance.language + "&word=" + encodeURIComponent(instance.getInput().value));
//	if(document.getElementById("eolinTagScript"))
//		document.body.removeChild(document.getElementById("eolinTagScript"));
	document.body.appendChild(script);
}

// tag list의 이전 항목으로 이동
Tag.prototype.moveBack = function()
{
	var prevNode = this.getInput().parentNode.previousSibling;

	if(this.tagList.childNodes.length > 1 && prevNode)
	{
		this.hideSuggestion();

		var text = prevNode.innerHTML.unhtmlspecialchars();

		prevNode.parentNode.removeChild(prevNode);

		this.tagList.firstChild.className = "firstChild";
		this.getInput().value = text;
	}
}

// suggestion window 커서를 위로 이동
Tag.prototype.moveUp = function()
{
	if(this.isSuggestionShown)
	{
		this.cursor++;
		this.suggestion.selectedIndex--;

		if(this.suggestion.selectedIndex < 1)
			this.suggestion.selectedIndex = this.suggestion.childNodes.length;

		this.highlightRow();
	}
}

// suggestion window 커서를 아래로 이동
Tag.prototype.moveDown = function()
{
	if(this.isSuggestionShown)
	{
		this.cursor++;
		this.suggestion.selectedIndex++;

		if(this.suggestion.selectedIndex > this.suggestion.childNodes.length)
			this.suggestion.selectedIndex = 1;

		this.highlightRow();
	}
}

// 이동 후에 현재 열의 style class를 변경한다
Tag.prototype.highlightRow = function()
{
	// suggest window가 보이지 않는 상태거나 전송받은 내용이 없으면 제낌
	if(this.isSuggestionShown && this.suggestion.childNodes[0].className != "disabled")
	{
		for(var i=0; i<this.suggestion.childNodes.length; i++)
			this.suggestion.childNodes[i].className = (i == this.suggestion.selectedIndex - 1) ? "hover" : "";

		// 선택된 열의 값을 input box에 채운다
		this.getInput().value = this.typingText = this.suggestion.childNodes[this.suggestion.selectedIndex-1].innerHTML.replace(new RegExp("</?strong>", "gi"), "").unhtmlspecialchars();
	}
}

// 노드의 값을 배열로 반환한다
Tag.prototype.getValues = function()
{
	var values = new Array();

	for(var i=0; i<this.tagList.childNodes.length-1; i++)
		values[i] = this.tagList.childNodes[i].innerHTML.trim().unhtmlspecialchars();

	return values;
}

// 마지막 노드의 input box에 값을 추가하거나 임시 input box의 값을 tag list에 세팅한다
Tag.prototype.setValue = function(str)
{
	if(this.isSettingValue)
		return;
	else
		this.isSettingValue = true;

	this.hideSuggestion();

	if(this.isTemporaryEditing())
	{
		this.inputTemporary.parentNode.onclick = this.tagListMouseClick;

		if(str.trim() == "")
			this.inputTemporary.parentNode.parentNode.removeChild(this.inputTemporary.parentNode);
		else
			this.inputTemporary.parentNode.innerHTML = str;

		this.typingText = "";
		this.inputTemporary = null;
	}
	else if(str.trim() != "")
	{
		var inputContainer = this.tagList.lastChild;
		inputContainer.className = "";

		var listItem = document.createElement("li");
		listItem.onclick = this.tagListMouseClick;
		listItem.appendChild(document.createTextNode(str));

		this.tagList.removeChild(inputContainer);
		this.tagList.appendChild(listItem);
		this.tagList.appendChild(inputContainer);

		this.typingText = "";
		this.inputOnLast.value = "";
		this.focusOnInput();
	}

	this.tagList.firstChild.className = "firstChild";

	this.isSettingValue = false;
}

// tag list를 마우스로 클릭하면 input box로 변신시키기 위한 이벤트 핸들러
Tag.prototype.tagListMouseClick = function()
{
	var instance = this.parentNode.instance;

	instance.inputTemporary = instance.createSuggestInput();
	instance.inputTemporary.value = this.innerHTML.unhtmlspecialchars();
	instance.typingText = this.innerHTML;

	this.innerHTML = "";
	this.onclick = null;
	this.appendChild(instance.inputTemporary);

	instance.focusOnInput();
	instance.requestSuggestion();
}

// suggestion window를 숨긴다
Tag.prototype.hideSuggestion = function()
{
	this.isSuggestionShown = false;
	this.suggestion.style.display = "none";
	this.suggestion.selectedIndex = 0;

	/* TODO : temporary code */
	try {
		document.getElementById("fileList").style.visibility = "visible";
	} catch(e) { }
}

// 적절한 input box로 포커스를 이동시킨다
Tag.prototype.focusOnInput = function()
{
	this.getInput().focus();
	this.getInput().select();
}

// 이하 잡 유틸들

function getOffsetTop(obj)
{ return obj ? obj.offsetTop + getOffsetTop(obj.offsetParent) : 0; }

function getOffsetLeft(obj)
{ return obj ? obj.offsetLeft + getOffsetLeft(obj.offsetParent) : 0; }

var StringBuffer = function()
{ this.buffer = new Array(); }

StringBuffer.prototype.append=function(str)
{ this.buffer[this.buffer.length] = str; }

StringBuffer.prototype.toString = function()
{ return this.buffer.join(""); }

if(!String.prototype.trim) {
	String.prototype.trim = function()
	{ return this.replace(new RegExp("(^\\s*)|(\\s*$)", "g"), ""); }
}

if(!String.prototype.htmlspecialchars) {
	String.prototype.htmlspecialchars = function()
	{ return this.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll("<", "&gt;"); }
}

if(!String.prototype.unhtmlspecialchars) {
	String.prototype.unhtmlspecialchars = function()
	{ return this.replaceAll("&amp;", "&").replaceAll("&lt;", "<").replaceAll("&gt;", ">"); }
}

var x=0;
function debug(s){try{document.getElementById("debug").innerHTML=++x+")"+s+"<br>"+document.getElementById("debug").innerHTML}catch(e){}}