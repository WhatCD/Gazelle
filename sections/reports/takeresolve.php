<?
authorize();

if(!check_perms('admin_reports') && !check_perms('project_team')) {
	error(403);
}

if(empty($_POST['reportid']) && !is_number($_POST['reportid'])) {
	error(403);
}

$ReportID = $_POST['reportid'];

$DB->query("SELECT Type FROM reports WHERE ID = ".$ReportID);
list($Type) = $DB->next_record();
if(!check_perms('admin_reports')) {
	if($Type != "request_update") {
		error(403);
	}
}

$DB->query("UPDATE reports 
			SET Status='Resolved',
				ResolvedTime='".sqltime()."',
				ResolverID='".$LoggedUser['ID']."'
			WHERE ID='".db_string($ReportID)."'");

$Channels = array("#forumreports");
if($Type == "request_update") {
	$Channels[] = "#requestedits";
	$Cache->decrement('num_update_reports');
}

$DB->query("SELECT COUNT(ID) FROM reports WHERE Status = 'New'");
list($Remaining) = $DB->next_record();

foreach($Channels as $Channel) {
	send_irc("PRIVMSG ".$Channel." :Report ".$ReportID." resolved by ".preg_replace("/^(.{2})/", "$1·", $LoggedUser['Username'])." on site (".(int)$Remaining." remaining).");
}

$Cache->delete_value('num_other_reports');

header('Location: reports.php');
?>
