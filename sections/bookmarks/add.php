<?
authorize();
if(!is_number($_GET['groupid'])) {
	error(0);
}
$DB->query("SELECT GroupID FROM bookmarks_torrents WHERE UserID='$LoggedUser[ID]' AND GroupID='".db_string($_GET['groupid'])."'");
if($DB->record_count() == 0) {
	$DB->query("INSERT IGNORE INTO bookmarks_torrents 
		(UserID, GroupID, Time) 
		VALUES 
		('$LoggedUser[ID]', '".db_string($_GET['groupid'])."', '".sqltime()."')");
	$Cache->delete_value('bookmarks_'.$LoggedUser['ID']);
	$Cache->delete_value('bookmarks_'.$LoggedUser['ID'].'_groups');
}
?>
