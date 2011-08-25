<?php

authorize();

require(SERVER_ROOT.'/sections/torrents/functions.php');

$GroupAllowed = array('WikiBody', 'WikiImage', 'ID', 'Name', 'Year', 'RecordLabel', 'CatalogueNumber', 'ReleaseType', 'CategoryID', 'Time', 'VanityHouse');
$TorrentAllowed = array('ID', 'Media', 'Format', 'Encoding', 'Remastered', 'RemasterYear', 'RemasterTitle', 'RemasterRecordLabel', 'RemasterCatalogueNumber', 'Scene', 'HasLog', 'HasCue', 'LogScore', 'FileCount', 'Size', 'Seeders', 'Leechers', 'Snatched', 'FreeTorrent', 'Time', 'Description', 'FileList', 'FilePath', 'UserID', 'Username');

$GroupID = (int)$_GET['id'];

if ($GroupID == 0) { error('bad id parameter', true); }

$TorrentCache = get_group_info($GroupID, true, 0);

// http://stackoverflow.com/questions/4260086/php-how-to-use-array-filter-to-filter-array-keys
function filter_by_key($input, $keys) { return array_intersect_key($input, array_flip($keys)); }

$TorrentDetails = filter_by_key($TorrentCache[0][0], $GroupAllowed);
$TorrentList = array();
foreach ($TorrentCache[1] as $Torrent) {
	$TorrentList[] = filter_by_key($Torrent, $TorrentAllowed);
}

header('Content-Type: text/plain; charset=utf-8');

print json_encode(array('status' => 'success', 'response' => array('group' => $TorrentDetails, 'torrents' => $TorrentList)));
