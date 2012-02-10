<?
if(!check_perms('site_torrents_notify')) { error(403); }

define('NOTIFICATIONS_PER_PAGE', 50);
list($Page,$Limit) = page_limit(NOTIFICATIONS_PER_PAGE);

$TokenTorrents = $Cache->get_value('users_tokens_'.$UserID);
if (empty($TokenTorrents)) {
	$DB->query("SELECT TorrentID FROM users_freeleeches WHERE UserID=$UserID AND Expired=FALSE");
	$TokenTorrents = $DB->collect('TorrentID');
	$Cache->cache_value('users_tokens_'.$UserID, $TokenTorrents);
}

$Results = $DB->query("SELECT SQL_CALC_FOUND_ROWS
		t.ID,
		g.ID,
		g.Name,
		g.CategoryID,
		g.TagList,
		t.Size,
		t.FileCount,
		t.Format,
		t.Encoding,
		t.Media,
		t.Scene,
		t.RemasterYear,
		g.Year,
		t.RemasterYear,
		t.RemasterTitle,
		t.Snatched,
		t.Seeders,
		t.Leechers,
		t.Time,
		t.HasLog,
		t.HasCue,
		t.LogScore,
		t.FreeTorrent,
		tln.TorrentID AS LogInDB,
		unt.UnRead,
		unt.FilterID,
		unf.Label,
		g.ReleaseType
		FROM users_notify_torrents AS unt
		JOIN torrents AS t ON t.ID=unt.TorrentID
		JOIN torrents_group AS g ON g.ID = t.GroupID 
		LEFT JOIN users_notify_filters AS unf ON unf.ID=unt.FilterID
		LEFT JOIN torrents_logs_new AS tln ON tln.TorrentID=t.ID
		WHERE unt.UserID='$LoggedUser[ID]'
		GROUP BY t.ID
		ORDER BY t.ID DESC LIMIT $Limit");
$DB->query('SELECT FOUND_ROWS()');
list($TorrentCount) = $DB->next_record();

//Clear before header but after query so as to not have the alert bar on this page load
$DB->query("UPDATE users_notify_torrents SET UnRead='0' WHERE UserID=".$LoggedUser['ID']);
$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
show_header('My notifications','notifications');

$DB->set_query_id($Results);

$Pages=get_pages($Page,$TorrentCount,NOTIFICATIONS_PER_PAGE,9);



?>
<h2>Latest notifications <a href="torrents.php?action=notify_clear&amp;auth=<?=$LoggedUser['AuthKey']?>">(clear all)</a> <a href="javascript:SuperGroupClear()">(clear selected)</a> <a href="user.php?action=notify">(edit filters)</a></h2>
<div class="linkbox">
	<?=$Pages?>
</div>
<? if($DB->record_count()==0) { ?>
<table class="border">
	<tr class="rowb">
		<td colspan="8" class="center">
			No new notifications found! <a href="user.php?action=notify">Edit notification filters</a>
		</td>
	</tr>
</table>
<? } else {
	$FilterGroups = array();
	while($Result = $DB->next_record()) {
		if(!$Result['FilterID']) {
			$Result['FilterID'] = 0;
		}
		if(!isset($FilterGroups[$Result['FilterID']])) {
			$FilterGroups[$Result['FilterID']] = array();
			$FilterGroups[$Result['FilterID']]['FilterLabel'] = ($Result['FilterID'] && !empty($Result['Label']) ? $Result['Label'] : 'unknown filter'.($Result['FilterID']?' ['.$Result['FilterID'].']':''));
		}
		array_push($FilterGroups[$Result['FilterID']], $Result);
	}
	unset($Result);
	foreach($FilterGroups as $ID => $FilterResults) {
?>
<h3>Matches for <?=$FilterResults['FilterLabel']?> (<a href="torrents.php?action=notify_cleargroup&amp;filterid=<?=$ID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">Clear</a>) <a href="javascript:GroupClear($('#notificationform_<?=$ID?>').raw())">(clear selected)</a></h3>
<form id="notificationform_<?=$ID?>">
<table class="border">
	<tr class="colhead">
		<td style="text-align: center"><input type="checkbox" name="toggle" onClick="ToggleBoxes(this.form, this.checked)" /></td>
		<td class="small cats_col"></td>
		<td style="width:100%;"><strong>Name</strong></td>
		<td><strong>Files</strong></td>
		<td><strong>Time</strong></td>
		<td><strong>Size</strong></td>
		<td style="text-align:right"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" alt="Snatches" title="Snatches" /></td>
		<td style="text-align:right"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/seeders.png" alt="Seeders" title="Seeders" /></td>
		<td style="text-align:right"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/leechers.png" alt="Leechers" title="Leechers" /></td>
	</tr>
<?
		unset($FilterResults['FilterLabel']);
		foreach($FilterResults as $Result) {
			list($TorrentID, $GroupID, $GroupName, $GroupCategoryID, $TorrentTags, $Size, $FileCount, $Format, $Encoding,
				$Media, $Scene, $RemasterYear, $GroupYear, $RemasterYear, $RemasterTitle, $Snatched, $Seeders, 
				$Leechers, $NotificationTime, $HasLog, $HasCue, $LogScore, $FreeTorrent, $LogInDB, $UnRead, $FilterLabel, $FilterLabel, $ReleaseType) = $Result;
			// generate torrent's title
			$DisplayName='';
			
			$Artists = get_artist($GroupID);
			
			if(!empty($Artists)) {
				$DisplayName = display_artists($Artists, true, true);
			}
			
			if (in_array($TorrentID, $TokenTorrents) && empty($Torrent['FreeTorrent'])) {
				$Data['PersonalFL'] = 1;
			}
			
			$DisplayName.= "<a href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID#torrent$TorrentID'  title='View Torrent'>".$GroupName."</a>";
	
			if($GroupCategoryID==1 && $GroupYear>0) {
				$DisplayName.= " [$GroupYear]";
			}
			if($GroupCategoryID==1 && $ReleaseType>0) {
				$DisplayName.= " [".$ReleaseTypes[$ReleaseType]."]";
			}
	
			// append extra info to torrent title
			$ExtraInfo='';
			$AddExtra='';
			if($Format) 		{ $ExtraInfo.=$Format; $AddExtra=' / '; }
			if($Encoding) 		{ $ExtraInfo.=$AddExtra.$Encoding; $AddExtra=' / '; }
			if($HasLog) 		{ $ExtraInfo.=$AddExtra.'Log'; $AddExtra=' / '; }
			if($HasLog && $LogInDB)	{ $ExtraInfo.=' ('.(int) $LogScore.'%)'; }
			if($HasCue) 		{ $ExtraInfo.=$AddExtra.'Cue'; $AddExtra=' / '; }
			if($Media) 		{ $ExtraInfo.=$AddExtra.$Media; $AddExtra=' / '; }
			if($Scene) 		{ $ExtraInfo.=$AddExtra.'Scene'; $AddExtra=' / '; }
			if($RemasterYear)	{ $ExtraInfo.=$AddExtra.$RemasterYear; $AddExtra=' '; }
			if($RemasterTitle) 	{ $ExtraInfo.=$AddExtra.$RemasterTitle; }
			if($ExtraInfo!='') 	{
				$ExtraInfo = "- [$ExtraInfo]";
			}
			
			$TagLinks=array();
			if($TorrentTags!='') {
				$TorrentTags=explode(' ',$TorrentTags);
				$MainTag = $TorrentTags[0];
				foreach ($TorrentTags as $TagKey => $TagName) {
					$TagName = str_replace('_','.',$TagName);
					$TagLinks[]='<a href="torrents.php?taglist='.$TagName.'">'.$TagName.'</a>';
				}
				$TagLinks = implode(', ', $TagLinks);
				$TorrentTags='<br /><div class="tags">'.$TagLinks.'</div>';
			} else {
				$MainTag = $Categories[$GroupCategoryID-1];
			}

		// print row
?>
	<tr class="group_torrent" id="torrent<?=$TorrentID?>">
		<td style="text-align: center"><input type="checkbox" value="<?=$TorrentID?>" id="clear_<?=$TorrentID?>" /></td>
		<td class="center cats_cols"><div title="<?=ucfirst(str_replace('_',' ',$MainTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1])).' tags_'.str_replace('.','_',$MainTag)?>"></div></td>
		<td>
			<span>
				[<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a> 
<?			if (($LoggedUser['FLTokens'] > 0) && ($Size < 1073741824) 
				&& !in_array($TorrentID, $TokenTorrents) && empty($FreeTorrent) && ($LoggedUser['CanLeech'] == '1')) { ?>
				| <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&usetoken=1" title="Use a FL Token" onClick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
				| <a href="#" onclick="Clear(<?=$TorrentID?>);return false;" title="Remove from notifications list">CL</a>]
			</span>
			<strong><?=$DisplayName?></strong> <?=$ExtraInfo ?>
			<? if($UnRead) { echo '<strong>New!</strong>'; } ?>
			<?=$TorrentTags?>
		</td>
		<td><?=$FileCount ?></td>
		<td style="text-align:right" class="nobr"><?=time_diff($NotificationTime)?></td>
		<td class="nobr" style="text-align:right"><?=get_size($Size)?></td>
		<td style="text-align:right"><?=number_format($Snatched)?></td>
		<td style="text-align:right"><?=number_format($Seeders)?></td>
		<td style="text-align:right"><?=number_format($Leechers)?></td>
	</tr>
<?
		}
?>
</table>
</form>
<?
	}
}

?>
<div class="linkbox">
	<?=$Pages?>
</div>

<?
show_footer();
?>
