<?
enforce_login();
include(SERVER_ROOT.'/sections/bookmarks/functions.php');

// Number of users per page 
define('BOOKMARKS_PER_PAGE', '20');

if (empty($_REQUEST['action'])) { $_REQUEST['action'] = 'view'; }
switch($_REQUEST['action']) {
	case 'add':
		require(SERVER_ROOT.'/sections/bookmarks/add.php');
		break;


	case 'remove':
		require(SERVER_ROOT.'/sections/bookmarks/remove.php');
		break;
		
	case 'remove_snatched':
		authorize();
		$DB->query("DELETE b FROM bookmarks_torrents AS b WHERE b.UserID='".$LoggedUser['ID']."' AND b.GroupID IN(SELECT DISTINCT t.GroupID FROM torrents AS t INNER JOIN xbt_snatched AS s ON s.fid=t.ID AND s.uid='".$LoggedUser['ID']."')");
		$Cache->delete_value('bookmarks_torrent_'.$UserID);
		$Cache->delete_value('bookmarks_torrent_'.$UserID.'_full');
		header('Location: bookmarks.php');
		die();
		break;
		
	case 'view':
		if (empty($_REQUEST['type'])) { $_REQUEST['type'] = 'torrents'; }
		switch ($_REQUEST['type']) {
			case 'torrents':
				require(SERVER_ROOT.'/sections/bookmarks/torrents.php');
				break;
			case 'artists':
				require(SERVER_ROOT.'/sections/bookmarks/artists.php');
				break;
			case 'collages':
				$_GET['bookmarks'] = 1;
				require(SERVER_ROOT.'/sections/collages/browse.php');
				break;
			case 'requests':
				include(SERVER_ROOT.'/sections/requests/functions.php');
				$_GET['type'] = 'bookmarks';
				require(SERVER_ROOT.'/sections/requests/requests.php');
				break;
			default:
				error(404);
		}
		break;
	default:
		error(404);
}
?>
