<?
authorize();

if (!check_perms('torrents_edit')) {
	error(403);
}
if (!empty($_POST['newartistid']) && !empty($_POST['newartistname'])) {
	error('Please enter a valid artist ID number or a valid artist name.');
}
$ArtistID = (int)$_POST['artistid'];
$NewArtistID = (int)$_POST['newartistid'];
$NewArtistName = $_POST['newartistname'];


if (!is_number($ArtistID) || !$ArtistID) {
	error('Please select a valid artist to change.');
}
if (empty($NewArtistName) && (!$NewArtistID || !is_number($NewArtistID))) {
	error('Please enter a valid artist ID number or a valid artist name.');
}

$DB->query("
	SELECT Name
	FROM artists_group
	WHERE ArtistID = $ArtistID
	LIMIT 1");
if (!(list($ArtistName) = $DB->next_record(MYSQLI_NUM, false))) {
	error('An error has occurred.');
}

if ($NewArtistID > 0) {
	// Make sure that's a real artist ID number, and grab the name
	$DB->query("
		SELECT Name
		FROM artists_group
		WHERE ArtistID = $NewArtistID
		LIMIT 1");
	if (!(list($NewArtistName) = $DB->next_record())) {
		error('Please enter a valid artist ID number.');
	}
} else {
	// Didn't give an ID, so try to grab based on the name
	$DB->query("
		SELECT ArtistID
		FROM artists_alias
		WHERE Name = '".db_string($NewArtistName)."'
		LIMIT 1");
	if (!(list($NewArtistID) = $DB->next_record())) {
		error('No artist by that name was found.');
	}
}

if ($ArtistID == $NewArtistID) {
	error('You cannot merge an artist with itself.');
}
if (isset($_POST['confirm'])) {
	// Get the information for the cache update
	$DB->query("
		SELECT DISTINCT GroupID
		FROM torrents_artists
		WHERE ArtistID = $ArtistID");
	$Groups = $DB->collect('GroupID');
	$DB->query("
		SELECT DISTINCT RequestID
		FROM requests_artists
		WHERE ArtistID = $ArtistID");
	$Requests = $DB->collect('RequestID');
	$DB->query("
		SELECT DISTINCT UserID
		FROM bookmarks_artists
		WHERE ArtistID = $ArtistID");
	$BookmarkUsers = $DB->collect('UserID');
	$DB->query("
		SELECT DISTINCT ct.CollageID
		FROM collages_torrents AS ct
			JOIN torrents_artists AS ta ON ta.GroupID = ct.GroupID
		WHERE ta.ArtistID = $ArtistID");
	$Collages = $DB->collect('CollageID');

	// And the info to avoid double-listing an artist if it and the target are on the same group
	$DB->query("
		SELECT DISTINCT GroupID
		FROM torrents_artists
		WHERE ArtistID = $NewArtistID");
	$NewArtistGroups = $DB->collect('GroupID');
	$NewArtistGroups[] = '0';
	$NewArtistGroups = implode(',', $NewArtistGroups);

	$DB->query("
		SELECT DISTINCT RequestID
		FROM requests_artists
		WHERE ArtistID = $NewArtistID");
	$NewArtistRequests = $DB->collect('RequestID');
	$NewArtistRequests[] = '0';
	$NewArtistRequests = implode(',', $NewArtistRequests);

	$DB->query("
		SELECT DISTINCT UserID
		FROM bookmarks_artists
		WHERE ArtistID = $NewArtistID");
	$NewArtistBookmarks = $DB->collect('UserID');
	$NewArtistBookmarks[] = '0';
	$NewArtistBookmarks = implode(',', $NewArtistBookmarks);

	// Merge all of this artist's aliases onto the new artist
	$DB->query("
		UPDATE artists_alias
		SET ArtistID = $NewArtistID
		WHERE ArtistID = $ArtistID");

	// Update the torrent groups, requests, and bookmarks
	$DB->query("
		UPDATE IGNORE torrents_artists
		SET ArtistID = $NewArtistID
		WHERE ArtistID = $ArtistID
			AND GroupID NOT IN ($NewArtistGroups)");
	$DB->query("
		DELETE FROM torrents_artists
		WHERE ArtistID = $ArtistID");
	$DB->query("
		UPDATE IGNORE requests_artists
		SET ArtistID = $NewArtistID
		WHERE ArtistID = $ArtistID
			AND RequestID NOT IN ($NewArtistRequests)");
	$DB->query("
		DELETE FROM requests_artists
		WHERE ArtistID = $ArtistID");
	$DB->query("
		UPDATE IGNORE bookmarks_artists
		SET ArtistID = $NewArtistID
		WHERE ArtistID = $ArtistID
			AND UserID NOT IN ($NewArtistBookmarks)");
	$DB->query("
		DELETE FROM bookmarks_artists
		WHERE ArtistID = $ArtistID");

	// Cache clearing
	if (!empty($Groups)) {
		foreach ($Groups as $GroupID) {
			$Cache->delete_value("groups_artists_$GroupID");
			Torrents::update_hash($GroupID);
		}
	}
	if (!empty($Requests)) {
		foreach ($Requests as $RequestID) {
			$Cache->delete_value("request_artists_$RequestID");
			Requests::update_sphinx_requests($RequestID);
		}
	}
	if (!empty($BookmarkUsers)) {
		foreach ($BookmarkUsers as $UserID) {
			$Cache->delete_value("notify_artists_$UserID");
		}
	}
	if (!empty($Collages)) {
		foreach ($Collages as $CollageID) {
			$Cache->delete_value("collage_$CollageID");
		}
	}

	$Cache->delete_value("artist_$ArtistID");
	$Cache->delete_value("artist_$NewArtistID");
	$Cache->delete_value("artist_groups_$ArtistID");
	$Cache->delete_value("artist_groups_$NewArtistID");

	// Delete the old artist
	$DB->query("
		DELETE FROM artists_group
		WHERE ArtistID = $ArtistID");

	Misc::write_log("The artist $ArtistID ($ArtistName) was made into a non-redirecting alias of artist $NewArtistID ($NewArtistName) by user ".$LoggedUser['ID']." (".$LoggedUser['Username'].')');

	header("Location: artist.php?action=edit&artistid=$NewArtistID");
} else {
	View::show_header('Merging Artists');
?>
	<div class="header">
		<h2>Confirm merge</h2>
	</div>
	<form class="merge_form" name="artist" action="artist.php" method="post">
		<input type="hidden" name="action" value="change_artistid" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="artistid" value="<?=$ArtistID?>" />
		<input type="hidden" name="newartistid" value="<?=$NewArtistID?>" />
		<input type="hidden" name="confirm" value="1" />
		<div style="text-align: center;">
			<p>Please confirm that you wish to make <a href="artist.php?id=<?=$ArtistID?>"><?=display_str($ArtistName)?> (<?=$ArtistID?>)</a> into a non-redirecting alias of <a href="artist.php?id=<?=$NewArtistID?>"><?=display_str($NewArtistName)?> (<?=$NewArtistID?>)</a>.</p>
			<br />
			<input type="submit" value="Confirm" />
		</div>
	</form>
<?
	View::show_footer();
}
?>
