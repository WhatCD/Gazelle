<?
/*********************************************************************\
//--------------Get Post--------------------------------------------//

This gets the raw BBCode of a post. It's used for editing and 
quoting posts. 

It gets called if $_GET['action'] == 'get_post'. It requires 
$_GET['post'], which is the ID of the post.

\*********************************************************************/

// Quick SQL injection check
if(!$_GET['post'] || !is_number($_GET['post'])){
	error(0);
}

// Variables for database input
$PostID = $_GET['post'];

// Mainly 
$DB->query("SELECT Body FROM collages_comments WHERE ID='$PostID'");
list($Body) = $DB->next_record(MYSQLI_NUM);

// This gets sent to the browser, which echoes it wherever 

echo trim($Body);
