<?
/* AJAX Previews, simple stuff. */
Text::$TOC = true;
if (!empty($_POST['AdminComment'])) {
	echo Text::full_format($_POST['AdminComment']);
} else {
	$Content = $_REQUEST['body']; // Don't use URL decode.
	echo Text::full_format($Content);
}

