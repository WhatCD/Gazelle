<?
// perform the back end of subscribing to collages
authorize();

if(!is_number($_GET['collageid'])) {
	error(0);
}

if(!$UserSubscriptions = $Cache->get_value('collage_subs_user_'.$LoggedUser['ID'])) {
	$DB->query('SELECT CollageID FROM users_collage_subs WHERE UserID = '.db_string($LoggedUser['ID']));
	$UserSubscriptions = $DB->collect(0);
	$Cache->cache_value('collage_subs_user_'.$LoggedUser['ID'],$UserSubscriptions,0);
}

if(($Key = array_search($_GET['collageid'],$UserSubscriptions)) !== FALSE) {
	$DB->query('DELETE FROM users_collage_subs WHERE UserID = '.db_string($LoggedUser['ID']).' AND CollageID = '.db_string($_GET['collageid']));
	unset($UserSubscriptions[$Key]);
} else {
	$DB->query("INSERT IGNORE INTO users_collage_subs (UserID, CollageID, LastVisit) VALUES ($LoggedUser[ID], ".db_string($_GET['collageid']).", NOW())");
	array_push($UserSubscriptions, $_GET['collageid']);
}
$Cache->replace_value('collage_subs_user_'.$LoggedUser['ID'], $UserSubscriptions, 0);
$Cache->delete_value('collage_subs_user_new_'.$LoggedUser['ID']);
