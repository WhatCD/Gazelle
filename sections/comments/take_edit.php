<?
authorize();

include(SERVER_ROOT . '/classes/text.class.php'); // Text formatting class
$Text = new TEXT;

if (!isset($_POST['postid']) || !is_number($_POST['postid']) || !isset($_POST['body']) || trim($_POST['body']) === '') {
	error(0);
}

Comments::edit((int)$_POST['postid'], $_POST['body']);

// This gets sent to the browser, which echoes it in place of the old body
echo $Text->full_format($_POST['body']);