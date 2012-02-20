<?

/*
 * This is the page that displays the request to the end user after being created.
 */

include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
include(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

if(empty($_GET['id']) || !is_number($_GET['id'])) { 
	error(0);
}

$RequestID = $_GET['id'];

//First things first, lets get the data for the request.

$Request = get_requests(array($RequestID));	
$Request = $Request['matches'][$RequestID];
if(empty($Request)) {
	error(404);
}

list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Year, $Image, $Description, $CatalogueNumber, $RecordLabel, $ReleaseType,
	$BitrateList, $FormatList, $MediaList, $LogCue, $FillerID, $FillerName, $TorrentID, $TimeFilled, $GroupID) = $Request;

//Convenience variables
$IsFilled = !empty($TorrentID);
$CanVote = (empty($TorrentID) && check_perms('site_vote'));

if($CategoryID == 0) {
	$CategoryName = "Unknown";
} else {
	$CategoryName = $Categories[$CategoryID - 1];
}

//Do we need to get artists?
if($CategoryName == "Music") {
	$ArtistForm = get_request_artists($RequestID);
	$ArtistName = display_artists($ArtistForm, false, true);
	$ArtistLink = display_artists($ArtistForm, true, true);
	
	if($IsFilled) {
		$DisplayLink = $ArtistLink."<a href='torrents.php?torrentid=".$TorrentID."'>".$Title."</a> [".$Year."]";
	} else {
		$DisplayLink = $ArtistLink.$Title." [".$Year."]";
	}
	$FullName = $ArtistName.$Title." [".$Year."]";
	
	if($BitrateList != "") {
		$BitrateString = implode(", ", explode("|", $BitrateList));
		$FormatString = implode(", ", explode("|", $FormatList));
		$MediaString = implode(", ", explode("|", $MediaList));
	} else {
		$BitrateString = "Unknown, please read the description.";
		$FormatString = "Unknown, please read the description.";
		$MediaString = "Unknown, please read the description.";
	}
	
	if(empty($ReleaseType)) {
		$ReleaseName = "Unknown";
	} else {
		$ReleaseName = $ReleaseTypes[$ReleaseType];
	}
	
} else if($CategoryName == "Audiobooks" || $CategoryName == "Comedy") {
	$FullName = $Title." [".$Year."]";
	$DisplayLink = $Title." [".$Year."]";
} else {
	$FullName = $Title;
	$DisplayLink = $Title;
}

//Votes time
$RequestVotes = get_votes_array($RequestID);
$VoteCount = count($RequestVotes['Voters']);
$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0) || ($CategoryName == "Music" && $Year == 0)));
$UserCanEdit = (!$IsFilled && $LoggedUser['ID'] == $RequestorID && $VoteCount < 2);
$CanEdit = ($UserCanEdit || $ProjectCanEdit || check_perms('site_moderate_requests'));

show_header('View request: '.$FullName, 'comments,requests,bbcode');

?>
<div class="thin">
	<h2><a href="requests.php">Requests</a> &gt; <?=$CategoryName?> &gt; <?=$DisplayLink?></h2>
	<div class="linkbox">
<? if($CanEdit) { ?> 
		<a href="requests.php?action=edit&amp;id=<?=$RequestID?>">[Edit]</a>
<? }
if($UserCanEdit || check_perms('users_mod')) { //check_perms('site_moderate_requests')) { ?>
		<a href="requests.php?action=delete&amp;id=<?=$RequestID?>">[Delete]</a>
<? } ?>
<?	if(has_bookmarked('request', $RequestID)) { ?>
		<a href="#" id="bookmarklink_request_<?=$RequestID?>" onclick="Unbookmark('request', <?=$RequestID?>,'[Bookmark]');return false;">[Remove bookmark]</a>
<?	} else { ?>
		<a href="#" id="bookmarklink_request_<?=$RequestID?>" onclick="Bookmark('request', <?=$RequestID?>,'[Remove bookmark]');return false;">[Bookmark]</a>
<?	} ?>
		<a href="reports.php?action=report&amp;type=request&amp;id=<?=$RequestID?>">[Report Request]</a>
		<a href="upload.php?requestid=<?=$RequestID?><?=($GroupID?"&groupid=$GroupID":'')?>">[Upload Request]</a>
<? if(!$IsFilled && (($CategoryID == 0) || ($CategoryName == "Music" && $Year == 0))) { ?>
		<a href="reports.php?action=report&amp;type=request_update&amp;id=<?=$RequestID?>">[Request Update]</a>
<? } ?>
	</div>
	
	<div class="sidebar">
<? if($CategoryID != 0) { ?>
		<div class="box box_albumart">
			<div class="head"><strong>Cover</strong></div>
<?	if (!empty($Image)) { ?>
			<p align="center"><img style="max-width: 220px;" src="<?=$Image?>" alt="<?=$FullName?>" onclick="lightbox.init(this,220);" /></p>
<?	} else { ?>
			<p align="center"><img src="<?=STATIC_SERVER?>common/noartwork/<?=$CategoryIcons[$CategoryID-1]?>" alt="<?=$CategoryName?>" title="<?=$CategoryName?>" width="220" height="220" border="0" /></p>
<?	} ?>
		</div>
<? } 
	if($CategoryName == "Music") { ?>	
		<div class="box box_artists">
			<div class="head"><strong>Artists</strong></div>
			<ul class="stats nobullet">
<?
		if(!empty($ArtistForm[4]) && count($ArtistForm[4]) > 0) { 
?>
				<li class="artists_composer"><strong>Composers:</strong></li>
<?			foreach($ArtistForm[4] as $Artist) {
?>
				<li class="artists_composer">
					<?=display_artist($Artist)?>
				</li>
<?			}
		}
		if(!empty($ArtistForm[6]) && count($ArtistForm[6]) > 0) { 
?>
				<li class="artists_dj"><strong>DJ / Compiler:</strong></li>
<?			foreach($ArtistForm[6] as $Artist) {
?>
				<li class="artists_dj">
					<?=display_artist($Artist)?>
				</li>
<?
			}
		}
		if ((count($ArtistForm[6]) > 0) && (count($ArtistForm[1]) > 0)) {
			print '				<li class="artists_main"><strong>Artists:</strong></li>';
		} elseif ((count($ArtistForm[4]) > 0) && (count($ArtistForm[1]) > 0)) {
			print '				<li class="artists_main"><strong>Performers:</strong></li>';
		}
		foreach($ArtistForm[1] as $Artist) {
?>
				<li class="artists_main">
					<?=display_artist($Artist)?>
				</li>
<?		}
		if(!empty($ArtistForm[2]) && count($ArtistForm[2]) > 0) { 
?>
				<li class="artists_with"><strong>With:</strong></li>
<?			foreach($ArtistForm[2] as $Artist) {
?>
				<li class="artists_with">
					<?=display_artist($Artist)?>
				</li>
<?			}
		}
		if(!empty($ArtistForm[5]) && count($ArtistForm[5]) > 0) { 
?>
				<li class="artists_conductor"><strong>Conducted by:</strong></li>
<?			foreach($ArtistForm[5] as $Artist) {
?>
				<li class="artist_guest">
					<?=display_artist($Artist)?>
				</li>
<?			}
		}
		if(!empty($ArtistForm[3]) && count($ArtistForm[3]) > 0) { 
?>
				<li class="artists_remix"><strong>Remixed by:</strong></li>
<?			foreach($ArtistForm[3] as $Artist) {
?>
				<li class="artists_remix">
					<?=display_artist($Artist)?>
				</li>
<?
			}
		}
		if(!empty($ArtistForm[7]) && count($ArtistForm[7]) > 0) { 
?>
				<li class="artists_producer"><strong>Produced by:</strong></li>
<?			foreach($ArtistForm[7] as $Artist) {
?>
				<li class="artists_remix">
					<?=display_artist($Artist)?>
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
<?	foreach($Request['Tags'] as $TagID => $TagName) { ?>
				<li>
					<a href="torrents.php?taglist=<?=$TagName?>"><?=display_str($TagName)?></a>
					<br style="clear:both" />
				</li>
<?	} ?>
			</ul>
		</div>
		<div class="box box_votes">
			<div class="head"><strong>Top Contributors</strong></div>
			<table>
<?	$VoteMax = ($VoteCount < 5 ? $VoteCount : 5);
	$ViewerVote = false;
	for($i = 0; $i < $VoteMax; $i++) { 
		$User = array_shift($RequestVotes['Voters']);
		$Boldify = false;
		if ($User['UserID'] == $LoggedUser['ID']) {
			$ViewerVote = true;
			$Boldify = true;
		}
?>
				<tr>
					<td>
						<a href="user.php?id=<?=$User['UserID']?>"><?=$Boldify?'<strong>':''?><?=display_str($User['Username'])?><?=$Boldify?'</strong>':''?></a>
					</td>
					<td>
						<?=$Boldify?'<strong>':''?><?=get_size($User['Bounty'])?><?=$Boldify?'</strong>':''?>
					</td>
				</tr>
<?	} 
	reset($RequestVotes['Voters']);
	if (!$ViewerVote) {
		foreach ($RequestVotes['Voters'] as $User) {
			if ($User['UserID'] == $LoggedUser['ID']) { ?>
				<tr>
					<td>
						<a href="user.php?id=<?=$User['UserID']?>"><strong><?=display_str($User['Username'])?></strong></a>
					</td>
					<td>
						<strong><?=get_size($User['Bounty'])?></strong>
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
		<table>
			<tr>
				<td class="label">Created</td>
				<td>
					<?=time_diff($TimeAdded)?>	by  <strong><?=format_username($RequestorID, $RequestorName)?></strong>
				</td>
			</tr>
<?	if($CategoryName == "Music") {
		if(!empty($RecordLabel)) { ?>
			<tr>
				<td class="label">Record Label</td>
				<td>
					<?=$RecordLabel?>
				</td>
			</tr>
<?		} 
		if(!empty($CatalogueNumber)) { ?>
			<tr>
				<td class="label">Catalogue Number</td>
				<td>
					<?=$CatalogueNumber?>
				</td>
			</tr>
<?		} ?>
			<tr>
				<td class="label">Release Type</td>
				<td>
					<?=$ReleaseName?>
				</td>
			</tr>
			<tr>
				<td class="label">Acceptable Bitrates</td>
				<td>
					<?=$BitrateString?>
				</td>
			</tr>
			<tr>
				<td class="label">Acceptable Formats</td>
				<td>
					<?=$FormatString?>
				</td>
			</tr>
			<tr>
				<td class="label">Acceptable Media</td>
				<td>
					<?=$MediaString?>
				</td>
			</tr>
<?		if(!empty($LogCue)) { ?>
			<tr>
				<td class="label">Required FLAC only extra(s)</td>
				<td>
					<?=$LogCue?>
				</td>
			</tr>
<?		}
	} 
	if ($GroupID) { 
		/*$Groups = get_groups(array($GroupID), true, true, false);
		$Group = $Groups['matches'][$GroupID];
		$GroupLink = display_artists($Group['ExtendedArtists']).'<a href="torrents.php?id='.$GroupID.'">'.$Group['Name'].'</a>';*/
?>
			<tr>
				<td class="label">Torrent Group</td>
				<td><a href="torrents.php?id=<?=$GroupID?>">torrents.php?id=<?=$GroupID?></td>
			</tr>
<?	} ?>
			<tr>
				<td class="label">Votes</td>
				<td>
					<span id="votecount"><?=$VoteCount?></span> 
<?	if($CanVote) { ?>
					&nbsp;<a href="javascript:Vote(0)"><strong>(+)</strong></a>
					<strong>Costs <?=get_size($MinimumVote, 0)?></strong>
<?	} ?> 
				</td>
			</tr>
<?	if($CanVote) { ?>
			<tr id="voting">
				<td class="label">Custom Vote (MB)</td>
				<td>
					<input type="text" id="amount_box" size="8" onchange="Calculate();" />
					<select id="unit" name="unit" onchange="Calculate();">
						<option value='mb'>MB</option>
						<option value='gb'>GB</option>
					</select>
					<input type="button" value="Preview" onclick="Calculate();"/>
					<strong><?=($RequestTax * 100)?>% of this is deducted as tax by the system.</strong>
				</td>
			</tr>
			<tr>
				<td class="label">Post vote information</td>
				<td>
					<form action="requests.php" method="get" id="request_form">
						<input type="hidden" name="action" value="vote" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" id="request_tax" value="<?=$RequestTax?>" />
						<input type="hidden" id="requestid" name="id" value="<?=$RequestID?>" />
						<input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" id="amount" name="amount" value="0">
						<input type="hidden" id="current_uploaded" value="<?=$LoggedUser['BytesUploaded']?>" />
						<input type="hidden" id="current_downloaded" value="<?=$LoggedUser['BytesDownloaded']?>" />
						<input id="total_bounty" type="hidden" value="<?=$RequestVotes['TotalBounty']?>" />
						If you add the entered <strong><span id="new_bounty">0.00 MB</span></strong> of bounty, your new stats will be: <br/>
						Uploaded: <span id="new_uploaded"><?=get_size($LoggedUser['BytesUploaded'])?></span>
						Ratio: <span id="new_ratio"><?=ratio($LoggedUser['BytesUploaded'],$LoggedUser['BytesDownloaded'])?></span>
						<input type="button" id="button" value="Vote!" disabled="disabled" onclick="Vote();"/> 
					</form>
				</td>
			</tr>
<? }?> 
			<tr id="bounty">
				<td class="label">Bounty</td>
				<td id="formatted_bounty"><?=get_size($RequestVotes['TotalBounty'])?></td>
			</tr>
<?
	if($IsFilled) {
		$TimeCompare = 1267643718; // Requests v2 was implemented 2010-03-03 20:15:18
?>
			<tr>
				<td class="label">Filled</td>
				<td>
					<strong><a href="torrents.php?<?=(strtotime($TimeFilled)<$TimeCompare?'id=':'torrentid=').$TorrentID?>">Yes</a></strong>, 
					by user <?=format_username($FillerID, $FillerName)?>
<?		if($LoggedUser['ID'] == $RequestorID || $LoggedUser['ID'] == $FillerID || check_perms('site_moderate_requests')) { ?>
						<strong><a href="requests.php?action=unfill&amp;id=<?=$RequestID?>">(Unfill)</a></strong> Unfilling a request without a valid, nontrivial reason will result in a warning. 
<?		} ?>
				</td>
			</tr>
<?	} else { ?>
			<tr>
				<td class="label" valign="top">Fill request</td>
				<td>
					<form action="" method="post">
						<div>
							<input type="hidden" name="action" value="takefill" />
							<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
							<input type="hidden" name="requestid" value="<?=$RequestID?>" />
							<input type="text" size="50" name="link" <?=(!empty($Link) ? "value='$Link' " : '')?>/>
							<strong>Should be the permalink (PL) to the torrent (e.g. http://<?=NONSSL_SITE_URL?>/torrents.php?torrentid=xxxx).</strong>
							<br />
							<br />
							<? if(check_perms('site_moderate_requests')) { ?> For User: <input type="text" size="25" name="user" <?=(!empty($FillerUsername) ? "value='$FillerUsername' " : '')?>/>
							<br />
							<? } ?>
							<input type="submit" value="Fill request" />
							<br /> 
						</div>
					</form>
					
				</td>
			</tr>
<?	} ?>
			<tr>
				<td colspan="2" class="center"><strong>Description</strong></td>
			</tr>
			<tr>
				<td colspan="2"><?=$Text->full_format($Description)?></td>
			</tr>
		</table>
<?

$Results = $Cache->get_value('request_comments_'.$RequestID);
if($Results === false) {
	$DB->query("SELECT
			COUNT(c.ID)
			FROM requests_comments as c
			WHERE c.RequestID = '$RequestID'");
	list($Results) = $DB->next_record();
	$Cache->cache_value('request_comments_'.$RequestID, $Results, 0);
}

list($Page,$Limit) = page_limit(TORRENT_COMMENTS_PER_PAGE,$Results);

//Get the cache catalogue
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
$CatalogueLimit=$CatalogueID*THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
$Catalogue = $Cache->get_value('request_comments_'.$RequestID.'_catalogue_'.$CatalogueID);
if($Catalogue === false) {
	$DB->query("SELECT
			c.ID,
			c.AuthorID,
			c.AddedTime,
			c.Body,
			c.EditedUserID,
			c.EditedTime,
			u.Username
			FROM requests_comments as c
			LEFT JOIN users_main AS u ON u.ID=c.EditedUserID
			WHERE c.RequestID = '$RequestID'
			ORDER BY c.ID
			LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array(false,MYSQLI_ASSOC);
	$Cache->cache_value('request_comments_'.$RequestID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
}

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue,((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)%THREAD_CATALOGUE),TORRENT_COMMENTS_PER_PAGE,true);
?>
	<div class="linkbox"><a name="comments"></a>
<?
$Pages=get_pages($Page,$Results,TORRENT_COMMENTS_PER_PAGE,9,'#comments');
echo $Pages;
?>
	</div>
<?

//---------- Begin printing
foreach($Thread as $Key => $Post){
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(user_info($AuthorID));
?>
<table class="forum_post box vertical_margin<?=$HeavyInfo['DisableAvatars'] ? ' noavatar' : ''?>" id="post<?=$PostID?>">
	<tr class="colhead_dark">
		<td colspan="2">
			<span style="float:left;"><a href='#post<?=$PostID?>'>#<?=$PostID?></a>
				by <strong><?=format_username($AuthorID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?></strong> <?=time_diff($AddedTime)?> <a href="reports.php?action=report&amp;type=requests_comment&amp;id=<?=$PostID?>">[Report Comment]</a>
				- <a href="#quickpost" onclick="Quote('<?=$PostID?>','<?=$Username?>');">[Quote]</a>
<?if ($AuthorID == $LoggedUser['ID'] || check_perms('site_moderate_forums')){ ?>				- <a href="#post<?=$PostID?>" onclick="Edit_Form('<?=$PostID?>','<?=$Key?>');">[Edit]</a><? }
if (check_perms('site_moderate_forums')){ ?>				- <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');">[Delete]</a> <? } ?>
			</span>
			<span id="bar<?=$PostID?>" style="float:right;">
				<a href="#">&uarr;</a>
			</span>
		</td>
	</tr>
	<tr>
<? if(empty($HeavyInfo['DisableAvatars'])) { ?>
		<td class="avatar" valign="top">
	<? if ($Avatar) { ?>
			<img src="<?=$Avatar?>" width="150" alt="<?=$Username ?>'s avatar" />
	<? } else { ?>
			<img src="<?=STATIC_SERVER?>common/avatars/default.png" width="150" alt="Default avatar" />
	<?
	}
?>
		</td>
<?
}
?>
		<td class="body" valign="top">
			<div id="content<?=$PostID?>">
<?=$Text->full_format($Body)?>
<? if($EditedUserID){ ?>
				<br />
				<br />
<?	if(check_perms('site_moderate_forums')) { ?>
				<a href="#content<?=$PostID?>" onclick="LoadEdit('requests', <?=$PostID?>, 1); return false;">&laquo;</a> 
<? 	} ?>
				Last edited by
				<?=format_username($EditedUserID, $EditedUsername) ?> <?=time_diff($EditedTime,2,true,true)?>
<? } ?>
			</div>
		</td>
	</tr>
</table>
<?	} ?>
		<div class="linkbox">
		<?=$Pages?>
		</div>
<?
if(!$LoggedUser['DisablePosting']) { ?>
			<br />
			<h3>Post comment</h3>
			<div class="box pad" style="padding:20px 10px 10px 10px;">
				<table id="quickreplypreview" class="hidden forum_post box vertical_margin" id="preview">
					<tr class="colhead_dark">
						<td colspan="2">
							<span style="float:left;"><a href='#quickreplypreview'>#XXXXXX</a>
								by <strong><?=format_username($LoggedUser['ID'], $LoggedUser['Username'], $LoggedUser['Donor'], $LoggedUser['Warned'], $LoggedUser['Enabled'] == 2 ? false : true, $LoggedUser['PermissionID'])?></strong> <? if (!empty($LoggedUser['Title'])) { echo '('.$LoggedUser['Title'].')'; }?>
								Just now
								<a href="#quickreplypreview">[Report Comment]</a>
							</span>
							<span style="float:right;">
								<a href="#">&uarr;</a>
							</span>
						</td>
					</tr>
					<tr>
						<td class="avatar" valign="top">
				<? if (!empty($LoggedUser['Avatar'])) { ?>
							<img src="<?=$LoggedUser['Avatar']?>" width="150" alt="<?=$LoggedUser['Username']?>'s avatar" />
				<? } else { ?>
							<img src="<?=STATIC_SERVER?>common/avatars/default.png" width="150" alt="Default avatar" />
				<? } ?>
						</td>
						<td class="body" valign="top">
							<div id="contentpreview" style="text-align:left;"></div>
						</td>
					</tr>
				</table>
				<form id="quickpostform" action="" method="post" style="display: block; text-align: center;">
					<div id="quickreplytext">
						<input type="hidden" name="action" value="reply" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="requestid" value="<?=$RequestID?>" />
						<textarea id="quickpost" name="body" cols="70" rows="8"></textarea> <br />
					</div>
					<input id="post_preview" type="button" value="Preview" onclick="if(this.preview){Quick_Edit();}else{Quick_Preview();}" />
					<input type="submit" value="Post reply" />
				</form>
			</div>
<? } ?>
	</div>
</div>
<? show_footer(); ?>
