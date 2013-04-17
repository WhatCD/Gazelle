<?php

ini_set('display_errors', '1');
authorize();

$ForumID = db_string($_GET['forumid']);
if ($_GET['perform'] == 'add') {
	$DB->query("INSERT IGNORE INTO subscribed_forums (ForumID, SubscriberID) VALUES ('$ForumID', '$LoggedUser[ID]')");
} elseif ($_GET['perform'] == 'remove') {
	$DB->query("DELETE FROM subscribed_forums WHERE ForumID = '$ForumID' AND SubscriberID = '$LoggedUser[ID]'");
}
header('Location: forums.php?action=viewforum&forumid=' . $ForumID);
?>

