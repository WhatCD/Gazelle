<?
authorize(true);
include(SERVER_ROOT.'/sections/bookmarks/functions.php');

// Number of users per page 
define('BOOKMARKS_PER_PAGE', '20');

if (empty($_REQUEST['type'])) { $_REQUEST['type'] = 'torrents'; }
switch ($_REQUEST['type']) {
	case 'torrents':
		require(SERVER_ROOT.'/sections/ajax/bookmarks/torrents.php');
		break;
	case 'artists':
		require(SERVER_ROOT.'/sections/ajax/bookmarks/artists.php');
		break;
	case 'collages':
		$_GET['bookmarks'] = 1;
		require(SERVER_ROOT.'/sections/ajax/collages/browse.php');
		break;
	case 'requests':
		include(SERVER_ROOT.'/sections/requests/functions.php');
		$_GET['type'] = 'bookmarks';
		require(SERVER_ROOT.'/sections/ajax/requests/requests.php');
		break;
	default:
		print 
			json_encode(
				array(
					'status' => 'failure'
				)
			);
		die();
}

?>
