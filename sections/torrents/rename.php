<?
authorize();

$GroupID = $_POST['groupid'];
$OldGroupID = $GroupID;
$NewName = $_POST['name'];

if (!$GroupID || !is_number($GroupID)) {
	error(404);
}

if (empty($NewName)) {
	error('Torrent groups must have a name');
}

if (!check_perms('torrents_edit')) {
	error(403);
}

$DB->query("
	SELECT Name
	FROM torrents_group
	WHERE ID = $GroupID");
list($OldName) = $DB->next_record(MYSQLI_NUM, false);

$DB->query("
	UPDATE torrents_group
	SET Name = '".db_string($NewName)."'
	WHERE ID = '$GroupID'");
$Cache->delete_value("torrents_details_$GroupID");

Torrents::update_hash($GroupID);

Misc::write_log("Torrent Group $GroupID ($OldName) was renamed to \"$NewName\" from \"$OldName\" by ".$LoggedUser['Username']);
Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "renamed to \"$NewName\" from \"$OldName\"", 0);

header("Location: torrents.php?id=$GroupID");
