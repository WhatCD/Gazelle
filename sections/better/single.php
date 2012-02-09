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

$Results = get_groups(array_keys($GroupIDs));

show_header('Single seeder FLACs');
?>
<div class="thin">
	<table width="100%">
		<tr class="colhead">
			<td>Torrent</td>
		</tr>
<?
$Results = $Results['matches'];
foreach ($Results as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TorrentTags, $ReleaseType, $GroupVanityHouse, $Torrents, $Artists) = array_values($Group);
	$FlacID = $GroupIDs[$GroupID]['TorrentID'];
	
	$DisplayName = '';
	if(count($Artists)>0) {
		$DisplayName = display_artists(array('1'=>$Artists));
	}
	$DisplayName.='<a href="torrents.php?id='.$GroupID.'&amp;torrentid='.$FlacID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
	if($ReleaseType>0) { $DisplayName.=" [".$ReleaseTypes[$ReleaseType]."]"; }
	
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
		<tr>
			<td>
				<?=$DisplayName?>	
				[<a href="torrents.php?action=download&amp;id=<?=$FlacID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&torrent_pass=<?=$LoggedUser['torrent_pass']?>">DL</a>]
				<?=$TorrentTags?>
			</td>
		</tr>
<?	} ?>
	</table>
</div>
<?
show_footer();
?>
