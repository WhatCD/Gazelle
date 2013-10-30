<?
include(SERVER_ROOT.'/sections/torrents/functions.php');

/** Start default parameters and validation **/
// Setting default search options
if (!empty($_GET['setdefault'])) {
	$UnsetList = array('page', 'setdefault');
	$UnsetRegexp = '/(&|^)('.implode('|', $UnsetList).')=.*?(&|$)/i';

	$DB->query("
		SELECT SiteOptions
		FROM users_info
		WHERE UserID = '".db_string($LoggedUser['ID'])."'");
	list($SiteOptions) = $DB->next_record(MYSQLI_NUM, false);
	if (!empty($SiteOptions)) {
		$SiteOptions = unserialize($SiteOptions);
	} else {
		$SiteOptions = array();
	}
	$SiteOptions['DefaultSearch'] = preg_replace($UnsetRegexp, '', $_SERVER['QUERY_STRING']);
	$DB->query("
		UPDATE users_info
		SET SiteOptions = '".db_string(serialize($SiteOptions))."'
		WHERE UserID = '".db_string($LoggedUser['ID'])."'");
	$Cache->begin_transaction("user_info_heavy_$UserID");
	$Cache->update_row(false, array('DefaultSearch' => $SiteOptions['DefaultSearch']));
	$Cache->commit_transaction(0);

// Clearing default search options
} elseif (!empty($_GET['cleardefault'])) {
	$DB->query("
		SELECT SiteOptions
		FROM users_info
		WHERE UserID = '".db_string($LoggedUser['ID'])."'");
	list($SiteOptions) = $DB->next_record(MYSQLI_NUM, false);
	$SiteOptions = unserialize($SiteOptions);
	$SiteOptions['DefaultSearch'] = '';
	$DB->query("
		UPDATE users_info
		SET SiteOptions = '".db_string(serialize($SiteOptions))."'
		WHERE UserID = '".db_string($LoggedUser['ID'])."'");
	$Cache->begin_transaction("user_info_heavy_$UserID");
	$Cache->update_row(false, array('DefaultSearch' => ''));
	$Cache->commit_transaction(0);

// Use default search options
} elseif (empty($_SERVER['QUERY_STRING']) || (count($_GET) === 1 && isset($_GET['page']))) {
	if (!empty($LoggedUser['DefaultSearch'])) {
		if (!empty($_GET['page'])) {
			$Page = $_GET['page'];
			parse_str($LoggedUser['DefaultSearch'], $_GET);
			$_GET['page'] = $Page;
		} else {
			parse_str($LoggedUser['DefaultSearch'], $_GET);
		}
	}
}
// Terms were not submitted via the search form
if (!isset($_GET['searchsubmit'])) {
	$_GET['group_results'] = !$LoggedUser['DisableGrouping2'];
}

if (isset($_GET['group_results']) && $_GET['group_results']) {
	$_GET['group_results'] = 1;
	$GroupResults = true;
	$SortOrders = array(
		// 'url attr' => [global order, order within group]
		'year' => array('year', 'year'),
		'time' => array('id', 'id'),
		'size' => array('maxsize', 'size'),
		'seeders' => array('sumseeders', 'seeders'),
		'leechers' => array('sumleechers', 'leechers'),
		'snatched' => array('sumsnatched', 'snatched'),
		'random' => false);

	$AggregateExp = array(
		'maxsize' => 'MAX(size) AS maxsize',
		'sumseeders' => 'SUM(seeders) AS sumseeders',
		'sumleechers' => 'SUM(leechers) AS sumleechers',
		'sumsnatched' => 'SUM(snatched) AS sumsnatched');
} else {
	$GroupResults = false;
	$SortOrders = array(
		'year' => 'year',
		'time' => 'id',
		'size' => 'size',
		'seeders' => 'seeders',
		'leechers' => 'leechers',
		'snatched' => 'snatched',
		'random' => false);
}

if (empty($_GET['order_by']) || !isset($SortOrders[$_GET['order_by']])) {
	$_GET['order_by'] = 'time';
	$OrderBy = 'time'; // For header links
} else {
	$OrderBy = $_GET['order_by'];
}

if (!empty($_GET['order_way']) && $_GET['order_way'] == 'asc') {
	$OrderWay = 'asc';
} else {
	$_GET['order_way'] = 'desc';
	$OrderWay = 'desc';
}

/** End default parameters and validation **/

/** Start preparation of property arrays **/
array_pop($Bitrates); // remove 'other'
$SearchBitrates = array_merge($Bitrates, array('v0', 'v1', 'v2', '24bit'));

foreach ($SearchBitrates as $ID => $Val) {
	$SearchBitrates[$ID] = strtolower($Val);
}
foreach ($Formats as $ID => $Val) {
	$SearchFormats[$ID] = strtolower($Val);
}
/** End preparation of property arrays **/

/** Start query preparation **/
$SphQL = new SphinxqlQuery();
$SphQLTor = new SphinxqlQuery();

if ($OrderBy == 'random') {
	$SphQL->select('id, groupid, categoryid')
		->order_by('RAND()', '');
	$Random = true;
} elseif ($GroupResults) {
	$OrderProperties = $SortOrders[$OrderBy];
	$SphQL->select('groupid, categoryid' . (isset($AggregateExp[$OrderProperties[0]]) ? ', '.$AggregateExp[$OrderProperties[0]] : ''))
		->group_by('groupid')
		->order_by($OrderProperties[0], $OrderWay)
		->order_group_by($OrderProperties[1], $OrderWay);

} else {
	$SphQL->select('id, groupid, categoryid')
		->order_by($SortOrders[$OrderBy], $OrderWay);
}
$SphQL->from('torrents, delta');
$SphQLTor->select('id, groupid')->from('torrents, delta');
/** End query preparation **/

/** Start building search query **/
$Filtered = false;
$EnableNegation = false; // Sphinx needs at least one positive search condition to support the NOT operator

// File list searches make use of the proximity operator to ensure that all keywords match the same file
if (!empty($_GET['filelist'])) {
	$SearchString = trim($_GET['filelist']);
	if ($SearchString !== '') {
		$SearchString = '"'.Sphinxql::sph_escape_string($_GET['filelist']).'"~20';
		$SphQL->where_match($SearchString, 'filelist', false);
		$SphQLTor->where_match($SearchString, 'filelist', false);
		$EnableNegation = true;
	}
}

// Collect all entered search terms to find out whether to enable the NOT operator
$SearchWords = array();
foreach (array('artistname', 'groupname', 'recordlabel', 'cataloguenumber',
			'taglist', 'remastertitle', 'remasteryear', 'remasterrecordlabel',
			'remastercataloguenumber', 'encoding', 'format', 'media', 'description') as $Search) {
	if (!empty($_GET[$Search])) {
		$SearchString = trim($_GET[$Search]);
		if ($SearchString !== '') {
			$SearchWords[$Search] = array('include' => array(), 'exclude' => array());
			if ($Search == 'taglist') {
				$SearchString = strtr($SearchString, '.', '_');
				$Words = explode(',', $SearchString);
			} else {
				$Words = explode(' ', $SearchString);
			}
			foreach ($Words as $Word) {
				$Word = trim($Word);
				// Skip isolated hyphens to enable "Artist - Title" searches
				if ($Word === '-') {
					continue;
				}
				if ($Word[0] === '!' && strlen($Word) >= 2) {
					if (strpos($Word, '!', 1) === false) {
						$SearchWords[$Search]['exclude'][] = $Word;
					} else {
						$SearchWords[$Search]['include'][] = $Word;
						$EnableNegation = true;
					}
				} elseif ($Word !== '') {
					$SearchWords[$Search]['include'][] = $Word;
					$EnableNegation = true;
				}
			}
		}
	}
}

//Simple search
if (!empty($_GET['searchstr'])) {
	$SearchString = trim($_GET['searchstr']);
	$Words = explode(' ', strtolower($SearchString));
	if (!empty($Words)) {
		$FilterBitrates = $FilterFormats = array();
		$BasicSearch = array('include' => array(), 'exclude' => array());
		foreach ($Words as $Word) {
			$Word = trim($Word);
			// Skip isolated hyphens to enable "Artist - Title" searches
			if ($Word === '-') {
				continue;
			}
			if ($Word[0] === '!' && strlen($Word) >= 2) {
				if ($Word === '!100%') {
					$_GET['haslog'] = '-1';
				} elseif (strpos($Word, '!', 1) === false) {
					$BasicSearch['exclude'][] = $Word;
				} else {
					$BasicSearch['include'][] = $Word;
					$EnableNegation = true;
				}
			} elseif (in_array($Word, $SearchBitrates)) {
				$FilterBitrates[] = $Word;
				$EnableNegation = true;
			} elseif (in_array($Word, $SearchFormats)) {
				$FilterFormats[] = $Word;
				$EnableNegation = true;
			} elseif ($Word === '100%') {
				$_GET['haslog'] = '100';
			} elseif ($Word !== '') {
				$BasicSearch['include'][] = $Word;
				$EnableNegation = true;
			}
		}
		if (!$EnableNegation && !empty($BasicSearch['exclude'])) {
			$BasicSearch['include'] = array_merge($BasicSearch['include'], $BasicSearch['exclude']);
			unset($BasicSearch['exclude']);
		}
		$QueryParts = array();
		foreach ($BasicSearch['include'] as $Word) {
			$QueryParts[] = Sphinxql::sph_escape_string($Word);
		}
		if (!empty($BasicSearch['exclude'])) {
			foreach ($BasicSearch['exclude'] as $Word) {
				$QueryParts[] = '!'.Sphinxql::sph_escape_string(substr($Word, 1));
			}
		}
		if (!empty($FilterBitrates)) {
			$SearchString = implode(' ', $FilterBitrates);
			$SphQL->where_match($SearchString, 'encoding', false);
			$SphQLTor->where_match($SearchString, 'encoding', false);
		}
		if (!empty($FilterFormats)) {
			$SearchString = implode(' ', $FilterFormats);
			$SphQL->where_match($SearchString, 'format', false);
			$SphQLTor->where_match($SearchString, 'format', false);
		}
		if (!empty($QueryParts)) {
			$SearchString = implode(' ', $QueryParts);
			$SphQL->where_match($SearchString, '(groupname,artistname,yearfulltext)', false);
			$SphQLTor->where_match($SearchString, '(groupname,artistname,yearfulltext)', false);
		}
	}
}

// Tag list
if (!empty($SearchWords['taglist'])) {
	//Get tag aliases.
	$TagAliases = $Cache->get_value('tag_aliases_search');
	if ($TagAliases === false) {
		$DB->query('
			SELECT ID, BadTag, AliasTag
			FROM tag_aliases
			ORDER BY BadTag');
		$TagAliases = $DB->to_array(false, MYSQLI_ASSOC, false);
		//Unify tag aliases to be in_this_format as tags not in.this.format
		array_walk_recursive($TagAliases, create_function('&$val', '$val = preg_replace("/\./","_", $val);'));
		//Clean up the array for smaller cache size
		foreach ($TagAliases as &$TagAlias) {
			foreach (array_keys($TagAlias) as $Key) {
				if (is_numeric($Key)) {
					unset($TagAlias[$Key]);
				}
			}
		}
		$Cache->cache_value('tag_aliases_search', $TagAliases, 3600 * 24 * 7); // cache for 7 days
	}
	//Get tags
	$Tags = $SearchWords['taglist'];
	//Replace bad tags with tag aliases
	$End = count($Tags['include']);
	for ($i = 0; $i < $End; $i++) {
		foreach ($TagAliases as $TagAlias) {
			if ($Tags['include'][$i] === $TagAlias['BadTag']) {
				$Tags['include'][$i] = $TagAlias['AliasTag'];
				break;
			}
		}
	}
	$End = count($Tags['exclude']);
	for ($i = 0; $i < $End; $i++) {
		foreach ($TagAliases as $TagAlias) {
			if (substr($Tags['exclude'][$i], 1) === $TagAlias['BadTag']) {
				$Tags['exclude'][$i] = '!'.$TagAlias['AliasTag'];
				break;
			}
		}
	}
	//Only keep unique entries after unifying tag standard
	$Tags['include'] = array_unique($Tags['include']);
	$Tags['exclude'] = array_unique($Tags['exclude']);
	$TagListString = implode(', ', array_merge($Tags['include'], $Tags['exclude']));
	if (!$EnableNegation && !empty($Tags['exclude'])) {
		$Tags['include'] = array_merge($Tags['include'], $Tags['exclude']);
		unset($Tags['exclude']);
	}
	foreach ($Tags['include'] as &$Tag) {
		$Tag = Sphinxql::sph_escape_string($Tag);
	}
	if (!empty($Tags['exclude'])) {
		foreach ($Tags['exclude'] as &$Tag) {
			$Tag = '!'.Sphinxql::sph_escape_string(substr($Tag, 1));
		}
	}

	$QueryParts = array();
	// 'All' tags
	if (!isset($_GET['tags_type']) || $_GET['tags_type'] == 1) {
		$_GET['tags_type'] = '1';
		$Tags = array_merge($Tags['include'], $Tags['exclude']);
		if (!empty($Tags)) {
			$QueryParts[] = implode(' ', $Tags);
		}
	}
	// 'Any' tags
	else {
		$_GET['tags_type'] = '0';
		if (!empty($Tags['include'])) {
			$QueryParts[] = '( '.implode(' | ', $Tags['include']).' )';
		}
		if (!empty($Tags['exclude'])) {
			$QueryParts[] = implode(' ', $Tags['exclude']);
		}
	}
	if (!empty($QueryParts)) {
		$SphQL->where_match(implode(' ', $QueryParts), 'taglist', false);
		$SphQLTor->where_match(implode(' ', $QueryParts), 'taglist', false);
	}
	unset($SearchWords['taglist']);
}
elseif (!isset($_GET['tags_type'])) {
	$_GET['tags_type'] = '1';
}
if (!isset($TagListString)) {
	$TagListString = "";
}

foreach ($SearchWords as $Search => $Words) {
	$QueryParts = array();
	if (!$EnableNegation && !empty($Words['exclude'])) {
		$Words['include'] = array_merge($Words['include'], $Words['exclude']);
		unset($Words['exclude']);
	}
	foreach ($Words['include'] as $Word) {
		$QueryParts[] = Sphinxql::sph_escape_string($Word);
	}
	if (!empty($Words['exclude'])) {
		foreach ($Words['exclude'] as $Word) {
			$QueryParts[] = '!'.Sphinxql::sph_escape_string(substr($Word, 1));
		}
	}
	if (!empty($QueryParts)) {
		$SearchString = implode(' ', $QueryParts);
		$SphQL->where_match($SearchString, $Search, false);
		$SphQLTor->where_match($SearchString, $Search, false);
	}
}

if (!empty($_GET['year'])) {
	$Years = explode('-', $_GET['year']);
	if (is_number($Years[0]) || (empty($Years[0]) && !empty($Years[1]) && is_number($Years[1]))) {
		if (count($Years) === 1) {
			$SphQL->where('year', (int)$Years[0]);
			$SphQLTor->where('year', (int)$Years[0]);
		} else {
			if (empty($Years[1]) || !is_number($Years[1])) {
				$Years[1] = PHP_INT_MAX;
			} elseif ($Years[0] > $Years[1]) {
				$Years = array_reverse($Years);
			}
			$SphQL->where_between('year', array((int)$Years[0], (int)$Years[1]));
			$SphQLTor->where_between('year', array((int)$Years[0], (int)$Years[1]));
		}
	}
}

if (isset($_GET['haslog']) && $_GET['haslog'] !== '') {
	if ($_GET['haslog'] === '100') {
		$SphQL->where('logscore', 100);
		$SphQLTor->where('logscore', 100);
	} elseif ($_GET['haslog'] < 0) {
		// Exclude torrents with log score equal to 100
		$SphQL->where('logscore', 100, true);
		$SphQL->where('haslog', 1);
		$SphQLTor->where('logscore', 100, true);
		$SphQLTor->where('haslog', 1);
	} elseif ($_GET['haslog'] == 0) {
		$SphQL->where('haslog', 0);
		$SphQLTor->where('haslog', 0);
	} else {
		$SphQL->where('haslog', 1);
		$SphQLTor->where('haslog', 1);
	}
}

foreach (array('hascue', 'scene', 'vanityhouse', 'releasetype') as $Search) {
	if (isset($_GET[$Search]) && $_GET[$Search] !== '') {
		$SphQL->where($Search, $_GET[$Search]);
		// Release type is group specific
		if ($Search != 'releasetype') {
			$SphQLTor->where($Search, $_GET[$Search]);
		}
	}
}

if (isset($_GET['freetorrent']) && $_GET['freetorrent'] !== '') {
	switch ($_GET['freetorrent']) {
		case 0: // Only normal freeleech
			$SphQL->where('freetorrent', 0);
			$SphQLTor->where('freetorrent', 0);
			break;
		case 1: // Only free leech
			$SphQL->where('freetorrent', 1);
			$SphQLTor->where('freetorrent', 1);
			break;
		case 2: // Only neutral leech
			$SphQL->where('freetorrent', 2);
			$SphQLTor->where('freetorrent', 2);
			break;
		case 3: // Free or neutral leech
			$SphQL->where('freetorrent', 0, true);
			$SphQLTor->where('freetorrent', 0, true);
			break;
	}
}

if (!empty($_GET['filter_cat'])) {
	$SphQL->where('categoryid', array_keys($_GET['filter_cat']));
}
/** End building search query **/

/** Run search query and collect results **/
if (isset($Random) && $GroupResults) {
	// ORDER BY RAND() can't be used together with GROUP BY, so we need some special tactics
	$Page = 1;
	$SphQL->limit(0, 5 * TORRENTS_PER_PAGE, 5 * TORRENTS_PER_PAGE);
	$SphQLResult = $SphQL->query();
	$TotalCount = $SphQLResult->get_meta('total_found');
	$Results = $SphQLResult->to_array('groupid');
	$GroupIDs = array_keys($Results);
	$GroupCount = count($GroupIDs);
	while ($SphQLResult->get_meta('total') < $TotalCount && $GroupCount < TORRENTS_PER_PAGE) {
		// Make sure we get TORRENTS_PER_PAGE results, or all of them if there are less than TORRENTS_PER_PAGE hits
		$SphQL->where('groupid', $GroupIDs, true);
		$SphQLResult = $SphQL->query();
		if (!$SphQLResult->has_results()) {
			break;
		}
		$Results += $SphQLResult->to_array('groupid');
		$GroupIDs = array_keys($Results);
		$GroupCount = count($GroupIDs);
	}
	if ($GroupCount > TORRENTS_PER_PAGE) {
		$Results = array_slice($Results, 0, TORRENTS_PER_PAGE, true);
	}
	$GroupIDs = array_keys($Results);
	$NumResults = count($Results);
} else {
	if (!empty($_GET['page']) && is_number($_GET['page']) && $_GET['page'] > 0) {
		if (check_perms('site_search_many')) {
			$Page = $_GET['page'];
		} else {
			$Page = min(SPHINX_MAX_MATCHES / TORRENTS_PER_PAGE, $_GET['page']);
		}
		$Offset = ($Page - 1) * TORRENTS_PER_PAGE;
		$SphQL->limit($Offset, TORRENTS_PER_PAGE, $Offset + TORRENTS_PER_PAGE);
	} else {
		$Page = 1;
		$SphQL->limit(0, TORRENTS_PER_PAGE, TORRENTS_PER_PAGE);
	}
	$SphQLResult = $SphQL->query();
	$NumResults = $SphQLResult->get_meta('total_found');
	if ($GroupResults) {
		$Results = $SphQLResult->to_array('groupid');
		$GroupIDs = array_keys($Results);
	} else {
		$Results = $SphQLResult->to_array('id');
		$GroupIDs = $SphQLResult->collect('groupid');
	}
}

if (!check_perms('site_search_many') && $NumResults > SPHINX_MAX_MATCHES) {
	$NumResults = SPHINX_MAX_MATCHES;
}

if ($NumResults) {
	$Groups = Torrents::get_groups($GroupIDs);

	if (!empty($Groups) && $GroupResults) {
		$TorrentIDs = array();
		foreach ($Groups as $Group) {
			if (!empty($Group['Torrents'])) {
				$TorrentIDs = array_merge($TorrentIDs, array_keys($Group['Torrents']));
			}
		}
		$TorrentCount = count($TorrentIDs);
		if ($TorrentCount > 0) {
			// Get a list of all torrent ids that match the search query
			$SphQLTor->where('id', $TorrentIDs)->limit(0, $TorrentCount, $TorrentCount);
			$SphQLResultTor = $SphQLTor->query();
			$TorrentIDs = $SphQLResultTor->to_pair('id', 'id'); // Because isset() is faster than in_array()
		}
	}
}
/** End run search query and collect results **/

if ($NumResults == 0) {

$DB->query("
	SELECT
		tags.Name,
		((COUNT(tags.Name) - 2) * (SUM(tt.PositiveVotes) - SUM(tt.NegativeVotes))) / (tags.Uses * 0.8) AS Score
	FROM xbt_snatched AS s
		INNER JOIN torrents AS t ON t.ID = s.fid
		INNER JOIN torrents_group AS g ON t.GroupID = g.ID
		INNER JOIN torrents_tags AS tt ON tt.GroupID = g.ID
		INNER JOIN tags ON tags.ID = tt.TagID
	WHERE s.uid = '$LoggedUser[ID]'
		AND tt.TagID != '13679'
		AND tt.TagID != '4820'
		AND tt.TagID != '2838'
		AND g.CategoryID = '1'
		AND tags.Uses > '10'
	GROUP BY tt.TagID
	ORDER BY Score DESC
	LIMIT 8");
	$JsonYouMightLike = array();
	while (list($Tag) = $DB->next_record()) {
		$JsonYouMightLike[] = $Tag;
	}


	json_die("success", array(
		'results' => array(),
		'youMightLike' => $JsonYouMightLike
	));
}

$Bookmarks = Bookmarks::all_bookmarks('torrent');

$JsonGroups = array();
foreach ($Results as $Result) {
	$GroupID = $Result['groupid'];
	$GroupInfo = $Groups[$GroupID];
	if (empty($GroupInfo['Torrents'])) {
		continue;
	}
	$CategoryID = $Result['categoryid'];
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
			if ($T['Size'] > $MaxSize) {
				$MaxSize = $T['Size'];
			}
			$TotalLeechers += $T['Leechers'];
			$TotalSeeders += $T['Seeders'];
			$TotalSnatched += $T['Snatched'];
		}
	} else {
		$Torrents = array($Result['id'] => $GroupInfo['Torrents'][$Result['id']]);
	}

	$TagList = explode(' ', str_replace('_', '.', $GroupInfo['TagList']));
	$JsonArtists = array();
	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName = Artists::display_artists($ExtendedArtists, false, false, true);
		foreach ($ExtendedArtists[1] as $Artist) {
			$JsonArtists[] = array(
							'id' => (int)$Artist['id'],
							'name' => $Artist['name'],
							'aliasid' => (int)$Artist['id']
							);
		}
	} elseif (!empty($Artists)) {
		$DisplayName = Artists::display_artists(array(1 => $Artists), false, false, true);
		foreach ($Artists as $Artist) {
			$JsonArtists[] = array(
							'id' => (int)$Artist['id'],
							'name' => $Artist['name'],
							'aliasid' => (int)$Artist['id']
							);
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

			// If they're using the advanced search and have chosen enabled grouping, we just skip the torrents that don't check out
			if (!isset($TorrentIDs[$TorrentID])) {
				continue;
			}

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

echo json_encode(
	array(
		'status' => 'success',
		'response' => array(
			'currentPage' => intval($Page),
			'pages' => ceil($NumResults / TORRENTS_PER_PAGE),
			'results' => $JsonGroups)));
