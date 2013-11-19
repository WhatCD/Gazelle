<?
//TODO: make this use the cache version of the thread, save the db query
/*********************************************************************\
//--------------Get Post--------------------------------------------//

This gets the raw BBCode of a post. It's used for editing and
quoting posts.

It gets called if $_GET['action'] == 'get_post'. It requires
$_GET['post'], which is the ID of the post.

\*********************************************************************/

// Quick SQL injection check
if (!$_GET['post'] || !is_number($_GET['post'])) {
	error(0);
}

// Variables for database input
$PostID = $_GET['post'];

// Mainly
$DB->query("
	SELECT
		p.Body,
		t.ForumID
	FROM forums_posts AS p
		JOIN forums_topics AS t ON p.TopicID = t.ID
	WHERE p.ID = '$PostID'");
list($Body, $ForumID) = $DB->next_record(MYSQLI_NUM);

// Is the user allowed to view the post?
if (!Forums::check_forumperm($ForumID)) {
	error(0);
}

// This gets sent to the browser, which echoes it wherever

echo trim($Body);

?>
