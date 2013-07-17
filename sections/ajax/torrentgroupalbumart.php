<?php
require(SERVER_ROOT.'/sections/torrents/functions.php');

$GroupID = (int)$_GET['id'];
if ($GroupID === 0) {
	error('bad id parameter', true);
}

$TorrentDetails = get_group_info($GroupID, true, 0, false);
$TorrentDetails = $TorrentDetails[0];
$Image = $TorrentDetails['WikiImage'];
if (!$Image) { // handle no artwork
	$Image = STATIC_SERVER.'common/noartwork/'.$CategoryIcons[$TorrentDetails['CategoryID'] - 1];
}

json_die("success", array(
	'wikiImage' => $Image
));
