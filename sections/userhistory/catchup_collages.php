<?
authorize();
$DB->query("UPDATE users_collage_subs SET LastVisit = NOW() WHERE UserID = ".$LoggedUser['ID']);
$Cache->delete_value('collage_subs_user_new_'.$LoggedUser['ID']);
header('Location: userhistory.php?action=subscribed_collages');
?>
