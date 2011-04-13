<?
ini_set('memory_limit', -1);
//~~~~~~~~~~~ Main bookmarks page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

function compare($X, $Y){
	return($Y['count'] - $X['count']);
}

if(!empty($_GET['userid'])) {
	if(!check_perms('users_mod')) {
		error(403);
	}
	$UserID = $_GET['userid'];
	$Sneaky = true;
	if(!is_number($UserID)) { error(404); }
} else {
	$UserID = $LoggedUser['ID'];
}

$Data = $Cache->get_value('bookmarks_'.$UserID);

if($Data) {
	$Data = unserialize($Data);
	list($K, list($Username, $TorrentList, $CollageDataList)) = each($Data);
} else {
	$DB->query("SELECT Username FROM users_main WHERE ID='$UserID'");
	list($Username) = $DB->next_record();
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

if(empty($TorrentList)) {
	error("You do not have any bookmarks yet!");
}

show_header($Username."'s Bookmarks",'browse');

// Loop through the result set, building up $Collage and $TorrentTable
// Then we print them.
$Collage = array();
$TorrentTable = '';

$NumGroups = 0;
$Artists = array();
$Tags = array();

foreach ($TorrentList as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TagList, $ReleaseType, $Torrents, $GroupArtists) = array_values($Group);
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

	$DisplayName = '';
	if(count($GroupArtists)>0) {
		$DisplayName = display_artists(array('1'=>$GroupArtists));
	}
	$DisplayName .= '<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName = $DisplayName. ' ['. $GroupYear .']';}
	
	// Start an output buffer, so we can store this output in $TorrentTable
	ob_start(); 
	if(count($Torrents)>1 || $GroupCategoryID==1) {
			 // Grouped torrents
?>
			<tr class="group discog" id="group_<?=$GroupID?>">
				<td class="center">
					<div title="View" id="showimg_<?=$GroupID?>" class="hide_torrents">
						<a href="#" class="show_torrents_link" onclick="ToggleGroup(<?=$GroupID?>); return false;"></a>
					</div>
				</td>
				<td class="center">
					<div title="<?=ucfirst(str_replace('_',' ',$PrimaryTag))?>" class="cats_<?=strtolower(str_replace(array('-',' '),array('',''),$Categories[$GroupCategoryID-1]))?> tags_<?=str_replace('.','_',$PrimaryTag)?>"></div>
				</td>
				<td colspan="5">
					<span style="float:left;"><strong><?=$DisplayName?></strong></span>
<? if(!isset($Sneaky)){ ?>					
					<span style="float:right;"><a href="#group_<?=$GroupID?>" onclick="unbookmark(<?=$GroupID?>);return false;">Remove Bookmark</a></span>
<? } ?>
					<br /><span style="float:left;"><?=$TorrentTags?></span>
					<span style="float:right;"><?=time_diff($AddedTime);?></span>
				</td>
			</tr>
<?
		$LastRemasterYear = '-';
		$LastRemasterTitle = '';
		$LastRemasterRecordLabel = '';
		$LastRemasterCatalogueNumber = '';
		
		foreach ($Torrents as $TorrentID => $Torrent) {
			
			if($Torrent['RemasterTitle'] != $LastRemasterTitle || $Torrent['RemasterYear'] != $LastRemasterYear ||
			$Torrent['RemasterRecordLabel'] != $LastRemasterRecordLabel || $Torrent['RemasterCatalogueNumber'] != $LastRemasterCatalogueNumber) {
				if($Torrent['RemasterTitle']  || $Torrent['RemasterYear'] || $Torrent['RemasterRecordLabel'] || $Torrent['RemasterCatalogueNumber']) {
					
					$RemasterName = $Torrent['RemasterYear'];
					$AddExtra = " - ";
					if($Torrent['RemasterRecordLabel']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterRecordLabel']); $AddExtra=' / '; }
					if($Torrent['RemasterCatalogueNumber']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterCatalogueNumber']); $AddExtra=' / '; }
					if($Torrent['RemasterTitle']) { $RemasterName .= $AddExtra.display_str($Torrent['RemasterTitle']); $AddExtra=' / '; }
					
?>
	<tr class="group_torrent groupid_<?=$GroupID?><? if(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; } ?>">
		<td colspan="7" class="edition_info"><strong><?=$RemasterName?></strong></td>
	</tr>
<?
				} else {
					$MasterName = "Original Release";
					$AddExtra = " / ";
					if($GroupRecordLabel) { $MasterName .= $AddExtra.$GroupRecordLabel; $AddExtra=' / '; }
					if($GroupCatalogueNumber) { $MasterName .= $AddExtra.$GroupCatalogueNumber; $AddExtra=' / '; }
?>
	<tr class="group_torrent groupid_<?=$GroupID?><? if (!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; }?>">
		<td colspan="7" class="edition_info"><strong><?=$MasterName?></strong></td>
	</tr>
<?
				}
			}
			$LastRemasterTitle = $Torrent['RemasterTitle'];
			$LastRemasterYear = $Torrent['RemasterYear'];
			$LastRemasterRecordLabel = $Torrent['RemasterRecordLabel'];
			$LastRemasterCatalogueNumber = $Torrent['RemasterCatalogueNumber'];
?>
<tr class="group_torrent groupid_<?=$GroupID?><? if(!empty($LoggedUser['TorrentGrouping']) && $LoggedUser['TorrentGrouping']==1) { echo ' hidden'; } ?>">
		<td colspan="3">
			<span>
				[<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>]
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
				[<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a>
				| <a href="reportsv2.php?action=report&amp;id=<?=$TorrentID?>" title="Report">RP</a>]
			</span>
			<strong><?=$DisplayName?></strong>
			<?=$TorrentTags?>
			<? if(empty($Sneaky)){ ?>
			<span style="float:left;"><a href="#group_<?=$GroupID?>" onclick="unbookmark(<?=$GroupID?>);return false;">Remove Bookmark</a></span>
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
	if(!empty($GroupArtists)) {
		$DisplayName.= display_artists(array('1'=>$GroupArtists), false);
	}
	$DisplayName .= $GroupName;
	if($GroupYear>0) { $DisplayName = $DisplayName. ' ['. $GroupYear .']';}
?>
		<li class="image_group_<?=$GroupID?>">
			<a href="#group_<?=$GroupID?>">
<?	if($Image) { ?>
				<img src="<?=$Image?>" alt="<?=$DisplayName?>" title="<?=$DisplayName?>" width="117" />
<?	} else { ?>
				<div style="width:107px;padding:5px"><?=$DisplayName?></div>
<?	} ?>
			</a>
		</li>
<?
	$Collage[]=ob_get_clean();
	
}

?>
<div class="thin">
	<h2><?=$Username?>'s Bookmarks</h2>
	<div class="linkbox">
		<a href="bookmarks.php?action=remove_snatched&amp;auth=<?=$LoggedUser['AuthKey']?>" onclick="return confirm('Are you sure you want to remove the bookmarks for all items you\'ve snatched?');">[Remove Snatched]</a>
	</div>
	<div class="sidebar">
<?

?>
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
<? if(empty($LoggedUser['HideCollage'])) { ?>
		<ul class="collage_images">
<? foreach($Collage as $Group) { ?>
			<?=$Group?>
<? } ?>
		</ul>
<? } ?>
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
$Cache->cache_value('bookmarks_'.$UserID, serialize(array(array($Username, $TorrentList, $CollageDataList))), 3600);
?>
