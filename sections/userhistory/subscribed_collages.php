<?
/*
User collage subscription page
*/
if (!check_perms('site_collages_subscribe')) {
	error(403);
}

View::show_header('Subscribed collages','browse,collage');

$ShowAll = !empty($_GET['showall']);

if (!$ShowAll) {
	$sql = "
		SELECT
			c.ID,
			c.Name,
			c.NumTorrents,
			s.LastVisit
		FROM collages AS c
			JOIN users_collage_subs AS s ON s.CollageID = c.ID
			JOIN collages_torrents AS ct ON ct.CollageID = c.ID
		WHERE s.UserID = $LoggedUser[ID] AND c.Deleted = '0'
			AND ct.AddedOn > s.LastVisit
		GROUP BY c.ID";
} else {
	$sql = "
		SELECT
			c.ID,
			c.Name,
			c.NumTorrents,
			s.LastVisit
		FROM collages AS c
			JOIN users_collage_subs AS s ON s.CollageID = c.ID
			LEFT JOIN collages_torrents AS ct ON ct.CollageID = c.ID
		WHERE s.UserID = $LoggedUser[ID] AND c.Deleted = '0'
		GROUP BY c.ID";
}

$DB->query($sql);
$NumResults = $DB->record_count();
$CollageSubs = $DB->to_array();
?>
<div class="thin">
	<div class="header">
		<h2>Subscribed collages<?=($ShowAll ? '' : ' with new additions')?></h2>

		<div class="linkbox">
<?
if ($ShowAll) {
?>
			<br /><br />
			<a href="userhistory.php?action=subscribed_collages&amp;showall=0" class="brackets">Only display collages with new additions</a>&nbsp;&nbsp;&nbsp;
<?
} else {
?>
			<br /><br />
			<a href="userhistory.php?action=subscribed_collages&amp;showall=1" class="brackets">Show all subscribed collages</a>&nbsp;&nbsp;&nbsp;
<?
}
?>
			<a href="userhistory.php?action=catchup_collages&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Catch up</a>&nbsp;&nbsp;&nbsp;
		</div>
	</div>
<?
if (!$NumResults) {
?>
	<div class="center">
		No subscribed collages<?=($ShowAll ? '' : ' with new additions')?>
	</div>
<?
} else {
	$HideGroup = '';
	$ActionTitle = 'Hide';
	$ActionURL = 'hide';
	$ShowGroups = 0;

	foreach ($CollageSubs as $Collage) {
		unset($TorrentTable);

		list($CollageID, $CollageName, $CollageSize, $LastVisit) = $Collage;
		$RS = $DB->query("
			SELECT GroupID
			FROM collages_torrents
			WHERE CollageID = $CollageID
				AND AddedOn > '" . db_string($LastVisit) . "'
			ORDER BY AddedOn");
		$NewTorrentCount = $DB->record_count();

		$GroupIDs = $DB->collect('GroupID', false);
		if (count($GroupIDs) > 0) {
			$TorrentList = Torrents::get_groups($GroupIDs);
		} else {
			$TorrentList = array();
		}

		$Artists = Artists::get_artists($GroupIDs);
		$Number = 0;

		foreach ($GroupIDs as $GroupID) {
			if (!isset($TorrentList[$GroupID])) {
				continue;
			}
			$Group = $TorrentList[$GroupID];
			extract(Torrents::array_group($Group));

			$DisplayName = '';

			$TorrentTags = new Tags($TagList);

			if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
				unset($ExtendedArtists[2]);
				unset($ExtendedArtists[3]);
				$DisplayName .= Artists::display_artists($ExtendedArtists);
			} elseif (count($Artists) > 0) {
				$DisplayName .= Artists::display_artists(array('1' => $Artists));
			}
			$DisplayName .= "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"View torrent group\" dir=\"ltr\">$GroupName</a>";
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
?>
			<tr class="group discog<?=$SnatchedGroupClass?>" id="group_<?=$CollageID?><?=$GroupID?>">
				<td class="center">
					<div id="showimg_<?=$CollageID?><?=$GroupID?>" class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
						<a href="#" class="tooltip show_torrents_link" onclick="toggle_group(<?=$CollageID?><?=$GroupID?>, this, event);" title="Expand this group. Hold &quot;Ctrl&quot; while clicking to expand all groups on this page."></a>
					</div>
				</td>
				<td colspan="5" class="big_info">
<? if ($LoggedUser['CoverArt']) { ?>
					<div class="group_image float_left clear">
						<? ImageTools::cover_thumb($WikiImage, $GroupCategoryID) ?>
					</div>
<? } ?>
					<div class="group_info clear">
						<strong><?=$DisplayName?></strong>
						<div class="tags"><?=$TorrentTags->format()?></tags>
					</div>
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

					if ($Torrent['RemasterTitle'] != $LastRemasterTitle
						|| $Torrent['RemasterYear'] != $LastRemasterYear
						|| $Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel
						|| $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber
						|| $FirstUnknown
						|| $Torrent['Media'] != $LastMedia
					) {
						$EditionID++;
?>
	<tr class="group_torrent groupid_<?=$CollageID . $GroupID?> edition<?=$SnatchedGroupClass?> hidden">
		<td colspan="6" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$CollageID?><?=$GroupID?>, <?=$EditionID?>, this, event);" class="tooltip" title="Collapse this edition. Hold &quot;Ctrl&quot; while clicking to collapse all editions in this torrent group.">&minus;</a> <?=Torrents::edition_string($Torrent, $Group)?></strong></td>
	</tr>
<?
					}
					$LastRemasterTitle = $Torrent['RemasterTitle'];
					$LastRemasterYear = $Torrent['RemasterYear'];
					$LastRemasterRecordLabel = $Torrent['RemasterRecordLabel'];
					$LastRemasterCatalogueNumber = $Torrent['RemasterCatalogueNumber'];
					$LastMedia = $Torrent['Media'];
?>
	<tr class="group_torrent groupid_<?=$CollageID . $GroupID?> edition_<?=$EditionID?> hidden<?=$SnatchedTorrentClass . $SnatchedGroupClass?>">
		<td colspan="2">
			<span>
				<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download" class="brackets tooltip">DL</a>
			</span>
			&nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?></a>
		</td>
		<td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?></td>
		<td class="number_column"><?=number_format($Torrent['Snatched'])?></td>
		<td class="number_column<?=($Torrent['Seeders'] == 0) ? ' r00' : ''?>"><?=number_format($Torrent['Seeders'])?></td>
		<td class="number_column"><?=number_format($Torrent['Leechers'])?></td>
	</tr>
<?
				}
			} else {
				// Viewing a type that does not require grouping

				list($TorrentID, $Torrent) = each($Torrents);

				$DisplayName = "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"View torrent group\" dir=\"ltr\">$GroupName</a>";

				if ($Torrent['IsSnatched']) {
					$DisplayName .= ' ' . Format::torrent_label('Snatched!');
				}
				if (!empty($Torrent['FreeTorrent'])) {
					$DisplayName .= ' ' . Format::torrent_label('Freeleech!');
				}
				$SnatchedTorrentClass = $Torrent['IsSnatched'] ? ' snatched_torrent' : '';
?>
	<tr class="torrent<?=$SnatchedTorrentClass?>" id="group_<?=$CollageID . $GroupID?>">
		<td></td>
		<td class="center">
			<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>">
			</div>
		</td>
		<td class="big_info">
<? if ($LoggedUser['CoverArt']) { ?>
			<div class="group_image float_left clear">
				<? ImageTools::cover_thumb($WikiImage, $GroupCategoryID) ?>
			</div>
<? } ?>
			<div class="group_info clear">
				<span>
					[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
					| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a> ]
				</span>
				<strong><?=$DisplayName?></strong>
				<div class="tags"><?=$TorrentTags->format()?></div>
			</div>
		</td>
		<td class="number_column nobr"><?=Format::get_size($Torrent['Size'])?></td>
		<td class="number_column"><?=number_format($Torrent['Snatched'])?></td>
		<td class="number_column<?=($Torrent['Seeders'] == 0) ? ' r00' : ''?>"><?=number_format($Torrent['Seeders'])?></td>
		<td class="number_column"><?=number_format($Torrent['Leechers'])?></td>
	</tr>
<?
			}
			$TorrentTable .= ob_get_clean();
		} ?>
	<!-- I hate that proton is making me do it like this -->
	<!--<div class="head colhead_dark" style="margin-top: 8px;">-->
	<table style="margin-top: 8px;" class="subscribed_collages_table">
		<tr class="colhead_dark">
			<td>
				<span style="float: left;">
					<strong><a href="collage.php?id=<?=$CollageID?>"><?=$CollageName?></a></strong> (<?=$NewTorrentCount?> new torrent<?=($NewTorrentCount == 1 ? '' : 's')?>)
				</span>&nbsp;
				<span style="float: right;">
					<a href="#" onclick="$('#discog_table_<?=$CollageID?>').gtoggle(); this.innerHTML = (this.innerHTML == 'Hide' ? 'Show' : 'Hide'); return false;" class="brackets"><?=($ShowAll ? 'Show' : 'Hide')?></a>&nbsp;&nbsp;&nbsp;<a href="userhistory.php?action=catchup_collages&amp;auth=<?=$LoggedUser['AuthKey']?>&amp;collageid=<?=$CollageID?>" class="brackets">Catch up</a>&nbsp;&nbsp;&nbsp;<a href="#" onclick="CollageSubscribe(<?=$CollageID?>); return false;" id="subscribelink<?=$CollageID?>" class="brackets">Unsubscribe</a>
				</span>
			</td>
		</tr>
	</table>
	<!--</div>-->
	<table class="torrent_table<?=$ShowAll ? ' hidden' : ''?>" id="discog_table_<?=$CollageID?>">
		<tr class="colhead">
			<td width="1%"><!-- expand/collapse --></td>
			<td width="70%"><strong>Torrents</strong></td>
			<td>Size</td>
			<td class="sign snatches"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" class="tooltip" alt="Snatches" title="Snatches" /></td>
			<td class="sign seeders"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/seeders.png" class="tooltip" alt="Seeders" title="Seeders" /></td>
			<td class="sign leechers"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/leechers.png" class="tooltip" alt="Leechers" title="Leechers" /></td>
		</tr>
<?=$TorrentTable?>
	</table>
<?
	} // foreach ()
} // else -- if (empty($NumResults))
?>
</div>
<?

View::show_footer();

?>
