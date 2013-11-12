<?
/************************************************************************
*-------------------- Browse page ---------------------------------------
* Welcome to one of the most complicated pages in all of gazelle - the
* browse page.
*
* This is the page that is displayed when someone visits torrents.php
*
* It offers normal and advanced search, as well as enabled/disabled
* grouping.
*
* Don't blink.
* Blink and you're dead.
* Don't turn your back.
* Don't look away.
* And don't blink.
* Good Luck.
*
*************************************************************************/

include(SERVER_ROOT.'/sections/torrents/functions.php');

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = 'desc') {
	global $OrderBy, $OrderWay;
	if ($SortKey == $OrderBy) {
		if ($OrderWay == 'desc') {
			$NewWay = 'asc';
		} else {
			$NewWay = 'desc';
		}
	} else {
		$NewWay = $DefaultWay;
	}
	return "torrents.php?order_way=$NewWay&amp;order_by=$SortKey&amp;".Format::get_url(array('order_way', 'order_by'));
}

/** Start default parameters and validation **/
if (!empty($_GET['searchstr']) || !empty($_GET['groupname'])) {
	if (!empty($_GET['searchstr'])) {
		$InfoHash = $_GET['searchstr'];
	} else {
		$InfoHash = $_GET['groupname'];
	}

	// Search by infohash
	if ($InfoHash = is_valid_torrenthash($InfoHash)) {
		$InfoHash = db_string(pack('H*', $InfoHash));
		$DB->query("
			SELECT ID, GroupID
			FROM torrents
			WHERE info_hash = '$InfoHash'");
		if ($DB->has_results()) {
			list($ID, $GroupID) = $DB->next_record();
			header("Location: torrents.php?id=$GroupID&torrentid=$ID");
			die();
		}
	}
}

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
$SphQLTor->select('id')->from('torrents, delta');
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
		$Filtered = true;
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
			$Filtered = true;
		}
		if (!empty($FilterFormats)) {
			$SearchString = implode(' ', $FilterFormats);
			$SphQL->where_match($SearchString, 'format', false);
			$SphQLTor->where_match($SearchString, 'format', false);
			$Filtered = true;
		}
		if (!empty($QueryParts)) {
			$SearchString = implode(' ', $QueryParts);
			$SphQL->where_match($SearchString, '(groupname,artistname,yearfulltext)', false);
			$SphQLTor->where_match($SearchString, '(groupname,artistname,yearfulltext)', false);
			$Filtered = true;
		}
	}
}

// Tag list
if (!empty($SearchWords['taglist'])) {
	// Get tag aliases.
	$TagAliases = $Cache->get_value('tag_aliases_search');
	if ($TagAliases === false) {
		$DB->query('
			SELECT ID, BadTag, AliasTag
			FROM tag_aliases
			ORDER BY BadTag');
		$TagAliases = $DB->to_array(false, MYSQLI_ASSOC, false);
		// Unify tag aliases to be in_this_format as tags not in.this.format
		array_walk_recursive($TagAliases, create_function('&$val', '$val = preg_replace("/\./","_", $val);'));
		// Clean up the array for smaller cache size
		foreach ($TagAliases as &$TagAlias) {
			foreach (array_keys($TagAlias) as $Key) {
				if (is_numeric($Key)) {
					unset($TagAlias[$Key]);
				}
			}
		}
		$Cache->cache_value('tag_aliases_search', $TagAliases, 3600 * 24 * 7); // cache for 7 days
	}
	// Get tags
	$Tags = $SearchWords['taglist'];
	// Replace bad tags with tag aliases
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
	// Only keep unique entries after unifying tag standard
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
		$Filtered = true;
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
		$Filtered = true;
	}
}

if (!empty($_GET['year'])) {
	$Years = explode('-', $_GET['year']);
	if (is_number($Years[0]) || (empty($Years[0]) && !empty($Years[1]) && is_number($Years[1]))) {
		if (count($Years) === 1) {
			$SphQL->where('year', $Years[0]);
			$SphQLTor->where('year', $Years[0]);
		} else {
			if (empty($Years[0])) {
				$SphQL->where_lt('year', $Years[1], true);
				$SphQLTor->where_lt('year', $Years[1], true);
			} elseif (empty($Years[1]) || !is_number($Years[1])) {
				$SphQL->where_gt('year', $Years[0], true);
				$SphQLTor->where_gt('year', $Years[0], true);
			} else {
				if ($Years[0] > $Years[1]) {
					$Years = array_reverse($Years);
				}
				$SphQL->where_between('year', array($Years[0], $Years[1]));
				$SphQLTor->where_between('year', array($Years[0], $Years[1]));
			}
		}
		$Filtered = true;
	}
}

if (isset($_GET['haslog']) && $_GET['haslog'] !== '') {
	if ($_GET['haslog'] === '100') {
		$SphQL->where('logscore', 100);
		$SphQLTor->where('logscore', 100);
	} elseif ($_GET['haslog'] < 0) {
		// Look for torrents with log score < 100
		$SphQL->where_lt('logscore', 100);
		$SphQL->where('haslog', 1);
		$SphQLTor->where_lt('logscore', 100);
		$SphQLTor->where('haslog', 1);
	} elseif ($_GET['haslog'] == 0) {
		$SphQL->where('haslog', 0);
		$SphQLTor->where('haslog', 0);
	} else {
		$SphQL->where('haslog', 1);
		$SphQLTor->where('haslog', 1);
	}
	$Filtered = true;
}

foreach (array('hascue', 'scene', 'vanityhouse', 'releasetype') as $Search) {
	if (isset($_GET[$Search]) && $_GET[$Search] !== '') {
		$SphQL->where($Search, $_GET[$Search]);
		// Release type is group specific
		if ($Search != 'releasetype') {
			$SphQLTor->where($Search, $_GET[$Search]);
		}
		$Filtered = true;
	}
}

if (isset($_GET['freetorrent']) && $_GET['freetorrent'] !== '') {
	switch ($_GET['freetorrent']) {
		case 0: // Only normal freeleech
			$SphQL->where('freetorrent', 0);
			$SphQLTor->where('freetorrent', 0);
			$Filtered = true;
			break;
		case 1: // Only free leech
			$SphQL->where('freetorrent', 1);
			$SphQLTor->where('freetorrent', 1);
			$Filtered = true;
			break;
		case 2: // Only neutral leech
			$SphQL->where('freetorrent', 2);
			$SphQLTor->where('freetorrent', 2);
			$Filtered = true;
			break;
		case 3: // Free or neutral leech
			$SphQL->where('freetorrent', 0, true);
			$SphQLTor->where('freetorrent', 0, true);
			$Filtered = true;
			break;
		default:
			unset($_GET['freetorrent']);
			break;
	}
}

if (!empty($_GET['filter_cat'])) {
	$SphQL->where('categoryid', array_keys($_GET['filter_cat']));
	$Filtered = true;
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

$HideFilter = isset($LoggedUser['ShowTorFilter']) && $LoggedUser['ShowTorFilter'] == 0;
// This is kinda ugly, but the enormous if paragraph was really hard to read
$AdvancedSearch = !empty($_GET['action']) && $_GET['action'] == 'advanced';
$AdvancedSearch |= !empty($LoggedUser['SearchType']) && (empty($_GET['action']) || $_GET['action'] == 'advanced');
$AdvancedSearch &= check_perms('site_advanced_search');
if ($AdvancedSearch) {
	$Action = 'action=advanced';
	$HideBasic = ' hidden';
	$HideAdvanced = '';
} else {
	$Action = 'action=basic';
	$HideBasic = '';
	$HideAdvanced = ' hidden';
}


View::show_header('Browse Torrents', 'browse');

?>
<div class="thin widethin">
<div class="header">
	<h2>Torrents</h2>
</div>
<form class="search_form" name="torrents" method="get" action="" onsubmit="$(this).disableUnset();">
<div class="box filter_torrents">
	<div class="head">
		<strong>
			<span id="ft_basic_text" class="<?=$HideBasic?>">Basic /</span>
			<span id="ft_basic_link" class="<?=$HideAdvanced?>"><a href="#" onclick="return toggleTorrentSearch('basic');">Basic</a> /</span>
			<span id="ft_advanced_text" class="<?=$HideAdvanced?>">Advanced</span>
			<span id="ft_advanced_link" class="<?=$HideBasic?>"><a href="#" onclick="return toggleTorrentSearch('advanced');">Advanced</a></span>
			Search
		</strong>
		<span style="float: right;">
			<a href="#" onclick="return toggleTorrentSearch(0);" id="ft_toggle" class="brackets"><?=$HideFilter ? 'Show' : 'Hide'?></a>
		</span>
	</div>
	<div id="ft_container" class="pad<?=$HideFilter ? ' hidden' : ''?>">
		<table class="layout">
			<tr id="artist_name" class="ftr_advanced<?=$HideAdvanced?>">
				<td class="label">Artist name:</td>
				<td colspan="3" class="ft_artistname">
					<input type="text" spellcheck="false" size="40" name="artistname" class="inputtext smaller fti_advanced" value="<?Format::form('artistname')?>" />
				</td>
			</tr>
			<tr id="album_torrent_name" class="ftr_advanced<?=$HideAdvanced?>">
				<td class="label">Album/Torrent name:</td>
				<td colspan="3" class="ft_groupname">
					<input type="text" spellcheck="false" size="40" name="groupname" class="inputtext smaller fti_advanced" value="<?Format::form('groupname')?>" />
				</td>
			</tr>
			<tr id="record_label" class="ftr_advanced<?=$HideAdvanced?>">
				<td class="label">Record label:</td>
				<td colspan="3" class="ft_recordlabel">
					<input type="text" spellcheck="false" size="40" name="recordlabel" class="inputtext smaller fti_advanced" value="<?Format::form('recordlabel')?>" />
				</td>
			</tr>
			<tr id="catalogue_number_year" class="ftr_advanced<?=$HideAdvanced?>">
				<td class="label">Catalogue number:</td>
				<td class="ft_cataloguenumber">
					<input type="text" size="40" name="cataloguenumber" class="inputtext smallest fti_advanced" value="<?Format::form('cataloguenumber')?>" />
				</td>
				<td class="label">Year:</td>
				<td class="ft_year">
					<input type="text" name="year" class="inputtext smallest fti_advanced" value="<?Format::form('year')?>" size="4" />
				</td>
			</tr>
			<tr id="edition_expand" class="ftr_advanced<?=$HideAdvanced?>">
				<td colspan="4" class="center ft_edition_expand"><a href="#" class="brackets" onclick="ToggleEditionRows(); return false;">Click here to toggle searching for specific remaster information</a></td>
			</tr>
<?
if (Format::form('remastertitle', true) == ''
	&& Format::form('remasteryear', true) == ''
	&& Format::form('remasterrecordlabel', true) == ''
	&& Format::form('remastercataloguenumber', true) == ''
) {
	$Hidden = ' hidden';
} else {
	$Hidden = '';
}
?>
			<tr id="edition_title" class="ftr_advanced<?=$HideAdvanced . $Hidden?>">
				<td class="label">Edition title:</td>
				<td class="ft_remastertitle">
					<input type="text" spellcheck="false" size="40" name="remastertitle" class="inputtext smaller fti_advanced" value="<?Format::form('remastertitle')?>" />
				</td>
				<td class="label">Edition year:</td>
				<td class="ft_remasteryear">
					<input type="text" name="remasteryear" class="inputtext smallest fti_advanced" value="<?Format::form('remasteryear')?>" size="4" />
				</td>
			</tr>
			<tr id="edition_label" class="ftr_advanced<?=$HideAdvanced . $Hidden?>">
				<td class="label">Edition release label:</td>
				<td colspan="3" class="ft_remasterrecordlabel">
					<input type="text" spellcheck="false" size="40" name="remasterrecordlabel" class="inputtext smaller fti_advanced" value="<?Format::form('remasterrecordlabel')?>" />
				</td>
			</tr>
			<tr id="edition_catalogue" class="ftr_advanced<?=$HideAdvanced . $Hidden?>">
				<td class="label">Edition catalogue number:</td>
				<td colspan="3" class="ft_remastercataloguenumber">
					<input type="text" size="40" name="remastercataloguenumber" class="inputtext smallest fti_advanced" value="<?Format::form('remastercataloguenumber')?>" />
				</td>
			</tr>
			<tr id="file_list" class="ftr_advanced<?=$HideAdvanced?>">
				<td class="label">File list:</td>
				<td colspan="3" class="ft_filelist">
					<input type="text" spellcheck="false" size="40" name="filelist" class="inputtext fti_advanced" value="<?Format::form('filelist')?>" />
				</td>
			</tr>
			<tr id="torrent_description" class="ftr_advanced<?=$HideAdvanced?>">
				<td class="label"><span title="Search torrent descriptions (not group information)" class="tooltip">Torrent description:</span></td>
				<td colspan="3" class="ft_description">
					<input type="text" spellcheck="false" size="40" name="description" class="inputtext fti_advanced" value="<?Format::form('description')?>" />
				</td>
			</tr>
			<tr id="rip_specifics" class="ftr_advanced<?=$HideAdvanced?>">
				<td class="label">Rip specifics:</td>
				<td class="nobr ft_ripspecifics" colspan="3">
					<select id="bitrate" name="encoding" class="ft_bitrate fti_advanced">
						<option value="">Bitrate</option>
<?	foreach ($Bitrates as $BitrateName) { ?>
						<option value="<?=display_str($BitrateName); ?>"<?Format::selected('encoding', $BitrateName)?>><?=display_str($BitrateName); ?></option>
<?	} ?>			</select>

					<select name="format" class="ft_format fti_advanced">
						<option value="">Format</option>
<?	foreach ($Formats as $FormatName) { ?>
						<option value="<?=display_str($FormatName); ?>"<?Format::selected('format', $FormatName)?>><?=display_str($FormatName); ?></option>
<?	} ?>			</select>
					<select name="media" class="ft_media fti_advanced">
						<option value="">Media</option>
<?	foreach ($Media as $MediaName) { ?>
						<option value="<?=display_str($MediaName); ?>"<?Format::selected('media', $MediaName)?>><?=display_str($MediaName); ?></option>
<?	} ?>
					</select>
					<select name="releasetype" class="ft_releasetype fti_advanced">
						<option value="">Release type</option>
<?	foreach ($ReleaseTypes as $ID=>$Type) { ?>
						<option value="<?=display_str($ID); ?>"<?Format::selected('releasetype', $ID)?>><?=display_str($Type); ?></option>
<?	} ?>
					</select>
				</td>
			</tr>
			<tr id="misc" class="ftr_advanced<?=$HideAdvanced?>">
				<td class="label">Misc:</td>
				<td class="nobr ft_misc" colspan="3">
					<select name="haslog" class="ft_haslog fti_advanced">
						<option value="">Has Log</option>
						<option value="1"<?Format::selected('haslog', '1')?>>Yes</option>
						<option value="0"<?Format::selected('haslog', '0')?>>No</option>
						<option value="100"<?Format::selected('haslog', '100')?>>100% only</option>
						<option value="-1"<?Format::selected('haslog', '-1')?>>&lt;100%/Unscored</option>
					</select>
					<select name="hascue" class="ft_hascue fti_advanced">
						<option value="">Has Cue</option>
						<option value="1"<?Format::selected('hascue', 1)?>>Yes</option>
						<option value="0"<?Format::selected('hascue', 0)?>>No</option>
					</select>
					<select name="scene" class="ft_scene fti_advanced">
						<option value="">Scene</option>
						<option value="1"<?Format::selected('scene', 1)?>>Yes</option>
						<option value="0"<?Format::selected('scene', 0)?>>No</option>
					</select>
					<select name="vanityhouse" class="ft_vanityhouse fti_advanced">
						<option value="">Vanity House</option>
						<option value="1"<?Format::selected('vanityhouse', 1)?>>Yes</option>
						<option value="0"<?Format::selected('vanityhouse', 0)?>>No</option>
					</select>
					<select name="freetorrent" class="ft_freetorrent fti_advanced">
						<option value="">Leech Status</option>
						<option value="1"<?Format::selected('freetorrent', 1)?>>Freeleech</option>
						<option value="2"<?Format::selected('freetorrent', 2)?>>Neutral Leech</option>
						<option value="3"<?Format::selected('freetorrent', 3)?>>Either</option>
						<option value="0"<?Format::selected('freetorrent', 0)?>>Normal</option>
					</select>
				</td>
			</tr>
			<tr id="search_terms" class="ftr_basic<?=$HideBasic?>">
				<td class="label">Search terms:</td>
				<td colspan="3" class="ftb_searchstr">
					<input type="text" spellcheck="false" size="40" name="searchstr" class="inputtext fti_basic" value="<?Format::form('searchstr')?>" />
				</td>
			</tr>
			<tr id="tagfilter">
				<td class="label"><span title="Use !tag to exclude tag" class="tooltip">Tags (comma-separated):</span></td>
				<td colspan="3" class="ft_taglist">
					<input type="text" size="40" id="tags" name="taglist" class="inputtext smaller" value="<?=str_replace('_', '.', display_str($TagListString)) /* Use aliased tags, not actual query string. */ ?>" />&nbsp;
					<input type="radio" name="tags_type" id="tags_type0" value="0"<?Format::selected('tags_type', 0, 'checked')?> /><label for="tags_type0"> Any</label>&nbsp;&nbsp;
					<input type="radio" name="tags_type" id="tags_type1" value="1"<?Format::selected('tags_type', 1, 'checked')?> /><label for="tags_type1"> All</label>
				</td>
			</tr>
			<tr id="order">
				<td class="label">Order by:</td>
				<td colspan="3" class="ft_order">
					<select name="order_by" style="width: auto;" class="ft_order_by">
						<option value="time"<?Format::selected('order_by', 'time')?>>Time added</option>
						<option value="year"<?Format::selected('order_by', 'year')?>>Year</option>
						<option value="size"<?Format::selected('order_by', 'size')?>>Size</option>
						<option value="snatched"<?Format::selected('order_by', 'snatched')?>>Snatched</option>
						<option value="seeders"<?Format::selected('order_by', 'seeders')?>>Seeders</option>
						<option value="leechers"<?Format::selected('order_by', 'leechers')?>>Leechers</option>
						<option value="random"<?Format::selected('order_by', 'random')?>>Random</option>
					</select>
					<select name="order_way" class="ft_order_way">
						<option value="desc"<?Format::selected('order_way', 'desc')?>>Descending</option>
						<option value="asc"<?Format::selected('order_way', 'asc')?>>Ascending</option>
					</select>
				</td>
			</tr>
			<tr id="search_group_results">
				<td class="label">
					<label for="group_results">Group by release:</label>
				</td>
				<td colspan="3" class="ft_group_results">
					<input type="checkbox" value="1" name="group_results" id="group_results"<?Format::selected('group_results', 1, 'checked')?> />
				</td>
			</tr>
		</table>
		<table class="layout cat_list ft_cat_list">
<?
$x = 0;
reset($Categories);
foreach ($Categories as $CatKey => $CatName) {
	if ($x % 7 == 0) {
		if ($x > 0) {
?>
			</tr>
<?		} ?>
			<tr>
<?
	}
	$x++;
?>
				<td>
					<input type="checkbox" name="filter_cat[<?=($CatKey + 1)?>]" id="cat_<?=($CatKey + 1)?>" value="1"<? if (isset($_GET['filter_cat'][$CatKey + 1])) { ?> checked="checked"<? } ?> />
					<label for="cat_<?=($CatKey + 1)?>"><?=$CatName?></label>
				</td>
<?
}
?>
			</tr>
		</table>
		<table class="layout cat_list<? if (empty($LoggedUser['ShowTags'])) { ?> hidden<? } ?>" id="taglist">
			<tr>
<?
$GenreTags = $Cache->get_value('genre_tags');
if (!$GenreTags) {
	$DB->query('
		SELECT Name
		FROM tags
		WHERE TagType = \'genre\'
		ORDER BY Name');
	$GenreTags = $DB->collect('Name');
	$Cache->cache_value('genre_tags', $GenreTags, 3600 * 6);
}

$x = 0;
foreach ($GenreTags as $Tag) {
?>
				<td width="12.5%"><a href="#" onclick="add_tag('<?=$Tag?>'); return false;"><?=$Tag?></a></td>
<?
	$x++;
	if ($x % 7 == 0) {
?>
			</tr>
			<tr>
<?
	}
}
if ($x % 7 != 0) { // Padding
?>
				<td colspan="<?=(7 - ($x % 7))?>"> </td>
<? } ?>
			</tr>
		</table>
		<table class="layout cat_list" width="100%">
			<tr>
				<td class="label">
					<a class="brackets" href="#" onclick="$('#taglist').gtoggle(); if (this.innerHTML == 'View tags') { this.innerHTML = 'Hide tags'; } else { this.innerHTML = 'View tags'; }; return false;"><?=(empty($LoggedUser['ShowTags']) ? 'View tags' : 'Hide tags')?></a>
				</td>
			</tr>
		</table>
		<div class="submit ft_submit">
			<span style="float: left;"><?=number_format($NumResults)?> Results</span>
			<input type="submit" value="Filter torrents" />
			<input type="hidden" name="action" id="ft_type" value="<?=($AdvancedSearch ? 'advanced' : 'basic')?>" />
			<input type="hidden" name="searchsubmit" value="1" />
			<input type="button" value="Reset" onclick="location.href = 'torrents.php<? if (isset($_GET['action']) && $_GET['action'] === 'advanced') { ?>?action=advanced<? } ?>'" />
			&nbsp;&nbsp;
<?	if ($Filtered) { ?>
			<input type="submit" name="setdefault" value="Make default" />
<?
	}

	if (!empty($LoggedUser['DefaultSearch'])) {
?>
			<input type="submit" name="cleardefault" value="Clear default" />
<?	} ?>
		</div>
	</div>
</div>
</form>
<?
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
?>
<div class="box pad" align="center">
	<h2>Your search did not match anything.</h2>
	<p>Make sure all names are spelled correctly, or try making your search less specific.</p>
	<p>You might like (beta): <? while (list($Tag) = $DB->next_record()) { ?><a href="torrents.php?taglist=<?=$Tag?>"><?=$Tag?></a><? } ?></p>
</div>
</div>
<? 
View::show_footer();die();
}

if ($NumResults < ($Page - 1) * TORRENTS_PER_PAGE + 1) {
	$LastPage = ceil($NumResults / TORRENTS_PER_PAGE);
	$Pages = Format::get_pages(0, $NumResults, TORRENTS_PER_PAGE);
?>
<div class="box pad" align="center">
	<h2>The requested page contains no matches.</h2>
	<p>You are requesting page <?=$Page?>, but the search returned only <?=number_format($LastPage) ?> pages.</p>
</div>
<div class="linkbox">Go to page <?=$Pages?></div>
</div>
<?
View::show_footer();die();
}

// List of pages
$Pages = Format::get_pages($Page, $NumResults, TORRENTS_PER_PAGE);

$Bookmarks = Bookmarks::all_bookmarks('torrent');
?>

<div class="linkbox"><?=$Pages?></div>

<table class="torrent_table cats <?=$GroupResults ? 'grouping' : 'no_grouping'?>" id="torrent_table">
	<tr class="colhead">
<?	if ($GroupResults) { ?>
		<td class="small"></td>
<?	} ?>
		<td class="small cats_col"></td>
		<td width="100%">Name / <a href="<?=header_link('year')?>">Year</a></td>
		<td>Files</td>
		<td><a href="<?=header_link('time')?>">Time</a></td>
		<td><a href="<?=header_link('size')?>">Size</a></td>
		<td class="sign snatches">
			<a href="<?=header_link('snatched')?>">
				<img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" class="tooltip" alt="Snatches" title="Snatches" />
			</a>
		</td>
		<td class="sign seeders">
			<a href="<?=header_link('seeders')?>">
				<img src="static/styles/<?=$LoggedUser['StyleName']?>/images/seeders.png" class="tooltip" alt="Seeders" title="Seeders" />
			</a>
		</td>
		<td class="sign leechers">
			<a href="<?=header_link('leechers')?>">
				<img src="static/styles/<?=$LoggedUser['StyleName']?>/images/leechers.png" class="tooltip" alt="Leechers" title="Leechers" />
			</a>
		</td>
	</tr>
<?

// Start printing torrent list
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
			if (isset($TorrentIDs[$T['ID']])) {
				$GroupTime = max($GroupTime, strtotime($T['Time']));
				$MaxSize = max($MaxSize, $T['Size']);
				$TotalLeechers += $T['Leechers'];
				$TotalSeeders += $T['Seeders'];
				$TotalSnatched += $T['Snatched'];
			}
		}
	} else {
		$Torrents = array($Result['id'] => $GroupInfo['Torrents'][$Result['id']]);
	}

	$TorrentTags = new Tags($GroupInfo['TagList']);

	if (!empty($ExtendedArtists[1])
		|| !empty($ExtendedArtists[4])
		|| !empty($ExtendedArtists[5])
		|| !empty($ExtendedArtists[6])
	) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName = Artists::display_artists($ExtendedArtists);
	} else {
		$DisplayName = '';
	}
	$SnatchedGroupClass = $GroupInfo['Flags']['IsSnatched'] ? ' snatched_group' : '';

	if ($GroupResults && (count($Torrents) > 1 || isset($GroupedCategories[$CategoryID - 1]))) {
		// These torrents are in a group
		$DisplayName .= "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"View torrent group\" dir=\"ltr\">$GroupName</a>";
		if ($GroupYear > 0) {
			$DisplayName .= " [$GroupYear]";
		}
		if ($GroupInfo['VanityHouse']) {
			$DisplayName .= ' [<abbr class="tooltip" title="This is a Vanity House release">VH</abbr>]';
		}
		$DisplayName .= ' ['.$ReleaseTypes[$ReleaseType].']';
?>
	<tr class="group<?=$SnatchedGroupClass?>">
<?
$ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1);
?>
		<td class="center">
			<div id="showimg_<?=$GroupID?>" class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
				<a href="#" class="tooltip show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event)" title="Collapse this group. Hold &quot;Ctrl&quot; while clicking to collapse all groups on this page."></a>
			</div>
		</td>
		<td class="center cats_col">
			<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($CategoryID)?> <?=$TorrentTags->css_name()?>">
			</div>
		</td>
		<td colspan="2" class="big_info">
<?	if ($LoggedUser['CoverArt']) { ?>
			<div class="group_image float_left clear">
				<? ImageTools::cover_thumb($GroupInfo['WikiImage'], $GroupInfo['CategoryID']) ?>
			</div>
<?	} ?>
			<div class="group_info clear">
				<?=$DisplayName?>
<?	if (in_array($GroupID, $Bookmarks)) { ?>
				<span class="remove_bookmark float_right"><a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="brackets" onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove bookmark</a></span>
<?	} else { ?>
				<span class="add_bookmark float_right"><a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="brackets" onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a></span>
<?	} ?>
				<br />
				<div class="tags"><?=$TorrentTags->format('torrents.php?'.$Action.'&amp;taglist=')?></div>
			</div>
		</td>
		<td class="nobr"><?=time_diff($GroupTime, 1)?></td>
		<td class="number_column nobr"><?=Format::get_size($MaxSize)?> (Max)</td>
		<td class="number_column"><?=number_format($TotalSnatched)?></td>
		<td class="number_column<?=($TotalSeeders == 0 ? ' r00' : '')?>"><?=number_format($TotalSeeders)?></td>
		<td class="number_column"><?=number_format($TotalLeechers)?></td>
	</tr>
<?
		$LastRemasterYear = '-';
		$LastRemasterTitle = '';
		$LastRemasterRecordLabel = '';
		$LastRemasterCatalogueNumber = '';
		$LastMedia = '';

		$EditionID = 0;
		$FirstUnknown = null;

		foreach ($Torrents as $TorrentID => $Data) {
			// All of the individual torrents in the group

			// If they're using the advanced search and have chosen enabled grouping, we just skip the torrents that don't check out
			if (!isset($TorrentIDs[$TorrentID])) {
				continue;
			}

			//Get report info for each torrent, use the cache if available, if not, add to it.
			$Reported = false;
			$Reports = Torrents::get_reports($TorrentID);
			if (count($Reports) > 0) {
				$Reported = true;
			}

			if ($Data['Remastered'] && !$Data['RemasterYear']) {
				$FirstUnknown = !isset($FirstUnknown);
			}
			$SnatchedTorrentClass = $Data['IsSnatched'] ? ' snatched_torrent' : '';

			if (isset($GroupedCategories[$CategoryID - 1])
					&& ($Data['RemasterTitle'] != $LastRemasterTitle
						|| $Data['RemasterYear'] != $LastRemasterYear
						|| $Data['RemasterRecordLabel'] != $LastRemasterRecordLabel
						|| $Data['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber)
					|| $FirstUnknown
					|| $Data['Media'] != $LastMedia
			) {
				$EditionID++;

?>
	<tr class="group_torrent groupid_<?=$GroupID?> edition<?=$SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1 ? ' hidden' : '')?>">
		<td colspan="9" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event);" class="tooltip" title="Collapse this edition. Hold &quot;Ctrl&quot; while clicking to collapse all editions in this torrent group.">&minus;</a> <?=Torrents::edition_string($Data, $GroupInfo)?></strong></td>
	</tr>
<?
			}
			$LastRemasterTitle = $Data['RemasterTitle'];
			$LastRemasterYear = $Data['RemasterYear'];
			$LastRemasterRecordLabel = $Data['RemasterRecordLabel'];
			$LastRemasterCatalogueNumber = $Data['RemasterCatalogueNumber'];
			$LastMedia = $Data['Media'];
?>
	<tr class="group_torrent groupid_<?=$GroupID?> edition_<?=$EditionID?><?=$SnatchedTorrentClass . $SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1 ? ' hidden' : '')?>">
		<td colspan="3">
			<span>
				[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download"><?=$Data['HasFile'] ? 'DL' : 'Missing'?></a>
<?			if (Torrents::can_use_token($Data)) { ?>
				| <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
				| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
			</span>
			&raquo; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Data)?><? if ($Reported) { ?> / <strong class="torrent_label tl_reported">Reported</strong><? } ?></a>
		</td>
		<td><?=$Data['FileCount']?></td>
		<td class="nobr"><?=time_diff($Data['Time'], 1)?></td>
		<td class="number_column nobr"><?=Format::get_size($Data['Size'])?></td>
		<td class="number_column"><?=number_format($Data['Snatched'])?></td>
		<td class="number_column<?=($Data['Seeders'] == 0) ? ' r00' : ''?>"><?=number_format($Data['Seeders'])?></td>
		<td class="number_column"><?=number_format($Data['Leechers'])?></td>
	</tr>
<?
		}
	} else {
		// Viewing a type that does not require grouping

		list($TorrentID, $Data) = each($Torrents);
		$DisplayName .= "<a href=\"torrents.php?id=$GroupID&amp;torrentid=$TorrentID#torrent$TorrentID\" class=\"tooltip\" title=\"View torrent\" dir=\"ltr\">$GroupName</a>";
		if (isset($GroupedCategories[$CategoryID - 1])) {
			if ($GroupYear) {
				$DisplayName .= " [$GroupYear]";
			}
			if ($CategoryID == 1 && $ReleaseType > 0) {
				$DisplayName .= ' ['.$ReleaseTypes[$ReleaseType].']';
			}
			$ExtraInfo = Torrents::torrent_info($Data, true, true);
		} elseif ($Data['IsSnatched']) {
			$ExtraInfo = Format::torrent_label('Snatched!');
		} else {
			$ExtraInfo = '';
		}
		$SnatchedTorrentClass = $Data['IsSnatched'] ? ' snatched_torrent' : '';
?>
	<tr class="torrent<?=$SnatchedTorrentClass . $SnatchedGroupClass?>">
<?		if ($GroupResults) { ?>
		<td></td>
<?		} ?>
		<td class="center cats_col">
			<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($CategoryID)?> <?=$TorrentTags->css_name()?>"></div>
		</td>
		<td class="big_info">
<?		if ($LoggedUser['CoverArt']) { ?>
			<div class="group_image float_left clear">
				<?=ImageTools::cover_thumb($GroupInfo['WikiImage'], $CategoryID) ?>
			</div>
<?		} ?>
			<div class="group_info clear">
				<span>
					[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?		if (Torrents::can_use_token($Data)) { ?>
					| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?		} ?>
					| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
				</span>
				<?=$DisplayName?>
				<div class="torrent_info"><?=$ExtraInfo?></div>
				<div class="tags"><?=$TorrentTags->format("torrents.php?$Action&amp;taglist=")?></div>
			</div>
		</td>
		<td><?=$Data['FileCount']?></td>
		<td class="nobr"><?=time_diff($Data['Time'], 1)?></td>
		<td class="number_column nobr"><?=Format::get_size($Data['Size'])?></td>
		<td class="number_column"><?=number_format($Data['Snatched'])?></td>
		<td class="number_column<?=($Data['Seeders'] == 0) ? ' r00' : ''?>"><?=number_format($Data['Seeders'])?></td>
		<td class="number_column"><?=number_format($Data['Leechers'])?></td>
	</tr>
<?
	}
}
?>
</table>
<div class="linkbox"><?=$Pages?></div>
</div>
<? View::show_footer(); ?>
