<?
set_time_limit(0);
//~~~~~~~~~~~ Main bookmarks page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

function compare($X, $Y){
	return($Y['count'] - $X['count']);
}

if(!empty($_GET['userid'])) {
	if(!check_perms('users_override_paranoia')) {
		error(403);
	}
	$UserID = $_GET['userid'];
	if(!is_number($UserID)) { error(404); }
	$DB->query("SELECT Username FROM users_main WHERE ID='$UserID'");
	list($Username) = $DB->next_record();
} else {
	$UserID = $LoggedUser['ID'];
}

$Sneaky = ($UserID != $LoggedUser['ID']);

$Data = $Cache->get_value('bookmarks_torrent_'.$UserID.'_full');

if($Data) {
	$Data = unserialize($Data);
	list($K, list($TorrentList, $CollageDataList)) = each($Data);
} else {
	// Build the data for the collage and the torrent list
	$DB->query("SELECT 
		bt.GroupID, 
		tg.WikiImage,
		tg.CategoryID,
		bt.Time
		FROM bookmarks_torrents AS bt
		JOIN torrents_group AS tg ON tg.ID=bt.GroupID
		WHERE bt.UserID='$UserID'
		ORDER BY bt.Time");
	
	$GroupIDs = $DB->collect('GroupID');
	$CollageDataList=$DB->to_array('GroupID', MYSQLI_ASSOC);
	if(count($GroupIDs)>0) {
		$TorrentList = get_groups($GroupIDs);
		$TorrentList = $TorrentList['matches'];
	} else {
		$TorrentList = array();
	}
}

$TokenTorrents = $Cache->get_value('users_tokens_'.$UserID);
if (empty($TokenTorrents)) {
	$DB->query("SELECT TorrentID FROM users_freeleeches WHERE UserID=$UserID AND Expired=FALSE");
	$TokenTorrents = $DB->collect('TorrentID');
	$Cache->cache_value('users_tokens_'.$UserID, $TokenTorrents);
}

$Title = ($Sneaky)?"$Username's bookmarked torrents":'Your bookmarked torrents';

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = array();
$TorrentTable = '';

$NumGroups = 0;
$Artists = array();
$Tags = array();

foreach ($TorrentList as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TagList, $ReleaseType, $GroupVanityHouse, $Torrents, $GroupArtists, $ExtendedArtists) = array_values($Group);
	list($GroupID2, $Image, $GroupCategoryID, $AddedTime) = array_values($CollageDataList[$GroupID]);
	
	// Handle stats and stuff
	$NumGroups++;
	
	if($GroupArtists) {
		foreach($GroupArtists as $Artist) {
			if(!isset($Artists[$Artist['id']])) {
				$Artists[$Artist['id']] = array('name'=>$Artist['name'], 'count'=>1);
			} else {
				$Artists[$Artist['id']]['count']++;
			}
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

	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
			unset($ExtendedArtists[2]);
			unset($ExtendedArtists[3]);
			$DisplayName = display_artists($ExtendedArtists);
	} elseif(count($GroupArtists)>0) {
			$DisplayName = display_artists(array('1'=>$GroupArtists));
	} else {
		$DisplayName = '';
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
					<span style="float:left;"><strong><?=$DisplayName?></strong></span>
					<span style="float:right;text-align:right">
<? if(!$Sneaky){ ?>
						<a href="#group_<?=$GroupID?>" onclick="Unbookmark('torrent', <?=$GroupID?>, '');return false;">Remove Bookmark</a>
						<br />
<? } ?>
						<?=time_diff($AddedTime);?>
					</span>
					<br /><span style="float:left;"><?=$TorrentTags?></span>
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
			<span>[ <a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
<?			if (($LoggedUser['FLTokens'] > 0)  && ($Torrent['Size'] < 1073741824) 
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
			$DisplayName .= '<strong>Personal Freeleech!</strong>';
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
				| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a> ]
			</span>
			<strong><?=$DisplayName?></strong>
			<?=$TorrentTags?>
<? if(!$Sneaky){ ?>
			<span style="float:left;"><a href="#group_<?=$GroupID?>" onclick="Unbookmark('torrent', <?=$GroupID?>, '');return false;">Remove Bookmark</a></span>
<? } ?>
			<span style="float:right;"><?=time_diff($AddedTime);?></span>

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
			<a href="#group_<?=$GroupID?>" class="bookmark_<?=$GroupID?>">
<?	if($Image) { 
		if(check_perms('site_proxy_images')) {
			$Image = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?i='.urlencode($Image);
		}
?>
				<img src="<?=$Image?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?>" width="117" />
<?	} else { ?>
				<div style="width:107px;padding:5px"><?=$DisplayName?></div>
<?	} ?>
			</a>
		</li>
<?
	$Collage[]=ob_get_clean();
	
}

$CollageCovers = isset($LoggedUser['CollageCovers'])?$LoggedUser['CollageCovers']:25;
$CollagePages = array();
for ($i=0; $i < $NumGroups/$CollageCovers; $i++) {
	$Groups = array_slice($Collage, $i*$CollageCovers, $CollageCovers);
	$CollagePage = '';
	foreach ($Groups as $Group) {
		$CollagePage .= $Group;
	}
	$CollagePages[] = $CollagePage;
}

show_header($Title, 'browse,collage');
?>
<div class="thin">
	<h2><? if (!$Sneaky) { ?><a href="feeds.php?feed=torrents_bookmarks_t_<?=$LoggedUser['torrent_pass']?>&amp;user=<?=$LoggedUser['ID']?>&amp;auth=<?=$LoggedUser['RSS_Auth']?>&amp;passkey=<?=$LoggedUser['torrent_pass']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;name=<?=urlencode(SITE_NAME.': Bookmarked Torrents')?>"><img src="<?=STATIC_SERVER?>/common/symbols/rss.png" alt="RSS feed" /></a>&nbsp;<? } ?><?=$Title?></h2>
	<div class="linkbox">
		<a href="bookmarks.php?type=torrents">[Torrents]</a>
		<a href="bookmarks.php?type=artists">[Artists]</a>
		<a href="bookmarks.php?type=collages">[Collages]</a>
		<a href="bookmarks.php?type=requests">[Requests]</a>
<? if (count($TorrentList) > 0) { ?>
		<br /><br />
		<a href="bookmarks.php?action=remove_snatched&amp;auth=<?=$LoggedUser['AuthKey']?>" onclick="return confirm('Are you sure you want to remove the bookmarks for all items you\'ve snatched?');">[Remove Snatched]</a>
<? } ?>
	</div>
<? if (count($TorrentList) == 0) { ?>
	<div class="box pad" align="center">
		<h2>You have not bookmarked any torrents.</h2>
	</div>
</div><!--content-->
<?
	show_footer();
	die();
} ?>
	<div class="sidebar">
		<div class="box">
			<div class="head"><strong>Stats</strong></div>
			<ul class="stats nobullet">
				<li>Torrents: <?=$NumGroups?></li>
<? if(count($Artists) >0) { ?>	<li>Artists: <?=count($Artists)?></li> <? } ?>
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
					<li><a href="torrents.php?taglist=<?=$TagName?>"><?=$TagName?></a> (<?=$Tag['count']?>)</li>
<?
}
?>
				</ol>
			</div>
		</div>
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
		<br />
		<table class="torrent_table" id="torrent_table">
			<tr class="colhead_dark">
				<td><!-- expand/collapse --></td>
				<td><!-- Category --></td>
				<td width="70%"><strong>Torrents</strong> (<a href="#" onclick="return false;">View</a>)</td>
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
$Cache->cache_value('bookmarks_torrent_'.$UserID.'_full', serialize(array(array($TorrentList, $CollageDataList))), 3600);
?>
