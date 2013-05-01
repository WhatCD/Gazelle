<?
// We need these to do our rankification
include(SERVER_ROOT.'/sections/torrents/ranking_funcs.php');


$UserVotes = Votes::get_user_votes($LoggedUser['ID']);

if (!empty($_GET['advanced']) && check_perms('site_advanced_top10')) {
	$Details = 'all';
	$Limit = 25;

	if ($_GET['tags']) {
		$Tags = explode(',', str_replace(".","_",trim($_GET['tags'])));
		foreach ($Tags as $Tag) {
			$Tag = preg_replace('/[^a-z0-9_]/', '', $Tag);
			if ($Tag != '') {
				$Where[]="g.TagList REGEXP '[[:<:]]".db_string($Tag)."[[:>:]]'";
			}
		}
	}
	$Year1 = (int)$_GET['year1'];
	$Year2 = (int)$_GET['year2'];
	if ($Year1 > 0 && $Year2 <= 0) {
		$Where[] = "g.Year = $Year1";
	} elseif ($Year1 > 0 && $Year2 > 0) {
		$Where[] = "g.Year BETWEEN $Year1 AND $Year2";
	} elseif ($Year2 > 0 && $Year1 <= 0) {
		$Where[] = "g.Year <= $Year2";
	}
} else {
	$Details = 'all';
	// defaults to 10 (duh)
	$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
	$Limit = in_array($Limit, array(25, 100, 250)) ? $Limit : 25;
}
$Filtered = !empty($Where);

if ($_GET['anyall'] == 'any' && !empty($Where)) {
	$Where = '('.implode(' OR ', $Where).')';
} else {
	$Where = implode(' AND ', $Where);
}
$WhereSum = (empty($Where)) ? '' : md5($Where);

// Unlike the other top 10s, this query just gets some raw stats
// We'll need to do some fancy-pants stuff to translate it into
// BPCI scores before getting the torrent data
$Query = 'SELECT v.GroupID, v.Ups, v.Total, v.Score
			FROM torrents_votes AS v';
if (!empty($Where)) {
	$Query .= " JOIN torrents_group AS g ON g.ID = v.GroupID
				WHERE $Where AND ";
} else {
	$Query .= ' WHERE ';
}
$Query .= "Score > 0 ORDER BY Score DESC LIMIT $Limit";

$TopVotes = $Cache->get_value('top10votes_'.$Limit.$WhereSum);
if ($TopVotes === false) {
	if ($Cache->get_query_lock('top10votes')) {
		$DB->query($Query);

		$Results = $DB->collect('GroupID');
		$Data = $DB->to_array('GroupID');

		$Groups = Torrents::get_groups($Results);

		$TopVotes = array();
		foreach ($Results as $GroupID) {
			$TopVotes[$GroupID] = $Groups['matches'][$GroupID];
			$TopVotes[$GroupID]['Ups'] = $Data[$GroupID]['Ups'];
			$TopVotes[$GroupID]['Total'] = $Data[$GroupID]['Total'];
			$TopVotes[$GroupID]['Score'] = $Data[$GroupID]['Score'];
		}

		$Cache->cache_value('top10votes_'.$Limit.$WhereSum,$TopVotes,60*30);
		$Cache->clear_query_lock('top10votes');
	} else {
		$TopVotes = false;
	}
}

View::show_header('Top '.$Limit.' Voted Groups','browse,voting');
?>
<div class="thin">
	<div class="header">
		<h2>Top <?=$Limit?> Voted Groups</h2>
		<div class="linkbox">
			<a href="top10.php?type=torrents" class="brackets">Torrents</a>
			<a href="top10.php?type=users" class="brackets">Users</a>
			<a href="top10.php?type=tags" class="brackets">Tags</a>
			<a href="top10.php?type=votes" class="brackets"><strong>Favorites</strong></a>
		</div>
	</div>
<?

if (check_perms('site_advanced_top10')) { ?>
	<form class="search_form" name="votes" action="" method="get">
		<input type="hidden" name="advanced" value="1" />
		<input type="hidden" name="type" value="votes" />
		<table cellpadding="6" cellspacing="1" border="0" class="layout border" width="100%">
			<tr id="tagfilter">
				<td class="label">Tags (comma-separated):</td>
				<td class="ft_taglist">
					<input type="text" name="tags" size="75" value="<? if (!empty($_GET['tags'])) { echo display_str($_GET['tags']);} ?>" />&nbsp;
					<input type="radio" id="rdoAll" name="anyall" value="all"<?=($_GET['anyall'] != 'any' ? ' checked="checked"' : '')?> /><label for="rdoAll"> All</label>&nbsp;&nbsp;
					<input type="radio" id="rdoAny" name="anyall" value="any"<?=($_GET['anyall'] == 'any' ? ' checked="checked"' : '')?> /><label for="rdoAny"> Any</label>
				</td>
			</tr>
			<tr id="yearfilter">
				<td class="label">Year:</td>
				<td class="ft_year">
					<input type="text" name="year1" size="4" value="<? if (!empty($_GET['year1'])) { echo display_str($_GET['year1']);} ?>" />
					to
					<input type="text" name="year2" size="4" value="<? if (!empty($_GET['year2'])) { echo display_str($_GET['year2']);} ?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
					<input type="submit" value="Filter torrents" />
				</td>
			</tr>
		</table>
	</form>
<?
}

$Bookmarks = Bookmarks::all_bookmarks('torrent');
?>
	<h3>Top <?=$Limit.' '.$Caption?>
<?
if (empty($_GET['advanced'])) { ?>
		<small class="top10_quantity_links">
<?
	switch ($Limit) {
		case 100: ?>
			- <a href="top10.php?type=votes" class="brackets">Top 25</a>
			- <span class="brackets">Top 100</span>
			- <a href="top10.php?type=votes&amp;limit=250" class="brackets">Top 250</a>
<?			break;
		case 250: ?>
			- <a href="top10.php?type=votes" class="brackets">Top 25</a>
			- <a href="top10.php?type=votes&amp;limit=100" class="brackets">Top 100</a>
			- <span class="brackets">Top 250</span>
<?			break;
		default: ?>
			- <span class="brackets">Top 25</span>
			- <a href="top10.php?type=votes&amp;limit=100" class="brackets">Top 100</a>
			- <a href="top10.php?type=votes&amp;limit=250" class="brackets">Top 250</a>
<?	} ?>
		</small>
<?
} ?>
	</h3>
<?

// This code was copy-pasted from collages and should die in a fire
$Number = 0;
$NumGroups = 0;
foreach ($TopVotes as $GroupID => $Group) {
	extract(Torrents::array_group($Group));
	$Ups = $Group['Ups'];
	$Total = $Group['Total'];
	$Score = $Group['Score'];

	$IsBookmarked = in_array($GroupID, $Bookmarks);

	// Handle stats and stuff
	$Number++;
	$NumGroups++;

	$TorrentTags = new Tags($TagList);

	$DisplayName = $Number.' - ';

	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5])|| !empty($ExtendedArtists[6])) {
			unset($ExtendedArtists[2]);
			unset($ExtendedArtists[3]);
			$DisplayName .= Artists::display_artists($ExtendedArtists);
	} elseif (count($GroupArtists) > 0) {
			$DisplayName .= Artists::display_artists(array('1'=>$GroupArtists));
	}

	$DisplayName .= '<a href="torrents.php?id='.$GroupID.'" title="View Torrent" dir="ltr">'.$GroupName.'</a>';
	if ($GroupYear > 0) {
		$DisplayName = $DisplayName. " [$GroupYear]";
	}
	if ($GroupVanityHouse) {
		$DisplayName .= ' [<abbr title="This is a Vanity House release">VH</abbr>]';
	}
	// Start an output buffer, so we can store this output in $TorrentTable
	ob_start();

	if (count($Torrents) > 1 || $GroupCategoryID == 1) :
		// Grouped torrents
		$GroupSnatched = false;
		foreach ($Torrents as &$Torrent) {
			if (($Torrent['IsSnatched'] = Torrents::has_snatched($Torrent['ID'])) && !$GroupSnatched) {
				$GroupSnatched = true;
			}
		}
		unset($Torrent);
		$SnatchedGroupClass = $GroupSnatched ? ' snatched_group' : '';
?>
				<tr class="group discog<?=$SnatchedGroupClass?>" id="group_<?=$GroupID?>">
					<td class="center">
						<div title="View" id="showimg_<?=$GroupID?>" class="show_torrents">
							<a href="#" class="show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event)" title="Collapse this group. Hold &quot;Ctrl&quot; while clicking to collapse all groups on this page."></a>
						</div>
					</td>
					<td class="center">
						<div title="<?=$TorrentTags->title()?>" class="<?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>"></div>
					</td>
					<td class="big_info">
<?		if ($LoggedUser['CoverArt']) : ?>
						<div class="group_image float_left clear">
							<? ImageTools::cover_thumb($WikiImage, $GroupCategoryID) ?>
						</div>
<?		endif; ?>
						<div class="group_info clear">

							<strong><?=$DisplayName?></strong> <!--<?Votes::vote_link($GroupID,$UserVotes[$GroupID]['Type']);?>-->
<?		if ($IsBookmarked) { ?>
							<span class="bookmark" style="float: right;"><a href="#" class="bookmarklink_torrent_<?=$GroupID?> brackets remove_bookmark" title="Remove bookmark" onclick="Unbookmark('torrent',<?=$GroupID?>,'Bookmark');return false;">Unbookmark</a></span>
<?		} else { ?>
							<span class="bookmark" style="float: right;"><a href="#" class="bookmarklink_torrent_<?=$GroupID?> brackets add_bookmark" title="Add bookmark" onclick="Bookmark('torrent',<?=$GroupID?>,'Unbookmark');return false;">Bookmark</a></span>
<?		} ?>
							<div class="tags"><?=$TorrentTags->format()?></div>

						</div>
					</td>
					<td colspan="4" class="votes_info_td"><strong><?=number_format($Ups)?></strong> upvotes out of <strong><?=number_format($Total)?></strong> total (<span title="Score: <?=number_format($Score * 100,4)?>">Score: <?=number_format($Score * 100)?></span>).</td>
				</tr>
<?
		$LastRemasterYear = '-';
		$LastRemasterTitle = '';
		$LastRemasterRecordLabel = '';
		$LastRemasterCatalogueNumber = '';
		$LastMedia = '';

		$EditionID = 0;
		unset($FirstUnknown);

		foreach ($Torrents as $TorrentID => $Torrent) :
			//Get report info, use the cache if available, if not, add to it.
			$Reported = false;
			$Reports = get_reports($TorrentID);
			if (count($Reports) > 0) {
				$Reported = true;
			}
			if ($Torrent['Remastered'] && !$Torrent['RemasterYear']) {
				$FirstUnknown = !isset($FirstUnknown);
			}
			$SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';

			if ($Torrent['RemasterTitle'] != $LastRemasterTitle || $Torrent['RemasterYear'] != $LastRemasterYear ||
			$Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel || $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber || $FirstUnknown || $Torrent['Media'] != $LastMedia) {
				$EditionID++;
?>
		<tr class="group_torrent groupid_<?=$GroupID?> edition<?=$SnatchedGroupClass?> hidden">
			<td colspan="7" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition. Hold &quot;Ctrl&quot; while clicking to collapse all editions in this torrent group.">&minus;</a> <?=Torrents::edition_string($Torrent, $Group)?></strong></td>
		</tr>
<?
			}
			$LastRemasterTitle = $Torrent['RemasterTitle'];
			$LastRemasterYear = $Torrent['RemasterYear'];
			$LastRemasterRecordLabel = $Torrent['RemasterRecordLabel'];
			$LastRemasterCatalogueNumber = $Torrent['RemasterCatalogueNumber'];
			$LastMedia = $Torrent['Media'];
?>
		<tr class="group_torrent torrent_row groupid_<?=$GroupID?> edition_<?=$EditionID?><?=$SnatchedTorrentClass . $SnatchedGroupClass?> hidden">
			<td colspan="3">
				<span>
					[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
<?			if (Torrents::can_use_token($Torrent)) { ?>
					| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
					| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a> ]
				</span>
				&nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?><? if ($Reported) { ?> / <strong class="torrent_label tl_reported" title="Reported">Reported</strong><? } ?></a>
			</td>
			<td class="nobr"><?=Format::get_size($Torrent['Size'])?></td>
			<td><?=number_format($Torrent['Snatched'])?></td>
			<td<?=($Torrent['Seeders'] == 0) ? ' class="r00"' : '' ?>><?=number_format($Torrent['Seeders'])?></td>
			<td><?=number_format($Torrent['Leechers'])?></td>
		</tr>
<?
		endforeach;
	else:
		// Viewing a type that does not require grouping

		list($TorrentID, $Torrent) = each($Torrents);
		$Torrent['IsSnatched'] = Torrents::has_snatched($TorrentID);

		$DisplayName = $Number .' - <a href="torrents.php?id='.$GroupID.'" title="View Torrent" dir="ltr">'.$GroupName.'</a>';
		if ($Torrent['IsSnatched']) {
			$DisplayName .= ' ' . Format::torrent_label('Snatched!');
		}
		if ($Torrent['FreeTorrent'] == '1') {
			$DisplayName .= ' ' . Format::torrent_label('Freeleech!');
		} elseif ($Torrent['FreeTorrent'] == '2') {
			$DisplayName .= ' ' . Format::torrent_label('Neutral leech!');
		} elseif (Torrents::has_token($TorrentID)) {
			$DisplayName .= ' ' . Format::torrent_label('Personal freeleech!');
		}
		$SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';
?>
		<tr class="torrent torrent_row<?=$SnatchedTorrentClass . $SnatchedGroupClass?>" id="group_<?=$GroupID?>">
			<td></td>
			<td class="center">
				<div title="<?=$TorrentTags->title()?>" class="<?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>">
				</div>
			</td>
			<td class="nobr big_info">
<?		if ($LoggedUser['CoverArt']) : ?>
				<div class="group_image float_left clear">
					<? ImageTools::cover_thumb($WikiImage, $GroupCategoryID) ?>
				</div>
<?		endif; ?>
				<div class="group_info clear">
					<span>
						[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
<?		if (Torrents::can_use_token($Torrent)) { ?>
						| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?		} ?>
						| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a>
<?		if ($IsBookmarked) { ?>
						| <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="remove_bookmark" title="Remove bookmark" onclick="Unbookmark('torrent',<?=$GroupID?>,'Bookmark');return false;">Unbookmark</a>
<?		} else { ?>
						| <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="add_bookmark" title="Add bookmark" onclick="Bookmark('torrent',<?=$GroupID?>,'Unbookmark');return false;">Bookmark</a>
<?		} ?>
						]
					</span>
					<strong><?=$DisplayName?></strong> <!--<?Votes::vote_link($GroupID,$UserVotes[$GroupID]['Type']);?>-->
					<div class="tags"><?=$TorrentTags->format()?></div>
				</div>
			</td>
			<td class="nobr"><?=Format::get_size($Torrent['Size'])?></td>
			<td><?=number_format($Torrent['Snatched'])?></td>
			<td<?=($Torrent['Seeders'] == 0) ? ' class="r00"' : '' ?>><?=number_format($Torrent['Seeders'])?></td>
			<td><?=number_format($Torrent['Leechers'])?></td>
		</tr>
<?
	endif;
	$TorrentTable.=ob_get_clean();
}
?>
<table class="torrent_table grouping cats" id="discog_table">
	<tr class="colhead_dark">
		<td><!-- expand/collapse --></td>
		<td><!-- Category --></td>
		<td width="70%"><strong>Torrents</strong></td>
		<td>Size</td>
		<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" alt="Snatches" title="Snatches" /></td>
		<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/seeders.png" alt="Seeders" title="Seeders" /></td>
		<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/leechers.png" alt="Leechers" title="Leechers" /></td>
	</tr>
<?
if ($TorrentList === false) { ?>
	<tr>
		<td colspan="7" class="center">Server is busy processing another top list request. Please try again in a minute.</td>
	</tr>
<?
} elseif (count($TopVotes) == 0) { ?>
	<tr>
		<td colspan="7" class="center">No torrents were found that meet your criteria.</td>
	</tr>
<?
} else {
	echo $TorrentTable;
}
?>
</table>
</div>
<?
View::show_footer();
?>
