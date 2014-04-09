<?
/***************************************************************
* This page handles the backend of the "edit group ID" function
* (found on edit.php). It simply changes the group ID of a
* torrent.
****************************************************************/

if (!check_perms('torrents_edit')) {
	error(403);
}

$OldGroupID = $_POST['oldgroupid'];
$GroupID = $_POST['groupid'];
$TorrentID = $_POST['torrentid'];

if (!is_number($OldGroupID) || !is_number($GroupID) || !is_number($TorrentID) || !$OldGroupID || !$GroupID || !$TorrentID) {
	error(0);
}
if ($OldGroupID == $GroupID) {
	header('Location: '.$_SERVER['HTTP_REFERER']);
	die();
}

//Everything is legit, let's just confim they're not retarded
if (empty($_POST['confirm'])) {
	$DB->query("
		SELECT Name
		FROM torrents_group
		WHERE ID = $OldGroupID");
	if (!$DB->has_results()) {
		//Trying to move to an empty group? I think not!
		set_message('The destination torrent group does not exist!');
		header('Location: '.$_SERVER['HTTP_REFERER']);
		die();
	}
	list($Name) = $DB->next_record();
	$DB->query("
		SELECT CategoryID, Name
		FROM torrents_group
		WHERE ID = $GroupID");
	list($CategoryID, $NewName) = $DB->next_record();
	if ($Categories[$CategoryID - 1] != 'Music') {
		error('Destination torrent group must be in the "Music" category.');
	}

	$Artists = Artists::get_artists(array($OldGroupID, $GroupID));

	View::show_header();
?>
	<div class="thin">
		<div class="header">
			<h2>Torrent Group ID Change Confirmation</h2>
		</div>
		<div class="box pad">
			<form class="confirm_form" name="torrent_group" action="torrents.php" method="post">
				<input type="hidden" name="action" value="editgroupid" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="confirm" value="true" />
				<input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
				<input type="hidden" name="oldgroupid" value="<?=$OldGroupID?>" />
				<input type="hidden" name="groupid" value="<?=$GroupID?>" />
				<h3>You are attempting to move the torrent with ID <?=$TorrentID?> from the group:</h3>
				<ul>
					<li><?= Artists::display_artists($Artists[$OldGroupID], true, false)?> - <a href="torrents.php?id=<?=$OldGroupID?>"><?=$Name?></a></li>
				</ul>
				<h3>Into the group:</h3>
				<ul>
					<li><?= Artists::display_artists($Artists[$GroupID], true, false)?> - <a href="torrents.php?id=<?=$GroupID?>"><?=$NewName?></a></li>
				</ul>
				<input type="submit" value="Confirm" />
			</form>
		</div>
	</div>
<?
	View::show_footer();
} else {
	authorize();

	$DB->query("
		UPDATE torrents
		SET	GroupID = '$GroupID'
		WHERE ID = $TorrentID");

	// Delete old torrent group if it's empty now
	$DB->query("
		SELECT COUNT(ID)
		FROM torrents
		WHERE GroupID = '$OldGroupID'");
	list($TorrentsInGroup) = $DB->next_record();
	if ($TorrentsInGroup == 0) {
		// TODO: votes etc!
		$DB->query("
			UPDATE comments
			SET PageID = '$GroupID'
			WHERE Page = 'torrents'
				AND PageID = '$OldGroupID'");
		$Cache->delete_value("torrent_comments_{$GroupID}_catalogue_0");
		$Cache->delete_value("torrent_comments_$GroupID");
		Torrents::delete_group($OldGroupID);
	} else {
		Torrents::update_hash($OldGroupID);
	}
	Torrents::update_hash($GroupID);

	Misc::write_log("Torrent $TorrentID was edited by " . $LoggedUser['Username']); // TODO: this is probably broken
	Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "merged group $OldGroupID", 0);
	$DB->query("
		UPDATE group_log
		SET GroupID = $GroupID
		WHERE GroupID = $OldGroupID");

	$Cache->delete_value("torrents_details_$GroupID");
	$Cache->delete_value("torrent_download_$TorrentID");

	header("Location: torrents.php?id=$GroupID");
	}
?>
