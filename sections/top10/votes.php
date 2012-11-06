<?
// We need these to do our rankification
include(SERVER_ROOT.'/sections/torrents/ranking_funcs.php');
include(SERVER_ROOT.'/sections/bookmarks/functions.php');

$UserVotes = Votes::get_user_votes($LoggedUser['ID']);

if(!empty($_GET['advanced']) && check_perms('site_advanced_top10')) {
	$Details = 'all';
	$Limit = 25;

	if($_GET['tags']) {
		$Tags = explode(',', str_replace(".","_",trim($_GET['tags'])));
		foreach ($Tags as $Tag) {
			$Tag = preg_replace('/[^a-z0-9_]/', '', $Tag);
			if($Tag != '') {
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
$Query = "SELECT v.GroupID, v.Ups, v.Total, v.Score
		  FROM torrents_votes AS v";
if (!empty($Where)) {
	$Query .= " JOIN torrents_group AS g ON g.ID = v.GroupID
			   WHERE $Where AND ";
} else {
	$Query .= " WHERE ";
}
$Query .= "Score > 0 ORDER BY Score DESC LIMIT $Limit";

$TopVotes = $Cache->get_value('top10votes_'.$Limit.$WhereSum);
if ($TopVotes === false) {
	if ($Cache->get_query_lock('top10votes')) {
		$DB->query($Query);

		$Results = $DB->collect('GroupID');
		$Data    = $DB->to_array('GroupID');
		
		$Groups = Torrents::get_groups($Results);
		if (count($Results) > 0) {
			$DB->query('SELECT ID, CategoryID FROM torrents_group
						WHERE ID IN ('.implode(',', $Results).')');
			$Cats = $DB->to_array('ID');
		}
		// Make sure it's still in order.
		$TopVotes = array();
		foreach ($Results as $GroupID) {
			$TopVotes[$GroupID] = $Groups['matches'][$GroupID];
			$TopVotes[$GroupID]['CategoryID'] = $Cats[$GroupID]['CategoryID'];
			$TopVotes[$GroupID]['Ups']   = $Data[$GroupID]['Ups'];
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
			<a href="top10.php?type=torrents">[Torrents]</a>
			<a href="top10.php?type=users">[Users]</a>
			<a href="top10.php?type=tags">[Tags]</a>
			<a href="top10.php?type=votes"><strong>[Favorites]</strong></a>
		</div>
	</div>
<?

if(check_perms('site_advanced_top10')) { ?>
	<form class="search_form" name="votes" action="" method="get">
		<input type="hidden" name="advanced" value="1" />
		<input type="hidden" name="type" value="votes" />
		<table cellpadding="6" cellspacing="1" border="0" class="layout border" width="100%">
			<tr id="tagfilter">
				<td class="label">Tags (comma-separated):</td>
				<td class="ft_taglist">
					<input type="text" name="tags" size="75" value="<? if(!empty($_GET['tags'])) { echo display_str($_GET['tags']);} ?>" />&nbsp;
					<input type="radio" id="rdoAll" name="anyall" value="all"<?=($_GET['anyall']!='any'?' checked':'')?>><label for="rdoAll"> All</label>&nbsp;&nbsp;
					<input type="radio" id="rdoAny" name="anyall" value="any"<?=($_GET['anyall']=='any'?' checked':'')?>><label for="rdoAny"> Any</label>
				</td>
			</tr>
			<tr id="yearfilter">
				<td class="label">Year:</td>
				<td class="ft_year">
					<input type="text" name="year1" size="4" value="<? if(!empty($_GET['year1'])) { echo display_str($_GET['year1']);} ?>" />
					to
					<input type="text" name="year2" size="4" value="<? if(!empty($_GET['year2'])) { echo display_str($_GET['year2']);} ?>" />
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

$Bookmarks = all_bookmarks('torrent');
?>
	<h3>Top <?=$Limit.' '.$Caption?>
<?
if(empty($_GET['advanced'])){ ?>
		<small>
<?
	switch($Limit) {
		case 100: ?>
			- [<a href="top10.php?type=votes">Top 25</a>]
			- [Top 100]
			- [<a href="top10.php?type=votes&amp;limit=250">Top 250</a>]
		<?	break;
		case 250: ?>
			- [<a href="top10.php?type=votes">Top 25</a>]
			- [<a href="top10.php?type=votes&amp;limit=100">Top 100</a>]
			- [Top 250]
		<?	break;
		default: ?>
			- [Top 25]
			- [<a href="top10.php?type=votes&amp;limit=100">Top 100</a>]
			- [<a href="top10.php?type=votes&amp;limit=250">Top 250</a>]
<?	} ?>
		</small>
<?
} ?>
	</h3>
<?

// This code was copy-pasted from collages and should die in a fire
$Number = 0;
$NumGroups = 0;
foreach ($TopVotes as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel,
		 $GroupCatalogueNumber, $TagList, $ReleaseType, $GroupVanityHouse,
		 $Torrents, $GroupArtists, $ExtendedArtists, $GroupCategoryID,$Ups,$Total,$Score) = array_values($Group);

	$IsBookmarked = in_array($GroupID, $Bookmarks);

	// Handle stats and stuff
	$Number++;
	$NumGroups++;

	$TagList = explode(' ',str_replace('_','.',$TagList));
	$TorrentTags = array();
	foreach($TagList as $Tag) {
		$TorrentTags[]='<a href="torrents.php?taglist='.$Tag.'">'.$Tag.'</a>';
	}
	$PrimaryTag = $TagList[0];
	$TorrentTags = implode(', ', $TorrentTags);
	$TorrentTags='<br /><div class="tags">'.$TorrentTags.'</div>';

	$DisplayName = $Number.' - ';

	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5])|| !empty($ExtendedArtists[6])) {
			unset($ExtendedArtists[2]);
			unset($ExtendedArtists[3]);
			$DisplayName .= Artists::display_artists($ExtendedArtists);
	} elseif(count($GroupArtists)>0) {
			$DisplayName .= Artists::display_artists(array('1'=>$GroupArtists));
	}

	$DisplayName .= '<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName = $DisplayName. ' ['. $GroupYear .']';}
	if($GroupVanityHouse) { $DisplayName .= ' [<abbr title="This is a vanity house release">VH</abbr>]'; }
	// Start an output buffer, so we can store this output in $TorrentTable
	ob_start();

	if(count($Torrents)>1 || $GroupCategoryID==1) {
			// Grouped torrents
?>
				<tr class="group discog" id="group_<?=$GroupID?>">
					<td class="center">
						<div title="View" id="showimg_<?=$GroupID?>" class="show_torrents">
							<a href="#" class="show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event)" title="Collapse this group"></a>
						</div>
					</td>
					<td class="center">
						<div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>"></div>
					</td>
					<td>
						<strong><?=$DisplayName?></strong> <!--<?Votes::vote_link($GroupID,$UserVotes[$GroupID]['Type']);?>-->
		<?	if($IsBookmarked) { ?>
						<span class="bookmark" style="float:right;">[ <a href="#" class="bookmarklink_torrent_<?=$GroupID?>" title="Remove bookmark" onclick="Unbookmark('torrent',<?=$GroupID?>,'Bookmark');return false;">Unbookmark</a> ]</span>
		<?	} else { ?>
						<span class="bookmark" style="float:right;">[ <a href="#" class="bookmarklink_torrent_<?=$GroupID?>" title="Add bookmark" onclick="Bookmark('torrent',<?=$GroupID?>,'Unbookmark');return false;">Bookmark</a> ]</span>
		<?	} ?>
					<?=$TorrentTags?>
					</td>
					<td colspan="4" class="votes_info_td"><strong><?=$Ups?></strong> upvotes out of <strong><?=$Total?></strong> total (<span title="Score: <?=round($Score*100,4)?>">Score: <?=round($Score*100)?></span>).</td>
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

			$Torrent['IsSnatched'] = Torrents::has_snatched($TorrentID);
			if ($Torrent['Remastered'] && !$Torrent['RemasterYear']) {
				$FirstUnknown = !isset($FirstUnknown);
			}

			if($Torrent['RemasterTitle'] != $LastRemasterTitle || $Torrent['RemasterYear'] != $LastRemasterYear ||
			$Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel || $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber || $FirstUnknown || $Torrent['Media'] != $LastMedia) {
				$EditionID++;
				if($Torrent['Remastered'] && $Torrent['RemasterYear'] != 0) {
					$RemasterName = $Torrent['RemasterYear'];
					$AddExtra = " - ";
					if($Torrent['RemasterRecordLabel']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterRecordLabel']); $AddExtra=' / '; }
					if($Torrent['RemasterCatalogueNumber']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterCatalogueNumber']); $AddExtra=' / '; }
					if($Torrent['RemasterTitle']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterTitle']); $AddExtra=' / '; }
					$RemasterName .= $AddExtra.display_str($Torrent['Media']);

?>
		<tr class="group_torrent groupid_<?=$GroupID?> edition hidden">
			<td colspan="7" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition. Hold &quot;Ctrl&quot; while clicking to collapse all editions in this torrent group.">&minus;</a> <?=$RemasterName?></strong></td>
		</tr>
<?
				} else {
					$AddExtra = " / ";
					if (!$Torrent['Remastered']) {
						$MasterName = "Original Release";
						if($GroupRecordLabel) { $MasterName .= $AddExtra.$GroupRecordLabel; $AddExtra=' / '; }
						if($GroupCatalogueNumber) { $MasterName .= $AddExtra.$GroupCatalogueNumber; $AddExtra=' / '; }
					} else {
						$MasterName = "Unknown Release(s)";
					}
					$MasterName .= $AddExtra.display_str($Torrent['Media']);
?>
		<tr class="group_torrent groupid_<?=$GroupID?> edition hidden">
			<td colspan="7" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition. Hold &quot;Ctrl&quot; while clicking to collapse all editions in this torrent group.">&minus;</a> <?=$MasterName?></strong></td>
		</tr>
<?
				}
			}
			$LastRemasterTitle = $Torrent['RemasterTitle'];
			$LastRemasterYear = $Torrent['RemasterYear'];
			$LastRemasterRecordLabel = $Torrent['RemasterRecordLabel'];
			$LastRemasterCatalogueNumber = $Torrent['RemasterCatalogueNumber'];
			$LastMedia = $Torrent['Media'];
?>
		<tr class="group_torrent groupid_<?=$GroupID?> edition_<?=$EditionID?> hidden<?=$Torrent['IsSnatched'] ? ' snatched_torrent' : ''?>">
			<td colspan="3">
				<span>
					[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
<?			if (Torrents::can_use_token($Torrent)) { ?>
					| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
					| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a> ]
				</span>
				&nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=Torrents::torrent_info($Torrent)?></a>
			</td>
			<td class="nobr"><?=Format::get_size($Torrent['Size'])?></td>
			<td><?=number_format($Torrent['Snatched'])?></td>
			<td<?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format($Torrent['Seeders'])?></td>
			<td><?=number_format($Torrent['Leechers'])?></td>
		</tr>
<?
		}
	} else {
		// Viewing a type that does not require grouping

		list($TorrentID, $Torrent) = each($Torrents);
		$Torrent['IsSnatched'] = Torrents::has_snatched($TorrentID);

		$DisplayName = $Number .' - <a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
		if($Torrent['IsSnatched']) {
			$DisplayName .= ' <strong class="snatched_torrent_label">Snatched!</strong>';
		}
		if ($Torrent['FreeTorrent'] == '1') {
			$DisplayName .= ' <strong class="freeleech_torrent_label">Freeleech!</strong>';
		} elseif ($Torrent['FreeTorrent'] == '2') {
			$DisplayName .= ' <strong class="neutral_leech_torrent_label">Neutral Leech!</strong>';
		} elseif (Torrents::has_token($TorrentID)) {
			$DisplayName .= '<strong class="personal_freeleech_torrent_label">Personal Freeleech!</strong>';
		}

?>
		<tr class="torrent<?=$Torrent['IsSnatched'] ? ' snatched_torrent' : ''?>" id="group_<?=$GroupID?>">
			<td></td>
			<td class="center">
				<div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>">
				</div>
			</td>
			<td class="nobr">
				<span>
					[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
<?		if (Torrents::can_use_token($Torrent)) { ?>
					| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?		} ?>
					| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a>
<?		if($IsBookmarked) { ?>
					| <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" title="Remove bookmark" onclick="Unbookmark('torrent',<?=$GroupID?>,'Bookmark');return false;">Unbookmark</a>
<?		} else { ?>
					| <a href="#" id="bookmarklink_torrent_<?=$GroupID?>" title="Add bookmark" onclick="Bookmark('torrent',<?=$GroupID?>,'Unbookmark');return false;">Bookmark</a>
<?		} ?>
					]
				</span>
				<strong><?=$DisplayName?></strong> <!--<?Votes::vote_link($GroupID,$UserVotes[$GroupID]['Type']);?>-->
				<?=$TorrentTags?>
			</td>
			<td class="nobr"><?=Format::get_size($Torrent['Size'])?></td>
			<td><?=number_format($Torrent['Snatched'])?></td>
			<td<?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format($Torrent['Seeders'])?></td>
			<td><?=number_format($Torrent['Leechers'])?></td>
		</tr>
	<?
	}
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
if($TorrentList === false) { ?>
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