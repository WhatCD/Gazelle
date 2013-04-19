<?
$DB->query("
	DELETE FROM friends
	WHERE UserID='$LoggedUser[ID]'
		AND FriendID='$P[friendid]'");

header('Location: friends.php');
?>
