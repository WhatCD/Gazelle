<?
authorize();


$CollageID = $_POST['collageid'];
if(!is_number($CollageID) || !$CollageID) { 
	error(404); 
}

$DB->query("SELECT Name, UserID FROM collages WHERE ID='$CollageID'");
list($Name, $UserID) = $DB->next_record();

if(!check_perms('site_collages_delete') && $UserID != $LoggedUser['ID']) {
	error(403);
}

$Reason = trim($_POST['reason']);
if(!$Reason) {
	error("You must enter a reason!");
}

$DB->query("SELECT GroupID FROM collages_torrents WHERE CollageID='$CollageID'");
while(list($GroupID) = $DB->next_record()) {
	$Cache->delete_value('torrents_details_'.$GroupID);
}

if (preg_match("/personal collage$/", $Name) > 0) {
	$DB->query("DELETE FROM collages WHERE ID='$CollageID'");
	$DB->query("DELETE FROM collages_torrents WHERE CollageID='$CollageID'");
	$DB->query("DELETE FROM collages_comments WHERE CollageID='$CollageID'");
} else {
	$DB->query("UPDATE collages SET Deleted = '1' WHERE ID='$CollageID'");
}

write_log("Collage ".$CollageID." (".$Name.") was deleted by ".$LoggedUser['Username'].": ".$Reason);

$Cache->delete_value('collage_'.$CollageID);
header('Location: collages.php');
