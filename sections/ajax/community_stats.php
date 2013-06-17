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
		SELECT IF(remaining=0,'Seeding','Leeching') AS Type, COUNT(x.uid)
		FROM xbt_files_users AS x
			INNER JOIN torrents AS t ON t.ID=x.fid
		WHERE x.uid='$UserID'
			AND x.active=1
		GROUP BY Type");
	$PeerCount = $DB->to_array(0, MYSQLI_NUM, false);
	if (check_paranoia('seeding+')) {
		$CommStats['seeding'] = isset($PeerCount['Seeding']) ? $PeerCount['Seeding'][1] : 0;
	}
	if (check_paranoia('leeching+')) {
		$CommStats['leeching'] = isset($PeerCount['Leeching']) ? $PeerCount['Leeching'][1] : 0;
	}
}
if (check_paranoia_here('snatched+')) {
	$DB->query("
		SELECT COUNT(x.uid), COUNT(DISTINCT x.fid)
		FROM xbt_snatched AS x
			INNER JOIN torrents AS t ON t.ID=x.fid
		WHERE x.uid = '$UserID'");
	list($Snatched, $UniqueSnatched) = $DB->next_record(MYSQLI_NUM, false);
	$CommStats['snatched'] = $Snatched;
	if (check_perms('site_view_torrent_snatchlist', $User['Class'])) {
		$CommStats['usnatched'] = $UniqueSnatched;
	}
	if (check_paranoia_here('seeding') && check_paranoia_here('snatched')) {
		$CommStats['seedingperc'] = $UniqueSnatched > 0 ? 100 * min(1, round($CommStats['seeding'] / $UniqueSnatched, 2)) : -1;
	}
}
if (check_perms('site_view_torrent_snatchlist', $Class)) {
	$DB->query("
		SELECT COUNT(ud.UserID), COUNT(DISTINCT ud.TorrentID)
		FROM users_downloads AS ud
			JOIN torrents AS t ON t.ID=ud.TorrentID
		WHERE ud.UserID='$UserID'");
	list($NumDownloads, $UniqueDownloads) = $DB->next_record(MYSQLI_NUM, false);
	$CommStats['downloaded'] = $NumDownloads;
	$CommStats['udownloaded'] = $UniqueDownloads;
}

$CommStats['leeching'] = number_format($CommStats['leeching']);
$CommStats['seeding'] = number_format($CommStats['seeding']);
$CommStats['snatched'] = number_format($CommStats['snatched']);
$CommStats['usnatched'] = number_format($CommStats['usnatched']);
$CommStats['downloaded'] = number_format($CommStats['downloaded']);
$CommStats['udownloaded'] = number_format($CommStats['udownloaded']);
$CommStats['seedingperc'] = number_format($CommStats['seedingperc']);

json_die('success', $CommStats);
