<?
$ArtistID = db_string($_GET['artistid']);
$GroupID = db_string($_GET['groupid']);
$Importance = db_string($_GET['importance']);

if (!is_number($ArtistID) || !is_number($GroupID) || !is_number($Importance)) {
	error(404);
}
if (!check_perms('torrents_edit')) {
	error(403);
}

$DB->query("
	DELETE FROM torrents_artists
	WHERE GroupID = '$GroupID'
		AND ArtistID = '$ArtistID'
		AND Importance = '$Importance'");
$DB->query("
	SELECT Name
	FROM artists_group
	WHERE ArtistID = $ArtistID");
list($ArtistName) = $DB->next_record(MYSQLI_NUM, false);

$DB->query("
	SELECT Name
	FROM torrents_group
	WHERE ID = $GroupID");
if (!$DB->has_results()) {
	error(404);
}
list($GroupName) = $DB->next_record(MYSQLI_NUM, false);

// Get a count of how many groups or requests use this artist ID
$DB->query("
	SELECT ag.ArtistID
	FROM artists_group AS ag
		LEFT JOIN requests_artists AS ra ON ag.ArtistID = ra.ArtistID
	WHERE ra.ArtistID IS NOT NULL
		AND ag.ArtistID = $ArtistID");
$ReqCount = $DB->record_count();
$DB->query("
	SELECT ag.ArtistID
	FROM artists_group AS ag
		LEFT JOIN torrents_artists AS ta ON ag.ArtistID = ta.ArtistID
	WHERE ta.ArtistID IS NOT NULL
		AND ag.ArtistID = $ArtistID");
$GroupCount = $DB->record_count();
if (($ReqCount + $GroupCount) == 0) {
	// The only group to use this artist
	Artists::delete_artist($ArtistID);
}

$Cache->delete_value("torrents_details_$GroupID"); // Delete torrent group cache
$Cache->delete_value("groups_artists_$GroupID"); // Delete group artist cache
Misc::write_log('Artist ('.$ArtistTypes[$Importance].") $ArtistID ($ArtistName) was removed from the group $GroupID ($GroupName) by user ".$LoggedUser['ID'].' ('.$LoggedUser['Username'].')');
Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "removed artist $ArtistName (".$ArtistTypes[$Importance].')', 0);

Torrents::update_hash($GroupID);
$Cache->delete_value("artist_groups_$ArtistID");

header('Location: '.$_SERVER['HTTP_REFERER']);
?>
