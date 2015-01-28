<?php
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
	json_die("failure", "bad id parameter");
}
$UserID = $_GET['id'];


if ($UserID == $LoggedUser['ID']) {
	$OwnProfile = true;
} else {
	$OwnProfile = false;
}

// Always view as a normal user.
$DB->query("
	SELECT
		m.Username,
		m.Email,
		m.LastAccess,
		m.IP,
		p.Level AS Class,
		m.Uploaded,
		m.Downloaded,
		m.RequiredRatio,
		m.Enabled,
		m.Paranoia,
		m.Invites,
		m.Title,
		m.torrent_pass,
		m.can_leech,
		i.JoinDate,
		i.Info,
		i.Avatar,
		i.Donor,
		i.Warned,
		COUNT(posts.id) AS ForumPosts,
		i.Inviter,
		i.DisableInvites,
		inviter.username
	FROM users_main AS m
		JOIN users_info AS i ON i.UserID = m.ID
		LEFT JOIN permissions AS p ON p.ID = m.PermissionID
		LEFT JOIN users_main AS inviter ON i.Inviter = inviter.ID
		LEFT JOIN forums_posts AS posts ON posts.AuthorID = m.ID
	WHERE m.ID = $UserID
	GROUP BY AuthorID");

if (!$DB->has_results()) { // If user doesn't exist
	json_die("failure", "no such user");
}

list($Username, $Email, $LastAccess, $IP, $Class, $Uploaded, $Downloaded, $RequiredRatio, $Enabled, $Paranoia, $Invites, $CustomTitle, $torrent_pass, $DisableLeech, $JoinDate, $Info, $Avatar, $Donor, $Warned, $ForumPosts, $InviterID, $DisableInvites, $InviterName, $RatioWatchEnds, $RatioWatchDownload) = $DB->next_record(MYSQLI_NUM, array(9, 11));

$Paranoia = unserialize($Paranoia);
if (!is_array($Paranoia)) {
	$Paranoia = array();
}
$ParanoiaLevel = 0;
foreach ($Paranoia as $P) {
	$ParanoiaLevel++;
	if (strpos($P, '+') !== false) {
		$ParanoiaLevel++;
	}
}

// Raw time is better for JSON.
//$JoinedDate = time_diff($JoinDate);
//$LastAccess = time_diff($LastAccess);

function check_paranoia_here($Setting) {
	global $Paranoia, $Class, $UserID;
	return check_paranoia($Setting, $Paranoia, $Class, $UserID);
}

$Friend = false;
$DB->query("
	SELECT FriendID
	FROM friends
	WHERE UserID = '$LoggedUser[ID]'
		AND FriendID = '$UserID'");
if ($DB->has_results()) {
	$Friend = true;
}

if (check_paranoia_here('requestsfilled_count') || check_paranoia_here('requestsfilled_bounty')) {
	$DB->query("
		SELECT COUNT(DISTINCT r.ID), SUM(rv.Bounty)
		FROM requests AS r
			LEFT JOIN requests_votes AS rv ON r.ID = rv.RequestID
		WHERE r.FillerID = $UserID");
	list($RequestsFilled, $TotalBounty) = $DB->next_record();
	$DB->query("
		SELECT COUNT(RequestID), SUM(Bounty)
		FROM requests_votes
		WHERE UserID = $UserID");
	list($RequestsVoted, $TotalSpent) = $DB->next_record();

	$DB->query("
		SELECT COUNT(ID)
		FROM torrents
		WHERE UserID = '$UserID'");
	list($Uploads) = $DB->next_record();
} else {
	$RequestsFilled = null;
	$TotalBounty = null;
	$RequestsVoted = null;
	$TotalSpent = null;
}
if (check_paranoia_here('uploads+')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM torrents
		WHERE UserID = '$UserID'");
	list($Uploads) = $DB->next_record();
} else {
	$Uploads = null;
}

if (check_paranoia_here('artistsadded')) {
	$DB->query("
		SELECT COUNT(ArtistID)
		FROM torrents_artists
		WHERE UserID = $UserID");
	list($ArtistsAdded) = $DB->next_record();
} else {
	$ArtistsAdded = null;
}

// Do the ranks.
if (check_paranoia_here('uploaded')) {
	$UploadedRank = UserRank::get_rank('uploaded', $Uploaded);
} else {
	$UploadedRank = null;
}
if (check_paranoia_here('downloaded')) {
	$DownloadedRank = UserRank::get_rank('downloaded', $Downloaded);
} else {
	$DownloadedRank = null;
}
if (check_paranoia_here('uploads+')) {
	$UploadsRank = UserRank::get_rank('uploads', $Uploads);
} else {
	$UploadsRank = null;
}
if (check_paranoia_here('requestsfilled_count')) {
	$RequestRank = UserRank::get_rank('requests', $RequestsFilled);
} else {
	$RequestRank = null;
}
$PostRank = UserRank::get_rank('posts', $ForumPosts);
if (check_paranoia_here('requestsvoted_bounty')) {
	$BountyRank = UserRank::get_rank('bounty', $TotalSpent);
} else {
	$BountyRank = null;
}
if (check_paranoia_here('artistsadded')) {
	$ArtistsRank = UserRank::get_rank('artists', $ArtistsAdded);
} else {
	$ArtistsRank = null;
}

if ($Downloaded == 0) {
	$Ratio = 1;
} elseif ($Uploaded == 0) {
	$Ratio = 0.5;
} else {
	$Ratio = round($Uploaded / $Downloaded, 2);
}
if (check_paranoia_here(array('uploaded', 'downloaded', 'uploads+', 'requestsfilled_count', 'requestsvoted_bounty', 'artistsadded'))) {
	$OverallRank = floor(UserRank::overall_score($UploadedRank, $DownloadedRank, $UploadsRank, $RequestRank, $PostRank, $BountyRank, $ArtistsRank, $Ratio));
} else {
	$OverallRank = null;
}

// Community section
if (check_paranoia_here('snatched+')) {
$DB->query("
	SELECT COUNT(x.uid), COUNT(DISTINCT x.fid)
	FROM xbt_snatched AS x
		INNER JOIN torrents AS t ON t.ID = x.fid
	WHERE x.uid = '$UserID'");
list($Snatched, $UniqueSnatched) = $DB->next_record();
}

if (check_paranoia_here('torrentcomments+')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM comments
		WHERE Page = 'torrents'
			AND AuthorID = '$UserID'");
	list($NumComments) = $DB->next_record();
}

if (check_paranoia_here('torrentcomments+')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM comments
		WHERE Page = 'artist'
			AND AuthorID = '$UserID'");
	list($NumArtistComments) = $DB->next_record();
}

if (check_paranoia_here('torrentcomments+')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM comments
		WHERE Page = 'collages'
			AND AuthorID = '$UserID'");
	list($NumCollageComments) = $DB->next_record();
}

if (check_paranoia_here('torrentcomments+')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM comments
		WHERE Page = 'requests'
			AND AuthorID = '$UserID'");
	list($NumRequestComments) = $DB->next_record();
}

if (check_paranoia_here('collages+')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM collages
		WHERE Deleted = '0'
			AND UserID = '$UserID'");
	list($NumCollages) = $DB->next_record();
}

if (check_paranoia_here('collagecontribs+')) {
	$DB->query("
		SELECT COUNT(DISTINCT ct.CollageID)
		FROM collages_torrents AS ct
			JOIN collages AS c ON ct.CollageID = c.ID
		WHERE c.Deleted = '0'
			AND ct.UserID = '$UserID'");
	list($NumCollageContribs) = $DB->next_record();
}

if (check_paranoia_here('uniquegroups+')) {
	$DB->query("
		SELECT COUNT(DISTINCT GroupID)
		FROM torrents
		WHERE UserID = '$UserID'");
	list($UniqueGroups) = $DB->next_record();
}

if (check_paranoia_here('perfectflacs+')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM torrents
		WHERE (
			(LogScore = 100 AND Format = 'FLAC')
			OR (Media = 'Vinyl' AND Format = 'FLAC')
			OR (Media = 'WEB' AND Format = 'FLAC')
			OR (Media = 'DVD' AND Format = 'FLAC')
			OR (Media = 'Soundboard' AND Format = 'FLAC')
			OR (Media = 'Cassette' AND Format = 'FLAC')
			OR (Media = 'SACD' AND Format = 'FLAC')
			OR (Media = 'Blu-ray' AND Format = 'FLAC')
			OR (Media = 'DAT' AND Format = 'FLAC')
			)
			AND UserID = '$UserID'");
	list($PerfectFLACs) = $DB->next_record();
}

if (check_paranoia_here('seeding+')) {
	$DB->query("
		SELECT COUNT(x.uid)
		FROM xbt_files_users AS x
			INNER JOIN torrents AS t ON t.ID = x.fid
		WHERE x.uid = '$UserID'
			AND x.remaining = 0");
	list($Seeding) = $DB->next_record();
}

if (check_paranoia_here('leeching+')) {
	$DB->query("
		SELECT COUNT(x.uid)
		FROM xbt_files_users AS x
			INNER JOIN torrents AS t ON t.ID = x.fid
		WHERE x.uid = '$UserID'
			AND x.remaining > 0");
	list($Leeching) = $DB->next_record();
}

if (check_paranoia_here('invitedcount')) {
	$DB->query("
		SELECT COUNT(UserID)
		FROM users_info
		WHERE Inviter = '$UserID'");
	list($Invited) = $DB->next_record();
}

if (!$OwnProfile) {
	$torrent_pass = '';
}

// Run through some paranoia stuff to decide what we can send out.
if (!check_paranoia_here('lastseen')) {
	$LastAccess = '';
}
if (check_paranoia_here('ratio')) {
	$Ratio = Format::get_ratio($Uploaded, $Downloaded, 5);
} else {
	$Ratio = null;
}
if (!check_paranoia_here('uploaded')) {
	$Uploaded = null;
}
if (!check_paranoia_here('downloaded')) {
	$Downloaded = null;
}
if (isset($RequiredRatio) && !check_paranoia_here('requiredratio')) {
	$RequiredRatio = null;
}
if ($ParanoiaLevel == 0) {
	$ParanoiaLevelText = 'Off';
} elseif ($ParanoiaLevel == 1) {
	$ParanoiaLevelText = 'Very Low';
} elseif ($ParanoiaLevel <= 5) {
	$ParanoiaLevelText = 'Low';
} elseif ($ParanoiaLevel <= 20) {
	$ParanoiaLevelText = 'High';
} else {
	$ParanoiaLevelText = 'Very high';
}

//Bugfix for no access time available
if ($LastAccess == '0000-00-00 00:00:00') {
	$LastAccess = '';
}

header('Content-Type: text/plain; charset=utf-8');

json_print("success", array(
	'username' => $Username,
	'avatar' => $Avatar,
	'isFriend' => $Friend,
	'profileText' => Text::full_format($Info),
	'stats' => array(
		'joinedDate' => $JoinDate,
		'lastAccess' => $LastAccess,
		'uploaded' => (($Uploaded == null) ? null : (int)$Uploaded),
		'downloaded' => (($Downloaded == null) ? null : (int)$Downloaded),
		'ratio' => $Ratio,
		'requiredRatio' => (($RequiredRatio == null) ? null : (float)$RequiredRatio)
	),
	'ranks' => array(
		'uploaded' => $UploadedRank,
		'downloaded' => $DownloadedRank,
		'uploads' => $UploadsRank,
		'requests' => $RequestRank,
		'bounty' => $BountyRank,
		'posts' => $PostRank,
		'artists' => $ArtistsRank,
		'overall' => (($OverallRank == null) ? 0 : $OverallRank)
	),
	'personal' => array(
		'class' => $ClassLevels[$Class]['Name'],
		'paranoia' => $ParanoiaLevel,
		'paranoiaText' => $ParanoiaLevelText,
		'donor' => ($Donor == 1),
		'warned' => ($Warned != '0000-00-00 00:00:00'),
		'enabled' => ($Enabled == '1' || $Enabled == '0' || !$Enabled),
		'passkey' => $torrent_pass
	),
	'community' => array(
		'posts' => (int)$ForumPosts,
		'torrentComments' => (($NumComments == null) ? null : (int)$NumComments),
		'artistComments' => (($NumArtistComments == null) ? null : (int)$NumArtistComments),
		'collageComments' => (($NumCollageComments == null) ? null : (int)$NumCollageComments),
		'requestComments' => (($NumRequestComments == null) ? null : (int)$NumRequestComments),
		'collagesStarted' => (($NumCollages == null) ? null : (int)$NumCollages),
		'collagesContrib' => (($NumCollageContribs == null) ? null : (int)$NumCollageContribs),
		'requestsFilled' => (($RequestsFilled == null) ? null : (int)$RequestsFilled),
		'bountyEarned' => (($TotalBounty == null) ? null : (int)$TotalBounty),
		'requestsVoted' => (($RequestsVoted == null) ? null : (int)$RequestsVoted),
		'bountySpent' => (($TotalSpent == null) ? null : (int)$TotalSpent),
		'perfectFlacs' => (($PerfectFLACs == null) ? null : (int)$PerfectFLACs),
		'uploaded' => (($Uploads == null) ? null : (int)$Uploads),
		'groups' => (($UniqueGroups == null) ? null : (int)$UniqueGroups),
		'seeding' => (($Seeding == null) ? null : (int)$Seeding),
		'leeching' => (($Leeching == null) ? null : (int)$Leeching),
		'snatched' => (($Snatched == null) ? null : (int)$Snatched),
		'invited' => (($Invited == null) ? null : (int)$Invited),
		'artistsAdded' => (($ArtistsAdded == null) ? null : (int)$ArtistsAdded)
	)
));
?>
