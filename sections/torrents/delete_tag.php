<?
if (!empty($LoggedUser['DisableTagging']) || !check_perms('site_delete_tag')) {
	error(403);
}

$TagID = $_GET['tagid'];
$GroupID = $_GET['groupid'];

if (!is_number($TagID) || !is_number($GroupID)) {
	error(404);
}

$DB->query("
	SELECT Name
	FROM tags
	WHERE ID = '$TagID'");
if (list($TagName) = $DB->next_record()) {
	$DB->query("
		INSERT INTO group_log
			(GroupID, UserID, Time, Info)
		VALUES
			('$GroupID',".$LoggedUser['ID'].",'".sqltime()."','".db_string('Tag "'.$TagName.'" removed from group')."')");
}

$DB->query("
	DELETE FROM torrents_tags_votes
	WHERE GroupID = '$GroupID'
		AND TagID = '$TagID'");
$DB->query("
	DELETE FROM torrents_tags
	WHERE GroupID = '$GroupID'
		AND TagID = '$TagID'");

Torrents::update_hash($GroupID);

$DB->query("
	SELECT COUNT(GroupID)
	FROM torrents_tags
	WHERE TagID = $TagID");
list($Count) = $DB->next_record();
if ($Count < 1) {
	$DB->query("
		SELECT Name
		FROM tags
		WHERE ID = $TagID");
	list($TagName) = $DB->next_record();

	$DB->query("
		DELETE FROM tags
		WHERE ID = $TagID");
}
// Cache the deleted tag for 5 minutes
$Cache->cache_value('deleted_tags_'.$GroupID.'_'.$LoggedUser['ID'], $TagName, 300);
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
