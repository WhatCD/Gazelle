<?
$TagID = db_string($_GET['tagid']);
$GroupID = db_string($_GET['groupid']);

if(!is_number($TagID) || !is_number($GroupID)) {
	error(404);
}
if(!check_perms('site_delete_tag')) {
	error(403);
}

$DB->query("DELETE FROM torrents_tags_votes WHERE GroupID='$GroupID' AND TagID='$TagID'");
$DB->query("DELETE FROM torrents_tags WHERE GroupID='$GroupID' AND TagID='$TagID'");

$Cache->delete_value('torrents_details_'.$GroupID); // Delete torrent group cache
update_hash($GroupID);

$DB->query("SELECT COUNT(GroupID) FROM torrents_tags WHERE TagID=".$TagID);
list($Count) = $DB->next_record();
if($Count < 1) {
	$DB->query("SELECT Name FROM tags WHERE ID=".$TagID);
	list($TagName) = $DB->next_record();
	
	$DB->query("DELETE FROM tags WHERE ID=".$TagID);
}

header('Location: '.$_SERVER['HTTP_REFERER']);
?>
