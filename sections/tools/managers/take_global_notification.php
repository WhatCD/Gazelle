<?

if (!check_perms("users_mod")) {
	error(404);
}
if ($_POST['set']) {
	$Expiration = $_POST['length'] * 60;
	NotificationsManager::set_global_notification($_POST['message'], $_POST['url'], $_POST['importance'], $Expiration);
} elseif ($_POST['delete']) {
	NotificationsManager::delete_global_notification();
}

header("Location: tools.php?action=global_notification");
