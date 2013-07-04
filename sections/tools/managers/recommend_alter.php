<?
//******************************************************************************//
//--------------- Delete a recommendation --------------------------------------//

if (!check_perms('site_recommend_own') && !check_perms('site_manage_recommendations')) {
	error(403);
}

$GroupIDi = $_GET['groupid'];
if (!$GroupID || !is_number($GroupID)) {
	error(404);
}

if (!check_perms('site_manage_recommendations')) {
	$DB->query("
		SELECT UserID
		FROM torrents_recommended
		WHERE GroupID = '$GroupID'");
	list($UserID) = $DB->next_record();
	if ($UserID != $LoggedUser['ID']) {
		error(403);
	}
}

$DB->query("
	DELETE FROM torrents_recommended
	WHERE GroupID = '$GroupID'");

$Cache->delete_value('recommend');
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
