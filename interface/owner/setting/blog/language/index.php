<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'GET' => array(
		'language'=> array('string', 'default' => 'ko'),
		'blogLanguage'=> array('string', 'default' => 'ko')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();
if (!empty($_GET['language']) && setBlogLanguage($blogid, $_GET['language'], $_GET['blogLanguage'])) {
	respondResultPage(true);
}
respondResultPage(false);
?>
