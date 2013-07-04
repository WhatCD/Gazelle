<?
/* AJAX Previews, simple stuff. */

include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class
$Text = new TEXT(true);
if (!empty($_POST['AdminComment'])) {
	echo $Text->full_format($_POST['AdminComment']);
} else {
	$Content = $_REQUEST['body']; // Don't use URL decode.
	echo $Text->full_format($Content);
}

