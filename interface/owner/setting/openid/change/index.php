<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

$IV = array(
	'POST' => array(
		'openidonlycomment' => array('bool', 'mandatory' => true ),
		'openidlogodisplay' => array('bool', 'mandatory' => true )
	)
);

require ROOT . '/lib/includeForBlogOwner.php';
requireComponent( 'Textcube.Control.Openid' );
requireLibrary('blog.skin');
requireStrictRoute();
requireModel( 'common.plugin' );

if( OpenIDConsumer::setComment( $_POST['openidonlycomment'] ) &&
	OpenIDConsumer::setOpenIDLogoDisplay( $_POST['openidlogodisplay'] ) ) {
	if( !empty($_POST['openidonlycomment']) || !empty($_POST['openidlogodisplay']) ) {
		activatePlugin('CL_OpenID');
	}
	Skin::purgeCache();
	respond::ResultPage(0);
} else {
	respond::ResultPage(-1);
}
?>
