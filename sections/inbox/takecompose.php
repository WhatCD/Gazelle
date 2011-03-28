<?
authorize();


if(empty($_POST['toid'])) { error(404); }

if(!empty($LoggedUser['DisablePM']) && !isset($StaffIDs[$_POST['toid']])) {
	error(403);
}


if (isset($_POST['convid']) && is_number($_POST['convid'])) {
	$ConvID = $_POST['convid'];
	$Subject='';
	$ToID = explode(',', $_POST['toid']);
	foreach($ToID as $TID) {
		if(!is_number($TID)) {
			$Err = "A recipient does not exist.";
		}
	}
	$DB->query("SELECT UserID FROM pm_conversations_users WHERE UserID='$LoggedUser[ID]' AND ConvID='$ConvID'");
	if($DB->record_count() == 0) {
		error(403);
	}
} else {
	$ConvID='';
	if(!is_number($_POST['toid'])) {
		$Err = "This recipient does not exist.";
	} else {
		$ToID = $_POST['toid'];
	}
	$Subject = trim($_POST['subject']);
	if (empty($Subject)) {
		$Err = "You can't send a message without a subject.";
	}
}
$Body = trim($_POST['body']);
if(empty($Body)) {
	$Err = "You can't send a message without a body!";
}

if(!empty($Err)) {
	error($Err);
	//header('Location: inbox.php?action=compose&to='.$_POST['toid']);
	$ToID = $_POST['toid'];
	$Return = true;
	include(SERVER_ROOT.'/sections/inbox/compose.php');
	die();
}

$ConvID = send_pm($ToID,$LoggedUser['ID'],db_string($Subject),db_string($Body),$ConvID);

header('Location: inbox.php');
?>
