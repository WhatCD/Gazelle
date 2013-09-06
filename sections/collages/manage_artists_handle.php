<?php

authorize();

$CollageID = $_POST['collageid'];
if (!is_number($CollageID)) {
	error(404);
}

$DB->query("
	SELECT UserID, CategoryID
	FROM collages
	WHERE ID = '$CollageID'");
list($UserID, $CategoryID) = $DB->next_record();
if ($CategoryID === '0' && $UserID !== $LoggedUser['ID'] && !check_perms('site_collages_delete')) {
	error(403);
}
if ($CategoryID !== array_search(ARTIST_COLLAGE, $CollageCats)) {
	error(403);
}

$ArtistID = $_POST['artistid'];
if (!is_number($ArtistID)) {
	error(404);
}

if ($_POST['submit'] === 'Remove') {
	$DB->query("
		DELETE FROM collages_artists
		WHERE CollageID = '$CollageID'
			AND ArtistID = '$ArtistID'");
	$Rows = $DB->affected_rows();
	$DB->query("
		UPDATE collages
		SET NumTorrents = NumTorrents - $Rows
		WHERE ID = '$CollageID'");
	$Cache->delete_value("artists_collages_$ArtistID");
	$Cache->delete_value("artists_collages_personal_$ArtistID");
} elseif (isset($_POST['drag_drop_collage_sort_order'])) {

	@parse_str($_POST['drag_drop_collage_sort_order'], $Series);
	$Series = @array_shift($Series);
	if (is_array($Series)) {
		$SQL = array();
		foreach ($Series as $Sort => $ArtistID) {
			if (is_number($Sort) && is_number($ArtistID)) {
				$Sort = ($Sort + 1) * 10;
				$SQL[] = sprintf('(%d, %d, %d)', $ArtistID, $Sort, $CollageID);
			}
		}

		$SQL = '
			INSERT INTO collages_artists
				(ArtistID, Sort, CollageID)
			VALUES
				' . implode(', ', $SQL) . '
			ON DUPLICATE KEY UPDATE
				Sort = VALUES (Sort)';

		$DB->query($SQL);
	}

} else {
	$Sort = $_POST['sort'];
	if (!is_number($Sort)) {
		error(404);
	}
	$DB->query("
		UPDATE collages_artists
		SET Sort = '$Sort'
		WHERE CollageID = '$CollageID'
			AND ArtistID = '$ArtistID'");
}

$Cache->delete_value("collage_$CollageID");
header("Location: collages.php?action=manage_artists&collageid=$CollageID");
