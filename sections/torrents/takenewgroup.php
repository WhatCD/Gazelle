<?
/***************************************************************
* This page handles the backend of the "new group" function
* which splits a torrent off into a new group. 
****************************************************************/

authorize();

if(!check_perms('torrents_edit')) { error(403); }

$OldGroupID = $_POST['oldgroupid'];
$TorrentID = $_POST['torrentid'];
$ArtistName = db_string(trim($_POST['artist']));
$Title = db_string(trim($_POST['title']));
$Year = trim($_POST['year']);
$SearchText = db_string(trim($_POST['artist']) . ' ' . trim($_POST['title']) . ' ' . trim($_POST['year']));

$OldArtistID = (int)$_POST['oldartistid']; // Doesn't hit the database, so we don't have to be especially paranoid

if(!is_number($OldGroupID) || !is_number($TorrentID) || !is_number($Year) || !$OldGroupID || !$TorrentID || !$Year || empty($Title) || empty($ArtistName)) {
	error(0);
}

$DB->query("SELECT ArtistID, AliasID, Redirect, Name FROM artists_alias WHERE Name LIKE '$ArtistName'");
if($DB->record_count() == 0) {
	$Redirect = 0;
	$DB->query("INSERT INTO artists_group (Name) VALUES ('$ArtistName')");
	$ArtistID = $DB->inserted_id();
	$DB->query("INSERT INTO artists_alias (ArtistID, Name) VALUES ('$ArtistID', '$ArtistName')");
	list($AliasID) = $DB->next_record();
} else {
	list($ArtistID, $AliasID, $Redirect, $ArtistName) = $DB->next_record();
	if($Redirect) {
		$AliasID = $Redirect;
	}
}

$DB->query("INSERT INTO torrents_group
	(ArtistID, NumArtists, CategoryID, Name, Year, Time, WikiBody, WikiImage, SearchText) 
	VALUES
	($ArtistID, '1', '1', '$Title', '$Year', '".sqltime()."', '', '', '$SearchText')");
$GroupID = $DB->inserted_id();

$DB->query("INSERT INTO torrents_artists 
	(GroupID, ArtistID, AliasID, Importance, UserID) VALUES 
	('$GroupID', '$ArtistID', '$AliasID', '1', '$LoggedUser[ID]')");

$DB->query("UPDATE torrents SET
	GroupID='$GroupID'
	WHERE ID='$TorrentID'");

// Delete old group if needed
$DB->query("SELECT ID FROM torrents WHERE GroupID='$OldGroupID'");
if($DB->record_count() == 0) {
	delete_group($OldGroupID);
} else {
	update_hash($OldGroupID);
}

update_hash($GroupID);

$Cache->delete_value('torrent_download_'.$TorrentID);
$Cache->delete_value('artist_'.$ArtistID);
$Cache->delete_value('artist_'.$OldArtistID);

write_log("Torrent $TorrentID was edited by " . $LoggedUser['Username']);

header("Location: torrents.php?id=$GroupID");
?>
