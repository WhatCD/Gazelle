<?
authorize();

/*********************************************************************\
//--------------Take Post--------------------------------------------//

The page that handles the backend of the 'edit post' function. 

$_GET['action'] must be "takeedit" for this page to work.

It will be accompanied with:
	$_POST['post'] - the ID of the post
	$_POST['body']


\*********************************************************************/

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

// Quick SQL injection check
if(!$_POST['post'] || !is_number($_POST['post']) || !is_number($_POST['key'])) {
	error(0,true);
}
// End injection check

// Variables for database input
$UserID = $LoggedUser['ID'];
$Body = db_string($_POST['body']); //Don't URL Decode
$PostID = $_POST['post'];
$Key = $_POST['key'];

// Mainly 
$DB->query("SELECT
		p.Body,
		p.AuthorID,
		p.TopicID,
		t.IsLocked,
		t.ForumID,
		f.MinClassWrite,
		CEIL((SELECT COUNT(ID) 
			FROM forums_posts 
			WHERE forums_posts.TopicID = p.TopicID 
			AND forums_posts.ID <= '$PostID')/".POSTS_PER_PAGE.") 
			AS Page
		FROM forums_posts as p
		JOIN forums_topics as t on p.TopicID = t.ID
		JOIN forums as f ON t.ForumID=f.ID 
		WHERE p.ID='$PostID'");
list($OldBody, $AuthorID, $TopicID, $IsLocked, $ForumID, $MinClassWrite, $Page) = $DB->next_record();

// Make sure they aren't trying to edit posts they shouldn't
// We use die() here instead of error() because whatever we spit out is displayed to the user in the box where his forum post is
if($LoggedUser['Class'] < $MinClassWrite || ($IsLocked && !check_perms('site_moderate_forums'))) { 
	error('Either the thread is locked, or you lack the permission to edit this post.',true);
}
if($UserID != $AuthorID && !check_perms('site_moderate_forums')) {
	error(403,true);
}
if($LoggedUser['DisablePosting']) {
	error('Your posting rights have been removed.',true);
}
if($DB->record_count()==0) {
	error(404,true);
}

// Perform the update
$DB->query("UPDATE forums_posts SET
	Body = '$Body',
	EditedUserID = '$UserID',
	EditedTime = '".sqltime()."'
	WHERE ID='$PostID'");

$CatalogueID = floor((POSTS_PER_PAGE*$Page-POSTS_PER_PAGE)/THREAD_CATALOGUE);
$Cache->begin_transaction('thread_'.$TopicID.'_catalogue_'.$CatalogueID);
if ($Cache->MemcacheDBArray[$Key]['ID'] != $PostID) {
	$Cache->cancel_transaction();
	$Cache->delete('thread_'.$TopicID.'_catalogue_'.$CatalogueID); //just clear the cache for would be cache-screwer-uppers
} else {
	$Cache->update_row($Key, array(
			'ID'=>$Cache->MemcacheDBArray[$Key]['ID'],
			'AuthorID'=>$Cache->MemcacheDBArray[$Key]['AuthorID'],
			'AddedTime'=>$Cache->MemcacheDBArray[$Key]['AddedTime'],
			'Body'=>$_POST['body'], //Don't url decode.
			'EditedUserID'=>$LoggedUser['ID'],
			'EditedTime'=>sqltime(),
			'Username'=>$LoggedUser['Username']
			));
	$Cache->commit_transaction(3600*24*5);
}
//$Cache->delete('thread_'.$TopicID.'_page_'.$Page); // Delete thread cache

$DB->query("INSERT INTO comments_edits (Page, PostID, EditUser, EditTime, Body)
								VALUES ('forums', ".$PostID.", ".$UserID.", '".sqltime()."', '".db_string($OldBody)."')");

// This gets sent to the browser, which echoes it in place of the old body
echo $Text->full_format($_POST['body']);
?>
<br /><br />Last edited by <a href="user.php?id=<?=$LoggedUser['ID']?>"><?=$LoggedUser['Username']?></a> just now
