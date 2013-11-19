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

// Message is selected providing the user quoting is one of the two people in the thread
$DB->query("
	SELECT m.Body
	FROM pm_messages AS m
		JOIN pm_conversations_users AS u ON m.ConvID = u.ConvID
	WHERE m.ID = '$PostID'
		AND u.UserID = ".$LoggedUser['ID']);
list($Body) = $DB->next_record(MYSQLI_NUM);

// This gets sent to the browser, which echoes it wherever

echo trim($Body);

?>
