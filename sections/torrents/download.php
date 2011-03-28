<?
if (!isset($_REQUEST['authkey']) || !isset($_REQUEST['torrent_pass'])) {
	enforce_login();
	$TorrentPass = $LoggedUser['torrent_pass'];
	$DownloadAlt = $LoggedUser['DownloadAlt'];
} else {
	$UserInfo = $Cache->get_value('user_'.$_REQUEST['torrent_pass']);
	if(!is_array($UserInfo)) {
		$DB->query("SELECT 
			ID,
			DownloadAlt
			FROM users_main AS m 
			INNER JOIN users_info AS i ON i.UserID=m.ID 
			WHERE m.torrent_pass='".db_string($_REQUEST['torrent_pass'])."' 
			AND m.Enabled='1'");
		$UserInfo = $DB->next_record();
		$Cache->cache_value('user_'.$_REQUEST['torrent_pass'], $UserInfo, 3600);
	}
	$UserInfo = array($UserInfo);
	list($UserID,$DownloadAlt)=array_shift($UserInfo);
	if(!$UserID) { error(403); }
	$TorrentPass = $_REQUEST['torrent_pass'];
}
require(SERVER_ROOT.'/classes/class_torrent.php');

$TorrentID = $_REQUEST['id'];

if (!is_number($TorrentID)){ error(0); }

$Info = $Cache->get_value('torrent_download_'.$TorrentID);
if(!is_array($Info)) {
	$DB->query("SELECT
		t.Media,
		t.Format,
		t.Encoding,
		IF(t.RemasterYear=0,tg.Year,t.RemasterYear),
		tg.ID AS GroupID,
		tg.Name,
		tg.WikiImage,
		tg.CategoryID
		FROM torrents AS t
		INNER JOIN torrents_group AS tg ON tg.ID=t.GroupID
		WHERE t.ID='".db_string($TorrentID)."'");
	if($DB->record_count() < 1) {
		header('Location: log.php?search='.$TorrentID);
		die();
	}
	$Info = array($DB->next_record(MYSQLI_NUM, array(4,5,6)));
	$Info['Artists'] = display_artists(get_artist($Info[0][4],false), false, true);
	$Cache->cache_value('torrent_download_'.$TorrentID, $Info, 0);
}
if(!is_array($Info[0])) {
	error(404);
}
list($Media,$Format,$Encoding,$Year,$GroupID,$Name,$Image, $CategoryID) = array_shift($Info); // used for generating the filename
$Artists = $Info['Artists'];

//Stupid Recent Snatches On User Page
if($CategoryID == '1' && $Image != "") {
	$RecentSnatches = $Cache->get_value('recent_snatches_'.$UserID);
	if(!empty($RecentSnatches)) {
		$Snatch = array('ID'=>$GroupID,'Name'=>$Name,'Artist'=>$Artists,'WikiImage'=>$Image);
		if(!in_array($Snatch, $RecentSnatches)) {
			if(count($RecentSnatches) == 5) {
				array_pop($RecentSnatches);
			}
			array_unshift($RecentSnatches, $Snatch);
		} elseif(!is_array($RecentSnatches)) {
			$RecentSnatches = array($Snatch);
		}
		$Cache->cache_value('recent_snatches_'.$UserID, $RecentSnatches, 0);
	}
}

$DB->query("INSERT INTO users_downloads (UserID, TorrentID, Time) VALUES ('$UserID', '$TorrentID', '".sqltime()."') ON DUPLICATE KEY UPDATE Time=VALUES(Time)");


$DB->query("SELECT File FROM torrents_files WHERE TorrentID='$TorrentID'");

list($Contents) = $DB->next_record(MYSQLI_NUM, array(0));
$Contents = unserialize(base64_decode($Contents));
$Tor = new TORRENT($Contents, true); // New TORRENT object
// Set torrent announce URL
$Tor->set_announce_url(ANNOUNCE_URL.'/'.$TorrentPass.'/announce');
// Remove multiple trackers from torrent
unset($Tor->Val['announce-list']);
// Remove web seeds (put here for old torrents not caught by previous commit
unset($Tor->Val['url-list']);
// Remove libtorrent resume info
unset($Tor->Val['libtorrent_resume']);
// Torrent name takes the format of Artist - Album - YYYY (Media - Format - Encoding)

$TorrentName='';
$TorrentInfo='';

$TorrentName = $Artists;

$TorrentName.=$Name;

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

if(!empty($_GET['mode']) && $_GET['mode'] == 'bbb'){
	$TorrentName = $Artists.' -- '.$Name;
}

if (!$TorrentName) { $TorrentName="No Name"; }

$FileName = ($Browser == 'Internet Explorer') ? urlencode(file_string($TorrentName)) : file_string($TorrentName);
$MaxLength = $DownloadAlt ? 213 : 209;
$FileName = cut_string($FileName, $MaxLength, true, false);
$FileName = $DownloadAlt ? $FileName.'.txt' : $FileName.'.torrent';


if($DownloadAlt) {
	header('Content-Type: text/plain; charset=utf-8');
} elseif(!$DownloadAlt || $Failed) {
	header('Content-Type: application/x-bittorrent; charset=utf-8');
}
header('Content-disposition: attachment; filename="'.$FileName.'"');

echo $Tor->enc();
