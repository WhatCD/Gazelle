<?
$UserID = (int)$_GET['userid'];
$Limit = (int)$_GET['limit'];

if (empty($UserID) || $Limit > 50) {
	json_die("failure", "bad parameters");
}
if (empty($Limit)) {
	$Limit = 15;
}
$Results = array();
if (check_paranoia_here('snatched')) {
	$DB->query("
		SELECT
			g.ID,
			g.Name,
			g.WikiImage
		FROM xbt_snatched AS s
			INNER JOIN torrents AS t ON t.ID = s.fid
			INNER JOIN torrents_group AS g ON t.GroupID = g.ID
		WHERE s.uid = '$UserID'
			AND g.CategoryID = '1'
			AND g.WikiImage != ''
		GROUP BY g.ID
		ORDER BY s.tstamp DESC
		LIMIT $Limit");
	$RecentSnatches = $DB->to_array(false, MYSQLI_ASSOC);
	$Artists = Artists::get_artists($DB->collect('ID'));
	foreach ($RecentSnatches as $Key => $SnatchInfo) {
		$RecentSnatches[$Key]['artists'][] = $Artists[$SnatchInfo['ID']];
		$RecentSnatches[$Key]['ID'] = (int)$RecentSnatches[$Key]['ID'];

	}
	$Results['snatches'] = $RecentSnatches;
} else {
	$Results['snatches'] = "hidden";
}

if (check_paranoia_here('uploads')) {
	$DB->query("
		SELECT
			g.ID,
			g.Name,
			g.WikiImage
		FROM torrents_group AS g
			INNER JOIN torrents AS t ON t.GroupID = g.ID
		WHERE t.UserID = '$UserID'
			AND g.CategoryID = '1'
			AND g.WikiImage != ''
		GROUP BY g.ID
		ORDER BY t.Time DESC
		LIMIT $Limit");
	$RecentUploads = $DB->to_array(false, MYSQLI_ASSOC);
	$Artists = Artists::get_artists($DB->collect('ID'));
	foreach ($RecentUploads as $Key => $UploadInfo) {
		$RecentUploads[$Key]['artists'][] = $Artists[$UploadInfo['ID']];
		$RecentUploads[$Key]['ID'] = (int)$RecentUploads[$Key]['ID'];

	}
	$Results['uploads'] = $RecentUploads;
} else {
	$Results['uploads'] = "hidden";
}

json_print("success", $Results);

function check_paranoia_here($Setting) {
	global $Paranoia, $Class, $UserID, $Preview;
	if ($Preview == 1) {
		return check_paranoia($Setting, $Paranoia, $Class);
	} else {
		return check_paranoia($Setting, $Paranoia, $Class, $UserID);
	}
}
