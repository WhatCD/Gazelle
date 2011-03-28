<?
authorize();

$CollageID = $_POST['collageid'];
if(!is_number($CollageID)) { error(404); }

$DB->query("SELECT UserID, CategoryID FROM collages WHERE ID='$CollageID'");
list($UserID, $CategoryID) = $DB->next_record();
if($CategoryID == 0 && $UserID!=$LoggedUser['ID'] && !check_perms('site_collages_delete')) { error(403); }

$GroupID = $_POST['groupid'];
if(!is_number($GroupID)) { error(404); }


if($_POST['submit'] == 'Remove') {
	$DB->query("DELETE FROM collages_torrents WHERE CollageID='$CollageID' AND GroupID='$GroupID'");
	$DB->query("UPDATE collages SET NumTorrents=NumTorrents-1 WHERE ID='$CollageID'");
	$Cache->delete_value('torrents_details_'.$GroupID);
	$Cache->delete_value('torrent_collages_'.$GroupID);
} else {
	$Sort = $_POST['sort'];
	if(!is_number($Sort)) { error(404); }
	$DB->query("UPDATE collages_torrents SET Sort='$Sort' WHERE CollageID='$CollageID' AND GroupID='$GroupID'");
}
$Cache->delete_value('collage_'.$CollageID);
header('Location: collages.php?action=manage&collageid='.$CollageID);
?>
