<?
if (!empty($_GET['userid']) && is_number($_GET['userid'])) {
	$UserID = $_GET['userid'];
} else {
	error(0);
}

if (!check_perms('zip_downloader')) {
	error(403);
}

$User = Users::user_info($UserID);
$Perms = Permissions::get_permissions($User['PermissionID']);
$UserClass = $Perms['Class'];
list($UserID, $Username) = array_values($User);

if (empty($_GET['type'])) {
	error(0);
} else {

	switch ($_GET['type']) {
		case 'uploads':
			if (!check_paranoia('uploads', $User['Paranoia'], $UserClass, $UserID)) {
				error(403);
			}
			$SQL = "WHERE t.UserID = '$UserID'";
			$Month = "t.Time";
			break;
		case 'snatches':
			if (!check_paranoia('snatched', $User['Paranoia'], $UserClass, $UserID)) {
				error(403);
			}
			$SQL = "
					JOIN xbt_snatched AS x ON t.ID = x.fid
				WHERE x.uid = '$UserID'";
			$Month = "FROM_UNIXTIME(x.tstamp)";
			break;
		case 'seeding':
			if (!check_paranoia('seeding', $User['Paranoia'], $UserClass, $UserID)) {
				error(403);
			}
			$SQL = "
					JOIN xbt_files_users AS xfu ON t.ID = xfu.fid
				WHERE xfu.uid = '$UserID'
					AND xfu.remaining = 0";
			$Month = "FROM_UNIXTIME(xfu.mtime)";
			break;
		default:
			error(0);
	}
}

$DownloadsQ = $DB->query("
	SELECT
		t.ID AS TorrentID,
		DATE_FORMAT($Month, '%Y - %m') AS Month,
		t.GroupID,
		t.Media,
		t.Format,
		t.Encoding,
		IF(t.RemasterYear = 0, tg.Year, t.RemasterYear) AS Year,
		tg.Name,
		t.Size
	FROM torrents AS t
		JOIN torrents_group AS tg ON t.GroupID = tg.ID
	$SQL
	GROUP BY TorrentID");

$Collector = new TorrentsDL($DownloadsQ, "$Username's ".ucfirst($_GET['type']));

while (list($Downloads, $GroupIDs) = $Collector->get_downloads('TorrentID')) {
	$Artists = Artists::get_artists($GroupIDs);
	$TorrentIDs = array_keys($GroupIDs);
	$TorrentFilesQ = $DB->query('
		SELECT TorrentID, File
		FROM torrents_files
		WHERE TorrentID IN ('.implode(',', $TorrentIDs).')', false);
	if (is_int($TorrentFilesQ)) {
		// Query failed. Let's not create a broken zip archive
		foreach ($TorrentIDs as $TorrentID) {
			$Download =& $Downloads[$TorrentID];
			$Download['Artist'] = Artists::display_artists($Artists[$Download['GroupID']], false, true, false);
			$Collector->fail_file($Download);
		}
		continue;
	}
	while (list($TorrentID, $TorrentFile) = $DB->next_record(MYSQLI_NUM, false)) {
		$Download =& $Downloads[$TorrentID];
		$Download['Artist'] = Artists::display_artists($Artists[$Download['GroupID']], false, true, false);
		$Collector->add_file($TorrentFile, $Download, $Download['Month']);
		unset($Download);
	}
}
$Collector->finalize(false);

define('SKIP_NO_CACHE_HEADERS', 1);
