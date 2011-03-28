<?
authorize();

$TorrentID = $_POST['torrentid'];
if(!$TorrentID || !is_number($TorrentID)) { error(404); }

$DB->query("SELECT
	t.UserID,
	t.GroupID,
	t.Size,
	t.info_hash,
	tg.Name,
	ag.Name,
	t.Time, 
	COUNT(x.uid) 
	FROM torrents AS t
	LEFT JOIN torrents_group AS tg ON tg.ID=t.GroupID
	LEFT JOIN artists_group AS ag ON ag.ArtistID=tg.ArtistID
	LEFT JOIN xbt_snatched AS x ON x.fid=t.ID
	WHERE t.ID='$TorrentID'");
list($UserID, $GroupID, $Size, $InfoHash, $Name, $ArtistName, $Time, $Snatches) = $DB->next_record(MYSQLI_NUM, array(3));

if(($LoggedUser['ID']!=$UserID || time_ago($Time) > 3600*24*7 || $Snatches > 4) && !check_perms('torrents_delete')) {
	error(403);
}

if($ArtistName) {
	$Name = $ArtistName.' - '.$Name;
}

if(isset($_SESSION['logged_user']['multi_delete'])) {
	if($_SESSION['logged_user']['multi_delete']>=3 && !check_perms('torrents_delete_fast')) {
		error('You have recently deleted 3 torrents, please contact a staff member if you need to delete more.');
	}
	$_SESSION['logged_user']['multi_delete']++;
} else {
	$_SESSION['logged_user']['multi_delete'] = 1;
}

$InfoHash = unpack("H*", $InfoHash);
delete_torrent($TorrentID, $GroupID);
write_log('Torrent '.$TorrentID.' ('.$Name.') ('.number_format($Size/(1024*1024), 2).' MB) ('.strtoupper($InfoHash[1]).') was deleted by '.$LoggedUser['Username'].': ' .$_POST['reason'].' '.$_POST['extra']);

show_header('Torrent deleted');
?>
<div class="thin">
	<h3>Torrent was successfully deleted.</h3>
</div>
<?
show_footer();
