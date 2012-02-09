<?

if(!empty($_GET['userid']) && is_number($_GET['userid'])) {
	if (check_perms('users_override_paranoia')) {
		$UserID = $_GET['userid'];
	} else {
		error(403);
	}
} else {
	$UserID = $LoggedUser['ID'];
}

if(!empty($_GET['filter']) && $_GET['filter'] == 'seeding') {
	$SeedingOnly = true;
} else {
	$SeedingOnly = false;
}

// Get list of FLAC snatches
$DB->query("SELECT t.GroupID, x.fid
	FROM ".($SeedingOnly ? 'xbt_files_users' : 'xbt_snatched')." AS x
		JOIN torrents AS t ON t.ID=x.fid
	WHERE t.Format='FLAC' 
		AND ((t.LogScore = '100' AND t.Media = 'CD')
			OR t.Media = 'Vinyl')
		AND x.uid='$UserID'");

$SnatchedGroupIDs = $DB->collect('GroupID');
$Snatches = $DB->to_array('GroupID');

if(count($SnatchedGroupIDs) == 0) { error(($SeedingOnly ? "You aren't seeding any 100% FLACs!" : "You haven't snatched any 100% FLACs!")); }
// Create hash table

$DB->query("CREATE TEMPORARY TABLE temp_sections_better_snatch
	SELECT t.GroupID,
	GROUP_CONCAT(t.Encoding SEPARATOR ' ') AS EncodingList
	FROM torrents AS t
	WHERE t.GroupID IN(".implode(',',$SnatchedGroupIDs).")
	GROUP BY t.GroupID");

//$DB->query('SELECT * FROM t');

$DB->query("SELECT GroupID FROM temp_sections_better_snatch
		WHERE EncodingList NOT LIKE '%V0 (VBR)%' 
		OR EncodingList NOT LIKE '%V2 (VBR)%' 
		OR EncodingList NOT LIKE '%320%'");

$GroupIDs = $DB->collect('GroupID');

if(count($GroupIDs) == 0) { error('No results found'); }

$Results = get_groups($GroupIDs);

show_header('Transcode Snatches');
?>
<div class="linkbox">
<? if($SeedingOnly) { ?>
	<a href="better.php?method=snatch">Show all</a>
<? } else { ?>
	<a href="better.php?method=snatch&amp;filter=seeding">Just those currently seeding</a>
<? } ?>
</div>
<div class="thin">
	<table width="100%">
		<tr class="colhead">
			<td>Torrent</td>
			<td>V2</td>
			<td>V0</td>
			<td>320</td>
		</tr>
<?
$Results = $Results['matches'];
foreach ($Results as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TorrentTags, $ReleaseType, $GroupVanityHouse, $Torrents, $Artists) = array_values($Group);
	$FlacID = $Snatches[$GroupID]['fid'];
	
	$DisplayName = '';
	if(count($Artists)>0) {
		$DisplayName = display_artists(array('1'=>$Artists));
	}
	$DisplayName.='<a href="torrents.php?id='.$GroupID.'&amp;torrentid='.$FlacID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
	if($ReleaseType>0) { $DisplayName.=" [".$ReleaseTypes[$ReleaseType]."]"; }
	
	$MissingEncodings = array('V0 (VBR)'=>1, 'V2 (VBR)'=>1, '320'=>1);
	
	foreach($Torrents as $Torrent) {
		if(!empty($MissingEncodings[$Torrent['Encoding']])) {
			$MissingEncodings[$Torrent['Encoding']] = 0;
		}
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
		<tr>
			<td>
				<?=$DisplayName?>	
				[<a href="torrents.php?action=download&amp;id=<?=$FlacID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&torrent_pass=<?=$LoggedUser['torrent_pass']?>">DL</a>]
				<?=$TorrentTags?>
			</td>
			<td><strong><?=($MissingEncodings['V2 (VBR)'] == 0)?'<span style="color: green;">YES</span>':'<span style="color: red;">NO</span>'?></strong></td>
			<td><strong><?=($MissingEncodings['V0 (VBR)'] == 0)?'<span style="color: green;">YES</span>':'<span style="color: red;">NO</span>'?></strong></td>
			<td><strong><?=($MissingEncodings['320'] == 0)?'<span style="color: green;">YES</span>':'<span style="color: red;">NO</span>'?></strong></td>
		</tr>
<?	} ?>
	</table>
</div>
<?
show_footer();
?>
