<?
include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class
$Text = new TEXT;

authorize();

// Quick SQL injection check
if (!$_GET['postid'] || !is_number($_GET['postid'])) {
	error(0);
}
$PostID = $_GET['postid'];

// Make sure they are moderators
if (!check_perms('site_moderate_forums')) {
	error(403);
}

// Get number of pages
// $Pages = number of pages in the thread
// $Page = which page the post is on
$DB->query("SELECT
	CollageID,
	CEIL(COUNT(ID)/" . TORRENT_COMMENTS_PER_PAGE . ") AS Pages,
	CEIL(SUM(IF(ID<='$PostID',1,0))/" . TORRENT_COMMENTS_PER_PAGE . ") AS Page
	FROM collages_comments
	WHERE CollageID=(SELECT CollageID FROM collages_comments WHERE ID='$PostID')
	GROUP BY CollageID");
list($CollageID, $Pages, $Page) = $DB->next_record();

$DB->query("DELETE FROM collages_comments WHERE ID='$PostID'");

$Cache->delete_value('collage_'.$CollageID);
$Cache->increment_value('collage_comments_'.$CollageID, -1);

//We need to clear all subsequential catalogues as they've all been bumped with the absence of this post
$ThisCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $Page - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
$LastCatalogue = floor((TORRENT_COMMENTS_PER_PAGE * $Pages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
for ($i = $ThisCatalogue; $i <= $LastCatalogue; $i++) {
	$Cache->delete_value('collage_comments_'.$CollageID.'_catalogue_'.$i);
}
?>
