<?
$ArtistID = db_string($_GET['artistid']);
$GroupID = db_string($_GET['groupid']);

if(!is_number($ArtistID) || !is_number($GroupID)) {
	error(404);
}
if(!check_perms('torrents_edit')) {
	error(403);
}

$DB->query("DELETE FROM torrents_artists WHERE GroupID='$GroupID' AND ArtistID='$ArtistID'");
$DB->query("SELECT Name FROM artists_group WHERE ArtistID=".$ArtistID);
list($ArtistName) = $DB->next_record();

$DB->query("SELECT Name FROM torrents_group WHERE ID=".$GroupID);
list($GroupName) = $DB->next_record();

//Get a count of how many groups or requests use this artist ID
$DB->query("SELECT ag.ArtistID
			FROM artists_group as ag 
				LEFT JOIN requests_artists AS ra ON ag.ArtistID=ra.ArtistID 
			WHERE ra.ArtistID IS NOT NULL
				AND ag.ArtistID = ".$ArtistID);
$ReqCount = $DB->record_count();
$DB->query("SELECT ag.ArtistID
			FROM artists_group as ag 
				LEFT JOIN torrents_artists AS ta ON ag.ArtistID=ta.ArtistID 
			WHERE ta.ArtistID IS NOT NULL
				AND ag.ArtistID = ".$ArtistID);
$GroupCount = $DB->record_count();
if(($ReqCount + $GroupCount) == 0) {
	//The only group to use this artist
	delete_artist($ArtistID);
} else {
	//Not the only group, still need to clear cache
	$Cache->delete_value('artist_'.$ArtistID);
}

$DB->query("INSERT INTO torrents_group (ID, NumArtists) 
		SELECT ta.GroupID, COUNT(ta.ArtistID) 
		FROM torrents_artists AS ta 
		WHERE ta.GroupID='$GroupID' 
		AND ta.Importance='1'
		GROUP BY ta.GroupID 
	ON DUPLICATE KEY UPDATE 
	NumArtists=VALUES(NumArtists);");

$Cache->delete_value('torrents_details_'.$GroupID); // Delete torrent group cache
$Cache->delete_value('groups_artists_'.$GroupID); // Delete group artist cache
write_log("Artist ".$ArtistID." (".$ArtistName.") was removed from the group ".$GroupID." (".$GroupName.") by user ".$LoggedUser['ID']." (".$LoggedUser['Username'].")");

update_hash($GroupID);

header('Location: '.$_SERVER['HTTP_REFERER']);
?>
