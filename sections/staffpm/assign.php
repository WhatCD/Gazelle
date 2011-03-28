<?
if (!($IsFLS)) {
	// Logged in user is not FLS or Staff
	error(403);
}

if ($ConvID = (int)$_GET['convid']) {
	// FLS, check level of conversation
	$DB->query("SELECT Level FROM staff_pm_conversations WHERE ID=$ConvID");
	list($Level) = $DB->next_record;
	
	if ($Level == 0) {
		// FLS conversation, assign to staff (moderator)
		$DB->query("UPDATE staff_pm_conversations SET Level=700 WHERE ID=$ConvID");

		header('Location: staffpm.php');
		
	} else {
		// FLS trying to assign non-FLS conversation
		error(403);
	}
	
} elseif ($ConvID = (int)$_POST['convid']) {
	// Staff (via ajax), get current assign of conversation
	$DB->query("SELECT Level, AssignedToUser FROM staff_pm_conversations WHERE ID=$ConvID");
	list($Level, $AssignedToUser) = $DB->next_record;
	
	if ($LoggedUser['Class'] >= $Level || $AssignedToUser == $LoggedUser['ID']) {
		// Staff member is allowed to assign conversation, assign
		list($LevelType, $NewLevel) = explode("_", db_string($_POST['assign']));
		
		if ($LevelType == 'class') {
			// Assign to class
			$DB->query("UPDATE staff_pm_conversations SET Status='Unanswered', Level=$NewLevel, AssignedToUser=NULL WHERE ID=$ConvID");
		} else {
			// Assign to user
			$DB->query("UPDATE staff_pm_conversations SET Status='Unanswered', AssignedToUser=$NewLevel WHERE ID=$ConvID");
		}
		echo '1';
		
	} else {
		// Staff member is not allowed to assign conversation
		echo '-1';
	}
	
} else {
	// No id
	header('Location: staffpm.php');
}
?>
