<?
//******************************************************************************//
//--------------- Take unfill request ------------------------------------------//

authorize();

$RequestID = $_POST['id'];
if(!is_number($RequestID)){
	error(0);
}

$DB->query("SELECT
		r.CategoryID,
		r.UserID, 
		r.FillerID, 
		r.Title,
		u.Uploaded,
		r.GroupID
	FROM requests AS r 
		LEFT JOIN users_main AS u ON u.ID=FillerID
	WHERE r.ID= ".$RequestID);
list($CategoryID, $UserID, $FillerID, $Title, $Uploaded, $GroupID) = $DB->next_record();

if((($LoggedUser['ID'] != $UserID && $LoggedUser['ID'] != $FillerID) && !check_perms('site_moderate_requests')) || $FillerID == 0) {
		error(403);
}

// Unfill
$DB->query("UPDATE requests SET
			TorrentID = 0,
			FillerID = 0,
			TimeFilled = '0000-00-00 00:00:00',
			Visible = 1
			WHERE ID = ".$RequestID);

$CategoryName = $Categories[$CategoryID - 1];

if($CategoryName == "Music") {
	$ArtistForm = get_request_artists($RequestID);
	$ArtistName = display_artists($ArtistForm, false, true);
	$FullName = $ArtistName.$Title;
} else {
	$FullName = $Title;
}

$RequestVotes = get_votes_array($RequestID);

if ($RequestVotes['TotalBounty'] > $Uploaded) {
	// If we can't take it all out of upload, zero that out and add whatever is left as download.
	$DB->query("UPDATE users_main SET Uploaded = 0 WHERE ID = ".$FillerID);
	$DB->query("UPDATE users_main SET Downloaded = Downloaded + ".($RequestVotes['TotalBounty']-$Uploaded)." WHERE ID = ".$FillerID);
} else {
	$DB->query("UPDATE users_main SET Uploaded = Uploaded - ".$RequestVotes['TotalBounty']." WHERE ID = ".$FillerID);
}
send_pm($FillerID, 0, db_string("A request you filled has been unfilled"), db_string("The request '[url=http://".NONSSL_SITE_URL."/requests.php?action=view&id=".$RequestID."]".$FullName."[/url]' was unfilled by [url=http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID']."]".$LoggedUser['Username']."[/url] for the reason: ".$_POST['reason']));

$Cache->delete_value('user_stats_'.$FillerID);

if($UserID != $LoggedUser['ID']) {
	send_pm($UserID, 0, db_string("A request you created has been unfilled"), db_string("The request '[url=http://".NONSSL_SITE_URL."/requests.php?action=view&id=".$RequestID."]".$FullName."[/url]' was unfilled by [url=http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID']."]".$LoggedUser['Username']."[/url] for the reason: ".$_POST['reason']));
}

write_log("Request $RequestID ($FullName), with a ".get_size($RequestVotes['TotalBounty'])." bounty, was un-filled by user ".$LoggedUser['ID']." (".$LoggedUser['Username'].") for the reason: ".$_POST['reason']);

$Cache->delete_value('request_'.$RequestID);
$Cache->delete_value('request_artists_'.$RequestID);
if ($GroupID) {
	$Cache->delete_value('requests_group_'.$GroupID);
}

update_sphinx_requests($RequestID);

if(!empty($ArtistForm)) {
	foreach($ArtistForm as $ArtistType) {
		foreach($ArtistType as $Artist) {
			$Cache->delete_value('artist_'.$Artist['id']);
			$Cache->delete_value('artists_requests_'.$Artist['id']);
		}
	}
}


header('Location: requests.php?action=view&id='.$RequestID);
?>
