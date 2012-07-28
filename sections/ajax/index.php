<?
/*
AJAX Switch Center

This page acts as an AJAX "switch" - it's called by scripts, and it includes the required pages. 

The required page is determined by $_GET['action']. 

*/

enforce_login();

header('Content-Type: application/json; charset=utf-8');

switch ($_GET['action']){
	// things that (may be) used on the site
	case 'upload_section':
		// Gets one of the upload forms
		require(SERVER_ROOT.'/sections/ajax/upload.php');
		break;
	case 'preview':
		require('preview.php');
		break;
	case 'torrent_info':
		require('torrent_info.php');
		break;
	case 'giveback_report':
		require('giveback_report.php');
		break;
	case 'grab_report':
		require('grab_report.php');
		break;
	case 'stats':
		require(SERVER_ROOT.'/sections/ajax/stats.php');
		break;
	
	case 'checkprivate':
		include('checkprivate.php');
		break;
	// things not yet used on the site
	case 'torrentgroup':
		require('torrentgroup.php');
		break;
	case 'tcomments':
		require(SERVER_ROOT.'/sections/ajax/tcomments.php');
		break;
	case 'user':
		require(SERVER_ROOT.'/sections/ajax/user.php');
		break;
	case 'forum':
		require(SERVER_ROOT.'/sections/ajax/forum/index.php');
		break;
	case 'top10':
		require(SERVER_ROOT.'/sections/ajax/top10/index.php');
		break;
	case 'browse':
		require(SERVER_ROOT.'/sections/ajax/browse.php');
		break;
	case 'usersearch':
		require(SERVER_ROOT.'/sections/ajax/usersearch.php');
		break;
	case 'requests':
		require(SERVER_ROOT.'/sections/ajax/requests.php');
		break;
	case 'artist':
		require(SERVER_ROOT.'/sections/ajax/artist.php');
		break;
	case 'inbox':
		require(SERVER_ROOT.'/sections/ajax/inbox/index.php');
		break;
	case 'subscriptions':
		require(SERVER_ROOT.'/sections/ajax/subscriptions.php');
		break;
	case 'index':
		require(SERVER_ROOT.'/sections/ajax/info.php');
		break;
	case 'bookmarks':
		require(SERVER_ROOT.'/sections/ajax/bookmarks/index.php');
		break;
	case 'announcements':
		require(SERVER_ROOT.'/sections/ajax/announcements.php');
                break;
	case 'notifications':
		require(SERVER_ROOT.'/sections/ajax/notifications.php');
		break;
	case 'request':
		require(SERVER_ROOT.'/sections/ajax/request.php');
		break;
	case 'loadavg':
		require(SERVER_ROOT.'/sections/ajax/loadavg.php');
		break;
	case 'better':
		require(SERVER_ROOT.'/sections/ajax/better/index.php');
		break;
	case 'password_validate':
                require(SERVER_ROOT.'/sections/ajax/password_validate.php');
                break;
	case 'similar_artists':
                require(SERVER_ROOT.'/sections/ajax/similar_artists.php');
                break;
	case 'userhistory':
                require(SERVER_ROOT.'/sections/ajax/userhistory/index.php');
                break;
	default:
		// If they're screwing around with the query string
		print json_encode(array('status' => 'failure'));
}

function pullmediainfo($Array) {
	$NewArray = array();
	foreach ($Array as $Item) {
		$NewArray[] = array(
			'id' => (int) $Item['id'],
			'name' => $Item['name']
		);
	}
	return $NewArray;
}

?>
