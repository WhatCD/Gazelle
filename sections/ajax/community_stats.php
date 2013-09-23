<?
if (!isset($_GET['userid']) || !is_number($_GET['userid'])) {
	json_die('failure');
}

$UserID = $_GET['userid'];
$CommStats = array(
	'leeching' => false,
	'seeding' => false,
	'snatched' => false,
	'usnatched' => false,
	'downloaded' => false,
	'udownloaded' => false,
	'seedingperc' => false,
);

$User = Users::user_info($UserID);

function check_paranoia_here($Setting) {
	global $User;
	return check_paranoia($Setting, $User['Paranoia'], $User['Class'], $User['ID']);
}

if (check_paranoia_here('seeding+') || check_paranoia_here('leeching+')) {
	$DB->query("
		SELECT IF(remaining = 0, 'Seeding', 'Leeching') AS Type, COUNT(x.uid)
		FROM xbt_files_users AS x
			INNER JOIN torrents AS t ON t.ID = x.fid
		WHERE x.uid = '$UserID'
			AND x.active = 1
		GROUP BY Type");
	$PeerCount = $DB->to_array(0, MYSQLI_NUM, false);
	if (check_paranoia('seeding+')) {
		$Seeding = isset($PeerCount['Seeding']) ? $PeerCount['Seeding'][1] : 0;
		$CommStats['seeding'] = number_format($Seeding);
	}
	if (check_paranoia('leeching+')) {
		$CommStats['leeching'] = isset($PeerCount['Leeching']) ? number_format($PeerCount['Leeching'][1]) : 0;
	}
}
if (check_paranoia_here('snatched+')) {
	$DB->query("
		SELECT COUNT(x.uid), COUNT(DISTINCT x.fid)
		FROM xbt_snatched AS x
			INNER JOIN torrents AS t ON t.ID = x.fid
		WHERE x.uid = '$UserID'");
	list($Snatched, $UniqueSnatched) = $DB->next_record(MYSQLI_NUM, false);
	$CommStats['snatched'] = number_format($Snatched);
	if (check_perms('site_view_torrent_snatchlist', $User['Class'])) {
		$CommStats['usnatched'] = number_format($UniqueSnatched);
	}
	if (check_paranoia_here('seeding+') && check_paranoia_here('snatched+') && $UniqueSnatched > 0) {
		$CommStats['seedingperc'] = 100 * min(1, round($Seeding / $UniqueSnatched, 2));
	}
}
if (check_perms('site_view_torrent_snatchlist', $Class)) {
	$DB->query("
		SELECT COUNT(ud.UserID), COUNT(DISTINCT ud.TorrentID)
		FROM users_downloads AS ud
			JOIN torrents AS t ON t.ID = ud.TorrentID
		WHERE ud.UserID = '$UserID'");
	list($NumDownloads, $UniqueDownloads) = $DB->next_record(MYSQLI_NUM, false);
	$CommStats['downloaded'] = number_format($NumDownloads);
	$CommStats['udownloaded'] = number_format($UniqueDownloads);
}

json_die('success', $CommStats);
