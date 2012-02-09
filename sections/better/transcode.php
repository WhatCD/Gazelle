<?
if(!isset($_GET['type']) || !is_number($_GET['type']) || $_GET['type'] > 3) { error(0); }

$Options = array('v0','v2','320');

if ($_GET['type'] == 3) {
	$List = "!(v0 | v2 | 320)";
} else {
	$List = '!'.$Options[$_GET['type']];
	if($_GET['type'] == 0) {
		$_GET['type'] = '0';
	} else {
		$_GET['type'] = display_str($_GET['type']);
	}
}

$Query = '@format FLAC @encoding '.$List;

if(!empty($_GET['search'])) {
	$Query.=' @(groupname,artistname,yearfulltext) '.$SS->EscapeString($_GET['search']);
}

$SS->SetFilter('logscore', array(100));
$SS->SetSortMode(SPH_SORT_EXTENDED, "@random");
$SS->limit(0, TORRENTS_PER_PAGE);

$SS->set_index(SPHINX_INDEX.' delta');

$Results = $SS->search($Query, '', 0, array(), '', '');

if(count($Results) == 0) { error('No results found!'); }
/*
// If some were fetched from memcached, get their artists
if(!empty($Results['matches'])) { // Fetch the artists for groups
	$GroupIDs = array_keys($Results['matches']);
	$Artists = get_artists($GroupIDs);
	foreach($Artists as $GroupID=>$Data) {
		if(!empty($Data[1])) {
			$Results['matches'][$GroupID]['Artists']=$Data[1]; // Only use main artists
		}
		ksort($Results['matches'][$GroupID]);
	}
}
*/
 // These ones were not found in the cache, run SQL
if(!empty($Results['notfound'])) {
	$SQLResults = get_groups($Results['notfound']);
	
	if(is_array($SQLResults['notfound'])) { // Something wasn't found in the db, remove it from results
		reset($SQLResults['notfound']);
		foreach($SQLResults['notfound'] as $ID) {
			unset($SQLResults['matches'][$ID]);
			unset($Results['matches'][$ID]);
		}
	}
	
	// Merge SQL results with memcached results
	foreach($SQLResults['matches'] as $ID=>$SQLResult) {
		$Results['matches'][$ID] = array_merge($Results['matches'][$ID], $SQLResult);
		ksort($Results['matches'][$ID]);
	}
}

$Results = $Results['matches'];


show_header('Transcode Search');
?>
<br />
<div class="thin">
	<div>
		<form action="" method="get">
			<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
				<tr>
					<td class="label"><strong>Search:</strong></td>
					<td>
						<input type="hidden" name="method" value="transcode" />
						<input type="hidden" name="type" value="<?=$_GET['type']?>" />
						<input type="text" name="search" size="60" value="<?=(!empty($_GET['search']) ? display_str($_GET['search']) : '')?>" />
						&nbsp;
						<input type="submit" value="Search" />
					</td>
				</tr>
			</table>	
		</form>
	</div>
	<table width="100%">
		<tr class="colhead">
			<td>Torrent</td>
			<td>V2</td>
			<td>V0</td>
			<td>320</td>
		</tr>
<?
foreach($Results as $GroupID=>$Data) {
$Debug->log_var($Data);
	list($Artists, $GroupCatalogueNumber, $ExtendedArtists, $GroupID2, $GroupName, $GroupRecordLabel, $ReleaseType, $TorrentTags, $Torrents, $GroupVanityHouse, $GroupYear, $CategoryID, $FreeTorrent, $HasCue, $HasLog, $TotalLeechers, $LogScore, $ReleaseType, $ReleaseType, $TotalSeeders, $MaxSize, $TotalSnatched, $GroupTime) = array_values($Data);
	
	$DisplayName = '';
	if(count($Artists)>0) {
		$DisplayName = display_artists(array('1'=>$Artists));
	}
	$DisplayName.='<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
	if($GroupYear>0) { $DisplayName.=" [".$GroupYear."]"; }
	if($ReleaseType>0) { $DisplayName.=" [".$ReleaseTypes[$ReleaseType]."]"; }
	$MissingEncodings = array('V0 (VBR)'=>1, 'V2 (VBR)'=>1, '320'=>1);
	$FlacID = 0;
	
	foreach($Torrents as $Torrent) {
		if(!empty($MissingEncodings[$Torrent['Encoding']])) {
			$MissingEncodings[$Torrent['Encoding']] = 0;
		} elseif($Torrent['Format'] == 'FLAC' && $FlacID == 0) {
			$FlacID = $Torrent['ID'];
		}
	}
	
	if($_GET['type'] == '3' && in_array(0, $MissingEncodings)) {
		continue;
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
				[<a href="torrents.php?action=download&amp;id=<?=$FlacID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>">DL</a>]
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
