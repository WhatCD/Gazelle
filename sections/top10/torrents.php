<?
$Where = array();

if(!empty($_GET['advanced']) && check_perms('site_advanced_top10')) {
	$Details = 'all';
	$Limit = 10;
	
	if($_GET['tags']) {
		$Tags = explode(',', str_replace(".","_",trim($_GET['tags'])));
		foreach ($Tags as $Tag) {
			$Tag = preg_replace('/[^a-z0-9_]/', '', $Tag);
			if($Tag != '') {
				$Where[]="g.TagList REGEXP '[[:<:]]".db_string($Tag)."[[:>:]]'";
			}
		}
	}
	
	if($_GET['format']) {
		if(in_array($_GET['format'], $Formats)) {
			$Where[]="t.Format='".db_string($_GET['format'])."'";
		}
	}
	
} else {
	// error out on invalid requests (before caching)
	if(isset($_GET['details'])) {
		if(in_array($_GET['details'], array('day','week','overall','snatched','data','seeded'))) {
			$Details = $_GET['details'];
		} else {
			error(404);
		}
	} else {
		$Details = 'all';
	}
	
	// defaults to 10 (duh)
	$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
	$Limit = in_array($Limit, array(10, 100, 250)) ? $Limit : 10;
}
$Filtered = !empty($Where);
show_header('Top '.$Limit.' Torrents');
?>
<div class="thin">
	<h2> Top <?=$Limit?> Torrents </h2>
	<div class="linkbox">
		<a href="top10.php?type=torrents"><strong>[Torrents]</strong></a>
		<a href="top10.php?type=users">[Users]</a>
		<a href="top10.php?type=tags">[Tags]</a>
	</div>
<?

if(check_perms('site_advanced_top10')) {
?>
	<div>
		<form action="" method="get">
			<input type="hidden" name="advanced" value="1" />
			<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
				<tr>
					<td class="label">Tags (comma-separated):</td>
					<td>
						<input type="text" name="tags" size="75" value="<? if(!empty($_GET['tags'])) { echo display_str($_GET['tags']);} ?>" />
					</td>
				</tr>
				<tr>
					<td class="label">Format</td>
					<td>
						<select name="format" style="width:auto;">
							<option value="">Any</option>
<?	foreach ($Formats as $FormatName) { ?>
							<option value="<?=display_str($FormatName)?>" <? if(isset($_GET['format']) && $FormatName==$_GET['format']) { ?>selected="selected"<? } ?>><?=display_str($FormatName)?></option>
<?	} ?>					</select>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="center">
						<input type="submit" value="Filter torrents" />
					</td>
				</tr>
			</table>	
		</form>
	</div>
<?
}

// default setting to have them shown
$DisableFreeTorrentTop10 = (isset($LoggedUser['DisableFreeTorrentTop10']) ? $LoggedUser['DisableFreeTorrentTop10'] : 0);
// did they just toggle it?
if(isset($_GET['freeleech'])) {
	// what did they choose?
	$NewPref = ($_GET['freeleech'] == 'hide') ? 1 : 0;

	// Pref id different
	if ($NewPref != $DisableFreeTorrentTop10) {
		$DisableFreeTorrentTop10 = $NewPref;
		update_site_options($LoggedUser['ID'], array('DisableFreeTorrentTop10' => $DisableFreeTorrentTop10));
	}
}

// Modify the Where query
if ($DisableFreeTorrentTop10) {
	$Where[] = "t.FreeTorrent='0'";
}

// The link should say the opposite of the current setting
$FreeleechToggleName = ($DisableFreeTorrentTop10 ? 'show' : 'hide');
$FreeleechToggleQuery = get_url(array('freeleech'));

if (!empty($FreeleechToggleQuery))
	$FreeleechToggleQuery .= '&amp;';

$FreeleechToggleQuery .= 'freeleech=' . $FreeleechToggleName;

?>
	<div style="text-align: right;">
		<a href="top10.php?<?=$FreeleechToggleQuery?>">[<?=ucfirst($FreeleechToggleName)?> Freeleech in Top 10]</a>
	</div>
<?

$Where = implode(' AND ', $Where);

$WhereSum = (empty($Where)) ? '' : md5($Where);
$BaseQuery = "SELECT
	t.ID,
	g.ID,
	g.Name,
	g.CategoryID,
	g.TagList,
	t.Format,
	t.Encoding,
	t.Media,
	t.Scene,
	t.HasLog,
	t.HasCue,
	t.LogScore,
	t.RemasterYear,
	g.Year,
	t.RemasterTitle,
	t.Snatched,
	t.Seeders,
	t.Leechers,
	((t.Size * t.Snatched) + (t.Size * 0.5 * t.Leechers)) AS Data,
	g.ReleaseType
	FROM torrents AS t
	LEFT JOIN torrents_group AS g ON g.ID = t.GroupID ";
	
if($Details=='all' || $Details=='day') {
	if (!$TopTorrentsActiveLastDay = $Cache->get_value('top10tor_day_'.$Limit.$WhereSum)) {
		$DayAgo = time_minus(86400);
		$Query = $BaseQuery.' WHERE t.Seeders>0 AND ';
		if (!empty($Where)) { $Query .= $Where.' AND '; }
		$Query .= "
			t.Time>'$DayAgo'
			ORDER BY (t.Seeders + t.Leechers) DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsActiveLastDay = $DB->to_array(false, MYSQLI_NUM);
		$Cache->cache_value('top10tor_day_'.$Limit.$WhereSum,$TopTorrentsActiveLastDay,3600*2);
	}
	generate_torrent_table('Most Active Torrents Uploaded in the Past Day', 'day', $TopTorrentsActiveLastDay, $Limit);
}
if($Details=='all' || $Details=='week') {
	if (!$TopTorrentsActiveLastWeek = $Cache->get_value('top10tor_week_'.$Limit.$WhereSum)) {
		$WeekAgo = time_minus(604800);
		$Query = $BaseQuery.' WHERE ';
		if (!empty($Where)) { $Query .= $Where.' AND '; }
		$Query .= "
			t.Time>'$WeekAgo'
			ORDER BY (t.Seeders + t.Leechers) DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsActiveLastWeek = $DB->to_array(false, MYSQLI_NUM);
		$Cache->cache_value('top10tor_week_'.$Limit.$WhereSum,$TopTorrentsActiveLastWeek,3600*6);
	}
	generate_torrent_table('Most Active Torrents Uploaded in the Past Week', 'week', $TopTorrentsActiveLastWeek, $Limit);
}

if($Details=='all' || $Details=='overall') {
	if (!$TopTorrentsActiveAllTime = $Cache->get_value('top10tor_overall_'.$Limit.$WhereSum)) {
		// IMPORTANT NOTE - we use WHERE t.Seeders>500 in order to speed up this query. You should remove it!
		$Query = $BaseQuery;
		if ($Details=='all' && !$Filtered) {
			$Query .= " WHERE t.Seeders>=500 ";
			if (!empty($Where)) { $Query .= ' AND '.$Where; }
		}
		elseif (!empty($Where)) { $Query .= ' WHERE '.$Where; }
		$Query .= "
			ORDER BY (t.Seeders + t.Leechers) DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsActiveAllTime = $DB->to_array(false, MYSQLI_NUM);
		$Cache->cache_value('top10tor_overall_'.$Limit.$WhereSum,$TopTorrentsActiveAllTime,3600*6);
	}
	generate_torrent_table('Most Active Torrents of All Time', 'overall', $TopTorrentsActiveAllTime, $Limit);
}

if(($Details=='all' || $Details=='snatched') && !$Filtered) {
	if (!$TopTorrentsSnatched = $Cache->get_value('top10tor_snatched_'.$Limit.$WhereSum)) {
		$Query = $BaseQuery;
		if (!empty($Where)) { $Query .= ' WHERE '.$Where; }
		$Query .= "
			ORDER BY t.Snatched DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsSnatched = $DB->to_array(false, MYSQLI_NUM);
		$Cache->cache_value('top10tor_snatched_'.$Limit.$WhereSum,$TopTorrentsSnatched,3600*6);
	}
	generate_torrent_table('Most Snatched Torrents', 'snatched', $TopTorrentsSnatched, $Limit);
}

if(($Details=='all' || $Details=='data') && !$Filtered) {
	if (!$TopTorrentsTransferred = $Cache->get_value('top10tor_data_'.$Limit.$WhereSum)) {
		// IMPORTANT NOTE - we use WHERE t.Snatched>100 in order to speed up this query. You should remove it!
		$Query = $BaseQuery;
		if ($Details=='all') {
			$Query .= " WHERE t.Snatched>=100 ";
			if (!empty($Where)) { $Query .= ' AND '.$Where; }
		}
		$Query .= "
			ORDER BY Data DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsTransferred = $DB->to_array(false, MYSQLI_NUM);
		$Cache->cache_value('top10tor_data_'.$Limit.$WhereSum,$TopTorrentsTransferred,3600*6);
	}
	generate_torrent_table('Most Data Transferred Torrents', 'data', $TopTorrentsTransferred, $Limit);
}

if(($Details=='all' || $Details=='seeded') && !$Filtered) {
	$TopTorrentsSeeded = $Cache->get_value('top10tor_seeded_'.$Limit.$WhereSum);
	if ($TopTorrentsSeeded === FALSE) {
		$Query = $BaseQuery;
		if (!empty($Where)) { $Query .= ' WHERE '.$Where; }
		$Query .= "
			ORDER BY t.Seeders DESC
			LIMIT $Limit;";
		$DB->query($Query);
		$TopTorrentsSeeded = $DB->to_array(false, MYSQLI_NUM);
		$Cache->cache_value('top10tor_seeded_'.$Limit.$WhereSum,$TopTorrentsSeeded,3600*6);
	}
	generate_torrent_table('Best Seeded Torrents', 'seeded', $TopTorrentsSeeded, $Limit);
}

?>
</div>
<?
show_footer();

// generate a table based on data from most recent query to $DB
function generate_torrent_table($Caption, $Tag, $Details, $Limit) {
	global $LoggedUser,$Categories,$Debug,$ReleaseTypes;
?>
		<h3>Top <?=$Limit.' '.$Caption?>
<?	if(empty($_GET['advanced'])){ ?> 
		<small>
			- [<a href="top10.php?type=torrents&amp;limit=100&amp;details=<?=$Tag?>">Top 100</a>]
			- [<a href="top10.php?type=torrents&amp;limit=250&amp;details=<?=$Tag?>">Top 250</a>]
		</small>
<?	} ?> 
		</h3>
	<table class="border">
	<tr class="colhead">
		<td class="center" style="width:15px;"></td>
		<td></td>
		<td><strong>Name</strong></td>
		<td style="text-align:right"><strong>Data</strong></td>
		<td style="text-align:right"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" alt="Snatches" title="Snatches" /></td>
		<td style="text-align:right"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/seeders.png" alt="Seeders" title="Seeders" /></td>
		<td style="text-align:right"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/leechers.png" alt="Leechers" title="Leechers" /></td>
		<td style="text-align:right"><strong>Peers</strong></td>
	</tr>
<?
	// in the unlikely event that query finds 0 rows...
	if(empty($Details)) {
?>
		<tr class="rowb">
			<td colspan="9" class="center">
				Found no torrents matching the criteria
			</td>
		</tr>
		</table><br />
<?
		return;
	}
	$Rank = 0;
	foreach ($Details as $Detail) {
		$GroupIDs[] = $Detail[1];
	}
	$Artists = get_artists($GroupIDs);

	foreach ($Details as $Detail) {
		list($TorrentID,$GroupID,$GroupName,$GroupCategoryID,$TorrentTags,
			$Format,$Encoding,$Media,$Scene,$HasLog,$HasCue,$LogScore,$Year,$GroupYear,
			$RemasterTitle,$Snatched,$Seeders,$Leechers,$Data,$ReleaseType) = $Detail;

		// highlight every other row
		$Rank++;
		$Highlight = ($Rank % 2 ? 'a' : 'b');

		// generate torrent's title
		$DisplayName='';
		
		
		if(!empty($Artists[$GroupID])) {
			$DisplayName = display_artists($Artists[$GroupID], true, true);
		}
		
		$DisplayName.= "<a href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID'  title='View Torrent'>$GroupName</a>";

		if($GroupCategoryID==1 && $GroupYear>0) {
			$DisplayName.= " [$GroupYear]";
		}
		if($GroupCategoryID==1 && $ReleaseType > 0) {
			$DisplayName.= ' ['.$ReleaseTypes[$ReleaseType].']';
		}

		// append extra info to torrent title
		$ExtraInfo='';
		$AddExtra='';
		if($Format) { $ExtraInfo.=$Format; $AddExtra=' / '; }
		if($Encoding) { $ExtraInfo.=$AddExtra.$Encoding; $AddExtra=' / '; }
		"FLAC / Lossless / Log (100%) / Cue / CD";
		if($HasLog) { $ExtraInfo.=$AddExtra."Log (".$LogScore."%)"; $AddExtra=' / '; }
		if($HasCue) { $ExtraInfo.=$AddExtra."Cue"; $AddExtra=' / '; }
		if($Media) { $ExtraInfo.=$AddExtra.$Media; $AddExtra=' / '; }
		if($Scene) { $ExtraInfo.=$AddExtra.'Scene'; $AddExtra=' / '; }
		if($Year>0) { $ExtraInfo.=$AddExtra.$Year; $AddExtra=' '; }
		if($RemasterTitle) { $ExtraInfo.=$AddExtra.$RemasterTitle; }
		if($ExtraInfo!='') {
			$ExtraInfo = "- [$ExtraInfo]";
		}
		
		$TagList=array();
		
		$PrimaryTag = '';
		if($TorrentTags!='') {
			$TorrentTags=explode(' ',$TorrentTags);
			foreach ($TorrentTags as $TagKey => $TagName) {
				$TagName = str_replace('_','.',$TagName);
				$TagList[]='<a href="torrents.php?taglist='.$TagName.'">'.$TagName.'</a>';
			}
			$PrimaryTag = $TorrentTags[0];
			$TagList = implode(', ', $TagList);
			$TorrentTags='<br /><div class="tags">'.$TagList.'</div>';
		}

		// print row
?>
	<tr class="group_torrent row<?=$Highlight?>">
		<td style="padding:8px;text-align:center;"><strong><?=$Rank?></strong></td>
<?
		//fix array offset php error
		if ($GroupCategoryID > 0) {
			$GroupCatOffset = $GroupCategoryID - 1;
		}
?>
		<td class="center cats_col"><div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCatOffset]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>"></div></td>
		<td>
		<span>[<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>]</span>
			<strong><?=$DisplayName?></strong> <?=$ExtraInfo?>
			<?=$TorrentTags?>
		</td>
		<td style="text-align:right" class="nobr"><?=get_size($Data)?></td>
		<td style="text-align:right"><?=number_format((double) $Snatched)?></td>
		<td style="text-align:right"><?=number_format((double) $Seeders)?></td>
		<td style="text-align:right"><?=number_format((double) $Leechers)?></td>
		<td style="text-align:right"><?=number_format($Seeders+$Leechers)?></td>
	</tr>
<?
	}
?>
	</table><br />
<?
}
?>
