<?
/* AJAX Previews, simple stuff. */

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

if(!empty($_POST['message'])) {
	echo $Text->full_format($_POST['message']);
}
?>
