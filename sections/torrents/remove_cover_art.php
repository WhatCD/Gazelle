<?
authorize();
if (!check_perms('site_edit_wiki')) {
	error(403);
}

$ID = $_GET['id'];
$GroupID = $_GET['groupid'];


if (!is_number($ID) || !is_number($ID) || !is_number($GroupID) || !is_number($GroupID)) {
	error(404);
}

$DB->query("
	SELECT Image, Summary
	FROM cover_art
	WHERE ID = '$ID'");
list($Image, $Summary) = $DB->next_record();

$DB->query("
	DELETE FROM cover_art
	WHERE ID = '$ID'");

$DB->query("
	INSERT INTO group_log
		(GroupID, UserID, Time, Info)
	VALUES
		('$GroupID', ".$LoggedUser['ID'].", '".sqltime()."', '".db_string("Additional cover \"$Summary - $Image\" removed from group")."')");

$Cache->delete_value("torrents_cover_art_$GroupID");
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
