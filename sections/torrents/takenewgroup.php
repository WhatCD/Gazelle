<?
/***************************************************************
* This page handles the backend of the "new group" function
* which splits a torrent off into a new group.
****************************************************************/

authorize();

if (!check_perms('torrents_edit')) {
	error(403);
}

$OldGroupID = $_POST['oldgroupid'];
$TorrentID = $_POST['torrentid'];
$ArtistName = db_string(trim($_POST['artist']));
$Title = db_string(trim($_POST['title']));
$Year = trim($_POST['year']);

if (!is_number($OldGroupID) || !is_number($TorrentID) || !is_number($Year) || !$OldGroupID || !$TorrentID || !$Year || empty($Title) || empty($ArtistName)) {
	error(0);
}

//Everything is legit, let's just confim they're not retarded
if (empty($_POST['confirm'])) {
	View::show_header();
?>
	<div class="center thin">
	<div class="header">
		<h2>Split Confirm!</h2>
	</div>
	<div class="box pad">
		<form class="confirm_form" name="torrent_group" action="torrents.php" method="post">
			<input type="hidden" name="action" value="newgroup" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="confirm" value="true" />
			<input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
			<input type="hidden" name="oldgroupid" value="<?=$OldGroupID?>" />
			<input type="hidden" name="artist" value="<?=display_str($_POST['artist'])?>" />
			<input type="hidden" name="title" value="<?=display_str($_POST['title'])?>" />
			<input type="hidden" name="year" value="<?=$Year?>" />
			<h3>You are attempting to split the torrent <a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> off into a new group:</h3>
			<ul><li><?=display_str($_POST['artist'])?> - <?=display_str($_POST['title'])?> [<?=$Year?>]</li></ul>
			<input type="submit" value="Confirm" />
		</form>
	</div>
	</div>
<?
	View::show_footer();
} else {
	$DB->query("
		SELECT ArtistID, AliasID, Redirect, Name
		FROM artists_alias
		WHERE Name = '$ArtistName'");
	if (!$DB->has_results()) {
		$Redirect = 0;
		$DB->query("
			INSERT INTO artists_group (Name)
			VALUES ('$ArtistName')");
		$ArtistID = $DB->inserted_id();
		$DB->query("
			INSERT INTO artists_alias (ArtistID, Name)
			VALUES ('$ArtistID', '$ArtistName')");
		$AliasID = $DB->inserted_id();
	} else {
		list($ArtistID, $AliasID, $Redirect, $ArtistName) = $DB->next_record();
		if ($Redirect) {
			$AliasID = $Redirect;
		}
	}

	$DB->query("
		INSERT INTO torrents_group
			(ArtistID, CategoryID, Name, Year, Time, WikiBody, WikiImage)
		VALUES
			($ArtistID, '1', '$Title', '$Year', '".sqltime()."', '', '')");
	$GroupID = $DB->inserted_id();

	$DB->query("
		INSERT INTO torrents_artists
			(GroupID, ArtistID, AliasID, Importance, UserID)
		VALUES
			('$GroupID', '$ArtistID', '$AliasID', '1', '$LoggedUser[ID]')");

	$DB->query("
		UPDATE torrents
		SET GroupID = '$GroupID'
		WHERE ID = '$TorrentID'");

	// Delete old group if needed
	$DB->query("
		SELECT ID
		FROM torrents
		WHERE GroupID = '$OldGroupID'");
	if (!$DB->has_results()) {
		Torrents::delete_group($OldGroupID);
	} else {
		Torrents::update_hash($OldGroupID);
	}

	Torrents::update_hash($GroupID);

	$Cache->delete_value("torrent_download_$TorrentID");

	Misc::write_log("Torrent $TorrentID was edited by " . $LoggedUser['Username']);

	header("Location: torrents.php?id=$GroupID");
}
?>
