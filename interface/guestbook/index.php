<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
require ROOT . '/lib/includeForBlog.php';
if (false) {
	fetchConfigVal();
}
if(!empty($suri['value'])) {
	$suri['page'] = getGuestbookPageById($blogid,$suri['value']);
}
notifyComment();
require ROOT . '/lib/piece/blog/begin.php';
require ROOT . '/lib/piece/blog/guestbook.php';
require ROOT . '/lib/piece/blog/end.php';
?>
