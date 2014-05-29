<?php
if (!check_perms('site_torrents_notify')) {
	error(403);
}

define('NOTIFICATIONS_PER_PAGE', 50);
define('NOTIFICATIONS_MAX_SLOWSORT', 10000);

$OrderBys = array(
		'time'     => array('unt' => 'unt.TorrentID'),
		'size'     => array('t'   => 't.Size'),
		'snatches' => array('t'   => 't.Snatched'),
		'seeders'  => array('t'   => 't.Seeders'),
		'leechers' => array('t'   => 't.Leechers'),
		'year'     => array('tg'  => 'tnt.Year'));

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

list($Page, $Limit) = Format::page_limit(NOTIFICATIONS_PER_PAGE);

// The "order by x" links on columns headers
function header_link($SortKey, $DefaultWay = 'desc') {
	global $OrderWay;
	if ($SortKey == $_GET['order_by']) {
		if ($OrderWay == 'DESC') {
			$NewWay = 'asc';
		} else {
			$NewWay = 'desc';
		}
	} else {
		$NewWay = $DefaultWay;
	}
	return "?action=notify&amp;order_way=$NewWay&amp;order_by=$SortKey&amp;".Format::get_url(array('page', 'order_way', 'order_by'));
}
//Perhaps this should be a feature at some point
if (check_perms('users_mod') && !empty($_GET['userid']) && is_number($_GET['userid']) && $_GET['userid'] != $LoggedUser['ID']) {
	$UserID = $_GET['userid'];
	$Sneaky = true;
} else {
	$Sneaky = false;
	$UserID = $LoggedUser['ID'];
}

// Sorting by release year requires joining torrents_group, which is slow. Using a temporary table
// makes it speedy enough as long as there aren't too many records to create
if ($OrderTbl == 'tg') {
	$DB->query("
		SELECT COUNT(*)
		FROM users_notify_torrents AS unt
			JOIN torrents AS t ON t.ID=unt.TorrentID
		WHERE unt.UserID=$UserID".
		($FilterID
			? " AND FilterID=$FilterID"
			: ''));
	list($TorrentCount) = $DB->next_record();
	if ($TorrentCount > NOTIFICATIONS_MAX_SLOWSORT) {
		error('Due to performance issues, torrent lists with more than '.number_format(NOTIFICATIONS_MAX_SLOWSORT).' items cannot be ordered by release year.');
	}

	$DB->query("
		CREATE TEMPORARY TABLE temp_notify_torrents
			(TorrentID int, GroupID int, UnRead tinyint, FilterID int, Year smallint, PRIMARY KEY(GroupID, TorrentID), KEY(Year))
		ENGINE=MyISAM");
	$DB->query("
		INSERT IGNORE INTO temp_notify_torrents (TorrentID, GroupID, UnRead, FilterID)
		SELECT t.ID, t.GroupID, unt.UnRead, unt.FilterID
		FROM users_notify_torrents AS unt
			JOIN torrents AS t ON t.ID=unt.TorrentID
		WHERE unt.UserID=$UserID".
		($FilterID
			? " AND unt.FilterID=$FilterID"
			: ''));
	$DB->query("
		UPDATE temp_notify_torrents AS tnt
			JOIN torrents_group AS tg ON tnt.GroupID = tg.ID
		SET tnt.Year = tg.Year");

	$DB->query("
		SELECT TorrentID, GroupID, UnRead, FilterID
		FROM temp_notify_torrents AS tnt
		ORDER BY $OrderCol $OrderWay, GroupID $OrderWay
		LIMIT $Limit");
	$Results = $DB->to_array(false, MYSQLI_ASSOC, false);
} else {
	$DB->query("
		SELECT
			SQL_CALC_FOUND_ROWS
			unt.TorrentID,
			unt.UnRead,
			unt.FilterID,
			t.GroupID
		FROM users_notify_torrents AS unt
			JOIN torrents AS t ON t.ID = unt.TorrentID
		WHERE unt.UserID = $UserID".
		($FilterID
			? " AND unt.FilterID = $FilterID"
			: '')."
		ORDER BY $OrderCol $OrderWay
		LIMIT $Limit");
	$Results = $DB->to_array(false, MYSQLI_ASSOC, false);
	$DB->query('SELECT FOUND_ROWS()');
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

	// Get the relevant filter labels
	$DB->query('
		SELECT ID, Label, Artists
		FROM users_notify_filters
		WHERE ID IN ('.implode(',', $FilterIDs).')');
	$Filters = $DB->to_array('ID', MYSQLI_ASSOC, array('Artists'));
	foreach ($Filters as &$Filter) {
		$Filter['Artists'] = explode('|', trim($Filter['Artists'], '|'));
		foreach ($Filter['Artists'] as &$FilterArtist) {
			$FilterArtist = mb_strtolower($FilterArtist, 'UTF-8');
		}
		$Filter['Artists'] = array_flip($Filter['Artists']);
	}
	unset($Filter);

	if (!empty($UnReadIDs)) {
		//Clear before header but after query so as to not have the alert bar on this page load
		$DB->query("
			UPDATE users_notify_torrents
			SET UnRead = '0'
			WHERE UserID = ".$LoggedUser['ID'].'
				AND TorrentID IN ('.implode(',', $UnReadIDs).')');
		$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
	}
}
if ($Sneaky) {
	$UserInfo = Users::user_info($UserID);
	View::show_header($UserInfo['Username'].'\'s notifications', 'notifications');
} else {
	View::show_header('My notifications', 'notifications');
}
?>
<div class="thin widethin">
<div class="header">
	<h2>Latest notifications</h2>
</div>
<div class="linkbox">
<?	if ($FilterID) { ?>
	<a href="torrents.php?action=notify<?=($Sneaky ? "&amp;userid=$UserID" : '')?>" class="brackets">View all</a>&nbsp;&nbsp;&nbsp;
<?	} elseif (!$Sneaky) { ?>
	<a href="torrents.php?action=notify_clear&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Clear all old</a>&nbsp;&nbsp;&nbsp;
	<a href="#" onclick="clearSelected(); return false;" class="brackets">Clear selected</a>&nbsp;&nbsp;&nbsp;
	<a href="torrents.php?action=notify_catchup&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Catch up</a>&nbsp;&nbsp;&nbsp;
<?	} ?>
	<a href="user.php?action=notify" class="brackets">Edit filters</a>&nbsp;&nbsp;&nbsp;
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
			No new notifications found! <a href="user.php?action=notify" class="brackets">Edit notification filters</a>
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
<?		if ($FilterResults['FilterLabel'] !== false) { ?>
		Matches for <a href="torrents.php?action=notify&amp;filterid=<?=$FilterID.($Sneaky ? "&amp;userid=$UserID" : '')?>"><?=$FilterResults['FilterLabel']?></a>
<?		} else { ?>
		Matches for unknown filter[<?=$FilterID?>]
<?		} ?>
	</h3>
</div>
<div class="linkbox notify_filter_links">
<?		if (!$Sneaky) { ?>
	<a href="#" onclick="clearSelected(<?=$FilterID?>); return false;" class="brackets">Clear selected in filter</a>
	<a href="torrents.php?action=notify_clear_filter&amp;filterid=<?=$FilterID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Clear all old in filter</a>
	<a href="torrents.php?action=notify_catchup_filter&amp;filterid=<?=$FilterID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Mark all in filter as read</a>
<?		} ?>
</div>
<form class="manage_form" name="torrents" id="notificationform_<?=$FilterID?>" action="">
<table class="torrent_table cats checkboxes border">
	<tr class="colhead">
		<td style="text-align: center;"><input type="checkbox" name="toggle" onclick="toggleChecks('notificationform_<?=$FilterID?>', this, '.notify_box')" /></td>
		<td class="small cats_col"></td>
		<td style="width: 100%;">Name<?=$TorrentCount <= NOTIFICATIONS_MAX_SLOWSORT ? ' / <a href="'.header_link('year').'">Year</a>' : ''?></td>
		<td>Files</td>
		<td><a href="<?=header_link('time')?>">Time</a></td>
		<td><a href="<?=header_link('size')?>">Size</a></td>
		<td class="sign snatches"><a href="<?=header_link('snatches')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/snatched.png" class="tooltip" alt="Snatches" title="Snatches" /></a></td>
		<td class="sign seeders"><a href="<?=header_link('seeders')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/seeders.png" class="tooltip" alt="Seeders" title="Seeders" /></a></td>
		<td class="sign leechers"><a href="<?=header_link('leechers')?>"><img src="static/styles/<?=$LoggedUser['StyleName']?>/images/leechers.png" class="tooltip" alt="Leechers" title="Leechers" /></a></td>
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
						if (isset($Filters[$FilterID]['Artists'][mb_strtolower($GroupArtist['name'], 'UTF-8')])) {
							$MatchingArtists[] = $GroupArtist['name'];
						}
					}
				}
				$MatchingArtistsText = (!empty($MatchingArtists) ? 'Caught by filter for '.implode(', ', $MatchingArtists) : '');
				$DisplayName = Artists::display_artists($GroupInfo['ExtendedArtists'], true, true);
			}
			$DisplayName .= "<a href=\"torrents.php?id=$GroupID&amp;torrentid=$TorrentID#torrent$TorrentID\" class=\"tooltip\" title=\"View torrent\" dir=\"ltr\">" . $GroupInfo['Name'] . '</a>';

			$GroupCategoryID = $GroupInfo['CategoryID'];
			if ($GroupCategoryID == 1) {
				if ($GroupInfo['Year'] > 0) {
					$DisplayName .= " [$GroupInfo[Year]]";
				}
				if ($GroupInfo['ReleaseType'] > 0) {
					$DisplayName .= ' ['.$ReleaseTypes[$GroupInfo['ReleaseType']].']';
				}
			}

			// append extra info to torrent title
			$ExtraInfo = Torrents::torrent_info($TorrentInfo, true, true);

			$TorrentTags = new Tags($GroupInfo['TagList']);

			if ($GroupInfo['TagList'] == '')
				$TorrentTags->set_primary($Categories[$GroupCategoryID - 1]);

		// print row
?>
	<tr class="torrent torrent_row<?=($TorrentInfo['IsSnatched'] ? ' snatched_torrent' : '') . ($GroupInfo['Flags']['IsSnatched'] ? ' snatched_group' : '') . ($MatchingArtistsText ? ' tooltip" title="'.display_str($MatchingArtistsText) : '')?>" id="torrent<?=$TorrentID?>">
		<td style="text-align: center;">
			<input type="checkbox" class="notify_box notify_box_<?=$FilterID?>" value="<?=$TorrentID?>" id="clear_<?=$TorrentID?>" tabindex="1" />
		</td>
		<td class="center cats_col">
			<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>"></div>
		</td>
		<td class="big_info">
<? if ($LoggedUser['CoverArt']) { ?>
			<div class="group_image float_left clear">
				<? ImageTools::cover_thumb($GroupInfo['WikiImage'], $GroupCategoryID) ?>
			</div>
<? } ?>
			<div class="group_info clear">
				<span>
					[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?			if (Torrents::can_use_token($TorrentInfo)) { ?>
					| <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?
			}
			if (!$Sneaky) { ?>
					| <a href="#" onclick="clearItem(<?=$TorrentID?>); return false;" class="tooltip" title="Remove from notifications list">CL</a>
<?			} ?> ]
				</span>
				<strong><?=$DisplayName?></strong>
				<div class="torrent_info">
					<?=$ExtraInfo?>
					<? if ($Result['UnRead']) {
					echo '<strong class="new">New!</strong>';
					} ?>
<?				if (Bookmarks::has_bookmarked('torrent', $GroupID)) { ?>
					<span class="remove_bookmark float_right">
						<a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="brackets" onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove bookmark</a>
					</span>
<?				} else { ?>
					<span class="add_bookmark float_right">
						<a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="brackets" onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
<?				} ?>
					</span>
				</div>
				<div class="tags"><?=$TorrentTags->format()?></div>
			</div>
		</td>
		<td><?=$TorrentInfo['FileCount']?></td>
		<td class="number_column nobr"><?=time_diff($TorrentInfo['Time'])?></td>
		<td class="number_column nobr"><?=Format::get_size($TorrentInfo['Size'])?></td>
		<td class="number_column"><?=number_format($TorrentInfo['Snatched'])?></td>
		<td class="number_column"><?=number_format($TorrentInfo['Seeders'])?></td>
		<td class="number_column"><?=number_format($TorrentInfo['Leechers'])?></td>
	</tr>
<?
		}
?>
</table>
</form>
<?
	}
}

	if ($Pages) { ?>
	<div class="linkbox"><?=$Pages?></div>
<?	} ?>
</div>
<? View::show_footer(); ?>
