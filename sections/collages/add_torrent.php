<?
authorize();

include(SERVER_ROOT.'/classes/class_validate.php');
$Val = new VALIDATE;

function AddTorrent($CollageID, $GroupID) {
	global $Cache, $LoggedUser, $DB;

	$DB->query("SELECT MAX(Sort) FROM collages_torrents WHERE CollageID='$CollageID'");
	list($Sort) = $DB->next_record();
	$Sort+=10;

	$DB->query("SELECT GroupID FROM collages_torrents WHERE CollageID='$CollageID' AND GroupID='$GroupID'");
	if($DB->record_count() == 0) {
		$DB->query("INSERT IGNORE INTO collages_torrents
			(CollageID, GroupID, UserID, Sort, AddedOn) 
			VALUES
			('$CollageID', '$GroupID', '$LoggedUser[ID]', '$Sort', NOW())");
		
		$DB->query("UPDATE collages SET NumTorrents=NumTorrents+1 WHERE ID='$CollageID'");

		$Cache->delete_value('collage_'.$CollageID);
		$Cache->delete_value('torrents_details_'.$GroupID);
		$Cache->delete_value('torrent_collages_'.$GroupID);
		$Cache->delete_value('torrent_collages_personal_'.$GroupID);
		
		$DB->query("SELECT UserID FROM users_collage_subs WHERE CollageID=$CollageID");
		while (list($CacheUserID) = $DB->next_record()) {
			$Cache->delete_value('collage_subs_user_new_'.$CacheUserID);
		}
	}
}

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

if ($_REQUEST['action'] == 'add_torrent') {
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
	
	AddTorrent($CollageID, $GroupID);
} else {
	$URLRegex = '/^https?:\/\/(www\.|ssl\.)?'.NONSSL_SITE_URL.'\/torrents\.php\?(page=[0-9]+&)?id=([0-9]+)/i';
	
	$URLs = explode("\n",$_REQUEST['urls']);
	$GroupIDs = array();
	$Err = '';
	
	foreach ($URLs as $URL) {
		$URL = trim($URL);
		if ($URL == '') { continue; }
		
		$Matches = array();
		if (preg_match($URLRegex, $URL, $Matches)) {
			$GroupIDs[] = $Matches[3];
			$GroupID    = $Matches[3];
		} else {
			$Err = "One of the entered URLs ($URL) does not correspond to a torrent on the site.";
			break;
		}
		
		$DB->query("SELECT ID FROM torrents_group WHERE ID='$GroupID'");
		if(!$DB->record_count()) {
			$Err = "One of the entered URLs ($URL) does not correspond to a torrent on the site.";
			break;
		}
	}
	
	if($Err) {
		error($Err);
		header('Location: collages.php?id='.$CollageID);
		die();
	}

	foreach ($GroupIDs as $GroupID) {
		AddTorrent($CollageID, $GroupID);
	}	
}

header('Location: collages.php?id='.$CollageID);

?>
