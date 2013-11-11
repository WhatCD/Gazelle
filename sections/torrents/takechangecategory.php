<?
/***************************************************************
* Temp handler for changing the category for a single torrent.
****************************************************************/

authorize();
if (!check_perms('users_mod')) {
	error(403);
}

$OldGroupID = $_POST['oldgroupid'];
$TorrentID = $_POST['torrentid'];
$Title = db_string(trim($_POST['title']));
$OldCategoryID = $_POST['oldcategoryid'];
$NewCategoryID = $_POST['newcategoryid'];
if (!is_number($OldGroupID) || !is_number($TorrentID) || !$OldGroupID || !$TorrentID || empty($Title)) {
	error(0);
}

switch ($Categories[$NewCategoryID-1]) {
	case 'Music':
		$ArtistName = db_string(trim($_POST['artist']));
		$Year = trim($_POST['year']);
		$ReleaseType = trim($_POST['releasetype']);
		if (empty($Year) || empty($ArtistName) || !is_number($Year) || empty($ReleaseType) || !is_number($ReleaseType)) {
			error(0);
		}
		$DB->query("
			SELECT ArtistID, AliasID, Redirect, Name
			FROM artists_alias
			WHERE Name LIKE '$ArtistName'");
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
				$AliasID = $ArtistID;
			}
		}

		$DB->query("
			INSERT INTO torrents_group
				(ArtistID, CategoryID, Name, Year, ReleaseType, Time, WikiBody, WikiImage)
			VALUES
				($ArtistID, '1', '$Title', '$Year', '$ReleaseType', '".sqltime()."', '', '')");
		$GroupID = $DB->inserted_id();

		$DB->query("
			INSERT INTO torrents_artists
				(GroupID, ArtistID, AliasID, Importance, UserID)
			VALUES
				('$GroupID', '$ArtistID', '$AliasID', '1', '$LoggedUser[ID]')");
		break;
	case 'Audiobooks':
	case 'Comedy':
		$Year = trim($_POST['year']);
		if (empty($Year) || !is_number($Year)) {
			error(0);
		}
		$DB->query("
			INSERT INTO torrents_group
				(CategoryID, Name, Year, Time, WikiBody, WikiImage)
			VALUES
				($NewCategoryID, '$Title', '$Year', '".sqltime()."', '', '')");
		$GroupID = $DB->inserted_id();
		break;
	case 'Applications':
	case 'Comics':
	case 'E-Books':
	case 'E-Learning Videos':
		$DB->query("
			INSERT INTO torrents_group
				(CategoryID, Name, Time, WikiBody, WikiImage)
			VALUES
				($NewCategoryID, '$Title', '".sqltime()."', '', '')");
		$GroupID = $DB->inserted_id();
		break;
}

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
	// TODO: votes etc.
	$DB->query("
		UPDATE comments
		SET PageID = '$GroupID'
		WHERE Page = 'torrents'
			AND PageID = '$OldGroupID'");
	Torrents::delete_group($OldGroupID);
	$Cache->delete_value("torrent_comments_{$GroupID}_catalogue_0");
} else {
	Torrents::update_hash($OldGroupID);
}

Torrents::update_hash($GroupID);

$Cache->delete_value("torrent_download_$TorrentID");

Misc::write_log("Torrent $TorrentID was edited by $LoggedUser[Username]");
Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "merged from group $OldGroupID", 0);
$DB->query("
	UPDATE group_log
	SET GroupID = $GroupID
	WHERE GroupID = $OldGroupID");

header("Location: torrents.php?id=$GroupID");
?>
