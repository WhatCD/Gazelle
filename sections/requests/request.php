<?php

/*
 * This is the page that displays the request to the end user after being created.
 */

if (empty($_GET['id']) || !is_number($_GET['id'])) {
	error(0);
}

$RequestID = $_GET['id'];

//First things first, lets get the data for the request.

$Request = Requests::get_request($RequestID);
if ($Request === false) {
	error(404);
}

//Convenience variables
$IsFilled = !empty($Request['TorrentID']);
$CanVote = !$IsFilled && check_perms('site_vote');

if ($Request['CategoryID'] === '0') {
	$CategoryName = 'Unknown';
} else {
	$CategoryName = $Categories[$Request['CategoryID'] - 1];
}

//Do we need to get artists?
if ($CategoryName === 'Music') {
	$ArtistForm = Requests::get_artists($RequestID);
	$ArtistName = Artists::display_artists($ArtistForm, false, true);
	$ArtistLink = Artists::display_artists($ArtistForm, true, true);

	if ($IsFilled) {
		$DisplayLink = "$ArtistLink<a href=\"torrents.php?torrentid=$Request[TorrentID]\" dir=\"ltr\">$Request[Title]</a> [$Request[Year]]";
	} else {
		$DisplayLink = $ArtistLink.'<span dir="ltr">'.$Request['Title']."</span> [$Request[Year]]";
	}
	$FullName = $ArtistName.$Request['Title']." [$Request[Year]]";

	if ($Request['BitrateList'] != '') {
		$BitrateString = implode(', ', explode('|', $Request['BitrateList']));
		$FormatString = implode(', ', explode('|', $Request['FormatList']));
		$MediaString = implode(', ', explode('|', $Request['MediaList']));
	} else {
		$BitrateString = 'Unknown, please read the description.';
		$FormatString = 'Unknown, please read the description.';
		$MediaString = 'Unknown, please read the description.';
	}

	if (empty($Request['ReleaseType'])) {
		$ReleaseName = 'Unknown';
	} else {
		$ReleaseName = $ReleaseTypes[$Request['ReleaseType']];
	}

} elseif ($CategoryName === 'Audiobooks' || $CategoryName === 'Comedy') {
	$FullName = "$Request[Title] [$Request[Year]]";
	$DisplayLink = "<span dir=\"ltr\">$Request[Title]</span> [$Request[Year]]";
} else {
	$FullName = $Request['Title'];
	$DisplayLink = "<span dir=\"ltr\">$Request[Title]</span>";
}

//Votes time
$RequestVotes = Requests::get_votes_array($RequestID);
$VoteCount = count($RequestVotes['Voters']);
$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && ($Request['CategoryID'] === '0' || ($CategoryName === 'Music' && $Request['Year'] === '0')));
$UserCanEdit = (!$IsFilled && $LoggedUser['ID'] === $Request['UserID'] && $VoteCount < 2);
$CanEdit = ($UserCanEdit || $ProjectCanEdit || check_perms('site_moderate_requests'));

// Comments (must be loaded before View::show_header so that subscriptions and quote notifications are handled properly)
list($NumComments, $Page, $Thread, $LastRead) = Comments::load('requests', $RequestID);

View::show_header("View request: $FullName", 'comments,requests,bbcode,subscriptions');

?>
<div class="thin">
	<div class="header">
		<h2><a href="requests.php">Requests</a> &gt; <?=$CategoryName?> &gt; <?=$DisplayLink?></h2>
		<div class="linkbox">
<?	if ($CanEdit) { ?>
			<a href="requests.php?action=edit&amp;id=<?=$RequestID?>" class="brackets">Edit</a>
<?	}
	if ($UserCanEdit || check_perms('users_mod')) { //check_perms('site_moderate_requests')) { ?>
			<a href="requests.php?action=delete&amp;id=<?=$RequestID?>" class="brackets">Delete</a>
<?	}
	if (Bookmarks::has_bookmarked('request', $RequestID)) { ?>
			<a href="#" id="bookmarklink_request_<?=$RequestID?>" onclick="Unbookmark('request', <?=$RequestID?>, 'Bookmark'); return false;" class="brackets">Remove bookmark</a>
<?	} else { ?>
			<a href="#" id="bookmarklink_request_<?=$RequestID?>" onclick="Bookmark('request', <?=$RequestID?>, 'Remove bookmark'); return false;" class="brackets">Bookmark</a>
<?	} ?>
			<a href="#" id="subscribelink_requests<?=$RequestID?>" class="brackets" onclick="SubscribeComments('requests',<?=$RequestID?>);return false;"><?=Subscriptions::has_subscribed_comments('requests', $RequestID) !== false ? 'Unsubscribe' : 'Subscribe'?></a>
			<a href="reports.php?action=report&amp;type=request&amp;id=<?=$RequestID?>" class="brackets">Report request</a>
<?	if (!$IsFilled) { ?>
			<a href="upload.php?requestid=<?=$RequestID?><?=($Request['GroupID'] ? "&amp;groupid=$Request[GroupID]" : '')?>" class="brackets">Upload request</a>
<?	}
	if (!$IsFilled && ($Request['CategoryID'] === '0' || ($CategoryName === 'Music' && $Request['Year'] === '0'))) { ?>
			<a href="reports.php?action=report&amp;type=request_update&amp;id=<?=$RequestID?>" class="brackets">Request update</a>
<? } ?>

<?
// Create a search URL to WorldCat and Google based on title
$encoded_title = urlencode(preg_replace("/\([^\)]+\)/", '', $Request['Title']));
$encoded_artist = substr(str_replace('&amp;', 'and', $ArtistName), 0, -3);
$encoded_artist = str_ireplace('Performed By', '', $encoded_artist);
$encoded_artist = preg_replace("/\([^\)]+\)/", '', $encoded_artist);
$encoded_artist = urlencode($encoded_artist);

$worldcat_url = 'https://www.worldcat.org/search?qt=worldcat_org_all&amp;q=' . "$encoded_artist%20$encoded_title";
$google_url = 'https://www.google.com/search?tbm=shop&amp;q=' . "$encoded_artist%20$encoded_title";
?>
			<a href="<? echo $worldcat_url; ?>" class="brackets">Find in library</a>
			<a href="<? echo $google_url; ?>" class="brackets">Find in stores</a>
		</div>
	</div>
	<div class="sidebar">
<?	if ($Request['CategoryID'] !== '0') { ?>
		<div class="box box_image box_image_albumart box_albumart"><!-- .box_albumart deprecated -->
			<div class="head"><strong>Cover</strong></div>
			<div id="covers">
				<div class="pad">
<?
		if (!empty($Request['Image'])) {
?>
					<p align="center"><img style="width: 100%;" src="<?=ImageTools::process($Request['Image'], true)?>" alt="<?=$FullName?>" onclick="lightbox.init('<?=ImageTools::process($Request['Image'])?>', 220);" /></p>
<?		} else { ?>
					<p align="center"><img style="width: 100%;" src="<?=STATIC_SERVER?>common/noartwork/<?=$CategoryIcons[$Request['CategoryID'] - 1]?>" alt="<?=$CategoryName?>" class="tooltip" title="<?=$CategoryName?>" height="220" border="0" /></p>
<?		} ?>
				</div>
			</div>
		</div>
<?
	}
	if ($CategoryName === 'Music') { ?>
		<div class="box box_artists">
			<div class="head"><strong>Artists</strong></div>
			<ul class="stats nobullet">
<?		if (!empty($ArtistForm[4]) && count($ArtistForm[4]) > 0) { ?>
				<li class="artists_composer"><strong>Composers:</strong></li>
<?			foreach ($ArtistForm[4] as $Artist) { ?>
				<li class="artists_composer">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
			}
		}
		if (!empty($ArtistForm[6]) && count($ArtistForm[6]) > 0) {
?>
				<li class="artists_dj"><strong>DJ / Compiler:</strong></li>
<?			foreach ($ArtistForm[6] as $Artist) { ?>
				<li class="artists_dj">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
			}
		}
		if ((count($ArtistForm[6]) > 0) && (count($ArtistForm[1]) > 0)) {
			print '				<li class="artists_main"><strong>Artists:</strong></li>';
		} elseif ((count($ArtistForm[4]) > 0) && (count($ArtistForm[1]) > 0)) {
			print '				<li class="artists_main"><strong>Performers:</strong></li>';
		}
		foreach ($ArtistForm[1] as $Artist) {
?>
				<li class="artists_main">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
		}
		if (!empty($ArtistForm[2]) && count($ArtistForm[2]) > 0) {
?>
				<li class="artists_with"><strong>With:</strong></li>
<?			foreach ($ArtistForm[2] as $Artist) { ?>
				<li class="artists_with">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
			}
		}
		if (!empty($ArtistForm[5]) && count($ArtistForm[5]) > 0) {
?>
				<li class="artists_conductor"><strong>Conducted by:</strong></li>
<?			foreach ($ArtistForm[5] as $Artist) { ?>
				<li class="artist_guest">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
			}
		}
		if (!empty($ArtistForm[3]) && count($ArtistForm[3]) > 0) {
?>
				<li class="artists_remix"><strong>Remixed by:</strong></li>
<?			foreach ($ArtistForm[3] as $Artist) { ?>
				<li class="artists_remix">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
			}
		}
		if (!empty($ArtistForm[7]) && count($ArtistForm[7]) > 0) {
?>
				<li class="artists_producer"><strong>Produced by:</strong></li>
<?			foreach ($ArtistForm[7] as $Artist) { ?>
				<li class="artists_remix">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
			}
		}
?>
			</ul>
		</div>
<?	} ?>
		<div class="box box_tags">
			<div class="head"><strong>Tags</strong></div>
			<ul class="stats nobullet">
<?	foreach ($Request['Tags'] as $TagID => $TagName) { ?>
				<li>
					<a href="torrents.php?taglist=<?=$TagName?>"><?=display_str($TagName)?></a>
					<br style="clear: both;" />
				</li>
<?	} ?>
			</ul>
		</div>
		<div class="box box_votes">
			<div class="head"><strong>Top Contributors</strong></div>
			<table class="layout" id="request_top_contrib">
<?
	$VoteMax = ($VoteCount < 5 ? $VoteCount : 5);
	$ViewerVote = false;
	for ($i = 0; $i < $VoteMax; $i++) {
		$User = array_shift($RequestVotes['Voters']);
		$Boldify = false;
		if ($User['UserID'] === $LoggedUser['ID']) {
			$ViewerVote = true;
			$Boldify = true;
		}
?>
				<tr>
					<td>
						<a href="user.php?id=<?=$User['UserID']?>"><?=($Boldify ? '<strong>' : '') . display_str($User['Username']) . ($Boldify ? '</strong>' : '')?></a>
					</td>
					<td class="number_column">
						<?=($Boldify ? '<strong>' : '') . Format::get_size($User['Bounty']) . ($Boldify ? "</strong>\n" : "\n")?>
					</td>
				</tr>
<?	}
	reset($RequestVotes['Voters']);
	if (!$ViewerVote) {
		foreach ($RequestVotes['Voters'] as $User) {
			if ($User['UserID'] === $LoggedUser['ID']) { ?>
				<tr>
					<td>
						<a href="user.php?id=<?=$User['UserID']?>"><strong><?=display_str($User['Username'])?></strong></a>
					</td>
					<td class="number_column">
						<strong><?=Format::get_size($User['Bounty'])?></strong>
					</td>
				</tr>
<?			}
		}
	}
?>
			</table>
		</div>
	</div>
	<div class="main_column">
		<table class="layout">
			<tr>
				<td class="label">Created</td>
				<td>
					<?=time_diff($Request['TimeAdded'])?> by <strong><?=Users::format_username($Request['UserID'], false, false, false)?></strong>
				</td>
			</tr>
<?	if ($CategoryName === 'Music') {
		if (!empty($Request['RecordLabel'])) { ?>
			<tr>
				<td class="label">Record label</td>
				<td><?=$Request['RecordLabel']?></td>
			</tr>
<?		}
		if (!empty($Request['CatalogueNumber'])) { ?>
			<tr>
				<td class="label">Catalogue number</td>
				<td><?=$Request['CatalogueNumber']?></td>
			</tr>
<?		} ?>
			<tr>
				<td class="label">Release type</td>
				<td><?=$ReleaseName?></td>
			</tr>
			<tr>
				<td class="label">Acceptable bitrates</td>
				<td><?=$BitrateString?></td>
			</tr>
			<tr>
				<td class="label">Acceptable formats</td>
				<td><?=$FormatString?></td>
			</tr>
			<tr>
				<td class="label">Acceptable media</td>
				<td><?=$MediaString?></td>
			</tr>
<?		if (!empty($Request['LogCue'])) { ?>
			<tr>
				<td class="label">Required CD FLAC only extras</td>
				<td><?=$Request['LogCue']?></td>
			</tr>
<?
		}
	}
	$Worldcat = '';
	$OCLC = str_replace(' ', '', $Request['OCLC']);
	if ($OCLC !== '') {
		$OCLCs = explode(',', $OCLC);
		for ($i = 0; $i < count($OCLCs); $i++) {
			if (!empty($Worldcat)) {
				$Worldcat .= ', <a href="https://www.worldcat.org/oclc/'.$OCLCs[$i].'">'.$OCLCs[$i].'</a>';
			} else {
				$Worldcat = '<a href="https://www.worldcat.org/oclc/'.$OCLCs[$i].'">'.$OCLCs[$i].'</a>';
			}
		}
	}
	if (!empty($Worldcat)) {
?>
		<tr>
			<td class="label">WorldCat (OCLC) ID</td>
			<td><?=$Worldcat?></td>
		</tr>
<?
	}
	if ($Request['GroupID']) {
?>
			<tr>
				<td class="label">Torrent group</td>
				<td><a href="torrents.php?id=<?=$Request['GroupID']?>">torrents.php?id=<?=$Request['GroupID']?></a></td>
			</tr>
<?	} ?>
			<tr>
				<td class="label">Votes</td>
				<td>
					<span id="votecount"><?=number_format($VoteCount)?></span>
<?	if ($CanVote) { ?>
					&nbsp;&nbsp;<a href="javascript:Vote(0)" class="brackets"><strong>+</strong></a>
					<strong>Costs <?=Format::get_size($MinimumVote, 0)?></strong>
<?	} ?>
				</td>
			</tr>
<?	if ($Request['LastVote'] > $Request['TimeAdded']) { ?>
			<tr>
				<td class="label">Last voted</td>
				<td><?=time_diff($Request['LastVote'])?></td>
			</tr>
<?
	}
	if ($CanVote) {
?>
			<tr id="voting">
				<td class="label tooltip" title="These units are in base 2, not base 10. For example, there are 1,024 MB in 1 GB.">Custom vote (MB)</td>
				<td>
					<input type="text" id="amount_box" size="8" onchange="Calculate();" />
					<select id="unit" name="unit" onchange="Calculate();">
						<option value="mb">MB</option>
						<option value="gb">GB</option>
					</select>
					<input type="button" value="Preview" onclick="Calculate();" />
					<strong><?=($RequestTax * 100)?>% of this is deducted as tax by the system.</strong>
				</td>
			</tr>
			<tr>
				<td class="label">Post vote information</td>
				<td>
					<form class="add_form" name="request" action="requests.php" method="get" id="request_form">
						<input type="hidden" name="action" value="vote" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" id="request_tax" value="<?=$RequestTax?>" />
						<input type="hidden" id="requestid" name="id" value="<?=$RequestID?>" />
						<input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" id="amount" name="amount" value="0" />
						<input type="hidden" id="current_uploaded" value="<?=$LoggedUser['BytesUploaded']?>" />
						<input type="hidden" id="current_downloaded" value="<?=$LoggedUser['BytesDownloaded']?>" />
						<input type="hidden" id="current_rr" value="<?=(float)$LoggedUser['RequiredRatio']?>" />
						<input id="total_bounty" type="hidden" value="<?=$RequestVotes['TotalBounty']?>" />
						Bounty after tax: <strong><span id="bounty_after_tax">0.00 MB</span></strong><br />
						If you add the entered <strong><span id="new_bounty">0.00 MB</span></strong> of bounty, your new stats will be: <br />
						Uploaded: <span id="new_uploaded"><?=Format::get_size($LoggedUser['BytesUploaded'])?></span><br />
						Ratio: <span id="new_ratio"><?=Format::get_ratio_html($LoggedUser['BytesUploaded'],$LoggedUser['BytesDownloaded'])?></span>
						<input type="button" id="button" value="Vote!" disabled="disabled" onclick="Vote();" />
					</form>
				</td>
			</tr>
<?	} ?>
			<tr id="bounty">
				<td class="label">Bounty</td>
				<td id="formatted_bounty"><?=Format::get_size($RequestVotes['TotalBounty'])?></td>
			</tr>
<?
	if ($IsFilled) {
		$TimeCompare = 1267643718; // Requests v2 was implemented 2010-03-03 20:15:18
?>
			<tr>
				<td class="label">Filled</td>
				<td>
					<strong><a href="torrents.php?<?=(strtotime($Request['TimeFilled']) < $TimeCompare ? 'id=' : 'torrentid=') . $Request['TorrentID']?>">Yes</a></strong>,
					by user <?=Users::format_username($Request['FillerID'], false, false, false)?>
<?		if ($LoggedUser['ID'] == $Request['UserID'] || $LoggedUser['ID'] == $Request['FillerID'] || check_perms('site_moderate_requests')) { ?>
						<strong><a href="requests.php?action=unfill&amp;id=<?=$RequestID?>" class="brackets">Unfill</a></strong> Unfilling a request without a valid, nontrivial reason will result in a warning.
<?		} ?>
				</td>
			</tr>
<?	} else { ?>
			<tr>
				<td class="label" valign="top">Fill request</td>
				<td>
					<form class="edit_form" name="request" action="" method="post">
						<div class="field_div">
							<input type="hidden" name="action" value="takefill" />
							<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
							<input type="hidden" name="requestid" value="<?=$RequestID?>" />
							<input type="text" size="50" name="link"<?=(!empty($Link) ? " value=\"$Link\"" : '')?> />
							<br />
							<strong>Should be the permalink (PL) to the torrent (e.g. <?=site_url()?>torrents.php?torrentid=xxxx).</strong>
						</div>
<?		if (check_perms('site_moderate_requests')) { ?>
						<div class="field_div">
							For user: <input type="text" size="25" name="user"<?=(!empty($FillerUsername) ? " value=\"$FillerUsername\"" : '')?> />
						</div>
<?		} ?>
						<div class="submit_div">
							<input type="submit" value="Fill request" />
						</div>
					</form>
				</td>
			</tr>
<?	} ?>
		</table>
		<div class="box box2 box_request_desc">
			<div class="head"><strong>Description</strong></div>
			<div class="pad">
<?=				Text::full_format($Request['Description']);?>
			</div>
		</div>
	<div id="request_comments">
		<div class="linkbox">
			<a name="comments"></a>
<?
$Pages = Format::get_pages($Page, $NumComments, TORRENT_COMMENTS_PER_PAGE, 9, '#comments');
echo $Pages;
?>
		</div>
<?

//---------- Begin printing
CommentsView::render_comments($Thread, $LastRead, "requests.php?action=view&amp;id=$RequestID");

if ($Pages) { ?>
		<div class="linkbox pager"><?=$Pages?></div>
<?
}

View::parse('generic/reply/quickreply.php', array(
	'InputName' => 'pageid',
	'InputID' => $RequestID,
	'Action' => 'comments.php?page=requests',
	'InputAction' => 'take_post',
	'SubscribeBox' => true
));
?>
		</div>
	</div>
</div>
<? View::show_footer(); ?>
