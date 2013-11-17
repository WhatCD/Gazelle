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

// Message is selected providing the user quoting is the guy who opened the PM or has
// the right level
$DB->query("
	SELECT m.Message, c.Level, c.UserID
	FROM staff_pm_messages AS m
		JOIN staff_pm_conversations AS c ON m.ConvID = c.ID
	WHERE m.ID = '$PostID'");
list($Message, $Level, $UserID) = $DB->next_record(MYSQLI_NUM);

if (($LoggedUser['ID'] == $UserID) || ($IsFLS && $LoggedUser['Class'] >= $Level)) {
	// This gets sent to the browser, which echoes it wherever
	echo trim($Message);
} else {
	error(403);
}

?>
