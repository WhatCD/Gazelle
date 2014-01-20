<?
authorize();
if (!check_perms('users_mod') && $_GET['userid'] != $LoggedUser['ID']) {
	error(403);
}

$UserID = db_string($_GET['userid']);
NotificationsManager::send_push($UserID, 'Push!', 'You\'ve been pushed by ' . $LoggedUser['Username']);

header('Location: user.php?action=edit&userid=' . $UserID . "");
?>
