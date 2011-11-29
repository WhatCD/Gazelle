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
if(!is_array($Info) || !array_key_exists('PlainArtists', $Info) || empty($Info[10])) {
	$DB->query("SELECT
		t.Media,
		t.Format,
		t.Encoding,
		IF(t.RemasterYear=0,tg.Year,t.RemasterYear),
		tg.ID AS GroupID,
		tg.Name,
		tg.WikiImage,
		tg.CategoryID,
		t.Size,
		t.FreeTorrent,
		t.info_hash
		FROM torrents AS t
		INNER JOIN torrents_group AS tg ON tg.ID=t.GroupID
		WHERE t.ID='".db_string($TorrentID)."'");
	if($DB->record_count() < 1) {
		header('Location: log.php?search='.$TorrentID);
		die();
	}
	$Info = array($DB->next_record(MYSQLI_NUM, array(4,5,6,10)));
	$Artists = get_artist($Info[0][4],false);
	$Info['Artists'] = display_artists($Artists, false, true);
	$Info['PlainArtists'] = display_artists($Artists, false, true, false);
	$Cache->cache_value('torrent_download_'.$TorrentID, $Info, 0);
}
if(!is_array($Info[0])) {
	error(404);
}
list($Media,$Format,$Encoding,$Year,$GroupID,$Name,$Image, $CategoryID, $Size, $FreeTorrent, $InfoHash) = array_shift($Info); // used for generating the filename
$Artists = $Info['Artists'];

// If he's trying use a token on this, we need to make sure he has one,
// deduct it, add this to the FLs table, and update his cache key.
if ($_REQUEST['usetoken'] && $FreeTorrent == '0') {
	if (isset($LoggedUser)) {
		$FLTokens = $LoggedUser['FLTokens'];
		if ($LoggedUser['CanLeech'] != '1') {
			error('You cannot use tokens while leech disabled.');
		}
	}
	else {
		$UInfo = user_heavy_info($UserID);
		if ($UInfo['CanLeech'] != '1') {
			error('You may not use tokens while leech disabled.');
		}
		$FLTokens = $UInfo['FLTokens'];
	}
	
	// First make sure this isn't already FL, and if it is, do nothing
	$TokenTorrents = $Cache->get_value('users_tokens_'.$UserID);
	if (empty($TokenTorrents)) {
		$DB->query("SELECT TorrentID FROM users_freeleeches WHERE UserID=$UserID AND Expired=FALSE");
		$TokenTorrents = $DB->collect('TorrentID');
	}
	
	if (!in_array($TorrentID, $TokenTorrents)) {
		if ($FLTokens <= 0) {
			error("You do not have any freeleech tokens left.  Please use the regular DL link.");
		}
		if ($Size >= 1073741824) {
			error("This torrent is too large.  Please use the regular DL link.");
		}
		
		// Let the tracker know about this
		if (!update_tracker('add_token', array('info_hash' => rawurlencode($InfoHash), 'userid' => $UserID))) {
			error("Sorry! An error occurred while trying to register your token. Most often, this is due to the tracker being down or under heavy load. Please try again later.");
		}
		
		// We need to fetch and check this again here because of people 
		// double-clicking the FL link while waiting for a tracker response.
		$TokenTorrents = $Cache->get_value('users_tokens_'.$UserID);
		if (empty($TokenTorrents)) {
			$DB->query("SELECT TorrentID FROM users_freeleeches WHERE UserID=$UserID AND Expired=FALSE");
			$TokenTorrents = $DB->collect('TorrentID');
		}
		
		if (!in_array($TorrentID, $TokenTorrents)) {
			$DB->query("INSERT INTO users_freeleeches (UserID, TorrentID, Time) VALUES ($UserID, $TorrentID, NOW())
							ON DUPLICATE KEY UPDATE Time=VALUES(Time), Expired=FALSE, Uses=Uses+1");
			$DB->query("UPDATE users_main SET FLTokens = FLTokens - 1 WHERE ID=$UserID");
			
			// Fix for downloadthemall messing with the cached token count
			$UInfo = user_heavy_info($UserID);
			$FLTokens = $UInfo['FLTokens'];
			
			$Cache->begin_transaction('user_info_heavy_'.$UserID);
			$Cache->update_row(false, array('FLTokens'=>($FLTokens - 1)));
			$Cache->commit_transaction(0);
			
			$TokenTorrents[] = $TorrentID;
			$Cache->cache_value('users_tokens_'.$UserID, $TokenTorrents);
		}
	}
}

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

$TorrentName = $Info['PlainArtists'];

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

// Let's try to shorten the filename intelligently before chopping it off
if (strlen($TorrentName) + strlen($TorrentInfo) + 3 > 200) {
	$TorrentName = $Name . (($Year>0)?(' - '.$Year):'');
}

if ($TorrentInfo!='') { $TorrentName.=' ('.$TorrentInfo.')'; }

if(!empty($_GET['mode']) && $_GET['mode'] == 'bbb'){
	$TorrentName = $Artists.' -- '.$Name;
}

if (!$TorrentName) { $TorrentName="No Name"; }

$FileName = ($Browser == 'Internet Explorer') ? urlencode(file_string($TorrentName)) : file_string($TorrentName);
$MaxLength = $DownloadAlt ? 192 : 196;
$FileName = cut_string($FileName, $MaxLength, true, false);
$FileName = $DownloadAlt ? $FileName.'.txt' : $FileName.'.torrent';


if($DownloadAlt) {
	header('Content-Type: text/plain; charset=utf-8');
} elseif(!$DownloadAlt || $Failed) {
	header('Content-Type: application/x-bittorrent; charset=utf-8');
}
header('Content-disposition: attachment; filename="'.$FileName.'"');

echo $Tor->enc();
