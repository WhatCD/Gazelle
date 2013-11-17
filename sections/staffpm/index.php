<?
enforce_login();

if (!isset($_REQUEST['action'])) {
	$_REQUEST['action'] = '';
}

// Get user level
$DB->query("
	SELECT
		i.SupportFor,
		p.DisplayStaff
	FROM users_info AS i
		JOIN users_main AS m ON m.ID = i.UserID
		JOIN permissions AS p ON p.ID = m.PermissionID
	WHERE i.UserID = ".$LoggedUser['ID']
);
list($SupportFor, $DisplayStaff) = $DB->next_record();
// Logged in user is staff
$IsStaff = ($DisplayStaff == 1);
// Logged in user is Staff or FLS
$IsFLS = ($IsStaff || $LoggedUser['ExtraClasses'][41]);

switch ($_REQUEST['action']) {
	case 'viewconv':
		require('viewconv.php');
		break;
	case 'takepost':
		require('takepost.php');
		break;
	case 'resolve':
		require('resolve.php');
		break;
	case 'unresolve':
		require('unresolve.php');
		break;
	case 'multiresolve':
		require('multiresolve.php');
		break;
	case 'assign':
		require('assign.php');
		break;
	case 'make_donor':
		require('makedonor.php');
		break;
	case 'responses':
		require('common_responses.php');
		break;
	case 'get_response':
		require('ajax_get_response.php');
		break;
	case 'delete_response':
		require('ajax_delete_response.php');
		break;
	case 'edit_response':
		require('ajax_edit_response.php');
		break;
	case 'preview':
		require('ajax_preview_response.php');
		break;
	case 'get_post':
		require('get_post.php');
		break;
	case 'scoreboard':
		require('scoreboard.php');
		break;
	default:
		if ($IsStaff || $IsFLS) {
			require('staff_inbox.php');
		} else {
			require('user_inbox.php');
		}
		break;
}

?>
