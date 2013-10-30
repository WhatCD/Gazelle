<?
if (!isset($_GET['type']) || !is_number($_GET['type']) || $_GET['type'] > 3) {
	error(0);
}

$Options = array('v0', 'v2', '320');
$Encodings = array('V0 (VBR)', 'V2 (VBR)', '320');
$EncodingKeys = array_fill_keys($Encodings, true);

if ($_GET['type'] === '3') {
	$List = "!(v0 | v2 | 320)";
} else {
	$List = '!'.$Options[$_GET['type']];
	if ($_GET['type'] !== '0') {
		$_GET['type'] = display_str($_GET['type']);
	}
}
$SphQL = new SphinxqlQuery();
$SphQL->select('id, groupid')
	->from('better_transcode')
	->where('logscore', 100)
	->where_match('FLAC', 'format')
	->where_match($List, 'encoding', false)
	->order_by('RAND()')
	->limit(0, TORRENTS_PER_PAGE, TORRENTS_PER_PAGE);
if (!empty($_GET['search'])) {
	$SphQL->where_match($_GET['search'], '(groupname,artistname,year,taglist)');
}

$SphQLResult = $SphQL->query();
$TorrentCount = $SphQLResult->get_meta('total');

if ($TorrentCount == 0) {
	error('No results found!');
}

$Results = $SphQLResult->to_array('groupid');
$Groups = Torrents::get_groups(array_keys($Results));

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
					'RemasterTitle' => $Torrent['RemasterTitle'],
					'RemasterYear' => $Torrent['RemasterYear'],
					'RemasterRecordLabel' => $Torrent['RemasterRecordLabel'],
					'RemasterCatalogueNumber' => $Torrent['RemasterCatalogueNumber'],
					'IsSnatched' => false
				)
			);
		} elseif (!isset($TorrentGroups[$Group['ID']][$TorRemIdent])) {
			$TorrentGroups[$Group['ID']][$TorRemIdent] = array(
				'FlacID' => 0,
				'Formats' => array(),
				'RemasterTitle' => $Torrent['RemasterTitle'],
				'RemasterYear' => $Torrent['RemasterYear'],
				'RemasterRecordLabel' => $Torrent['RemasterRecordLabel'],
				'RemasterCatalogueNumber' => $Torrent['RemasterCatalogueNumber'],
				'IsSnatched' => false
			);
		}
		if (isset($EncodingKeys[$Torrent['Encoding']])) {
			$TorrentGroups[$Group['ID']][$TorRemIdent]['Formats'][$Torrent['Encoding']] = true;
		} elseif ($TorrentGroups[$Group['ID']][$TorRemIdent]['FlacID'] == 0 && $Torrent['Format'] == 'FLAC' && $Torrent['LogScore'] == 100) {
			$TorrentGroups[$Group['ID']][$TorRemIdent]['FlacID'] = $Torrent['ID'];
			$TorrentGroups[$Group['ID']][$TorRemIdent]['IsSnatched'] = $Torrent['IsSnatched'];
		}
	}
}

$JsonResults = array();
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
		$ArtistNames = Artists::display_artists($ExtendedArtists, false, false, false);
	} else {
		$ArtistNames = '';
	}

	$TagList = array();
	$TagList = explode(' ', str_replace('_', '.', $GroupInfo['TagList']));
	$TorrentTags = array();
	foreach ($TagList as $Tag) {
		$TorrentTags[] = "<a href=\"torrents.php?taglist=$Tag\">$Tag</a>";
	}
	$TorrentTags = implode(', ', $TorrentTags);
	foreach ($Editions as $RemIdent => $Edition) {
		if (!$Edition['FlacID']
				|| !empty($Edition['Formats']) && $_GET['type'] === '3'
				|| $Edition['Formats'][$Encodings[$_GET['type']]] == true) {
			continue;
		}

		$JsonResults[] = array(
			'torrentId' => (int)$Edition['FlacID'],
			'groupId' => (int)$GroupID,
			'artist' => $ArtistNames,
			'groupName' => $GroupName,
			'groupYear' => (int)$GroupYear,
			'missingV2' => !isset($Edition['Formats']['V2 (VBR)']),
			'missingV0' => !isset($Edition['Formats']['V0 (VBR)']),
			'missing320' => !isset($Encodings['Formats']['320']),
			'downloadUrl' => 'torrents.php?action=download&id='.$Edition['FlacID'].'&authkey='.$LoggedUser['AuthKey'].'&torrent_pass='.$LoggedUser['torrent_pass']
		);
	}
}

print json_encode(
	array(
		'status' => 'success',
		'response' => $JsonResults
	)
);
