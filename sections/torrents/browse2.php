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
* For an outdated non-Sphinx version, use /sections/torrents/browse.php.
*
* Don't blink.
* Blink and you're dead.
* Don't turn your back.
* Don't look away.
* And don't blink.
* Good Luck.
*
*************************************************************************/

include(SERVER_ROOT.'/sections/bookmarks/functions.php');
include(SERVER_ROOT.'/sections/torrents/functions.php');


// The "order by x" links on columns headers
function header_link($SortKey,$DefaultWay="desc") {
	global $OrderBy,$OrderWay;
	if($SortKey==$OrderBy) {
		if($OrderWay=="desc") { $NewWay="asc"; }
		else { $NewWay="desc"; }
	} else { $NewWay=$DefaultWay; }
	
	return "torrents.php?order_way=".$NewWay."&amp;order_by=".$SortKey."&amp;".get_url(array('order_way','order_by'));
}

$TokenTorrents = $Cache->get_value('users_tokens_'.$UserID);
if (empty($TokenTorrents)) {
	$DB->query("SELECT TorrentID FROM users_freeleeches WHERE UserID=$UserID AND Expired=FALSE");
	$TokenTorrents = $DB->collect('TorrentID');
	$Cache->cache_value('users_tokens_'.$UserID, $TokenTorrents);
}

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
			$Queries[]='@filelist "'.$SS->EscapeString($_GET['filelist']).'"~20';
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

if(empty($_GET['order_by']) || !in_array($_GET['order_by'], array('year','time','size','seeders','leechers','snatched','random'))) {
	$_GET['order_by'] = 'time';
	$OrderBy = 'time'; // For header links
} elseif($_GET['order_by'] == 'random') {
	$OrderBy = '@random';
	$Way = SPH_SORT_EXTENDED;
	$SS->limit(0, TORRENTS_PER_PAGE, TORRENTS_PER_PAGE);
} else {
	$OrderBy = $_GET['order_by'];
}

$SS->SetSortMode($Way, $OrderBy);


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




show_header('Browse Torrents','browse');

 // List of pages
$Pages=get_pages($Page,$TorrentCount,TORRENTS_PER_PAGE);


?>
<form name="filter" method="get" action=''>
<div class="filter_torrents">
	<h3>
		Filter		
<? if($AdvancedSearch) { ?>
			(<a href="torrents.php?<? if(!empty($LoggedUser['SearchType'])) { ?>action=basic&amp;<? } echo get_url(array('action')); ?>">Basic Search</a>)
<? } else { ?>
			(<a href="torrents.php?action=advanced&amp;<?=get_url(array('action'))?>">Advanced Search</a>)
<? } 
?>
	</h3>
	<div class="box pad">
		<table>
<? if($AdvancedSearch) { ?>
			<tr>
				<td class="label">Artist Name:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="artistname" class="inputtext smaller" value="<?form('artistname')?>" />
					<input type="hidden" name="action" value="advanced" />
				</td>
			</tr>
			<tr>
				<td class="label">Album/Torrent Name:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="groupname" class="inputtext smaller" value="<?form('groupname')?>" />
				</td>
			</tr>
			<tr>
				<td class="label">Record Label:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="recordlabel" class="inputtext smaller" value="<?form('recordlabel')?>" />
				</td>
			</tr>
			<tr>
				<td class="label">Catalogue Number:</td>
				<td>
					<input type="text" size="40" name="cataloguenumber" class="inputtext smallest" value="<?form('"cataloguenumber"')?>"  />
				</td>
				<td class="label">Year:</td>
				<td>
					<input type="text" name="year" class="inputtext smallest" value="<?form('year')?>" size="4" />
				</td>
			</tr>
			<tr id="edition_expand">
				<td colspan="4" class="center">[<a href="#" onclick="ToggleEditionRows();return false;">Click here to toggle searching for specific remaster information</a>]</td>
			</tr>
<?
if(form('remastertitle', true) == "" && form('remasteryear', true) == "" && 
	form('remasterrecordlabel', true) == "" && form('remastercataloguenumber', true) == "") {
		$Hidden = 'hidden';
} else {
	$Hidden = '';
}
?>
			<tr id="edition_title" class="<?=$Hidden?>">
				<td class="label">Edition Title:</td>
				<td>
					<input type="text" spellcheck="false" size="40" name="remastertitle" class="inputtext smaller" value="<?form('remastertitle')?>" />
				</td>
				<td class="label">Edition Year:</td>
				<td>
					<input type="text" name="remasteryear" class="inputtext smallest" value="<?form('remasteryear')?>" size="4" />
				</td>
			</tr>
			<tr id="edition_label" class="<?=$Hidden?>">
				<td class="label">Edition Release Label:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="remasterrecordlabel" class="inputtext smaller" value="<?form('remasterrecordlabel')?>" />
				</td>
			</tr>
			<tr id="edition_catalogue" class="<?=$Hidden?>">
				<td class="label">Edition Catalogue Number:</td>
				<td colspan="3">
					<input type="text" size="40" name="remastercataloguenumber" class="inputtext smallest" value="<?form('remastercataloguenumber')?>" />
				</td>
			</tr>
			<tr>
				<td class="label">File List:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="filelist" class="inputtext" value="<?form('filelist')?>" />
				</td>
			</tr>
			<tr>
				<td class="label">Rip Specifics:</td>
				<td class="nobr" colspan="3">
					<select id="bitrate" name="encoding">
						<option value="">Bitrate</option>
<?	foreach($Bitrates as $BitrateName) { ?>
						<option value="<?=display_str($BitrateName); ?>" <?selected('encoding', $BitrateName)?>><?=display_str($BitrateName); ?></option>
<?	} ?>			</select>
					
					<select name="format">
						<option value="">Format</option>
<?	foreach($Formats as $FormatName) { ?>
						<option value="<?=display_str($FormatName); ?>" <?selected('format', $FormatName)?>><?=display_str($FormatName); ?></option>
<?	} ?>			</select>
					<select name="media">
						<option value="">Media</option>
<?	foreach($Media as $MediaName) { ?>
						<option value="<?=display_str($MediaName); ?>" <?selected('media',$MediaName)?>><?=display_str($MediaName); ?></option>
<?	} ?>
					</select>
					<select name="releasetype">
						<option value="">Release type</option>
<?	foreach($ReleaseTypes as $ID=>$Type) { ?>
						<option value="<?=display_str($ID); ?>" <?selected('releasetype',$ID)?>><?=display_str($Type); ?></option>
<?	} ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Misc:</td>
				<td class="nobr" colspan="3">
					<select name="haslog">
						<option value="">Has Log</option>
						<option value="1" <?selected('haslog','1')?>>Yes</option>
						<option value="0" <?selected('haslog','0')?>>No</option>
						<option value="100" <?selected('haslog','100')?>>100% only</option>
						<option value="-1" <?selected('haslog','-1')?>>&lt;100%/Unscored</option>
					</select>
					<select name="hascue">
						<option value="">Has Cue</option>
						<option value="1" <?selected('hascue',1)?>>Yes</option>
						<option value="0" <?selected('hascue',0)?>>No</option>
					</select>
					<select name="scene">
						<option value="">Scene</option>
						<option value="1" <?selected('scene',1)?>>Yes</option>
						<option value="0" <?selected('scene',0)?>>No</option>
					</select>
					<select name="vanityhouse">
						<option value="">Vanity House</option>
						<option value="1" <?selected('vanityhouse',1)?>>Yes</option>
						<option value="0" <?selected('vanityhouse',0)?>>No</option>
					</select>
					<select name="freetorrent">
						<option value="">Leech Status</option>
						<option value="1" <?selected('freetorrent',1)?>>Freeleech</option>
						<option value="2" <?selected('freetorrent',2)?>>Neutral Leech</option>
						<option value="3" <?selected('freetorrent',3)?>>Either</option>
						<option value="0" <?selected('freetorrent',0)?>>Normal</option>
					</select>
				</td>
			</tr>
<? } else { // BASIC SEARCH ?>
			<tr>
				<td class="label">Search terms:</td>
				<td colspan="3">
					<input type="text" spellcheck="false" size="40" name="searchstr" class="inputtext" value="<?form('searchstr')?>" />
<?	if(!empty($LoggedUser['SearchType'])) { ?>
					<input type="hidden" name="action" value="basic" />
<?	} ?>
				</td>
			</tr>
<? } ?>
			<tr>
				<td class="label">Tags (comma-separated):</td>
				<td colspan="3">
					<input type="text" size="40" id="tags" name="taglist" class="inputtext smaller" title="Use !tag to exclude tag" value="<?=str_replace('_','.',form('taglist', true))?>" />&nbsp;
					<input type="radio" name="tags_type" id="tags_type0" value="0" <?selected('tags_type',0,'checked')?> /><label for="tags_type0"> Any</label>&nbsp;&nbsp;
					<input type="radio" name="tags_type" id="tags_type1" value="1"  <?selected('tags_type',1,'checked')?> /><label for="tags_type1"> All</label>
				</td>
			</tr>
			<tr>
				<td class="label">Order by:</td>
				<td colspan="<?=($AdvancedSearch)?'3':'1'?>">
					<select name="order_by" style="width:auto;">
						<option value="time"<?selected('order_by','time')?>>Time added</option>
						<option value="year"<?selected('order_by','year')?>>Year</option>
						<option value="size"<?selected('order_by','size')?>>Size</option>
						<option value="snatched"<?selected('order_by','snatched')?>>Snatched</option>
						<option value="seeders"<?selected('order_by','seeders')?>>Seeders</option>
						<option value="leechers"<?selected('order_by','leechers')?>>Leechers</option>
						<option value="random"<?selected('order_by','random')?>>Random</option>
					</select>
					<select name="order_way">
						<option value="desc"<?selected('order_way','desc')?>>Descending</option>
						<option value="asc" <?selected('order_way','asc')?>>Ascending</option>
					</select>
				</td>
			</tr>
<?	if(check_perms('site_search_many')) { ?>
			<tr>
				<td class="label">Limited search results:</td>
				<td><input type="checkbox" value="1" name="limit_matches" <?selected('limit_matches',1,'checked')?> /></td>
			</tr>
<?	} ?>
		</table>
		<table class="cat_list">
<?
$x=0;
reset($Categories);
foreach($Categories as $CatKey => $CatName) {
	if($x%7==0) {
		if($x > 0) {
?>
			</tr>
<?		} ?>
			<tr>
<?
	}
	$x++;
?>
				<td>
					<input type="checkbox" name="filter_cat[<?=($CatKey+1)?>]" id="cat_<?=($CatKey+1)?>" value="1" <? if(isset($_GET['filter_cat'][$CatKey+1])) { ?>checked="checked"<? } ?> />
					<label for="cat_<?=($CatKey+1)?>"><?=$CatName?></label>
				</td>
<?
}
?>
			</tr>
		</table>
		<table class="cat_list <? if(empty($LoggedUser['ShowTags'])) { ?>hidden<? } ?>" id="taglist">
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
				<td class="label">
					<a href="#" onclick="$('#taglist').toggle(); if(this.innerHTML=='(View Tags)'){this.innerHTML='(Hide Tags)';} else {this.innerHTML='(View Tags)';}; return false;"><?=(empty($LoggedUser['ShowTags'])) ? '(View Tags)' : '(Hide Tags)'?></a>
				</td>
			</tr>
		</table>
		<div class="submit">
			<span style="float:left;"><?=number_format($TorrentCount).($TorrentCount < SPHINX_MAX_MATCHES && $TorrentCount == $MaxMatches ? '+' : '')?> Results</span>
			<input type="submit" value="Filter Torrents" />
			<input type="button" value="Reset" onclick="location.href='torrents.php<? if(isset($_GET['action']) && $_GET['action']=="advanced") { ?>?action=advanced<? } ?>'" />
			&nbsp;&nbsp;
<? if (count($Queries)>0 || count($SS->Filters)>0) { ?>
			<input type="submit" name="setdefault" value="Make Default" />
<?
}

if (!empty($LoggedUser['DefaultSearch'])) {
?>
			<input type="submit" name="cleardefault" value="Clear Default" />
<? } ?>
		</div>
	</div>
</div>
</form>

<div class="linkbox"><?=$Pages?></div>
<? if(count($Results)==0) {
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
	<p>You might like (Beta): <? while(list($Tag)=$DB->next_record()) { ?><a href="torrents.php?taglist=<?=$Tag?>"><?=$Tag?></a> <? } ?></p>
</div>
<? 
show_footer();die();
}

$Bookmarks = all_bookmarks('torrent');

?>


<table class="torrent_table grouping" id="torrent_table">
	<tr class="colhead">
		<td class="small"></td>
		<td class="small cats_col"></td>
		<td width="100%">Name / <a href="<?=header_link('year')?>">Year</a></td>
		<td>Files</td>
		<td><a href="<?=header_link('time')?>">Time</a></td>
		<td><a href="<?=header_link('size')?>">Size</a></td>
		<td class="sign"><a href="<?=header_link('snatched')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" alt="Snatches" title="Snatches" /></a></td>
		<td class="sign"><a href="<?=header_link('seeders')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/seeders.png" alt="Seeders" title="Seeders" /></a></td>
		<td class="sign"><a href="<?=header_link('leechers')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/leechers.png" alt="Leechers" title="Leechers" /></a></td>
	</tr>
<?
// Start printing torrent list

foreach($Results as $GroupID=>$Data) {
	list($Artists, $GroupCatalogueNumber, $ExtendedArtists, $GroupID2, $GroupName, $GroupRecordLabel, $ReleaseType, $TagList, $Torrents, $GroupVanityHouse, $GroupYear, $CategoryID, $FreeTorrent, $HasCue, $HasLog, $TotalLeechers, $LogScore, $ReleaseType, $ReleaseType, $TotalSeeders, $MaxSize, $TotalSnatched, $GroupTime) = array_values($Data);
	
	$TagList = explode(' ',str_replace('_','.',$TagList));
	
	$TorrentTags = array();
	foreach($TagList as $Tag) {
		$TorrentTags[]='<a href="torrents.php?'.$Action.'&amp;taglist='.$Tag.'">'.$Tag.'</a>';
	}
	$TorrentTags = implode(', ', $TorrentTags);
	
	if(count($Torrents)>1 || $CategoryID==1) {
		// These torrents are in a group
		if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
			unset($ExtendedArtists[2]);
			unset($ExtendedArtists[3]);
			$DisplayName = display_artists($ExtendedArtists);
		} elseif(!empty($Artists)) {
			$DisplayName = display_artists(array(1=>$Artists));
		} else {
			$DisplayName='';
		}
		$DisplayName.='<a href="torrents.php?id='.$GroupID.'" title="View Torrent" dir="ltr">'.$GroupName.'</a>';
		if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
		if($GroupVanityHouse) { $DisplayName .= ' [<abbr title="This is a vanity house release">VH</abbr>]'; }
		$DisplayName .= ' ['.$ReleaseTypes[$ReleaseType].']';
?>
	<tr class="group">
<?
$ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1);
?>
		<td class="center">
			<div title="View" id="showimg_<?=$GroupID?>" class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
				<a href="#" class="show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event)" title="Collapse this group"></a>
			</div>
		</td>
		<td class="center cats_col">
			<div title="<?=ucfirst(str_replace('_',' ',$TagList[0]))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$CategoryID-1]))?> tags_<?=str_replace('.','_',$TagList[0])?>">
			</div>
		</td>
		<td colspan="2">
			<?=$DisplayName?>
<?	if(in_array($GroupID, $Bookmarks)) { ?>
			<span style="float:right;"><a href="#" id="bookmarklink_torrent_<?=$GroupID?>" title="Remove bookmark" onclick="Unbookmark('torrent',<?=$GroupID?>,'Bookmark');return false;">Unbookmark</a></span>
<?	} else { ?>
			<span style="float:right;"><a href="#" id="bookmarklink_torrent_<?=$GroupID?>" title="Add bookmark" onclick="Bookmark('torrent',<?=$GroupID?>,'Unbookmark');return false;">Bookmark</a></span>
<?	} ?>
			<br />
			<div class="tags">
				<?=$TorrentTags?>
			</div>
		</td>
		<td class="nobr"><?=time_diff($GroupTime,1)?></td>
		<td class="nobr"><?=get_size($MaxSize*1024)?> (Max)</td>
		<td><?=number_format($TotalSnatched)?></td>
		<td<?=($TotalSeeders==0)?' class="r00"':''?>><?=number_format($TotalSeeders)?></td>
		<td><?=number_format($TotalLeechers)?></td>
	</tr>
<?		
		$LastRemasterYear = '-';
		$LastRemasterTitle = '';
		$LastRemasterRecordLabel = '';
		$LastRemasterCatalogueNumber = '';
		$LastMedia = '';
		
		$EditionID = 0;
		unset($FirstUnknown);

		foreach($Torrents as $TorrentID => $Data) {
			// All of the individual torrents in the group
			
			// If they're using the advanced search and have chosen enabled grouping, we just skip the torrents that don't check out
			
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
			
			if (in_array($TorrentID, $TokenTorrents) && empty($Torrent['FreeTorrent'])) {
				$Data['PersonalFL'] = 1;
			}

			if($CategoryID == 1 && ($Data['RemasterTitle'] != $LastRemasterTitle || $Data['RemasterYear'] != $LastRemasterYear ||
			$Data['RemasterRecordLabel'] != $LastRemasterRecordLabel || $Data['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber) || $FirstUnknown || $Data['Media'] != $LastMedia) {
				$EditionID++;

				if($Data['Remastered'] && $Data['RemasterYear'] != 0) {
					
					$RemasterName = $Data['RemasterYear'];
					$AddExtra = " - ";
					if($Data['RemasterRecordLabel']) { $RemasterName .= $AddExtra.display_str($Data['RemasterRecordLabel']); $AddExtra=' / '; }
					if($Data['RemasterCatalogueNumber']) { $RemasterName .= $AddExtra.display_str($Data['RemasterCatalogueNumber']); $AddExtra=' / '; }
					if($Data['RemasterTitle']) { $RemasterName .= $AddExtra.display_str($Data['RemasterTitle']); $AddExtra=' / '; }
					$RemasterName .= $AddExtra.display_str($Data['Media']);
					
?>
	<tr class="group_torrent groupid_<?=$GroupID?> edition<? if (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; }?>">
		<td colspan="9" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition">&minus;</a> <?=$RemasterName?></strong></td>
	</tr>
<?
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
?>
	<tr class="group_torrent groupid_<?=$GroupID?> edition<? if (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; }?>">
		<td colspan="9" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition">&minus;</a> <?=$MasterName?></strong></td>
	</tr>
<?
				}
			}
			$LastRemasterTitle = $Data['RemasterTitle'];
			$LastRemasterYear = $Data['RemasterYear'];
			$LastRemasterRecordLabel = $Data['RemasterRecordLabel'];
			$LastRemasterCatalogueNumber = $Data['RemasterCatalogueNumber'];
			$LastMedia = $Data['Media'];
?>
	<tr class="group_torrent groupid_<?=$GroupID?> edition_<?=$EditionID?><? if (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; }?>">
		<td colspan="3">
			<span>
				[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download"><?=$Data['HasFile'] ? 'DL' : 'Missing'?></a>
<?			if (($LoggedUser['FLTokens'] > 0) && $Data['HasFile'] && ($Data['Size'] < 1073741824) 
				&& !in_array($TorrentID, $TokenTorrents) && empty($Data['FreeTorrent']) && ($LoggedUser['CanLeech'] == '1')) { ?>
				| <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&usetoken=1" title="Use a FL Token" onClick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
				| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a> ]
			</span>
			&raquo; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=torrent_info($Data)?></a>
		</td>
		<td><?=$Data['FileCount']?></td>
		<td class="nobr"><?=time_diff($Data['Time'],1)?></td>
		<td class="nobr"><?=get_size($Data['Size'])?></td>
		<td><?=number_format($Data['Snatched'])?></td>
		<td<?=($Data['Seeders']==0)?' class="r00"':''?>><?=number_format($Data['Seeders'])?></td>
		<td><?=number_format($Data['Leechers'])?></td>
	</tr>
<?
		}
	} else {
		// Viewing a type that does not require grouping
		
		list($TorrentID, $Data) = each($Torrents);
		
		$DisplayName = '<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
		if($Data['FreeTorrent'] == '1') {
			$DisplayName .= ' <strong>Freeleech!</strong>';
		} elseif($Data['FreeTorrent'] == '2') {
			$DisplayName .= ' <strong>Neutral Leech!</strong>';
		}  elseif(in_array($TorrentID, $TokenTorrents)) { 
			$DisplayName .= $AddExtra.'<strong>Personal Freeleech!</strong>';
		}
?>
	<tr class="torrent">
		<td></td>
		<td class="center cats_col">
			<div title="<?=ucfirst(str_replace('.',' ',$TagList[0]))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$CategoryID-1]))?> tags_<?=str_replace('.','_',$TagList[0])?>"></div>
		</td>
		<td>
			<span>
				[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
<?		if (($LoggedUser['FLTokens'] > 0) && $Data['HasFile'] && ($Data['Size'] < 1073741824) 
			&& !in_array($TorrentID, $TokenTorrents) && empty($Data['FreeTorrent']) && ($LoggedUser['CanLeech'] == '1')) { ?>
				| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&usetoken=1" title="Use a FL Token" onClick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?		} ?>				
				| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a>]
			</span>
			<?=$DisplayName?>
			<br />
			<div class="tags">
				<?=$TorrentTags?>
			</div>
		</td>
		<td><?=$Data['FileCount']?></td>
		<td class="nobr"><?=time_diff($GroupTime,1)?></td>
		<td class="nobr"><?=get_size($Data['Size'])?></td>
		<td><?=number_format($TotalSnatched)?></td>
		<td<?=($TotalSeeders==0)?' class="r00"':''?>><?=number_format($TotalSeeders)?></td>
		<td><?=number_format($TotalLeechers)?></td>
	</tr>
<?
	}
}
?>
</table>
<div class="linkbox"><?=$Pages?></div>
<? show_footer(array('disclaimer'=>false)); ?>
