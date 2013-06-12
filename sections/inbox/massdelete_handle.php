<?

/* replace
$UserID = $LoggedUser['ID'];
authorize();
replace */

if (!isset($_POST['messages']) || !is_array($_POST['messages'])) {
	error('You forgot to select messages to delete.');
	header('Location: ' . Inbox::get_inbox_link());
	die();
}

$Messages = $_POST['messages'];
foreach ($Messages AS $ConvID) {
	$ConvID = trim($ConvID);
	if (!is_number($ConvID)) {
		error(0);
	}
}
$ConvIDs = implode(',', $Messages);
$DB->query("
	SELECT COUNT(ConvID)
	FROM pm_conversations_users
	WHERE ConvID IN ($ConvIDs)
		AND UserID=$UserID");
list($MessageCount) = $DB->next_record();
if ($MessageCount != count($Messages)) {
	error(0);
}

if (isset($_POST['delete'])) {
	$DB->query("
		UPDATE pm_conversations_users
		SET
			InInbox='0',
			InSentbox='0',
			Sticky='0',
			UnRead='0'
		WHERE ConvID IN($ConvIDs)
			AND UserID=$UserID");
} elseif (isset($_POST['unread'])) {
	$DB->query("
		UPDATE pm_conversations_users
		SET Unread='1'
		WHERE ConvID IN($ConvIDs) AND UserID=$UserID");
} elseif (isset($_POST['read'])) {
	$DB->query("
		UPDATE pm_conversations_users
		SET Unread='0'
		WHERE ConvID IN($ConvIDs) AND UserID=$UserID");
}
$Cache->delete_value('inbox_new_'.$UserID);

header('Location: ' . Inbox::get_inbox_link());
?>
