<?

authorize(true);
include(SERVER_ROOT.'/sections/requests/functions.php');

$Queries = array();

$OrderWays = array('year', 'votes', 'bounty', 'created', 'lastvote', 'filled');
list($Page,$Limit) = page_limit(REQUESTS_PER_PAGE);
$Submitted = !empty($_GET['submit']);
					
//Paranoia					
$UserInfo = user_info((int)$_GET['userid']);
$Perms = get_permissions($UserInfo['PermissionID']);
$UserClass = $Perms['Class'];

$BookmarkView = false;

if(empty($_GET['type'])) { 
	$Title = 'Requests';
	if(!check_perms('site_see_old_requests') || empty($_GET['showall'])) {
		$SS->set_filter('visible', array(1));
	}
} else {
	switch($_GET['type']) {
		case 'created':
			$Title = 'My requests';
			$SS->set_filter('userid', array($LoggedUser['ID']));
			break;
		case 'voted':
			if(!empty($_GET['userid'])) {
				if(is_number($_GET['userid'])) {
					if (!check_paranoia('requestsvoted_list', $UserInfo['Paranoia'], $Perms['Class'], $_GET['userid'])) {
						print json_encode(array('status' => 'failure'));
						die();
					}
					$Title = "Requests voted for by ".$UserInfo['Username'];
					$SS->set_filter('voter', array($_GET['userid']));
				} else {
					print json_encode(array('status' => 'failure'));
					die();
				}
			} else {
				$Title = "Requests I've voted on";
				$SS->set_filter('voter', array($LoggedUser['ID']));
			}
			break;
		case 'filled':
			if(empty($_GET['userid']) || !is_number($_GET['userid'])) {
				print json_encode(array('status' => 'failure'));
				die();
			} else {
				if (!check_paranoia('requestsfilled_list', $UserInfo['Paranoia'], $Perms['Class'], $_GET['userid'])) {
					print json_encode(array('status' => 'failure'));
					die();
				}
				$Title = "Requests filled by ".$UserInfo['Username'];
				$SS->set_filter('fillerid', array($_GET['userid']));
			}
			break;
		case 'bookmarks':
			$Title = 'Your bookmarked requests';
			$BookmarkView = true;
			$SS->set_filter('bookmarker', array($LoggedUser['ID']));
			break;
		default:
			print json_encode(array('status' => 'failure'));
			die();
	}
}

if($Submitted && empty($_GET['show_filled'])) {
	$SS->set_filter('torrentid', array(0));
}

if(!empty($_GET['search'])) {
	$Words = explode(' ', $_GET['search']);
	foreach($Words as $Key => &$Word) {
		if($Word[0] == '!' && strlen($Word) > 2) {
			if(strpos($Word,'!',1) === false) {
				$Word = '!'.$SS->EscapeString(substr($Word,1));
			} else {
				$Word = $SS->EscapeString($Word);
			}
		} elseif(strlen($Word) >= 2) {
			$Word = $SS->EscapeString($Word);
		} else {
			unset($Words[$Key]);
		}
	}
	if(!empty($Words)) {
		$Queries[] = "@* ".implode(' ', $Words);
	}
}

if(!empty($_GET['tags'])){
	$Tags = explode(',', $_GET['tags']);
	$TagNames = array();
	foreach ($Tags as $Tag) {
		$Tag = sanitize_tag($Tag);
		if(!empty($Tag)) {
			$TagNames[] = $Tag;
		}
	}
	$Tags = get_tags($TagNames);
}

if(empty($_GET['tags_type']) && !empty($Tags)) {
	$_GET['tags_type'] = '0';
	$SS->set_filter('tagid', array_keys($Tags));
} elseif(!empty($Tags)) {
	foreach(array_keys($Tags) as $Tag) {
		$SS->set_filter('tagid', array($Tag));
	}
} else {
	$_GET['tags_type'] = '1';
}

if(!empty($_GET['filter_cat'])) {
	$Keys = array_keys($_GET['filter_cat']);
	$SS->set_filter('categoryid', $Keys);
}

if(!empty($_GET['releases'])) {
	$ReleaseArray = $_GET['releases'];
	if(count($ReleaseArray) != count($ReleaseTypes)) {
		foreach($ReleaseArray as $Index => $Value) {
			if(!is_number($Value)) {
				print json_encode(array('status' => 'failure'));
				die();
			}
		}
		
		$SS->set_filter('releasetype', $ReleaseArray);
	}
}

if(!empty($_GET['formats'])) {
	$FormatArray = $_GET['formats'];
	if(count($FormatArray) != count($Formats)) {
		$FormatNameArray = array();
		foreach($FormatArray as $Index => $MasterIndex) {
			if(array_key_exists($Index, $Formats)) {
				$FormatNameArray[$Index] = $Formats[$MasterIndex];
			} else {
				//Hax
				print json_encode(array('status' => 'failure'));
				die();
			}
		}
		
		$Queries[]='@formatlist '.implode(' | ', $FormatNameArray);
	}
}

if(!empty($_GET['media'])) {
	$MediaArray = $_GET['media'];
	if(count($MediaArray) != count($Media)) {
		$MediaNameArray = array();
		foreach($MediaArray as $Index => $MasterIndex) {
			if(array_key_exists($Index, $Media)) {
				$MediaNameArray[$Index] = $Media[$MasterIndex];
			} else {
				//Hax
				print json_encode(array('status' => 'failure'));
				die();
			}
		}

		$Queries[]='@medialist '.implode(' | ', $MediaNameArray);
	}
}

if(!empty($_GET['bitrates'])) {
	$BitrateArray = $_GET['bitrates'];
	if(count($BitrateArray) != count($Bitrates)) {
		$BitrateNameArray = array();
		foreach($BitrateArray as $Index => $MasterIndex) {
			if(array_key_exists($Index, $Bitrates)) {
				$BitrateNameArray[$Index] = $SS->EscapeString($Bitrates[$MasterIndex]);
			} else {
				//Hax
				print json_encode(array('status' => 'failure'));
				die();
			}
		}

		$Queries[]='@bitratelist '.implode(' | ', $BitrateNameArray);
	}
}

if(!empty($_GET['requestor']) && check_perms('site_see_old_requests')) {
	if(is_number($_GET['requestor'])) {
		$SS->set_filter('userid', array($_GET['requestor']));
	} else {
		print json_encode(array('status' => 'failure'));
		die();
	}
}

if(isset($_GET['year'])) {
	if(is_number($_GET['year']) || $_GET['year'] == 0) {
		$SS->set_filter('year', array($_GET['year']));
	} else {
		print json_encode(array('status' => 'failure'));
		die();
	}
}

if(!empty($_GET['page']) && is_number($_GET['page'])) {
	$Page = min($_GET['page'], 50000/REQUESTS_PER_PAGE);
	$SS->limit(($Page - 1) * REQUESTS_PER_PAGE, REQUESTS_PER_PAGE, 50000);
} else {
	$Page = 1;
	$SS->limit(0, REQUESTS_PER_PAGE, 50000);
}

if(empty($_GET['order'])) {
	$CurrentOrder = 'created';
	$CurrentSort = 'desc';
	$Way = SPH_SORT_ATTR_DESC;
	$NewSort = 'asc';
} else {
	if(in_array($_GET['order'], $OrderWays)) {
		$CurrentOrder = $_GET['order'];
		if($_GET['sort'] == 'asc' || $_GET['sort'] == 'desc') {
			$CurrentSort = $_GET['sort'];
			$Way = ($CurrentSort == 'asc' ? SPH_SORT_ATTR_ASC : SPH_SORT_ATTR_DESC);
			$NewSort = ($_GET['sort'] == 'asc' ? 'desc' : 'asc');
		} else {
			print json_encode(array('status' => 'failure'));
			die();
		}
	} else {
		print json_encode(array('status' => 'failure'));
		die();
	}
}

switch($CurrentOrder) {
	case 'votes' :
		$OrderBy = "Votes";
		break;
	case 'bounty' :
		$OrderBy = "Bounty";
		break;
	case 'created' :
		$OrderBy = "TimeAdded";
		break;
	case 'lastvote' :
		$OrderBy = "LastVote";
		break;
	case 'filled' :
		$OrderBy = "TimeFilled";
		break;
	case 'year' :
		$OrderBy = "Year";
		break;
	default :
		$OrderBy = "TimeAdded";
		break;
}
//print($Way); print($OrderBy); die();
$SS->SetSortMode($Way, $OrderBy);

if(count($Queries) > 0) {
	$Query = implode(' ',$Queries);
} else {
	$Query='';
}

$SS->set_index('requests requests_delta');
$SphinxResults = $SS->search($Query, '', 0, array(), '', '');
$NumResults = $SS->TotalResults;
//We don't use sphinxapi's default cache searcher, we use our own functions

if(!empty($SphinxResults['notfound'])) {
	$SQLResults = get_requests($SphinxResults['notfound']);
	if(is_array($SQLResults['notfound'])) {
		//Something wasn't found in the db, remove it from results
		reset($SQLResults['notfound']);
		foreach($SQLResults['notfound'] as $ID) {
			unset($SQLResults['matches'][$ID]);
			unset($SphinxResults['matches'][$ID]);
		}
	}
	
	// Merge SQL results with memcached results
	foreach($SQLResults['matches'] as $ID => $SQLResult) {
		$SphinxResults['matches'][$ID] = $SQLResult;
		
		//$Requests['matches'][$ID] = array_merge($Requests['matches'][$ID], $SQLResult);
		//We ksort because depending on the filter modes, we're given our data in an unpredictable order
		//ksort($Requests['matches'][$ID]);
	}
}

$Requests = $SphinxResults['matches'];

if ($NumResults == 0) {
	print json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'currentPage' => 1,
				'pages' => 1,
				'results' => array()
			)
			)
		);
	die();
} else {
	$JsonResults = array();
	$TimeCompare = 1267643718; // Requests v2 was implemented 2010-03-03 20:15:18
	foreach ($Requests as $RequestID => $Request) {
		
		//list($BitrateList, $CatalogueNumber, $CategoryID, $Description, $FillerID, $FormatList, $RequestID, $Image, $LogCue, $MediaList, $ReleaseType, 
		//	$Tags, $TimeAdded, $TimeFilled, $Title, $TorrentID, $RequestorID, $RequestorName, $Year, $RequestID, $Categoryid, $FillerID, $LastVote, 
		//	$ReleaseType, $TagIDs, $TimeAdded, $TimeFilled, $TorrentID, $RequestorID, $Voters) = array_values($Request);
		
		list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Year, $Image, $Description, $CatalogueNumber, 
			$ReleaseType, $BitrateList, $FormatList, $MediaList, $LogCue, $FillerID, $FillerName, $TorrentID, $TimeFilled) = $Request;
			
		$RequestVotes = get_votes_array($RequestID);
		
		$VoteCount = count($RequestVotes['Voters']);
		
		if($CategoryID == 0) {
			$CategoryName = "Unknown";
		} else {
			$CategoryName = $Categories[$CategoryID - 1];
		}
		
		$JsonArtists = array();
		if($CategoryName == "Music") {
			$ArtistForm = get_request_artists($RequestID);
			$JsonArtists = array_values($ArtistForm);
		}

		$Tags = $Request['Tags'];
		
		$JsonResults[] = array(
			'requestId' => (int) $RequestID,
			'requestorId' => (int) $RequestorID,
			'requestorName' => $RequestorName,
			'timeAdded' => $TimeAdded,
			'lastVote' => $LastVote,
			'voteCount' => $VoteCount,
			'bounty' => $RequestVotes['TotalBounty'],
			'categoryId' => (int) $CategoryID,
			'categoryName' => $CategoryName,
			'artists' => $JsonArtists,
			'title' => $Title,
			'year' => (int) $Year,
			'image' => $Image,
			'description' => $Description,
			'catalogueNumber' => $CatalogueNumber,
			'releaseType' => $ReleaseType,
			'bitrateList' => $BitrateList,
			'formatList' => $FormatList,
			'mediaList' => $MediaList,
			'logCue' => $LogCue,
			'isFilled' => ($TorrentID > 0),
			'fillerId' => (int) $FillerID,
			'fillerName' => $FillerName == 0 ? "" : $FillerName,
			'torrentId' => (int) $TorrentID,
			'timeFilled' => $TimeFilled == 0 ? "" : $TimeFilled
		);
	}

	print
		json_encode(
			array(
				'status' => 'success',
				'response' => array(
					'currentPage' => intval($Page),
					'pages' => ceil($NumResults/REQUESTS_PER_PAGE),
					'results' => $JsonResults
				)
			)
		);
}
?>
