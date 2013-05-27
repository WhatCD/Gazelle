<?
authorize();
if (!check_perms('site_edit_wiki')) {
	error(403);
}

$ID = $_GET['id'];

if (!is_number($ID) || !is_number($ID)) {
	error(404);
}

$DB->query("DELETE FROM cover_art WHERE ID = '$ID'");

$Cache->delete_value('torrents_cover_art_' . $GroupID);
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
