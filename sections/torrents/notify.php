<?
if (!check_perms('site_torrents_notify')) {
	error(403);
}

define('NOTIFICATIONS_PER_PAGE', 50);
define('NOTIFICATIONS_MAX_SLOWSORT', 10000);

$OrderBys = array(
		'time' => array('unt' => 'unt.TorrentID'),
		'size' => array('t' => 't.Size'),
		'snatches' => array('t' => 't.Snatched'),
		'seeders' => array('t' => 't.Seeders'),
		'leechers' => array('t' => 't.Leechers'),
		'year' => array('tg' => 'tnt.Year'));

if (empty($_GET['order_by']) || !isset($OrderBys[$_GET['order_by']])) {
	$_GET['order_by'] = 'time';
}
list($OrderTbl, $OrderCol) = each($OrderBys[$_GET['order_by']]);

if (!empty($_GET['order_way']) && $_GET['order_way'] == 'asc') {
	$OrderWay = 'ASC';
} else {
	$OrderWay = 'DESC';
}

if (!empty($_GET['filterid']) && is_number($_GET['filterid'])) {
	$FilterID = $_GET['filterid'];
} else {
	$FilterID = false;
}

list($Page,$Limit) = Format::page_limit(NOTIFICATIONS_PER_PAGE);

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = "desc") {
	global $OrderWay;
	if ($SortKey == $_GET['order_by']) {
		if ($OrderWay == "DESC") {
			$NewWay = "asc";
		} else {
			$NewWay = "desc";
		}
	} else {
		$NewWay = $DefaultWay;
	}
	return "?action=notify&amp;order_way=".$NewWay."&amp;order_by=".$SortKey."&amp;".Format::get_url(array('page','order_way','order_by'));
}
$UserID = $LoggedUser['ID'];

// Sorting by release year requires joining torrents_group, which is slow. Using a temporary table
// makes it speedy enough as long as there aren't too many records to create
if ($OrderTbl == 'tg') {
	$DB->query("SELECT COUNT(*) FROM users_notify_torrents AS unt
		JOIN torrents AS t ON t.ID=unt.TorrentID
		WHERE unt.UserID=$UserID".
		($FilterID
			? " AND FilterID=$FilterID"
			: ""));
	list($TorrentCount) = $DB->next_record();
	if ($TorrentCount > NOTIFICATIONS_MAX_SLOWSORT) {
		error("Due to performance issues, torrent lists with more than ".number_format(NOTIFICATIONS_MAX_SLOWSORT)." items cannot be ordered by release year.");
	}

	$DB->query("CREATE TEMPORARY TABLE temp_notify_torrents
		(TorrentID int, GroupID int, UnRead tinyint, FilterID int, Year smallint, PRIMARY KEY(GroupID, TorrentID), KEY(Year)) ENGINE=MyISAM");
	$DB->query("INSERT INTO temp_notify_torrents (TorrentID, GroupID, UnRead, FilterID)
		SELECT t.ID, t.GroupID, unt.UnRead, unt.FilterID
		FROM users_notify_torrents AS unt JOIN torrents AS t ON t.ID=unt.TorrentID
		WHERE unt.UserID=$UserID".
		($FilterID
			? " AND unt.FilterID=$FilterID"
			: ""));
	$DB->query("UPDATE temp_notify_torrents AS tnt JOIN torrents_group AS tg ON tnt.GroupID=tg.ID SET tnt.Year=tg.Year");

	$DB->query("SELECT TorrentID, GroupID, UnRead, FilterID
		FROM temp_notify_torrents AS tnt
		ORDER BY $OrderCol $OrderWay, GroupID $OrderWay LIMIT $Limit");
	$Results = $DB->to_array(false, MYSQLI_ASSOC, false);
} else {
	$DB->query("SELECT SQL_CALC_FOUND_ROWS unt.TorrentID, unt.UnRead, unt.FilterID, t.GroupID
		FROM users_notify_torrents AS unt
		JOIN torrents AS t ON t.ID = unt.TorrentID
		WHERE unt.UserID=$UserID".
		($FilterID
			? " AND unt.FilterID=$FilterID"
			: "")."
		ORDER BY $OrderCol $OrderWay LIMIT $Limit");
	$Results = $DB->to_array(false, MYSQLI_ASSOC, false);
	$DB->query("SELECT FOUND_ROWS()");
	list($TorrentCount) = $DB->next_record();
}

$GroupIDs = $FilterIDs = $UnReadIDs = array();
foreach ($Results as $Torrent) {
	$GroupIDs[$Torrent['GroupID']] = 1;
	$FilterIDs[$Torrent['FilterID']] = 1;
	if ($Torrent['UnRead']) {
		$UnReadIDs[] = $Torrent['TorrentID'];
	}
}
$Pages = Format::get_pages($Page, $TorrentCount, NOTIFICATIONS_PER_PAGE, 9);

if (!empty($GroupIDs)) {
	$GroupIDs = array_keys($GroupIDs);
	$FilterIDs = array_keys($FilterIDs);
	$TorrentGroups = Torrents::get_groups($GroupIDs);
	$TorrentGroups = $TorrentGroups['matches'];

	// Need some extra info that Torrents::get_groups() doesn't return
	$DB->query("SELECT ID, CategoryID FROM torrents_group WHERE ID IN (".implode(',', $GroupIDs).")");
	$GroupCategoryIDs = $DB->to_array('ID', MYSQLI_ASSOC, false);

	// Get the relevant filter labels
	$DB->query("SELECT ID, Label, Artists FROM users_notify_filters WHERE ID IN (".implode(',', $FilterIDs).")");
	$Filters = $DB->to_array('ID', MYSQLI_ASSOC, array(2));
	foreach ($Filters as &$Filter) {
		$Filter['Artists'] = explode('|', trim($Filter['Artists'], '|'));
	}
	unset($Filter);

	if (!empty($UnReadIDs)) {
		//Clear before header but after query so as to not have the alert bar on this page load
		$DB->query("UPDATE users_notify_torrents SET UnRead='0' WHERE UserID=".$LoggedUser['ID']." AND TorrentID IN (".implode(',', $UnReadIDs).")");
		$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
	}
}

View::show_header('My notifications', 'notifications');

?>
<div class="header">
	<h2>Latest notifications</h2>
</div>
<div class="linkbox">
<? if($FilterID) { ?>
	<a href="torrents.php?action=notify">View all</a>&nbsp;&nbsp;&nbsp;
<? } else { ?>
	<a href="torrents.php?action=notify_clear&amp;auth=<?=$LoggedUser['AuthKey']?>">Clear all</a>&nbsp;&nbsp;&nbsp;
	<a href="javascript:SuperGroupClear()">Clear selected</a>&nbsp;&nbsp;&nbsp;
	<a href="torrents.php?action=notify_catchup&amp;auth=<?=$LoggedUser['AuthKey']?>">Catch up</a>&nbsp;&nbsp;&nbsp;
<? } ?>
	<a href="user.php?action=notify">Edit filters</a>&nbsp;&nbsp;&nbsp;
</div>
<? if ($TorrentCount > NOTIFICATIONS_PER_PAGE) { ?>
<div class="linkbox">
	<?=$Pages?>
</div>
<?
}
if (empty($Results)) {
?>
<table class="layout border">
	<tr class="rowb">
		<td colspan="8" class="center">
			No new notifications found! <a href="user.php?action=notify">Edit notification filters</a>
		</td>
	</tr>
</table>
<?
} else {
	$FilterGroups = array();
	foreach ($Results as $Result) {
		if (!isset($FilterGroups[$Result['FilterID']])) {
			$FilterGroups[$Result['FilterID']] = array();
			$FilterGroups[$Result['FilterID']]['FilterLabel'] = isset($Filters[$Result['FilterID']])
				? $Filters[$Result['FilterID']]['Label']
				: false;
		}
		$FilterGroups[$Result['FilterID']][] = $Result;
	}

	foreach ($FilterGroups as $FilterID => $FilterResults) {
?>
<div class="header">
	<h3>
		Matches for <?=$FilterResults['FilterLabel'] !== false
				? '<a href="torrents.php?action=notify&amp;filterid='.$FilterID.'">'.$FilterResults['FilterLabel'].'</a>'
				: 'unknown filter['.$FilterID.']'?>
	</h3>
</div>
<div class="notify_filter_links">
	<a href="javascript:GroupClear($('#notificationform_<?=$FilterID?>').raw())">Clear selected in filter</a>&nbsp;&nbsp;&nbsp;
	<a href="torrents.php?action=notify_clear_filter&amp;filterid=<?=$FilterID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">Clear all in filter</a>&nbsp;&nbsp;&nbsp;
	<a href="torrents.php?action=notify_catchup_filter&amp;filterid=<?=$FilterID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">Mark all in filter as read</a>
</div>
<form class="manage_form" name="torrents" id="notificationform_<?=$FilterID?>">
<table class="torrent_table cats checkboxes border">
	<tr class="colhead">
		<td style="text-align: center"><input type="checkbox" name="toggle" onclick="ToggleBoxes(this.form, this.checked)" /></td>
		<td class="small cats_col"></td>
		<td style="width:100%;">Name<?=$TorrentCount <= NOTIFICATIONS_MAX_SLOWSORT ? ' / <a href="'.header_link('year').'">Year</a>' : ''?></td>
		<td>Files</td>
		<td><a href="<?=header_link('time')?>">Time</a></td>
		<td><a href="<?=header_link('size')?>">Size</a></td>
		<td class="sign"><a href="<?=header_link('snatches')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" alt="Snatches" title="Snatches" /></a></td>
		<td class="sign"><a href="<?=header_link('seeders')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/seeders.png" alt="Seeders" title="Seeders" /></a></td>
		<td class="sign"><a href="<?=header_link('leechers')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/leechers.png" alt="Leechers" title="Leechers" /></a></td>
	</tr>
<?
		unset($FilterResults['FilterLabel']);
		foreach ($FilterResults as $Result) {
			$TorrentID = $Result['TorrentID'];
			$GroupID = $Result['GroupID'];
			$GroupInfo = $TorrentGroups[$Result['GroupID']];
			if (!isset($GroupInfo['Torrents'][$TorrentID]) || !isset($GroupInfo['ID'])) {
				// If $GroupInfo['ID'] is unset, the torrent group associated with the torrent doesn't exist
				continue;
			}
			$TorrentInfo = $GroupInfo['Torrents'][$TorrentID];
			// generate torrent's title
			$DisplayName = '';
			if (!empty($GroupInfo['ExtendedArtists'])) {
				$MatchingArtists = array();
				foreach ($GroupInfo['ExtendedArtists'] as $GroupArtists) {
					foreach ($GroupArtists as $GroupArtist) {
						foreach ($Filters[$FilterID]['Artists'] as $FilterArtist) {
							if (!strcasecmp($FilterArtist, $GroupArtist['name'])) {
								$MatchingArtists[] = $GroupArtist['name'];
							}
						}
					}
				}
				$MatchingArtistsText = !empty($MatchingArtists)
					? 'Caught by filter for '.implode(', ', $MatchingArtists)
					: '';
				$DisplayName = Artists::display_artists($GroupInfo['ExtendedArtists'], true, true);
			}
			$DisplayName .= "<a href='torrents.php?id=$GroupID&amp;torrentid=$TorrentID#torrent$TorrentID' title='View Torrent'>".$GroupInfo['Name']."</a>";

			$GroupCategoryID = $GroupCategoryIDs[$GroupID]['CategoryID'];
			if ($GroupCategoryID == 1) {
				if ($GroupInfo['Year'] > 0) {
					$DisplayName .= " [$GroupInfo[Year]]";
				}
				if ($GroupInfo['ReleaseType'] > 0) {
					$DisplayName.= " [".$ReleaseTypes[$GroupInfo['ReleaseType']]."]";
				}
			}

			// append extra info to torrent title
			$ExtraInfo = Torrents::torrent_info($TorrentInfo, true, true);

			$TagLinks = array();
			if ($GroupInfo['TagList'] != '') {
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
	<tr class="torrent" id="torrent<?=$TorrentID?>"<?=$MatchingArtistsText ? 'title="'.$MatchingArtistsText.'"' : ''?>>
		<td style="text-align: center"><input type="checkbox" value="<?=$TorrentID?>" id="clear_<?=$TorrentID?>" /></td>
		<td class="center cats_col"><div title="<?=ucfirst(str_replace('_',' ',$MainTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1])).' tags_'.str_replace('.','_',$MainTag)?>"></div></td>
		<td>
			<span>
				[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a> 
<?			if (Torrents::can_use_token($TorrentInfo)) { ?>
				| <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
				| <a href="#" onclick="Clear(<?=$TorrentID?>);return false;" title="Remove from notifications list">CL</a> ]
			</span>
			<strong><?=$DisplayName?></strong> <?=$ExtraInfo?>
			<? if ($Result['UnRead']) {
				echo '<strong class="new">New!</strong>';
			} ?>
			<?=$TorrentTags?>
		</td>
		<td><?=$TorrentInfo['FileCount']?></td>
		<td style="text-align:right" class="nobr"><?=time_diff($TorrentInfo['Time'])?></td>
		<td class="nobr" style="text-align:right"><?=Format::get_size($TorrentInfo['Size'])?></td>
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
<? View::show_footer(); ?>
