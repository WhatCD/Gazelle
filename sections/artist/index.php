<?
/**************************************************************************
Artists Switch Center

This page acts as a switch that includes the real artist pages (to keep
the root less cluttered).

enforce_login() is run here - the entire artist pages are off limits for
non members.
 ****************************************************************************/

// Width and height of similar artist map
define('WIDTH', 585);
define('HEIGHT', 400);

enforce_login();
if (!empty($_POST['action'])) {
	switch ($_POST['action']) {
		case 'edit':
			require(SERVER_ROOT . '/sections/artist/takeedit.php');
			break;
		case 'download':
			require(SERVER_ROOT . '/sections/artist/download.php');
			break;
		case 'rename':
			require(SERVER_ROOT . '/sections/artist/rename.php');
			break;
		case 'add_similar':
			require(SERVER_ROOT . '/sections/artist/add_similar.php');
			break;
		case 'add_alias':
			require(SERVER_ROOT . '/sections/artist/add_alias.php');
			break;
		case 'change_artistid':
			require(SERVER_ROOT . '/sections/artist/change_artistid.php');
			break;
		case 'concert_thread':
			include(SERVER_ROOT . '/sections/artist/concert_thread.php');
			break;
		case 'take_concert_thread':
			include(SERVER_ROOT . '/sections/artist/take_concert_thread.php');
			break;
		default:
			error(0);
	}
} elseif (!empty($_GET['action'])) {
	switch ($_GET['action']) {
		case 'autocomplete':
			require('sections/artist/autocomplete.php');
			break;

		case 'edit':
			require(SERVER_ROOT . '/sections/artist/edit.php');
			break;
		case 'delete':
			require(SERVER_ROOT . '/sections/artist/delete.php');
			break;
		case 'revert':
			require(SERVER_ROOT . '/sections/artist/takeedit.php');
			break;
		case 'history':
			require(SERVER_ROOT . '/sections/artist/history.php');
			break;
		case 'vote_similar':
			require(SERVER_ROOT . '/sections/artist/vote_similar.php');
			break;
		case 'delete_similar':
			require(SERVER_ROOT . '/sections/artist/delete_similar.php');
			break;
		case 'similar':
			require(SERVER_ROOT . '/sections/artist/similar.php');
			break;
		case 'similar_bg':
			require(SERVER_ROOT . '/sections/artist/similar_bg.php');
			break;
		case 'notify':
			require(SERVER_ROOT . '/sections/artist/notify.php');
			break;
		case 'notifyremove':
			require(SERVER_ROOT . '/sections/artist/notifyremove.php');
			break;
		case 'delete_alias':
			require(SERVER_ROOT . '/sections/artist/delete_alias.php');
			break;
		case 'change_artistid':
			require(SERVER_ROOT . '/sections/artist/change_artistid.php');
			break;
		default:
			error(0);
			break;
	}
} else {
	if (!empty($_GET['id'])) {

		include (SERVER_ROOT . '/sections/artist/artist.php');

	} elseif (!empty($_GET['artistname'])) {

		$NameSearch = str_replace('\\', '\\\\', trim($_GET['artistname']));
		$DB->query("
			SELECT ArtistID, Name
			FROM artists_alias
			WHERE Name LIKE '" . db_string($NameSearch) . "'");
		if (!$DB->has_results()) {
			if (isset($LoggedUser['SearchType']) && $LoggedUser['SearchType']) {
				header('Location: torrents.php?action=advanced&artistname=' . urlencode($_GET['artistname']));
			} else {
				header('Location: torrents.php?searchstr=' . urlencode($_GET['artistname']));
			}
			die();
		}
		list($FirstID, $Name) = $DB->next_record(MYSQLI_NUM, false);
		if ($DB->record_count() === 1 || !strcasecmp($Name, $NameSearch)) {
			header("Location: artist.php?id=$FirstID");
			die();
		}
		while (list($ID, $Name) = $DB->next_record(MYSQLI_NUM, false)) {
			if (!strcasecmp($Name, $NameSearch)) {
				header("Location: artist.php?id=$ID");
				die();
			}
		}
		header("Location: artist.php?id=$FirstID");
		die();
	} else {
		header('Location: torrents.php');
	}
}
?>
