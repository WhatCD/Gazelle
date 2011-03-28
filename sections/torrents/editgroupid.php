<?
/***************************************************************
* This page handles the backend of the "edit group ID" function
* (found on edit.php). It simply changes the group ID of a 
* torrent. 
****************************************************************/

if(!check_perms('torrents_edit')) { error(403); }

$OldGroupID = $_POST['oldgroupid'];
$GroupID = $_POST['groupid'];
$TorrentID = $_POST['torrentid'];

if(!is_number($OldGroupID) || !is_number($GroupID) || !is_number($TorrentID) || !$OldGroupID || !$GroupID || !$TorrentID) {
	error(0);
}
if($OldGroupID == $GroupID) {
	header('Location: '.$_SERVER['HTTP_REFERER']);
	die();
}

//Everything is legit, let's just confim they're not retarded
if(empty($_POST['confirm'])) {
	$DB->query("SELECT Name FROM torrents_group WHERE ID = ".$OldGroupID);
	if($DB->record_count() < 1) {
		//Trying to move to an empty group? I think not!
		set_message("That group doesn't exist!");
		header('Location: '.$_SERVER['HTTP_REFERER']);
		die();
	}
	list($Name) = $DB->next_record();
	$DB->query("SELECT Name FROM torrents_group WHERE ID = ".$GroupID);
	list($NewName) = $DB->next_record();
	
	$Artists = get_artists(array($OldGroupID, $GroupID));
	
	show_header();
?>
	<div class="thin">
	<h2>Change Group Confirm!</h2>
	<div class="box pad">
		<form action="torrents.php" method="post">
			<input type="hidden" name="action" value="editgroupid" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="confirm" value="true" />
			<input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
			<input type="hidden" name="oldgroupid" value="<?=$OldGroupID?>" />
			<input type="hidden" name="groupid" value="<?=$GroupID?>" />
			<h2>You are attempting to move the torrent with ID <?=$TorrentID?> from the group:</h2>
			<ul><li><?= display_artists($Artists[$OldGroupID], true, false)?> - <a href="torrents.php?id=<?=$OldGroupID?>"><?=$Name?></a></li></ul>
			<h2>Into the group:</h2>
			<ul><li><?= display_artists($Artists[$GroupID], true, false)?> - <a href="torrents.php?id=<?=$GroupID?>"><?=$NewName?></a></li></ul>
			<input type="submit" value="Confirm" />
		</form>
	</div>
	</div>
<?
	show_footer();
} else {
	authorize();

	$DB->query("UPDATE torrents
				SET	GroupID='$GroupID'
				WHERE ID=$TorrentID");
	
	// Delete old torrent group if it's empty now
	$DB->query("SELECT COUNT(ID) FROM torrents WHERE GroupID='$OldGroupID'");
	list($TorrentsInGroup) = $DB->next_record();
	if($TorrentsInGroup == 0) {
		delete_group($OldGroupID);
	} else {
		update_hash($OldGroupID);
	}
	update_hash($GroupID);
	
	// Clear artist caches
	$DB->query("SELECT DISTINCT ArtistID FROM torrents_artists WHERE GroupID IN ('$GroupID', '$OldGroupID')");
	while(list($ArtistID) = $DB->next_record()) {
		$Cache->delete_value('artist_'.$ArtistID); 
	}
	
	write_log("Torrent $TorrentID was edited by " . $LoggedUser['Username']); // TODO: this is probably broken
	
	$Cache->delete_value('torrents_details_'.$GroupID);	
	$Cache->delete_value('torrent_download_'.$TorrentID);
	
	header("Location: torrents.php?id=$GroupID");
	}
?>
