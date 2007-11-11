/// Copyright (c) 2004-2008, Tatter & Company / Tatter & Friends.
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

var xfnInputs, xfnResults, xfnMe;

function debug_dump(e,ret)
{
        var b = ""; 
        var c = 0;
        var i = 0;
        if( typeof e == 'string' ) { 
                e = $(e);
        }   
        for( a in e ) { 
                i++;
                if( typeof e[a] == 'function' ) { 
                        b += i + ". " + a + " : FUNCTION\r\n";
                } else {
                        b += i + ". " + a + " : " + e[a] + "\r\n";
                }   
                if( c > 40 ) { 
                        c = 0;
                        if( ret != undefined ) { 
                                alert(b);
                                b = ''; 
                        }   
                } else {
                        c++;
                }   
        }   
        if( c != 0 ) { 
                if( ret == undefined ) { 
                        alert(b);
                } else {
                        return b;
                }   
        }   
        return ''; 
}

function srcElement(event)
{
	return event.target || event.srcElement;
}

function getElementByXFNId(id)
{
	var elements = new Array();
	var re = new RegExp( '^.*_id_' + id + '$' );
	for( var i=0; i<xfnInputs.length; i++ ) {
		if( xfnInputs[i].id.match( re ) ) {
			elements.push(xfnInputs[i]);
		}
	}
	return elements;
}

function xfnClick(event)
{
	var id = STD.event(event).target.id;
	id = id.replace( /^.*_id_(\d+)$/, "$1" );
	var isMe = xfnMe[id].checked;
	var inputColl = xfnInputs[id];
	var inputs = '';
	for (i = 0; i < inputColl.length; i++) {
		inputColl[i].disabled = isMe;
		if (!isMe && inputColl[i].checked && inputColl[i].value != '') {
			inputs += inputColl[i].value + ' ';
		}
	}
	inputs = inputs.substr(0,inputs.length - 1);
	if (isMe) inputs='me';
	xfnResults[id].value = inputs;
}

function installOnClick()
{
	xfnInputs = new Array();
	xfnResults = new Array();
	xfnMe = new Array();
	var inputs = document.getElementsByTagName('input');
	for (var i = 0; i < inputs.length; i++) {		
		input_id = inputs[i].id;
		id = input_id.replace( /^.*_id_(\d+)$/, "$1" );
		if( xfnInputs[id] == undefined ) {
			xfnInputs[id] = new Array();
		}
		inputs[i].onclick = xfnClick;
		if( input_id.substr(0,3) == "me_" ) {
			xfnMe[id] = inputs[i];
		} else if( input_id.substr(0,4) == "xfn_" ) {
			xfnResults[id] = inputs[i];
		} else {
			xfnInputs[id].push( inputs[i] );
		}
	}
	for( id in xfnMe ) {
		if( !xfnMe[id].checked ) {
			continue;
		}
		var inputColl = xfnInputs[id];
		for (i = 0; i < inputColl.length; i++) {
			inputColl[i].disabled = true;
		}
		
	}
}

var backupWindowOnload = window.onload;
window.onload = function() { installOnClick(); if( backupWindowOnload ) backupWindowOnload(); }
