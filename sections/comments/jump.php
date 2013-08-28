<?
if (!isset($_REQUEST['postid']) || !is_number($_REQUEST['postid'])) {
	error(0);
}

$URL = Comments::get_url_query((int)$_REQUEST['postid']);
if (!$URL) {
	error(0);
}
header("Location: $URL");
die();
