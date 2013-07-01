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
		case 'reply':
			authorize();

			if (!isset($_POST['artistid']) || !isset($_POST['body']) || !is_number($_POST['artistid']) || trim($_POST['body']) === '') {
				error(0);
			}
			if ($LoggedUser['DisablePosting']) {
				error('Your posting privileges have been removed.');
			}

			$ArtistID = $_POST['artistid'];
			if (!$ArtistID) {
				error(404);
			}

			$DB->query("
				SELECT
					CEIL((
						SELECT COUNT(ID)+1
						FROM artist_comments AS ac
						WHERE ac.ArtistID='" . db_string($ArtistID) . "'
						)/" . TORRENT_COMMENTS_PER_PAGE . "
					) AS Pages");
			list($Pages) = $DB->next_record();

			$DB->query("
				INSERT INTO artist_comments (ArtistID,AuthorID,AddedTime,Body)
				VALUES ('" . db_string($ArtistID) . "', '" . db_string($LoggedUser['ID']) . "','" . sqltime() . "','" . db_string($_POST['body']) . "')");
			$PostID = $DB->inserted_id();

			$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $Pages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
			$Cache->begin_transaction('artist_comments_' . $ArtistID . '_catalogue_' . $CatalogueID);
			$Post = array(
				'ID' => $PostID,
				'AuthorID' => $LoggedUser['ID'],
				'AddedTime' => sqltime(),
				'Body' => $_POST['body'],
				'EditedUserID' => 0,
				'EditedTime' => '0000-00-00 00:00:00',
				'Username' => ''
			);
			$Cache->insert('', $Post);
			$Cache->commit_transaction(0);
			$Cache->increment('artist_comments_' . $ArtistID);

			header('Location: artist.php?id=' . $ArtistID . '&page=' . $Pages);
			break;
		case 'warn' :
			include(SERVER_ROOT . '/sections/artist/warn.php');
			break;
		case 'take_warn' :
			include(SERVER_ROOT . '/sections/artist/take_warn.php');
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
		case 'get_post':
			if (!$_GET['post'] || !is_number($_GET['post'])) {
				error(0);
			}
			$DB->query("SELECT Body FROM artist_comments WHERE ID='" . db_string($_GET['post']) . "'");
			list($Body) = $DB->next_record(MYSQLI_NUM);
			echo trim($Body);
			break;

		case 'delete_comment':
			authorize();

			// Quick SQL injection check
			if (!$_GET['postid'] || !is_number($_GET['postid'])) {
				error(0);
			}

			// Make sure they are moderators
			if (!check_perms('site_moderate_forums')) {
				error(403);
			}

			// Get topicid, forumid, number of pages
			$DB->query("
				SELECT
					ArtistID,
					CEIL(COUNT(ac.ID)/" . TORRENT_COMMENTS_PER_PAGE . ") AS Pages,
					CEIL(SUM(IF(ac.ID<=" . $_GET['postid'] . ",1,0))/" . TORRENT_COMMENTS_PER_PAGE . ") AS Page
				FROM artist_comments AS ac
				WHERE ac.ArtistID=(
						SELECT ArtistID
						FROM artist_comments
						WHERE ID=" . $_GET['postid'] . "
						)
				GROUP BY ac.ArtistID");
			list($ArtistID, $Pages, $Page) = $DB->next_record();

			// $Pages = number of pages in the thread
			// $Page = which page the post is on
			// These are set for cache clearing.

			$DB->query("DELETE FROM artist_comments WHERE ID='" . db_string($_GET['postid']) . "'");

			//We need to clear all subsequential catalogues as they've all been bumped with the absence of this post
			$ThisCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $Page - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
			$LastCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $Pages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
			for ($i = $ThisCatalogue; $i <= $LastCatalogue; $i++) {
				$Cache->delete_value('artist_comments_' . $ArtistID . '_catalogue_' . $i);
			}

			// Delete thread info cache (eg. number of pages)
			$Cache->delete_value('artist_comments_' . $ArtistID);

			break;

		case 'takeedit_post':
			authorize();

			include(SERVER_ROOT . '/classes/text.class.php'); // Text formatting class
			$Text = new TEXT;

			// Quick SQL injection check
			if (!$_POST['post'] || !is_number($_POST['post'])) {
				error(0);
			}

			// Mainly
			$DB->query("
				SELECT
					ac.Body,
					ac.AuthorID,
					ac.ArtistID,
					ac.AddedTime
				FROM artist_comments AS ac
				WHERE ac.ID='" . db_string($_POST['post']) . "'");
			list($OldBody, $AuthorID, $ArtistID, $AddedTime) = $DB->next_record();

			$DB->query("
				SELECT ceil(COUNT(ID) / " . TORRENT_COMMENTS_PER_PAGE . ") AS Page
				FROM artist_comments
				WHERE ArtistID = $ArtistID
					AND ID <= $_POST[post]");
			list($Page) = $DB->next_record();

			if ($LoggedUser['ID'] != $AuthorID && !check_perms('site_moderate_forums')) {
				error(404);
			}
			if ($DB->record_count() == 0) {
				error(404);
			}

			// Perform the update
			$DB->query("
				UPDATE artist_comments
				SET
					Body = '" . db_string($_POST['body']) . "',
					EditedUserID = '" . db_string($LoggedUser['ID']) . "',
					EditedTime = '" . sqltime() . "'
				WHERE ID='" . db_string($_POST['post']) . "'");

			// Update the cache
			$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $Page - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
			$Cache->begin_transaction('artist_comments_' . $ArtistID . '_catalogue_' . $CatalogueID);

			$Cache->update_row($_POST['key'], array(
				'ID' => $_POST['post'],
				'AuthorID' => $AuthorID,
				'AddedTime' => $AddedTime,
				'Body' => $_POST['body'],
				'EditedUserID' => db_string($LoggedUser['ID']),
				'EditedTime' => sqltime(),
				'Username' => $LoggedUser['Username']
			));
			$Cache->commit_transaction(0);

			$DB->query("
				INSERT INTO comments_edits (Page, PostID, EditUser, EditTime, Body)
				VALUES ('artist', " . db_string($_POST['post']) . ", " . db_string($LoggedUser['ID']) . ", '" . sqltime() . "', '" . db_string($OldBody) . "')");

			// This gets sent to the browser, which echoes it in place of the old body
			echo $Text->full_format($_POST['body']);
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
		if ($DB->record_count() == 0) {
			if (isset($LoggedUser['SearchType']) && $LoggedUser['SearchType']) {
				header('Location: torrents.php?action=advanced&artistname=' . urlencode($_GET['artistname']));
			} else {
				header('Location: torrents.php?searchstr=' . urlencode($_GET['artistname']));
			}
			die();
		}
		list($FirstID, $Name) = $DB->next_record(MYSQLI_NUM, false);
		if ($DB->record_count() == 1 || !strcasecmp($Name, $NameSearch)) {
			header('Location: artist.php?id=' . $FirstID);
			die();
		}
		while (list($ID, $Name) = $DB->next_record(MYSQLI_NUM, false)) {
			if (!strcasecmp($Name, $NameSearch)) {
				header('Location: artist.php?id=' . $ID);
				die();
			}
		}
		header('Location: artist.php?id=' . $FirstID);
		die();
	} else {
		header('Location: torrents.php');
	}
}
?>
