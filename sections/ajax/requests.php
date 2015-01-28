<?php
$SphQL = new SphinxqlQuery();
$SphQL->select('id, votes, bounty')->from('requests, requests_delta');

$SortOrders = array(
	'votes' => 'votes',
	'bounty' => 'bounty',
	'lastvote' => 'lastvote',
	'filled' => 'timefilled',
	'year' => 'year',
	'created' => 'timeadded',
	'random' => false);

if (empty($_GET['order']) || !isset($SortOrders[$_GET['order']])) {
	$_GET['order'] = 'created';
}
$OrderBy = $_GET['order'];

if (!empty($_GET['sort']) && $_GET['sort'] === 'asc') {
	$OrderWay = 'asc';
} else {
	$_GET['sort'] = 'desc';
	$OrderWay = 'desc';
}
$NewSort = $_GET['sort'] === 'asc' ? 'desc' : 'asc';

if ($OrderBy === 'random') {
	$SphQL->order_by('RAND()', '');
	unset($_GET['page']);
} else {
	$SphQL->order_by($SortOrders[$OrderBy], $OrderWay);
}

$Submitted = !empty($_GET['submit']);

//Paranoia
if (!empty($_GET['userid'])) {
	if (!is_number($_GET['userid'])) {
		json_die("failure");
	}
	$UserInfo = Users::user_info($_GET['userid']);
	if (empty($UserInfo)) {
		json_die("failure");
	}
	$Perms = Permissions::get_permissions($UserInfo['PermissionID']);
	$UserClass = $Perms['Class'];
}
$BookmarkView = false;

if (empty($_GET['type'])) {
	$Title = 'Requests';
	if (empty($_GET['showall'])) {
		$SphQL->where('visible', 1);
	}
} else {
	switch ($_GET['type']) {
		case 'created':
			if (!empty($UserInfo)) {
				if (!check_paranoia('requestsvoted_list', $UserInfo['Paranoia'], $Perms['Class'], $UserInfo['ID'])) {
					json_die("failure");
				}
				$Title = "Requests created by $UserInfo[Username]";
				$SphQL->where('userid', $UserInfo['ID']);
			} else {
				$Title = 'My requests';
				$SphQL->where('userid', $LoggedUser['ID']);
			}
			break;
		case 'voted':
			if (!empty($UserInfo)) {
				if (!check_paranoia('requestsvoted_list', $UserInfo['Paranoia'], $Perms['Class'], $UserInfo['ID'])) {
					json_die("failure");
				}
				$Title = "Requests voted for by $UserInfo[Username]";
				$SphQL->where('voter', $UserInfo['ID']);
			} else {
				$Title = 'Requests I have voted on';
				$SphQL->where('voter', $LoggedUser['ID']);
			}
			break;
		case 'filled':
			if (!empty($UserInfo)) {
				if (!check_paranoia('requestsfilled_list', $UserInfo['Paranoia'], $Perms['Class'], $UserInfo['ID'])) {
					json_die("failure");
				}
				$Title = "Requests filled by $UserInfo[Username]";
				$SphQL->where('fillerid', $UserInfo['ID']);
			} else {
				$Title = 'Requests I have filled';
				$SphQL->where('fillerid', $LoggedUser['ID']);
			}
			break;
		case 'bookmarks':
			$Title = 'Your bookmarked requests';
			$BookmarkView = true;
			$SphQL->where('bookmarker', $LoggedUser['ID']);
			break;
		default:
			json_die("failure");
	}
}

if ($Submitted && empty($_GET['show_filled'])) {
	$SphQL->where('torrentid', 0);
}

$EnableNegation = false; // Sphinx needs at least one positive search condition to support the NOT operator

if (!empty($_GET['formats'])) {
	$FormatArray = $_GET['formats'];
	if (count($FormatArray) !== count($Formats)) {
		$FormatNameArray = array();
		foreach ($FormatArray as $Index => $MasterIndex) {
			if (isset($Formats[$MasterIndex])) {
				$FormatNameArray[$Index] = '"' . strtr(Sphinxql::sph_escape_string($Formats[$MasterIndex]), '-.', '  ') . '"';
			}
		}
		if (count($FormatNameArray) >= 1) {
			$EnableNegation = true;
			if (!empty($_GET['formats_strict'])) {
				$SearchString = '(' . implode(' | ', $FormatNameArray) . ')';
			} else {
				$SearchString = '(any | ' . implode(' | ', $FormatNameArray) . ')';
			}
			$SphQL->where_match($SearchString, 'formatlist', false);
		}
	}
}

if (!empty($_GET['media'])) {
	$MediaArray = $_GET['media'];
	if (count($MediaArray) !== count($Media)) {
		$MediaNameArray = array();
		foreach ($MediaArray as $Index => $MasterIndex) {
			if (isset($Media[$MasterIndex])) {
				$MediaNameArray[$Index] = '"' . strtr(Sphinxql::sph_escape_string($Media[$MasterIndex]), '-.', '  ') . '"';
			}
		}

		if (count($MediaNameArray) >= 1) {
			$EnableNegation = true;
			if (!empty($_GET['media_strict'])) {
				$SearchString = '(' . implode(' | ', $MediaNameArray) . ')';
			} else {
				$SearchString = '(any | ' . implode(' | ', $MediaNameArray) . ')';
			}
			$SphQL->where_match($SearchString, 'medialist', false);
		}
	}
}

if (!empty($_GET['bitrates'])) {
	$BitrateArray = $_GET['bitrates'];
	if (count($BitrateArray) !== count($Bitrates)) {
		$BitrateNameArray = array();
		foreach ($BitrateArray as $Index => $MasterIndex) {
			if (isset($Bitrates[$MasterIndex])) {
				$BitrateNameArray[$Index] = '"' . strtr(Sphinxql::sph_escape_string($Bitrates[$MasterIndex]), '-.', '  ') . '"';
			}
		}

		if (count($BitrateNameArray) >= 1) {
			$EnableNegation = true;
			if (!empty($_GET['bitrate_strict'])) {
				$SearchString = '(' . implode(' | ', $BitrateNameArray) . ')';
			} else {
				$SearchString = '(any | ' . implode(' | ', $BitrateNameArray) . ')';
			}
			$SphQL->where_match($SearchString, 'bitratelist', false);
		}
	}
}

if (!empty($_GET['search'])) {
	$SearchString = trim($_GET['search']);
	if ($SearchString !== '') {
		$SearchWords = array('include' => array(), 'exclude' => array());
		$Words = explode(' ', $SearchString);
		foreach ($Words as $Word) {
			$Word = trim($Word);
			// Skip isolated hyphens to enable "Artist - Title" searches
			if ($Word === '-') {
				continue;
			}
			if ($Word[0] === '!' && strlen($Word) >= 2) {
				if (strpos($Word, '!', 1) === false) {
					$SearchWords['exclude'][] = $Word;
				} else {
					$SearchWords['include'][] = $Word;
					$EnableNegation = true;
				}
			} elseif ($Word !== '') {
				$SearchWords['include'][] = $Word;
				$EnableNegation = true;
			}
		}
	}
}

if (!isset($_GET['tags_type']) || $_GET['tags_type'] === '1') {
	$TagType = 1;
	$_GET['tags_type'] = '1';
} else {
	$TagType = 0;
	$_GET['tags_type'] = '0';
}
if (!empty($_GET['tags'])) {
	$SearchTags = array('include' => array(), 'exclude' => array());
	$Tags = explode(',', $_GET['tags']);
	foreach ($Tags as $Tag) {
		$Tag = trim($Tag);
		if ($Tag[0] === '!' && strlen($Tag) >= 2) {
			if (strpos($Tag, '!', 1) === false) {
				$SearchTags['exclude'][] = $Tag;
			} else {
				$SearchTags['include'][] = $Tag;
				$EnableNegation = true;
			}
		} elseif ($Tag !== '') {
			$SearchTags['include'][] = $Tag;
			$EnableNegation = true;
		}
	}

	$TagFilter = Tags::tag_filter_sph($SearchTags, $EnableNegation, $TagType);

	if (!empty($TagFilter['predicate'])) {
		$SphQL->where_match($TagFilter['predicate'], 'taglist', false);
	}
} elseif (!isset($_GET['tags_type']) || $_GET['tags_type'] !== '0') {
	$_GET['tags_type'] = 1;
} else {
	$_GET['tags_type'] = 0;
}

if (isset($SearchWords)) {
	$QueryParts = array();
	if (!$EnableNegation && !empty($SearchWords['exclude'])) {
		$SearchWords['include'] = array_merge($SearchWords['include'], $SearchWords['exclude']);
		unset($SearchWords['exclude']);
	}
	foreach ($SearchWords['include'] as $Word) {
		$QueryParts[] = Sphinxql::sph_escape_string($Word);
	}
	if (!empty($SearchWords['exclude'])) {
		foreach ($SearchWords['exclude'] as $Word) {
			$QueryParts[] = '!' . Sphinxql::sph_escape_string(substr($Word, 1));
		}
	}
	if (!empty($QueryParts)) {
		$SearchString = implode(' ', $QueryParts);
		$SphQL->where_match($SearchString, '*', false);
	}
}

if (!empty($_GET['filter_cat'])) {
	$CategoryArray = array_keys($_GET['filter_cat']);
	if (count($CategoryArray) !== count($Categories)) {
		foreach ($CategoryArray as $Key => $Index) {
			if (!isset($Categories[$Index - 1])) {
				unset($CategoryArray[$Key]);
			}
		}
		if (count($CategoryArray) >= 1) {
			$SphQL->where('categoryid', $CategoryArray);
		}
	}
}

if (!empty($_GET['releases'])) {
	$ReleaseArray = $_GET['releases'];
	if (count($ReleaseArray) !== count($ReleaseTypes)) {
		foreach ($ReleaseArray as $Index => $Value) {
			if (!isset($ReleaseTypes[$Value])) {
				unset($ReleaseArray[$Index]);
			}
		}
		if (count($ReleaseArray) >= 1) {
			$SphQL->where('releasetype', $ReleaseArray);
		}
	}
}

if (!empty($_GET['requestor'])) {
	if (is_number($_GET['requestor'])) {
		$SphQL->where('userid', $_GET['requestor']);
	} else {
		error(404);
	}
}

if (isset($_GET['year'])) {
	if (is_number($_GET['year']) || $_GET['year'] === '0') {
		$SphQL->where('year', $_GET['year']);
	} else {
		error(404);
	}
}

if (!empty($_GET['page']) && is_number($_GET['page']) && $_GET['page'] > 0) {
	$Page = $_GET['page'];
	$Offset = ($Page - 1) * REQUESTS_PER_PAGE;
	$SphQL->limit($Offset, REQUESTS_PER_PAGE, $Offset + REQUESTS_PER_PAGE);
} else {
	$Page = 1;
	$SphQL->limit(0, REQUESTS_PER_PAGE, REQUESTS_PER_PAGE);
}

$SphQLResult = $SphQL->query();
$NumResults = (int)$SphQLResult->get_meta('total_found');
if ($NumResults > 0) {
	$SphRequests = $SphQLResult->to_array('id');
	if ($OrderBy === 'random') {
		$NumResults = count($RequestIDs);
	}
	if ($NumResults > REQUESTS_PER_PAGE) {
		if (($Page - 1) * REQUESTS_PER_PAGE > $NumResults) {
			$Page = 0;
		}
	}
}

if ($NumResults == 0) {
	json_print("success", array(
		'currentPage' => 1,
		'pages' => 1,
		'results' => array()
	));
} else {
	$JsonResults = array();
	$TimeCompare = 1267643718; // Requests v2 was implemented 2010-03-03 20:15:18
	$Requests = Requests::get_requests(array_keys($SphRequests));
	foreach ($SphRequests as $RequestID => $SphRequest) {
		$Request = $Requests[$RequestID];
		$VoteCount = $SphRequest['votes'];
		$Bounty = $SphRequest['bounty'] * 1024; // Sphinx stores bounty in kB
		$Requestor = Users::user_info($Request['UserID']);
		$Filler = $Request['FillerID'] ? Users::user_info($Request['FillerID']) : null;

		if ($Request['CategoryID'] == 0) {
			$CategoryName = 'Unknown';
		} else {
			$CategoryName = $Categories[$Request['CategoryID'] - 1];
		}

		$JsonArtists = array();
		if ($CategoryName == 'Music') {
			$ArtistForm = Requests::get_artists($RequestID);
			$JsonArtists = array_values($ArtistForm);
		}

		$Tags = $Request['Tags'];

		$JsonResults[] = array(
			'requestId' => (int)$RequestID,
			'requestorId' => (int)$Requestor['ID'],
			'requestorName' => $Requestor['Username'],
			'timeAdded' => $Request['TimeAdded'],
			'lastVote' => $Request['LastVote'],
			'voteCount' => (int)$VoteCount,
			'bounty' => (int)$Bounty,
			'categoryId' => (int)$Request['CategoryID'],
			'categoryName' => $CategoryName,
			'artists' => $JsonArtists,
			'title' => $Request['Title'],
			'year' => (int)$Request['Year'],
			'image' => $Request['Image'],
			'description' => $Request['Description'],
			'recordLabel' => $Request['RecordLabel'],
			'catalogueNumber' => $Request['CatalogueNumber'],
			'releaseType' => $ReleaseTypes[$Request['ReleaseType']],
			'bitrateList' => $Request['BitrateList'],
			'formatList' => $Request['FormatList'],
			'mediaList' => $Request['MediaList'],
			'logCue' => $Request['LogCue'],
			'isFilled' => ($Request['TorrentID'] > 0),
			'fillerId' => (int)$Request['FillerID'],
			'fillerName' => $Filler ? $Filler['Username'] : '',
			'torrentId' => (int)$Request['TorrentID'],
			'timeFilled' => $Request['TimeFilled'] == 0 ? '' : $Request['TimeFilled']
		);
	}

	json_print("success", array(
		'currentPage' => intval($Page),
		'pages' => ceil($NumResults / REQUESTS_PER_PAGE),
		'results' => $JsonResults
	));
}
?>
