<?
/**
 * New transcode module:
 * $_GET['filter'] determines which torrents should be shown and can be empty/all (default), uploaded, snatched or seeding
 * $_GET['target'] further filters which transcodes one would like to do and can be empty/any (default), v0, v2, 320 or all
 *	Here, 'any' means that at least one of the formats V0, V2 and 320 is missing and 'all' means that all of them are missing.
 *	'v0', etc. mean that this specific format is missing (but others might be present).
 *
 * Furthermore, there's $_GET['userid'] which allows to see the page as a different user would see it (specifically relevant for uploaded/snatched/seeding).
 */

if (!empty($_GET['userid']) && is_number($_GET['userid'])) {
	if (check_perms('users_override_paranoia')) {
		$UserID = $_GET['userid'];
	} else {
		error(403);
	}
} else {
	$UserID = $LoggedUser['ID'];
}

if (empty($_GET['filter']) || !in_array($_GET['filter'], array('uploaded', 'seeding', 'snatched'))) {
	$_GET['filter'] = 'all';
}
if (empty($_GET['target']) || !in_array($_GET['target'], array('v0', 'v2', '320', 'all'))) {
	$_GET['target'] = 'any';
}
$Encodings = array('v0' => 'V0 (VBR)', 'v2' => 'V2 (VBR)', '320' => '320');

function transcode_init_sphql() {
	// Initializes a basic SphinxqlQuery object
	$SphQL = new SphinxqlQuery();
	$SphQL->select('groupid')
		->from('better_transcode')
		->where('logscore', 100)
		->where_match('FLAC', 'format')
		->order_by('RAND()')
		->limit(0, TORRENTS_PER_PAGE, TORRENTS_PER_PAGE);
	if (in_array($_GET['target'], array('v0', 'v2', '320'))) {
		// V0/V2/320 is missing
		$SphQL->where_match('!'.$_GET['target'], 'encoding', false);
	} elseif ($_GET['target'] === 'all') {
		// all transcodes are missing
		$SphQL->where_match('!(v0 | v2 | 320)', 'encoding', false);
	} else {
		// any transcode is missing
		$SphQL->where_match('!(v0 v2 320)', 'encoding', false);
	}
	if (!empty($_GET['search'])) {
		$SphQL->where_match($_GET['search'], '(groupname,artistname,year,taglist)');
	}
	return $SphQL;
}

function transcode_parse_groups($Groups) {
	$TorrentGroups = array();
	foreach ($Groups as $GroupID => $Group) {
		if (empty($Group['Torrents'])) {
			continue;
		}
		foreach ($Group['Torrents'] as $TorrentID => $Torrent) {
			$RemIdent = "$Torrent[Media] $Torrent[RemasterYear] $Torrent[RemasterTitle] $Torrent[RemasterRecordLabel] $Torrent[RemasterCatalogueNumber]";
			if (!isset($TorrentGroups[$GroupID])) {
				$TorrentGroups[$GroupID] = array(
					'Year' => $Group['Year'],
					'ExtendedArtists' => $Group['ExtendedArtists'],
					'Name' => $Group['Name'],
					'ReleaseType' => $Group['ReleaseType'],
					'TagList' => $Group['TagList'],
					'Editions' => array()
				);
			}
			if (!isset($TorrentGroups[$GroupID]['Editions'][$RemIdent])) {
				if ($Torrent['Remastered'] && $Torrent['RemasterYear'] != 0) {
					$EditionName = $Torrent['RemasterYear'];
					$AddExtra = ' - ';
					if ($Torrent['RemasterRecordLabel']) {
						$EditionName .= $AddExtra.display_str($Torrent['RemasterRecordLabel']);
						$AddExtra = ' / ';
					}
					if ($Torrent['RemasterCatalogueNumber']) {
						$EditionName .= $AddExtra.display_str($Torrent['RemasterCatalogueNumber']);
						$AddExtra = ' / ';
					}
					if ($Torrent['RemasterTitle']) {
						$EditionName .= $AddExtra.display_str($Torrent['RemasterTitle']);
						$AddExtra = ' / ';
					}
					$EditionName .= $AddExtra.display_str($Torrent['Media']);
				} else {
					$AddExtra = ' / ';
					if (!$Torrent['Remastered']) {
						$EditionName = 'Original Release';
						if ($Group['RecordLabel']) {
							$EditionName .= $AddExtra.$Group['RecordLabel'];
							$AddExtra = ' / ';
						}
						if ($Group['CatalogueNumber']) {
							$EditionName .= $AddExtra.$Group['CatalogueNumber'];
							$AddExtra = ' / ';
						}
					} else {
						$EditionName = 'Unknown Release(s)';
					}
					$EditionName .= $AddExtra.display_str($Torrent['Media']);
				}
				$TorrentGroups[$GroupID]['Editions'][$RemIdent] = array(
					'FlacIDs' => array(),
					'MP3s' => array(),
					'Media' => $Torrent['Media'],
					'EditionName' => $EditionName,
					'FLACIsSnatched' => false
				);
			}

			if ($Torrent['Format'] == 'MP3') {
				$TorrentGroups[$GroupID]['Editions'][$RemIdent]['MP3s'][$Torrent['Encoding']] = true;
			} elseif ($Torrent['Format'] == 'FLAC' && ($Torrent['LogScore'] == 100 || $Torrent['Media'] != 'CD')
					&& !isset($TorrentGroups[$GroupID]['Editions'][$RemIdent]['FlacIDs'][$TorrentID])) {
				$TorrentGroups[$GroupID]['Editions'][$RemIdent]['FlacIDs'][$TorrentID] = true;
				$TorrentGroups[$GroupID]['Editions'][$RemIdent]['FLACIsSnatched'] = $TorrentGroups[$GroupID]['Editions'][$RemIdent]['FLACIsSnatched'] || $Torrent['IsSnatched'];
			}
		}
	}
	return $TorrentGroups;
}

$Groups = array();
$ResultCount = 0;
if (in_array($_GET['filter'], array('all', 'uploaded'))) {
	$SphQL = transcode_init_sphql();
	if ($_GET['filter'] === 'uploaded') {
		$SphQL->where('uploader', $UserID);
	}

	$SphQLResult = $SphQL->query();
	$ResultCount = $SphQLResult->get_meta('total');
	if ($ResultCount != 0) {
		$Results = $SphQLResult->collect('groupid');
		$Groups = Torrents::get_groups(array_values($Results));
		$Groups = transcode_parse_groups($Groups);
	}
	unset($SphQL, $SphQLResult, $Results);
} elseif (in_array($_GET['filter'], array('snatched', 'seeding'))) {
	// Read all snatched/seeding torrents
	$DB->query("
		SELECT t.GroupID, x.fid
		FROM ".($_GET['filter'] === 'seeding' ? 'xbt_files_users' : 'xbt_snatched')." AS x
			JOIN torrents AS t ON t.ID=x.fid
			JOIN torrents_group AS tg ON tg.ID = t.GroupID
		WHERE t.Format='FLAC'
			AND (t.LogScore = '100' OR t.Media != 'CD')
			AND tg.CategoryID = 1
			AND x.uid = '$UserID'
			".($_GET['filter'] === 'seeding' ? 'AND x.active=1 AND x.Remaining=0' : ''));
	$Debug->set_flag('SELECTed ' . $_GET['filter'] . ' torrents');
	$Snatched = $DB->to_array();
	$Debug->set_flag('Received data from DB');
	shuffle($Snatched); // randomize results
	while ($ResultCount < TORRENTS_PER_PAGE && count($Snatched) > 0) {
		// we throw TORRENTS_PER_PAGE results into Sphinx until we have at least TORRENTS_PER_PAGE results (or no snatches left)
		$SnatchedTmp = array_slice($Snatched, 0, TORRENTS_PER_PAGE);
		$Snatched = array_slice($Snatched, TORRENTS_PER_PAGE);

		$SphQL = transcode_init_sphql();
		$SphQL->where('groupid', array_map(function ($row) { return $row['GroupID']; }, $SnatchedTmp));

		$SphQLResult = $SphQL->query();
		$ResultsTmp = $SphQLResult->collect('groupid');
		$GroupsTmp = Torrents::get_groups(array_values($ResultsTmp));
		$GroupsTmp = transcode_parse_groups($GroupsTmp);
		// Since we're asking Sphinxql about groups and remidents, the result can/will contain different editions that are transcodable but weren't snatched, so let's filter them out
		foreach ($GroupsTmp as $GroupID => $Group) {
			foreach ($Group['Editions'] as $RemIdent => $Edition) {
				$EditionSnatched = false;
				foreach ($SnatchedTmp as $SnatchedTmpE) {
					if (isset($Edition['FlacIDs'][$SnatchedTmpE['fid']])) {
						$EditionSnatched = true;
						break;
					}
				}
				if (!$EditionSnatched || count($Edition['MP3s']) === 3) {
					unset($GroupsTmp[$GroupID]['Editions'][$RemIdent]);
				}
			}
			$ResultCount += count($GroupsTmp[$GroupID]['Editions']);
			if (count($GroupsTmp[$GroupID]['Editions']) === 0) {
				unset($GroupsTmp[$GroupID]);
			}
		}
		$Groups = $GroupsTmp + $Groups;
		unset($SnatchedTmp, $SphQL, $SphQLResult, $ResultsTmp, $GroupsTmp);
	}
}
$Debug->log_var($Groups, 'Groups');

$Counter = array(
	'total' => 0, //how many FLAC torrents can be transcoded?
	'miss_total' => 0, //how many transcodes are missing?
	'miss_V0 (VBR)' => 0, //how many V0 transcodes are missing?
	'miss_V2 (VBR)' => 0, //how many V2 transcodes are missing?
	'miss_320' => 0, //how many 320 transcodes are missing?
);
foreach ($Groups as $GroupID => $Group) {
	foreach ($Group['Editions'] as $RemIdent => $Edition) {
		if (count($Edition['FlacIDs']) === 0 //no FLAC in this group
				|| (!empty($Edition['MP3s']) && $_GET['target'] === 'all') //at least one transcode present when we only wanted groups containing no transcodes at all
				|| isset($Edition['MP3s'][$Encodings[$_GET['target']]]) //the transcode we asked for is already there
				|| count($Edition['MP3s']) === 3) //all 3 transcodes are there already (this can happen due to the caching of Sphinx's better_transcode table)
		{
			$Debug->log_var($Edition, 'Skipping '.$RemIdent);
			unset($Groups[$GroupID]['Editions'][$RemIdent]);
			continue;
		}
		$edition_miss = 0; //number of transcodes missing in this edition
		foreach ($Encodings as $Encoding) {
			if (!isset($Edition['MP3s'][$Encoding])) {
				++$edition_miss;
				++$Counter['miss_'.$Encoding];
			}
		}
		$Counter['miss_total'] += $edition_miss;
		$Counter['total'] += (bool)$edition_miss;
	}
}
$Debug->log_var($Counter, 'counter');

View::show_header('Transcode Search');
?>
<br />
<div class="thin">
	<h2>Transcodes</h2>
	<h3>Search</h3>
	<form class="search_form" name="transcodes" action="" method="get">
		<input type="hidden" name="method" value="transcode_beta" />
		<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
			<tr>
				<td class="label"><strong>Filter</strong></td>
				<td>
					<select name="filter">
						<option value="all"<?=($_GET['filter'] == 'all' ? ' selected="selected"' : '')?>>Show all torrents</option>
						<option value="snatched"<?=($_GET['filter'] == 'snatched' ? ' selected="selected"' : '')?>>Snatched only</option>
						<option value="seeding"<?=($_GET['filter'] == 'seeding' ? ' selected="selected"' : '')?>>Seeding only</option>
						<option value="uploaded"<?=($_GET['filter'] == 'uploaded' ? ' selected="selected"' : '')?>>Uploaded only</option>
					</select>
					<select name="target">
						<option value="any"<?=($_GET['target'] == 'any' ? ' selected="selected"' : '')?>>Any transcode missing</option>
						<option value="v0"<?=($_GET['target'] == 'v0' ? ' selected="selected"' : '')?>>V0 missing</option>
						<option value="v2"<?=($_GET['target'] == 'v2' ? ' selected="selected"' : '')?>>V2 missing</option>
						<option value="320"<?=($_GET['target'] == '320' ? ' selected="selected"' : '')?>>320 missing</option>
						<option value="all"<?=($_GET['target'] == 'all' ? ' selected="selected"' : '')?>>All transcodes missing</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Search</strong></td>
				<td>
					<input type="search" name="search" size="60" value="<?=(!empty($_GET['search']) ? display_str($_GET['search']) : '')?>" />
				</td>
			</tr>
			<tr><td>&nbsp;</td><td><input type="submit" value="Search" /></td></tr>
		</table>
	</form>
	<h3>About</h3>
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
		<p>
			This page aims at listing <?=TORRENTS_PER_PAGE?> random transcodable perfect FLACs matching the options you selected above, but there can be more or less matches on this page. The following numbers tell you something about the torrents currently listed below and can change if you reload.<br /><br />

			Number of perfect FLACs you can transcode: <?=number_format($Counter['total'])?><br />
			Number of missing transcodes: <?=number_format($Counter['miss_total'])?><br />
			Number of missing V2 / V0 / 320 transcodes: <?=number_format($Counter['miss_V2 (VBR)'])?> / <?=number_format($Counter['miss_V0 (VBR)'])?> / <?=number_format($Counter['miss_320'])?>
		</p>
	</div>
	<h3>List</h3>
	<table width="100%" class="torrent_table">
		<tr class="colhead">
			<td>Torrent</td>
			<td>V2</td>
			<td>V0</td>
			<td>320</td>
		</tr>
<?
if ($ResultCount == 0) {
?>
		<tr><td colspan="4">No results found!</td></tr>
<?
} else {
	foreach ($Groups as $GroupID => $Group) {
		$GroupYear = $Group['Year'];
		$ExtendedArtists = $Group['ExtendedArtists'];
		$GroupName = $Group['Name'];
		$ReleaseType = $Group['ReleaseType'];

		if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
			unset($ExtendedArtists[2]);
			unset($ExtendedArtists[3]);
			$ArtistNames = Artists::display_artists($ExtendedArtists);
		} else {
			$ArtistNames = '';
		}

		$TorrentTags = new Tags($Group['TagList']);

		foreach ($Group['Editions'] as $RemIdent => $Edition) {
			// TODO: point to the correct FLAC (?)
			$FlacID = array_search(true, $Edition['FlacIDs']);
			$DisplayName = $ArtistNames . "<a href=\"torrents.php?id=$GroupID&amp;torrentid=$FlacID#torrent$FlacID\" class=\"tooltip\" title=\"View torrent\" dir=\"ltr\">$GroupName</a>";
			if ($GroupYear > 0) {
				$DisplayName .= " [$GroupYear]";
			}
			if ($ReleaseType > 0) {
				$DisplayName .= ' ['.$ReleaseTypes[$ReleaseType].']';
			}
			if ($Edition['FLACIsSnatched']) {
				$DisplayName .= ' ' . Format::torrent_label('Snatched!');
			}
?>
		<tr<?=($Edition['FLACIsSnatched'] ? ' class="snatched_torrent"' : '')?>>
			<td>
				<span class="torrent_links_block">
					<a href="torrents.php?action=download&amp;id=<?=$Edition['FlacID']?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download" class="brackets tooltip">DL</a>
				</span>
				<?=$DisplayName?>
				<div class="torrent_info"><?=$Edition['EditionName']?></div>
				<div class="tags"><?=$TorrentTags->format('better.php?action=transcode&tags=')?></div>
			</td>
			<td><?=(isset($Edition['MP3s']['V2 (VBR)']) ? '<strong class="important_text_alt">YES</strong>' : '<strong class="important_text">NO</strong>')?></td>
			<td><?=(isset($Edition['MP3s']['V0 (VBR)']) ? '<strong class="important_text_alt">YES</strong>' : '<strong class="important_text">NO</strong>')?></td>
			<td><?=(isset($Edition['MP3s']['320']) ? '<strong class="important_text_alt">YES</strong>' : '<strong class="important_text">NO</strong>')?></td>
		</tr>
<?
		}
	}
}
?>
	</table>
</div>
<?
View::show_footer();
?>
