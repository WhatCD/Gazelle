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


$GroupID = $_POST['groupid'];
if (!is_number($GroupID)) {
	error(404);
}

if ($_POST['submit'] === 'Remove') {
	$DB->query("
		DELETE FROM collages_torrents
		WHERE CollageID = '$CollageID'
			AND GroupID = '$GroupID'");
	$Rows = $DB->affected_rows();
	$DB->query("
		UPDATE collages
		SET NumTorrents = NumTorrents - $Rows
		WHERE ID = '$CollageID'");
	$Cache->delete_value("torrents_details_$GroupID");
	$Cache->delete_value("torrent_collages_$GroupID");
	$Cache->delete_value("torrent_collages_personal_$GroupID");
} elseif (isset($_POST['drag_drop_collage_sort_order'])) {

	@parse_str($_POST['drag_drop_collage_sort_order'], $Series);
	$Series = @array_shift($Series);
	if (is_array($Series)) {
		$SQL = array();
		foreach ($Series as $Sort => $GroupID) {
			if (is_number($Sort) && is_number($GroupID)) {
				$Sort = ($Sort + 1) * 10;
				$SQL[] = sprintf('(%d, %d, %d)', $GroupID, $Sort, $CollageID);
			}
		}

		$SQL = '
			INSERT INTO collages_torrents
				(GroupID, Sort, CollageID)
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
		UPDATE collages_torrents
		SET Sort = '$Sort'
		WHERE CollageID = '$CollageID'
			AND GroupID = '$GroupID'");
}

$Cache->delete_value("collage_$CollageID");
header("Location: collages.php?action=manage&collageid=$CollageID");
