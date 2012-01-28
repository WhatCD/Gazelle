<?
if(!isset($_GET['type']) || !is_number($_GET['type']) || $_GET['type'] > 3) { error(0); }

$Options = array('v0','v2','320');

if ($_GET['type'] == 3) {
	$List = "!(v0 | v2 | 320)";
} else {
	$List = '!'.$Options[$_GET['type']];
	if($_GET['type'] == 0) {
		$_GET['type'] = '0';
	} else {
		$_GET['type'] = display_str($_GET['type']);
	}
}

$Query = '@format FLAC @encoding '.$List;

if(!empty($_GET['search'])) {
	$Query.=' @(groupname,artistname,yearfulltext) '.$SS->EscapeString($_GET['search']);
}

$SS->SetFilter('logscore', array(100));
$SS->SetSortMode(SPH_SORT_EXTENDED, "@random");
$SS->limit(0, TORRENTS_PER_PAGE);

$SS->set_index(SPHINX_INDEX.' delta');

$Results = $SS->search($Query, '', 0, array(), '', '');

if(count($Results) == 0) { error('No results found!'); }
/*
// If some were fetched from memcached, get their artists
if(!empty($Results['matches'])) { // Fetch the artists for groups
	$GroupIDs = array_keys($Results['matches']);
	$Artists = get_artists($GroupIDs);
	foreach($Artists as $GroupID=>$Data) {
		if(!empty($Data[1])) {
			$Results['matches'][$GroupID]['Artists']=$Data[1]; // Only use main artists
		}
		ksort($Results['matches'][$GroupID]);
	}
}
*/
 // These ones were not found in the cache, run SQL
if(!empty($Results['notfound'])) {
	$SQLResults = get_groups($Results['notfound']);
	
	if(is_array($SQLResults['notfound'])) { // Something wasn't found in the db, remove it from results
		reset($SQLResults['notfound']);
		foreach($SQLResults['notfound'] as $ID) {
			unset($SQLResults['matches'][$ID]);
			unset($Results['matches'][$ID]);
		}
	}
	
	// Merge SQL results with memcached results
	foreach($SQLResults['matches'] as $ID=>$SQLResult) {
		$Results['matches'][$ID] = array_merge($Results['matches'][$ID], $SQLResult);
		ksort($Results['matches'][$ID]);
	}
}

$Results = $Results['matches'];

$JsonResults = array();
foreach($Results as $GroupID=>$Data) {
$Debug->log_var($Data);
	list($Artists, $GroupCatalogueNumber, $ExtendedArtists, $GroupID2, $GroupName, $GroupRecordLabel, $ReleaseType, $TagList, $Torrents, $GroupVanityHouse, $GroupYear, $CategoryID, $FreeTorrent, $HasCue, $HasLog, $TotalLeechers, $LogScore, $ReleaseType, $ReleaseType, $TotalSeeders, $MaxSize, $TotalSnatched, $GroupTime) = array_values($Data);
	
	$DisplayName = '';
	if(count($Artists)>0) {
		$DisplayName = display_artists(array('1'=>$Artists));
	}
	$DisplayName.='<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
	
	$MissingEncodings = array('V0 (VBR)'=>1, 'V2 (VBR)'=>1, '320'=>1);
	$FlacID = 0;
	
	foreach($Torrents as $Torrent) {
		if(!empty($MissingEncodings[$Torrent['Encoding']])) {
			$MissingEncodings[$Torrent['Encoding']] = 0;
		} elseif($Torrent['Format'] == 'FLAC' && $FlacID == 0) {
			$FlacID = $Torrent['ID'];
		}
	}
	
	if($_GET['type'] == '3' && in_array(0, $MissingEncodings)) {
		continue;
	}

	$JsonResults[] = array(
		'torrentId' => (int) $TorrentID,
		'groupId' => (int) $GroupID,
		'artist' => $DisplayName,
		'groupName' => $GroupName,
		'groupYear' => (int) $GroupYear,
		'missingV2' => $MissingEncodings['V2 (VBR)'] == 0,
		'missingV0' => $MissingEncodings['V0 (VBR)'] == 0,
		'missing320' => $MissingEncodings['320'] == 0,
		'downloadUrl' => 'torrents.php?action=download&id='.$FlacID.'&authkey='.$LoggedUser['AuthKey'].'&torrent_pass='.$LoggedUser['torrent_pass']
	);
}

print json_encode(
	array(
		'status' => 'success',
		'response' => $JsonResults
	)
);
