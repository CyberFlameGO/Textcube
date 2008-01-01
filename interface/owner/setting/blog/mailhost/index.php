<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'POST' => array(
		'useCustomSMTP' => array('bool', 'mandatory' => false ),
		'smtpHost' => array('ip'),
		'smtpPort' => array('number', 'min' => '1', 'max' => '65535' )
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireComponent( 'Textcube.Function.misc' );
requireStrictRoute();
if (!acl::check('group.creators'))
	respondResultPage(false);

$result = setSmtpServer( empty($_POST['useCustomSMTP']) ? 0:1, $_POST['smtpHost'], $_POST['smtpPort'] );
respondResultPage($result);
?>
