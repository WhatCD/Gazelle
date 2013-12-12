<?
/* AJAX Previews, simple stuff. */

if (!empty($_POST['message'])) {
	echo Text::full_format($_POST['message']);
}
