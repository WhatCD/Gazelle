<?
spl_autoload_register(function ($ClassName) {
	$FileName = '';
	switch ($ClassName) {
		case 'Artists':
			$FileName = 'artists.class';
			break;
		case 'Bencode':
			$FileName = 'bencode.class';
			break;
		case 'BencodeDecode':
			$FileName = 'bencodedecode.class';
			break;
		case 'BencodeTorrent':
			$FileName = 'bencodetorrent.class';
			break;
		case 'Bookmarks':
			$FileName = 'bookmarks.class';
			break;
		case 'Collages':
			$FileName = 'collages.class';
			break;
		case 'Format':
			$FileName = 'format.class';
			break;
		case 'Forums':
			$FileName = 'forums.class';
			break;
		case 'Forum':
			$FileName = 'forum.class';
			break;
		case 'ForumView':
			$FileName = 'forumsview.class';
			break;
		case 'G':
			$FileName = 'g.class';
			break;
		case 'ImageTools':
			$FileName = 'image_tools.class';
			break;
		case 'Inbox':
			$FileName = 'inbox.class';
			break;
		case 'LastFM':
			$FileName = 'lastfm.class';
			break;
		case 'MASS_USER_BOOKMARKS_EDITOR':
			$FileName = 'mass_user_bookmarks_editor.class';
			break;
		case 'MASS_USER_TORRENTS_EDITOR':
			$FileName = 'mass_user_torrents_editor.class';
			break;
		case 'MASS_USER_TORRENTS_TABLE_VIEW':
			$FileName = 'mass_user_torrents_table_view.class';
		    break;
		case 'Misc':
			$FileName = 'misc.class';
			break;
		case 'Permissions':
			$FileName = 'permissions.class';
			break;
		case 'Requests':
			$FileName = 'requests.class';
			break;
		case 'Rippy':
			$FileName = 'rippy.class';
			break;
		case 'Rules':
			$FileName = 'rules.class';
			break;
		case 'Sphinxql':
			$FileName = 'sphinxql.class';
			break;
		case 'SphinxqlQuery':
			$FileName = 'sphinxqlquery.class';
			break;
		case 'SphinxqlResult':
			$FileName = 'sphinxqlresult.class';
			break;
		case 'Tags':
			$FileName = 'tags.class';
			break;
		case 'TEXTAREA_PREVIEW':
			$FileName = 'textarea_preview.class';
			break;
		case 'Thread':
			$FileName = 'thread.class';
			break;
		case 'Tools':
			$FileName = 'tools.class';
			break;
		case 'TORRENT':
		case 'BENCODE_DICT':
		case 'BENCODE_LIST':
			$FileName = 'torrent.class';
			break;
		case 'Torrents':
			$FileName = 'torrents.class';
			break;
		case 'TorrentsDL':
			$FileName = 'torrentsdl.class';
			break;
		case 'Tracker':
			$FileName = 'tracker.class';
			break;
		case 'UserAgent':
			$FileName = 'useragent.class';
			break;
		case 'UserRank':
			$FileName = 'user_rank.class';
			break;
		case 'Users':
			$FileName = 'users.class';
			break;
		case 'View':
			$FileName = 'view.class';
			break;
		case 'Votes':
			$FileName = 'votes.class';
			break;
		case 'Wiki':
			$FileName = 'wiki.class';
			break;
		case 'Zip':
			$FileName = 'zip.class';
			break;
		default:
			die("Couldn't import class $ClassName");
	}
	require_once(SERVER_ROOT . "/classes/$FileName.php");
});