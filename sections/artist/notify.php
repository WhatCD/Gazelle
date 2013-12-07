<?
authorize();
if (!check_perms('site_torrents_notify')) {
	error(403);
}
$ArtistID = $_GET['artistid'];
if (!is_number($ArtistID)) {
	error(0);
}

$DB->query("
	SELECT GROUP_CONCAT(Name SEPARATOR '|')
	FROM artists_alias
	WHERE ArtistID = '$ArtistID'
		AND Redirect = 0
	GROUP BY ArtistID");
list($ArtistAliases) = $DB->next_record(MYSQLI_NUM, FALSE);

$Notify = $Cache->get_value('notify_artists_'.$LoggedUser['ID']);
if (empty($Notify)) {
	$DB->query("
		SELECT ID, Artists
		FROM users_notify_filters
		WHERE Label = 'Artist notifications'
			AND UserID = '$LoggedUser[ID]'
		ORDER BY ID
		LIMIT 1");
} else {
	$DB->query("
		SELECT ID, Artists
		FROM users_notify_filters
		WHERE ID = '$Notify[ID]'");
}
if (empty($Notify) && !$DB->has_results()) {
	$DB->query("
		INSERT INTO users_notify_filters
			(UserID, Label, Artists)
		VALUES
			('$LoggedUser[ID]', 'Artist notifications', '|".db_string($ArtistAliases)."|')");
	$FilterID = $DB->inserted_id();
	$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);
	$Cache->delete_value('notify_artists_'.$LoggedUser['ID']);
} else {
	list($ID, $ArtistNames) = $DB->next_record(MYSQLI_NUM, FALSE);
	if (stripos($ArtistNames, "|$ArtistAliases|") === false) {
		$ArtistNames .= "$ArtistAliases|";
		$DB->query("
			UPDATE users_notify_filters
			SET Artists = '".db_string($ArtistNames)."'
			WHERE ID = '$ID'");
		$Cache->delete_value('notify_filters_'.$LoggedUser['ID']);
		$Cache->delete_value('notify_artists_'.$LoggedUser['ID']);
	}
}
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
