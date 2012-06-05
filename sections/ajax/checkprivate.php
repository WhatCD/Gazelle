<?
require(SERVER_ROOT.'/classes/class_torrent.php');
$TorrentID = $_GET['torrentid'];
if (!is_number($TorrentID)) {
	echo('Invalid TorrentID');
	die();
}

$DB->query("SELECT File FROM torrents_files WHERE TorrentID='$TorrentID'");
if($DB->record_count() == 0) {
	echo('Torrent not found.');
	die();
}
list($Contents) = $DB->next_record(MYSQLI_NUM, array(0));
$Contents = unserialize(base64_decode($Contents));
$Tor = new TORRENT($Contents, true); // New TORRENT object
$Private = $Tor->make_private();

if ($Private) {
	echo '<span style="color: #0c0; font-weight: bold;">Private</span>';
} else {
	echo '<span style="color: #c00; font-weight: bold;">Public</span>';
}
?>