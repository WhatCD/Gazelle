<?
if(($GroupIDs = $Cache->get_value('better_single_groupids')) === false) {
	$DB->query("SELECT t.ID AS TorrentID,
	       	t.GroupID AS GroupID
		FROM xbt_files_users AS x
			JOIN torrents AS t ON t.ID=x.fid
		WHERE t.Format='FLAC'
		GROUP BY x.fid
			HAVING COUNT(x.uid) = 1
		ORDER BY t.LogScore DESC, t.Time ASC LIMIT 30");

	$GroupIDs = $DB->to_array('GroupID');
	$Cache->cache_value('better_single_groupids', $GroupIDs, 30*60);
}

$Results = Torrents::get_groups(array_keys($GroupIDs));

View::show_header('Single seeder FLACs');
?>
<div class="thin">
	<table width="100%" class="torrent_table">
		<tr class="colhead">
			<td>Torrent</td>
		</tr>
<?
$Results = $Results['matches'];
foreach ($Results as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TorrentTags, $ReleaseType, $GroupVanityHouse, $Torrents, $Artists, $ExtendedArtists, $GroupFlags) = array_values($Group);
	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName = Artists::display_artists($ExtendedArtists);
	} else {
		$DisplayName = '';
	}
	$FlacID = $GroupIDs[$GroupID]['TorrentID'];

	$DisplayName.='<a href="torrents.php?id='.$GroupID.'&amp;torrentid='.$FlacID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
	if($ReleaseType>0) { $DisplayName.=" [".$ReleaseTypes[$ReleaseType]."]"; }
	
	$ExtraInfo = Torrents::torrent_info($Torrents[$FlacID]);
	if($ExtraInfo) {
		$DisplayName.=' - '.$ExtraInfo;
	}

	$TagList=array();
	if($TorrentTags!='') {
		$TorrentTags=explode(' ',$TorrentTags);
		foreach ($TorrentTags as $TagKey => $TagName) {
			$TagName = str_replace('_','.',$TagName);
			$TagList[]='<a href="torrents.php?searchtags='.$TagName.'">'.$TagName.'</a>';
		}
		$PrimaryTag = $TorrentTags[0];
		$TagList = implode(', ', $TagList);
		$TorrentTags='<br /><div class="tags">'.$TagList.'</div>';
	}
?>
		<tr class="torrent torrent_row<?=$Torrents[$FlacID]['IsSnatched'] ? ' snatched_torrent"' : ''?>">
			<td>
				<span class="torrent_links_block">
					[ <a href="torrents.php?action=download&amp;id=<?=$FlacID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&torrent_pass=<?=$LoggedUser['torrent_pass']?>">DL</a> ]
				</span>
				<?=$DisplayName?>
				<?=$TorrentTags?>
			</td>
		</tr>
<?	} ?>
	</table>
</div>
<?
View::show_footer();
?>
