<?
enforce_login();

$StaffIDs = $Cache->get_value('staff_ids');
if (!is_array($StaffIDs)) {
	$DB->query("
		SELECT m.ID, m.Username
		FROM users_main AS m
			JOIN permissions AS p ON p.ID=m.PermissionID
		WHERE p.DisplayStaff='1'");
	while (list($StaffID, $StaffName) = $DB->next_record()) {
		$StaffIDs[$StaffID] = $StaffName;
	}
	uasort($StaffIDs, 'strcasecmp');
	$Cache->cache_value('staff_ids', $StaffIDs);
}

if (!isset($_REQUEST['action'])) {
	$_REQUEST['action'] = '';
}
switch ($_REQUEST['action']) {
	case 'takecompose':
		require('takecompose.php');
		break;
	case 'takeedit':
		require('takeedit.php');
		break;
	case 'compose':
		require('compose.php');
		break;
	case 'viewconv':
		require('conversation.php');
		break;
	case 'masschange':
		require('massdelete_handle.php');
		break;
	case 'get_post':
		require('get_post.php');
		break;
	case 'forward':
		require('forward.php');
		break;
	default:
		require(SERVER_ROOT.'/sections/inbox/inbox.php');
}
