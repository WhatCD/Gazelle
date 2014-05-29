<?
// We need these to do our rankification
include(SERVER_ROOT.'/sections/torrents/ranking_funcs.php');


$UserVotes = Votes::get_user_votes($LoggedUser['ID']);

if (!empty($_GET['advanced']) && check_perms('site_advanced_top10')) {
	$Details = 'all';
	$Limit = 25;

	if (!empty($_GET['tags'])) {
		$TagsAny = isset($_GET['anyall']) && $_GET['anyall'] === 'any';
		$Tags = explode(',', str_replace('.', '_', trim($_GET['tags'])));
		foreach ($Tags as $Tag) {
			$Tag = preg_replace('/[^a-z0-9_]/', '', $Tag);
			if ($Tag != '') {
				$TagWhere[] = "g.TagList REGEXP '[[:<:]]".db_string($Tag)."[[:>:]]'";
			}
		}
		$Operator = $TagsAny ? ' OR ' : ' AND ';
		$Where[] = '('.implode($Operator, $TagWhere).')';
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

if (!empty($Where)) {
	$Where = implode(' AND ', $Where);
}
$WhereSum = (empty($Where)) ? '' : md5($Where);

// Unlike the other top 10s, this query just gets some raw stats
// We'll need to do some fancy-pants stuff to translate it into
// BPCI scores before getting the torrent data
$Query = '
	SELECT v.GroupID, v.Ups, v.Total, v.Score
	FROM torrents_votes AS v';
if (!empty($Where)) {
	$Query .= "
		JOIN torrents_group AS g ON g.ID = v.GroupID
	WHERE $Where AND ";
} else {
	$Query .= '
	WHERE ';
}
$Query .= "
		Score > 0
	ORDER BY Score DESC
	LIMIT $Limit";

$TopVotes = $Cache->get_value('top10votes_'.$Limit.$WhereSum);
if ($TopVotes === false) {
	if ($Cache->get_query_lock('top10votes')) {
		$DB->query($Query);

		$Results = $DB->to_array('GroupID', MYSQLI_ASSOC, false);
		$Ranks = Votes::calc_ranks($DB->to_pair('GroupID', 'Score', false));

		$Groups = Torrents::get_groups(array_keys($Results));

		$TopVotes = array();
		foreach ($Results as $GroupID => $Votes) {
			$TopVotes[$GroupID] = $Groups[$GroupID];
			$TopVotes[$GroupID]['Ups'] = $Votes['Ups'];
			$TopVotes[$GroupID]['Total'] = $Votes['Total'];
			$TopVotes[$GroupID]['Score'] = $Votes['Score'];
			$TopVotes[$GroupID]['Rank'] = $Ranks[$GroupID];
		}

		$Cache->cache_value('top10votes_'.$Limit.$WhereSum, $TopVotes, 60 * 30);
		$Cache->clear_query_lock('top10votes');
	} else {
		$TopVotes = false;
	}
}
View::show_header("Top $Limit Voted Groups",'browse,voting');
?>
<div class="thin">
	<div class="header">
		<h2>Top <?=$Limit?> Voted Groups</h2>
		<? Top10View::render_linkbox("votes"); ?>
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
					<input type="radio" id="rdoAll" name="anyall" value="all"<?=(!isset($TagsAny) ? ' checked="checked"' : '')?> /><label for="rdoAll"> All</label>&nbsp;&nbsp;
					<input type="radio" id="rdoAny" name="anyall" value="any"<?=(isset($TagsAny) ? ' checked="checked"' : '')?> /><label for="rdoAny"> Any</label>
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
	<h3>Top <?=$Limit?>
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

$Number = 0;
$TorrentTable = '';
foreach ($TopVotes as $GroupID => $Group) {
	extract(Torrents::array_group($Group));
	$UpVotes = $Group['Ups'];
	$TotalVotes = $Group['Total'];
	$Score = $Group['Score'];
	$DownVotes = $TotalVotes - $UpVotes;

	$IsBookmarked = in_array($GroupID, $Bookmarks);
	$UserVote = isset($UserVotes[$GroupID]) ? $UserVotes[$GroupID]['Type'] : '';

	$TorrentTags = new Tags($TagList);
	$DisplayName = "$Group[Rank] - ";

	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5])|| !empty($ExtendedArtists[6])) {
			unset($ExtendedArtists[2]);
			unset($ExtendedArtists[3]);
			$DisplayName .= Artists::display_artists($ExtendedArtists);
	} elseif (count($Artists) > 0) {
			$DisplayName .= Artists::display_artists(array('1' => $Artists));
	}

	$DisplayName .= '<a href="torrents.php?id='.$GroupID.'" class="tooltip" title="View torrent group" dir="ltr">'.$GroupName.'</a>';
	if ($GroupYear > 0) {
		$DisplayName = $DisplayName. " [$GroupYear]";
	}
	if ($GroupVanityHouse) {
		$DisplayName .= ' [<abbr class="tooltip" title="This is a Vanity House release">VH</abbr>]';
	}
	// Start an output buffer, so we can store this output in $TorrentTable
	ob_start();

	if (count($Torrents) > 1 || $GroupCategoryID == 1) {
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
						<div id="showimg_<?=$GroupID?>" class="show_torrents">
							<a href="#" class="tooltip show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event);" title="Expand this group. Hold &quot;Ctrl&quot; while clicking to expand all groups on this page."></a>
						</div>
					</td>
					<td class="center cats_col">
						<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>"></div>
					</td>
					<td class="big_info">
<?		if ($LoggedUser['CoverArt']) { ?>
						<div class="group_image float_left clear">
							<? ImageTools::cover_thumb($WikiImage, $GroupCategoryID) ?>
						</div>
<?		} ?>
						<div class="group_info clear">

							<strong><?=$DisplayName?></strong> <!--<?Votes::vote_link($GroupID, $UserVote);?>-->
<?		if ($IsBookmarked) { ?>
							<span class="remove_bookmark float_right">
								<a href="#" class="bookmarklink_torrent_<?=$GroupID?> brackets" onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove bookmark</a>
							</span>
<?		} else { ?>
							<span class="add_bookmark float_right">
								<a href="#" class="bookmarklink_torrent_<?=$GroupID?> brackets" onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
							</span>
<?		} ?>
							<div class="tags"><?=$TorrentTags->format()?></div>

						</div>
					</td>
					<td colspan="4" class="votes_info_td">
						<span style="white-space: nowrap;">
							<span class="favoritecount_small tooltip" title="<?=$UpVotes . ($UpVotes == 1 ? ' upvote' : ' upvotes')?>"><span id="upvotes"><?=number_format($UpVotes)?></span> <span class="vote_album_up">&and;</span></span>
							&nbsp; &nbsp;
							<span class="favoritecount_small tooltip" title="<?=$DownVotes . ($DownVotes == 1 ? ' downvote' : ' downvotes')?>"><span id="downvotes"><?=number_format($DownVotes)?></span> <span class="vote_album_down">&or;</span></span>
							&nbsp;
							<span style="float: right;"><span class="favoritecount_small" id="totalvotes"><?=number_format($TotalVotes)?></span> Total</span>
						</span>
						<br />
						<span style="white-space: nowrap;">
							<span class="tooltip_interactive" title="&lt;span style=&quot;font-weight: bold;&quot;&gt;Score: <?=number_format($Score * 100, 4)?>&lt;/span&gt;&lt;br /&gt;&lt;br /&gt;This is the lower bound of the binomial confidence interval &lt;a href=&quot;wiki.php?action=article&amp;id=1037&quot;&gt;described here&lt;/a&gt;, multiplied by 100." data-title-plain="Score: <?=number_format($Score * 100, 4)?>. This is the lower bound of the binomial confidence interval described in the Favorite Album Votes wiki article, multiplied by 100.">Score: <span class="favoritecount_small"><?=number_format($Score * 100, 1)?></span></span>
							&nbsp; | &nbsp;
							<span class="favoritecount_small"><?=number_format($UpVotes / $TotalVotes * 100, 1)?>%</span> positive
						</span>
					</td>
				</tr>
<?
		$LastRemasterYear = '-';
		$LastRemasterTitle = '';
		$LastRemasterRecordLabel = '';
		$LastRemasterCatalogueNumber = '';
		$LastMedia = '';

		$EditionID = 0;
		unset($FirstUnknown);

		foreach ($Torrents as $TorrentID => $Torrent) {
			//Get report info, use the cache if available, if not, add to it.
			$Reported = false;
			$Reports = Torrents::get_reports($TorrentID);
			if (count($Reports) > 0) {
				$Reported = true;
			}
			if ($Torrent['Remastered'] && !$Torrent['RemasterYear']) {
				$FirstUnknown = !isset($FirstUnknown);
			}
			$SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';

			if ($Torrent['RemasterTitle'] != $LastRemasterTitle
				|| $Torrent['RemasterYear'] != $LastRemasterYear
				|| $Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel
				|| $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber
				|| $FirstUnknown
				|| $Torrent['Media'] != $LastMedia
				) {
				$EditionID++;
?>
		<tr class="group_torrent groupid_<?=$GroupID?> edition<?=$SnatchedGroupClass?> hidden">
			<td colspan="7" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event);" class="tooltip" title="Collapse this edition. Hold &quot;Ctrl&quot; while clicking to collapse all editions in this torrent group.">&minus;</a> <?=Torrents::edition_string($Torrent, $Group)?></strong></td>
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
					[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?			if (Torrents::can_use_token($Torrent)) { ?>
					| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
					| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
				</span>
				&nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?><? if ($Reported) { ?> / <strong class="torrent_label tl_reported">Reported</strong><? } ?></a>
			</td>
			<td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?></td>
			<td class="number_column"><?=number_format($Torrent['Snatched'])?></td>
			<td class="number_column<?=($Torrent['Seeders'] == 0) ? ' r00' : '' ?>"><?=number_format($Torrent['Seeders'])?></td>
			<td class="number_column"><?=number_format($Torrent['Leechers'])?></td>
		</tr>
<?
		}
	} else { //if (count($Torrents) > 1 || $GroupCategoryID == 1)
		// Viewing a type that does not require grouping

		list($TorrentID, $Torrent) = each($Torrents);
		$Torrent['IsSnatched'] = Torrents::has_snatched($TorrentID);

		$DisplayName = $Number .' - <a href="torrents.php?id='.$GroupID.'" class="tooltip" title="View torrent group" dir="ltr">'.$GroupName.'</a>';
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
			<td class="center cats_col">
				<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>">
				</div>
			</td>
			<td class="nobr big_info">
<?		if ($LoggedUser['CoverArt']) { ?>
				<div class="group_image float_left clear">
					<? ImageTools::cover_thumb($WikiImage, $GroupCategoryID) ?>
				</div>
<?		} ?>
				<div class="group_info clear">
					<span>
						[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?		if (Torrents::can_use_token($Torrent)) { ?>
						| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?		} ?>
						| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a>
<?		if ($IsBookmarked) { ?>
						| <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="remove_bookmark" onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove bookmark</a>
<?		} else { ?>
						| <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="add_bookmark" onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
<?		} ?>
						]
					</span>
					<strong><?=$DisplayName?></strong> <!--<?Votes::vote_link($GroupID, $UserVote);?>-->
					<div class="tags"><?=$TorrentTags->format()?></div>
				</div>
			</td>
			<td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?></td>
			<td class="number_column"><?=number_format($Torrent['Snatched'])?></td>
			<td class="number_column<?=($Torrent['Seeders'] == 0) ? ' r00' : '' ?>"><?=number_format($Torrent['Seeders'])?></td>
			<td class="number_column"><?=number_format($Torrent['Leechers'])?></td>
		</tr>
<?
	} //if (count($Torrents) > 1 || $GroupCategoryID == 1)
	$TorrentTable .= ob_get_clean();
}
?>
<table class="torrent_table grouping cats" id="discog_table">
	<tr class="colhead_dark">
		<td><!-- expand/collapse --></td>
		<td class="cats_col"><!-- category --></td>
		<td width="70%">Torrents</td>
		<td>Size</td>
		<td class="sign snatches"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" alt="Snatches" title="Snatches" class="tooltip" /></td>
		<td class="sign seeders"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/seeders.png" alt="Seeders" title="Seeders" class="tooltip" /></td>
		<td class="sign leechers"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/leechers.png" alt="Leechers" title="Leechers" class="tooltip" /></td>
	</tr>
<?
if ($TopVotes === false) { ?>
	<tr>
		<td colspan="7" class="center">Server is busy processing another top list request. Please try again in a minute.</td>
	</tr>
<?
} elseif (count($TopVotes) === 0) { ?>
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
