<?php

ini_set('display_errors', '1');
authorize();

$UserID = db_string($_GET['userid']);
if ($_GET['perform'] == 'add') {
	$DB->query("INSERT IGNORE INTO subscribed_users (UserID, SubscriberID) VALUES ('$UserID', '$LoggedUser[ID]')");
} elseif ($_GET['perform'] == 'remove') {
	$DB->query("DELETE FROM subscribed_users WHERE UserID = '$UserID' AND SubscriberID = '$LoggedUser[ID]'");
}
header('Location: user.php?id=' . $UserID);
?>
