<?
authorize();

if (!can_bookmark($_GET['type'])) { error(404); }

$Type = $_GET['type'];

list($Table, $Col) = bookmark_schema($Type);

if(!is_number($_GET['id'])) {
	error(0);
}

$DB->query("DELETE FROM $Table WHERE UserID='".$LoggedUser['ID']."' AND $Col='".db_string($_GET['id'])."'");
$Cache->delete_value('bookmarks_'.$Type.'_'.$UserID);
if ($Type == 'torrent') {
	$Cache->delete_value('bookmarks_torrent_'.$UserID.'_full');
} elseif ($Type == 'request') {
	$DB->query("SELECT UserID FROM $Table WHERE $Col='".db_string($_GET['id'])."'");
	$Bookmarkers = $DB->collect('UserID');
	$SS->UpdateAttributes('requests requests_delta', array('bookmarker'), array($_GET['id'] => array($Bookmarkers)), true);
}
?>
