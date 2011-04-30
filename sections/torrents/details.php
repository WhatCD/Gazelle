<?

function compare($X, $Y){
	return($Y['score'] - $X['score']);
}

include(SERVER_ROOT.'/classes/class_text.php');
$Text = NEW TEXT;

$GroupID=ceil($_GET['id']);
if(!empty($_GET['revisionid']) && is_number($_GET['revisionid'])) {
	$RevisionID = $_GET['revisionid'];
} else { $RevisionID = 0; }

include(SERVER_ROOT.'/sections/torrents/functions.php');
$TorrentCache = get_group_info($GroupID, true, $RevisionID);

$TorrentDetails = $TorrentCache[0];
$TorrentList = $TorrentCache[1];

// Group details
list($WikiBody, $WikiImage, $GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $ReleaseType, $GroupCategoryID,
	$GroupTime, $TorrentTags, $TorrentTagIDs, $TorrentTagUserIDs, $TagPositiveVotes, $TagNegativeVotes) = array_shift($TorrentDetails);

$DisplayName=$GroupName;
$AltName=$GroupName; // Goes in the alt text of the image
$Title=$GroupName; // goes in <title>
$WikiBody = $Text->full_format($WikiBody);

$Artists = get_artist($GroupID);

if($Artists) {
	$DisplayName = '<span dir="ltr">'.display_artists($Artists, true).$DisplayName.'</span>';
	$AltName = display_str(display_artists($Artists, false)).$AltName;
	$Title = $AltName;
}

if($GroupYear>0) {
	$DisplayName.=' ['.$GroupYear.']';
	$AltName.=' ['.$GroupYear.']';
}
if($GroupCategoryID == 1) {
	$DisplayName.=' ['.$ReleaseTypes[$ReleaseType].']';
	$AltName.=' ['.$ReleaseTypes[$ReleaseType].']';
}

$Tags = array();
if ($TorrentTags != '') {
	$TorrentTags=explode('|',$TorrentTags);
	$TorrentTagIDs=explode('|',$TorrentTagIDs);
	$TorrentTagUserIDs=explode('|',$TorrentTagUserIDs);
	$TagPositiveVotes=explode('|',$TagPositiveVotes);
	$TagNegativeVotes=explode('|',$TagNegativeVotes);
	
	foreach ($TorrentTags as $TagKey => $TagName) {
		$Tags[$TagKey]['name'] = $TagName;
		$Tags[$TagKey]['score'] = ($TagPositiveVotes[$TagKey] - $TagNegativeVotes[$TagKey]);
		$Tags[$TagKey]['id']=$TorrentTagIDs[$TagKey];
		$Tags[$TagKey]['userid']=$TorrentTagUserIDs[$TagKey];
	}
	uasort($Tags, 'compare');
}

/*if (check_perms('site_debug')) {
	print_r($TorrentTags);
	print_r($Tags);
	print_r($TorrentTagUserIDs);
	die();
}*/

// Start output
show_header($Title,'browse,comments,torrent,bbcode');
?>
<div class="thin">
	<h2><?=$DisplayName?></h2>
	<div class="linkbox">
<?	if(check_perms('site_edit_wiki')) { ?>
		<a href="torrents.php?action=editgroup&amp;groupid=<?=$GroupID?>">[Edit description]</a>
<?	} ?>
		<a href="torrents.php?action=history&amp;groupid=<?=$GroupID?>">[View history]</a>
<?	if($RevisionID && check_perms('site_edit_wiki')) { ?>
		<a href="/torrents.php?action=revert&amp;groupid=<?=$GroupID ?>&amp;revisionid=<?=$RevisionID ?>&amp;auth=<?=$LoggedUser['AuthKey']?>">[Revert to this revision]</a>
<?	}
	if(($Bookmarks = $Cache->get_value('bookmarks_'.$LoggedUser['ID'])) === FALSE) {
		if(($Bookmarks = $Cache->get_value('bookmarks_'.$LoggedUser['ID'].'_groups')) === FALSE) {
			$DB->query("SELECT GroupID FROM bookmarks_torrents WHERE UserID = ".$LoggedUser['ID']);
			$Bookmarks = $DB->collect('GroupID');
			$Cache->cache_value('bookmarks_'.$LoggedUser['ID'].'_groups', $Bookmarks, 0);
		}
		$Bookmarked = in_array($GroupID,$Bookmarks);
	} else {
		$Bookmarks = unserialize($Bookmarks);
		$Bookmarked = array_key_exists($GroupID, $Bookmarks[0][1]);
	}
	if($Bookmarked) {
?>
		<a href="#" id="bookmarklink<?=$GroupID?>" onclick="unbookmark(<?=$GroupID?>,'[Bookmark]');return false;">[Remove bookmark]</a>
<?	} else { ?>
		<a href="#" id="bookmarklink<?=$GroupID?>" onclick="Bookmark(<?=$GroupID?>,'[Remove bookmark]');return false;">[Bookmark]</a>
<?	}
	if($Categories[$GroupCategoryID-1] == 'Music') { ?>
		<a href="upload.php?groupid=<?=$GroupID?>">[Add format]</a>
<?	} 
	if(check_perms('site_submit_requests')) { ?>
		<a href="requests.php?action=new&amp;groupid=<?=$GroupID?>">[Request format]</a>
<?	}?>
	</div>

	<div class="sidebar">
		<div class="box box_albumart">
			<div class="head"><strong>Cover</strong></div>
<?
if ($WikiImage!="") {
?>
			<p align="center"><img style="max-width: 220px;" src="<?=$WikiImage?>" alt="<?=$AltName?>" onclick="lightbox.init(this,220);" /></p>
<?
} else {
?>
			<p align="center"><img src="<?=STATIC_SERVER?>common/noartwork/<?=$CategoryIcons[$GroupCategoryID-1]?>" alt="<?=$Categories[$GroupCategoryID-1]?>" title="<?=$Categories[$GroupCategoryID-1]?>" width="220" height="220" border="0" /></p>
<?
}
?>
		</div>
<?
if($Categories[$GroupCategoryID-1] == 'Music') {
	$ShownWith = false;
?>
		<div class="box box_artists">
			<div class="head"><strong>Artists</strong>
			<?=(check_perms('torrents_edit')) ? '<span style="float:right;"><a onclick="ArtistManager(); return false;" href="#">[Edit]</a></span>' : ''?>
			</div>
			<ul class="stats nobullet" id="artist_list">
<?
	foreach($Artists[1] as $Artist) {
?>
				<li class="artist_main">
					<?=display_artist($Artist).'&lrm;'?>
<?		if(check_perms('torrents_edit')){
			$AliasID = $Artist['aliasid'];
			if (empty($AliasID)) {
				$AliasID = $Artist['id'];
			}
?>
			&nbsp;(<?=$AliasID?>)&nbsp;
				<span class="remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>');this.parentNode.parentNode.style.display = 'none';">[X]</a></span>
<?		} ?>
				</li>
<?
	}
	if(!empty($Artists[2]) && count($Artists[2]) > 0) {
		print '				<li class="artists_with"><strong>With:</strong></li>';
		foreach($Artists[2] as $Artist) {
?>
				<li class="artist_guest">
					<?=display_artist($Artist).'&lrm;'?>
<?			if(check_perms('torrents_edit')){
				$DB->query("SELECT AliasID FROM artists_alias WHERE ArtistID = ".$Artist['id']." AND ArtistID != AliasID AND Name = '".db_string($Artist['name'])."'");
				list($AliasID) = $DB->next_record();
				if (empty($AliasID)) {
					$AliasID = $Artist['id'];
				}
?>
				&nbsp;(<?=$AliasID?>)&nbsp;
					<span class="remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>');this.parentNode.parentNode.style.display = 'none';">[X]</a></span>
<?			} ?>
				</li>
<?
		}
	}
	if (!empty($Artists[3]) && count($Artists[3]) > 0) {
		print '				<li class="artists_remix"><strong>Remixed By:</strong></li>';
		foreach($Artists[3] as $Artist) {
?>
				<li class="artists_remix">
					<?=display_artist($Artist).'&lrm;'?>
<?		      if(check_perms('torrents_edit')){
			        $DB->query("SELECT AliasID FROM artists_alias WHERE ArtistID = ".$Artist['id']." AND ArtistID != AliasID AND Name = '".db_string($Artist['name'])."'");
                                list($AliasID) = $DB->next_record();
                                if (empty($AliasID)) {
                                        $AliasID = $Artist['id'];
                                }
?>
				&nbsp;(<?=$AliasID?>)&nbsp;
					<span class="remove_artist"><a href="javascript:void(0);" onclick="ajax.get('torrents.php?action=delete_alias&amp;auth=' + authkey + '&amp;groupid=<?=$GroupID?>&amp;artistid=<?=$Artist['id']?>');this.parentNode.parentNode.style.display = 'none';">[X]</a></span>
<?		      } ?>
				</li>
<?
		}
	}
?>
			</ul>
		</div>
<? 
		if(check_perms('torrents_add_artist')) { ?>
		<div class="box box_addartists">
			<div class="head"><strong>Add artist</strong><span style="float:right;"><a onclick="AddArtistField(); return false;" href="#">[+]</a></span></div>
			<div class="body">
				<form action="torrents.php" method="post">
					<div id="AddArtists">
						<input type="hidden" name="action" value="add_alias" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="groupid" value="<?=$GroupID?>" />
						<input type="text" name="aliasname[]" size="17" />
						<select name="importance[]">
							<option value="1">Main</option>
							<option value="2">Guest</option>
							<option value="3">Remixer</option>
						</select>
					</div>
					<input type="submit" value="Add" />
				</form>
			</div>
		</div>
<?		}
	}?>
		<div class="box box_tags">
			<div class="head"><strong>Tags</strong></div>
<?
if(count($Tags) > 0) {
?>
			<ul class="stats nobullet">
<?
	foreach($Tags as $TagKey=>$Tag) {
			
?>
				<li>
					<a href="torrents.php?taglist=<?=$Tag['name']?>" style="float:left; display:block;"><?=display_str($Tag['name'])?></a>
					<div style="float:right; display:block; letter-spacing: -1px;">
					<a href="torrents.php?action=vote_tag&amp;way=down&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" style="font-family: monospace;" >[-]</a>
					<?=$Tag['score']?>
					<a href="torrents.php?action=vote_tag&amp;way=up&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" style="font-family: monospace;">[+]</a>
<?		if(check_perms('users_warn')){ ?>
					<a href="user.php?id=<?=$Tag['userid']?>" >[U]</a>
<?		} ?>
<?		if(check_perms('site_delete_tag')){ ?>
					<a href="torrents.php?action=delete_tag&amp;groupid=<?=$GroupID?>&amp;tagid=<?=$Tag['id']?>&amp;auth=<?=$LoggedUser['AuthKey']?>" >[X]</a>
<?		} ?>
					</div>
					<br style="clear:both" />
				</li>
<?
	}
?>
			</ul>
<?
} else {
?>
			There are no tags to display.
<?
}
?>
		</div>
		<div class="box">
			<div class="head"><strong>Add tag</strong></div>
			<div class="body">
				<form action="torrents.php" method="post">
					<input type="hidden" name="action" value="add_tag" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="groupid" value="<?=$GroupID?>" />
					<input type="text" name="tagname" size="20" />
					<input type="submit" value="+" />
				</form>
				<br /><br />
				<strong><a href="rules.php?p=tag">Tagging rules</a></strong>
			</div>
		</div>
	</div>
	<div class="main_column">
		<table class="torrent_table">
			<tr class="colhead_dark">
				<td width="80%"><strong>Torrents</strong></td>
				<td><strong>Size</strong></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" alt="Snatches" title="Snatches" /></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/seeders.png" alt="Seeders" title="Seeders" /></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/leechers.png" alt="Leechers" title="Leechers" /></td>
			</tr>
<?

function filelist($Str) {
	return "</td><td>".get_size($Str[1])."</td></tr>";
}

$LastRemasterYear = '-';
$LastRemasterTitle = '';
$LastRemasterRecordLabel = '';
$LastRemasterCatalogueNumber = '';

foreach ($TorrentList as $Torrent) {
	
		//t.ID,	t.Media, t.Format, t.Encoding, t.Remastered, t.RemasterYear, t.RemasterTitle, t.RemasterRecordLabel,t.RemasterCatalogueNumber,
		//t.Scene, t.HasLog, t.HasCue, t.LogScore, t.FileCount, t.Size, t.Seeders, t.Leechers, t.Snatched, t.FreeTorrent, t.Time, t.Description,
		//t.FileList, t.FilePath, t.UserID, um.Username, t.last_action
	
	list($TorrentID, $Media, $Format, $Encoding, $Remastered, $RemasterYear, $RemasterTitle, $RemasterRecordLabel, $RemasterCatalogueNumber, 
		$Scene, $HasLog, $HasCue, $LogScore, $FileCount, $Size, $Seeders, $Leechers, $Snatched, $FreeTorrent, $TorrentTime, $Description, 
		$FileList, $FilePath, $UserID, $Username, $LastActive, $BadTags, $BadFolders, $BadFiles, $LastReseedRequest, $LogInDB, $HasFile, $ExtendedGrace) = $Torrent;

	if($Remastered && !$RemasterYear) {
		if(!isset($FirstUnknown)) {
			$FirstUnknown = true;
		} else {
			$FirstUnknown = false;
		}
	}

	$Reported = false;
	unset($ReportedTimes);
	$Reports = $Cache->get_value('reports_torrent_'.$TorrentID);
	if($Reports === false) {
		$DB->query("SELECT r.ID,
				r.ReporterID,
				r.Type,
				r.UserComment,
				r.ReportedTime
			FROM reportsv2 AS r
			WHERE TorrentID = $TorrentID
				AND Type != 'edited'
				AND Status != 'Resolved'");
		$Reports = $DB->to_array();
		$Cache->cache_value('reports_torrent_'.$TorrentID, $Reports, 0);
	}	
	if(count($Reports) > 0) {
		$Reported = true;
		include(SERVER_ROOT.'/sections/reportsv2/array.php');
		$ReportInfo = "<table><tr class='colhead_dark' style='font-weight: bold;'><td>This torrent has ".count($Reports)." active ".(count($Reports) > 1 ?'reports' : 'report').":</td></tr>";

		foreach($Reports as $Report) {
			list($ReportID, $ReporterID, $ReportType, $ReportReason, $ReportedTime) = $Report;

			$Reporter = user_info($ReporterID);
			$ReporterName = $Reporter['Username'];

			if (array_key_exists($ReportType, $Types[$GroupCategoryID])) {
				$ReportType = $Types[$GroupCategoryID][$ReportType];
			} else if(array_key_exists($ReportType,$Types['master'])) {
				$ReportType = $Types['master'][$ReportType];
			} else {
				//There was a type but it wasn't an option!
				$ReportType = $Types['master']['other'];
			}
			$ReportInfo .= "<tr><td>".(check_perms('admin_reports') ? "<a href='user.php?id=$ReporterID'>$ReporterName</a> <a href='reportsv2.php?view=report&amp;id=$ReportID'>reported it</a> " : "Someone reported it ").strtolower(time_diff($ReportedTime))." for the reason '".$ReportType['title']."':";
			$ReportInfo .= "<blockquote>".$Text->full_format($ReportReason)."</blockquote></td></tr>";
		}
		$ReportInfo .= "</table>";
	}
	
	$CanEdit = (check_perms('torrents_edit') || (($UserID == $LoggedUser['ID'] && !$LoggedUser['DisableWiki']) && !($Remastered && !$RemasterYear)));
	
	$FileList = str_replace(array('_','-'), ' ', $FileList);
	$FileList = str_replace('|||','<tr><td>',display_str($FileList));
	$FileList = preg_replace_callback('/\{\{\{([^\{]*)\}\}\}/i','filelist',$FileList);
	$FileList = '<table style="overflow-x:auto;"><tr class="colhead_dark"><td><strong><div style="float: left; display: block;">File Name'.(check_perms('users_mod') ? ' [<a href="torrents.php?action=regen_filelist&amp;torrentid='.$TorrentID.'">Regenerate</a>]' : '').'</div></strong><div style="float:right; display:block;">'.(empty($FilePath) ? '' : '/'.$FilePath.'/' ).'</div></td><td><strong>Size</strong></td></tr><tr><td>'.$FileList."</table>";

	$ExtraInfo=''; // String that contains information on the torrent, eg. format and encoding
	$AddExtra=''; // Separator between torrent properties

	$TorrentUploader = $Username; // Save this for "Uploaded by:" below

	if($Format) { $ExtraInfo.=display_str($Format); $AddExtra=' / '; }
	if($Encoding) { $ExtraInfo.=$AddExtra.display_str($Encoding); $AddExtra=' / '; }
	if($HasLog) { $ExtraInfo.=$AddExtra.'Log'; $AddExtra=' / '; }
	if($HasLog && $LogInDB) { $ExtraInfo.=' ('.(int) $LogScore.'%)'; }
	if($HasCue) { $ExtraInfo.=$AddExtra.'Cue'; $AddExtra=' / '; }
	if($Media) { $ExtraInfo.=$AddExtra.display_str($Media); $AddExtra=' / '; }
	if($Scene) { $ExtraInfo.=$AddExtra.'Scene'; $AddExtra=' / '; }
	if(!$ExtraInfo) {
		$ExtraInfo = $GroupName ; $AddExtra=' / ';
	}
	if($FreeTorrent == '1') { $ExtraInfo.=$AddExtra.'<strong>Freeleech!</strong>'; $AddExtra=' / '; }
	if($FreeTorrent == '2') { $ExtraInfo.=$AddExtra.'<strong>Neutral Leech!</strong>'; $AddExtra=' / '; }
	if($Reported) { $ExtraInfo.=$AddExtra.'<strong>Reported</strong>'; $AddExtra=' / '; }
	if(!empty($BadTags)) { $ExtraInfo.=$AddExtra.'<strong>Bad Tags</strong>'; $AddExtra=' / '; }
	if(!empty($BadFolders)) { $ExtraInfo.=$AddExtra.'<strong>Bad Folders</strong>'; $AddExtra=' / '; }
	if(!empty($BadFiles)) { $ExtraInfo.=$AddExtra.'<strong>Bad File Names</strong>'; $AddExtra=' / '; }
	if (!empty($ExtendedGrace)) { $ExtraInfo.=$AddExtra.'<strong>Extended Grace Period</strong>'; $AddExtra=' / '; }
	
	if($GroupCategoryID == 1 
		&& ($RemasterTitle != $LastRemasterTitle
		|| $RemasterYear != $LastRemasterYear
		|| $RemasterRecordLabel != $LastRemasterRecordLabel 
		|| $RemasterCatalogueNumber != $LastRemasterCatalogueNumber
		|| $FirstUnknown)) {

		if($Remastered && $RemasterYear != 0){
		
			$RemasterName = $RemasterYear;
			$AddExtra = " - ";
			if($RemasterRecordLabel) { $RemasterName .= $AddExtra.display_str($RemasterRecordLabel); $AddExtra=' / '; }
			if($RemasterCatalogueNumber) { $RemasterName .= $AddExtra.display_str($RemasterCatalogueNumber); $AddExtra=' / '; }
			if($RemasterTitle) { $RemasterName .= $AddExtra.display_str($RemasterTitle); $AddExtra=' / '; }			
?>
			<tr class="group_torrent">
				<td colspan="5" class="edition_info"><strong><?=$RemasterName?></strong></td>
			</tr>
<?
		} else {
			if(!$Remastered) {
				$MasterName = "Original Release";
				$AddExtra = " / ";
				if($GroupRecordLabel) { $MasterName .= $AddExtra.$GroupRecordLabel; $AddExtra=' / '; }
				if($GroupCatalogueNumber) { $MasterName .= $AddExtra.$GroupCatalogueNumber; $AddExtra=' / '; }
?>
		<tr class="group_torrent">
			<td colspan="5" class="edition_info"><strong><?=$MasterName?></strong></td>
		</tr>
<?
			} else {
?>
		<tr class="group_torrent">
			<td colspan="5" class="edition_info"><strong>Unknown Release(s)</strong></td>
		</tr>
<?
			}
		}
	}
	$LastRemasterTitle = $RemasterTitle;
	$LastRemasterYear = $RemasterYear;
	$LastRemasterRecordLabel = $RemasterRecordLabel;
	$LastRemasterCatalogueNumber = $RemasterCatalogueNumber;
?>

			<tr class="group_torrent" style="font-weight: normal;" id="torrent<?=$TorrentID?>">
				<td>
					<span>[
						<a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download"><?=$HasFile ? 'DL' : 'Missing'?></a>
						| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a>
<?	if($CanEdit) { ?>
						| <a href="torrents.php?action=edit&amp;id=<?=$TorrentID ?>" title="Edit">ED</a>
<?	} ?>
<?	if(check_perms('torrents_delete') || $UserID == $LoggedUser['ID']) { ?>
						| <a href="torrents.php?action=delete&amp;torrentid=<?=$TorrentID ?>" title="Remove">RM</a>
<?	} ?>

						| <a href="torrents.php?torrentid=<?=$TorrentID ?>" title="Permalink">PL</a>
					]</span>
					<a href="#" onclick="$('#torrent_<?=$TorrentID?>').toggle(); return false;">&raquo; <?=$ExtraInfo; ?></a>
				</td>
				<td class="nobr"><?=get_size($Size)?></td>
				<td><?=number_format($Snatched)?></td>
				<td><?=number_format($Seeders)?></td>
				<td><?=number_format($Leechers)?></td>
			</tr>
			<tr class="pad <? if(!isset($_GET['torrentid']) || $_GET['torrentid']!=$TorrentID) { ?>hidden<? } ?>" id="torrent_<?=$TorrentID; ?>">
				<td colspan="5">
					<blockquote>
						Uploaded by <?=format_username($UserID, $TorrentUploader)?> <?=time_diff($TorrentTime);?>
<? if($Seeders == 0){ ?>
						<?
						if ($LastActive != '0000-00-00 00:00:00' && time() - strtotime($LastActive) >= 1209600) { ?>
							<br /><strong>Last active: <?=time_diff($LastActive);?></strong>
						<?} else { ?>
						<br />Last active: <?=time_diff($LastActive);?>
						<?} ?>
						<?
						if ($LastActive != '0000-00-00 00:00:00' && time() - strtotime($LastActive) >= 345678 && time()-strtotime($LastReseedRequest)>=864000) { ?>
						<br /><a href="torrents.php?action=reseed&amp;torrentid=<?=$TorrentID?>&amp;groupid=<?=$GroupID?>">[Request re-seed]</a>
						<?} ?>
						
<? } ?>

					</blockquote>
<? if(check_perms('site_moderate_requests')) { ?>
					<div class="linkbox">
						<a href="torrents.php?action=masspm&amp;id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>">[Mass PM Snatchers]</a>
					</div>
<? } ?>
					<div class="linkbox">
						<a href="#" onclick="show_peers('<?=$TorrentID?>', 0);return false;">(View Peerlist)</a>
<? if(check_perms('site_view_torrent_snatchlist')) { ?> 
						<a href="#" onclick="show_downloads('<?=$TorrentID?>', 0);return false;">(View Downloadlist)</a>
						<a href="#" onclick="show_snatches('<?=$TorrentID?>', 0);return false;">(View Snatchlist)</a>
<? } ?>
						<a href="#" onclick="show_files('<?=$TorrentID?>');return false;">(View Filelist)</a>
<? if($Reported) { ?> 
						<a href="#" onclick="show_reported('<?=$TorrentID?>');return false;">(View Report Information)</a>
<? } ?>
					</div>
					<div id="peers_<?=$TorrentID?>" class="hidden"></div>
					<div id="downloads_<?=$TorrentID?>" class="hidden"></div>
					<div id="snatches_<?=$TorrentID?>" class="hidden"></div>
					<div id="files_<?=$TorrentID?>" class="hidden"><?=$FileList?></div>
<?  if($Reported) { ?> 
					<div id="reported_<?=$TorrentID?>" class="hidden"><?=$ReportInfo?></div>
<? } ?>
					<? if(!empty($Description)) {
						echo '<blockquote>'.$Text->full_format($Description).'</blockquote>';}
					?>
				</td>
			</tr>
<? } ?>
		</table>
<?
$Collages = $Cache->get_value('torrent_collages_'.$GroupID);
if(!is_array($Collages)) {
	$DB->query("SELECT c.Name, c.NumTorrents, c.ID FROM collages AS c JOIN collages_torrents AS ct ON ct.CollageID=c.ID WHERE ct.GroupID='$GroupID' AND Deleted='0' AND CategoryID!='0'");
	$Collages = $DB->to_array();
	$Cache->cache_value('torrent_collages_'.$GroupID, $Collages, 3600*6);
}
if(count($Collages)>0) {
?>
		<table id="collages">
			<tr class="colhead">
				<td width="85%">Collage name</td>
				<td># torrents</td>
			</tr>
<?	foreach ($Collages as $Collage) { 
		list($CollageName, $CollageTorrents, $CollageID) = $Collage;
?>
			<tr>
				<td><a href="collages.php?id=<?=$CollageID?>"><?=$CollageName?></a></td>
				<td><?=$CollageTorrents?></td>
			</tr>
<?	} ?>
		</table>
<?
}
?>
		<div class="box">
			<div class="head"><strong><?=(!empty($ReleaseType) ? $ReleaseTypes[$ReleaseType].' info' : 'Info' )?></strong></div>
			<div class="body"><? if ($WikiBody!="") { echo $WikiBody; } else { echo "There is no information on this torrent."; } ?></div>
		</div>
<?

$Results = $Cache->get_value('torrent_comments_'.$GroupID);
if($Results === false) {
	$DB->query("SELECT
			COUNT(c.ID)
			FROM torrents_comments as c
			WHERE c.GroupID = '$GroupID'");
	list($Results) = $DB->next_record();
	$Cache->cache_value('torrent_comments_'.$GroupID, $Results, 0);
}

if(isset($_GET['postid']) && is_number($_GET['postid']) && $Results > TORRENT_COMMENTS_PER_PAGE) {
	$DB->query("SELECT COUNT(ID) FROM torrents_comments WHERE GroupID = $GroupID AND ID <= $_GET[postid]");
	list($PostNum) = $DB->next_record();
	list($Page,$Limit) = page_limit(TORRENT_COMMENTS_PER_PAGE,$PostNum);
} else {
	list($Page,$Limit) = page_limit(TORRENT_COMMENTS_PER_PAGE,$Results);
}

//Get the cache catalogue
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
$CatalogueLimit=$CatalogueID*THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
$Catalogue = $Cache->get_value('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID);
if($Catalogue === false) {
	$DB->query("SELECT
			c.ID,
			c.AuthorID,
			c.AddedTime,
			c.Body,
			c.EditedUserID,
			c.EditedTime,
			u.Username
			FROM torrents_comments as c
			LEFT JOIN users_main AS u ON u.ID=c.EditedUserID
			WHERE c.GroupID = '$GroupID'
			ORDER BY c.ID
			LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array(false,MYSQLI_ASSOC);
	$Cache->cache_value('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
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
			<span style="float:left;"><a class="post_id" href='torrents.php?id=<?=$GroupID?>&amp;postid=<?=$PostID?>#post<?=$PostID?>'>#<?=$PostID?></a>
				<strong><?=format_username($AuthorID, $Username, $Donor, $Warned, $Enabled == 2 ? false : true, $PermissionID)?></strong> <?=time_diff($AddedTime)?> <a href="reports.php?action=report&amp;type=torrents_comment&amp;id=<?=$PostID?>">[Report]</a>
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
				<a href="#content<?=$PostID?>" onclick="LoadEdit('torrents', <?=$PostID?>, 1); return false;">&laquo;</a> 
<? 	} ?>
				Last edited by
				<?=format_username($EditedUserID, $EditedUsername) ?> <?=strtolower(time_diff($EditedTime))?>
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
			<h3>Post reply</h3>
			<div class="box pad">
				<table id="quickreplypreview" class="forum_post box vertical_margin hidden" style="text-align:left;">
					<tr class="colhead_dark">
						<td colspan="2">
							<span style="float:left;"><a href='#quickreplypreview'>#XXXXXX</a>
								by <strong><?=format_username($LoggedUser['ID'], $LoggedUser['Username'], $LoggedUser['Donor'], $LoggedUser['Warned'], $LoggedUser['Enabled'] == 2 ? false : true, $LoggedUser['PermissionID'])?></strong>
							Just now
							<a href="#quickreplypreview">[Report Comment]</a>
							</span>
							<span id="barpreview" style="float:right;">
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
						<input type="hidden" name="groupid" value="<?=$GroupID?>" />
						<textarea id="quickpost" name="body"  cols="70"  rows="8"></textarea> <br />
					</div>
					<input id="post_preview" type="button" value="Preview" onclick="if(this.preview){Quick_Edit();}else{Quick_Preview();}" />
					<input type="submit" value="Post reply" />
				</form>
			</div>
<? } ?>
	</div>
</div>
<?

show_footer();
?>
