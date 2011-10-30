<?
authorize();
if ($_REQUEST['collageid'] && is_number($_REQUEST['collageid'])) {
	$Where = ' AND CollageID = '.$_REQUEST['collageid'];
} else {
	$Where = '';
}

$DB->query("UPDATE users_collage_subs SET LastVisit = NOW() WHERE UserID = ".$LoggedUser['ID'].$Where);
$Cache->delete_value('collage_subs_user_new_'.$LoggedUser['ID']);

header('Location: userhistory.php?action=subscribed_collages');
?>
