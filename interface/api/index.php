<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
require ROOT . '/lib/includeForBlog.php';

requireComponent( "Eolin.PHP.XMLStruct" );
requireComponent( "Eolin.PHP.XMLTree" );
requireComponent( "Eolin.PHP.XMLRPC" );
requireComponent( "Textcube.Control.RSS" );
requireComponent( "Textcube.Control.Auth" );
requireComponent( "Textcube.Data.Post" );
requireComponent( "Textcube.Data.Category" );
requireModel( "blog.api" );

/*--------- API main ---------------*/

if (getBlogSetting('useBlogAPI', 0) != 1) {
	respondNotFoundPage();
	exit;
}

function SendRSD()
{
	global $hostURL, $blogURL;
	global $blogid;
	$homeurl = $hostURL.$blogURL;
	$apiurl = $homeurl . "/api";
	$blogid = $blogid;

	header( "Content-type: text/xml", true );

	print( '<?xml version="1.0" encoding="utf-8" ?> 
<rsd xmlns="http://archipelago.phrasewise.com/rsd" version="1.0">
    <service xmlns="">
        <engineName>Textcube</engineName> 
        <engineLink>http://www.textcube.org/</engineLink>
        <homePageLink>' . $homeurl . '/</homePageLink>
        <apis>
        		<api name="MovableType" preferred="true" apiLink="' . $apiurl . '" blogID="' . $blogid . '" />
                <api name="MetaWeblog" preferred="false" apiLink="' . $apiurl . '" blogID="' . $blogid . '" />
                <api name="Blogger" preferred="false" apiLink="' . $apiurl . '" blogID="' . $blogid . '" />
        </apis>
    </service>
</rsd>' );
}

if( substr( $_SERVER["REQUEST_URI"], -8 ) == "/api?rsd" )
{
	SendRSD();
}
else
{
	if( strpos( $_SERVER["REQUEST_URI"], "api?rnd" ) !== false ) /* Writely.com */
	{
		api_setHint( "TagsFromCategories" );
	}
	if( isset($_GET['category']) ) {
		api_setHint( "Category", $_GET['category'] );
	}
	api_BlogAPI();
}

?>
