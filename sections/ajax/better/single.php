<?
if (($GroupIDs = $Cache->get_value('better_single_groupids')) === false) {
	$DB->query("
		SELECT
			t.ID AS TorrentID,
			t.GroupID AS GroupID
		FROM xbt_files_users AS x
			JOIN torrents AS t ON t.ID = x.fid
		WHERE t.Format = 'FLAC'
		GROUP BY x.fid
		HAVING COUNT(x.uid) = 1
		ORDER BY t.LogScore DESC, t.Time ASC
		LIMIT 30");

	$GroupIDs = $DB->to_array('GroupID');
	$Cache->cache_value('better_single_groupids', $GroupIDs, 30 * 60);
}

$Results = Torrents::get_groups(array_keys($GroupIDs));

$JsonResults = array();
foreach ($Results as $GroupID => $Group) {
	extract(Torrents::array_group($Group));
	$FlacID = $GroupIDs[$GroupID]['TorrentID'];

	$JsonArtists = array();
	if (count($Artists) > 0) {
		foreach ($Artists as $Artist) {
			$JsonArtists[] = array(
				'id' => (int)$Artist['id'],
				'name' => $Artist['name'],
				'aliasId' => (int)$Artist['aliasid']
			);
		}
	}

	$JsonResults[] = array(
		'torrentId' => (int)$FlacID,
		'groupId' => (int)$GroupID,
		'artist' => $JsonArtists,
		'groupName' => $GroupName,
		'groupYear' => (int)$GroupYear,
		'downloadUrl' => "torrents.php?action=download&id=$FlacID&authkey=".$LoggedUser['AuthKey'].'&torrent_pass='.$LoggedUser['torrent_pass']
	);
}

print json_encode(
	array(
		'status' => 'success',
		'response' => $JsonResults
	)
);
