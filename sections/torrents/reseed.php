<?
$TorrentID = (int)$_GET['torrentid'];

$DB->query("
	SELECT last_action, LastReseedRequest, UserID, Time, GroupID
	FROM torrents
	WHERE ID = '$TorrentID'");
list($LastActive, $LastReseedRequest, $UploaderID, $UploadedTime, $GroupID) = $DB->next_record();

if (!check_perms('users_mod')) {
	if (time() - strtotime($LastReseedRequest) < 864000) {
		error('There was already a re-seed request for this torrent within the past 10 days.');
	}
	if ($LastActive == '0000-00-00 00:00:00' || time() - strtotime($LastActive) < 345678) {
		error(403);
	}
}

$DB->query("
	UPDATE torrents
	SET LastReseedRequest = NOW()
	WHERE ID = '$TorrentID'");

$Group = Torrents::get_groups(array($GroupID));
extract(Torrents::array_group($Group[$GroupID]));

$Name = Artists::display_artists(array('1' => $Artists), false, true);
$Name .= $GroupName;

$usersToNotify = array();

$DB->query("
	SELECT s.uid AS id, MAX(s.tstamp) AS tstamp
	FROM xbt_snatched as s
	INNER JOIN users_main as u
	ON s.uid = u.ID
	WHERE s.fid = '$TorrentID'
	AND u.Enabled = '1'
	GROUP BY s.uid
       ORDER BY tstamp DESC
	LIMIT 100");
if ($DB->has_results()) {
	$Users = $DB->to_array();
	foreach ($Users as $User) {
		$UserID = $User['id'];
		$TimeStamp = $User['tstamp'];

		$usersToNotify[$UserID] = array("snatched", $TimeStamp);
	}
}

$usersToNotify[$UploaderID] = array("uploaded", strtotime($UploadedTime));

foreach ($usersToNotify as $UserID => $info) {
	$Username = Users::user_info($UserID)['Username'];
	list($action, $TimeStamp) = $info;

	$Request = "Hi $Username,

The user [url=".site_url()."user.php?id=$LoggedUser[ID]]$LoggedUser[Username][/url] has requested a re-seed for the torrent [url=".site_url()."torrents.php?id=$GroupID&torrentid=$TorrentID]{$Name}[/url], which you ".$action." on ".date('M d Y', $TimeStamp).". The torrent is now un-seeded, and we need your help to resurrect it!

The exact process for re-seeding a torrent is slightly different for each client, but the concept is the same. The idea is to download the torrent file and open it in your client, and point your client to the location where the data files are, then initiate a hash check.

Thanks!";

	Misc::send_pm($UserID, 0, "Re-seed request for torrent $Name", $Request);
}

$NumUsers = count($usersToNotify);

View::show_header();
?>
<div class="thin">
	<div class="header">
		<h2>Successfully sent re-seed request</h2>
	</div>
	<div class="box pad thin">
		<p style="color: black;">Successfully sent re-seed request for torrent <a href="torrents.php?id=<?=$GroupID?>&torrentid=<?=$TorrentID?>"><?=display_str($Name)?></a> to <?=$NumUsers?> user<?=$NumUsers === 1 ? '' : 's';?>.</p>
	</div>
</div>
<?
View::show_footer();
?>
