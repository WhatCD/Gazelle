<?
/************************************************************************
*-------------------- Browse page ---------------------------------------
* Welcome to one of the most complicated pages in all of gazelle - the
* browse page. 
* 
* This is the page that is displayed when someone visits torrents.php, if
* you're not using sphinx.
* 
* It also handle snatch lists, and seeding/leeching/uploaded lists.
* 
* It offers normal and advanced search, as well as enabled/disabled
* grouping. 
*
* For the Sphinx version, see sections/torrents/browse2.php
*
* This version is a little outdated. You should probably set up Sphinx.
*
* Don't blink.
*************************************************************************/

$ErrorPage = true;

define('EXPLAIN_HACK',false);

if(EXPLAIN_HACK){
	$SCFR = '';
} else {
	$SCFR = 'SQL_CALC_FOUND_ROWS';
}

// Function to build a SQL WHERE to search for a string
// Offers exact searching, fulltext searching, and negative searching
function build_search($SearchStr,$Field,$Exact=false,$SQLWhere='',$FullText=0,&$FilterString='') {
	if($SQLWhere!='') { $AddWhere=false; } else { $AddWhere=true; }

	if(!$Exact) {
		if ($FullText && preg_match('/[^a-zA-Z0-9 ]/i',$SearchStr)) { $FullText=0; }

		$SearchLength=strlen(trim($SearchStr));
		$SearchStr=preg_replace('/\s\s+/',' ',trim($SearchStr));
		$SearchStr=preg_replace_callback('/"(([^"])*)"/','quotes',$SearchStr);
		$SearchStr=explode(" ",$SearchStr);

		$FilterString="(.+?)";
		foreach($SearchStr as $SearchVal) {
			if(trim($SearchVal)!='') {
				$SearchVal=trim($SearchVal);
				$SearchVal=str_replace("{{SPACE}}"," ",$SearchVal);
				
				// Choose between fulltext or LIKE based off length of the string
				if ($FullText && strlen($SearchVal)>2) {
					if($SQLWhere!='') { $SQLWhere.=" AND "; }
					if (substr($SearchVal,0,1)=='-') {
						$SQLWhere.="MATCH (".$Field.") AGAINST ('".db_string($SearchVal)."' IN BOOLEAN MODE)";
					} else {
						$SQLWhere.="MATCH (".$Field.") AGAINST ('".db_string($SearchVal)."')";
					}
				} else {
					if($SQLWhere!='') { $SQLWhere.=" AND "; }
					if (substr($SearchVal,0,1)=="-") {
						$SQLWhere.=$Field." NOT LIKE '%".db_string(substr($SearchVal,1))."%'";
					} else {
						$SQLWhere.=$Field." LIKE '%".db_string($SearchVal)."%'";
					}
				}
				$FilterString.="(".$SearchVal.")(.+?)";
			}
		}

	} else {
		if($SQLWhere!='') { $SQLWhere.=" AND "; }
		$SQLWhere.=$Field." LIKE '".db_string($SearchStr)."'";
		$FilterString.="(.+?)(".$SearchStr.")(.+?)";
	}
	$Search = 1;
	$FilterString="/".$FilterString."/si";
	if($SQLWhere!='' && $AddWhere) { $SQLWhere="WHERE ".$SQLWhere; }
	return $SQLWhere;
}

function quotes($Str) {
	$Str = str_replace(' ','{{SPACE}}',trim($Str[1]));
	return ' '.$Str.' ';
}

// The "order by x" links on columns headers
function header_link($SortKey,$DefaultWay="DESC") {
	global $OrderBy,$OrderWay;
	if($SortKey==$OrderBy) {
		if($OrderWay=="DESC") { $NewWay="ASC"; }
		else { $NewWay="DESC"; }
	} else { $NewWay=$DefaultWay; }
	
	return "torrents.php?order_way=".$NewWay."&amp;order_by=".$SortKey."&amp;".get_url(array('order_way','order_by'));
}

// Setting default search options
if($_GET['setdefault']) {
	$UnsetList[]='/(&?page\=.+?&?)/i';
	$UnsetList[]='/(&?setdefault\=.+?&?)/i';

	$DB->query("SELECT SiteOptions FROM users_info WHERE UserID='".db_string($LoggedUser['ID'])."'");
	list($SiteOptions)=$DB->next_record(MYSQLI_NUM, true);
	$SiteOptions=unserialize($SiteOptions);
	$SiteOptions['DefaultSearch']=preg_replace($UnsetList,'',$_SERVER['QUERY_STRING']);
	$DB->query("UPDATE users_info SET SiteOptions='".db_string(serialize($SiteOptions))."' WHERE UserID='".db_string($LoggedUser['ID'])."'");
	$Cache->begin_transaction('user_info_heavy_'.$UserID);
	$Cache->update_row(false, array('DefaultSearch'=>preg_replace($UnsetList,'',$_SERVER['QUERY_STRING'])));
	$Cache->commit_transaction(0);

// Clearing default search options
} elseif($_GET['cleardefault']) {
	$DB->query("SELECT SiteOptions FROM users_info WHERE UserID='".db_string($LoggedUser['ID'])."'");
	list($SiteOptions)=$DB->next_record(MYSQLI_NUM, true);
	$SiteOptions=unserialize($SiteOptions);
	$SiteOptions['DefaultSearch']='';
	$DB->query("UPDATE users_info SET SiteOptions='".db_string(serialize($SiteOptions))."' WHERE UserID='".db_string($LoggedUser['ID'])."'");
	$Cache->begin_transaction('user_info_heavy_'.$UserID);
	$Cache->update_row(false, array('DefaultSearch'=>''));
	$Cache->commit_transaction(0);

// Use default search options
} elseif(!$_SERVER['QUERY_STRING'] && $LoggedUser['DefaultSearch']) {
	parse_str($LoggedUser['DefaultSearch'],$_GET);
}

// If a user is hammering the search page (either via a <script type="text/javascript">, or just general zeal)
if($_SERVER['QUERY_STRING'] != '' && !check_perms('torrents_search_fast') && $_SERVER['QUERY_STRING'] != 'action=basic' && $_SERVER['QUERY_STRING'] != 'action=advanced') {
	if($LoggedUser['last_browse']>time()-1) {
		error('You can only search for torrents once every second.');
	} else {
		$_SESSION['logged_user']['last_browse'] = time();
	}
}

$OrderBy="s3"; // We order by GroupTime by default
$OrderWay="DESC"; // We also order descending by default

list($Page,$Limit) = page_limit(TORRENTS_PER_PAGE);

if (preg_match('/^s[1-7]$/',$_GET['order_by'])) { $OrderBy=strtolower($_GET['order_by']); }
if (in_array(strtolower($_GET['order_way']),array('desc','asc'))) { $OrderWay=strtoupper($_GET['order_way']); }

// Uploaded, seeding, leeching, snatched lists
if($_GET['userid'] && is_number($_GET['userid'])) {
	$UserID=ceil($_GET['userid']);
	
	$DB->query("SELECT m.Paranoia, p.Level FROM users_main AS m JOIN permissions AS p ON p.ID=m.PermissionID WHERE ID='".$UserID."'");
	list($Paranoia, $UserClass) = $DB->next_record();

	$TorrentWhere='';
	$TorrentJoin='';
	if($_GET['type']=="uploaded") {
		if(!check_paranoia('uploads', $Paranoia, $UserClass, $UserID)) { error(403); }
		$TorrentWhere="WHERE t.UserID='".$UserID."'";
		$Title="Uploaded Torrents";
		
	} elseif($_GET['type']=="seeding") {
		if(!check_paranoia('seeding', $Paranoia, $UserClass, $UserID)) { error(403); }
		$TorrentJoin="JOIN xbt_files_users AS xfu ON xfu.fid=t.ID AND xfu.uid='$UserID' AND xfu.remaining=0";
		$Title="Seeding Torrents";
		$TimeField="xfu.mtime";
		$TimeLabel="Seeding Time";
		
	} elseif($_GET['type']=="leeching") {
		if(!check_paranoia('leeching', $Paranoia, $UserClass, $UserID)) { error(403); }
		$TorrentJoin="JOIN xbt_files_users AS xfu ON xfu.fid=t.ID AND xfu.uid='$UserID' AND xfu.remaining>0";
		$Title="Leeching Torrents";
		$TimeField="xfu.mtime";
		$TimeLabel="Leeching Time";
		
	} elseif($_GET['type']=="snatched") {
		if(!check_paranoia('snatched', $Paranoia, $UserClass, $UserID)) { error(403); }
		$TorrentJoin="JOIN xbt_snatched AS xs ON xs.fid=t.ID AND xs.uid='$UserID'";
		$Title="Snatched Torrents";
		$TimeField="xs.tstamp";
		$TimeLabel="Snatched Time";
		
	} else {
		// Something fishy in $_GET['type']
		unset($UserID);
		unset($_GET['userid']);
	}

	if ($TorrentJoin || $TorrentWhere) {
		$_GET['disablegrouping']=1; // We disable grouping on these lists
	}
}

$DisableGrouping = 0;
// If grouping is disabled
if (($LoggedUser['DisableGrouping'] && !$_GET['enablegrouping']) || $_GET['disablegrouping']=='1' || $_GET['artistname']!='') {
	$DisableGrouping=1;
}

// Advanced search
if((strtolower($_GET['action'])=="advanced" || ($LoggedUser['SearchType'] && strtolower($_GET['action'])!="basic")) && check_perms('site_advanced_search')) {
	$TorrentSpecifics=0; // How many options are we searching by? (Disabled grouping only)
	if($DisableGrouping) {
		foreach($_GET as $SearchType=>$SearchStr) {
			switch($SearchType) {
				case 'bitrate':
				case 'format':
				case 'media':
				case 'haslog':
				case 'hascue':
				case 'scene':
				case 'remastered':
				case 'remastertitle':
				case 'freeleech':
					if($SearchStr!='') { $TorrentSpecifics+=1; }
			}
		}
		reset($_GET);
		
	} else {
		$TorrentSpecifics=1;
	}
	
	// And now we start building the mega SQL query
	if($_GET['artistname']!='') {
			$TorrentJoin .= ' LEFT JOIN torrents_artists AS ta ON g.ID = ta.GroupID LEFT JOIN artists AS a ON ta.ArtistID = a.ID';
			$TorrentWhere=build_search($_GET['artistname'],'a.Name',$_GET['exactartist'],$TorrentWhere);
	}

	if($_GET['torrentname']!='') {
		if(!$DisableGrouping) {
			$GroupWhere=build_search($_GET['torrentname'],'GroupName',$_GET['exacttorrent'],$GroupWhere);
		} else {
			$TorrentWhere=build_search($_GET['torrentname'],'g.Name',$_GET['exacttorrent'],$TorrentWhere);
		}
	}

	if($_GET['remastertitle']!='') {
		$RemasterTitle = $_GET['remastertitle'];
		if($_GET['exactremaster']){
			$RemasterTitle = '%'.$RemasterTitle.'%';
		}
		$GroupWhere=build_search($RemasterTitle,'RemasterTitleList',$_GET['exactremaster'],$GroupWhere,0,$RemasterRegEx);
		if($TorrentSpecifics>0) {
			$TorrentWhere=build_search($_GET['remastertitle'],'t.RemasterTitle',$_GET['exactremaster'],$TorrentWhere);
		}
	}

	if($_GET['year']!='' && is_numeric($_GET['year'])) {
		if(!$DisableGrouping) {
			if($GroupWhere=='') { $GroupWhere="WHERE "; } else { $GroupWhere.=" AND "; }
			$GroupWhere.="GroupYear='".db_string($_GET['year'])."'";
		} else {
			if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
			$TorrentWhere.="g.Year='".db_string($_GET['year'])."'";
		}
	}

	if($_GET['bitrate']!='') {
		if(in_array($_GET['bitrate'],$Bitrates)) {
			if($_GET['bitrate'] == 'Other') {
				if($_GET['other_bitrate']!='') {
					$GroupWhere=build_search(db_string($_GET['other_bitrate']),'EncodingList',false,$GroupWhere);
					if($TorrentSpecifics>0) {
						if($TorrentWhere=='') { 
							$TorrentWhere="WHERE ";
						} else { 
							$TorrentWhere.=" AND ";
						}
					}
					$TorrentWhere.="t.Encoding LIKE '%".db_string($_GET['other_bitrate'])."%'";
				}
			} else {
				$GroupWhere=build_search(db_string($_GET['bitrate']),'EncodingList',false,$GroupWhere);
				if($TorrentSpecifics>0) {
					if($TorrentWhere=='') { 
						$TorrentWhere="WHERE ";
					} else { 
						$TorrentWhere.=" AND ";
					}
					$TorrentWhere.="t.Encoding LIKE '".db_string($_GET['bitrate'])."'";
				}
			}
		}
	}

	if($_GET['format']!='' && in_array($_GET['format'],$Formats)) {
		$GroupWhere=build_search("%".$_GET['format']."%",'FormatList',f,$GroupWhere);
		if($TorrentSpecifics>0) {
			if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
			$TorrentWhere.="t.Format='".db_string($_GET['format'])."'";
		}
	}

	if($_GET['media']!='' && in_array($_GET['media'],$Media)) {
		$GroupWhere=build_search("%".$_GET['media']."%",'MediaList',false,$GroupWhere);
		if($TorrentSpecifics>0) {
			if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
			$TorrentWhere.="t.Media='".db_string($_GET['media'])."'";
		}
	}

	if($_GET['filelist']!='') {
		$TorrentWhere=build_search("%".$_GET['filelist']."%",'t.FileList',true,$TorrentWhere);
	}

	if($_GET['haslog']!='') {
		$HasLog = ceil($_GET['haslog']);
		if($_GET['haslog'] == '100' || $_GET['haslog'] == '-100') {
			$GroupWhere=build_search($_GET['haslog'],'LogScoreList',false,$GroupWhere);
			$HasLog = 1;
		}
		
		$GroupWhere=build_search("%".$HasLog."%",'LogList',true,$GroupWhere);
		if($TorrentSpecifics>0) {
			if($_GET['haslog'] == '100' || $_GET['haslog'] == '-100') {
				if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
				if($_GET['haslog'] == '-100') {
					$TorrentWhere.="t.LogScore!='100'";
				} else {
					$TorrentWhere.="t.LogScore='100'";
				}
			}
			if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
			$TorrentWhere.="t.HasLog='".$HasLog."'";
		}
	}

	if($_GET['hascue']!='') {
		$GroupWhere=build_search("%".$_GET['hascue']."%",'CueList',true,$GroupWhere);
		if($TorrentSpecifics>0) {
			if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
			$TorrentWhere.="t.HasCue='".ceil($_GET['hascue'])."'";
		}
	}

	if($_GET['scene']!='') {
		$GroupWhere=build_search("%".$_GET['scene']."%",'SceneList',true,$GroupWhere);
		if($TorrentSpecifics>0) {
			if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
			$TorrentWhere.="t.Scene='".ceil($_GET['scene'])."'";
		}
	}

	if($_GET['freeleech']!='') {
		$GroupWhere=build_search("%".$_GET['freeleech']."%",'FreeTorrentList',true,$GroupWhere);
		if($TorrentSpecifics>0) {
			if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
			$TorrentWhere.="t.FreeTorrent='".ceil($_GET['freeleech'])."'";
		}
	}

	if($_GET['remastered']!='') {
		$GroupWhere=build_search("%".$_GET['remastered']."%",'RemasterList',true,$GroupWhere);
		if($TorrentSpecifics>0) {
			if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
			$TorrentWhere.="t.Remastered='".ceil($_GET['remastered'])."'";
		}
	}

} else {
	// Basic search
	if($_GET['searchstr']!='') {
		// Change special characters into 'normal' characters
		$SearchStr = strtr($_GET['searchstr'],$SpecialChars);

		if(!$DisableGrouping) {
			$GroupWhere=build_search($SearchStr,'SearchText',false,$GroupWhere,1);
		} else {
			$TorrentWhere=build_search($SearchStr,'g.SearchText',false,$TorrentWhere);
		}
	}
}

// Searching tags is the same for basic and advanced search
if(isset($_GET['searchtags']) && $_GET['searchtags']!='') {
	if($DisableGrouping) { $TagField="g.TagList"; } else { $TagField="h.TagList"; }
	
	$Tags=explode(',',$_GET['searchtags']);
	foreach($Tags as $Key => $Tag) {
		if(trim($Tag)!='') {
			if($TagSearch!='') {
				if($_GET['tags_type']) { $TagSearch.=" AND "; }
				else { $TagSearch.=" OR "; }
			}
			$Tag = trim(str_replace('.','_',$Tag));
			if(!$DisableGrouping) {
				// Fulltext
				$TagSearch.=" MATCH (".$TagField.") AGAINST ('".db_string($Tag)."'";
				if(substr($Tag,0,1)=='-') {
					$TagSearch.=' IN BOOLEAN MODE';
				}
				$TagSearch.=")";
			} else {
				$TagSearch.=$TagField." LIKE '%".db_string($Tag)."%'";
			}
		}
	}

	if($TagSearch!='') {
		if($DisableGrouping) {
			if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
			$TorrentWhere.="(".$TagSearch.")";
		} else {
			if($GroupWhere=='') { $GroupWhere="WHERE "; } else { $GroupWhere.=" AND "; }
			$GroupWhere.="(".$TagSearch.")";
		}
	}
}

// Filtering categories is also the same for basic and advanced search
if($_GET['filter_cat']!='') {
	if($DisableGrouping) { $CategoryField="g.CategoryID"; } else { $CategoryField="GroupCategoryID"; }

	foreach($_GET['filter_cat'] as $CatKey => $CatVal) {
		if($CatFilter!='') { $CatFilter.=" OR "; }
		$CatFilter.=$CategoryField."='".db_string(ceil($CatKey))."'";
	}

	if($DisableGrouping) {
		if($TorrentWhere=='') { $TorrentWhere="WHERE "; } else { $TorrentWhere.=" AND "; }
		$TorrentWhere.="(".$CatFilter.")";
	} else {
		if($GroupWhere=='') { $GroupWhere="WHERE "; } else { $GroupWhere.=" AND "; }
		$GroupWhere.="(".$CatFilter.")";
	}
}

// Label for the 'time' column - can also be "seeding time", "leeching time", or "snatched time"
if (!$TimeLabel) { $TimeLabel="Added"; }

if(!is_array($TorrentCache)) {
	if (!$DisableGrouping) {
		// Build SQL query for enabled grouping
		if($TorrentWhere!='') {
			if($GroupWhere=='') { $GroupWhere="WHERE "; } else { $GroupWhere.=" AND "; }
			$GroupWhere.="(SELECT t.GroupID FROM torrents AS t ".$TorrentWhere." AND t.GroupID=h.GroupID LIMIT 1)";
		}
		$DB->query("SELECT $SCFR
						h.GroupID,
						h.GroupName,
						h.GroupYear AS s2,
						h.GroupCategoryID,
						h.GroupTime AS s3,
						h.MaxTorrentSize AS s4,
						h.TotalSnatches AS s5,
						h.TotalSeeders AS s6,
						h.TotalLeechers AS s7,
						h.TorrentIDList,
						h.TagList,
						h.MediaList,
						h.FormatList,
						h.EncodingList,
						h.YearList,
						h.RemasterList,
						h.RemasterTitleList,
						h.SceneList,
						h.LogList,
						h.CueList,
						h.LogScoreList,
						h.FileCountList,
						h.FreeTorrentList,
						h.SizeList,
						h.LeechersList,
						h.SeedersList,
						h.SnatchedList,
						h.TimeList,
						h.SearchText AS s1
					FROM torrent_hash AS h
					$TorrentJoin
					$GroupWhere
					ORDER BY $OrderBy $OrderWay
					LIMIT $Limit");

		$TorrentList=$DB->to_array();
		if(EXPLAIN_HACK){
			$DB->query("EXPLAIN SELECT NULL FROM (SELECT NULL FROM torrent_hash AS h ".$TorrentJoin." ".$GroupWhere.") AS Count");
			list($Null,$Null,$Null,$Null,$Null,$Null,$Null,$Null,$TorrentCount)=$DB->next_record();
		} else {
			$DB->query("SELECT FOUND_ROWS()");
			list($TorrentCount) = $DB->next_record();
		}
	} else {
		// Build SQL for disabled grouping
		if (!$TimeField) { $TimeField="t.Time"; }
		$DB->query("SELECT $SCFR
						g.ID,
						g.Name,
						g.Year AS s2,
						g.CategoryID,
						".$TimeField." AS s3,
						t.Size AS s4,
						t.Snatched AS s5,
						t.Seeders AS s6,
						t.Leechers AS s7,
						t.ID,
						g.TagList,
						t.Media,
						t.Format,
						t.Encoding,
						t.RemasterYear,
						t.Remastered,
						t.RemasterTitle,
						t.Scene,
						t.HasLog,
						t.HasCue,
						t.LogScore,
						t.FileCount,
						t.FreeTorrent,
						g.SearchText AS s1
					FROM torrents AS t
					INNER JOIN torrents_group AS g ON g.ID=t.GroupID
					$TorrentJoin
					$TorrentWhere
					ORDER BY $OrderBy $OrderWay
					LIMIT $Limit");
					
		$TorrentList=$DB->to_array();
		if(EXPLAIN_HACK){
			$DB->query("EXPLAIN SELECT NULL FROM (SELECT NULL FROM torrent_hash AS h ".$TorrentJoin." ".$GroupWhere.") AS Count");
			list($Null,$Null,$Null,$Null,$Null,$Null,$Null,$Null,$TorrentCount)=$DB->next_record();
		} else {
			$DB->query("SELECT FOUND_ROWS()");
			list($TorrentCount) = $DB->next_record();
		}
	}

	if($UserID) {
		// Get the username, so we can display the title as "<user>'s snatched torrents", etc
		$DB->query("SELECT Username FROM users_main WHERE ID='".db_string($UserID)."'");
		list($TitleUser)=$DB->next_record();
	}
} else {
	// We got the results from cache
	$TorrentCount=$TorrentCache[0];
	$TorrentList=$TorrentCache[1];
	if($UserID) { $TitleUser=$TorrentCache[2]; }
}
 
 // List of pages
$Pages=get_pages($Page,$TorrentCount,TORRENTS_PER_PAGE);

// Gets tacked onto torrent download URLs
$DownloadString="&amp;authkey=".$LoggedUser['AuthKey']."&amp;torrent_pass=".$LoggedUser['torrent_pass'];

if($Title) { $Title=$TitleUser."'s  ".$Title; } else { $Title="Browse Torrents"; }

show_header($Title,'browse');
?>
<form name="filter" method="get" action=''>
<? if($UserID) { ?>
<input type="hidden" name="type" value="<?=display_str($_GET['type'])?>" />
<input type="hidden" name="userid" value="<?=display_str($_GET['userid'])?>" />
<? } ?>
<div class="filter_torrents">
	<h3>
		Filter
<? if(strtolower($_GET['action'])!="advanced" && (!$LoggedUser['SearchType'] || strtolower($_GET['action'])=="basic") && check_perms('site_advanced_search')) { ?>
		(<a href="torrents.php?action=advanced&amp;<?=get_url(array('action'))?>">Advanced Search</a>)
<? } elseif((strtolower($_GET['action'])=="advanced" || ($LoggedUser['SearchType'] && strtolower($_GET['action'])!="basic")) && check_perms('site_advanced_search')) { ?>
		(<a href="torrents.php?<? if($LoggedUser['SearchType']) { ?>action=basic&amp;<? } echo get_url(array('action')); ?>">Basic Search</a>)
<? } ?>
	</h3>
	<div class="box pad">
		<table>
<? $AdvancedSearch = false;
   if((strtolower($_GET['action'])=="advanced" || ($LoggedUser['SearchType'] && strtolower($_GET['action'])!="basic")) && check_perms('site_advanced_search')) {
	$AdvancedSearch = true;
?>
			<tr>
				<td class="label">Artist Name:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="artistname" class="inputtext smaller" value="<?=display_str($_GET['artistname'])?>" />&nbsp;
					<input type="checkbox" name="exactartist" id="exactartist" value="1" <? if($_GET['exactartist']) { ?>checked="checked"<? } ?> /> <label for="exactartist">Exact Phrase</label>
					<input type="hidden" name="action" value="advanced" />
				</td>
			</tr>
			<tr>
				<td class="label">Album/Torrent Name:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="torrentname" class="inputtext smaller" value="<?=display_str($_GET['torrentname'])?>" />&nbsp;
					<input type="checkbox" name="exacttorrent" id="exacttorrent" value="1" <? if($_GET['exacttorrent']) { ?>checked="checked"<? } ?> /> <label for="exacttorrent">Exact Phrase</label>
				</td>
			</tr>
			<tr>
				<td class="label">Edition Information:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="remastertitle" class="inputtext smaller" value="<?=display_str($_GET['remastertitle'])?>" />&nbsp;
					<input type="checkbox" name="exactremaster" id="exactremaster" value="1" <? if($_GET['exactremaster']) { ?>checked="checked"<? } ?> /> <label for="exactremaster">Exact Phrase</label>
				</td>
			</tr>
			<tr>
				<td class="label">File List:</td>
				<td colspan="3"><input type="text" spellcheck="false" size="40" name="filelist" class="inputtext" value="<?=display_str($_GET['filelist']); ?>" /></td>
			</tr>
			<tr>
				<td class="label">Rip Specifics:</td>
				<td class="nobr">
					<select id=bitrate name="bitrate" style="width:auto;" onchange="Bitrate()">
						<option value="">Bitrate</option>
<?		if($_GET['bitrate'] && $_GET['bitrate'] == 'Other'){
			$OtherBitrate = true;
		} else {
			$OtherBitrate = false;
		}
		
foreach($Bitrates as $BitrateName) { ?>
						<option value="<?=display_str($BitrateName); ?>" <? if($BitrateName==$_GET['bitrate']) { ?>selected="selected"<? } ?>><?=display_str($BitrateName); ?></option>
<?	} ?>			</select>
					
					<span id="other_bitrate_span"<? if(!$OtherBitrate){ echo " style='display: none;'"; } ?> >
						<input type="text" spellcheck="false" name="other_bitrate" size="5" id="other_bitrate"<? if($OtherBitrate){ echo " value='".display_str($_GET['other_bitrate'])."'";} ?> />
					</span>
					
					<select name="format" style="width:auto;">
						<option value="">Format</option>
<?	foreach($Formats as $FormatName) { ?>
						<option value="<?=display_str($FormatName); ?>" <? if($FormatName==$_GET['format']) { ?>selected="selected"<? } ?>><?=display_str($FormatName); ?></option>
<?	} ?>		</select>
					<select name="media" style="width:auto;">
						<option value="">Media</option>
<?	foreach($Media as $MediaName) { ?>
						<option value="<?=display_str($MediaName); ?>" <? if($MediaName==$_GET['media']) { ?>selected="selected"<? } ?>><?=display_str($MediaName); ?></option>
<?	} ?>
					</select>
				</td>
				<td class="label">Year:</td>
				<td><input type="text" name="year" class="inputtext smallest" value="<?=display_str($_GET['year']); ?>" size="4" /></td>
			</tr>
			<tr>
				<td class="label">Misc:</td>
				<td class="nobr" colspan="3">
					<select name="haslog" style="width:auto;">
						<option value="">Has Log</option>
						<option value="1" <? if($_GET['haslog']=="1") { ?>selected="selected"<? } ?>>Yes</option>
						<option value="0" <? if($_GET['haslog']=="0") { ?>selected="selected"<? } ?>>No</option>
						<option value="100" <? if($_GET['haslog']=="100") { ?>selected="selected"<? } ?>>100% only</option>
						<option value="-100" <? if($_GET['haslog']=="-100") { ?>selected="selected"<? } ?>>&lt;100%/Unscored</option>
					</select>
					<select name="hascue" style="width:auto;">
						<option value="">Has Cue</option>
						<option value="1" <? if($_GET['hascue']=="1") { ?>selected="selected"<? } ?>>Yes</option>
						<option value="0" <? if($_GET['hascue']=="0") { ?>selected="selected"<? } ?>>No</option>
					</select>
					<select name="scene" style="width:auto;">
						<option value="">Scene</option>
						<option value="1" <? if($_GET['scene']=="1") { ?>selected="selected"<? } ?>>Yes</option>
						<option value="0" <? if($_GET['scene']=="0") { ?>selected="selected"<? } ?>>No</option>
					</select>
					<select name="freeleech" style="width:auto;">
						<option value="">Freeleech</option>
						<option value="1" <? if($_GET['freeleech']=="1") { ?>selected="selected"<? } ?>>Yes</option>
						<option value="0" <? if($_GET['freeleech']=="0") { ?>selected="selected"<? } ?>>No</option>
					</select>
					<select name="remastered" style="width:auto;">
						<option value=''>Remastered</option>
						<option value="1" <? if($_GET['remastered']=="1") { ?>selected="selected"<? } ?>>Yes</option>
						<option value="0" <? if($_GET['remastered']=="0") { ?>selected="selected"<? } ?>>No</option>
					</select>
				</td>
			</tr>
<? } else { ?>
			<tr>
				<td class="label">Search terms:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="searchstr" class="inputtext" value="<?=display_str($_GET['searchstr'])?>" />
<?	if($LoggedUser['SearchType']) { ?>
					<input type="hidden" name="action" value="basic" />
<?	} ?>
				</td>
			</tr>
<? } ?>
			<tr>
				<td class="label">Tags (comma-separated):</td>
				<td colspan="3">
					<input type="text" size="40" id="tags" name="searchtags" class="inputtext smaller" title="Use -tag to exclude tag" value="<?=display_str($_GET['searchtags'])?>" />&nbsp;
					<input type="radio" name="tags_type" id="tags_type0" value="0" <? if($_GET['tags_type']==0) { ?>checked="checked"<? } ?> /> <label for="tags_type0">Any</label>&nbsp;&nbsp;
					<input type="radio" name="tags_type" id="tags_type1" value="1" <? if($_GET['tags_type']==1) { ?>checked="checked"<? } ?> /> <label for="tags_type1">All</label>
				</td>
			</tr>
			<tr>
				<td class="label">Order by:</td>
				<td colspan="<?=($AdvancedSearch)?'3':'1'?>">
					<select name="order_by" style="width:auto;">
						<option value="s1" <? if($OrderBy=="s1") { ?>selected="selected"<? } ?>>Name</option>
						<option value="s2" <? if($OrderBy=="s2") { ?>selected="selected"<? } ?>>Year</option>
						<option value="s3" <? if($OrderBy=="s3") { ?>selected="selected"<? } ?>><?=$TimeLabel?></option>
						<option value="s4" <? if($OrderBy=="s4") { ?>selected="selected"<? } ?>>Size</option>
						<option value="s5" <? if($OrderBy=="s5") { ?>selected="selected"<? } ?>>Snatched</option>
						<option value="s6" <? if($OrderBy=="s6") { ?>selected="selected"<? } ?>>Seeders</option>
						<option value="s7" <? if($OrderBy=="s7") { ?>selected="selected"<? } ?>>Leechers</option>
					</select>
					<select name="order_way">
						<option value="desc" <? if($OrderWay=="DESC") { ?>selected="selected"<? } ?>>Descending</option>
						<option value="asc" <? if($OrderWay=="ASC") { ?>selected="selected"<? } ?>>Ascending</option>
					</select>&nbsp;&nbsp;&nbsp;&nbsp;
<?/*
<? if ($LoggedUser['DisableGrouping']) { ?>
					<input type="checkbox" name="enablegrouping" id="enablegrouping" value="1" <? if(!$DisableGrouping) { ?>checked="checked"<? } ?> /> <label for="enablegrouping"><strong>Enable grouping</strong></label>
<? } else { ?>
					<input type="checkbox" name="disablegrouping" id="disablegrouping" value="1" <? if($DisableGrouping) { ?>checked="checked"<? } ?> /> <label for="disablegrouping"><strong>Disable grouping</strong></label>
<? } ?>
*/?>
				</td>
			</tr>
		</table>
		<table class="cat_list">
<?
$x=1;
reset($Categories);
foreach($Categories as $CatKey => $CatName) {
	if($x%8==0 || $x==1) {
?>
			<tr>
<?	} ?>
				<td>
					<input type="checkbox" name="filter_cat[<?=($CatKey+1)?>]" id="cat_<?=($CatKey+1)?>" value="1" <? if(isset($_GET['filter_cat'][$CatKey+1])) { ?>checked="checked"<? } ?> />
					<label for="cat_<?=($CatKey+1)?>"><?=$CatName?></label>
				</td>
<?
	if($x%7==0) {
?>
			</tr>
<?
	}

	$x++;
}
?>
		</table>
		<table class="cat_list <? if(!$LoggedUser['ShowTags']) { ?>hidden<? } ?>" id="taglist">
			<tr>
<?
$GenreTags = $Cache->get_value('genre_tags');
if(!$GenreTags) {
	$DB->query('SELECT Name FROM tags WHERE TagType=\'genre\' ORDER BY Name');
	$GenreTags =  $DB->collect('Name');
	$Cache->cache_value('genre_tags', $GenreTags, 3600*6);
}

$x = 0;
foreach($GenreTags as $Tag) {
?>
				<td width="12.5%"><a href="#" onclick="add_tag('<?=$Tag?>');return false;"><?=$Tag?></a></td>
<?
	$x++;
	if($x%7==0) {
?>
			</tr>
			<tr>
<?
	}
}
if($x%7!=0) { // Padding
?>
				<td colspan="<?=7-($x%7)?>"> </td>
<? } ?>
			</tr>
		</table>
		<table class="cat_list" width="100%">
			<tr>
				<td class="label"><a href="#" onclick="$('#taglist').toggle();return false;">(View Tags)</a></td>
			</tr>
		</table>
		<div class="submit">
			<span style="float:left;"><?=number_format($TorrentCount)?> Results</span>
			<input type="submit" value="Filter Torrents" />
			<input type="button" value="Reset" onclick="location.href='torrents.php<? if(isset($_GET['action']) && $_GET['action']=="advanced") { ?>?action=advanced<? } ?>'" />
			&nbsp;&nbsp;
<? if (isset($TorrentWhere) || isset($GroupWhere) || $OrderBy!="s3" || $OrderWay!="DESC") { ?>
			<input type="submit" name="setdefault" value="Make Default" />
<?
}

if ($LoggedUser['DefaultSearch']) {
?>
			<input type="submit" name="cleardefault" value="Clear Default" />
<? } ?>
		</div>
	</div>
</div>
</form>

<div class="linkbox"><?=$Pages?></div>
<? if (count($TorrentList)>0) { ?>
<table class="torrent_table <?=(($DisableGrouping)?'no_grouping':'grouping')?>" id="torrent_table">
	<tr class="colhead">
<?	if(!$DisableGrouping) { ?>
		<td class="small"></td>
<?	} ?>
		<td class="small cats_col"></td>
		<td width="100%"><a href="<?=header_link('s1','ASC')?>">Name</a> / <a href="<?=header_link('s2')?>">Year</a></td>
		<td>Files</td>
		<td><a href="<?=header_link('s3')?>"><?=$TimeLabel?></a></td>
		<td><a href="<?=header_link('s4')?>">Size</a></td>
		<td class="sign"><a href="<?=header_link('s5')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" alt="Snatches" title="Snatches" /></a></td>
		<td class="sign"><a href="<?=header_link('s6')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/seeders.png" alt="Seeders" title="Seeders" /></a></td>
		<td class="sign"><a href="<?=header_link('s7')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/leechers.png" alt="Leechers" title="Leechers" /></a></td>
	</tr>
<?
	if($LoggedUser['TorrentGrouping']==0) {
		$HideGroup='';
		$ActionTitle="Collapse";
		$ActionURL="hide";

	} elseif($LoggedUser['TorrentGrouping']==1) {
		$HideGroup="hide";
		$ActionTitle="Expand";
		$ActionURL="show";
	}
	// Start printing torrent list
	$GroupIDs = array();
	foreach($TorrentList as $Key => $Properties) {
		$GroupIDs[] = $Properties[0];
	}
	$Artists = get_artists($GroupIDs);
	foreach ($TorrentList as $Key => $Properties) {
		list($GroupID,$GroupName,$GroupYear,$GroupCategoryID,$GroupTime,$MaxSize,$TotalSnatched,$TotalSeeders,$TotalLeechers,$TorrentsID,$TorrentTags,$TorrentsMedia,$TorrentsFormat,$TorrentsEncoding,$TorrentsYear,$TorrentsRemastered,$TorrentsRemasterTitle,$TorrentsScene,$TorrentsLog,$TorrentsCue,$TorrentsLogScores,$TorrentsFileCount,$TorrentsFreeTorrent,$TorrentsSize,$TorrentsLeechers,$TorrentsSeeders,$TorrentsSnatched,$TorrentsTime) = $Properties;
		$Torrents['id']		=explode('|',$TorrentsID);
		$Torrents['media']	=explode('|',$TorrentsMedia);
		$Torrents['format']	=explode('|',$TorrentsFormat);
		$Torrents['encoding']	=explode('|',$TorrentsEncoding);
		$Torrents['year']	=explode('|',$TorrentsYear);
		$Torrents['remastered']	=explode('|',$TorrentsRemastered);
		$Torrents['remastertitle']=explode('|',$TorrentsRemasterTitle);
		$Torrents['scene']	=explode('|',$TorrentsScene);
		$Torrents['log']	=explode('|',$TorrentsLog);
		$Torrents['cue']	=explode('|',$TorrentsCue);
		$Torrents['score'] 	=explode('|',$TorrentsLogScores);
		$Torrents['filecount']	=explode('|',$TorrentsFileCount);
		$Torrents['size']	=explode('|',$TorrentsSize);
		$Torrents['leechers']	=explode('|',$TorrentsLeechers);
		$Torrents['seeders']	=explode('|',$TorrentsSeeders);
		$Torrents['snatched']	=explode('|',$TorrentsSnatched);
		$Torrents['freetorrent']=explode('|',$TorrentsFreeTorrent);
		$Torrents['time']	=explode('|',$TorrentsTime);

		if (!$DisableGrouping) {
			// Since these fields are surrounded by |s, we get extra elements added to the arrays
			array_pop($Torrents['media']);
			array_pop($Torrents['format']);
			array_pop($Torrents['encoding']);
			array_pop($Torrents['remastertitle']);
			array_pop($Torrents['log']);
			array_pop($Torrents['cue']);
			array_pop($Torrents['score']);
			array_pop($Torrents['freetorrent']);

			array_shift($Torrents['media']);
			array_shift($Torrents['format']);
			array_shift($Torrents['encoding']);
			array_shift($Torrents['remastertitle']);
			array_shift($Torrents['log']);
			array_shift($Torrents['cue']);
			array_shift($Torrents['score']);
			array_shift($Torrents['freetorrent']);

		} else {
			$Torrents['size'][0]=$MaxSize;
			$Torrents['leechers'][0]=$TotalLeechers;
			$Torrents['seeders'][0]=$TotalSeeders;
			$Torrents['snatched'][0]=$TotalSnatched;
		}

		$TagList=array();
		if($TorrentTags!='') {
			$TorrentTags=explode(' ',$TorrentTags);
			foreach ($TorrentTags as $TagKey => $TagName) {
				$TagName = str_replace('_','.',$TagName);
				$TagList[]='<a href="torrents.php?searchtags='.$TagName.'">'.$TagName.'</a>';
			}
			$PrimaryTag = $TorrentTags[0];
			$TagList = implode(', ', $TagList);
			$TorrentTags='<br /><div class="tags">'.$TagList.'</div>';
		}

		if ($GroupName=='') { $GroupName="- None -"; }
		$DisplayName = display_artists($Artists[$GroupID]);
		if((count($Torrents['id'])>1 || $GroupCategoryID==1) && !$DisableGrouping) {
			// These torrents are in a group
			$DisplayName.='<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
			if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
?>
	<tr class="group">
		<td class="center"><div title="<?=$ActionTitle?>" id="showimg_<?=$GroupID?>" class="<?=$ActionURL?>_torrents"><a href="#" class="show_torrents_link" onclick="$('.groupid_<?=$GroupID?>').toggle(); return false;"></a></div></td>
		<td class="center cats_col"><div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>"></div></td>
		<td colspan="2">
			<?=$DisplayName?>
			<span style="float:right;"><a href="#showimg_<?=$GroupID?>" onclick="Bookmark(<?=$GroupID?>);this.innerHTML='Bookmarked';return false;">Bookmark</a></span>
			<?=$TorrentTags?>
		</td>
		<td class="nobr"><?=time_diff($GroupTime,1)?></td>
		<td class="nobr"><?=get_size($MaxSize)?> (Max)</td>
		<td><?=number_format($TotalSnatched)?></td>
		<td<?=($TotalSeeders==0)?' class="r00"':''?>><?=number_format($TotalSeeders)?></td>
		<td><?=number_format($TotalLeechers)?></td>
	</tr>
<?
			$Row = 'a';
			foreach($Torrents['id'] as $Key => $Val) {
				// All of the individual torrents in the group
				
				// If they're using the advanced search and have chosen enabled grouping, we just skip the torrents that don't check out
				if(!empty($_GET['bitrate']) && $Torrents['encoding'][$Key]!=$_GET['bitrate']) { continue; }
				if(!empty($_GET['format']) && $Torrents['format'][$Key]!=$_GET['format']) { continue; }
				if(!empty($_GET['media']) && $Torrents['media'][$Key]!=$_GET['media']) { continue; }
				if(!empty($_GET['haslog'])) {
					if($_GET['haslog'] == '100' && $Torrents['score'][$Key]!=100) {
						continue;
					}
					if($_GET['haslog'] == '-100' && $Torrents['score'][$Key]==100 || !$Torrents['log'][$Key]) {
						continue;
					}
					if(($_GET['haslog'] == '1' || $_GET['haslog'] == '0') && $Torrents['log'][$Key]!=$_GET['haslog']) {
						continue; 
					}
				}
				if(!empty($_GET['hascue']) && $Torrents['cue'][$Key]!=$_GET['hascue']) { continue; }
				if(!empty($_GET['scene']) && $Torrents['scene'][$Key]!=$_GET['scene']) { continue; }
				if(!empty($_GET['freeleech']) && $Torrents['freetorrent'][$Key]!=$_GET['freeleech']) { continue; }
				if(!empty($_GET['remastered']) && $Torrents['remastered'][$Key]!=$_GET['remastered']) { continue; }
				if(!empty($_GET['exactremaster']) && !empty($_GET['remastertitle'])) {
					if(strtolower(trim($Torrents['remastertitle'][$Key])) != strtolower(trim($_GET['remastertitle']))) {
						continue;
					}
				} elseif(!empty($_GET['remastertitle'])) {
					$Continue = false;
					$RemasterParts = explode(' ', $_GET['remastertitle']);
					foreach($RemasterParts as $RemasterPart) {
						if(stripos($Torrents['remastertitle'][$Key],$RemasterPart) === false) {
							$Continue = true;
						}
					}
					if($Continue) {
						continue;
					}
				}
				
				$ExtraInfo='';
				$AddExtra='';
				
				if($Torrents['format'][$Key]) 		{ $ExtraInfo.=$Torrents['format'][$Key]; $AddExtra=" / "; }
				if($Torrents['encoding'][$Key]) 	{ $ExtraInfo.=$AddExtra.$Torrents['encoding'][$Key]; $AddExtra=" / "; }
				if($Torrents['log'][$Key]=="1") 	{
					$ExtraInfo.=$AddExtra."Log"; $AddExtra=" / ";
					if($Torrents['score'][$Key])		{ $ExtraInfo.=' ('.$Torrents['score'][$Key].'%) '; }
				}
				if($Torrents['cue'][$Key]=="1")		{ $ExtraInfo.=$AddExtra."Cue"; $AddExtra=" / "; }
				if($Torrents['media'][$Key])		{ $ExtraInfo.=$AddExtra.$Torrents['media'][$Key]; $AddExtra=" / "; }
				if($Torrents['scene'][$Key]=="1") 	{ $ExtraInfo.=$AddExtra."Scene"; $AddExtra=" / "; }
				if(trim($Torrents['remastertitle'][$Key])) {  $ExtraInfo.=$AddExtra.$Torrents['remastertitle'][$Key]; $AddExtra=" - "; }
				elseif($Torrents['remastered'][$Key]=="1") { $ExtraInfo.=$AddExtra."Remastered"; $AddExtra=" - "; }
				if($Torrents['year'][$Key]>"0") 	{ $ExtraInfo.=$AddExtra.$Torrents['year'][$Key]; $AddExtra=" / "; }
				if($Torrents['freetorrent'][$Key]=="1") { $ExtraInfo.=$AddExtra."<strong>Freeleech!</strong>"; $AddExtra=" / "; }
?>
	<tr class="group_torrent groupid_<?=$GroupID?> <?=$HideGroup?>">
		<td colspan="3">
			<span>
				[<a href="torrents.php?action=download&amp;id=<?=$Torrents['id'][$Key]?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
				| <a href="reportsv2.php?action=report&amp;id=<?=$Torrents['id'][$Key]?>" title="Report">RP</a>]
			</span>
			&raquo; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$Val?>"><?=$ExtraInfo?></a>
		</td>
		<td><?=$Torrents['filecount'][$Key]?></td>
		<td class="nobr"><?=time_diff($Torrents['time'][$Key],1)?></td>
		<td class="nobr"><?=get_size($Torrents['size'][$Key])?></td>
		<td><?=number_format($Torrents['snatched'][$Key])?></td>
		<td<?=($Torrents['seeders'][$Key]==0)?' class="r00"':''?>><?=number_format($Torrents['seeders'][$Key])?></td>
		<td><?=number_format($Torrents['leechers'][$Key])?></td>
	</tr>
<?
			}
		} else {
			// Either grouping is disabled, or we're viewing a type that does not require grouping
			if ($GroupCategoryID==1) {
				$DisplayName.='<a href="torrents.php?id='.$GroupID.'&amp;torrentid='.$Torrents['id'][0].'" title="View Torrent">'.$GroupName.'</a>';
			} else {
				$DisplayName.='<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
			}

			$ExtraInfo='';
			$AddExtra='';

			if($Torrents['format'][0]) 		{ $ExtraInfo.=$Torrents['format'][0]; $AddExtra=" / "; }
			if($Torrents['encoding'][0]) 		{ $ExtraInfo.=$AddExtra.$Torrents['encoding'][0]; $AddExtra=" / "; }
			if($Torrents['log'][0]=="1") 		{ $ExtraInfo.=$AddExtra."Log"; $AddExtra=" / "; }
			if($Torrents['score'][0])		{ $ExtraInfo.=' ('.$Torrents['score'][0].'%) '; }
			if($Torrents['cue'][0]=="1") 		{ $ExtraInfo.=$AddExtra."Cue"; $AddExtra=" / "; }
			if($Torrents['media'][0]) 		{ $ExtraInfo.=$AddExtra.$Torrents['media'][0]; $AddExtra=" / "; }
			if($Torrents['scene'][0]=="1") 		{ $ExtraInfo.=$AddExtra."Scene"; $AddExtra=" / "; }
			if(trim($Torrents['remastertitle'][0])) {  $ExtraInfo.=$AddExtra.$Torrents['remastertitle'][0]; $AddExtra=" - "; }
			elseif($Torrents['remastered'][0]=="1") { $ExtraInfo.=$AddExtra."Remastered"; $AddExtra=" - "; }
			if($Torrents['year'][0]>"0") 		{ $ExtraInfo.=$AddExtra.$Torrents['year'][0]; $AddExtra=" / "; }
			if($Torrents['freetorrent'][0]=="1") 	{ $ExtraInfo.=$AddExtra."<strong>Freeleech!</strong>"; $AddExtra=" / "; }
			if($ExtraInfo!='') 			{ $ExtraInfo="[".$ExtraInfo."]"; }
			if($GroupYear>0) 			{ $ExtraInfo.=" [".$GroupYear."]"; }
			
			if (!isset($TimeField) || $TimeField=="t.Time") { $GroupTime=strtotime($GroupTime); }
?>
	<tr class="torrent">
<?			if(!$DisableGrouping) { ?>
		<td></td>
<?			} ?>
		<td class="center cats_col"><div title="<?=ucfirst(str_replace('.',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>"></div></td>
		<td>
			<span>[<a href="torrents.php?action=download&amp;id=<?=$Torrents['id'][0].$DownloadString?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a> | <a href="reportsv2.php?action=report&amp;id=<?=$Torrents['id'][0]?>" title="Report">RP</a>]</span>
			<?=$DisplayName?>
			<?=$ExtraInfo?>
			<?=$TorrentTags?>
		</td>
		<td><?=$Torrents['filecount'][0]?></td>
		<td class="nobr"><?=time_diff($GroupTime,1)?></td>
		<td class="nobr"><?=get_size($Torrents['size'][0])?></td>
		<td><?=number_format($TotalSnatched)?></td>
		<td<?=($TotalSeeders==0)?' class="r00"':''?>><?=number_format($TotalSeeders)?></td>
		<td><?=number_format($TotalLeechers)?></td>
	</tr>
<?
		}
	}
?>
</table>
<? } else {
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
?>
<div class="box pad" align="center">
	<h2>Your search did not match anything.</h2>
	<p>Make sure all names are spelled correctly, or try making your search less specific.</p>
	<p>You might like (Beta): <? while(list($Tag)=$DB->next_record()) { ?><a href="torrents.php?searchtags=<?=$Tag?>"><?=$Tag?></a> <? } ?></p>
</div>
<? } ?>
<div class="linkbox"><?=$Pages?></div>
<? show_footer(array('disclaimer'=>false)); ?>
