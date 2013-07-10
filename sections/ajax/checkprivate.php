<?

include(SERVER_ROOT.'/classes/bencodetorrent.class.php');

$TorrentID = $_GET['torrentid'];
if (!is_number($TorrentID)) {
	echo('Invalid TorrentID');
	die();
}

$DB->query("
	SELECT File
	FROM torrents_files
	WHERE TorrentID = '$TorrentID'");
if (!$DB->has_results()) {
	echo('Torrent not found.');
	die();
}
list($Contents) = $DB->next_record(MYSQLI_NUM, array(0));
if (Misc::is_new_torrent($Contents)) {
	$Tor = new BencodeTorrent($Contents);
	$Private = $Tor->is_private();
} else {
	$Tor = new TORRENT(unserialize(base64_decode($Contents)), true); // New TORRENT object
	$Private = $Tor->make_private();
}

if ($Private === true) {
	echo '<span style="color: #0c0; font-weight: bold;">Private</span>';
} else {
	echo '<span style="color: #c00; font-weight: bold;">Public</span>';
}
?>
