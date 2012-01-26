<?
	if (!is_number($_GET['id'])) {
		error(404);
	}
	
	if (!check_perms('users_give_donor')) {
		error(403);
	}
	
	$ConvID = (int)$_GET['id'];
	$DB->query("SELECT c.Subject, c.UserID, c.Level, c.AssignedToUser, c.Unread, c.Status, u.Donor
				FROM staff_pm_conversations AS c
				JOIN users_info AS u ON u.UserID = c.UserID
				WHERE ID=$ConvID");
	list($Subject, $UserID, $Level, $AssignedToUser, $Unread, $Status, $Donor) = $DB->next_record();
	if ($DB->record_count() == 0) {
		error(404);
	}
	
	$Message = "Thank for for helping to support the site.  It's users like you who make all of this possible.";
	
	if ((int)$Donor === 0) {
		$Msg = db_string(sqltime() . ' - Donated: http://'.NONSSL_SITE_URL."/staffpm.php?action=viewconv&id=$ConvID\n\n");
		$DB->query("UPDATE users_info 
					SET Donor='1',
						AdminComment = CONCAT('$Msg',AdminComment) 
					WHERE UserID = $UserID");
		$DB->query("UPDATE users_main SET Invites=Invites+2 WHERE ID = $UserID");

		$Cache->delete_value('user_info_'.$UserID);
		$Cache->delete_value('user_info_heavy_'.$UserID);
		$Message .= "  Enjoy your new love from us!";
	} else {
		$Message .= "  ";
	}
	$DB->query("INSERT INTO staff_pm_messages (UserID, SentDate, Message, ConvID)
				VALUES (".$LoggedUser['ID'].", '".sqltime()."', '".db_string($Message)."', $ConvID)");
	$DB->query("UPDATE staff_pm_conversations 
	               SET Date='".sqltime()."', Unread=true, 
				       Status='Resolved', ResolverID=".$LoggedUser['ID']."
				 WHERE ID=$ConvID");
	header('Location: staffpm.php');
?>