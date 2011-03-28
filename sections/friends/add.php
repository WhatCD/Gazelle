<?
authorize();
$FriendID = db_string($_GET['friendid']);
$DB->query("INSERT IGNORE INTO friends (UserID, FriendID) VALUES ('$LoggedUser[ID]', '$FriendID')");
header('Location: friends.php');
?>