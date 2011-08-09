<?
authorize();

if (!can_bookmark($_GET['type'])) { error(404); }

$Type = $_GET['type'];

list($Table, $Col) = bookmark_schema($Type);

if(!is_number($_GET['id'])) {
	error(0);
}

$DB->query("SELECT UserID FROM $Table WHERE UserID='$LoggedUser[ID]' AND $Col='".db_string($_GET['id'])."'");
if($DB->record_count() == 0) {
	$DB->query("INSERT IGNORE INTO $Table 
		(UserID, $Col, Time) 
		VALUES 
		('$LoggedUser[ID]', '".db_string($_GET['id'])."', '".sqltime()."')");
	$Cache->delete_value('bookmarks_'.$Type.'_'.$LoggedUser['ID']);
	if ($Type == 'torrent') {
		$Cache->delete_value('bookmarks_torrent_'.$LoggedUser['ID'].'_full');
	} elseif ($Type == 'request') {
		$DB->query("SELECT UserID FROM $Table WHERE $Col='".db_string($_GET['id'])."'");
		$Bookmarkers = $DB->collect('UserID');
		$Bookmarkers = array(1);
		$SS->UpdateAttributes('requests', array('bookmarker'), array($_GET['id'] => array($Bookmarkers)), true);
	}
}
?>
