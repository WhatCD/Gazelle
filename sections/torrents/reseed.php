<?
$GroupID = $_GET['groupid'];
$TorrentID = $_GET['torrentid'];

if (!is_number($GroupID) || !is_number($TorrentID)) {
	error(0);
}

$DB->query("
	SELECT last_action, LastReseedRequest, UserID, Time
	FROM torrents
	WHERE ID = '$TorrentID'");
list($LastActive, $LastReseedRequest, $UploaderID, $UploadedTime) = $DB->next_record();

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

$Name = '';
$Name .= Artists::display_artists(array('1' => $Artists), false, true);
$Name .= $GroupName;

$DB->query("
	SELECT uid, MAX(tstamp) AS tstamp
	FROM xbt_snatched
	WHERE fid = '$TorrentID'
	GROUP BY uid
	ORDER BY tstamp DESC
	LIMIT 10");
if ($DB->has_results()) {
	$Users = $DB->to_array();
	foreach ($Users as $User) {
		$UserID = $User['uid'];

		$DB->query("
			SELECT UserID
			FROM top_snatchers
			WHERE UserID = '$UserID'");
		if ($DB->has_results()) {
			continue;
		}

		$UserInfo = Users::user_info($UserID);
		$Username = $UserInfo['Username'];
		$TimeStamp = $User['tstamp'];
		$Request = "Hi $Username,

The user [url=".site_url()."user.php?id=$LoggedUser[ID]]$LoggedUser[Username][/url] has requested a re-seed for the torrent [url=".site_url()."torrents.php?id=$GroupID&torrentid=$TorrentID]{$Name}[/url], which you snatched on ".date('M d Y', $TimeStamp).". The torrent is now un-seeded, and we need your help to resurrect it!

The exact process for re-seeding a torrent is slightly different for each client, but the concept is the same. The idea is to download the torrent file and open it in your client, and point your client to the location where the data files are, then initiate a hash check.

Thanks!";

		Misc::send_pm($UserID, 0, "Re-seed request for torrent $Name", $Request);
	}
	$NumUsers = count($Users);
} else {
	$UserInfo = Users::user_info($UploaderID);
	$Username = $UserInfo['Username'];

	$Request = "Hi $Username,

The user [url=".site_url()."user.php?id=$LoggedUser[ID]]$LoggedUser[Username][/url] has requested a re-seed for the torrent [url=".site_url()."torrents.php?id=$GroupID&torrentid=$TorrentID]{$Name}[/url], which you uploaded on ".date('M d Y', strtotime($UploadedTime)).". The torrent is now un-seeded, and we need your help to resurrect it!

The exact process for re-seeding a torrent is slightly different for each client, but the concept is the same. The idea is to download the torrent file and open it in your client, and point your client to the location where the data files are, then initiate a hash check.

Thanks!";
	Misc::send_pm($UploaderID, 0, "Re-seed request for torrent $Name", $Request);

	$NumUsers = 1;
}
View::show_header();
?>
<div class="thin">
	<div class="header">
		<h2>Successfully sent re-seed request</h2>
	</div>
	<div class="box pad thin">
		<p>Successfully sent re-seed request for torrent <a href="torrents.php?id=<?=$GroupID?>&amp;torrentid=<?=$TorrentID?>"><?=display_str($Name)?></a> to <?=$NumUsers?> user<?=$NumUsers === 1 ? '' : 's';?>.</p>
	</div>
</div>
<?
View::show_footer();
?>
