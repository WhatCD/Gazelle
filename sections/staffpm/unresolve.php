<?
if ($ID = (int)($_GET['id'])) {
	// Check if conversation belongs to user
	$DB->query("
		SELECT UserID, Level, AssignedToUser
		FROM staff_pm_conversations
		WHERE ID = $ID");
	list($UserID, $Level, $AssignedToUser) = $DB->next_record();

	if ($UserID == $LoggedUser['ID']
		|| ($IsFLS && $Level == 0)
		|| $AssignedToUser == $LoggedUser['ID']
		|| ($IsStaff && $Level <= $LoggedUser['EffectiveClass'])
		) {
		/*if ($Level != 0 && $IsStaff == false) {
			error(403);
		}*/

		// Conversation belongs to user or user is staff, unresolve it
		$DB->query("
			UPDATE staff_pm_conversations
			SET Status = 'Unanswered'
			WHERE ID = $ID");
		// Clear cache for user
		$Cache->delete_value("num_staff_pms_$LoggedUser[ID]");

		header('Location: staffpm.php');
	} else {
		// Conversation does not belong to user
		error(403);
	}
} else {
	// No ID
	header('Location: staffpm.php');
}
?>
