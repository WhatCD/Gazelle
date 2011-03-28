<?
//******************************************************************************//
//--------------- Delete request -----------------------------------------------//

authorize();

$RequestID = $_POST['id'];
if(!is_number($RequestID)) {
	error(0);
}

$DB->query("SELECT UserID,
			Title,
			CategoryID
			FROM requests
			WHERE ID = ".$RequestID);
list($UserID, $Title, $CategoryID) = $DB->next_record();

if($LoggedUser['ID'] != $UserID && !check_perms('site_moderate_requests')) { 
	error(403);
}

$CategoryName = $Categories[$CategoryID - 1];

//Do we need to get artists?
if($CategoryName == "Music") {
	$ArtistForm = get_request_artists($RequestID);
	$ArtistName = display_artists($ArtistForm, false, true);
	$FullName = $ArtistName.$Title;	
} else {
	$FullName = $Title;
}



// Delete request, votes and tags
$DB->query("DELETE FROM requests WHERE ID='$RequestID'");
$DB->query("DELETE FROM requests_votes WHERE RequestID='$RequestID'");
$DB->query("DELETE FROM requests_tags WHERE RequestID='$RequestID'");
$DB->query("SELECT ArtistID FROM requests_artists WHERE RequestID = ".$RequestID);
$RequestArtists = $DB->to_array();
foreach($RequestArtists as $RequestArtist) {
	$Cache->delete_value('artists_requests_'.$RequestArtist);
}
$DB->query("DELETE FROM requests_artists WHERE RequestID='$RequestID'");

if($UserID != $LoggedUser['ID']) {
	send_pm($UserID, 0, db_string("A request you created has been deleted"), db_string("The request '".$FullName."' was deleted by [url=http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID']."]".$LoggedUser['Username']."[/url] for the reason: ".$_POST['reason']));
}

write_log("Request $RequestID ($FullName) was deleted by user ".$LoggedUser['ID']." (".$LoggedUser['Username'].") for the reason: ".$_POST['reason']);

$Cache->delete_value('request_'.$RequestID);
$Cache->delete_value('request_votes_'.$RequestID);
update_sphinx_requests($RequestID);

header('Location: requests.php');
?>
