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

// Get list of FLAC snatches
$DB->query("SELECT t.GroupID, xs.fid
	FROM xbt_snatched AS xs
	JOIN torrents AS t ON t.ID=xs.fid
	WHERE 
	t.Format='FLAC' 
	AND ((t.LogScore = '100' AND t.Media = 'CD')
		OR t.Media = 'Vinyl')
	AND xs.uid='$UserID'");

$SnatchedGroupIDs = $DB->collect('GroupID');
$Snatches = $DB->to_array('GroupID');

if(count($SnatchedGroupIDs) == 0) { error('You haven\'t snatched any 100% flacs!'); }
// Create hash table

$DB->query("CREATE TEMPORARY TABLE t 
	SELECT t.GroupID,
	GROUP_CONCAT(t.Encoding SEPARATOR ' ') AS EncodingList
	FROM torrents AS t
	WHERE t.GroupID IN(".implode(',',$SnatchedGroupIDs).")
	GROUP BY t.GroupID");

//$DB->query('SELECT * FROM t');

$DB->query("SELECT GroupID FROM t 
		WHERE EncodingList NOT LIKE '%V0 (VBR)%' 
		OR EncodingList NOT LIKE '%V2 (VBR)%' 
		OR EncodingList NOT LIKE '%320%'");

$GroupIDs = $DB->collect('GroupID');

if(count($GroupIDs) == 0) { error('No results found'); }

$Results = get_groups($GroupIDs);

show_header('Transcode Snatches');
?>
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
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TagList, $ReleaseType, $Torrents, $Artists) = array_values($Group);
	$FlacID = $Snatches[$GroupID]['fid'];
	
	$DisplayName = '';
	if(count($Artists)>0) {
		$DisplayName = display_artists(array('1'=>$Artists));
	}
	$DisplayName.='<a href="torrents.php?id='.$GroupID.'&amp;torrentid='.$FlacID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
	
	$MissingEncodings = array('V0 (VBR)'=>1, 'V2 (VBR)'=>1, '320'=>1);
	
	foreach($Torrents as $Torrent) {
		if(!empty($MissingEncodings[$Torrent['Encoding']])) {
			$MissingEncodings[$Torrent['Encoding']] = 0;
		}
	}
	
?>
		<tr>
			<td>
				<?=$DisplayName?>	
				[<a href="torrents.php?action=download&amp;id=<?=$FlacID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&torrent_pass=<?=$LoggedUser['torrent_pass']?>">DL</a>]</td>
				<td><strong><?=($MissingEncodings['V2 (VBR)'] == 0)?'<span style="color: green;">YES</span>':'<span style="color: red;">NO</span>'?></strong></td>
				<td><strong><?=($MissingEncodings['V0 (VBR)'] == 0)?'<span style="color: green;">YES</span>':'<span style="color: red;">NO</span>'?></strong></td>
				<td><strong><?=($MissingEncodings['320'] == 0)?'<span style="color: green;">YES</span>':'<span style="color: red;">NO</span>'?></strong>
			</td>
		</tr>
<?	} ?>
	</table>
</div>
<?
show_footer();
?>
