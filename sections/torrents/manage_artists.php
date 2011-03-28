<?
if(empty($_POST['importance']) || empty($_POST['artists']) || empty($_POST['groupid']) || !is_number($_POST['importance']) || !is_number($_POST['groupid'])) {
	error(0);
}
if(!check_perms('torrents_edit')) {
	error(403);
}
authorize();

$Artists = explode(',', $_POST['artists']);
foreach($Artists as &$Artist) {
	if(!is_number($Artist)) {
		unset($Artist);
	} else {
		$Cache->delete_value('artist_'.$Artist);
	}
}
$ArtistsString = implode(',', $Artists);

if(count($Artists) > 0) {
	if($_POST['manager_action'] == 'delete') {
		$DB->query("SELECT Name FROM torrents_group WHERE ID = '".$_POST['groupid']."'");
		list($GroupName) = $DB->next_record();
		$DB->query("SELECT ArtistID, Name FROM artists_group WHERE ArtistID IN (".$ArtistsString.")");
		$ArtistNames = $DB->to_array('ArtistID');
		foreach($ArtistNames as $ArtistID => $ArtistInfo) {
			write_log("Artist ".$ArtistID." (".$ArtistInfo['Name'].") was removed from the group ".$_POST['groupid']." (".$GroupName.") by user ".$LoggedUser['ID']." (".$LoggedUser['Username'].")");
		}

		$DB->query("DELETE FROM torrents_artists WHERE GroupID = '".$_POST['groupid']."' AND ArtistID IN (".$ArtistsString.")");
		$DB->query("SELECT ArtistID
			FROM requests_artists
			WHERE ArtistID IN (".$ArtistsString.")
		UNION SELECT ArtistID
			FROM torrents_artists
			WHERE ArtistID IN (".$ArtistsString.")");
		$Items = $DB->collect('ArtistID');
		$EmptyArtists = array_diff($Artists, $Items);
		foreach($EmptyArtists as $ArtistID) {
			delete_artist($ArtistID);
		}
	} else {
		$DB->query("UPDATE torrents_artists SET Importance = '".$_POST['importance']."' WHERE GroupID = '".$_POST['groupid']."' AND ArtistID IN (".$ArtistsString.")");
	}
	$Cache->delete_value('groups_artists_'.$_POST['groupid']);
	header("Location: torrents.php?id=".$_POST['groupid']);
}
?>
