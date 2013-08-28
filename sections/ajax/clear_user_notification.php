<?

$Type = $_POST['type'];

switch($Type) {
	case NotificationsManager::INBOX:
		NotificationsManager::clear_inbox();
		break;
	case NotificationsManager::NEWS:
		NotificationsManager::clear_news();
		break;
	case NotificationsManager::BLOG:
		NotificationsManager::clear_blog();
		break;
	case NotificationsManager::STAFFPM:
		NotificationsManager::clear_staff_pms();
		break;
	case NotificationsManager::TORRENTS:
		NotificationsManager::clear_torrents();
		break;
	case NotificationsManager::QUOTES:
		NotificationsManager::clear_quotes();
		break;
	case NotificationsManager::SUBSCRIPTIONS:
		NotificationsManager::clear_subscriptions();
		break;
	case NotificationsManager::COLLAGES:
		NotificationsManager::clear_collages();
		break;
	case NotificationsManager::GLOBALNOTICE:
		NotificationsManager::clear_global_notification();
		break;
	default:
		break;
}

if (strpos($Type, "oneread_") === 0) {
	NotificationsManager::clear_one_read($Type);
}
