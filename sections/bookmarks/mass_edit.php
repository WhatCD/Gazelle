<?php

authorize();

if ($UserID != $LoggedUser['ID'] || !Bookmarks::can_bookmark('torrent')) {
	error(403);
}

if ($_POST['type'] === 'torrents') {
	// require_once SERVER_ROOT.'/classes/mass_user_bookmarks_editor.class.php'; //Bookmark Updater Class
	$BU = new MASS_USER_BOOKMARKS_EDITOR;
	if ($_POST['delete']) {
		$BU->mass_remove();
	} elseif ($_POST['update']) {
		$BU->mass_update();
	}
}

header('Location: bookmarks.php?type=torrents');
