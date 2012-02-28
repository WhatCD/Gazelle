<?
//~~~~~~~~~~~ Main collage page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

function compare($X, $Y){
	return($Y['count'] - $X['count']);
}

include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

$CollageID = $_GET['id'];
if(!is_number($CollageID)) { error(0); }

$TokenTorrents = $Cache->get_value('users_tokens_'.$UserID);
if (empty($TokenTorrents)) {
	$DB->query("SELECT TorrentID FROM users_freeleeches WHERE UserID=$UserID AND Expired=FALSE");
	$TokenTorrents = $DB->collect('TorrentID');
	$Cache->cache_value('users_tokens_'.$UserID, $TokenTorrents);
}

$Data = $Cache->get_value('collage_'.$CollageID);

if($Data) {
	$Data = unserialize($Data);
	list($K, list($Name, $Description, $CollageDataList, $TorrentList, $CommentList, $Deleted, $CollageCategoryID, $CreatorID)) = each($Data);
} else {
	$DB->query("SELECT Name, Description, UserID, Deleted, CategoryID, Locked, MaxGroups, MaxGroupsPerUser FROM collages WHERE ID='$CollageID'");
	if($DB->record_count() > 0) {
		list($Name, $Description, $CreatorID, $Deleted, $CollageCategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser) = $DB->next_record();
		$TorrentList='';
		$CollageList='';
	} else {
		$Deleted = '1';
	}
}

if($Deleted == '1') {
	header('Location: log.php?search=Collage+'.$CollageID);
	die();
}

if($CollageCategoryID == 0 && !check_perms('site_collages_delete')) {
	if(!check_perms('site_collages_personal') || $CreatorID!=$LoggedUser['ID']) {
		$Locked = true;
	}
}

//Handle subscriptions
if(($CollageSubscriptions = $Cache->get_value('collage_subs_user_'.$LoggedUser['ID'])) === FALSE) {
	$DB->query("SELECT CollageID FROM users_collage_subs WHERE UserID = '$LoggedUser[ID]'");
	$CollageSubscriptions = $DB->collect(0);
	$Cache->cache_value('collage_subs_user_'.$LoggedUser['ID'],$CollageSubscriptions,0);
}

if(empty($CollageSubscriptions)) {
	$CollageSubscriptions = array();
}

if(in_array($CollageID, $CollageSubscriptions)) {
	$Cache->delete_value('collage_subs_user_new_'.$LoggedUser['ID']);
}
$DB->query("UPDATE users_collage_subs SET LastVisit=NOW() WHERE UserID = ".$LoggedUser['ID']." AND CollageID=$CollageID");


// Build the data for the collage and the torrent list
if(!is_array($TorrentList)) {
	$DB->query("SELECT ct.GroupID,
			tg.WikiImage,
			tg.CategoryID,
			um.ID,
			um.Username
			FROM collages_torrents AS ct
			JOIN torrents_group AS tg ON tg.ID=ct.GroupID
			LEFT JOIN users_main AS um ON um.ID=ct.UserID
			WHERE ct.CollageID='$CollageID'
			ORDER BY ct.Sort");
	
	$GroupIDs = $DB->collect('GroupID');
	$CollageDataList=$DB->to_array('GroupID', MYSQLI_ASSOC);
	if(count($GroupIDs)>0) {
		$TorrentList = get_groups($GroupIDs);
		$TorrentList = $TorrentList['matches'];
	} else {
		$TorrentList = array();
	}
}

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = array();
$TorrentTable = '';

$NumGroups = 0;
$NumGroupsByUser = 0;
$Artists = array();
$Tags = array();
$Users = array();
$Number = 0;

foreach ($TorrentList as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TagList, $ReleaseType, $GroupVanityHouse, $Torrents, $GroupArtists, $ExtendedArtists) = array_values($Group);
	list($GroupID2, $Image, $GroupCategoryID, $UserID, $Username) = array_values($CollageDataList[$GroupID]);
	
	// Handle stats and stuff
	$Number++;
	$NumGroups++;
	if($UserID == $LoggedUser['ID']) {
		$NumGroupsByUser++;
	}
	
	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
		$CountArtists = array_merge((array)$ExtendedArtists[1], (array)$ExtendedArtists[4], (array)$ExtendedArtists[5], (array)$ExtendedArtists[6]);
	} else{
		$CountArtists = $GroupArtists;
	}
	
	if($CountArtists) {
		foreach($CountArtists as $Artist) {
			if(!isset($Artists[$Artist['id']])) {
				$Artists[$Artist['id']] = array('name'=>$Artist['name'], 'count'=>1);
			} else {
				$Artists[$Artist['id']]['count']++;
			}
		}
	}
	
	if($Username) {
		if(!isset($Users[$UserID])) {
			$Users[$UserID] = array('name'=>$Username, 'count'=>1);
		} else {
			$Users[$UserID]['count']++;
		}
	}
	
	$TagList = explode(' ',str_replace('_','.',$TagList));

	$TorrentTags = array();
	foreach($TagList as $Tag) {
		if(!isset($Tags[$Tag])) {
			$Tags[$Tag] = array('name'=>$Tag, 'count'=>1);
		} else {
			$Tags[$Tag]['count']++;
		}
		$TorrentTags[]='<a href="torrents.php?taglist='.$Tag.'">'.$Tag.'</a>';
	}
	$PrimaryTag = $TagList[0];
	$TorrentTags = implode(', ', $TorrentTags);
	$TorrentTags='<br /><div class="tags">'.$TorrentTags.'</div>';

	$DisplayName = $Number.' - ';
	
	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5])|| !empty($ExtendedArtists[6])) {
			unset($ExtendedArtists[2]);
			unset($ExtendedArtists[3]);
			$DisplayName .= display_artists($ExtendedArtists);
	} elseif(count($GroupArtists)>0) {
			$DisplayName .= display_artists(array('1'=>$GroupArtists));
	}
	
	$DisplayName .= '<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName = $DisplayName. ' ['. $GroupYear .']';}
	if($GroupVanityHouse) { $DisplayName .= ' [<abbr title="This is a vanity house release">VH</abbr>]'; }
	// Start an output buffer, so we can store this output in $TorrentTable
	ob_start();

	if(count($Torrents)>1 || $GroupCategoryID==1) {
			// Grouped torrents
			$ShowGroups = !(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping'] == 1);
?>
			<tr class="group discog" id="group_<?=$GroupID?>">
				<td class="center">
					<div title="View" id="showimg_<?=$GroupID?>" class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
						<a href="#" class="show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event)" title="Collapse this group"></a>
					</div>
				</td>
				<td class="center">
					<div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>"></div>
				</td>
				<td colspan="5">
					<strong><?=$DisplayName?></strong>
					<?=$TorrentTags?>
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
			
			if (in_array($TorrentID, $TokenTorrents) && empty($Torrent['FreeTorrent'])) {
				$Torrent['PersonalFL'] = 1;
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
	<tr class="group_torrent groupid_<?=$GroupID?> edition<? if(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; } ?>">
		<td colspan="7" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition">&minus;</a> <?=$RemasterName?></strong></td>
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
	<tr class="group_torrent groupid_<?=$GroupID?> edition<? if (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; }?>">
		<td colspan="7" class="edition_info"><strong><a href="#" onclick="toggle_edition(<?=$GroupID?>, <?=$EditionID?>, this, event)" title="Collapse this edition">&minus;</a> <?=$MasterName?></strong></td>
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
<tr class="group_torrent groupid_<?=$GroupID?> edition_<?=$EditionID?><? if(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; } ?>">
		<td colspan="3">
			<span>
				[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
<?			if (($LoggedUser['FLTokens'] > 0) && ($Torrent['Size'] < 1073741824) 
				&& !in_array($TorrentID, $TokenTorrents) && empty($Torrent['FreeTorrent']) && ($LoggedUser['CanLeech'] == '1')) { ?>
				| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&usetoken=1" title="Use a FL Token" onClick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?			} ?>
				| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a> ]
			</span>
			&nbsp;&nbsp;&raquo;&nbsp; <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=torrent_info($Torrent)?></a>
		</td>
		<td class="nobr"><?=get_size($Torrent['Size'])?></td>
		<td><?=number_format($Torrent['Snatched'])?></td>
		<td<?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format($Torrent['Seeders'])?></td>
		<td><?=number_format($Torrent['Leechers'])?></td>
	</tr>
<?
		}
	} else {
		// Viewing a type that does not require grouping
		
		list($TorrentID, $Torrent) = each($Torrents);
		
		$DisplayName = '<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
		
		if(!empty($Torrent['FreeTorrent'])) {
			$DisplayName .=' <strong>Freeleech!</strong>'; 
		} elseif(in_array($TorrentID, $TokenTorrents)) { 
			$DisplayName .= $AddExtra.'<strong>Personal Freeleech!</strong>';
		}
?>
	<tr class="torrent" id="group_<?=$GroupID?>">
		<td></td>
		<td class="center">
			<div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>">
			</div>
		</td>
		<td>
			<span>
				[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
<?		if (($LoggedUser['FLTokens'] > 0) && ($Torrent['Size'] < 1073741824) 
			&& !in_array($TorrentID, $TokenTorrents) && empty($Torrent['FreeTorrent']) && ($LoggedUser['CanLeech'] == '1')) { ?>
				| <a href="torrents.php?action=download&amp;id=<?=$TorrentID ?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>&usetoken=1" title="Use a FL Token" onClick="return confirm('Are you sure you want to use a freeleech token here?');">FL</a>
<?		} ?>						
				| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a>]
			</span>
			<strong><?=$DisplayName?></strong>
			<?=$TorrentTags?>
		</td>
		<td class="nobr"><?=get_size($Torrent['Size'])?></td>
		<td><?=number_format($Torrent['Snatched'])?></td>
		<td<?=($Torrent['Seeders']==0)?' class="r00"':''?>><?=number_format($Torrent['Seeders'])?></td>
		<td><?=number_format($Torrent['Leechers'])?></td>
	</tr>
<?
	}
	$TorrentTable.=ob_get_clean();
	
	// Album art
	
	ob_start();
	
	$DisplayName = '';
	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5])|| !empty($ExtendedArtists[6])) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName .= display_artists($ExtendedArtists, false);
	} elseif(count($GroupArtists)>0) {
		$DisplayName .= display_artists(array('1'=>$GroupArtists), false);
	}
	$DisplayName .= $GroupName;
	if($GroupYear>0) { $DisplayName = $DisplayName. ' ['. $GroupYear .']';}
?>
		<li class="image_group_<?=$GroupID?>">
			<a href="#group_<?=$GroupID?>">
<?	if($Image) { 
		if(check_perms('site_proxy_images')) {
			$Image = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?i='.urlencode($Image);
		}
?>
				<img src="<?=$Image?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?>" width="118" />
<?	} else { ?>
				<div style="width:107px;padding:5px"><?=$DisplayName?></div>
<?	} ?>
			</a>
		</li>
<?
	$Collage[]=ob_get_clean();
}

if(($MaxGroups>0 && $NumGroups>=$MaxGroups)  || ($MaxGroupsPerUser>0 && $NumGroupsByUser>=$MaxGroupsPerUser)) {
	$Locked = true;
}

// Silly hack for people who are on the old setting
$CollageCovers = isset($LoggedUser['CollageCovers'])?$LoggedUser['CollageCovers']:25*(abs($LoggedUser['HideCollage'] - 1));
$CollagePages = array();

// Pad it out
if ($NumGroups > $CollageCovers) {
	for ($i = $NumGroups + 1; $i <= ceil($NumGroups/$CollageCovers)*$CollageCovers; $i++) {
		$Collage[] = '<li></li>';
	}
}


for ($i=0; $i < $NumGroups/$CollageCovers; $i++) {
	$Groups = array_slice($Collage, $i*$CollageCovers, $CollageCovers);
	$CollagePage = '';
	foreach ($Groups as $Group) {
		$CollagePage .= $Group;
	}
	$CollagePages[] = $CollagePage;
}

show_header($Name,'browse,collage,bbcode');
?>
<div class="thin">
	<h2><?=$Name?></h2>
	<div class="linkbox">
		<a href="collages.php">[List of collages]</a> 
<? if (check_perms('site_collages_create')) { ?>
		<a href="collages.php?action=new">[New collage]</a> 
<? } ?>
		<br /><br />
<? if(check_perms('site_collages_subscribe')) { ?>
		<a href="#" onclick="CollageSubscribe(<?=$CollageID?>);return false;" id="subscribelink<?=$CollageID?>">[<?=(in_array($CollageID, $CollageSubscriptions) ? 'Unsubscribe' : 'Subscribe')?>]</a>
<? }
   if (check_perms('site_edit_wiki') && !$Locked) { ?>
		<a href="collages.php?action=edit&amp;collageid=<?=$CollageID?>">[Edit description]</a> 
<? }
	if(has_bookmarked('collage', $CollageID)) {
?>
		<a href="#" id="bookmarklink_collage_<?=$CollageID?>" onclick="Unbookmark('collage', <?=$CollageID?>,'[Bookmark]');return false;">[Remove bookmark]</a>
<?	} else { ?>
		<a href="#" id="bookmarklink_collage_<?=$CollageID?>" onclick="Bookmark('collage', <?=$CollageID?>,'[Remove bookmark]');return false;">[Bookmark]</a>
<?	}

if (check_perms('site_collages_manage') && !$Locked) { ?>
		<a href="collages.php?action=manage&amp;collageid=<?=$CollageID?>">[Manage torrents]</a> 
<? } ?>
	<a href="reports.php?action=report&amp;type=collage&amp;id=<?=$CollageID?>">[Report Collage]</a>
<? if (check_perms('site_collages_delete') || $CreatorID == $LoggedUser['ID']) { ?>
		<a href="collages.php?action=delete&amp;collageid=<?=$CollageID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" onclick="return confirm('Are you sure you want to delete this collage?.');">[Delete]</a> 
<? } ?>
	</div>
	<div class="sidebar">
		<div class="box">
			<div class="head"><strong>Category</strong></div>
			<div class="pad"><a href="collages.php?action=search&amp;cats[<?=(int)$CollageCategoryID?>]=1"><?=$CollageCats[(int)$CollageCategoryID]?></a></div>
		</div>
		<div class="box">
			<div class="head"><strong>Description</strong></div>
			<div class="pad"><?=$Text->full_format($Description)?></div>
		</div>
<?
if(check_perms('zip_downloader')){
	if(isset($LoggedUser['Collector'])) {
		list($ZIPList,$ZIPPrefs) = $LoggedUser['Collector'];
		$ZIPList = explode(':',$ZIPList);
	} else {
		$ZIPList = array('00','11');
		$ZIPPrefs = 1;
	}
?>
		<div class="box">
			<div class="head colhead_dark"><strong>Collector</strong></div>
			<div class="pad">
				<form action="collages.php" method="post">
				<input type="hidden" name="action" value="download" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="collageid" value="<?=$CollageID?>" /> 
				<ul id="list" class="nobullet">
<? foreach ($ZIPList as $ListItem) { ?>
					<li id="list<?=$ListItem?>">
						<input type="hidden" name="list[]" value="<?=$ListItem?>" /> 
						<span style="float:left;"><?=$ZIPOptions[$ListItem]['2']?></span>
						<a href="#" onclick="remove_selection('<?=$ListItem?>');return false;" style="float:right;">[X]</a>
						<br style="clear:all;" />
					</li>
<? } ?>
				</ul>
				<select id="formats" style="width:180px">
<?
$OpenGroup = false;
$LastGroupID=-1;

foreach ($ZIPOptions as $Option) {
	list($GroupID,$OptionID,$OptName) = $Option;

	if($GroupID!=$LastGroupID) {
		$LastGroupID=$GroupID;
		if($OpenGroup) { ?>
					</optgroup>
<?		} ?>
					<optgroup label="<?=$ZIPGroups[$GroupID]?>">
<?		$OpenGroup = true;
	}
?>
						<option id="opt<?=$GroupID.$OptionID?>" value="<?=$GroupID.$OptionID?>"<? if(in_array($GroupID.$OptionID,$ZIPList)){ echo ' disabled="disabled"'; }?>><?=$OptName?></option>
<?
}
?>
					</optgroup>
				</select>
				<button type="button" onclick="add_selection()">+</button>
				<select name="preference" style="width:210px">
					<option value="0"<? if($ZIPPrefs==0){ echo ' selected="selected"'; } ?>>Prefer Original</option>
					<option value="1"<? if($ZIPPrefs==1){ echo ' selected="selected"'; } ?>>Prefer Best Seeded</option>
					<option value="2"<? if($ZIPPrefs==2){ echo ' selected="selected"'; } ?>>Prefer Bonus Tracks</option>
				</select>
				<input type="submit" style="width:210px" value="Download" /> 
				</form>
			</div>
		</div>
<? } ?>
		<div class="box">
			<div class="head"><strong>Stats</strong></div>
			<ul class="stats nobullet">
				<li>Torrents: <?=$NumGroups?></li>
<? if(count($Artists) >0) { ?>	<li>Artists: <?=count($Artists)?></li> <? } ?>
				<li>Built by <?=count($Users)?> user<?=(count($Users)>1) ? 's' : ''?></li>
			</ul>
		</div>
		<div class="box">
			<div class="head"><strong>Top tags</strong></div>
			<div class="pad">
				<ol style="padding-left:5px;">
<?
uasort($Tags, 'compare');
$i = 0;
foreach ($Tags as $TagName => $Tag) {
	$i++;
	if($i>5) { break; }
?>
					<li><a href="collages.php?action=search&amp;tags=<?=$TagName?>"><?=$TagName?></a> (<?=$Tag['count']?>)</li>
<?
}
?>
				</ol>
			</div>
		</div>
<? if(!empty($Artists)) { ?>		
		<div class="box">
			<div class="head"><strong>Top artists</strong></div>
			<div class="pad">
				<ol style="padding-left:5px;">
<?
uasort($Artists, 'compare');
$i = 0;
foreach ($Artists as $ID => $Artist) {
	$i++;
	if($i>10) { break; }
?>
					<li><a href="artist.php?id=<?=$ID?>"><?=$Artist['name']?></a> (<?=$Artist['count']?>)</li>
<?
}
?>
				</ol>
			</div>
		</div>
<? } ?>
		<div class="box">
			<div class="head"><strong>Top contributors</strong></div>
			<div class="pad">
				<ol style="padding-left:5px;">
<?
uasort($Users, 'compare');
$i = 0;
foreach ($Users as $ID => $User) {
	$i++;
	if($i>5) { break; }
?>
					<li><?=format_username($ID, $User['name'])?> (<?=$User['count']?>)</li>
<?
}
?>
				</ol>
			
			</div>
		</div>
<? if(check_perms('site_collages_manage') && !$Locked) { ?>
		<div class="box">
			<div class="head"><strong>Add torrent</strong><span style="float: right"><a href="#" onClick="$('#addtorrent').toggle(); $('#batchadd').toggle(); this.innerHTML = (this.innerHTML == '[Batch Add]'?'[Individual Add]':'[Batch Add]'); return false;">[Batch Add]</a></span></div>
			<div class="pad" id="addtorrent">
				<form action="collages.php" method="post">
					<input type="hidden" name="action" value="add_torrent" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="collageid" value="<?=$CollageID?>" />
					<input type="text" size="20" name="url" />
					<input type="submit" value="+" />
					<br />
					<i>Enter the URL of a torrent on the site.</i>
				</form>
			</div>
			<div class="pad hidden" id="batchadd">
				<form action="collages.php" method="post">
					<input type="hidden" name="action" value="add_torrent_batch" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="collageid" value="<?=$CollageID?>" />
					<textarea name="urls" rows="5" cols="25" wrap="off"></textarea><br />
					<input type="submit" value="Add" />
					<br />
					<i>Enter the URLs of torrents on the site, one to a line.</i>
				</form>
			</div>
		</div>
<? } ?>
		<h3>Comments</h3>
<?
if(empty($CommentList)) {
	$DB->query("SELECT 
		cc.ID, 
		cc.Body, 
		cc.UserID, 
		um.Username,
		cc.Time 
		FROM collages_comments AS cc
		LEFT JOIN users_main AS um ON um.ID=cc.UserID
		WHERE CollageID='$CollageID' 
		ORDER BY ID DESC LIMIT 15");
	$CommentList = $DB->to_array();	
}
foreach ($CommentList as $Comment) {
	list($CommentID, $Body, $UserID, $Username, $CommentTime) = $Comment;
?>
		<div class="box">
			<div class="head">By <?=format_username($UserID, $Username) ?> <?=time_diff($CommentTime) ?> <a href="reports.php?action=report&amp;type=collages_comment&amp;id=<?=$CommentID?>">[Report Comment]</a></div>
			<div class="pad"><?=$Text->full_format($Body)?></div>
		</div>
<?
}
?>
		<div class="box pad">
			<a href="collages.php?action=comments&amp;collageid=<?=$CollageID?>">All comments</a>
		</div>
<?
if(!$LoggedUser['DisablePosting']) {
?>
		<div class="box">
			<div class="head"><strong>Add comment</strong></div>
			<form action="collages.php" method="post">
				<input type="hidden" name="action" value="add_comment" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="collageid" value="<?=$CollageID?>" />
				<div class="pad">
					<textarea name="body" cols="24" rows="5"></textarea>
					<br />
					<input type="submit" value="Add comment" />
				</div>
			</form>
		</div>
<?
}
?>
	</div>
	<div class="main_column">	
<?	
if($CollageCovers != 0) { ?>
		<div id="coverart" class="box">
			<div class="head" id="coverhead"><strong>Cover Art</strong></div>
			<ul class="collage_images" id="collage_page0">
<?
	$Page1 = array_slice($Collage, 0, $CollageCovers);
	foreach($Page1 as $Group) {
		echo $Group;
}?>
			</ul>
		</div>
<?		if ($NumGroups > $CollageCovers) { ?>
		<div class="linkbox pager" style="clear: left;" id="pageslinksdiv">
			<span id="firstpage" class="invisible"><a href="#" class="pageslink" onClick="collageShow.page(0, this); return false;">&lt;&lt; First</a> | </span>
			<span id="prevpage" class="invisible"><a href="#" id="prevpage"  class="pageslink" onClick="collageShow.prevPage(); return false;">&lt; Prev</a> | </span>
<?			for ($i=0; $i < $NumGroups/$CollageCovers; $i++) { ?>
			<span id="pagelink<?=$i?>" class="<?=(($i>4)?'hidden':'')?><?=(($i==0)?' selected':'')?>"><a href="#" class="pageslink" onClick="collageShow.page(<?=$i?>, this); return false;"><?=$CollageCovers*$i+1?>-<?=min($NumGroups,$CollageCovers*($i+1))?></a><?=($i != ceil($NumGroups/$CollageCovers)-1)?' | ':''?></span>
<?			} ?>
			<span id="nextbar" class="<?=($NumGroups/$CollageCovers > 5)?'hidden':''?>"> | </span>
			<span id="nextpage"><a href="#" class="pageslink" onClick="collageShow.nextPage(); return false;">Next &gt;</a></span>
			<span id="lastpage" class="<?=ceil($NumGroups/$CollageCovers)==2?'invisible':''?>"> | <a href="#" id="lastpage" class="pageslink" onClick="collageShow.page(<?=ceil($NumGroups/$CollageCovers)-1?>, this); return false;">Last &gt;&gt;</a></span>
		</div>
		<script type="text/javascript">
			collageShow.init(<?=json_encode($CollagePages)?>);
		</script>
<?		} 
} ?>
		<table class="torrent_table" id="discog_table">
			<tr class="colhead_dark">
				<td><!-- expand/collapse --></td>
				<td><!-- Category --></td>
				<td width="70%"><strong>Torrents</strong></td>
				<td>Size</td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/snatched.png" alt="Snatches" title="Snatches" /></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/seeders.png" alt="Seeders" title="Seeders" /></td>
				<td class="sign"><img src="static/styles/<?=$LoggedUser['StyleName'] ?>/images/leechers.png" alt="Leechers" title="Leechers" /></td>
			</tr>
<?=$TorrentTable?>
		</table>
	</div>
</div>
<?
show_footer();

$Cache->cache_value('collage_'.$CollageID, serialize(array(array($Name, $Description, $CollageDataList, $TorrentList, $CommentList, $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser))), 3600);
?>
