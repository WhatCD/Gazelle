<?
//For sorting tags
function compare($X, $Y) {
	return($Y['count'] - $X['count']);
}

if (!empty($_GET['artistreleases'])) {
	$OnlyArtistReleases = true;
}

if ($_GET['id'] && $_GET['artistname']) {
	json_die("failure", "bad parameters");
}

$ArtistID = $_GET['id'];
if ($ArtistID && !is_number($ArtistID)) {
	json_die("failure");
}

if (empty($ArtistID)) {
	if (!empty($_GET['artistname'])) {
		$Name = db_string(trim($_GET['artistname']));
		$DB->query("
			SELECT ArtistID
			FROM artists_alias
			WHERE Name LIKE '$Name'");
		if (!(list($ArtistID) = $DB->next_record(MYSQLI_NUM, false))) {
			json_die("failure");
		}
		// If we get here, we got the ID!
	}
}

if (!empty($_GET['revisionid'])) { // if they're viewing an old revision
	$RevisionID = $_GET['revisionid'];
	if (!is_number($RevisionID)) {
		error(0);
	}
	$Data = $Cache->get_value("artist_$ArtistID"."_revision_$RevisionID");
} else { // viewing the live version
	$Data = $Cache->get_value("artist_$ArtistID");
	$RevisionID = false;
}
if ($Data) {
	list($K, list($Name, $Image, $Body, $NumSimilar, $SimilarArray, , , $VanityHouseArtist)) = each($Data);
} else {
	if ($RevisionID) {
		$sql = "
			SELECT
				a.Name,
				wiki.Image,
				wiki.body,
				a.VanityHouse
			FROM wiki_artists AS wiki
				LEFT JOIN artists_group AS a ON wiki.RevisionID = a.RevisionID
			WHERE wiki.RevisionID = '$RevisionID' ";
	} else {
		$sql = "
			SELECT
				a.Name,
				wiki.Image,
				wiki.body,
				a.VanityHouse
			FROM artists_group AS a
				LEFT JOIN wiki_artists AS wiki ON wiki.RevisionID = a.RevisionID
			WHERE a.ArtistID = '$ArtistID' ";
	}
	$sql .= " GROUP BY a.ArtistID";
	$DB->query($sql);

	if (!$DB->has_results()) {
		json_die("failure");
	}

	list($Name, $Image, $Body, $VanityHouseArtist) = $DB->next_record(MYSQLI_NUM, array(0));
}

// Requests
$Requests = array();
if (empty($LoggedUser['DisableRequests'])) {
	$Requests = $Cache->get_value("artists_requests_$ArtistID");
	if (!is_array($Requests)) {
		$DB->query("
			SELECT
				r.ID,
				r.CategoryID,
				r.Title,
				r.Year,
				r.TimeAdded,
				COUNT(rv.UserID) AS Votes,
				SUM(rv.Bounty) AS Bounty
			FROM requests AS r
				LEFT JOIN requests_votes AS rv ON rv.RequestID = r.ID
				LEFT JOIN requests_artists AS ra ON r.ID = ra.RequestID
			WHERE ra.ArtistID = $ArtistID
				AND r.TorrentID = 0
			GROUP BY r.ID
			ORDER BY Votes DESC");
	
		if ($DB->has_results()) {
			$Requests = $DB->to_array('ID', MYSQLI_ASSOC, false);
		} else {
			$Requests = array();
		}
		$Cache->cache_value("artists_requests_$ArtistID", $Requests);
	}
}
$NumRequests = count($Requests);

if (($Importances = $Cache->get_value("artist_groups_$ArtistID")) === false) {
	$DB->query("
		SELECT
			DISTINCTROW ta.GroupID, ta.Importance, tg.VanityHouse, tg.Year
		FROM torrents_artists AS ta
			JOIN torrents_group AS tg ON tg.ID = ta.GroupID
		WHERE ta.ArtistID = '$ArtistID'
		ORDER BY tg.Year DESC, tg.Name DESC");
	$GroupIDs = $DB->collect('GroupID');
	$Importances = $DB->to_array(false, MYSQLI_BOTH, false);
	$Cache->cache_value("artist_groups_$ArtistID", $Importances, 0);
} else {
	$GroupIDs = array();
	foreach ($Importances as $Group) {
		$GroupIDs[] = $Group['GroupID'];
	}
}
if (count($GroupIDs) > 0) {
	$TorrentList = Torrents::get_groups($GroupIDs, true, true);
} else {
	$TorrentList = array();
}
$NumGroups = count($TorrentList);

//Get list of used release types
$UsedReleases = array();
foreach ($TorrentList as $GroupID=>$Group) {
	if ($Importances[$GroupID]['Importance'] == '2') {
		$TorrentList[$GroupID]['ReleaseType'] = 1024;
		$GuestAlbums = true;
	}
	if ($Importances[$GroupID]['Importance'] == '3') {
		$TorrentList[$GroupID]['ReleaseType'] = 1023;
		$RemixerAlbums = true;
	}
	if ($Importances[$GroupID]['Importance'] == '4') {
		$TorrentList[$GroupID]['ReleaseType'] = 1022;
		$ComposerAlbums = true;
	}
	if ($Importances[$GroupID]['Importance'] == '7') {
		$TorrentList[$GroupID]['ReleaseType'] = 1021;
		$ProducerAlbums = true;
	}
	if (!in_array($TorrentList[$GroupID]['ReleaseType'], $UsedReleases)) {
		$UsedReleases[] = $TorrentList[$GroupID]['ReleaseType'];
	}
}

if (!empty($GuestAlbums)) {
	$ReleaseTypes[1024] = 'Guest Appearance';
}
if (!empty($RemixerAlbums)) {
	$ReleaseTypes[1023] = 'Remixed By';
}
if (!empty($ComposerAlbums)) {
	$ReleaseTypes[1022] = 'Composition';
}
if (!empty($ProducerAlbums)) {
	$ReleaseTypes[1021] = 'Produced By';
}

reset($TorrentList);

$JsonTorrents = array();
$Tags = array();
$NumTorrents = $NumSeeders = $NumLeechers = $NumSnatches = 0;
foreach ($GroupIDs as $GroupID) {
	if (!isset($TorrentList[$GroupID])) {
		continue;
	}
	$Group = $TorrentList[$GroupID];
	extract(Torrents::array_group($Group));

	foreach ($Artists as &$Artist) {
		$Artist['id'] = (int)$Artist['id'];
		$Artist['aliasid'] = (int)$Artist['aliasid'];
	}

	foreach ($ExtendedArtists as &$ArtistGroup) {
		foreach ($ArtistGroup as &$Artist) {
			$Artist['id'] = (int)$Artist['id'];
			$Artist['aliasid'] = (int)$Artist['aliasid'];
		}
	}

	$Found = Misc::search_array($Artists, 'id', $ArtistID);
	if (isset($OnlyArtistReleases) && empty($Found)) {
		continue;
	}

	$GroupVanityHouse = $Importances[$GroupID]['VanityHouse'];

	$TagList = explode(' ',str_replace('_', '.', $TagList));

	// $Tags array is for the sidebar on the right
	foreach ($TagList as $Tag) {
		if (!isset($Tags[$Tag])) {
			$Tags[$Tag] = array('name' => $Tag, 'count' => 1);
		} else {
			$Tags[$Tag]['count']++;
		}
	}
	$InnerTorrents = array();
	foreach ($Torrents as $Torrent) {
		$NumTorrents++;
		$NumSeeders += $Torrent['Seeders'];
		$NumLeechers += $Torrent['Leechers'];
		$NumSnatches += $Torrent['Snatched'];

		$InnerTorrents[] = array(
			'id' => (int)$Torrent['ID'],
			'groupId' => (int)$Torrent['GroupID'],
			'media' => $Torrent['Media'],
			'format' => $Torrent['Format'],
			'encoding' => $Torrent['Encoding'],
			'remasterYear' => (int)$Torrent['RemasterYear'],
			'remastered' => $Torrent['Remastered'] == 1,
			'remasterTitle' => $Torrent['RemasterTitle'],
			'remasterRecordLabel' => $Torrent['RemasterRecordLabel'],
			'scene' => $Torrent['Scene'] == 1,
			'hasLog' => $Torrent['HasLog'] == 1,
			'hasCue' => $Torrent['HasCue'] == 1,
			'logScore' => (int)$Torrent['LogScore'],
			'fileCount' => (int)$Torrent['FileCount'],
			'freeTorrent' => $Torrent['FreeTorrent'] == 1,
			'size' => (int)$Torrent['Size'],
			'leechers' => (int)$Torrent['Leechers'],
			'seeders' => (int)$Torrent['Seeders'],
			'snatched' => (int)$Torrent['Snatched'],
			'time' => $Torrent['Time'],
			'hasFile' => (int)$Torrent['HasFile']
		);
	}
	$JsonTorrents[] = array(
		'groupId' => (int)$GroupID,
		'groupName' => $GroupName,
		'groupYear' => (int)$GroupYear,
		'groupRecordLabel' => $GroupRecordLabel,
		'groupCatalogueNumber' => $GroupCatalogueNumber,
		'groupCategoryID' => $GroupCategoryID,
		'tags' => $TagList,
		'releaseType' => (int)$ReleaseType,
		'wikiImage' => $WikiImage,
		'groupVanityHouse' => $GroupVanityHouse == 1,
		'hasBookmarked' => Bookmarks::has_bookmarked('torrent', $GroupID),
		'artists' => $Artists,
		'extendedArtists' => $ExtendedArtists,
		'torrent' => $InnerTorrents,

	);
}

$JsonSimilar = array();
if (empty($SimilarArray)) {
	$DB->query("
		SELECT
			s2.ArtistID,
			a.Name,
			ass.Score,
			ass.SimilarID
		FROM artists_similar AS s1
			JOIN artists_similar AS s2 ON s1.SimilarID = s2.SimilarID AND s1.ArtistID != s2.ArtistID
			JOIN artists_similar_scores AS ass ON ass.SimilarID = s1.SimilarID
			JOIN artists_group AS a ON a.ArtistID = s2.ArtistID
		WHERE s1.ArtistID = '$ArtistID'
		ORDER BY ass.Score DESC
		LIMIT 30
	");
	$SimilarArray = $DB->to_array();
	foreach ($SimilarArray as $Similar) {
		$JsonSimilar[] = array(
			'artistId' => (int)$Similar['ArtistID'],
			'name' => $Similar['Name'],
			'score' => (int)$Similar['Score'],
			'similarId' => (int)$Similar['SimilarID']
		);
	}
	$NumSimilar = count($SimilarArray);
} else {
	//If data already exists, use it
	foreach ($SimilarArray as $Similar) {
		$JsonSimilar[] = array(
			'artistId' => (int)$Similar['ArtistID'],
			'name' => $Similar['Name'],
			'score' => (int)$Similar['Score'],
			'similarId' => (int)$Similar['SimilarID']
		);
	}
}

$JsonRequests = array();
foreach ($Requests as $RequestID => $Request) {
	$JsonRequests[] = array(
		'requestId' => (int)$RequestID,
		'categoryId' => (int)$Request['CategoryID'],
		'title' => $Request['Title'],
		'year' => (int)$Request['Year'],
		'timeAdded' => $Request['TimeAdded'],
		'votes' => (int)$Request['Votes'],
		'bounty' => (int)$Request['Bounty']
	);
}

//notifications disabled by default
$notificationsEnabled = false;
if (check_perms('site_torrents_notify')) {
	if (($Notify = $Cache->get_value('notify_artists_'.$LoggedUser['ID'])) === false) {
		$DB->query("
			SELECT ID, Artists
			FROM users_notify_filters
			WHERE UserID = '$LoggedUser[ID]'
				AND Label = 'Artist notifications'
			LIMIT 1");
		$Notify = $DB->next_record(MYSQLI_ASSOC, false);
		$Cache->cache_value('notify_artists_'.$LoggedUser['ID'], $Notify, 0);
	}
	if (stripos($Notify['Artists'], "|$Name|") === false) {
		$notificationsEnabled = false;
	} else {
		$notificationsEnabled = true;
	}
}

// Cache page for later use

if ($RevisionID) {
	$Key = "artist_$ArtistID"."_revision_$RevisionID";
} else {
	$Key = "artist_$ArtistID";
}

$Data = array(array($Name, $Image, $Body, $NumSimilar, $SimilarArray, array(), array(), $VanityHouseArtist));

$Cache->cache_value($Key, $Data, 3600);

json_print("success", array(
	'id' => (int)$ArtistID,
	'name' => $Name,
	'notificationsEnabled' => $notificationsEnabled,
	'hasBookmarked' => Bookmarks::has_bookmarked('artist', $ArtistID),
	'image' => $Image,
	'body' => Text::full_format($Body),
	'vanityHouse' => $VanityHouseArtist == 1,
	'tags' => array_values($Tags),
	'similarArtists' => $JsonSimilar,
	'statistics' => array(
		'numGroups' => $NumGroups,
		'numTorrents' => $NumTorrents,
		'numSeeders' => $NumSeeders,
		'numLeechers' => $NumLeechers,
		'numSnatches' => $NumSnatches
	),
	'torrentgroup' => $JsonTorrents,
	'requests' => $JsonRequests
));

?>
