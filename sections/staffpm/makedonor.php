<?
authorize();
if (!check_perms('users_give_donor')) {
	error(403);
}
if (!is_number($_POST['id']) || !is_numeric($_POST['donation_amount']) || empty($_POST['donation_currency'])) {
	error(404);
}

$ConvID = (int)$_POST['id'];

$DB->query("
	SELECT c.Subject, c.UserID, c.Level, c.AssignedToUser, c.Unread, c.Status, u.Donor
	FROM staff_pm_conversations AS c
		JOIN users_info AS u ON u.UserID = c.UserID
	WHERE ID = $ConvID");
list($Subject, $UserID, $Level, $AssignedToUser, $Unread, $Status, $Donor) = $DB->next_record();
if ($DB->record_count() == 0) {
	error(404);
}

$Message = "Thank for for helping to support the site. It's users like you who make all of this possible.";

if ((int)$Donor === 0) {
	$Message .= ' Enjoy your new love from us!';
} else {
	$Message .= ' ';
}
/*
$DB->query("
	INSERT INTO staff_pm_messages
		(UserID, SentDate, Message, ConvID)
	VALUES
		(".$LoggedUser['ID'].", '".sqltime()."', '".db_string($Message)."', $ConvID)");
*/
$DB->query("
	UPDATE staff_pm_conversations
	SET Date = '".sqltime()."',
		Unread = true,
		Status = 'Resolved',
		ResolverID = ".$LoggedUser['ID']."
	WHERE ID = $ConvID");

Donations::donate($UserID, array(
							"Source" => "Staff PM",
							"Price" => $_POST['donation_amount'],
							"Currency" => $_POST['donation_currency'],
							"Reason" => $_POST['donation_reason'],
							"SendPM" => true));

header('Location: staffpm.php');
