<?
function compare($X, $Y) {
	return($Y['count'] - $X['count']);
}

// Build the data for the collage and the torrent list
// TODO: Cache this
$DB->query("
	SELECT
		ct.GroupID,
		ct.UserID
	FROM collages_torrents AS ct
		JOIN torrents_group AS tg ON tg.ID = ct.GroupID
	WHERE ct.CollageID = '$CollageID'
	ORDER BY ct.Sort");

$GroupIDs = $DB->collect('GroupID');
$Contributors = $DB->to_pair('GroupID', 'UserID', false);
if (count($GroupIDs) > 0) {
	$TorrentList = Torrents::get_groups($GroupIDs);
	$UserVotes = Votes::get_user_votes($LoggedUser['ID']);
} else {
	$TorrentList = array();
}

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = array();
$TorrentTable = '';

$NumGroups = count($TorrentList);
$NumGroupsByUser = 0;
$TopArtists = array();
$UserAdditions = array();
$Number = 0;

foreach ($GroupIDs as $GroupID) {
	if (!isset($TorrentList[$GroupID])) {
		continue;
	}
	$Group = $TorrentList[$GroupID];
	extract(Torrents::array_group($Group));
	$UserID = $Contributors[$GroupID];
	$TorrentTags = new Tags($TagList);

	// Handle stats and stuff
	$Number++;
	if ($UserID == $LoggedUser['ID']) {
		$NumGroupsByUser++;
	}

	if (!empty($ExtendedArtists[1])
		|| !empty($ExtendedArtists[4])
		|| !empty($ExtendedArtists[5])
		|| !empty($ExtendedArtists[6])
	) {
		$CountArtists = array_merge((array)$ExtendedArtists[1], (array)$ExtendedArtists[4], (array)$ExtendedArtists[5], (array)$ExtendedArtists[6]);
	} else {
		$CountArtists = $GroupArtists;
	}

	if ($CountArtists) {
		foreach ($CountArtists as $Artist) {
			if (!isset($TopArtists[$Artist['id']])) {
				$TopArtists[$Artist['id']] = array('name' => $Artist['name'], 'count' => 1);
			} else {
				$TopArtists[$Artist['id']]['count']++;
			}
		}
	}

	if (!isset($UserAdditions[$UserID])) {
		$UserAdditions[$UserID] = 0;
	}
	$UserAdditions[$UserID]++;

	$DisplayName = "$Number - ";

	if (!empty($ExtendedArtists[1])
		|| !empty($ExtendedArtists[4])
		|| !empty($ExtendedArtists[5])
		|| !empty($ExtendedArtists[6])
	) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName .= Artists::display_artists($ExtendedArtists);
	} elseif (count($GroupArtists) > 0) {
		$DisplayName .= Artists::display_artists(array('1' => $GroupArtists));
	}

	$DisplayName .= "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"View torrent group\" dir=\"ltr\">$GroupName</a>";
	if ($GroupYear > 0) {
		$DisplayName = "$DisplayName [$GroupYear]";
	}
	if ($GroupVanityHouse) {
		$DisplayName .= ' [<abbr class="tooltip" title="This is a Vanity House release">VH</abbr>]';
	}
	$SnatchedGroupClass = ($GroupFlags['IsSnatched'] ? ' snatched_group' : '');
	$UserVote = isset($UserVotes[$GroupID]) ? $UserVotes[$GroupID]['Type'] : '';
	// Start an output buffer, so we can store this output in $TorrentTable
	ob_start();

	if (count($Torrents) > 1 || $GroupCategoryID == 1) {
		// Grouped torrents
		$ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1);
?>
			<tr class="group discog<?=$SnatchedGroupClass?>" id="group_<?=$GroupID?>">
				<td class="center">
					<div id="showimg_<?=$GroupID?>" class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
						<a href="#" class="tooltip show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event);" title="Collapse this group. Hold &quot;Ctrl&quot; while clicking to collapse all groups on this page."></a>
					</div>
				</td>
				<td class="center">
					<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>"></div>
				</td>
				<td colspan="5">
					<strong><?=$DisplayName?></strong>
<?		if (Bookmarks::has_bookmarked('torrent', $GroupID)) { ?>
					<span class="remove_bookmark float_right">
						<a style="float: right;" href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="remove_bookmark brackets" onclick="Unbookmark('torrent', <?=$GroupID?>, 'Bookmark'); return false;">Remove bookmark</a>
					</span>
<?		} else { ?>
					<span class="add_bookmark float_right">
						<a style="float: right;" href="#" id="bookmarklink_torrent_<?=$GroupID?>" class="add_bookmark brackets" onclick="Bookmark('torrent', <?=$GroupID?>, 'Remove bookmark'); return false;">Bookmark</a>
					</span>
<?
		}
		Votes::vote_link($GroupID, $UserVote);
?>
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

			if ($Torrent['RemasterTitle'] != $LastRemasterTitle
				|| $Torrent['RemasterYear'] != $LastRemasterYear
				|| $Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel
				|| $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber
				|| $FirstUnknown
				|| $Torrent['Media'] != $LastMedia
			) {
				$EditionID++;
?>
			<tr class="group_torrent groupid_<?=$GroupID?> edition<?=$SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1 ? ' hidden' : '')?>">
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
			<tr class="group_torrent torrent_row groupid_<?=$GroupID?> edition_<?=$EditionID?><?=$SnatchedTorrentClass . $SnatchedGroupClass . (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1 ? ' hidden' : '')?>">
				<td colspan="3">
					<span class="brackets">
						<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?			if (Torrents::can_use_token($Torrent)) { ?>
						| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
						| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a>
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

		$DisplayName = "<a href=\"torrents.php?id=$GroupID\" class=\"tooltip\" title=\"View torrent group\" dir=\"ltr\">$GroupName</a>";

		if ($Torrent['IsSnatched']) {
			$DisplayName .= ' ' . Format::torrent_label('Snatched!');
		}
		if ($Torrent['FreeTorrent'] == '1') {
			$DisplayName .= ' ' . Format::torrent_label('Freeleech!');
		} elseif ($Torrent['FreeTorrent'] == '2') {
			$DisplayName .= ' ' . Format::torrent_label('Neutral Leech!');
		} elseif ($Torrent['PersonalFL']) {
			$DisplayName .= ' ' . Format::torrent_label('Personal Freeleech!');
		}
		$SnatchedTorrentClass = ($Torrent['IsSnatched'] ? ' snatched_torrent' : '');
?>
			<tr class="torrent torrent_row<?=$SnatchedTorrentClass . $SnatchedGroupClass?>" id="group_<?=$GroupID?>">
				<td></td>
				<td class="center">
					<div title="<?=$TorrentTags->title()?>" class="tooltip <?=Format::css_category($GroupCategoryID)?> <?=$TorrentTags->css_name()?>">
					</div>
				</td>
				<td>
					<span class="brackets">
						<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="tooltip" title="Download">DL</a>
<?		if (Torrents::can_use_token($Torrent)) { ?>
						| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&amp;usetoken=1" class="tooltip" title="Use a FL Token" onclick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?		} ?>
						| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" class="tooltip" title="Report">RP</a>
					</span>
					<strong><?=$DisplayName?></strong>
<?		Votes::vote_link($GroupID, $UserVote); ?>
					<div class="tags"><?=$TorrentTags->format()?></div>
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
	if (!empty($ExtendedArtists[1])
		|| !empty($ExtendedArtists[4])
		|| !empty($ExtendedArtists[5])
		|| !empty($ExtendedArtists[6])
	) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName .= Artists::display_artists($ExtendedArtists, false);
	} elseif (count($GroupArtists) > 0) {
		$DisplayName .= Artists::display_artists(array('1' => $GroupArtists), false);
	}
	$DisplayName .= $GroupName;
	if ($GroupYear > 0) {
		$DisplayName = "$DisplayName [$GroupYear]";
	}
	$Tags = display_str($TorrentTags->format());
	$PlainTags = implode(', ', $TorrentTags->get_tags());
?>
				<li class="image_group_<?=$GroupID?>">
					<a href="torrents.php?id=<?=$GroupID?>">
<?	if ($WikiImage) { ?>
						<img class="tooltip_interactive" src="<?=ImageTools::process($WikiImage, true)?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?> <br /> <?=$Tags?>" data-title-plain="<?="$DisplayName ($PlainTags)"?>" width="118" />
<?	} else { ?>
						<span style="width: 107px; padding: 5px;"><?=$DisplayName?></span>
<?	} ?>
					</a>
				</li>
<?
	$Collage[] = ob_get_clean();
}

if ($CollageCategoryID === '0' && !check_perms('site_collages_delete')) {
	if (!check_perms('site_collages_personal') || $CreatorID !== $LoggedUser['ID']) {
		$PreventAdditions = true;
	}
}

if (!check_perms('site_collages_delete')
	&& (
		$Locked
		|| ($MaxGroups > 0 && $NumGroups >= $MaxGroups)
		|| ($MaxGroupsPerUser > 0 && $NumGroupsByUser >= $MaxGroupsPerUser)
	)
) {
	$PreventAdditions = true;
}

// Silly hack for people who are on the old setting
$CollageCovers = isset($LoggedUser['CollageCovers']) ? $LoggedUser['CollageCovers'] : 25 * (abs($LoggedUser['HideCollage'] - 1));
$CollagePages = array();

// Pad it out
if ($NumGroups > $CollageCovers) {
	for ($i = $NumGroups + 1; $i <= ceil($NumGroups / $CollageCovers) * $CollageCovers; $i++) {
		$Collage[] = '<li></li>';
	}
}

for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) {
	$Groups = array_slice($Collage, $i * $CollageCovers, $CollageCovers);
	$CollagePage = '';
	foreach ($Groups as $Group) {
		$CollagePage .= $Group;
	}
	$CollagePages[] = $CollagePage;
}

View::show_header($Name, 'browse,collage,bbcode,voting,recommend');
?>
<div class="thin">
	<div class="header">
		<h2><?=$Name?></h2>
		<div class="linkbox">
			<a href="collages.php" class="brackets">List of collages</a>
<?	if (check_perms('site_collages_create')) { ?>
			<a href="collages.php?action=new" class="brackets">New collage</a>
<?	} ?>
			<br /><br />
<?	if (check_perms('site_collages_subscribe')) { ?>
			<a href="#" id="subscribelink<?=$CollageID?>" class="brackets" onclick="CollageSubscribe(<?=$CollageID?>); return false;"><?=(in_array($CollageID, $CollageSubscriptions) ? 'Unsubscribe' : 'Subscribe')?></a>
<?
	}
	if (check_perms('site_collages_delete') || (check_perms('site_edit_wiki') && !$Locked)) {
?>
			<a href="collages.php?action=edit&amp;collageid=<?=$CollageID?>" class="brackets">Edit description</a>
<?	} else { ?>
			<span class="brackets">Locked</span>
<?
	}
	if (Bookmarks::has_bookmarked('collage', $CollageID)) {
?>
			<a href="#" id="bookmarklink_collage_<?=$CollageID?>" class="brackets" onclick="Unbookmark('collage', <?=$CollageID?>, 'Bookmark'); return false;">Remove bookmark</a>
<?	} else { ?>
			<a href="#" id="bookmarklink_collage_<?=$CollageID?>" class="brackets" onclick="Bookmark('collage', <?=$CollageID?>, 'Remove bookmark'); return false;">Bookmark</a>
<?	} ?>
<!-- <a href="#" id="recommend" class="brackets">Recommend</a> -->
<?
	if (check_perms('site_collages_manage') && !$Locked) {
?>
			<a href="collages.php?action=manage&amp;collageid=<?=$CollageID?>" class="brackets">Manage torrents</a>
<?	} ?>
			<a href="reports.php?action=report&amp;type=collage&amp;id=<?=$CollageID?>" class="brackets">Report collage</a>
<?	if (check_perms('site_collages_delete') || $CreatorID == $LoggedUser['ID']) { ?>
			<a href="collages.php?action=delete&amp;collageid=<?=$CollageID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets" onclick="return confirm('Are you sure you want to delete this collage?');">Delete</a>
<?	} ?>
		</div>
	</div>
<? /* Misc::display_recommend($CollageID, "collage"); */ ?>
	<div class="sidebar">
		<div class="box box_category">
			<div class="head"><strong>Category</strong></div>
			<div class="pad"><a href="collages.php?action=search&amp;cats[<?=(int)$CollageCategoryID?>]=1"><?=$CollageCats[(int)$CollageCategoryID]?></a></div>
		</div>
		<div class="box box_description">
			<div class="head"><strong>Description</strong></div>
			<div class="pad"><?=Text::full_format($Description)?></div>
		</div>
<?
if (check_perms('zip_downloader')) {
	if (isset($LoggedUser['Collector'])) {
		list($ZIPList, $ZIPPrefs) = $LoggedUser['Collector'];
		$ZIPList = explode(':', $ZIPList);
	} else {
		$ZIPList = array('00', '11');
		$ZIPPrefs = 1;
	}
?>
		<div class="box box_zipdownload">
			<div class="head colhead_dark"><strong>Collector</strong></div>
			<div class="pad">
				<form class="download_form" name="zip" action="collages.php" method="post">
				<input type="hidden" name="action" value="download" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="collageid" value="<?=$CollageID?>" />
				<ul id="list" class="nobullet">
<? foreach ($ZIPList as $ListItem) { ?>
					<li id="list<?=$ListItem?>">
						<input type="hidden" name="list[]" value="<?=$ListItem?>" />
						<span class="float_left"><?=$ZIPOptions[$ListItem]['2']?></span>
						<span class="remove remove_collector"><a href="#" onclick="remove_selection('<?=$ListItem?>'); return false;" class="float_right brackets">X</a></span>
						<br style="clear: all;" />
					</li>
<? } ?>
				</ul>
				<select id="formats" style="width: 180px;">
<?
$OpenGroup = false;
$LastGroupID = -1;

foreach ($ZIPOptions as $Option) {
	list($GroupID, $OptionID, $OptName) = $Option;

	if ($GroupID != $LastGroupID) {
		$LastGroupID = $GroupID;
		if ($OpenGroup) {
?>
					</optgroup>
<?		} ?>
					<optgroup label="<?=$ZIPGroups[$GroupID]?>">
<?
		$OpenGroup = true;
	}
?>
						<option id="opt<?=$GroupID.$OptionID?>" value="<?=$GroupID.$OptionID?>"<? if (in_array($GroupID.$OptionID, $ZIPList)) { echo ' disabled="disabled"'; }?>><?=$OptName?></option>
<?
}
?>
					</optgroup>
				</select>
				<button type="button" onclick="add_selection();">+</button>
				<select name="preference" style="width: 210px;">
					<option value="0"<? if ($ZIPPrefs == 0) { echo ' selected="selected"'; } ?>>Prefer Original</option>
					<option value="1"<? if ($ZIPPrefs == 1) { echo ' selected="selected"'; } ?>>Prefer Best Seeded</option>
					<option value="2"<? if ($ZIPPrefs == 2) { echo ' selected="selected"'; } ?>>Prefer Bonus Tracks</option>
				</select>
				<input type="submit" style="width: 210px;" value="Download" />
				</form>
			</div>
		</div>
<? } ?>
		<div class="box box_info box_statistics_collage_torrents">
			<div class="head"><strong>Statistics</strong></div>
			<ul class="stats nobullet">
				<li>Torrents: <?=number_format($NumGroups)?></li>
<? if (!empty($TopArtists)) { ?>
				<li>Artists: <?=number_format(count($TopArtists))?></li>
<? } ?>
				<li>Subscribers: <?=number_format((int)$Subscribers)?></li>
				<li>Built by <?=number_format(count($UserAdditions))?> user<?=(count($UserAdditions) > 1 ? 's' : '')?></li>
				<li>Last updated: <?=time_diff($Updated)?></li>
			</ul>
		</div>
		<div class="box box_tags">
			<div class="head"><strong>Top Tags</strong></div>
			<div class="pad">
				<ol style="padding-left: 5px;">
<?
				Tags::format_top(5, 'collages.php?action=search&amp;tags=');
?>
				</ol>
			</div>
		</div>
<?	if (!empty($TopArtists)) { ?>
		<div class="box box_artists">
			<div class="head"><strong>Top Artists</strong></div>
			<div class="pad">
				<ol style="padding-left: 5px;">
<?
		uasort($TopArtists, 'compare');
		$i = 0;
		foreach ($TopArtists as $ID => $Artist) {
			$i++;
			if ($i > 10) {
				break;
			}
?>
					<li><a href="artist.php?id=<?=$ID?>"><?=$Artist['name']?></a> (<?=number_format($Artist['count'])?>)</li>
<?
		}
?>
				</ol>
			</div>
		</div>
<?	} ?>
		<div class="box box_contributors">
			<div class="head"><strong>Top Contributors</strong></div>
			<div class="pad">
				<ol style="padding-left: 5px;">
<?
arsort($UserAdditions);
$i = 0;
foreach ($UserAdditions as $UserID => $Additions) {
	$i++;
	if ($i > 5) {
		break;
	}
?>
					<li><?=Users::format_username($UserID, false, false, false)?> (<?=number_format($Additions)?>)</li>
<?
}
?>
				</ol>
			</div>
		</div>
<? if (check_perms('site_collages_manage') && !isset($PreventAdditions)) { ?>
		<div class="box box_addtorrent">
			<div class="head"><strong>Add torrent group</strong><span class="float_right"><a href="#" onclick="$('.add_torrent_container').toggle_class('hidden'); this.innerHTML = (this.innerHTML == 'Batch add' ? 'Individual add' : 'Batch add'); return false;" class="brackets">Batch add</a></span></div>
			<div class="pad add_torrent_container">
				<form class="add_form" name="torrent" action="collages.php" method="post">
					<input type="hidden" name="action" value="add_torrent" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="collageid" value="<?=$CollageID?>" />
					<div class="field_div">
						<input type="text" size="20" name="url" />
					</div>
					<div class="submit_div">
						<input type="submit" value="Add" />
					</div>
					<span style="font-style: italic;">Enter the URL of a torrent group on the site.</span>
				</form>
			</div>
			<div class="pad hidden add_torrent_container">
				<form class="add_form" name="torrents" action="collages.php" method="post">
					<input type="hidden" name="action" value="add_torrent_batch" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="collageid" value="<?=$CollageID?>" />
					<div class="field_div">
						<textarea name="urls" rows="5" cols="25" style="white-space: pre; word-wrap: normal; overflow: auto;"></textarea>
					</div>
					<div class="submit_div">
						<input type="submit" value="Add" />
					</div>
					<span style="font-style: italic;">Enter the URLs of torrent groups on the site, one per line.</span>
				</form>
			</div>
		</div>
<? } ?>
		<h3>Comments</h3>
<?
if ($CommentList === null) {
	$DB->query("
		SELECT
			c.ID,
			c.Body,
			c.AuthorID,
			um.Username,
			c.AddedTime
		FROM comments AS c
			LEFT JOIN users_main AS um ON um.ID = c.AuthorID
		WHERE c.Page = 'collages'
			AND c.PageID = $CollageID
		ORDER BY c.ID DESC
		LIMIT 15");
	$CommentList = $DB->to_array(false, MYSQLI_NUM);
}
foreach ($CommentList as $Comment) {
	list($CommentID, $Body, $UserID, $Username, $CommentTime) = $Comment;
?>
		<div class="box comment">
			<div class="head">
				<?=Users::format_username($UserID, false, false, false) ?> <?=time_diff($CommentTime) ?>
				<br />
				<a href="reports.php?action=report&amp;type=collages_comment&amp;id=<?=$CommentID?>" class="brackets">Report</a>
			</div>
			<div class="pad"><?=Text::full_format($Body)?></div>
		</div>
<?
}
?>
		<div class="box pad">
			<a href="collages.php?action=comments&amp;collageid=<?=$CollageID?>" class="brackets">View all comments</a>
		</div>
<?
if (!$LoggedUser['DisablePosting']) {
?>
		<div class="box box_addcomment">
			<div class="head"><strong>Add comment</strong></div>
			<form class="send_form" name="comment" id="quickpostform" onsubmit="quickpostform.submit_button.disabled = true;" action="comments.php" method="post">
				<input type="hidden" name="action" value="take_post" />
				<input type="hidden" name="page" value="collages" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="pageid" value="<?=$CollageID?>" />
				<div class="pad">
					<div class="field_div">
						<textarea name="body" cols="24" rows="5"></textarea>
					</div>
					<div class="submit_div">
						<input type="submit" id="submit_button" value="Add comment" />
					</div>
				</div>
			</form>
		</div>
<?
}
?>
	</div>
	<div class="main_column">
<?
if ($CollageCovers != 0) { ?>
		<div id="coverart" class="box">
			<div class="head" id="coverhead"><strong>Cover Art</strong></div>
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
			<span id="firstpage" class="invisible"><a href="#" class="pageslink" onclick="collageShow.page(0, this); return false;"><strong>&lt;&lt; First</strong></a> | </span>
			<span id="prevpage" class="invisible"><a href="#" class="pageslink" onclick="collageShow.prevPage(); return false;"><strong>&lt; Prev</strong></a> | </span>
<?		for ($i = 0; $i < $NumGroups / $CollageCovers; $i++) { ?>
			<span id="pagelink<?=$i?>" class="<?=(($i > 4) ? 'hidden' : '')?><?=(($i == 0) ? 'selected' : '')?>"><a href="#" class="pageslink" onclick="collageShow.page(<?=$i?>, this); return false;"><strong><?=$CollageCovers * $i + 1?>-<?=min($NumGroups, $CollageCovers * ($i + 1))?></strong></a><?=(($i != ceil($NumGroups / $CollageCovers) - 1) ? ' | ' : '')?></span>
<?		} ?>
			<span id="nextbar" class="<?=($NumGroups / $CollageCovers > 5) ? 'hidden' : ''?>"> | </span>
			<span id="nextpage"><a href="#" class="pageslink" onclick="collageShow.nextPage(); return false;"><strong>Next &gt;</strong></a></span>
			<span id="lastpage" class="<?=(ceil($NumGroups / $CollageCovers) == 2 ? 'invisible' : '')?>"> | <a href="#" class="pageslink" onclick="collageShow.page(<?=ceil($NumGroups / $CollageCovers) - 1?>, this); return false;"><strong>Last &gt;&gt;</strong></a></span>
		</div>
		<script type="text/javascript">//<![CDATA[
			collageShow.init(<?=json_encode($CollagePages)?>);
		//]]></script>
<?
	}
}
?>
		<table class="torrent_table grouping cats" id="discog_table">
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
?>
