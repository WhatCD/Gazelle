<?
//TODO
/*****************************************************************
Finish removing the take[action] pages and utilize the index correctly
Should the advanced search really only show if they match 3 perms?
Make sure all constants are defined in config.php and not in random files
*****************************************************************/
enforce_login();
include(SERVER_ROOT."/classes/validate.class.php");
$Val = NEW VALIDATE;

if (empty($_REQUEST['action'])) {
	$_REQUEST['action'] = '';
}

switch ($_REQUEST['action']) {
	case 'notify':
		include('notify_edit.php');
		break;
	case 'notify_handle':
		include('notify_handle.php');
		break;
	case 'notify_delete':
		authorize();
		if ($_GET['id'] && is_number($_GET['id'])) {
			$DB->query("DELETE FROM users_notify_filters WHERE ID='".db_string($_GET['id'])."' AND UserID='$LoggedUser[ID]'");
			$ArtistNotifications = $Cache->get_value('notify_artists_'.$LoggedUser['ID']);
			if (is_array($ArtistNotifications) && $ArtistNotifications['ID'] == $_GET['id']) {
				$Cache->delete_value('notify_artists_'.$LoggedUser['ID']);
			}
		}
		$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);
		header('Location: user.php?action=notify');
		break;
	case 'search':// User search
		if (check_perms('admin_advanced_user_search') && check_perms('users_view_ips') && check_perms('users_view_email')) {
			include('advancedsearch.php');
		} else {
			include('search.php');
		}
		break;
	case 'edit':
		include('edit.php');
		break;
	case 'take_edit':
		include('take_edit.php');
		break;
	case 'invitetree':
		include(SERVER_ROOT.'/sections/user/invitetree.php');
		break;
	case 'invite':
		include('invite.php');
		break;
	case 'take_invite':
		include('take_invite.php');
		break;
	case 'delete_invite':
		include('delete_invite.php');
		break;
	case 'sessions':
		include('sessions.php');
		break;
	case 'connchecker':
		include('connchecker.php');
		break;

	case 'permissions':
		include('permissions.php');
		break;
	case 'similar':
		include('similar.php');
		break;
	case 'moderate':
		include('takemoderate.php');
		break;
	case 'clearcache':
		if (!check_perms('admin_clear_cache') || !check_perms('users_override_paranoia')) {
			error(403);
		}
		$UserID = $_REQUEST['id'];
		$Cache->delete_value('user_info_'.$UserID);
		$Cache->delete_value('user_info_heavy_'.$UserID);
		$Cache->delete_value('subscriptions_user_new_'.$UserID);
		$Cache->delete_value('staff_pm_new_'.$UserID);
		$Cache->delete_value('inbox_new_'.$UserID);
		$Cache->delete_value('notifications_new_'.$UserID);
		$Cache->delete_value('collage_subs_user_new_'.$UserID);
		include(SERVER_ROOT.'/sections/user/user.php');
		break;

	// Provide public methods for Last.fm data gets.
	case 'lastfm_compare':
		if (isset($_GET['username'])) {
			echo LastFM::compare_user_with($_GET['username']);
		}
		break;
	case 'lastfm_last_played_track':
		if (isset($_GET['username'])) {
			echo LastFM::get_last_played_track($_GET['username']);
		}
		break;
	case 'lastfm_top_artists':
		if (isset($_GET['username'])) {
			echo LastFM::get_top_artists($_GET['username']);
		}
		break;
	case 'lastfm_top_albums':
		if (isset($_GET['username'])) {
			echo LastFM::get_top_albums($_GET['username']);
		}
		break;
	case 'lastfm_top_tracks':
		if (isset($_GET['username'])) {
			echo LastFM::get_top_tracks($_GET['username']);
		}
		break;
	case 'lastfm_clear_cache':
		if (isset($_GET['username']) && isset($_GET['uid'])) {
			echo LastFM::clear_cache($_GET['username'],$_GET['uid']);
		}
		break;
	case 'take_donate':
		break;
	case 'take_update_rank':
		break;
	default:
		if (isset($_REQUEST['id'])) {
			include(SERVER_ROOT.'/sections/user/user.php');
		} else {
			header('Location: index.php');
		}
}
?>
