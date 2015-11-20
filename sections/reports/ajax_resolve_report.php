<?
authorize();

if (!check_perms('admin_reports') && !check_perms('project_team') && !check_perms('site_moderate_forums')) {
	ajax_error();
}

$ReportID = (int) $_POST['reportid'];

$DB->query("
	SELECT Type
	FROM reports
	WHERE ID = $ReportID");
list($Type) = $DB->next_record();
if (!check_perms('admin_reports')) {
	if (check_perms('site_moderate_forums')) {
		if (!in_array($Type, array('comment', 'post', 'thread'))) {
			ajax_error();
		}
	} elseif (check_perms('project_team')) {
		if ($Type != 'request_update') {
			ajax_error();
		}
	}
}

$DB->query("
	UPDATE reports
	SET Status = 'Resolved',
		ResolvedTime = '".sqltime()."',
		ResolverID = '".$LoggedUser['ID']."'
	WHERE ID = '".db_string($ReportID)."'");

$Channels = array();

if ($Type == 'request_update') {
	$Channels[] = '#requestedits';
	$Cache->decrement('num_update_reports');
}

if (in_array($Type, array('comment', 'post', 'thread'))) {
	$Channels[] = '#forumreports';
	$Cache->decrement('num_forum_reports');
}

$DB->query("
	SELECT COUNT(ID)
	FROM reports
	WHERE Status = 'New'");
list($Remaining) = $DB->next_record();

foreach ($Channels as $Channel) {
	send_irc("PRIVMSG $Channel :Report $ReportID resolved by ".preg_replace('/^(.{2})/', '$1·', $LoggedUser['Username']).' on site ('.(int)$Remaining.' remaining).');
}

$Cache->delete_value('num_other_reports');

ajax_success();

function ajax_error($Error = 'error') {
	echo json_encode(array('status' => $Error));
	die();
}

function ajax_success() {
	echo json_encode(array('status' => 'success'));
	die();
}
?>
