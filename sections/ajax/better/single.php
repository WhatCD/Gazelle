<?
if(($GroupIDs = $Cache->get_value('better_single_groupids')) === false) {
	$DB->query("SELECT t.ID AS TorrentID,
	       	t.GroupID AS GroupID
		FROM xbt_files_users AS x
			JOIN torrents AS t ON t.ID=x.fid
		WHERE t.Format='FLAC'
		GROUP BY x.fid
			HAVING COUNT(x.uid) = 1
		ORDER BY t.LogScore DESC, t.Time ASC LIMIT 30");

	$GroupIDs = $DB->to_array('GroupID');
	$Cache->cache_value('better_single_groupids', $GroupIDs, 30*60);
}

$Results = get_groups(array_keys($GroupIDs));

$Results = $Results['matches'];

$JsonResults = array();
foreach ($Results as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TagList, $ReleaseType, $GroupVanityHouse, $Torrents, $Artists) = array_values($Group);
	$FlacID = $GroupIDs[$GroupID]['TorrentID'];
	
	$DisplayName = '';
	if(count($Artists)>0) {
		$DisplayName = display_artists(array('1'=>$Artists));
	}
	$DisplayName.='<a href="torrents.php?id='.$GroupID.'&amp;torrentid='.$FlacID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
	
	$JsonResults[] = array(
		'torrentId' => (int) $FlacID,
		'groupId' => (int) $GroupID,
		'artist' => $Artists,
		'groupName' => $GroupName,
		'groupYear' => (int) $GroupYear,
		'downloadUrl' => 'torrents.php?action=download&id='.$FlacID.'&authkey='.$LoggedUser['AuthKey'].'&torrent_pass='.$LoggedUser['torrent_pass']
	);
}

print json_encode(
	array(
		'status' => 'success',
		'response' => $JsonResults
	)
);
