<?

authorize();

if(isset($_GET['details'])) {
	if(in_array($_GET['details'], array('day','week','overall','snatched','data','seeded'))) {
		$Details = $_GET['details'];
	} else {
		print json_encode(array('status' => 'failure'));
		die();
	}
} else {
	$Details = 'all';
}

// defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, array(10, 100, 250)) ? $Limit : 10;

$WhereSum = (empty($Where)) ? '' : md5($Where);
$BaseQuery = "SELECT
	t.ID,
	g.ID,
	g.Name,
	g.CategoryID,
	g.TagList,
	t.Format,
	t.Encoding,
	t.Media,
	t.Scene,
	t.HasLog,
	t.HasCue,
	t.LogScore,
	t.RemasterYear,
	g.Year,
	t.RemasterTitle,
	t.Snatched,
	t.Seeders,
	t.Leechers,
	((t.Size * t.Snatched) + (t.Size * 0.5 * t.Leechers)) AS Data
	FROM torrents AS t
	LEFT JOIN torrents_group AS g ON g.ID = t.GroupID ";

$OuterResults = array();

if($Details == 'all' || $Details == 'day') {
	if (!$TopTorrentsActiveLastDay = $Cache->get_value('top10tor_day_'.$Limit.$WhereSum)) {
		$DayAgo = time_minus(86400);
		$Query = $BaseQuery.' WHERE t.Seeders>0 AND ';
		if (!empty($Where)) { $Query .= $Where.' AND '; }
		$Query .= "
			t.Time>'$DayAgo'
			ORDER BY (t.Seeders + t.Leechers) DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsActiveLastDay = $DB->to_array();
		$Cache->cache_value('top10tor_day_'.$Limit.$WhereSum,$TopTorrentsActiveLastDay,3600*2);
	}
	$OuterResults[] = generate_torrent_json('Most Active Torrents Uploaded in the Past Day', 'day', $TopTorrentsActiveLastDay, $Limit);
}
if($Details == 'all' || $Details == 'week') {
	if (!$TopTorrentsActiveLastWeek = $Cache->get_value('top10tor_week_'.$Limit.$WhereSum)) {
		$WeekAgo = time_minus(604800);
		$Query = $BaseQuery.' WHERE ';
		if (!empty($Where)) { $Query .= $Where.' AND '; }
		$Query .= "
			t.Time>'$WeekAgo'
			ORDER BY (t.Seeders + t.Leechers) DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsActiveLastWeek = $DB->to_array();
		$Cache->cache_value('top10tor_week_'.$Limit.$WhereSum,$TopTorrentsActiveLastWeek,3600*6);
	}
	$OuterResults[] = generate_torrent_json('Most Active Torrents Uploaded in the Past Week', 'week', $TopTorrentsActiveLastWeek, $Limit);
}

if($Details == 'all' || $Details == 'overall') {
	if (!$TopTorrentsActiveAllTime = $Cache->get_value('top10tor_overall_'.$Limit.$WhereSum)) {
		// IMPORTANT NOTE - we use WHERE t.Seeders>500 in order to speed up this query. You should remove it!
		$Query = $BaseQuery;
		if (!empty($Where)) { $Query .= ' WHERE '.$Where; }
		elseif ($Details=='all') { $Query .= " WHERE t.Seeders>500 "; }
		$Query .= "
			ORDER BY (t.Seeders + t.Leechers) DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsActiveAllTime = $DB->to_array();
		$Cache->cache_value('top10tor_overall_'.$Limit.$WhereSum,$TopTorrentsActiveAllTime,3600*6);
	}
	$OuterResults[] = generate_torrent_json('Most Active Torrents of All Time', 'overall', $TopTorrentsActiveAllTime, $Limit);
}

if(($Details == 'all' || $Details == 'snatched') && empty($Where)) {
	if (!$TopTorrentsSnatched = $Cache->get_value('top10tor_snatched_'.$Limit.$WhereSum)) {
		$Query = $BaseQuery;
		$Query .= "
			ORDER BY t.Snatched DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsSnatched = $DB->to_array();
		$Cache->cache_value('top10tor_snatched_'.$Limit.$WhereSum,$TopTorrentsSnatched,3600*6);
	}
	$OuterResults[] = generate_torrent_json('Most Snatched Torrents', 'snatched', $TopTorrentsSnatched, $Limit);
}

if(($Details == 'all' || $Details == 'data') && empty($Where)) {
	if (!$TopTorrentsTransferred = $Cache->get_value('top10tor_data_'.$Limit.$WhereSum)) {
		// IMPORTANT NOTE - we use WHERE t.Snatched>100 in order to speed up this query. You should remove it!
		$Query = $BaseQuery;
		if ($Details=='all') { $Query .= " WHERE t.Snatched>100 "; }
		$Query .= "
			ORDER BY Data DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsTransferred = $DB->to_array();
		$Cache->cache_value('top10tor_data_'.$Limit.$WhereSum,$TopTorrentsTransferred,3600*6);
	}
	$OuterResults[] = generate_torrent_json('Most Data Transferred Torrents', 'data', $TopTorrentsTransferred, $Limit);
}

if(($Details == 'all' || $Details == 'seeded') && empty($Where)) {
	if (!$TopTorrentsSeeded = $Cache->get_value('top10tor_seeded_'.$Limit.$WhereSum)) {
		$Query = $BaseQuery."
			ORDER BY t.Seeders DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsSeeded = $DB->to_array();
		$Cache->cache_value('top10tor_seeded_'.$Limit.$WhereSum,$TopTorrentsSeeded,3600*6);
	}
	$OuterResults[] = generate_torrent_json('Best Seeded Torrents', 'seeded', $TopTorrentsSeeded, $Limit);
}

print
	json_encode(
		array(
			'status' => 'success',
                        'response' => $OuterResults
                )
        );


function generate_torrent_json($Caption, $Tag, $Details, $Limit) {
	global $LoggedUser,$Categories;
	$results = array();
	foreach ($Details as $Detail) {
		list($TorrentID,$GroupID,$GroupName,$GroupCategoryID,$TorrentTags,
			$Format,$Encoding,$Media,$Scene,$HasLog,$HasCue,$LogScore,$Year,$GroupYear,
			$RemasterTitle,$Snatched,$Seeders,$Leechers,$Data) = $Detail;

		$Artist = display_artists(get_artist($GroupID), false, true);
		$TruncArtist = substr($Artist, 0, strlen($Artist)-3);

		$TagList=array();

		if($TorrentTags!='') {
			$TorrentTags=explode(' ',$TorrentTags);
			foreach ($TorrentTags as $TagKey => $TagName) {
				$TagName = str_replace('_','.',$TagName);
				$TagList[]=$TagName;
			}
		}

		// Append to the existing array.
		$results[] = array(
			'torrentId' => $TorrentID,
			'groupId' => $GroupID,
			'artist' => $TruncArtist,
			'groupName' => $GroupName,
			'groupCategory' => $GroupCategoryID,
			'groupYear' => $GroupYear,
			'remasterTitle' => $RemasterTitle,
			'format' => $Format,
			'encoding' => $Encoding,
			'hasLog' => $HasLog,
			'hasCue' => $HasCue,
			'media' => $Media,
			'scene' => $Scene,
			'year' => $Year,
			'tags' => $TagList,
			'snatched' => $Snatched,
			'seeders' => $Seeders,
			'leechers' => $Leechers,
			'data' => $Data
		);
	}

	return array(
		'caption' => $Caption,
		'tag' => $Tag,
		'limit' => $Limit,
		'results' => $results
		);
}
?>
