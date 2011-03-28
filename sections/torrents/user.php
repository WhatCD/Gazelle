<?

$Orders = array('Time', 'Name', 'Seeders', 'Leechers', 'Snatched', 'Size');
$Ways = array('ASC'=>'Ascending', 'DESC'=>'Descending');

// The "order by x" links on columns headers
function header_link($SortKey,$DefaultWay="DESC") {
	global $Order,$Way;
	if($SortKey==$Order) {
		if($Way=="DESC") { $NewWay="ASC"; }
		else { $NewWay="DESC"; }
	} else { $NewWay=$DefaultWay; }
	
	return "torrents.php?way=".$NewWay."&amp;order=".$SortKey."&amp;".get_url(array('way','order'));
}

$UserID = $_GET['userid'];
if(!is_number($UserID)) { error(0); }


if(!empty($_GET['page']) && is_number($_GET['page'])) {
	$Page = $_GET['page'];
	$Limit = ($Page-1)*TORRENTS_PER_PAGE.', '.TORRENTS_PER_PAGE;
} else {
	$Page = 1;
	$Limit = TORRENTS_PER_PAGE;
}

if(!empty($_GET['order']) && in_array($_GET['order'], $Orders)) {
	$Order = $_GET['order'];
} else {
	$Order = 'Time';
}

if(!empty($_GET['way']) && array_key_exists($_GET['way'], $Ways)) {
	$Way = $_GET['way'];
} else {
	$Way = 'DESC';
}

$SearchWhere = array();

if(!empty($_GET['format'])) {
	if(in_array($_GET['format'], $Formats)) {
		$SearchWhere[]="t.Format='".db_string($_GET['format'])."'";
	} elseif($_GET['format'] == 'perfectflac') {
		$_GET['filter'] = 'perfectflac';
	}
}

if(!empty($_GET['bitrate']) && in_array($_GET['bitrate'], $Bitrates)) {
	$SearchWhere[]="t.Encoding='".db_string($_GET['bitrate'])."'";
}

if(!empty($_GET['media']) && in_array($_GET['media'], $Media)) {
	$SearchWhere[]="t.Media='".db_string($_GET['media'])."'";
}

if(!empty($_GET['releasetype']) && array_key_exists($_GET['releasetype'], $ReleaseTypes)) {
	$SearchWhere[]="tg.ReleaseType='".db_string($_GET['releasetype'])."'";
}

if(isset($_GET['scene']) && in_array($_GET['scene'], array('1','0'))) {
	$SearchWhere[]="t.Scene='".db_string($_GET['scene'])."'";
}

if(isset($_GET['cue']) && in_array($_GET['cue'], array('1','0'))) {
	$SearchWhere[]="t.HasCue='".db_string($_GET['cue'])."'";
}

if(isset($_GET['log']) && in_array($_GET['log'], array('1','0', '100', '-1'))) {
	if($_GET['log'] == '100') {
		$SearchWhere[]="t.HasLog = '1'";
		$SearchWhere[]="t.LogScore = '100'";
	} elseif ($_GET['log'] == '-1') {
		$SearchWhere[]="t.HasLog = '1'";
		$SearchWhere[]="t.LogScore < '100'";
	} else {
		$SearchWhere[]="t.HasLog='".db_string($_GET['log'])."'";
	}
}

if(!empty($_GET['categories'])) {
	$Cats = array();
	foreach(array_keys($_GET['categories']) as $Cat) {
		if(!is_number($Cat)) {
			error(0);
		}
		$Cats[]="tg.CategoryID='".db_string($Cat)."'";
	}
	$SearchWhere[]='('.implode(' OR ', $Cats).')';
}

if(!empty($_GET['tags'])) {
	$Tags = explode(',',$_GET['tags']);
	$TagList = array();
	foreach($Tags as $Tag) {
		$Tag = trim(str_replace('.','_',$Tag));
		if(empty($Tag)) { continue; }
		$TagList[]="tg.TagList LIKE '%".db_string($Tag)."%'";
	}
	if(!empty($TagList)) {
		$SearchWhere[]='('.implode(' OR ', $TagList).')';
	}
}

$SearchWhere = implode(' AND ', $SearchWhere);
if(!empty($SearchWhere)) {
	$SearchWhere = ' AND '.$SearchWhere;
}

$User = user_info($UserID);
$Perms = get_permissions($UserInfo['PermissionID']);
$UserClass = $Perms['Class'];

switch($_GET['type']) {
	case 'snatched':
		if(!check_paranoia('snatched', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
		$Time = 'xs.tstamp';
		$UserField = 'xs.uid';
		$ExtraWhere = '';
		$From = "xbt_snatched AS xs JOIN torrents AS t ON t.ID=xs.fid";
		break;
	case 'seeding':
		if(!check_paranoia('seeding', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
		$Time = '(unix_timestamp(now()) - xfu.timespent)';
		$UserField = 'xfu.uid';
		$ExtraWhere = 'AND xfu.Remaining=0';
		$From = "xbt_files_users AS xfu JOIN torrents AS t ON t.ID=xfu.fid";
		break;
	case 'leeching':
		if(!check_paranoia('leeching', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
		$Time = '(unix_timestamp(now()) - xfu.timespent)';
		$UserField = 'xfu.uid';
		$ExtraWhere = 'AND xfu.Remaining>0';
		$From = "xbt_files_users AS xfu JOIN torrents AS t ON t.ID=xfu.fid";
		break;
	case 'uploaded':
		if (!check_paranoia('uploads', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
		$Time = 'unix_timestamp(t.Time)';
		$UserField = 't.UserID';
		$ExtraWhere = 'AND flags!=1';
		$From = "torrents AS t";
		break;
	case 'downloaded':
		if(!check_perms('site_view_torrent_snatchlist')) { error(403); }
		$Time = 'unix_timestamp(ud.Time)';
		$UserField = 'ud.UserID';
		$ExtraWhere = '';
		$From = "users_downloads AS ud JOIN torrents AS t ON t.ID=ud.TorrentID";
		break;
	default:
		error(404);
}

if(!empty($_GET['filter']) && (($_GET['filter'] == "perfectflac") || ($_GET['filter'] == "uniquegroup"))) {
	if($_GET['filter'] == "perfectflac") {
		if (!check_paranoia('perfectflacs', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
		$ExtraWhere .= ' AND t.Format = \'FLAC\'';
		if(empty($_GET['media'])) {
			$ExtraWhere .= ' AND (
				t.LogScore = 100 OR
				t.Media = \'Vinyl\' OR
				t.Media = \'WEB\' OR
				t.Media = \'DVD\' OR
				t.Media = \'Soundboard\')';
		} elseif(strtoupper($_GET['media']) == 'CD' && empty($_GET['log'])) {
			$ExtraWhere .= ' AND t.LogScore = 100';
		}
	} elseif($_GET['filter'] == "uniquegroup") {
		if (!check_paranoia('uniquegroups', $User['Paranoia'], $UserClass, $UserID)) { error(403); }
		$GroupBy = "tg.ID";
	}
}

if(empty($GroupBy)) {
	$GroupBy = "t.ID";
}

if((empty($_GET['search']) || trim($_GET['search']) == '') && $Order!='Name') {
	$SQL = "SELECT SQL_CALC_FOUND_ROWS t.GroupID, t.ID AS TorrentID, $Time AS Time, tg.CategoryID
		FROM $From
		JOIN torrents_group AS tg ON tg.ID=t.GroupID
		WHERE $UserField='$UserID' $ExtraWhere $SearchWhere
		GROUP BY ".$GroupBy."
		ORDER BY $Order $Way LIMIT $Limit";
} else {
	$DB->query("CREATE TEMPORARY TABLE t (
		GroupID int(10) unsigned not null,
		TorrentID int(10) unsigned not null,
		Time int(12) unsigned not null,
		CategoryID int(3) unsigned,
		Seeders int(6) unsigned,
		Leechers int(6) unsigned,
		Snatched int(10) unsigned,
		Name mediumtext,
		Size bigint(12) unsigned,
		PRIMARY KEY (TorrentID)) CHARSET=utf8");
	$DB->query("INSERT IGNORE INTO t SELECT
		t.GroupID, 
		t.ID AS TorrentID, 
		$Time AS Time, 
		tg.CategoryID,
		t.Seeders,
		t.Leechers,
		t.Snatched,
		CONCAT_WS(' ', GROUP_CONCAT(aa.Name SEPARATOR ' '), ' ', tg.Name, ' ', tg.Year, ' ') AS Name,
		t.Size
		FROM $From
		JOIN torrents_group AS tg ON tg.ID=t.GroupID
		LEFT JOIN torrents_artists AS ta ON ta.GroupID=tg.ID
		LEFT JOIN artists_alias AS aa ON aa.AliasID=ta.AliasID
		WHERE $UserField='$UserID' $ExtraWhere $SearchWhere 
		GROUP BY TorrentID, Time");
	
	if(!empty($_GET['search']) && trim($_GET['search']) != '') {
		$Words = array_unique(explode(' ', db_string($_GET['search'])));
	}

	$SQL = "SELECT SQL_CALC_FOUND_ROWS 
		GroupID, TorrentID, Time, CategoryID
		FROM t";
	if(!empty($Words)) {
		$SQL .= "
		WHERE Name LIKE '%".implode("%' AND Name LIKE '%", $Words)."%'";
	}
	$SQL .= "
		ORDER BY $Order $Way LIMIT $Limit";
}

$DB->query($SQL);
$GroupIDs = $DB->collect('GroupID');
$TorrentsInfo = $DB->to_array('TorrentID', MYSQLI_ASSOC);

$DB->query("SELECT FOUND_ROWS()");
list($TorrentCount) = $DB->next_record();

$Results = get_groups($GroupIDs);

$Action = display_str($_GET['type']);
$User = user_info($UserID);

show_header($User['Username'].'\'s '.$Action.' torrents');

$Pages=get_pages($Page,$TorrentCount,TORRENTS_PER_PAGE);


?>
<div class="thin">
	<h2><a href="user.php?id=<?=$UserID?>"><?=$User['Username']?></a><?='\'s '.$Action.' torrents'?></h2>
	
	<div>
		<form action="" method="get">
			<table>
				<tr>
					<td class="label"><strong>Search for:</strong></td>
					<td>
						<input type="hidden" name="type" value="<?=$_GET['type']?>" />
						<input type="hidden" name="userid" value="<?=$UserID?>" />
						<input type="text" name="search" size="60" value="<?form('search')?>" />
					</td>
				</tr>
				<tr>
					<td class="label"><strong>Rip Specifics:</strong></td>
					<td class="nobr" colspan="3">
						<select id="bitrate" name="bitrate">
							<option value="">Bitrate</option>
<?	foreach($Bitrates as $BitrateName) { ?>
							<option value="<?=display_str($BitrateName); ?>" <?selected('bitrate', $BitrateName)?>><?=display_str($BitrateName); ?></option>
<?	} ?>				</select>
						
						<select name="format">
							<option value="">Format</option>
<?	foreach($Formats as $FormatName) { ?>
							<option value="<?=display_str($FormatName); ?>" <?selected('format', $FormatName)?>><?=display_str($FormatName); ?></option>
<?	} ?>				
							<option value="perfectflac" <?selected('filter', 'perfectflac')?>>Perfect FLACs</option>
						</select>
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
					<td class="label"><strong>Misc:</strong></td>
					<td class="nobr" colspan="3">
						<select name="log">
							<option value="">Has Log</option>
							<option value="1" <?selected('log','1')?>>Yes</option>
							<option value="0" <?selected('log','0')?>>No</option>
							<option value="100" <?selected('log','100')?>>100% only</option>
							<option value="-1" <?selected('log','-1')?>>&lt;100%/Unscored</option>
						</select>
						<select name="cue">
							<option value="">Has Cue</option>
							<option value="1" <?selected('cue',1)?>>Yes</option>
							<option value="0" <?selected('cue',0)?>>No</option>
						</select>
						<select name="scene">
							<option value="">Scene</option>
							<option value="1" <?selected('scene',1)?>>Yes</option>
							<option value="0" <?selected('scene',0)?>>No</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="label"><strong>Tags:</strong></td>
					<td>
						<input type="text" name="tags" size="60" value="<?form('tags')?>" />
					</td>
				</tr>
				
				<tr>
					<td class="label"><strong>Order by</strong></td>
					<td>
						<select name="order">
<? foreach($Orders as $OrderText) { ?>
							<option value="<?=$OrderText?>" <?selected('order', $OrderText)?>><?=$OrderText?></option>
<? }?>
						</select>&nbsp;
						<select name="way">
<? foreach($Ways as $WayKey=>$WayText) { ?>
							<option value="<?=$WayKey?>" <?selected('way', $WayKey)?>><?=$WayText?></option>
<? }?>
						</select>
					</td>
				</tr>
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
						<input type="checkbox" name="categories[<?=($CatKey+1)?>]" id="cat_<?=($CatKey+1)?>" value="1"<? if(isset($_GET['categories'][$CatKey+1])) { ?> checked="checked"<? } ?> />
						<label for="cat_<?=($CatKey+1)?>"><?=$CatName?></label>
					</td>
<?
}
?>
				</tr>
			</table>
			<div class="submit">
				<input type="submit" value="Search torrents" />
			</div>
		</form>
	</div>
<?	if(count($GroupIDs) == 0) { ?>
	<div class="center">
		Nothing found!
	</div>
<?	} else { ?>
	<div class="linkbox"><?=$Pages?></div>
	<table width="100%">
		<tr class="colhead">
			<td></td>
			<td><a href="<?=header_link('Name', 'ASC')?>">Torrent</a></td>
			<td><a href="<?=header_link('Time')?>">Time</a></td>
			<td><a href="<?=header_link('Size')?>">Size</a></td>
			<td class="sign">
				<a href="<?=header_link('Snatched')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" alt="Snatches" title="Snatches" /></a>
			</td>
			<td class="sign">
				<a href="<?=header_link('Seeders')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/seeders.png" alt="Seeders" title="Seeders" /></a>
			</td>
			<td class="sign">
				<a href="<?=header_link('Leechers')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/leechers.png" alt="Leechers" title="Leechers" /></a>
			</td>
		</tr>
<?
	$Results = $Results['matches'];
	foreach($TorrentsInfo as $TorrentID=>$Info) {
		list($GroupID,, $Time, $CategoryID) = array_values($Info);
		
		list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TagList, $ReleaseType, $Torrents, $Artists) = array_values($Results[$GroupID]);
		$Torrent = $Torrents[$TorrentID];
		
		
		$TagList = explode(' ',str_replace('_','.',$TagList));
		
		$TorrentTags = array();
		foreach($TagList as $Tag) {
			$TorrentTags[]='<a href="torrents.php?type='.$Action.'&amp;userid='.$UserID.'&amp;tags='.$Tag.'">'.$Tag.'</a>';
		}
		$TorrentTags = implode(', ', $TorrentTags);
		
		
		$DisplayName = '';
		if(count($Artists)>0) {
			$DisplayName = display_artists(array('1'=>$Artists));
		}
		$DisplayName.='<a href="torrents.php?id='.$GroupID.'&amp;torrentid='.$TorrentID.'" title="View Torrent">'.$GroupName.'</a>';
		if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
		
		$ExtraInfo = torrent_info($Torrent);
		if($ExtraInfo) {
			$DisplayName.=' - '.$ExtraInfo;
		}
	
	
?>
		<tr>
			<td class="center cats_col">
				<div title="<?=ucfirst(str_replace('.',' ',$TagList[0]))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$CategoryID-1]))?> tags_<?=str_replace('.','_',$TagList[0])?>"></div>
			</td>
			<td>
				<span style="float: right;">
					[<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
					| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a>]
				</span>
				<?=$DisplayName?>
				<br />
				<div class="tags">
					<?=$TorrentTags?>
				</div>
			</td>
			<td class="nobr"><?=time_diff($Time,1)?></td>
			<td class="nobr"><?=get_size($Torrent['Size'])?></td>
			<td><?=number_format($Torrent['Snatched'])?></td>
			<td<?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format($Torrent['Seeders'])?></td>
			<td><?=number_format($Torrent['Leechers'])?></td>
		</tr>
<?
		}

	}
?>
	</table>
	<div class="linkbox"><?=$Pages?></div>
</div>
<?
show_footer();
?>
