<?
define('ARTIST_COLLAGE', 'Artists');
include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class
$Text = new TEXT;

if (empty($_GET['id'])) {
	json_die("failure", "bad parameters");
}

$CollageID = $_GET['id'];
if ($CollageID && !is_number($CollageID)) {
	json_die("failure");
}

$CacheKey = "collage_$CollageID";
$Data = $Cache->get_value($CacheKey);
if ($Data) {
	list($K, list($Name, $Description, , , , $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser)) = each($Data);
} else {
	$sql = "
		SELECT
			Name,
			Description,
			UserID,
			Deleted,
			CategoryID,
			Locked,
			MaxGroups,
			MaxGroupsPerUser,
			Subscribers
		FROM collages
		WHERE ID = '$CollageID'";
	$DB->query($sql);

	if (!$DB->has_results()) {
		json_die("failure");
	}

	list($Name, $Description, $CreatorID, $Deleted, $CollageCategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser) = $DB->next_record();
}

$JSON = array(
	'id'                  => (int)$CollageID,
	'name'                => $Name,
	'description'         => $Text->full_format($Description),
	'creatorID'           => (int)$CreatorID,
	'deleted'             => (bool)$Deleted,
	'collageCategoryID'   => (int)$CollageCategoryID,
	'collageCategoryName' => $CollageCats[(int)$CollageCategoryID],
	'locked'              => (bool)$Locked,
	'maxGroups'           => (int)$MaxGroups,
	'maxGroupsPerUser'    => (int)$MaxGroupsPerUser,
	'hasBookmarked'       => Bookmarks::has_bookmarked('collage', $CollageID)
);

if ($CollageCategoryID != array_search(ARTIST_COLLAGE, $CollageCats)) {
	// torrent collage
	$TorrentGroups = array();
	$DB->query("
		SELECT
			ct.GroupID
		FROM collages_torrents AS ct
			JOIN torrents_group AS tg ON tg.ID = ct.GroupID
		WHERE ct.CollageID = '$CollageID'
		ORDER BY ct.Sort");
	$GroupIDs = $DB->collect('GroupID');
	$GroupList = Torrents::get_groups($GroupIDs);
	$GroupList = $GroupList['matches'];
	foreach ($GroupIDs as $GroupID) {
		if (isset($GroupList[$GroupID])) {
			$GroupDetails = Torrents::array_group($GroupList[$GroupID]);
			if ($GroupDetails['GroupCategoryID'] > 0 && $Categories[$GroupDetails['GroupCategoryID'] - 1] == 'Music') {
				$ArtistForm = $GroupDetails['ExtendedArtists'];
				$JsonMusicInfo = array(
					'composers' => ($ArtistForm[4] == null) ? array() : pullmediainfo($ArtistForm[4]),
					'dj'        => ($ArtistForm[6] == null) ? array() : pullmediainfo($ArtistForm[6]),
					'artists'   => ($ArtistForm[1] == null) ? array() : pullmediainfo($ArtistForm[1]),
					'with'      => ($ArtistForm[2] == null) ? array() : pullmediainfo($ArtistForm[2]),
					'conductor' => ($ArtistForm[5] == null) ? array() : pullmediainfo($ArtistForm[5]),
					'remixedBy' => ($ArtistForm[3] == null) ? array() : pullmediainfo($ArtistForm[3]),
					'producer'  => ($ArtistForm[7] == null) ? array() : pullmediainfo($ArtistForm[7])
				);
			} else {
				$JsonMusicInfo = null;
			}
			$TorrentList = array();
			foreach ($GroupDetails['Torrents'] as $Torrent) {
				$TorrentList[] = array(
					'torrentid'               => (int)$Torrent['ID'],
					'media'                   => $Torrent['Media'],
					'format'                  => $Torrent['Format'],
					'encoding'                => $Torrent['Encoding'],
					'remastered'              => ($Torrent['Remastered'] == 1),
					'remasterYear'            => (int)$Torrent['RemasterYear'],
					'remasterTitle'           => $Torrent['RemasterTitle'],
					'remasterRecordLabel'     => $Torrent['RemasterRecordLabel'],
					'remasterCatalogueNumber' => $Torrent['RemasterCatalogueNumber'],
					'scene'                   => ($Torrent['Scene'] == 1),
					'hasLog'                  => ($Torrent['HasLog'] == 1),
					'hasCue'                  => ($Torrent['HasCue'] == 1),
					'logScore'                => (int)$Torrent['LogScore'],
					'fileCount'               => (int)$Torrent['FileCount'],
					'size'                    => (int)$Torrent['Size'],
					'seeders'                 => (int)$Torrent['Seeders'],
					'leechers'                => (int)$Torrent['Leechers'],
					'snatched'                => (int)$Torrent['Snatched'],
					'freeTorrent'             => ($Torrent['FreeTorrent'] == 1),
					'reported'                => (count(Torrents::get_reports((int)$Torrent['ID'])) > 0),
					'time'                    => $Torrent['Time']
				);
			}
			$TorrentGroups[] = array(
				'id'              => $GroupDetails['GroupID'],
				'name'            => $GroupDetails['GroupName'],
				'year'            => $GroupDetails['GroupYear'],
				'categoryId'      => $GroupDetails['GroupCategoryID'],
				'recordLabel'     => $GroupDetails['GroupRecordLabel'],
				'catalogueNumber' => $GroupDetails['GroupCatalogueNumber'],
				'vanityHouse'     => $GroupDetails['GroupVanityHouse'],
				'tagList'         => $GroupDetails['TagList'],
				'releaseType'     => $GroupDetails['ReleaseType'],
				'wikiImage'       => $GroupDetails['WikiImage'],
				'musicInfo'       => $JsonMusicInfo,
				'torrents'        => $TorrentList
			);
		}
	}
	$JSON['torrentgroups'] = $TorrentGroups;
} else {
	// artist collage
	$DB->query("
		SELECT
			ca.ArtistID,
			ag.Name,
			aw.Image
		FROM collages_artists AS ca
			JOIN artists_group AS ag ON ag.ArtistID=ca.ArtistID
			LEFT JOIN wiki_artists AS aw ON aw.RevisionID = ag.RevisionID
		WHERE ca.CollageID='$CollageID'
		ORDER BY ca.Sort");
	$Artists = array();
	while (list($ArtistID, $ArtistName, $ArtistImage) = $DB->next_record()) {
		$Artists[] = array(
			'id'    => (int)$ArtistID,
			'name'  => $ArtistName,
			'image' => $ArtistImage
		);
	}
	$JSON['artists'] = $Artists;
}

$Cache->cache_value($CacheKey, array(array($Name, $Description, array(), array(), array(), $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser)), 3600);

json_die("success", $JSON);
