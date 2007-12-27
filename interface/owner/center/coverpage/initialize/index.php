<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();

if (!array_key_exists('viewMode', $_REQUEST)) $_REQUEST['viewMode'] = '';
else $_REQUEST['viewMode'] = '?' . $_REQUEST['viewMode'];

POD::execute("DELETE FROM `{$database['prefix']}BlogSettings` WHERE `blogid` = {$blogid} AND `name` = 'coverpageOrder'");
header('Location: '. $blogURL . '/owner/center/coverpage' . $_REQUEST['viewMode']);
?>
