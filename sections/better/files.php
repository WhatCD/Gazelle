<?php 

if(check_perms('admin_reports') && !empty($_GET['remove']) && is_number($_GET['remove'])) {
	$DB->query("DELETE FROM torrents_bad_files WHERE TorrentID = ".$_GET['remove']);
	$DB->query("SELECT GroupID FROM torrents WHERE ID = ".$_GET['remove']);
	list($GroupID) = $DB->next_record();
	$Cache->delete_value('torrents_details_'.$GroupID);
}


if(!empty($_GET['filter']) && $_GET['filter'] == "all") {
	$Join = "";
	$All = true;
} else {
	$Join = "JOIN xbt_snatched as x ON x.fid=tfi.TorrentID AND x.uid = ".$LoggedUser['ID'];
	$All = false;
}

View::show_header('Torrents with bad file names');
$DB->query("SELECT tfi.TorrentID, t.GroupID FROM torrents_bad_files AS tfi JOIN torrents AS t ON t.ID = tfi.TorrentID ".$Join." ORDER BY tfi.TimeAdded ASC");
$TorrentsInfo = $DB->to_array('TorrentID', MYSQLI_ASSOC);
foreach($TorrentsInfo as $Torrent) {
	$GroupIDs[] = $Torrent['GroupID'];
}
$Results = Torrents::get_groups($GroupIDs);
$Results = $Results['matches'];
?>
<div class="header">
<? if($All) { ?>
	<h2>All torrents trumpable for bad file names</h2>
<? } else { ?>
	<h2>Torrents trumpable for bad file names that you have snatched</h2>
<? } ?>

	<div class="linkbox">
<? if($All) { ?>
		<a href="better.php?method=files" class="brackets">Show only those you have snatched</a>
<? } else { ?>
		<a href="better.php?method=files&amp;filter=all" class="brackets">Show all</a>
<? } ?>
	</div>
</div>
<div class="thin box pad">
	<h3>There are <?=count($TorrentsInfo)?> torrents remaining</h3>
	<table class="torrent_table">
<?
foreach ($TorrentsInfo as $TorrentID => $Info) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TorrentTags, $ReleaseType, $GroupVanityHouse, $Torrents, $Artists, $ExtendedArtists, $GroupFlags) = array_values($Results[$Info['GroupID']]);
	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName = Artists::display_artists($ExtendedArtists);
	} else {
		$DisplayName = '';
	}
	$DisplayName.='<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
	if($ReleaseType>0) { $DisplayName.=" [".$ReleaseTypes[$ReleaseType]."]"; }
	
	$ExtraInfo = Torrents::torrent_info($Torrents[$TorrentID]);
	if($ExtraInfo) {
		$DisplayName.=' - '.$ExtraInfo;
	}
	
	$TagList=array();
	if($TorrentTags!='') {
		$TorrentTags=explode(' ',$TorrentTags);
		foreach ($TorrentTags as $TagKey => $TagName) {
			$TagName = str_replace('_','.',$TagName);
			$TagList[]='<a href="torrents.php?taglist='.$TagName.'">'.$TagName.'</a>';
		}
		$PrimaryTag = $TorrentTags[0];
		$TagList = implode(', ', $TagList);
		$TorrentTags='<br /><div class="tags">'.$TagList.'</div>';
	}
?>
		<tr class="torrent torrent_row<?=$GroupFlags['IsSnatched'] ? ' snatched_torrent"' : ''?>">
			<td>
				<span class="torrent_links_block">
					<a href="torrents.php?action=download&amp;id=<?=$TorrentID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" class="brackets" title="Download torrent">DL</a>
				</span>
				<?=$DisplayName?>
<?	if (check_perms('admin_reports')) { ?>
				<a href="better.php?method=files&amp;remove=<?=$TorrentID?>" class="brackets">X</a>
<? 	} ?>
				<?=$TorrentTags?>
			</td>
		</tr>
<? } ?>
	</table>
</div>
<?
View::show_footer();
?>
