<?php
ini_set('max_execution_time', 600);
set_time_limit(0);

//~~~~~~~~~~~ Main bookmarks page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

function compare($X, $Y) {
	return($Y['count'] - $X['count']);
}

if (!empty($_GET['userid'])) {
	if (!check_perms('users_override_paranoia')) {
		error(403);
	}
	$UserID = $_GET['userid'];
	if (!is_number($UserID)) {
		error(404);
	}
	$DB->query("
		SELECT Username
		FROM users_main
		WHERE ID = '$UserID'");
	list($Username) = $DB->next_record();
} else {
	$UserID = $LoggedUser['ID'];
}

$Sneaky = $UserID !== $LoggedUser['ID'];
$Title = $Sneaky ? "$Username's bookmarked torrent groups" : 'Your bookmarked torrent groups';

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = array();
$TorrentTable = '';

$NumGroups = 0;
$ArtistCount = array();

list($GroupIDs, $CollageDataList, $TorrentList) = Users::get_bookmarks($UserID);
foreach ($GroupIDs as $GroupID) {
	if (!isset($TorrentList[$GroupID])) {
		continue;
	}
	$Group = $TorrentList[$GroupID];
	extract(Torrents::array_group($Group));
	list(, $Sort, $AddedTime) = array_values($CollageDataList[$GroupID]);

	// Handle stats and stuff
	$NumGroups++;

	if ($Artists) {
		foreach ($Artists as $Artist) {
			if (!isset($ArtistCount[$Artist['id']])) {
				$ArtistCount[$Artist['id']] = array('name' => $Artist['name'], 'count' => 1);
			} else {
				$ArtistCount[$Artist['id']]['count']++;
			}
		}
	}

	$TorrentTags = new Tags($TagList);

	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
			unset($ExtendedArtists[2]);
			unset($ExtendedArtists[3]);
			$DisplayName = Artists::display_artists($ExtendedArtists);
	} elseif (count($Artists) > 0) {
			$DisplayName = Artists::display_artists(array('1' => $Artists));
	} else {
		$DisplayName = '';
	}
	$DisplayName .= '<a href="torrents.php?id='.$GroupID.'" class="tooltip" title="View torrent group" dir="ltr">'.$GroupName.'</a>';
	if ($GroupYear > 0) {
		$DisplayName = "$DisplayName [$GroupYear]";
	}
	if ($GroupVanityHouse) {
		$DisplayName .= ' [<abbr class="tooltip" title="This is a Vanity House release">VH</abbr>]';
	}
	$SnatchedGroupClass = $GroupFlags['IsSnatched'] ? ' snatched_group' : '';

	// Start an output buffer, so we can store this output in $TorrentTable
	ob_start();
	if (count($Torrents) > 1 || $GroupCategoryID == 1) {
			// Grouped torrents
			$ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] === 1);
?>
			<tr class="group discog<?=$SnatchedGroupClass?>" id="group_<?=$GroupID?>">
				<td class="center">
					<div id="showimg_<?=$GroupID?>" class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
						<a href="#" class="tooltip show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event);" title="Collapse this group. Hold &quot;Ctrl&quot; while clicking to collape all groups on this page."></a>
					</div>
				</td>
				<td class="center">
					<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>"></div>
				</td>
				<td colspan="5">
					<strong><?=$DisplayName?></strong>
					<span style="text-align: right;" class="float_right">
<?		if (!$Sneaky) { ?>
						<a href="#group_<?=$GroupID?>" class="brackets remove_bookmark" onclick="Unbookmark('torrent', <?=$GroupID?>, ''); return false;">Remove bookmark</a>
						<br />
<?		} ?>
						<?=time_diff($AddedTime);?>
					</span>
					<div class="tags"><?=$TorrentTags->format()?></div>
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

			if ($Torrent['Remastered'] && !$Torrent['RemasterYear']) {
				$FirstUnknown = !isset($FirstUnknown);
			}
			$SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';

			if (
				$Torrent['RemasterTitle'] != $LastRemasterTitle
				|| $Torrent['RemasterYear'] != $LastRemasterYear
				|| $Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel
				|| $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber
				|| $FirstUnknown
				|| $Torrent['Media'] != $LastMedia
			) {
				$EditionID++;
?>
	<tr class="group_torrent groupid_<?=$GroupID?> edition<?=$SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] === 1 ? ' hidden' : '')?>">
		<td colspan="7" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" class="tooltip" title="Collapse this edition. Hold &quot;Ctrl&quot; while clicking to collapse all editions in this torrent group.">&minus;</a> <?=Torrents::edition_string($Torrent, $Group)?></strong></td>
	</tr>
<?
			}
			$LastRemasterTitle = $Torrent['RemasterTitle'];
			$LastRemasterYear = $Torrent['RemasterYear'];
			$LastRemasterRecordLabel = $Torrent['RemasterRecordLabel'];
			$LastRemasterCatalogueNumber = $Torrent['RemasterCatalogueNumber'];
			$LastMedia = $Torrent['Media'];
?>
	<tr class="group_torrent torrent_row groupid_<?=$GroupID?> edition_<?=$EditionID?><?=$SnatchedTorrentClass . $SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] === 1 ? ' hidden' : '')?>">
		<td colspan="3">
			<span>[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?			if (Torrents::can_use_token($Torrent)) { ?>
			| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
			| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
			</span>
			&nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?></a>
		</td>
		<td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?></td>
		<td class="number_column"><?=number_format($Torrent['Snatched'])?></td>
		<td class="number_column<?=(($Torrent['Seeders'] == 0) ? ' r00' : '')?>"><?=number_format($Torrent['Seeders'])?></td>
		<td class="number_column"><?=number_format($Torrent['Leechers'])?></td>
	</tr>
<?
		}
	} else {
		// Viewing a type that does not require grouping

		list($TorrentID, $Torrent) = each($Torrents);

		$DisplayName = '<a href="torrents.php?id='.$GroupID.'" class="tooltip" title="View torrent group" dir="ltr">'.$GroupName.'</a>';

		if ($Torrent['IsSnatched']) {
			$DisplayName .= ' ' . Format::torrent_label('Snatched!');
		}
		if ($Torrent['FreeTorrent'] === '1') {
			$DisplayName .= ' ' . Format::torrent_label('Freeleech!');
		} elseif ($Torrent['FreeTorrent'] === '2') {
			$DisplayName .= ' ' . Format::torrent_label('Neutral leech!');
		} elseif ($Torrent['PersonalFL']) {
			$DisplayName .= ' ' . Format::torrent_label('Personal Freeleech!');
		}
		$SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';
?>
	<tr class="torrent torrent_row<?=$SnatchedTorrentClass . $SnatchedGroupClass?>" id="group_<?=$GroupID?>">
		<td></td>
		<td class="center">
			<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>">
			</div>
		</td>
		<td>
			<span>
				[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?		if (Torrents::can_use_token($Torrent)) { ?>
				| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?		} ?>
				| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
			</span>
			<strong><?=$DisplayName?></strong>
			<div class="tags"><?=$TorrentTags->format()?></div>
<?		if (!$Sneaky) { ?>
			<span class="float_right float_clear"><a href="#group_<?=$GroupID?>" class="brackets remove_bookmark" onclick="Unbookmark('torrent', <?=$GroupID?>, ''); return false;">Remove bookmark</a></span>
<?		} ?>
			<span class="float_right float_clear"><?=time_diff($AddedTime);?></span>

		</td>
		<td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?></td>
		<td class="number_column"><?=number_format($Torrent['Snatched'])?></td>
		<td class="number_column<?=(($Torrent['Seeders'] == 0) ? ' r00' : '')?>"><?=number_format($Torrent['Seeders'])?></td>
		<td class="number_column"><?=number_format($Torrent['Leechers'])?></td>
	</tr>
<?
	}
	$TorrentTable .= ob_get_clean();

	// Album art

	ob_start();

	$DisplayName = '';
	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5])|| !empty($ExtendedArtists[6])) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName .= Artists::display_artists($ExtendedArtists, false);
	} elseif (count($Artists) > 0) {
		$DisplayName .= Artists::display_artists(array('1' => $Artists), false);
	}
	$DisplayName .= $GroupName;
	if ($GroupYear > 0) {
		$DisplayName = "$DisplayName [$GroupYear]";
	}
	$Tags = display_str($TorrentTags->format());
	$PlainTags = implode(', ', $TorrentTags->get_tags());
?>
		<li class="image_group_<?=$GroupID?>">
			<a href="torrents.php?id=<?=$GroupID?>" class="bookmark_<?=$GroupID?>">
<?	if ($WikiImage) { ?>
				<img class="tooltip_interactive" src="<?=ImageTools::process($WikiImage, true)?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?> <br /> <?=$Tags?>" data-title-plain="<?="$DisplayName ($PlainTags)"?>" width="118" />
<?	} else { ?>
				<div style="width: 107px; padding: 5px;"><?=$DisplayName?></div>
<?	} ?>
			</a>
		</li>
<?
	$Collage[] = ob_get_clean();

}

$CollageCovers = isset($LoggedUser['CollageCovers']) ? (int)$LoggedUser['CollageCovers'] : 25;
$CollagePages = array();
for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) {
	$Groups = array_slice($Collage, $i * $CollageCovers, $CollageCovers);
	$CollagePage = '';
	foreach ($Groups as $Group) {
		$CollagePage .= $Group;
	}
	$CollagePages[] = $CollagePage;
}

View::show_header($Title, 'browse,collage');
?>
<div class="thin">
	<div class="header">
		<h2><? if (!$Sneaky) { ?><a href="feeds.php?feed=torrents_bookmarks_t_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;name=<?=urlencode(SITE_NAME.': Bookmarked Torrents')?>"><img src="<?=STATIC_SERVER?>/common/symbols/rss.png" alt="RSS feed" /></a>&nbsp;<? } ?><?=$Title?></h2>
		<div class="linkbox">
			<a href="bookmarks.php?type=torrents" class="brackets">Torrents</a>
			<a href="bookmarks.php?type=artists" class="brackets">Artists</a>
			<a href="bookmarks.php?type=collages" class="brackets">Collages</a>
			<a href="bookmarks.php?type=requests" class="brackets">Requests</a>
<? if (count($TorrentList) > 0) { ?>
			<br /><br />
			<a href="bookmarks.php?action=remove_snatched&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets" onclick="return confirm('Are you sure you want to remove the bookmarks for all items you\'ve snatched?');">Remove snatched</a>
			<a href="bookmarks.php?action=edit&amp;type=torrents" class="brackets">Manage torrents</a>
<? } ?>
		</div>
	</div>
<? if (count($TorrentList) === 0) { ?>
	<div class="box pad" align="center">
		<h2>You have not bookmarked any torrents.</h2>
	</div>
</div><!--content-->
<?
	View::show_footer();
	die();
} ?>
	<div class="sidebar">
		<div class="box box_info box_statistics_bookmarked_torrents">
			<div class="head"><strong>Stats</strong></div>
			<ul class="stats nobullet">
				<li>Torrent groups: <?=$NumGroups?></li>
				<li>Artists: <?=count($ArtistCount)?></li>
			</ul>
		</div>
		<div class="box box_tags">
			<div class="head"><strong>Top Tags</strong></div>
			<div class="pad">
				<ol style="padding-left: 5px;">
<? Tags::format_top(5) ?>
				</ol>
			</div>
		</div>
		<div class="box box_artists">
			<div class="head"><strong>Top Artists</strong></div>
			<div class="pad">
<?
	$Indent = "\t\t\t\t";
	if (count($ArtistCount) > 0) {
		echo "$Indent<ol style=\"padding-left: 5px;\">\n";
		uasort($ArtistCount, 'compare');
		$i = 0;
		foreach ($ArtistCount as $ID => $Artist) {
			$i++;
			if ($i > 10) {
				break;
			}
?>
					<li><a href="artist.php?id=<?=$ID?>"><?=display_str($Artist['name'])?></a> (<?=$Artist['count']?>)</li>
<?
		}
		echo "$Indent</ol>\n";
	} else {
		echo "$Indent<ul class=\"nobullet\" style=\"padding-left: 5px;\">\n";
		echo "$Indent\t<li>There are no artists to display.</li>\n";
		echo "$Indent</ul>\n";
	}
?>
			</div>
		</div>
	</div>
	<div class="main_column">
<?
if ($CollageCovers !== 0) { ?>
		<div id="coverart" class="box">
			<div class="head" id="coverhead"><strong>Cover art</strong></div>
			<ul class="collage_images" id="collage_page0">
<?
	$Page1 = array_slice($Collage, 0, $CollageCovers);
	foreach ($Page1 as $Group) {
		echo $Group;
	}
?>
			</ul>
		</div>
<?	if ($NumGroups > $CollageCovers) { ?>
		<div class="linkbox pager" style="clear: left;" id="pageslinksdiv">
			<span id="firstpage" class="invisible"><a href="#" class="pageslink" onclick="collageShow.page(0, this); return false;">&lt;&lt; First</a> | </span>
			<span id="prevpage" class="invisible"><a href="#" id="prevpage" class="pageslink" onclick="collageShow.prevPage(); return false;">&lt; Prev</a> | </span>
<?		for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) { ?>
			<span id="pagelink<?=$i?>" class="<?=(($i > 4) ? 'hidden' : '')?><?=(($i === 0) ? ' selected' : '')?>"><a href="#" class="pageslink" onclick="collageShow.page(<?=$i?>, this); return false;"><?=($CollageCovers * $i + 1)?>-<?=min($NumGroups, $CollageCovers * ($i + 1))?></a><?=(($i !== ceil($NumGroups / $CollageCovers) - 1) ? ' | ' : '')?></span>
<?		} ?>
			<span id="nextbar" class="<?=(($NumGroups / $CollageCovers > 5) ? 'hidden' : '')?>"> | </span>
			<span id="nextpage"><a href="#" class="pageslink" onclick="collageShow.nextPage(); return false;">Next &gt;</a></span>
			<span id="lastpage" class="<?=(ceil($NumGroups / $CollageCovers) === 2 ? 'invisible' : '')?>"> | <a href="#" id="lastpage" class="pageslink" onclick="collageShow.page(<?=(ceil($NumGroups / $CollageCovers) - 1)?>, this); return false;">Last &gt;&gt;</a></span>
		</div>
		<script type="text/javascript">
			collageShow.init(<?=json_encode($CollagePages)?>);
		</script>
<?
	}
}
?>
		<table class="torrent_table grouping cats" id="torrent_table">
			<tr class="colhead_dark">
				<td><!-- expand/collapse --></td>
				<td><!-- Category --></td>
				<td width="70%"><strong>Torrents</strong></td>
				<td>Size</td>
				<td class="sign snatches"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" class="tooltip" alt="Snatches" title="Snatches" /></td>
				<td class="sign seeders"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/seeders.png" class="tooltip" alt="Seeders" title="Seeders" /></td>
				<td class="sign leechers"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/leechers.png" class="tooltip" alt="Leechers" title="Leechers" /></td>
			</tr>
<?=$TorrentTable?>
		</table>
	</div>
</div>
<?
View::show_footer();
