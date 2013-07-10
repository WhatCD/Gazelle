<?php
authorize();
if (!is_number($_GET['friendid'])) {
	error(404);
}
$FriendID = db_string($_GET['friendid']);

// Check if the user $FriendID exists
$DB->query("
	SELECT 1
	FROM users_main
	WHERE ID = '$FriendID'");
if (!$DB->has_results()) {
	error(404);
}

$DB->query("
	INSERT IGNORE INTO friends
		(UserID, FriendID)
	VALUES
		('$LoggedUser[ID]', '$FriendID')");

header('Location: friends.php');
