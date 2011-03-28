<?
authorize();

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

// Quick SQL injection check
if(!$_POST['post'] || !is_number($_POST['post'])) {
	error(404);
}
// End injection check

// Variables for database input
$UserID = $LoggedUser['ID'];
$Body = db_string(urldecode($_POST['body']));
$PostID = $_POST['post'];

// Mainly 
$DB->query("SELECT cc.Body, cc.UserID, cc.CollageID, (SELECT COUNT(ID) FROM collages_comments WHERE ID <= ".$PostID." AND collages_comments.CollageID = cc.CollageID) FROM collages_comments AS cc WHERE cc.ID='$PostID'");
list($OldBody, $AuthorID, $CollageID, $PostNum) = $DB->next_record();

// Make sure they aren't trying to edit posts they shouldn't
// We use die() here instead of error() because whatever we spit out is displayed to the user in the box where his forum post is
if($UserID!=$AuthorID && !check_perms('site_moderate_forums')) {
	die('Permission denied');
}
if($DB->record_count()==0) {
	die('Post not found!');
}

// Perform the update
$DB->query("UPDATE collages_comments SET
		Body = '$Body'
		WHERE ID='$PostID'");

$Cache->delete_value('collage_'.$CollageID);


$PageNum = ceil($PostNum / TORRENT_COMMENTS_PER_PAGE);
$CatalogueID = floor((POSTS_PER_PAGE*$PageNum-POSTS_PER_PAGE)/THREAD_CATALOGUE);
$Cache->delete_value('collage_'.$CollageID.'_catalogue_'.$CatalogueID);

$DB->query("INSERT INTO comments_edits (Page, PostID, EditUser, EditTime, Body)
								VALUES ('collages', ".$PostID.", ".$UserID.", '".sqltime()."', '".db_string($OldBody)."')");

// This gets sent to the browser, which echoes it in place of the old body
echo $Text->full_format($_POST['body']);

?>
