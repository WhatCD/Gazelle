<?
ini_set('memory_limit', -1);
//~~~~~~~~~~~ Main bookmarks page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//


function compare($X, $Y){
	return($Y['count'] - $X['count']);
}

if(!empty($_GET['userid'])) {
	if(!check_perms('users_override_paranoia')) {
		error(403);
	}
	$UserID = $_GET['userid'];
	if(!is_number($UserID)) { error(404); }
	$DB->query("SELECT Username FROM users_main WHERE ID='$UserID'");
	list($Username) = $DB->next_record();
} else {
	$UserID = $LoggedUser['ID'];
}

$Sneaky = ($UserID != $LoggedUser['ID']);

$Data = $Cache->get_value('bookmarks_torrent_'.$UserID.'_full');

if($Data) {
	$Data = unserialize($Data);
	list($K, list($TorrentList, $CollageDataList)) = each($Data);
} else {
	// Build the data for the collage and the torrent list
	$DB->query("SELECT 
		bt.GroupID, 
		tg.WikiImage,
		tg.CategoryID,
		bt.Time
		FROM bookmarks_torrents AS bt
		JOIN torrents_group AS tg ON tg.ID=bt.GroupID
		WHERE bt.UserID='$UserID'
		ORDER BY bt.Time");
	
	$GroupIDs = $DB->collect('GroupID');
	$CollageDataList=$DB->to_array('GroupID', MYSQLI_ASSOC);
	if(count($GroupIDs)>0) {
		$TorrentList = Torrents::get_groups($GroupIDs);
		$TorrentList = $TorrentList['matches'];
	} else {
		$TorrentList = array();
	}
}

$JsonBookmarks = array();
foreach ($TorrentList as $Torrent) {
	$JsonTorrents = array();
	foreach ($Torrent['Torrents'] as $GroupTorrents) {
		$JsonTorrents[] = array(
			'id' => (int) $GroupTorrents['ID'],
			'groupId' => (int) $GroupTorrents['GroupID'],
			'media' => $GroupTorrents['Media'],
			'format' => $GroupTorrents['Format'],
			'encoding' => $GroupTorrents['Encoding'],
			'remasterYear' => (int) $GroupTorrents['RemasterYear'],
			'remastered' => $GroupTorrents['Remastered'] == 1,
			'remasterTitle' => $GroupTorrents['RemasterTitle'],
			'remasterRecordLabel' => $GroupTorrents['RemasterRecordLabel'],
			'remasterCatalogueNumber' => $GroupTorrents['RemasterCatalogueNumber'],
			'scene' => $GroupTorrents['Scene'] == 1,
			'hasLog' => $GroupTorrents['HasLog'] == 1,
			'hasCue' => $GroupTorrents['HasCue'] == 1,
			'logScore' => (float) $GroupTorrents['LogScore'],
			'fileCount' => (int) $GroupTorrents['FileCount'],
			'freeTorrent' => $GroupTorrents['FreeTorrent'] == 1,
			'size' => (float) $GroupTorrents['Size'],
			'leechers' => (int) $GroupTorrents['Leechers'],
			'seeders' => (int) $GroupTorrents['Seeders'],
			'snatched' => (int) $GroupTorrents['Snatched'],
			'time' => $GroupTorrents['Time'],
			'hasFile' => (int) $GroupTorrents['HasFile']
		);
	}
	$JsonBookmarks[] = array(
		'id' => (int) $Torrent['ID'],
		'name' => $Torrent['Name'],
		'year' => (int) $Torrent['Year'],
		'recordLabel' => $Torrent['RecordLabel'],
		'catalogueNumber' => $Torrent['CatalogueNumber'],
		'tagList' => $Torrent['TagList'],
		'releaseType' => $Torrent['ReleaseType'],
		'vanityHouse' => $Torrent['VanityHouse'] == 1,
		'image' => $CollageDataList[$Torrent['ID']]['WikiImage'],
		'torrents' => $JsonTorrents
	);
}

print
	json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'bookmarks' => $JsonBookmarks
			)
		)
	);
?>
