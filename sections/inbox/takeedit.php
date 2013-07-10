<?
authorize();

$UserID = $LoggedUser['ID'];
$ConvID = $_POST['convid'];
if (!is_number($ConvID)) {
	error(404);
}
$DB->query("
	SELECT UserID
	FROM pm_conversations_users
	WHERE UserID='$UserID' AND ConvID='$ConvID'");
if (!$DB->has_results()) {
	error(403);
}

if (isset($_POST['delete'])) {
	$DB->query("
		UPDATE pm_conversations_users
		SET
			InInbox='0',
			InSentbox='0',
			Sticky='0'
		WHERE ConvID='$ConvID' AND UserID='$UserID'");
} else {
	if (isset($_POST['sticky'])) {
		$DB->query("
			UPDATE pm_conversations_users
			SET Sticky='1'
			WHERE ConvID='$ConvID' AND UserID='$UserID'");
	} else {
		$DB->query("
			UPDATE pm_conversations_users
			SET Sticky='0'
			WHERE ConvID='$ConvID' AND UserID='$UserID'");
	}
	if (isset($_POST['mark_unread'])) {
		$DB->query("
			UPDATE pm_conversations_users
			SET Unread='1'
			WHERE ConvID='$ConvID' AND UserID='$UserID'");
		$Cache->increment('inbox_new_'.$UserID);
	}
}
header('Location: ' . Inbox::get_inbox_link());
?>
