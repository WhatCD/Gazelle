<?
if(!check_perms('torrents_edit')) { error(403); }

$GroupID = $_POST['groupid'];
$OldGroupID = $GroupID;
$NewGroupID = db_string($_POST['targetgroupid']);

if(!$GroupID || !is_number($GroupID)) { error(404); }
if(!$NewGroupID || !is_number($NewGroupID)) { error(404); }
if($NewGroupID == $GroupID) {
	error('Old group ID is the same as new group ID!');
}
$DB->query("SELECT ID FROM torrents_group WHERE ID='$NewGroupID'");
if($DB->record_count()==0) {
	error('Target group does not exist.');
}

//Everything is legit, let's just confim they're not retarded
if(empty($_POST['confirm'])) {
	$DB->query("SELECT Name FROM torrents_group WHERE ID = ".$GroupID);
	list($Name) = $DB->next_record();
	$DB->query("SELECT Name FROM torrents_group WHERE ID = ".$NewGroupID);
	list($NewName) = $DB->next_record();
	
	$Artists = get_artists(array($GroupID, $NewGroupID));
	
	show_header();
?>
	<div class="center thin">
	<h2>Merge Confirm!</h2>
	<div class="box pad">
		<form action="torrents.php" method="post">
			<input type="hidden" name="action" value="merge" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="confirm" value="true" />
			<input type="hidden" name="groupid" value="<?=$GroupID?>" />
			<input type="hidden" name="targetgroupid" value="<?=$NewGroupID?>" />
			<h2>You are attempting to merge the group:</h2>
			<ul><li><?= display_artists($Artists[$GroupID], true, false)?> - <a href="torrents.php?id=<?=$GroupID?>"><?=$Name?></a></li></ul>
			<h2>Into the group:</h2>
			<ul><li><?= display_artists($Artists[$NewGroupID], true, false)?> - <a href="torrents.php?id=<?=$NewGroupID?>"><?=$NewName?></a></li></ul>
			<input type="submit" value="Confirm" />
		</form>
	</div>
	</div>
<?
	show_footer();
} else {
	authorize();

	$DB->query("UPDATE torrents SET GroupID='$NewGroupID' WHERE GroupID='$GroupID'");
	$DB->query("UPDATE wiki_torrents SET PageID='$NewGroupID' WHERE PageID='$GroupID'");
	$DB->query("UPDATE torrents_comments SET GroupID='$NewGroupID' WHERE GroupID='$GroupID'");
	
	delete_group($GroupID);
	
	$GroupID=$NewGroupID;
	
	//Collages
	$DB->query("SELECT CollageID FROM collages_torrents WHERE GroupID='$OldGroupID'"); //Select all collages that contain edited group
	while(list($CollageID) = $DB->next_record()) {
		$DB->query("UPDATE IGNORE collages_torrents SET GroupID='$NewGroupID' WHERE GroupID='$OldGroupID' AND CollageID='$CollageID'"); //Change collage groupid to new ID
		$DB->query("DELETE FROM collages_torrents WHERE GroupID='$OldGroupID' AND CollageID='$CollageID'");
		$Cache->delete_value('collage_'.$CollageID);
	}
	
	
	$DB->query("SELECT ID FROM torrents WHERE GroupID='$OldGroupID'");
	while(list($TorrentID) = $DB->next_record()) {
		$Cache->delete_value('torrent_download_'.$TorrentID);
	}
	$Cache->delete_value('torrents_details_'.$GroupID);
	
	$DB->query("SELECT DISTINCT ArtistID FROM torrents_artists WHERE GroupID IN ('$GroupID', '$OldGroupID')");
	while(list($ArtistID) = $DB->next_record()) {
		$Cache->delete_value('artist_'.$ArtistID); 
	}
	
	$Cache->delete_value('torrentcomments_count_'.$GroupID);
	$Cache->delete_value('torrentcomment_'.$GroupID.'_page_1');
	$Cache->delete_value('groups_artists_'.$GroupID);
	update_hash($GroupID);
	
	header('Location: torrents.php?id='.$GroupID);
}
?>
