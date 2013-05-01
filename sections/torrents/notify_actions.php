<?
switch ($_GET['action']) {
	case 'notify_clear':
		$DB->query("DELETE FROM users_notify_torrents WHERE UserID = '$LoggedUser[ID]' AND UnRead = '0'");
		$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
		header('Location: torrents.php?action=notify');
		break;

	case 'notify_clear_item':
	case 'notify_clearitem':
		if (!isset($_GET['torrentid']) || !is_number($_GET['torrentid'])) {
			error(0);
		}
		$DB->query("DELETE FROM users_notify_torrents WHERE UserID = '$LoggedUser[ID]' AND TorrentID = '$_GET[torrentid]'");
		$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
		break;

	case 'notify_clear_items':
		if (!isset($_GET['torrentids'])) {
			error(0);
		}
		$TorrentIDs = explode(',', $_GET['torrentids']);
		foreach ($TorrentIDs as $TorrentID) {
			if (!is_number($TorrentID)) {
				error(0);
			}
		}
		$DB->query("DELETE FROM users_notify_torrents WHERE UserID = $LoggedUser[ID] AND TorrentID IN ($_GET[torrentids])");
		$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
		break;

	case 'notify_clear_filter':
	case 'notify_cleargroup':
		if (!isset($_GET['filterid']) || !is_number($_GET['filterid'])) {
			error(0);
		}
		$DB->query("DELETE FROM users_notify_torrents WHERE UserID = '$LoggedUser[ID]' AND FilterID = '$_GET[filterid]' AND UnRead = '0'");
		$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
		header('Location: torrents.php?action=notify');
		break;

	case 'notify_catchup':
		$DB->query("UPDATE users_notify_torrents SET UnRead = '0' WHERE UserID=$LoggedUser[ID]");
		if ($DB->affected_rows()) {
			$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
		}
		header('Location: torrents.php?action=notify');
		break;

	case 'notify_catchup_filter':
		if (!isset($_GET['filterid']) || !is_number($_GET['filterid'])) {
			error(0);
		}
		$DB->query("UPDATE users_notify_torrents SET UnRead='0' WHERE UserID = $LoggedUser[ID] AND FilterID = $_GET[filterid]");
		if ($DB->affected_rows()) {
			$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
		}
		header('Location: torrents.php?action=notify');
		break;
	default:
		error(0);
}
