<?
enforce_login();
authorize();
if(!check_perms('site_moderate_forums')) {
	error(403);
}

$ThreadID = $_GET['threadid'];
$PostID = $_GET['postid'];
$Delete = !empty($_GET['remove']);

if(!$ThreadID || !$PostID || !is_number($ThreadID) || !is_number($PostID)) {
	error(404);
}

if($Delete) {
	$DB->query("UPDATE forums_topics SET StickyPostID = 0 WHERE ID = ".$ThreadID);
} else {
	$DB->query("UPDATE forums_topics SET StickyPostID = ".$PostID." WHERE ID = ".$ThreadID);
}
$Cache->delete_value('thread_'.$ThreadID.'_info');
$Cache->delete_value('thread_'.$ThreadID.'_catalogue_'.$CatalogueID);

header('Location: forums.php?action=viewthread&threadid='.$ThreadID);
