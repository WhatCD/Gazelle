<?
//TODO
/*****************************************************************
Finish removing the take[action] pages and utilize the index correctly
Should the advanced search really only show if they match 3 perms?
Make sure all constants are defined in config.php and not in random files
*****************************************************************/
enforce_login();
include(SERVER_ROOT."/classes/class_validate.php");
$Val=NEW VALIDATE;

if(empty($_REQUEST['action'])) { $_REQUEST['action']=''; }

switch ($_REQUEST['action']) {
	case 'notify':
		include('notify_edit.php');
		break;
	case 'notify_handle':
		include('notify_handle.php');
		break;
	case 'notify_delete':
		authorize();
		if($_GET['id'] && is_number($_GET['id'])){
			$DB->query("DELETE FROM users_notify_filters WHERE ID='".db_string($_GET['id'])."' AND UserID='$LoggedUser[ID]'");
			$ArtistNotifications = $Cache->get_value('notify_artists_'.$LoggedUser['ID']);
			if(is_array($ArtistNotifications) && $ArtistNotifications['ID'] == $_GET['id']) {
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
		
	case 'takeedit':
		include('takeedit.php');
		break;

	case 'invitetree':
		include(SERVER_ROOT.'/sections/user/invitetree.php');
		break;
	case 'invite':
		include('invite.php');
		break;
	case 'takeinvite':
		include('takeinvite.php');
		break;		
	case 'deleteinvite':
		include('deleteinvite.php');
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
	default:
		if (isset($_REQUEST['id'])) {
			include(SERVER_ROOT.'/sections/user/user.php');
		} else {
			header('Location: index.php');
		}
}
?>
