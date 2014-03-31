<?
/*
AJAX Switch Center

This page acts as an AJAX "switch" - it's called by scripts, and it includes the required pages.

The required page is determined by $_GET['action'].

*/

enforce_login();

/*	AJAX_LIMIT = array(x,y) = 'x' requests every 'y' seconds.
	e.g. array(5,10) = 5 requests every 10 seconds	*/
$AJAX_LIMIT = array(5,10);
$LimitedPages = array('tcomments','user','forum','top10','browse','usersearch','requests','artist','inbox','subscriptions','bookmarks','announcements','notifications','request','better','similar_artists','userhistory','votefavorite','wiki','torrentgroup','news_ajax','user_recents', 'collage', 'raw_bbcode');

// These users aren't rate limited.
// This array should contain user IDs.
$UserExceptions = array(

		);
$UserID = $LoggedUser['ID'];
header('Content-Type: application/json; charset=utf-8');
//	Enforce rate limiting everywhere except info.php
if (!in_array($UserID, $UserExceptions) && isset($_GET['action']) && in_array($_GET['action'], $LimitedPages)) {
	if (!$UserRequests = $Cache->get_value('ajax_requests_'.$UserID)) {
		$UserRequests = 0;
		$Cache->cache_value('ajax_requests_'.$UserID, '0', $AJAX_LIMIT[1]);
	}
	if ($UserRequests > $AJAX_LIMIT[0]) {
		json_die("failure", "rate limit exceeded");
	} else {
		$Cache->increment_value('ajax_requests_'.$UserID);
	}
}

switch ($_GET['action']) {
	// things that (may be) used on the site
	case 'upload_section':
		// Gets one of the upload forms
		require(SERVER_ROOT . '/sections/ajax/upload.php');
		break;
	case 'preview':
		require('preview.php');
		break;
	case 'torrent_info':
		require('torrent_info.php');
		break;
	case 'stats':
		require(SERVER_ROOT . '/sections/ajax/stats.php');
		break;

	case 'checkprivate':
		include('checkprivate.php');
		break;
	// things not yet used on the site
	case 'torrent':
		require('torrent.php');
		break;
	case 'torrentgroup':
		require('torrentgroup.php');
		break;
	case 'torrentgroupalbumart':		// so the album art script can function without breaking the ratelimit
		require(SERVER_ROOT . '/sections/ajax/torrentgroupalbumart.php');
		break;
	case 'tcomments':
		require(SERVER_ROOT . '/sections/ajax/tcomments.php');
		break;
	case 'user':
		require(SERVER_ROOT . '/sections/ajax/user.php');
		break;
	case 'forum':
		require(SERVER_ROOT . '/sections/ajax/forum/index.php');
		break;
	case 'top10':
		require(SERVER_ROOT . '/sections/ajax/top10/index.php');
		break;
	case 'browse':
		require(SERVER_ROOT . '/sections/ajax/browse.php');
		break;
	case 'usersearch':
		require(SERVER_ROOT . '/sections/ajax/usersearch.php');
		break;
	case 'requests':
		require(SERVER_ROOT . '/sections/ajax/requests.php');
		break;
	case 'artist':
		require(SERVER_ROOT . '/sections/ajax/artist.php');
		break;
	case 'inbox':
		require(SERVER_ROOT . '/sections/ajax/inbox/index.php');
		break;
	case 'subscriptions':
		require(SERVER_ROOT . '/sections/ajax/subscriptions.php');
		break;
	case 'index':
		require(SERVER_ROOT . '/sections/ajax/info.php');
		break;
	case 'bookmarks':
		require(SERVER_ROOT . '/sections/ajax/bookmarks/index.php');
		break;
	case 'announcements':
		require(SERVER_ROOT . '/sections/ajax/announcements.php');
		break;
	case 'notifications':
		require(SERVER_ROOT . '/sections/ajax/notifications.php');
		break;
	case 'request':
		require(SERVER_ROOT . '/sections/ajax/request.php');
		break;
	case 'loadavg':
		require(SERVER_ROOT . '/sections/ajax/loadavg.php');
		break;
	case 'better':
		require(SERVER_ROOT . '/sections/ajax/better/index.php');
		break;
	case 'password_validate':
		require(SERVER_ROOT . '/sections/ajax/password_validate.php');
		break;
	case 'similar_artists':
		require(SERVER_ROOT . '/sections/ajax/similar_artists.php');
		break;
	case 'userhistory':
		require(SERVER_ROOT . '/sections/ajax/userhistory/index.php');
		break;
	case 'votefavorite':
		require(SERVER_ROOT . '/sections/ajax/takevote.php');
		break;
	case 'wiki':
		require(SERVER_ROOT . '/sections/ajax/wiki.php');
		break;
	case 'send_recommendation':
		require(SERVER_ROOT . '/sections/ajax/send_recommendation.php');
		break;
	case 'get_friends':
		require(SERVER_ROOT . '/sections/ajax/get_friends.php');
		break;
	case 'news_ajax':
		require(SERVER_ROOT . '/sections/ajax/news_ajax.php');
		break;
	case 'community_stats':
		require(SERVER_ROOT . '/sections/ajax/community_stats.php');
		break;
	case 'user_recents':
		require(SERVER_ROOT . '/sections/ajax/user_recents.php');
		break;
	case 'collage':
		require(SERVER_ROOT . '/sections/ajax/collage.php');
		break;
	case 'raw_bbcode':
		require(SERVER_ROOT . '/sections/ajax/raw_bbcode.php');
		break;
	case 'get_user_notifications':
		require(SERVER_ROOT . '/sections/ajax/get_user_notifications.php');
		break;
	case 'clear_user_notification':
		require(SERVER_ROOT . '/sections/ajax/clear_user_notification.php');
		break;
	case 'pushbullet_devices':
		require(SERVER_ROOT . '/sections/ajax/pushbullet_devices.php');
		break;
	default:
		// If they're screwing around with the query string
		json_die("failure");
}

function pullmediainfo($Array) {
	$NewArray = array();
	foreach ($Array as $Item) {
		$NewArray[] = array(
			'id' => (int)$Item['id'],
			'name' => $Item['name']
		);
	}
	return $NewArray;
}

?>
