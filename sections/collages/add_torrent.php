<?
authorize();

include(SERVER_ROOT.'/classes/class_validate.php');
$Val = new VALIDATE;

$CollageID = $_POST['collageid'];
if(!is_number($CollageID)) { error(404); }

$DB->query("SELECT UserID, CategoryID, Locked, NumTorrents, MaxGroups, MaxGroupsPerUser FROM collages WHERE ID='$CollageID'");
list($UserID, $CategoryID, $Locked, $NumTorrents, $MaxGroups, $MaxGroupsPerUser) = $DB->next_record();
if($CategoryID == 0 && $UserID!=$LoggedUser['ID'] && !check_perms('site_collages_delete')) { error(403); }
if($Locked) { error(403); }
if($MaxGroups>0 && $NumTorrents>=$MaxGroups) { error(403); }
if($MaxGroupsPerUser>0) {
	$DB->query("SELECT COUNT(ID) FROM collages_torrents WHERE CollageID='$CollageID' AND UserID='$LoggedUser[ID]'");
	if($DB->record_count()>=$MaxGroupsPerUser) {
		error(403);
	}
}


$URLRegex = '/^https?:\/\/(www\.|ssl\.)?'.NONSSL_SITE_URL.'\/torrents\.php\?(page=[0-9]+&)?id=([0-9]+)/i';
$Val->SetFields('url', '1','regex','The URL must be a link to a torrent on the site.',array('regex'=>$URLRegex));
$Err = $Val->ValidateForm($_POST);

if($Err) {
	error($Err);
	header('Location: collages.php?id='.$CollageID);
	die();
}

$URL = $_POST['url'];

// Get torrent ID
$URLRegex = '/torrents\.php\?(page=[0-9]+&)?id=([0-9]+)/i';
preg_match($URLRegex, $URL, $Matches);
$TorrentID=$Matches[2];
if(!$TorrentID || (int)$TorrentID == 0) { error(404); }

$DB->query("SELECT ID FROM torrents_group WHERE ID='$TorrentID'");
list($GroupID) = $DB->next_record();
if(!$GroupID) {
	error('The torrent was not found in the database.');
}

$DB->query("SELECT MAX(Sort) FROM collages_torrents WHERE CollageID='$CollageID'");
list($Sort) = $DB->next_record();
$Sort+=10;

$DB->query("SELECT GroupID FROM collages_torrents WHERE CollageID='$CollageID' AND GroupID='$GroupID'");
if($DB->record_count() == 0) {
	$DB->query("INSERT IGNORE INTO collages_torrents
		(CollageID, GroupID, UserID, Sort) 
		VALUES
		('$CollageID', '$GroupID', '$LoggedUser[ID]', '$Sort')");
	
	$DB->query("UPDATE collages SET NumTorrents=NumTorrents+1 WHERE ID='$CollageID'");

	$Cache->delete_value('collage_'.$CollageID);
	$Cache->delete_value('torrents_details_'.$GroupID);
	$Cache->delete_value('torrent_collages_'.$GroupID);
} else {
	error('Torrent already in collage!');
}

header('Location: collages.php?id='.$CollageID);

?>
