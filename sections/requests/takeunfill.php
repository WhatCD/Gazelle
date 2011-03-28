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
		u.Uploaded
	FROM requests AS r 
		LEFT JOIN users_main AS u ON u.ID=FillerID
	WHERE r.ID= ".$RequestID);
list($CategoryID, $UserID, $FillerID, $Title, $Uploaded) = $DB->next_record();

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
	$DB->query("UPDATE users_main SET Downloaded = Downloaded + ".$RequestVotes['TotalBounty']." WHERE ID = ".$FillerID);
} else {
	$DB->query("UPDATE users_main SET Uploaded = Uploaded - ".$RequestVotes['TotalBounty']." WHERE ID = ".$FillerID);
}
send_pm($FillerID, 0, db_string("A request you filled has been unfilled"), db_string("The request '".$FullName."' was unfilled by [url=http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID']."]".$LoggedUser['Username']."[/url] for the reason: ".$_POST['reason']));

$Cache->delete_value('user_stats_'.$FillerID);

if($UserID != $LoggedUser['ID']) {
	send_pm($UserID, 0, db_string("A request you created has been unfilled"), db_string("The request '".$FullName."' was unfilled by [url=http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID']."]".$LoggedUser['Username']."[/url] for the reason: ".$_POST['reason']));
}

$DB->query("SELECT ID, UserID 
	FROM pm_conversations AS pc 
	JOIN pm_conversations_users AS pu ON pu.ConvID=pc.ID AND pu.UserID!=0 
	WHERE Subject='The request \"".db_string($FullName)."\" has been filled'");

$ConvIDs = implode(',',$DB->collect('ID'));
$UserIDs = $DB->collect('UserID');

if($ConvIDs){
	$DB->query("DELETE FROM pm_conversations WHERE ID IN($ConvIDs)");
	$DB->query("DELETE FROM pm_conversations_users WHERE ConvID IN($ConvIDs)");
	$DB->query("DELETE FROM pm_messages WHERE ConvID IN($ConvIDs)");
}
foreach($UserIDs as $UserID) {
	$Cache->delete_value('inbox_new_'.$UserID);
}

write_log("Request $RequestID ($FullName), with a ".get_size($RequestVotes['TotalBounty'])." bounty, was un-filled by user ".$LoggedUser['ID']." (".$LoggedUser['Username'].") for the reason: ".$_POST['reason']);

$Cache->delete_value('request_'.$RequestID);

$SS->UpdateAttributes('requests', array('torrentid','fillerid'), array($RequestID => array(0,0)));
update_sphinx_requests($RequestID);



header('Location: requests.php?action=view&id='.$RequestID);
?>
