<?
if(!check_perms('site_torrents_notify')) { error(403); }

define('NOTIFICATIONS_PER_PAGE', 50);
list($Page,$Limit) = page_limit(NOTIFICATIONS_PER_PAGE);

$TokenTorrents = $Cache->get_value('users_tokens_'.$LoggedUser['ID']);
if (empty($TokenTorrents)) {
	$DB->query("SELECT TorrentID FROM users_freeleeches WHERE UserID=$LoggedUser[ID] AND Expired=FALSE");
	$TokenTorrents = $DB->collect('TorrentID');
	$Cache->cache_value('users_tokens_'.$LoggedUser['ID'], $TokenTorrents);
}


$UserID = $LoggedUser['ID'];

$Results = $DB->query("SELECT SQL_CALC_FOUND_ROWS unt.TorrentID, unt.UnRead, unt.FilterID, unf.Label, t.GroupID
		FROM users_notify_torrents AS unt
		JOIN torrents AS t ON t.ID = unt.TorrentID
		LEFT JOIN users_notify_filters AS unf ON unf.ID = unt.FilterID
		WHERE unt.UserID=$UserID".
		((!empty($_GET['filterid']) && is_number($_GET['filterid']))
			? " AND unf.ID='$_GET[filterid]'"
			: "")."
		ORDER BY TorrentID DESC LIMIT $Limit");
$GroupIDs = array_unique($DB->collect('GroupID'));

$DB->query('SELECT FOUND_ROWS()');
list($TorrentCount) = $DB->next_record();
$Debug->log_var($TorrentCount, 'Torrent count');
$Pages = get_pages($Page, $TorrentCount, NOTIFICATIONS_PER_PAGE, 9);

if(count($GroupIDs)) {
	$TorrentGroups = get_groups($GroupIDs);
	$TorrentGroups = $TorrentGroups['matches'];

	// Need some extra info that get_groups() doesn't return
	$DB->query("SELECT ID, CategoryID FROM torrents_group WHERE ID IN (".implode(',', $GroupIDs).")");
	$GroupCategoryIDs = $DB->to_array('ID', MYSQLI_ASSOC, false);

	//Clear before header but after query so as to not have the alert bar on this page load
	$DB->query("UPDATE users_notify_torrents SET UnRead='0' WHERE UserID=".$LoggedUser['ID']);
	$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
}

show_header('My notifications', 'notifications');
$DB->set_query_id($Results);
?>
<div class="header">
	<h2>Latest notifications <a href="torrents.php?action=notify_clear&amp;auth=<?=$LoggedUser['AuthKey']?>">(clear all)</a> <a href="javascript:SuperGroupClear()">(clear selected)</a> <a href="user.php?action=notify">(edit filters)</a></h2>
</div>
<div class="linkbox">
	<?=$Pages?>
</div>
<? if(!$DB->record_count()) { ?>
<table class="layout border">
	<tr class="rowb">
		<td colspan="8" class="center">
			No new notifications found! <a href="user.php?action=notify">Edit notification filters</a>
		</td>
	</tr>
</table>
<? } else {
	$FilterGroups = array();
	while($Result = $DB->next_record(MYSQLI_ASSOC)) {
		if(!$Result['FilterID']) {
			$Result['FilterID'] = 0;
		}
		if(!isset($FilterGroups[$Result['FilterID']])) {
			$FilterGroups[$Result['FilterID']] = array();
			$FilterGroups[$Result['FilterID']]['FilterLabel'] = $Result['Label'] ? $Result['Label'] : false;
		}
		array_push($FilterGroups[$Result['FilterID']], $Result);
	}
	unset($Result);
	$Debug->log_var($FilterGroups, 'Filter groups');
	foreach($FilterGroups as $FilterID => $FilterResults) {
?>
<h3>
	Matches for <?=$FilterResults['FilterLabel'] !== false
			? '<a href="torrents.php?action=notify&amp;filterid='.$FilterID.'">'.$FilterResults['FilterLabel'].'</a>'
			: 'unknown filter['.$FilterID.']'?>
	<a href="torrents.php?action=notify_cleargroup&amp;filterid=<?=$FilterID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">(clear)</a>
	<a href="javascript:GroupClear($('#notificationform_<?=$FilterID?>').raw())">(clear selected)</a>
</h3>
<form id="notificationform_<?=$FilterID?>">
<table class="torrent_table cats checkboxes border">
	<tr class="colhead">
		<td style="text-align: center"><input type="checkbox" name="toggle" onclick="ToggleBoxes(this.form, this.checked)" /></td>
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
			$TorrentID = $Result['TorrentID'];
			$GroupID = $Result['GroupID'];
			$GroupCategoryID = $GroupCategoryIDs[$GroupID]['CategoryID'];

			$GroupInfo = $TorrentGroups[$Result['GroupID']];
			if(!$TorrentInfo = $GroupInfo['Torrents'][$TorrentID]) {
				continue;
			}

			// generate torrent's title
			$DisplayName = '';
			if(!empty($GroupInfo['ExtendedArtists'])) {
				$DisplayName = display_artists($GroupInfo['ExtendedArtists'], true, true);
			}
			$DisplayName .= "<a href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID#torrent$TorrentID' title='View Torrent'>".$GroupInfo['Name']."</a>";

			if($GroupCategoryID == 1) {
				if($GroupInfo['Year'] > 0) {
					$DisplayName .= " [$GroupInfo[Year]]";
				}
				if($GroupInfo['ReleaseType'] > 0) {
					$DisplayName.= " [".$ReleaseTypes[$GroupInfo['ReleaseType']]."]";
				}
			}

			// append extra info to torrent title
			$ExtraInfo = torrent_info($TorrentInfo, true, true);
			$Debug->log_var($ExtraInfo, "Extra torrent info ($TorrentID)");

			$TagLinks = array();
			if($GroupInfo['TagList'] != '') {
				$TorrentTags = explode(' ', $GroupInfo['TagList']);
				$MainTag = $TorrentTags[0];
				foreach ($TorrentTags as $TagKey => $TagName) {
					$TagName = str_replace('_', '.', $TagName);
					$TagLinks[] = '<a href="torrents.php?taglist='.$TagName.'">'.$TagName.'</a>';
				}
				$TagLinks = implode(', ', $TagLinks);
				$TorrentTags = '<br /><div class="tags">'.$TagLinks.'</div>';
			} else {
				$TorrentTags = '';
				$MainTag = $Categories[$GroupCategoryID-1];
			}

		// print row
?>
	<tr class="torrent" id="torrent<?=$TorrentID?>">
		<td style="text-align: center"><input type="checkbox" value="<?=$TorrentID?>" id="clear_<?=$TorrentID?>" /></td>
		<td class="center cats_col"><div title="<?=ucfirst(str_replace('_',' ',$MainTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1])).' tags_'.str_replace('.','_',$MainTag)?>"></div></td>
		<td>
			<span>
				[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a> 
<?			if (($LoggedUser['FLTokens'] > 0) && ($TorrentInfo['Size'] < 1073741824)
				&& !in_array($TorrentID, $TokenTorrents) && empty($TorrentInfo['FreeTorrent']) && ($LoggedUser['CanLeech'] == '1')) { ?>
				| <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
				| <a href="#" onclick="Clear(<?=$TorrentID?>);return false;" title="Remove from notifications list">CL</a> ]
			</span>
			<strong><?=$DisplayName?></strong> <?=$ExtraInfo?>
			<? if($Result['UnRead']) { echo '<strong class="new">New!</strong>'; } ?>
			<?=$TorrentTags?>
		</td>
		<td><?=$TorrentInfo['FileCount']?></td>
		<td style="text-align:right" class="nobr"><?=time_diff($TorrentInfo['Time'])?></td>
		<td class="nobr" style="text-align:right"><?=get_size($TorrentInfo['Size'])?></td>
		<td style="text-align:right"><?=number_format($TorrentInfo['Snatched'])?></td>
		<td style="text-align:right"><?=number_format($TorrentInfo['Seeders'])?></td>
		<td style="text-align:right"><?=number_format($TorrentInfo['Leechers'])?></td>
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
