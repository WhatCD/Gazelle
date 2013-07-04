<?

authorize();

//Set by system
if (!$_POST['groupid'] || !is_number($_POST['groupid'])) {
	error(404);
}
$GroupID = $_POST['groupid'];

//Usual perm checks
if (!check_perms('torrents_edit')) {
	$DB->query("
		SELECT UserID
		FROM torrents
		WHERE GroupID = $GroupID");
	if (!in_array($LoggedUser['ID'], $DB->collect('UserID'))) {
		error(403);
	}
}


if (check_perms('torrents_freeleech') && (isset($_POST['freeleech']) xor isset($_POST['neutralleech']) xor isset($_POST['unfreeleech']))) {
	if (isset($_POST['freeleech'])) {
		$Free = 1;
	} elseif (isset($_POST['neutralleech'])) {
		$Free = 2;
	} else {
		$Free = 0;
	}

	if (isset($_POST['freeleechtype']) && in_array($_POST['freeleechtype'], array(0, 1, 2, 3))) {
		$FreeType = $_POST['freeleechtype'];
	} else {
		error(404);
	}

	Torrents::freeleech_groups($GroupID, $Free, $FreeType);
}

//Escape fields
$Year = db_string((int)$_POST['year']);
$RecordLabel = db_string($_POST['record_label']);
$CatalogueNumber = db_string($_POST['catalogue_number']);

// Get some info for the group log
$DB->query("
	SELECT Year
	FROM torrents_group
	WHERE ID = $GroupID");
list($OldYear) = $DB->next_record();



$DB->query("
	UPDATE torrents_group
	SET
		Year = '$Year',
		RecordLabel = '".$RecordLabel."',
		CatalogueNumber = '".$CatalogueNumber."'
	WHERE ID = $GroupID");

if ($OldYear != $Year) {
	$DB->query("
		INSERT INTO group_log (GroupID, UserID, Time, Info)
		VALUES ('$GroupID', ".$LoggedUser['ID'].", '".sqltime()."', '".db_string("Year changed from $OldYear to $Year")."')");
}

$DB->query("
	SELECT ID
	FROM torrents
	WHERE GroupID = '$GroupID'");
while (list($TorrentID) = $DB->next_record()) {
	$Cache->delete_value("torrent_download_$TorrentID");
}
Torrents::update_hash($GroupID);
$Cache->delete_value("torrents_details_$GroupID");

header("Location: torrents.php?id=$GroupID");
?>
