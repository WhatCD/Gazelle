<?
if (!empty($_GET['userid']) && is_number($_GET['userid'])) {
	if (check_perms('users_override_paranoia')) {
		$UserID = $_GET['userid'];
	} else {
		error(403);
	}
} else {
	$UserID = $LoggedUser['ID'];
}

$Encodings = array('V0 (VBR)', 'V2 (VBR)', '320');
$EncodingKeys = array_fill_keys($Encodings, true);

if (!empty($_GET['filter']) && $_GET['filter'] == 'seeding') {
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

$SnatchedTorrentIDs = array_fill_keys($DB->collect('fid'), true);
$SnatchedGroupIDs = array_unique($DB->collect('GroupID'));
if (count($SnatchedGroupIDs) > 1000) {
	shuffle($SnatchedGroupIDs);
	$SnatchedGroupIDs = array_slice($SnatchedGroupIDs, 0, 1000);
}

if (count($SnatchedGroupIDs) == 0) {
	error(($SeedingOnly ? "You aren't seeding any 100% FLACs!" : "You haven't snatched any 100% FLACs!"));
}
// Create hash table

$DB->query("CREATE TEMPORARY TABLE temp_sections_better_snatch
	SELECT t.GroupID,
	GROUP_CONCAT(t.Encoding SEPARATOR ' ') AS EncodingList,
	CRC32(CONCAT_WS(' ', Media, Remasteryear, Remastertitle,
		Remasterrecordlabel, Remastercataloguenumber)) AS RemIdent
	FROM torrents AS t
	WHERE t.GroupID IN(".implode(',',$SnatchedGroupIDs).")
	GROUP BY t.GroupID, RemIdent");

//$DB->query('SELECT * FROM t');

$DB->query("SELECT GroupID FROM temp_sections_better_snatch
		WHERE EncodingList NOT LIKE '%V0 (VBR)%' 
		OR EncodingList NOT LIKE '%V2 (VBR)%' 
		OR EncodingList NOT LIKE '%320%'");

$GroupIDs = array_fill_keys($DB->collect('GroupID'), true);

if (count($GroupIDs) == 0) {
	error('No results found');
}

$Groups = Torrents::get_groups(array_keys($GroupIDs));
$Groups = $Groups['matches'];

$TorrentGroups = array();
foreach ($Groups as $GroupID => $Group) {
	if (empty($Group['Torrents'])) {
		unset($Groups[$GroupID]);
		continue;
	}
	foreach ($Group['Torrents'] as $Torrent) {
		$TorRemIdent = "$Torrent[Media] $Torrent[RemasterYear] $Torrent[RemasterTitle] $Torrent[RemasterRecordLabel] $Torrent[RemasterCatalogueNumber]";
		if (!isset($TorrentGroups[$Group['ID']])) {
			$TorrentGroups[$Group['ID']] = array(
				$TorRemIdent => array(
					'FlacID' => 0,
					'Formats' => array(),
					'IsSnatched' => $Torrent['IsSnatched'],
					'Medium' => $Torrent['Media'],
					'RemasterTitle' => $Torrent['RemasterTitle'],
					'RemasterYear' => $Torrent['RemasterYear'],
					'RemasterRecordLabel' => $Torrent['RemasterRecordLabel'],
					'RemasterCatalogueNumber' => $Torrent['RemasterCatalogueNumber']
				)
			);
		} elseif (!isset($TorrentGroups[$Group['ID']][$TorRemIdent])) {
			$TorrentGroups[$Group['ID']][$TorRemIdent] = array(
				'FlacID' => 0,
				'Formats' => array(),
				'IsSnatched' => $Torrent['IsSnatched'],
				'Medium' => $Torrent['Media'],
				'RemasterTitle' => $Torrent['RemasterTitle'],
				'RemasterYear' => $Torrent['RemasterYear'],
				'RemasterRecordLabel' => $Torrent['RemasterRecordLabel'],
				'RemasterCatalogueNumber' => $Torrent['RemasterCatalogueNumber']
			);
		}
		if (isset($EncodingKeys[$Torrent['Encoding']])) {
			$TorrentGroups[$Group['ID']][$TorRemIdent]['Formats'][$Torrent['Encoding']] = true;
		} elseif (isset($SnatchedTorrentIDs[$Torrent['ID']])) {
			$TorrentGroups[$Group['ID']][$TorRemIdent]['FlacID'] = $Torrent['ID'];
		}
	}
}

View::show_header('Transcode Snatches');
?>
<div class="linkbox">
<? if ($SeedingOnly) { ?>
	<a href="better.php?method=snatch">Show all</a>
<? } else { ?>
	<a href="better.php?method=snatch&amp;filter=seeding">Just those currently seeding</a>
<? } ?>
</div>
<div class="thin">
	<table width="100%" class="torrent_table">
		<tr class="colhead">
			<td>Torrent</td>
			<td>V2</td>
			<td>V0</td>
			<td>320</td>
		</tr>
<?
foreach ($TorrentGroups as $GroupID => $Editions) {
	$GroupInfo = $Groups[$GroupID];
	$GroupYear = $GroupInfo['Year'];
	$ExtendedArtists = $GroupInfo['ExtendedArtists'];
	$GroupCatalogueNumber = $GroupInfo['CatalogueNumber'];
	$GroupName = $GroupInfo['Name'];
	$GroupRecordLabel = $GroupInfo['RecordLabel'];
	$ReleaseType = $GroupInfo['ReleaseType'];

	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$ArtistNames = Artists::display_artists($ExtendedArtists);
	} else {
		$ArtistNames = '';
	}

	$TagList = array();
	$TagList = explode(' ', str_replace('_', '.', $GroupInfo['TagList']));
	$TorrentTags = array();
	foreach ($TagList as $Tag) {
		$TorrentTags[] = '<a href="torrents.php?taglist='.$Tag.'">'.$Tag.'</a>';
	}
	$TorrentTags = implode(', ', $TorrentTags);
	foreach ($Editions as $RemIdent => $Edition) {
		if (!$Edition['FlacID'] || count($Edition['Formats']) == 3) {
			continue;
		}
		$DisplayName = $ArtistNames . '<a href="torrents.php?id='.$GroupID.'&amp;torrentid='.$Edition['FlacID'].'#torrent'.$Edition['FlacID'].'" title="View Torrent">'.$GroupName.'</a>';
		if($GroupYear > 0) {
			$DisplayName .= " [".$GroupYear."]";
		}
		if ($ReleaseType > 0) {
			$DisplayName .= " [".$ReleaseTypes[$ReleaseType]."]";
		}
		$DisplayName .= ' ['.$Edition['Medium'].']';

		$EditionInfo = array();
		if (!empty($Edition['RemasterYear'])) {
			$ExtraInfo = $Edition['RemasterYear'];
		} else {
			$ExtraInfo = '';
		}
		if (!empty($Edition['RemasterRecordLabel'])) {
			$EditionInfo[] = $Edition['RemasterRecordLabel'];
		}
		if (!empty($Edition['RemasterTitle'])) {
			$EditionInfo[] = $Edition['RemasterTitle'];
		}
		if (!empty($Edition['RemasterCatalogueNumber'])) {
			$EditionInfo[] = $Edition['RemasterCatalogueNumber'];
		}
		if (!empty($Edition['RemasterYear'])) {
			$ExtraInfo .= ' - ';
		}
		$ExtraInfo .= implode(' / ', $EditionInfo);
?>
		<tr class="torrent torrent_row<?=$Edition['IsSnatched'] ? ' snatched_torrent' : ''?>">
			<td>
				<span class="torrent_links_block">
					[ <a href="torrents.php?action=download&amp;id=<?=$Edition['FlacID']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download">DL</a> ]
				</span>
				<?=$DisplayName?>
				<div class="torrent_info"><?=$ExtraInfo?></div>
				<div class="tags"><?=$TorrentTags?></div>
			</td>
			<td><?=isset($Edition['Formats']['V2 (VBR)'])?'<strong class="important_text_alt">YES</strong>':'<strong class="important_text">NO</strong>'?></td>
			<td><?=isset($Edition['Formats']['V0 (VBR)'])?'<strong class="important_text_alt">YES</strong>':'<strong class="important_text">NO</strong>'?></td>
			<td><?=isset($Edition['Formats']['320'])?'<strong class="important_text_alt">YES</strong>':'<strong class="important_text">NO</strong>'?></td>
		</tr>
<?
	}
}
?>
	</table>
</div>
<?
View::show_footer();
?>
