<?
if (!empty($_GET['userid']) && is_number($_GET['userid'])) {
	$UserID = $_GET['userid'];
} else {
	error(0);
}

if(!check_perms('zip_downloader')) {
	error(403);
}

$User = user_info($UserID);
$Perms = get_permissions($User['PermissionID']);
$UserClass = $Perms['Class'];

require(SERVER_ROOT.'/classes/class_torrent.php');
require(SERVER_ROOT.'/classes/class_zip.php');

if (empty($_GET['type'])) {
	error(0);
} else {
	
	switch ($_GET['type']) {
		case 'uploads':
			if(!check_paranoia('uploads', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
			$SQL = "WHERE t.UserID='$UserID'";
			$Month = "t.Time";
			break;
		case 'snatches':
			if(!check_paranoia('snatched', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
			$SQL = "JOIN xbt_snatched AS x ON t.ID=x.fid WHERE x.uid='$UserID'";
			$Month = "FROM_UNIXTIME(x.tstamp)";
			break;
		case 'seeding':
			if(!check_paranoia('seeding', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
			$SQL = "JOIN xbt_files_users AS xfu ON t.ID = xfu.fid WHERE xfu.uid='$UserID' AND xfu.remaining = 0";
			$Month = "FROM_UNIXTIME(xfu.mtime)";
			break;
		default:
			error(0);
	}
}

ZIP::unlimit();

$DB->query("SELECT 
	DATE_FORMAT(".$Month.",'%b \'%y') AS Month,
	t.GroupID,
	t.Media,
	t.Format,
	t.Encoding,
	IF(t.RemasterYear=0,tg.Year,t.RemasterYear),
	tg.Name,
	t.Size,
	f.File
	FROM torrents as t 
	JOIN torrents_group AS tg ON t.GroupID=tg.ID 
	LEFT JOIN torrents_files AS f ON t.ID=f.TorrentID
	".$SQL."
	GROUP BY t.ID");
$Downloads = $DB->to_array(false,MYSQLI_NUM,false);
$Artists = get_artists($DB->collect('GroupID'));

list($UserID, $Username) = array_values(user_info($UserID));
$Zip = new ZIP($Username.'\'s '.ucfirst($_GET['type']));
foreach($Downloads as $Download) {
	list($Month, $GroupID, $Media, $Format, $Encoding, $Year, $Album, $Size, $Contents) = $Download;
	$Artist = display_artists($Artists[$GroupID],false,true,false);
	$Contents = unserialize(base64_decode($Contents));
	$Tor = new TORRENT($Contents, true);
	$Tor->set_announce_url(ANNOUNCE_URL.'/'.$LoggedUser['torrent_pass'].'/announce');
	unset($Tor->Val['announce-list']);

	$TorrentName='';
	$TorrentInfo='';
	$TorrentName = $Artist;
	$TorrentName .= $Album;

	if ($Year>0) { $TorrentName.=' - '.$Year; }

	if ($Media!='') { $TorrentInfo.=$Media; }

	if ($Format!='') {
		if ($TorrentInfo!='') { $TorrentInfo.=' - '; }
		$TorrentInfo.=$Format;
	}

	if ($Encoding!='') {
		if ($TorrentInfo!='') { $TorrentInfo.=' - '; }
		$TorrentInfo.=$Encoding;
	}

	if ($TorrentInfo!='') { $TorrentName.=' ('.$TorrentInfo.')'; }

	if (!$TorrentName) { $TorrentName="No Name"; }

	$FileName = file_string($TorrentName);
	if ($Browser == 'Internet Explorer') {
		$FileName = urlencode($FileName);
	}
	$FileName .= '.torrent';
	$Zip->add_file($Tor->enc(), file_string($Month).'/'.$FileName);
}
$Zip->close_stream();
