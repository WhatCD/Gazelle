<?
include(SERVER_ROOT.'/sections/torrents/functions.php');

if (!empty($_GET['order_way']) && $_GET['order_way'] == 'asc') {
	$OrderWay = 'asc';
} else {
	$OrderWay = 'desc';
}

if (empty($_GET['order_by']) || !isset(TorrentSearch::$SortOrders[$_GET['order_by']])) {
	$OrderBy = 'time';
} else {
	$OrderBy = $_GET['order_by'];
}

$GroupResults = !isset($_GET['group_results']) || $_GET['group_results'] != '0';
$Page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
$Search = new TorrentSearch($GroupResults, $OrderBy, $OrderWay, $Page, TORRENTS_PER_PAGE);
$Results = $Search->query($_GET);
$Groups = $Search->get_groups();
$NumResults = $Search->record_count();

if ($Results === false) {
	json_die('error', 'Search returned an error. Make sure all parameters are valid and of the expected types.');
}
if ($NumResults == 0) {
	json_die('success', array(
		'results' => array(),
		'youMightLike' => array() // This slow and broken feature has been removed
	));
}

$Bookmarks = Bookmarks::all_bookmarks('torrent');

$JsonGroups = array();
foreach ($Results as $Key => $GroupID) {
	$GroupInfo = $Groups[$GroupID];
	if (empty($GroupInfo['Torrents'])) {
		continue;
	}
	$CategoryID = $GroupInfo['CategoryID'];
	$GroupYear = $GroupInfo['Year'];
	$ExtendedArtists = $GroupInfo['ExtendedArtists'];
	$GroupCatalogueNumber = $GroupInfo['CatalogueNumber'];
	$GroupName = $GroupInfo['Name'];
	$GroupRecordLabel = $GroupInfo['RecordLabel'];
	$ReleaseType = $GroupInfo['ReleaseType'];
	if ($GroupResults) {
		$Torrents = $GroupInfo['Torrents'];
		$GroupTime = $MaxSize = $TotalLeechers = $TotalSeeders = $TotalSnatched = 0;
		foreach ($Torrents as $T) {
			$GroupTime = max($GroupTime, strtotime($T['Time']));
			$MaxSize = max($MaxSize, $T['Size']);
			$TotalLeechers += $T['Leechers'];
			$TotalSeeders += $T['Seeders'];
			$TotalSnatched += $T['Snatched'];
		}
	} else {
		$TorrentID = $Key;
		$Torrents = array($TorrentID => $GroupInfo['Torrents'][$TorrentID]);
	}

	$TagList = explode(' ', str_replace('_', '.', $GroupInfo['TagList']));
	$JsonArtists = array();
	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName = Artists::display_artists($ExtendedArtists, false, false, false);
		foreach ($ExtendedArtists[1] as $Artist) {
			$JsonArtists[] = array(
				'id' => (int)$Artist['id'],
				'name' => $Artist['name'],
				'aliasid' => (int)$Artist['aliasid']);
		}
	} else {
		$DisplayName = '';
	}
	if ($GroupResults && (count($Torrents) > 1 || isset($GroupedCategories[$CategoryID - 1]))) {
		// These torrents are in a group
		$LastRemasterYear = '-';
		$LastRemasterTitle = '';
		$LastRemasterRecordLabel = '';
		$LastRemasterCatalogueNumber = '';
		$LastMedia = '';

		$EditionID = 0;
		unset($FirstUnknown);

		$JsonTorrents = array();
		foreach ($Torrents as $TorrentID => $Data) {
			// All of the individual torrents in the group

			if ($Data['Remastered'] && !$Data['RemasterYear']) {
				$FirstUnknown = !isset($FirstUnknown);
			}

			if (isset($GroupedCategories[$CategoryID - 1])
					&& ($Data['RemasterTitle'] != $LastRemasterTitle
						|| $Data['RemasterYear'] != $LastRemasterYear
						|| $Data['RemasterRecordLabel'] != $LastRemasterRecordLabel
						|| $Data['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber)
					|| $FirstUnknown
					|| $Data['Media'] != $LastMedia) {
				$EditionID++;

				if ($Data['Remastered'] && $Data['RemasterYear'] != 0) {

					$RemasterName = $Data['RemasterYear'];
					$AddExtra = ' - ';
					if ($Data['RemasterRecordLabel']) {
						$RemasterName .= $AddExtra.display_str($Data['RemasterRecordLabel']);
						$AddExtra = ' / ';
					}
					if ($Data['RemasterCatalogueNumber']) {
						$RemasterName .= $AddExtra.display_str($Data['RemasterCatalogueNumber']);
						$AddExtra = ' / ';
					}
					if ($Data['RemasterTitle']) {
						$RemasterName .= $AddExtra.display_str($Data['RemasterTitle']);
						$AddExtra = ' / ';
					}
					$RemasterName .= $AddExtra.display_str($Data['Media']);
				} else {
					$AddExtra = ' / ';
					if (!$Data['Remastered']) {
						$MasterName = 'Original Release';
						if ($GroupRecordLabel) {
							$MasterName .= $AddExtra.$GroupRecordLabel;
							$AddExtra = ' / ';
						}
						if ($GroupCatalogueNumber) {
							$MasterName .= $AddExtra.$GroupCatalogueNumber;
							$AddExtra = ' / ';
						}
					} else {
						$MasterName = 'Unknown Release(s)';
					}
					$MasterName .= $AddExtra.display_str($Data['Media']);
				}
			}
			$LastRemasterTitle = $Data['RemasterTitle'];
			$LastRemasterYear = $Data['RemasterYear'];
			$LastRemasterRecordLabel = $Data['RemasterRecordLabel'];
			$LastRemasterCatalogueNumber = $Data['RemasterCatalogueNumber'];
			$LastMedia = $Data['Media'];

			$JsonTorrents[] = array(
				'torrentId' => (int)$TorrentID,
				'editionId' => (int)$EditionID,
				'artists' => $JsonArtists,
				'remastered' => $Data['Remastered'] == '1',
				'remasterYear' => (int)$Data['RemasterYear'],
				'remasterCatalogueNumber' => $Data['RemasterCatalogueNumber'],
				'remasterTitle' => $Data['RemasterTitle'],
				'media' => $Data['Media'],
				'encoding' => $Data['Encoding'],
				'format' => $Data['Format'],
				'hasLog' => $Data['HasLog'] == '1',
				'logScore' => (int)$Data['LogScore'],
				'hasCue' => $Data['HasCue'] == '1',
				'scene' => $Data['Scene'] == '1',
				'vanityHouse' => $GroupInfo['VanityHouse'] == '1',
				'fileCount' => (int)$Data['FileCount'],
				'time' => $Data['Time'],
				'size' => (int)$Data['Size'],
				'snatches' => (int)$Data['Snatched'],
				'seeders' => (int)$Data['Seeders'],
				'leechers' => (int)$Data['Leechers'],
				'isFreeleech' => $Data['FreeTorrent'] == '1',
				'isNeutralLeech' => $Data['FreeTorrent'] == '2',
				'isPersonalFreeleech' => $Data['PersonalFL'],
				'canUseToken' => Torrents::can_use_token($Data),
				'hasSnatched' => $Data['IsSnatched']
			);
		}

		$JsonGroups[] = array(
			'groupId' => (int)$GroupID,
			'groupName' => $GroupName,
			'artist' => $DisplayName,
			'cover' => $GroupInfo['WikiImage'],
			'tags' => $TagList,
			'bookmarked' => in_array($GroupID, $Bookmarks),
			'vanityHouse' => $GroupInfo['VanityHouse'] == '1',
			'groupYear' => (int)$GroupYear,
			'releaseType' => $ReleaseTypes[$ReleaseType],
			'groupTime' => (string)$GroupTime,
			'maxSize' => (int)$MaxSize,
			'totalSnatched' => (int)$TotalSnatched,
			'totalSeeders' => (int)$TotalSeeders,
			'totalLeechers' => (int)$TotalLeechers,
			'torrents' => $JsonTorrents
		);
	} else {
		// Viewing a type that does not require grouping

		list($TorrentID, $Data) = each($Torrents);

		$JsonGroups[] = array(
			'groupId' => (int)$GroupID,
			'groupName' => $GroupName,
			'torrentId' => (int)$TorrentID,
			'tags' => $TagList,
			'category' => $Categories[$CategoryID - 1],
			'fileCount' => (int)$Data['FileCount'],
			'groupTime' => (string)strtotime($Data['Time']),
			'size' => (int)$Data['Size'],
			'snatches' => (int)$Data['Snatched'],
			'seeders' => (int)$Data['Seeders'],
			'leechers' => (int)$Data['Leechers'],
			'isFreeleech' => $Data['FreeTorrent'] == '1',
			'isNeutralLeech' => $Data['FreeTorrent'] == '2',
			'isPersonalFreeleech' => $Data['PersonalFL'],
			'canUseToken' => Torrents::can_use_token($Data),
			'hasSnatched' => $Data['IsSnatched']
		);
	}
}
json_print('success', array(
	'currentPage' => intval($Page),
	'pages' => ceil($NumResults / TORRENTS_PER_PAGE),
	'results' => $JsonGroups));
