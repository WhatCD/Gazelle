<?

authorize(true);

include(SERVER_ROOT.'/sections/bookmarks/functions.php');
include(SERVER_ROOT.'/sections/torrents/functions.php');

// Search by infohash
if(!empty($_GET['searchstr']) || !empty($_GET['groupname'])) {
	if(!empty($_GET['searchstr'])) {
		$InfoHash = $_GET['searchstr'];
	} else {
		$InfoHash = $_GET['groupname'];
	}
	
	if($InfoHash = is_valid_torrenthash($InfoHash)) {
		$InfoHash = db_string(pack("H*", $InfoHash));
		$DB->query("SELECT ID,GroupID FROM torrents WHERE info_hash='$InfoHash'");
		if($DB->record_count() > 0) {
			list($ID, $GroupID) = $DB->next_record();
			header('Location: torrents.php?id='.$GroupID.'&torrentid='.$ID);
			die();
		}
	}
}

// Setting default search options
if(!empty($_GET['setdefault'])) {
	$UnsetList = array('page','setdefault');
	$UnsetRegexp = '/(&|^)('.implode('|',$UnsetList).')=.*?(&|$)/i';

	$DB->query("SELECT SiteOptions FROM users_info WHERE UserID='".db_string($LoggedUser['ID'])."'");
	list($SiteOptions)=$DB->next_record(MYSQLI_NUM, false);
	if(!empty($SiteOptions)) {
		$SiteOptions = unserialize($SiteOptions);
	} else {
		$SiteOptions = array();
	}
	$SiteOptions['DefaultSearch'] = preg_replace($UnsetRegexp,'',$_SERVER['QUERY_STRING']);
	$DB->query("UPDATE users_info SET SiteOptions='".db_string(serialize($SiteOptions))."' WHERE UserID='".db_string($LoggedUser['ID'])."'");
	$Cache->begin_transaction('user_info_heavy_'.$UserID);
	$Cache->update_row(false, array('DefaultSearch'=>$SiteOptions['DefaultSearch']));
	$Cache->commit_transaction(0);

// Clearing default search options
} elseif(!empty($_GET['cleardefault'])) {
	$DB->query("SELECT SiteOptions FROM users_info WHERE UserID='".db_string($LoggedUser['ID'])."'");
	list($SiteOptions)=$DB->next_record(MYSQLI_NUM, false);
	$SiteOptions=unserialize($SiteOptions);
	$SiteOptions['DefaultSearch']='';
	$DB->query("UPDATE users_info SET SiteOptions='".db_string(serialize($SiteOptions))."' WHERE UserID='".db_string($LoggedUser['ID'])."'");
	$Cache->begin_transaction('user_info_heavy_'.$UserID);
	$Cache->update_row(false, array('DefaultSearch'=>''));
	$Cache->commit_transaction(0);

// Use default search options
} elseif((empty($_SERVER['QUERY_STRING']) || (count($_GET) == 1 && isset($_GET['page']))) && !empty($LoggedUser['DefaultSearch'])) {
	if(!empty($_GET['page'])) {
		$Page = $_GET['page'];
		parse_str($LoggedUser['DefaultSearch'],$_GET);
		$_GET['page'] = $Page;
	} else {
		parse_str($LoggedUser['DefaultSearch'],$_GET);
	}
}

array_pop($Bitrates); // remove 'other'
$SearchBitrates = array_merge($Bitrates, array('v0','v1','v2','24bit'));

foreach($SearchBitrates as $ID=>$Val) {
	$SearchBitrates[$ID]=strtolower($Val);
}
foreach($Formats as $ID=>$Val) {
	$SearchFormats[$ID]=strtolower($Val);
}

$Queries = array();

//Simple search
if(!empty($_GET['searchstr'])) {
	$Words = explode(' ',strtolower($_GET['searchstr']));
	$FilterBitrates = array_intersect($Words, $SearchBitrates);
	if(count($FilterBitrates)>0) {
		$Queries[]='@encoding '.implode(' ',$FilterBitrates);
	}
	
	$FilterFormats = array_intersect($Words, $SearchFormats);
	if(count($FilterFormats)>0) {
		$Queries[]='@format '.implode(' ',$FilterFormats);
	}
	
	if(in_array('100%', $Words)) {
		$_GET['haslog'] = '100';
		unset($Words[array_search('100%',$Words)]);
	}
	
	$Words = array_diff($Words, $FilterBitrates, $FilterFormats);
	if(!empty($Words)) {
		foreach($Words as $Key => &$Word) {
			if($Word[0] == '!' && strlen($Word) >= 3 && count($Words) >= 2) {
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
		unset($Word);
		$Words = trim(implode(' ',$Words));
		if(!empty($Words)) {
			$Queries[]='@(groupname,artistname,yearfulltext) '.$Words;
		}
	}
}

if(!empty($_GET['taglist'])) {
	$_GET['taglist'] = str_replace('.','_',$_GET['taglist']);
	$TagList = explode(',',$_GET['taglist']);
	$TagListEx = array();
	foreach($TagList as $Key => &$Tag) {
		$Tag = trim($Tag);
		if(strlen($Tag) >= 2) {
			if($Tag[0] == '!' && strlen($Tag) >= 3) {
				$TagListEx[] = '!'.$SS->EscapeString(substr($Tag,1));
				unset($TagList[$Key]);
			} else {
				$Tag = $SS->EscapeString($Tag);
			}
		} else {
			unset($TagList[$Key]);
		}
	}
	unset($Tag);
}

if(empty($_GET['tags_type']) && !empty($TagList) && count($TagList) > 1) {
	$_GET['tags_type'] = '0';
	if(!empty($TagListEx)) {
		$Queries[]='@taglist ( '.implode(' | ', $TagList).' ) '.implode(' ', $TagListEx);
	} else {
		$Queries[]='@taglist ( '.implode(' | ', $TagList).' )';
	}
} elseif(!empty($TagList)) {
	$Queries[]='@taglist '.implode(' ', array_merge($TagList,$TagListEx));
} else {
	$_GET['tags_type'] = '1';
}

foreach(array('artistname','groupname', 'recordlabel', 'cataloguenumber', 
				'remastertitle', 'remasteryear', 'remasterrecordlabel', 'remastercataloguenumber',
				'filelist', 'format', 'media') as $Search) {
	if(!empty($_GET[$Search])) {
		$_GET[$Search] = str_replace(array('%'), '', $_GET[$Search]);
		if($Search == 'filelist') {
			$Queries[]='@filelist "'.$SS->EscapeString(strtr($_GET['filelist'], '.', " ")).'"~20';
		} else {
			$Words = explode(' ', $_GET[$Search]);
			foreach($Words as $Key => &$Word) {
				if($Word[0] == '!' && strlen($Word) >= 3 && count($Words) >= 2) {
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
			$Words = trim(implode(' ',$Words));
			if(!empty($Words)) {
				$Queries[]="@$Search ".$Words;
			}
		}
	}
}

if(!empty($_GET['year'])) {
	$Years = explode('-', $_GET['year']);
	if(is_number($Years[0]) || (empty($Years[0]) && !empty($Years[1]) && is_number($Years[1]))) {
		if(count($Years) == 1) {
			$SS->set_filter('year', array((int)$Years[0]));
		} else {
			if(empty($Years[1]) || !is_number($Years[1])) {
				$Years[1] = PHP_INT_MAX;
			} elseif($Years[0] > $Years[1]) {
				$Years = array_reverse($Years);
			}
			$SS->set_filter_range('year', (int)$Years[0], (int)$Years[1]);
		}
	}
}
if(!empty($_GET['encoding'])) {
	$Queries[]='@encoding "'.$SS->EscapeString($_GET['encoding']).'"'; // Note the quotes, for 24bit lossless
}

if(isset($_GET['haslog']) && $_GET['haslog']!=='') {
	if($_GET['haslog'] == 100) {
		$SS->set_filter('logscore', array(100));
 	} elseif ($_GET['haslog'] < 0) {
 		// Exclude torrents with log score equal to 100 
 		$SS->set_filter('logscore', array(100), true);
 		$SS->set_filter('haslog', array(1));
	} else {
		$SS->set_filter('haslog', array(1));
	}	
}

foreach(array('hascue','scene','vanityhouse','freetorrent','releasetype') as $Search) {
	if(isset($_GET[$Search]) && $_GET[$Search]!=='') {
		if($Search == 'freetorrent') {
			switch($_GET[$Search]) {
				case 0: $SS->set_filter($Search, array(0)); break;
				case 1: $SS->set_filter($Search, array(1)); break;
				case 2: $SS->set_filter($Search, array(2)); break;
				case 3: $SS->set_filter($Search, array(0), true); break;
			}
		} else {
			$SS->set_filter($Search, array($_GET[$Search]));
		}
	}
}


if(!empty($_GET['filter_cat'])) {
	$SS->set_filter('categoryid', array_keys($_GET['filter_cat']));
}


if(!empty($_GET['page']) && is_number($_GET['page'])) {
	if(check_perms('site_search_many')) {
		$Page = $_GET['page'];
	} else {
		$Page = min(SPHINX_MAX_MATCHES/TORRENTS_PER_PAGE, $_GET['page']);
	}
	$MaxMatches = min(SPHINX_MAX_MATCHES, SPHINX_MATCHES_START + SPHINX_MATCHES_STEP*floor(($Page-1)*TORRENTS_PER_PAGE/SPHINX_MATCHES_STEP));
	$SS->limit(($Page-1)*TORRENTS_PER_PAGE, TORRENTS_PER_PAGE, $MaxMatches);
} else {
	$Page = 1;
	$MaxMatches = SPHINX_MATCHES_START;
	$SS->limit(0, TORRENTS_PER_PAGE);
}

if(!empty($_GET['order_way']) && $_GET['order_way'] == 'asc') {
	$Way = SPH_SORT_ATTR_ASC;
	$OrderWay = 'asc'; // For header links
} else {
	$Way = SPH_SORT_ATTR_DESC;
	$_GET['order_way'] = 'desc';
	$OrderWay = 'desc';
}

if(empty($_GET['order_by']) || !in_array($_GET['order_by'], array('year', 'time','size','seeders','leechers','snatched'))) {
	$_GET['order_by'] = 'time';
	$OrderBy = 'time'; // For header links
} else {
	$OrderBy = $_GET['order_by'];
}

$SS->SetSortMode($Way, $_GET['order_by']);


if(count($Queries)>0) {
	$Query = implode(' ',$Queries);
} else {
	$Query='';
	if(empty($SS->Filters)) {
		$SS->set_filter('size', array(0), true);
	}
}

$SS->set_index(SPHINX_INDEX.' delta');
$Results = $SS->search($Query, '', 0, array(), '', '');
$TorrentCount = $SS->TotalResults;

/*
// If some were fetched from memcached, get their artists
if(!empty($Results['matches'])) { // Fetch the artists for groups
	$GroupIDs = array_keys($Results['matches']);
	$Artists = get_artists($GroupIDs);
	
	foreach($Artists as $GroupID=>$Data) {
		if(!empty($Data[1])) {
			$Results['matches'][$GroupID]['Artists']=$Data[1]; // Only use main artists
		}
		ksort($Results['matches'][$GroupID]);
	}
}
*/
// These ones were not found in the cache, run SQL
if(!empty($Results['notfound'])) {
	$SQLResults = get_groups($Results['notfound']);
	
	if(is_array($SQLResults['notfound'])) { // Something wasn't found in the db, remove it from results
		reset($SQLResults['notfound']);
		foreach($SQLResults['notfound'] as $ID) {
			unset($SQLResults['matches'][$ID]);
			unset($Results['matches'][$ID]);
		}
	}
	
	// Merge SQL results with sphinx/memcached results
	foreach($SQLResults['matches'] as $ID=>$SQLResult) {
		$Results['matches'][$ID] = array_merge($Results['matches'][$ID], $SQLResult);
		ksort($Results['matches'][$ID]);
	}
}

$Results = $Results['matches'];

$AdvancedSearch = false;
$Action = 'action=basic';
if(((!empty($_GET['action']) && strtolower($_GET['action'])=="advanced") || (!empty($LoggedUser['SearchType']) && ((!empty($_GET['action']) && strtolower($_GET['action'])!="basic") || empty($_GET['action'])))) && check_perms('site_advanced_search')) {
	$AdvancedSearch = true;
	$Action = 'action=advanced';
}

 // List of pages
$Pages=get_pages($Page,$TorrentCount,TORRENTS_PER_PAGE);

if(count($Results)==0) {
	$DB->query("SELECT 
		tags.Name,
		((COUNT(tags.Name)-2)*(SUM(tt.PositiveVotes)-SUM(tt.NegativeVotes)))/(tags.Uses*0.8) AS Score
		FROM xbt_snatched AS s 
		INNER JOIN torrents AS t ON t.ID=s.fid 
		INNER JOIN torrents_group AS g ON t.GroupID=g.ID 
		INNER JOIN torrents_tags AS tt ON tt.GroupID=g.ID
		INNER JOIN tags ON tags.ID=tt.TagID
		WHERE s.uid='$LoggedUser[ID]'
		AND tt.TagID<>'13679'
		AND tt.TagID<>'4820'
		AND tt.TagID<>'2838'
		AND g.CategoryID='1'
		AND tags.Uses > '10'
		GROUP BY tt.TagID
		ORDER BY Score DESC
		LIMIT 8");

	$JsonYouMightLike = array();
	while(list($Tag)=$DB->next_record()) {
		$JsonYouMightLike[] = $Tag;
	}

	print
		json_encode(
			array(
				'status' => 'success',
				'response' => array(
					'results' => array(),
					'youMightLike' => $JsonYouMightLike
				)
			)
		);
	die();
}

$Bookmarks = all_bookmarks('torrent');

$JsonGroups = array();
foreach($Results as $GroupID=>$Data) {
	$JsonGroup = array();
	list($Artists, $GroupCatalogueNumber, $GroupID2, $GroupName, $GroupRecordLabel, $ReleaseType, $TagList, $Torrents, $GroupVanityHouse, $GroupYear, $CategoryID, $FreeTorrent, $HasCue, $HasLog, $TotalLeechers, $LogScore, $ReleaseType, $ReleaseType, $TotalSeeders, $MaxSize, $TotalSnatched, $GroupTime) = array_values($Data);
	$TagList = explode(' ',str_replace('_','.',$TagList));
	
	if(count($Torrents)>1 || $CategoryID==1) {
		// These torrents are in a group
		$ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1);
		$IsBookmarked = in_array($GroupID, $Bookmarks);
		
		$LastRemasterYear = '-';
		$LastRemasterTitle = '';
		$LastRemasterRecordLabel = '';
		$LastRemasterCatalogueNumber = '';
		$LastMedia = '';
		
		$EditionID = 0;
		unset($FirstUnknown);

		$JsonTorrents = array();
		foreach($Torrents as $TorrentID => $Data) {
			$JsonTorrent = array();

			$Filter = false;
			$Pass = false;
			
			if(!empty($FilterBitrates)) {
				$Filter = true;
				$Bitrate = strtolower(array_shift(explode(' ',$Data['Encoding'])));
				if(in_array($Bitrate, $FilterBitrates)) {
					$Pass = true;
				}
			}
			if(!empty($FilterFormats)) {
				$Filter = true;
				if(in_array(strtolower($Data['Format']), $FilterFormats)) {
					$Pass = true;
				}
			}
			
			if(!empty($_GET['encoding'])) {
				$Filter = true;
				if($Data['Encoding']==$_GET['encoding']) {
					$Pass = true;
				}
			}
			if(!empty($_GET['format'])) {
				$Filter = true;
				if($Data['Format']==$_GET['format']) {
					$Pass = true;
				}
			}
			
			
			if(!empty($_GET['media'])) {
				$Filter = true;
				if($Data['Media']==$_GET['media']) {
					$Pass = true;
				}
			}
			if(isset($_GET['haslog']) && $_GET['haslog']!=='') {
				$Filter = true;
				if($_GET['haslog'] == '100' && $Data['LogScore']==100) {
					$Pass = true;
				} elseif (($_GET['haslog'] == '-1') && ($Data['LogScore'] < 100) && ($Data['HasLog'] == '1')) {
 					$Pass = true;
 				} elseif(($_GET['haslog'] == '1' || $_GET['haslog'] == '0') && (int)$Data['HasLog']==$_GET['haslog']) {
					$Pass = true;
				}
			}
			if(isset($_GET['hascue']) && $_GET['hascue']!=='') {
				$Filter = true;
				if((int)$Data['HasCue']==$_GET['hascue']) {
					$Pass = true;
				}
			}
			if(isset($_GET['scene']) && $_GET['scene']!=='') {
				$Filter = true;
				if((int)$Data['Scene']==$_GET['scene']) {
					$Pass = true;
				}
			}
			if(isset($_GET['vanityhouse']) && $_GET['vanityhouse']!=='') {
				$Filter = true;
				if((int)$Data['VanityHouse']==$_GET['vanityhouse']) {
					$Pass = true;
				}
			}
			if(isset($_GET['freetorrent']) && $_GET['freetorrent']!=='') {
				$Filter = true;
				if((int)$Data['FreeTorrent'] & $_GET['freetorrent'] || (int)$Data['FreeTorrent'] == $_GET['freetorrent']) {
					$Pass = true;
				}
			}
			if(!empty($_GET['remastertitle'])) {
				$Filter = true;
				$Continue = false;
				$RemasterParts = explode(' ', $_GET['remastertitle']);
				foreach($RemasterParts as $RemasterPart) {
					if(stripos($Data['RemasterTitle'],$RemasterPart) === false) {
						$Continue = true;
					}
				}
				if(!$Continue) {
					$Pass = true;
				}
			}
			if($Filter && !$Pass) {
				continue;
			}

			if ($Data['Remastered'] && !$Data['RemasterYear']) {
				$FirstUnknown = !isset($FirstUnknown);
			}

			if($CategoryID == 1 && ($Data['RemasterTitle'] != $LastRemasterTitle ||
				$Data['RemasterYear'] != $LastRemasterYear ||
				$Data['RemasterRecordLabel'] != $LastRemasterRecordLabel ||
				$Data['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber) ||
				$FirstUnknown || $Data['Media'] != $LastMedia) {
				$EditionID++;

				if($Data['Remastered'] && $Data['RemasterYear'] != 0) {
					
					$RemasterName = $Data['RemasterYear'];
						$AddExtra = " - ";
					if($Data['RemasterRecordLabel']) { $RemasterName .= $AddExtra.display_str($Data['RemasterRecordLabel']); $AddExtra=' / '; }
					if($Data['RemasterCatalogueNumber']) { $RemasterName .= $AddExtra.display_str($Data['RemasterCatalogueNumber']); $AddExtra=' / '; }
					if($Data['RemasterTitle']) { $RemasterName .= $AddExtra.display_str($Data['RemasterTitle']); $AddExtra=' / '; }
					$RemasterName .= $AddExtra.display_str($Data['Media']);
				} else {
					$AddExtra = " / ";
					if (!$Data['Remastered']) {
						$MasterName = "Original Release";
						if($GroupRecordLabel) { $MasterName .= $AddExtra.$GroupRecordLabel; $AddExtra=' / '; }
						if($GroupCatalogueNumber) { $MasterName .= $AddExtra.$GroupCatalogueNumber; $AddExtra=' / '; }
					} else {
						$MasterName = "Unknown Release(s)";
					}
					$MasterName .= $AddExtra.display_str($Data['Media']);
				}
			}
			$JsonTorrents[] = $Data;
		}
		$LastRemasterTitle = $Data['RemasterTitle'];
		$LastRemasterYear = $Data['RemasterYear'];
		$LastRemasterRecordLabel = $Data['RemasterRecordLabel'];
		$LastRemasterCatalogueNumber = $Data['RemasterCatalogueNumber'];
		$LastMedia = $Data['Media'];
		$JsonGroup[] = $JsonTorrents;
	} else {
		list($TorrentID, $Data) = each($Torrents);
		$JsonGroup = $Data;
	}
	$JsonGroups[] = array(
		'groupId' => $GroupID,
		'groupName' => $GroupName,
		'tags' => $TagList,
		'torrentId' => $TorrentID,
		'categoryId' => $Categories[$CategoryID-1],
		'totalSnatched' => $TotalSnatched,
		'totalSeeders' => $TotalSeeders,
		'totalLeechers' => $TotalLeechers,
		'group' => $JsonGroup
	);
}

print
	json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'currentPage' => intval($Page),
				'pages' => ceil($Pages/TORRENTS_PER_PAGE),
				'results' => $JsonGroups
			)
		)
	);

?>