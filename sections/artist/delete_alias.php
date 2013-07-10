<?
authorize();
if (!check_perms('torrents_edit')) {
	error(403);
}

$AliasID = $_GET['aliasid'];

if (!is_number($AliasID)) {
	error(0);
}

$DB->query("
	SELECT aa.AliasID
	FROM artists_alias AS aa
		JOIN artists_alias AS aa2 ON aa.ArtistID=aa2.ArtistID
	WHERE aa.AliasID=".$AliasID);

if ($DB->record_count() === 1) {
	//This is the last alias on the artist
	error("That alias is the last alias for that artist; removing it would cause bad things to happen.");
}

$DB->query("
	SELECT GroupID
	FROM torrents_artists
	WHERE AliasID='$AliasID'");
if ($DB->has_results()) {
	list($GroupID) = $DB->next_record();
	if ($GroupID != 0) {
		error("That alias still has the group (<a href=\"torrents.php?id=$GroupID\">$GroupID</a>) attached. Fix that first.");
	}
}

$DB->query("
	SELECT aa.ArtistID, ag.Name, aa.Name
	FROM artists_alias AS aa
		JOIN artists_group AS ag ON aa.ArtistID=ag.ArtistID
	WHERE aa.AliasID=$AliasID");
list($ArtistID, $ArtistName, $AliasName) = $DB->next_record(MYSQLI_NUM, false);

$DB->query("
	DELETE FROM artists_alias
	WHERE AliasID='$AliasID'");
$DB->query("
	UPDATE artists_alias
	SET Redirect='0'
	WHERE Redirect='$AliasID'");

Misc::write_log("The alias $AliasID ($AliasName) was removed from the artist $ArtistID ($ArtistName) by user $LoggedUser[ID] ($LoggedUser[Username])");

header("Location: $_SERVER[HTTP_REFERER]");
