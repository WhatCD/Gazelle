<?
if (empty($_GET['id']) || !is_number($_GET['id']) || (!empty($_GET['preview']) && !is_number($_GET['preview']))) {
	error(404);
}
$UserID = (int)$_GET['id'];
$Preview = isset($_GET['preview']) ? $_GET['preview'] : 0;
if ($UserID == $LoggedUser['ID']) {
	$OwnProfile = true;
	if ($Preview == 1) {
		$OwnProfile = false;
		$ParanoiaString = $_GET['paranoia'];
		$CustomParanoia = explode(',', $ParanoiaString);
	}
} else {
	$OwnProfile = false;
	//Don't allow any kind of previewing on others' profiles
	$Preview = 0;
}
$EnabledRewards = Donations::get_enabled_rewards($UserID);
$ProfileRewards = Donations::get_profile_rewards($UserID);



if (check_perms('users_mod')) { // Person viewing is a staff member
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
			m.Title,
			m.torrent_pass,
			m.Enabled,
			m.Paranoia,
			m.Invites,
			m.can_leech,
			m.Visible,
			i.JoinDate,
			i.Info,
			i.Avatar,
			i.AdminComment,
			i.Donor,
			i.Artist,
			i.Warned,
			i.SupportFor,
			i.RestrictedForums,
			i.PermittedForums,
			i.Inviter,
			inviter.Username,
			COUNT(posts.id) AS ForumPosts,
			i.RatioWatchEnds,
			i.RatioWatchDownload,
			i.DisableAvatar,
			i.DisableInvites,
			i.DisablePosting,
			i.DisableForums,
			i.DisableTagging,
			i.DisableUpload,
			i.DisableWiki,
			i.DisablePM,
			i.DisableIRC,
			i.DisableRequests," . "
			m.FLTokens,
			SHA1(i.AdminComment),
			i.InfoTitle
		FROM users_main AS m
			JOIN users_info AS i ON i.UserID = m.ID
			LEFT JOIN users_main AS inviter ON i.Inviter = inviter.ID
			LEFT JOIN permissions AS p ON p.ID = m.PermissionID
			LEFT JOIN forums_posts AS posts ON posts.AuthorID = m.ID
		WHERE m.ID = '$UserID'
		GROUP BY AuthorID");

	if (!$DB->has_results()) { // If user doesn't exist
		header("Location: log.php?search=User+$UserID");
	}

	list($Username,	$Email,	$LastAccess, $IP, $Class, $Uploaded, $Downloaded, $RequiredRatio, $CustomTitle, $torrent_pass, $Enabled, $Paranoia, $Invites, $DisableLeech, $Visible, $JoinDate, $Info, $Avatar, $AdminComment, $Donor, $Artist, $Warned, $SupportFor, $RestrictedForums, $PermittedForums, $InviterID, $InviterName, $ForumPosts, $RatioWatchEnds, $RatioWatchDownload, $DisableAvatar, $DisableInvites, $DisablePosting, $DisableForums, $DisableTagging, $DisableUpload, $DisableWiki, $DisablePM, $DisableIRC, $DisableRequests, $FLTokens, $CommentHash, $InfoTitle) = $DB->next_record(MYSQLI_NUM, array(8, 11));
} else { // Person viewing is a normal user
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
			m.FLTokens,
			i.Donor,
			i.Warned,
			COUNT(posts.id) AS ForumPosts,
			i.Inviter,
			i.DisableInvites,
			inviter.username,
			i.InfoTitle
		FROM users_main AS m
			JOIN users_info AS i ON i.UserID = m.ID
			LEFT JOIN permissions AS p ON p.ID = m.PermissionID
			LEFT JOIN users_main AS inviter ON i.Inviter = inviter.ID
			LEFT JOIN forums_posts AS posts ON posts.AuthorID = m.ID
		WHERE m.ID = $UserID
		GROUP BY AuthorID");

	if (!$DB->has_results()) { // If user doesn't exist
		header("Location: log.php?search=User+$UserID");
	}

    list($Username, $Email, $LastAccess, $IP, $Class, $Uploaded, $Downloaded,
$RequiredRatio, $Enabled, $Paranoia, $Invites, $CustomTitle, $torrent_pass,
$DisableLeech, $JoinDate, $Info, $Avatar, $FLTokens, $Donor, $Warned,
$ForumPosts, $InviterID, $DisableInvites, $InviterName, $InfoTitle) = $DB->next_record(MYSQLI_NUM, array(9, 11));
}

// Image proxy CTs
$DisplayCustomTitle = $CustomTitle;
if (check_perms('site_proxy_images') && !empty($CustomTitle)) {
	$DisplayCustomTitle = preg_replace_callback('~src=("?)(http.+?)(["\s>])~',
								function($Matches) {
									return 'src=' . $Matches[1] . ImageTools::process($Matches[2]) . $Matches[3];
								}, $CustomTitle);
}

if ($Preview == 1) {
	if (strlen($ParanoiaString) == 0) {
		$Paranoia = array();
	} else {
		$Paranoia = $CustomParanoia;
	}
} else {
	$Paranoia = unserialize($Paranoia);
	if (!is_array($Paranoia)) {
		$Paranoia = array();
	}
}
$ParanoiaLevel = 0;
foreach ($Paranoia as $P) {
	$ParanoiaLevel++;
	if (strpos($P, '+') !== false) {
		$ParanoiaLevel++;
	}
}

$JoinedDate = time_diff($JoinDate);
$LastAccess = time_diff($LastAccess);

function check_paranoia_here($Setting) {
	global $Paranoia, $Class, $UserID, $Preview;
	if ($Preview == 1) {
		return check_paranoia($Setting, $Paranoia, $Class);
	} else {
		return check_paranoia($Setting, $Paranoia, $Class, $UserID);
	}
}

View::show_header($Username, "jquery.imagesloaded,jquery.wookmark,user,bbcode,requests,lastfm,comments,info_paster", "tiles");

?>
<div class="thin">
	<div class="header">
		<h2><?=Users::format_username($UserID, true, true, true, false, true)?></h2>
	</div>
	<div class="linkbox">
<?
if (!$OwnProfile) {
?>
		<a href="inbox.php?action=compose&amp;to=<?=$UserID?>" class="brackets">Send message</a>
<?
	$DB->query("
		SELECT FriendID
		FROM friends
		WHERE UserID = '$LoggedUser[ID]'
			AND FriendID = '$UserID'");
	if (!$DB->has_results()) {
?>
		<a href="friends.php?action=add&amp;friendid=<?=$UserID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Add to friends</a>
<?	} ?>
		<a href="reports.php?action=report&amp;type=user&amp;id=<?=$UserID?>" class="brackets">Report user</a>
<?

}

if (check_perms('users_edit_profiles', $Class) || $LoggedUser['ID'] == $UserID) {
?>
		<a href="user.php?action=edit&amp;userid=<?=$UserID?>" class="brackets">Settings</a>
<?
}
if (check_perms('users_view_invites', $Class)) {
?>
		<a href="user.php?action=invite&amp;userid=<?=$UserID?>" class="brackets">Invites</a>
<?
}
if (check_perms('admin_manage_permissions', $Class)) {
?>
		<a href="user.php?action=permissions&amp;userid=<?=$UserID?>" class="brackets">Permissions</a>
<?
}
if (check_perms('users_view_ips', $Class)) {
?>
		<a href="user.php?action=sessions&amp;userid=<?=$UserID?>" class="brackets">Sessions</a>
<?
}
if (check_perms('admin_reports')) {
?>
		<a href="reportsv2.php?view=reporter&amp;id=<?=$UserID?>" class="brackets">Reports</a>
<?
}
if (check_perms('users_mod')) {
?>
		<a href="userhistory.php?action=token_history&amp;userid=<?=$UserID?>" class="brackets">FL tokens</a>
<?
}
if (check_perms('admin_clear_cache') && check_perms('users_override_paranoia')) {
?>
		<a href="user.php?action=clearcache&amp;id=<?=$UserID?>" class="brackets">Clear cache</a>
<?
}
if (check_perms('users_mod')) {
?>
		<a href="#staff_tools" class="brackets">Jump to staff tools</a>
<?
}
?>
	</div>

	<div class="sidebar">
<?
if ($Avatar && Users::has_avatars_enabled()) {
?>
		<div class="box box_image box_image_avatar">
			<div class="head colhead_dark">Avatar</div>
			<div align="center">
<?=				Users::show_avatar($Avatar, $UserID, $Username, $HeavyInfo['DisableAvatars'])?>
			</div>
		</div>
<?
}
?>
		<div class="box box_info box_userinfo_stats">
			<div class="head colhead_dark">Statistics</div>
			<ul class="stats nobullet">
				<li>Joined: <?=$JoinedDate?></li>
<?	if (($Override = check_paranoia_here('lastseen'))) { ?>
				<li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Last seen: <?=$LastAccess?></li>
<?
	}
	if (($Override = check_paranoia_here('uploaded'))) {
?>
				<li class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>" title="<?=Format::get_size($Uploaded, 5)?>">Uploaded: <?=Format::get_size($Uploaded)?></li>
<?
	}
	if (($Override = check_paranoia_here('downloaded'))) {
?>
				<li class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>" title="<?=Format::get_size($Downloaded, 5)?>">Downloaded: <?=Format::get_size($Downloaded)?></li>
<?
	}
	if (($Override = check_paranoia_here('ratio'))) {
?>
				<li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Ratio: <?=Format::get_ratio_html($Uploaded, $Downloaded)?></li>
<?
	}
	if (($Override = check_paranoia_here('requiredratio')) && isset($RequiredRatio)) {
?>
				<li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Required Ratio: <span class="tooltip" title="<?=number_format((double)$RequiredRatio, 5)?>"><?=number_format((double)$RequiredRatio, 2)?></span></li>
<?
	}
	if ($OwnProfile || ($Override = check_paranoia_here(false)) || check_perms('users_mod')) {
?>
				<li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>><a href="userhistory.php?action=token_history&amp;userid=<?=$UserID?>">Tokens</a>: <?=number_format($FLTokens)?></li>
<?
	}
	if (($OwnProfile || check_perms('users_mod')) && $Warned != '0000-00-00 00:00:00') {
?>
				<li<?=($Override === 2 ? ' class="paranoia_override"' : '')?>>Warning expires in: <?=time_diff((date('Y-m-d H:i', strtotime($Warned))))?></li>
<?	} ?>
			</ul>
		</div>
<?
// Last.fm statistics and comparability
$LastFMUsername = LastFM::get_lastfm_username($UserID);
if ($LastFMUsername)  {
	LastFMView::render_sidebar($LastFMUsername, $UserID, $OwnProfile);
}

if (check_paranoia_here('requestsfilled_count') || check_paranoia_here('requestsfilled_bounty')) {
	$DB->query("
		SELECT
			COUNT(DISTINCT r.ID),
			SUM(rv.Bounty)
		FROM requests AS r
			LEFT JOIN requests_votes AS rv ON r.ID = rv.RequestID
		WHERE r.FillerID = $UserID");
	list($RequestsFilled, $TotalBounty) = $DB->next_record();
} else {
	$RequestsFilled = $TotalBounty = 0;
}

if (check_paranoia_here('requestsvoted_count') || check_paranoia_here('requestsvoted_bounty')) {
	$DB->query("
		SELECT COUNT(RequestID), SUM(Bounty)
		FROM requests_votes
		WHERE UserID = $UserID");
	list($RequestsVoted, $TotalSpent) = $DB->next_record();
	$DB->query("
		SELECT COUNT(r.ID), SUM(rv.Bounty)
		FROM requests AS r
			LEFT JOIN requests_votes AS rv ON rv.RequestID = r.ID AND rv.UserID = r.UserID
		WHERE r.UserID = $UserID");
	list($RequestsCreated, $RequestsCreatedSpent) = $DB->next_record();
} else {
	$RequestsVoted = $TotalSpent = $RequestsCreated = $RequestsCreatedSpent = 0;
}

if (check_paranoia_here('uploads+')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM torrents
		WHERE UserID = '$UserID'");
	list($Uploads) = $DB->next_record();
} else {
	$Uploads = 0;
}

if (check_paranoia_here('artistsadded')) {
	$DB->query("
		SELECT COUNT(ArtistID)
		FROM torrents_artists
		WHERE UserID = $UserID");
	list($ArtistsAdded) = $DB->next_record();
} else {
	$ArtistsAdded = 0;
}

//Do the ranks
$UploadedRank = UserRank::get_rank('uploaded', $Uploaded);
$DownloadedRank = UserRank::get_rank('downloaded', $Downloaded);
$UploadsRank = UserRank::get_rank('uploads', $Uploads);
$RequestRank = UserRank::get_rank('requests', $RequestsFilled);
$PostRank = UserRank::get_rank('posts', $ForumPosts);
$BountyRank = UserRank::get_rank('bounty', $TotalSpent);
$ArtistsRank = UserRank::get_rank('artists', $ArtistsAdded);

if ($Downloaded == 0) {
	$Ratio = 1;
} elseif ($Uploaded == 0) {
	$Ratio = 0.5;
} else {
	$Ratio = round($Uploaded / $Downloaded, 2);
}
$OverallRank = UserRank::overall_score($UploadedRank, $DownloadedRank, $UploadsRank, $RequestRank, $PostRank, $BountyRank, $ArtistsRank, $Ratio);

?>
		<div class="box box_info box_userinfo_percentile">
			<div class="head colhead_dark">Percentile Rankings (hover for values)</div>
			<ul class="stats nobullet">
<?	if (($Override = check_paranoia_here('uploaded'))) { ?>
				<li class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>" title="<?=Format::get_size($Uploaded)?>">Data uploaded: <?=$UploadedRank === false ? 'Server busy' : number_format($UploadedRank)?></li>
<?
	}
	if (($Override = check_paranoia_here('downloaded'))) { ?>
				<li class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>" title="<?=Format::get_size($Downloaded)?>">Data downloaded: <?=$DownloadedRank === false ? 'Server busy' : number_format($DownloadedRank)?></li>
<?
	}
	if (($Override = check_paranoia_here('uploads+'))) { ?>
				<li class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>" title="<?=number_format($Uploads)?>">Torrents uploaded: <?=$UploadsRank === false ? 'Server busy' : number_format($UploadsRank)?></li>
<?
	}
	if (($Override = check_paranoia_here('requestsfilled_count'))) { ?>
				<li class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>" title="<?=number_format($RequestsFilled)?>">Requests filled: <?=$RequestRank === false ? 'Server busy' : number_format($RequestRank)?></li>
<?
	}
	if (($Override = check_paranoia_here('requestsvoted_bounty'))) { ?>
				<li class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>" title="<?=Format::get_size($TotalSpent)?>">Bounty spent: <?=$BountyRank === false ? 'Server busy' : number_format($BountyRank)?></li>
<?	} ?>
				<li class="tooltip" title="<?=number_format($ForumPosts)?>">Posts made: <?=$PostRank === false ? 'Server busy' : number_format($PostRank)?></li>
<?	if (($Override = check_paranoia_here('artistsadded'))) { ?>
				<li class="tooltip<?=($Override === 2 ? ' paranoia_override' : '')?>" title="<?=number_format($ArtistsAdded)?>">Artists added: <?=$ArtistsRank === false ? 'Server busy' : number_format($ArtistsRank)?></li>
<?
	}
	if (check_paranoia_here(array('uploaded', 'downloaded', 'uploads+', 'requestsfilled_count', 'requestsvoted_bounty', 'artistsadded'))) { ?>
				<li><strong>Overall rank: <?=$OverallRank === false ? 'Server busy' : number_format($OverallRank)?></strong></li>
<?	} ?>
			</ul>
		</div>
<?
	if (check_perms('users_mod', $Class) || check_perms('users_view_ips', $Class) || check_perms('users_view_keys', $Class)) {
		$DB->query("
			SELECT COUNT(*)
			FROM users_history_passwords
			WHERE UserID = '$UserID'");
		list($PasswordChanges) = $DB->next_record();
		if (check_perms('users_view_keys', $Class)) {
			$DB->query("
				SELECT COUNT(*)
				FROM users_history_passkeys
				WHERE UserID = '$UserID'");
			list($PasskeyChanges) = $DB->next_record();
		}
		if (check_perms('users_view_ips', $Class)) {
			$DB->query("
				SELECT COUNT(DISTINCT IP)
				FROM users_history_ips
				WHERE UserID = '$UserID'");
			list($IPChanges) = $DB->next_record();
			$DB->query("
				SELECT COUNT(DISTINCT IP)
				FROM xbt_snatched
				WHERE uid = '$UserID'
					AND IP != ''");
			list($TrackerIPs) = $DB->next_record();
		}
		if (check_perms('users_view_email', $Class)) {
			$DB->query("
				SELECT COUNT(*)
				FROM users_history_emails
				WHERE UserID = '$UserID'");
			list($EmailChanges) = $DB->next_record();
		}
?>
		<div class="box box_info box_userinfo_history">
			<div class="head colhead_dark">History</div>
			<ul class="stats nobullet">
<?		if (check_perms('users_view_email', $Class)) { ?>
				<li>Emails: <?=number_format($EmailChanges)?> <a href="userhistory.php?action=email2&amp;userid=<?=$UserID?>" class="brackets">View</a>&nbsp;<a href="userhistory.php?action=email&amp;userid=<?=$UserID?>" class="brackets">Legacy view</a></li>
<?
		}
		if (check_perms('users_view_ips', $Class)) {
?>
				<li>IPs: <?=number_format($IPChanges)?> <a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>" class="brackets">View</a>&nbsp;<a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>&amp;usersonly=1" class="brackets">View users</a></li>
<?			if (check_perms('users_view_ips', $Class) && check_perms('users_mod', $Class)) { ?>
				<li>Tracker IPs: <?=number_format($TrackerIPs)?> <a href="userhistory.php?action=tracker_ips&amp;userid=<?=$UserID?>" class="brackets">View</a></li>
<?
			}
		}
		if (check_perms('users_view_keys', $Class)) {
?>
				<li>Passkeys: <?=number_format($PasskeyChanges)?> <a href="userhistory.php?action=passkeys&amp;userid=<?=$UserID?>" class="brackets">View</a></li>
<?
		}
		if (check_perms('users_mod', $Class)) {
?>
				<li>Passwords: <?=number_format($PasswordChanges)?> <a href="userhistory.php?action=passwords&amp;userid=<?=$UserID?>" class="brackets">View</a></li>
				<li>Stats: N/A <a href="userhistory.php?action=stats&amp;userid=<?=$UserID?>" class="brackets">View</a></li>
<?		} ?>
			</ul>
		</div>
<?	} ?>
		<div class="box box_info box_userinfo_personal">
			<div class="head colhead_dark">Personal</div>
			<ul class="stats nobullet">
				<li>Class: <?=$ClassLevels[$Class]['Name']?></li>
<?
$UserInfo = Users::user_info($UserID);
if (!empty($UserInfo['ExtraClasses'])) {
?>
				<li>
					<ul class="stats">
<?
	foreach ($UserInfo['ExtraClasses'] as $PermID => $Val) {
		?>
						<li><?=$Classes[$PermID]['Name']?></li>
<?	} ?>
					</ul>
				</li>
<?
}
// An easy way for people to measure the paranoia of a user, for e.g. contest eligibility
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
?>
				<li>Paranoia level: <span class="tooltip" title="<?=$ParanoiaLevel?>"><?=$ParanoiaLevelText?></span></li>
<?	if (check_perms('users_view_email', $Class) || $OwnProfile) { ?>
				<li>Email: <a href="mailto:<?=display_str($Email)?>"><?=display_str($Email)?></a>
<?		if (check_perms('users_view_email', $Class)) { ?>
					<a href="user.php?action=search&amp;email_history=on&amp;email=<?=display_str($Email)?>" title="Search" class="brackets tooltip">S</a>
<?		} ?>
				</li>
<?	}

if (check_perms('users_view_ips', $Class)) {
?>
				<li>IP: <?=Tools::display_ip($IP)?></li>
				<li>Host: <?=Tools::get_host_by_ajax($IP)?></li>
<?
}

if (check_perms('users_view_keys', $Class) || $OwnProfile) {
?>
				<li>Passkey: <a href="#" id="passkey" onclick="togglePassKey('<?=display_str($torrent_pass)?>'); return false;" class="brackets">View</a></li>
<?
}
if (check_perms('users_view_invites')) {
	if (!$InviterID) {
		$Invited = '<span style="font-style: italic;">Nobody</span>';
	} else {
		$Invited = "<a href=\"user.php?id=$InviterID\">$InviterName</a>";
	}

?>
				<li>Invited by: <?=$Invited?></li>
				<li>Invites: <?
				$DB->query("
					SELECT COUNT(InviterID)
					FROM invites
					WHERE InviterID = '$UserID'");
				list($Pending) = $DB->next_record();
				if ($DisableInvites) {
					echo 'X';
				} else {
					echo number_format($Invites);
				}
				echo " ($Pending)"
				?></li>
<?
}

if (!isset($SupportFor)) {
	$DB->query('
		SELECT SupportFor
		FROM users_info
		WHERE UserID = '.$LoggedUser['ID']);
	list($SupportFor) = $DB->next_record();
}
if ($Override = check_perms('users_mod') || $OwnProfile || !empty($SupportFor)) {
?>
				<li<?=(($Override === 2 || $SupportFor) ? ' class="paranoia_override"' : '')?>>Clients: <?
		$DB->query("
			SELECT DISTINCT useragent
			FROM xbt_files_users
			WHERE uid = $UserID");
		$Clients = $DB->collect(0);
		echo implode('; ', $Clients);
		?></li>
<?
}
?>
			</ul>
		</div>
<?
include(SERVER_ROOT.'/sections/user/community_stats.php');
DonationsView::render_donor_stats($UserID);
?>
	</div>
	<div class="main_column">
<?
if ($RatioWatchEnds != '0000-00-00 00:00:00'
		&& (time() < strtotime($RatioWatchEnds))
		&& ($Downloaded * $RequiredRatio) > $Uploaded
		) {
?>
		<div class="box">
			<div class="head">Ratio watch</div>
			<div class="pad">This user is currently on ratio watch and must upload <?=Format::get_size(($Downloaded * $RequiredRatio) - $Uploaded)?> in the next <?=time_diff($RatioWatchEnds)?>, or their leeching privileges will be revoked. Amount downloaded while on ratio watch: <?=Format::get_size($Downloaded - $RatioWatchDownload)?></div>
		</div>
<?
}
?>
		<div class="box">
			<div class="head">
				<?=!empty($InfoTitle) ? $InfoTitle : 'Profile';?>
				<span style="float: right;"><a href="#" onclick="$('#profilediv').gtoggle(); this.innerHTML = (this.innerHTML == 'Hide' ? 'Show' : 'Hide'); return false;" class="brackets">Hide</a></span>&nbsp;
			</div>
			<div class="pad profileinfo" id="profilediv">
<?
if (!$Info) {
?>
				This profile is currently empty.
<?
} else {
	echo Text::full_format($Info);
}
?>
			</div>
		</div>
<?
DonationsView::render_profile_rewards($EnabledRewards, $ProfileRewards);

if (check_paranoia_here('snatched')) {
	$RecentSnatches = $Cache->get_value("recent_snatches_$UserID");
	if ($RecentSnatches === false) {
		$DB->query("
			SELECT
				g.ID,
				g.Name,
				g.WikiImage
			FROM xbt_snatched AS s
				INNER JOIN torrents AS t ON t.ID = s.fid
				INNER JOIN torrents_group AS g ON t.GroupID = g.ID
			WHERE s.uid = '$UserID'
				AND g.CategoryID = '1'
				AND g.WikiImage != ''
			GROUP BY g.ID
			ORDER BY s.tstamp DESC
			LIMIT 5");
		$RecentSnatches = $DB->to_array();

		$Artists = Artists::get_artists($DB->collect('ID'));
		foreach ($RecentSnatches as $Key => $SnatchInfo) {
			$RecentSnatches[$Key]['Artist'] = Artists::display_artists($Artists[$SnatchInfo['ID']], false, true);
		}
		$Cache->cache_value("recent_snatches_$UserID", $RecentSnatches, 0); //inf cache
	}
	if (!empty($RecentSnatches)) {
?>
	<table class="layout recent" id="recent_snatches" cellpadding="0" cellspacing="0" border="0">
		<tr class="colhead">
			<td colspan="5">
				Recent Snatches
			</td>
		</tr>
		<tr>
<?		foreach ($RecentSnatches as $RS) { ?>
			<td>
				<a href="torrents.php?id=<?=$RS['ID']?>">
					<img class="tooltip" title="<?=display_str($RS['Artist'])?><?=display_str($RS['Name'])?>" src="<?=ImageTools::process($RS['WikiImage'], true)?>" alt="<?=display_str($RS['Artist'])?><?=display_str($RS['Name'])?>" width="107" />
				</a>
			</td>
<?		} ?>
		</tr>
	</table>
<?
	}
}

if (check_paranoia_here('uploads')) {
	$RecentUploads = $Cache->get_value("recent_uploads_$UserID");
	if ($RecentUploads === false) {
		$DB->query("
			SELECT
				g.ID,
				g.Name,
				g.WikiImage
			FROM torrents_group AS g
				INNER JOIN torrents AS t ON t.GroupID = g.ID
			WHERE t.UserID = '$UserID'
				AND g.CategoryID = '1'
				AND g.WikiImage != ''
			GROUP BY g.ID
			ORDER BY t.Time DESC
			LIMIT 5");
		$RecentUploads = $DB->to_array();
		$Artists = Artists::get_artists($DB->collect('ID'));
		foreach ($RecentUploads as $Key => $UploadInfo) {
			$RecentUploads[$Key]['Artist'] = Artists::display_artists($Artists[$UploadInfo['ID']], false, true);
		}
		$Cache->cache_value("recent_uploads_$UserID", $RecentUploads, 0); //inf cache
	}
	if (!empty($RecentUploads)) {
?>
	<table class="layout recent" id="recent_uploads" cellpadding="0" cellspacing="0" border="0">
		<tr class="colhead">
			<td colspan="5">
				Recent Uploads
			</td>
		</tr>
		<tr>
<?		foreach ($RecentUploads as $RU) { ?>
			<td>
				<a href="torrents.php?id=<?=$RU['ID']?>">
					<img class="tooltip" title="<?=$RU['Artist']?><?=$RU['Name']?>" src="<?=ImageTools::process($RU['WikiImage'], true)?>" alt="<?=$RU['Artist']?><?=$RU['Name']?>" width="107" />
				</a>
			</td>
<?		} ?>
		</tr>
	</table>
<?
	}
}

$DB->query("
	SELECT ID, Name
	FROM collages
	WHERE UserID = '$UserID'
		AND CategoryID = '0'
		AND Deleted = '0'
	ORDER BY Featured DESC,
		Name ASC");
$Collages = $DB->to_array(false, MYSQLI_NUM, false);
$FirstCol = true;
foreach ($Collages as $CollageInfo) {
	list($CollageID, $CName) = $CollageInfo;
	$DB->query("
		SELECT ct.GroupID,
			tg.WikiImage,
			tg.CategoryID
		FROM collages_torrents AS ct
			JOIN torrents_group AS tg ON tg.ID = ct.GroupID
		WHERE ct.CollageID = '$CollageID'
		ORDER BY ct.Sort
		LIMIT 5");
	$Collage = $DB->to_array(false, MYSQLI_ASSOC, false);
?>
	<table class="layout recent" id="collage<?=$CollageID?>_box" cellpadding="0" cellspacing="0" border="0">
		<tr class="colhead">
			<td colspan="5">
				<span style="float: left;">
					<?=display_str($CName)?> - <a href="collages.php?id=<?=$CollageID?>" class="brackets">See full</a>
				</span>
				<span style="float: right;">
					<a href="#" onclick="$('#collage<?=$CollageID?>_box .images').gtoggle(); this.innerHTML = (this.innerHTML == 'Hide' ? 'Show' : 'Hide'); return false;" class="brackets"><?=$FirstCol ? 'Hide' : 'Show' ?></a>
				</span>
			</td>
		</tr>
		<tr class="images<?=$FirstCol ? '' : ' hidden'?>">
<?	foreach ($Collage as $C) {
			$Group = Torrents::get_groups(array($C['GroupID']), true, true, false);
			extract(Torrents::array_group($Group[$C['GroupID']]));

			$Name = '';
			$Name .= Artists::display_artists(array('1' => $Artists), false, true);
			$Name .= $GroupName;
?>
			<td>
				<a href="torrents.php?id=<?=$GroupID?>">
					<img class="tooltip" title="<?=$Name?>" src="<?=ImageTools::process($C['WikiImage'], true)?>" alt="<?=$Name?>" width="107" />
				</a>
			</td>
<?	} ?>
		</tr>
	</table>
<?
	$FirstCol = false;
}
?>
	<!-- for the "jump to staff tools" button -->
	<a id="staff_tools"></a>
<?

// Linked accounts
if (check_perms('users_mod')) {
	include(SERVER_ROOT.'/sections/user/linkedfunctions.php');
	user_dupes_table($UserID);
}

if ((check_perms('users_view_invites')) && $Invited > 0) {
	include(SERVER_ROOT.'/classes/invite_tree.class.php');
	$Tree = new INVITE_TREE($UserID, array('visible' => false));
?>
		<div class="box" id="invitetree_box">
			<div class="head">
				Invite Tree <a href="#" onclick="$('#invitetree').gtoggle(); return false;" class="brackets">View</a>
			</div>
			<div id="invitetree" class="hidden">
<?				$Tree->make_tree(); ?>
			</div>
		</div>
<?
}

if (check_perms('users_mod')) {
	DonationsView::render_donation_history(Donations::get_donation_history($UserID));
}

// Requests
if (empty($LoggedUser['DisableRequests']) && check_paranoia_here('requestsvoted_list')) {
	$SphQL = new SphinxqlQuery();
	$SphQLResult = $SphQL->select('id, votes, bounty')
		->from('requests, requests_delta')
		->where('userid', $UserID)
		->where('torrentid', 0)
		->order_by('votes', 'desc')
		->order_by('bounty', 'desc')
		->limit(0, 100, 100) // Limit to 100 requests
		->query();
	if ($SphQLResult->has_results()) {
		$SphRequests = $SphQLResult->to_array('id', MYSQLI_ASSOC);
?>
		<div class="box" id="requests_box">
			<div class="head">
				Requests <a href="#" onclick="$('#requests').gtoggle(); return false;" class="brackets">View</a>
			</div>
			<div id="requests" class="request_table hidden">
				<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
					<tr class="colhead_dark">
						<td style="width: 48%;">
							<strong>Request Name</strong>
						</td>
						<td>
							<strong>Vote</strong>
						</td>
						<td>
							<strong>Bounty</strong>
						</td>
						<td>
							<strong>Added</strong>
						</td>
					</tr>
<?
		$Row = 'a';
		$Requests = Requests::get_requests(array_keys($SphRequests));
		foreach ($SphRequests as $RequestID => $SphRequest) {
			$Request = $Requests[$RequestID];
			$VotesCount = $SphRequest['votes'];
			$Bounty = $SphRequest['bounty'] * 1024; // Sphinx stores bounty in kB
			$CategoryName = $Categories[$Request['CategoryID'] - 1];

			if ($CategoryName == 'Music') {
				$ArtistForm = Requests::get_artists($RequestID);
				$ArtistLink = Artists::display_artists($ArtistForm, true, true);
				$FullName = "$ArtistLink<a href=\"requests.php?action=view&amp;id=$RequestID\">$Request[Title] [$Request[Year]]</a>";
			} elseif ($CategoryName == 'Audiobooks' || $CategoryName == 'Comedy') {
				$FullName = "<a href=\"requests.php?action=view&amp;id=$RequestID\">$Request[Title] [$Request[Year]]</a>";
			} else {
				$FullName = "<a href=\"requests.php?action=view&amp;id=$RequestID\">$Request[Title]</a>";
			}
?>
					<tr class="row<?=$Row === 'b' ? 'a' : 'b'?>">
						<td>
							<?=$FullName ?>
							<div class="tags">
<?
			$Tags = $Request['Tags'];
			$TagList = array();
			foreach ($Tags as $TagID => $TagName) {
				$TagList[] = "<a href=\"requests.php?tags=$TagName\">".display_str($TagName).'</a>';
			}
			$TagList = implode(', ', $TagList);
?>
								<?=$TagList?>
							</div>
						</td>
						<td>
							<span id="vote_count_<?=$RequestID?>"><?=$VotesCount?></span>
<?			if (check_perms('site_vote')) { ?>
							&nbsp;&nbsp; <a href="javascript:Vote(0, <?=$RequestID?>)" class="brackets">+</a>
<?			} ?>
						</td>
						<td>
							<span id="bounty_<?=$RequestID?>"><?=Format::get_size($Bounty)?></span>
						</td>
						<td>
							<?=time_diff($Request['TimeAdded']) ?>
						</td>
					</tr>
<?		} ?>
				</table>
			</div>
		</div>
<?
	}
}

$IsFLS = isset($LoggedUser['ExtraClasses'][FLS_TEAM]);
if (check_perms('users_mod', $Class) || $IsFLS) {
	$UserLevel = $LoggedUser['EffectiveClass'];
	$DB->query("
		SELECT
			SQL_CALC_FOUND_ROWS
			ID,
			Subject,
			Status,
			Level,
			AssignedToUser,
			Date,
			ResolverID
		FROM staff_pm_conversations
		WHERE UserID = $UserID
			AND (Level <= $UserLevel OR AssignedToUser = '".$LoggedUser['ID']."')
		ORDER BY Date DESC");
	if ($DB->has_results()) {
		$StaffPMs = $DB->to_array();
?>
		<div class="box" id="staffpms_box">
			<div class="head">
				Staff PMs <a href="#" onclick="$('#staffpms').gtoggle(); return false;" class="brackets">View</a>
			</div>
			<table width="100%" class="message_table hidden" id="staffpms">
				<tr class="colhead">
					<td>Subject</td>
					<td>Date</td>
					<td>Assigned to</td>
					<td>Resolved by</td>
				</tr>
<?
		foreach ($StaffPMs as $StaffPM) {
			list($ID, $Subject, $Status, $Level, $AssignedToUser, $Date, $ResolverID) = $StaffPM;
			// Get assigned
			if ($AssignedToUser == '') {
				// Assigned to class
				$Assigned = ($Level == 0) ? 'First Line Support' : $ClassLevels[$Level]['Name'];
				// No + on Sysops
				if ($Assigned != 'Sysop') {
					$Assigned .= '+';
				}

			} else {
				// Assigned to user
				$Assigned = Users::format_username($UserID, true, true, true, true);
			}

			if ($ResolverID) {
				$Resolver = Users::format_username($ResolverID, true, true, true, true);
			} else {
				$Resolver = '(unresolved)';
			}

			?>
				<tr>
					<td><a href="staffpm.php?action=viewconv&amp;id=<?=$ID?>"><?=display_str($Subject)?></a></td>
					<td><?=time_diff($Date, 2, true)?></td>
					<td><?=$Assigned?></td>
					<td><?=$Resolver?></td>
				</tr>
<?		} ?>
			</table>
		</div>
<?
	}
}

// Displays a table of forum warnings viewable only to Forum Moderators
if ($LoggedUser['Class'] == 650 && check_perms('users_warn', $Class)) {
	$DB->query("
		SELECT Comment
		FROM users_warnings_forums
		WHERE UserID = '$UserID'");
	list($ForumWarnings) = $DB->next_record();
	if ($DB->has_results()) {
?>
<div class="box">
	<div class="head">Forum warnings</div>
	<div class="pad">
		<div id="forumwarningslinks" class="AdminComment" style="width: 98%;"><?=Text::full_format($ForumWarnings)?></div>
	</div>
</div>
<?
	}
}
if (check_perms('users_mod', $Class)) { ?>
		<form class="manage_form" name="user" id="form" action="user.php" method="post">
		<input type="hidden" name="action" value="moderate" />
		<input type="hidden" name="userid" value="<?=$UserID?>" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />

		<div class="box box2" id="staff_notes_box">
			<div class="head">
				Staff Notes
				<a href="#" name="admincommentbutton" onclick="ChangeTo('text'); return false;" class="brackets">Edit</a>
				<a href="#" onclick="$('#staffnotes').gtoggle(); return false;" class="brackets">Toggle</a>
			</div>
			<div id="staffnotes" class="pad">
				<input type="hidden" name="comment_hash" value="<?=$CommentHash?>" />
				<div id="admincommentlinks" class="AdminComment" style="width: 98%;"><?=Text::full_format($AdminComment)?></div>
				<textarea id="admincomment" onkeyup="resize('admincomment');" class="AdminComment hidden" name="AdminComment" cols="65" rows="26" style="width: 98%;"><?=display_str($AdminComment)?></textarea>
				<a href="#" name="admincommentbutton" onclick="ChangeTo('text'); return false;" class="brackets">Toggle edit</a>
				<script type="text/javascript">
					resize('admincomment');
				</script>
			</div>
		</div>

		<table class="layout" id="user_info_box">
			<tr class="colhead">
				<td colspan="2">
					User Information
				</td>
			</tr>
<?	if (check_perms('users_edit_usernames', $Class)) { ?>
			<tr>
				<td class="label">Username:</td>
				<td><input type="text" size="20" name="Username" value="<?=display_str($Username)?>" /></td>
			</tr>
<?
	}
	if (check_perms('users_edit_titles')) {
?>
			<tr>
				<td class="label">Custom title:</td>
				<td><input type="text" class="wide_input_text" name="Title" value="<?=display_str($CustomTitle)?>" /></td>
			</tr>
<?
	}

	if (check_perms('users_promote_below', $Class) || check_perms('users_promote_to', $Class - 1)) {
?>
			<tr>
				<td class="label">Primary class:</td>
				<td>
					<select name="Class">
<?
		foreach ($ClassLevels as $CurClass) {
			if (check_perms('users_promote_below', $Class) && $CurClass['ID'] >= $LoggedUser['EffectiveClass']) {
				break;
			}
			if ($CurClass['ID'] > $LoggedUser['EffectiveClass']) {
				break;
			}
			if ($CurClass['Secondary']) {
				continue;
			}
			if ($Class === $CurClass['Level']) {
				$Selected = ' selected="selected"';
			} else {
				$Selected = '';
			}
?>
						<option value="<?=$CurClass['ID']?>"<?=$Selected?>><?=$CurClass['Name'].' ('.$CurClass['Level'].')'?></option>
<?		} ?>
					</select>
				</td>
			</tr>
<?
	}

	if (check_perms('users_give_donor')) {
?>
			<tr>
				<td class="label">Donor:</td>
				<td><input type="checkbox" name="Donor"<? if ($Donor == 1) { ?> checked="checked"<? } ?> /></td>
			</tr>
<?
	}
	if (check_perms('users_promote_below') || check_perms('users_promote_to')) { ?>
		<tr>
			<td class="label">Secondary classes:</td>
			<td>
<?
		$DB->query("
			SELECT p.ID, p.Name, l.UserID
			FROM permissions AS p
				LEFT JOIN users_levels AS l ON l.PermissionID = p.ID AND l.UserID = '$UserID'
			WHERE p.Secondary = 1
			ORDER BY p.Name");
		$i = 0;
		while (list($PermID, $PermName, $IsSet) = $DB->next_record()) {
			$i++;
?>
				<input type="checkbox" id="perm_<?=$PermID?>" name="secondary_classes[]" value="<?=$PermID?>"<? if ($IsSet) { ?> checked="checked"<? } ?> />&nbsp;<label for="perm_<?=$PermID?>" style="margin-right: 10px;"><?=$PermName?></label>
<?			if ($i % 3 == 0) {
				echo "\t\t\t\t<br />\n";
			}
		} ?>
			</td>
		</tr>
<?	}
	if (check_perms('users_make_invisible')) {
?>
			<tr>
				<td class="label">Visible in peer lists:</td>
				<td><input type="checkbox" name="Visible"<? if ($Visible == 1) { ?> checked="checked"<? } ?> /></td>
			</tr>
<?
	}

	if (check_perms('users_edit_ratio', $Class) || (check_perms('users_edit_own_ratio') && $UserID == $LoggedUser['ID'])) {
?>
			<tr>
				<td class="label tooltip" title="Upload amount in bytes. Also accepts e.g. +20GB or -35.6364MB on the end.">Uploaded:</td>
				<td>
					<input type="hidden" name="OldUploaded" value="<?=$Uploaded?>" />
					<input type="text" size="20" name="Uploaded" value="<?=$Uploaded?>" />
				</td>
			</tr>
			<tr>
				<td class="label tooltip" title="Download amount in bytes. Also accepts e.g. +20GB or -35.6364MB on the end.">Downloaded:</td>
				<td>
					<input type="hidden" name="OldDownloaded" value="<?=$Downloaded?>" />
					<input type="text" size="20" name="Downloaded" value="<?=$Downloaded?>" />
				</td>
			</tr>
			<tr>
				<td class="label tooltip" title="Enter a username.">Merge stats <strong>from:</strong></td>
				<td>
					<input type="text" size="40" name="MergeStatsFrom" />
				</td>
			</tr>
			<tr>
				<td class="label">Freeleech tokens:</td>
				<td>
					<input type="text" size="5" name="FLTokens" value="<?=$FLTokens?>" />
				</td>
			</tr>
<?
	}

	if (check_perms('users_edit_invites')) {
?>
			<tr>
				<td class="label tooltip" title="Number of invites">Invites:</td>
				<td><input type="text" size="5" name="Invites" value="<?=$Invites?>" /></td>
			</tr>
<?
	}

	if (check_perms('admin_manage_fls') || (check_perms('users_mod') && $OwnProfile)) {
?>
			<tr>
				<td class="label tooltip" title="This is the message shown in the right-hand column on /staff.php">FLS/Staff remark:</td>
				<td><input type="text" class="wide_input_text" name="SupportFor" value="<?=display_str($SupportFor)?>" /></td>
			</tr>
<?
	}

	if (check_perms('users_edit_reset_keys')) {
?>
			<tr>
				<td class="label">Reset:</td>
				<td>
					<input type="checkbox" name="ResetRatioWatch" id="ResetRatioWatch" /> <label for="ResetRatioWatch">Ratio watch</label> |
					<input type="checkbox" name="ResetPasskey" id="ResetPasskey" /> <label for="ResetPasskey">Passkey</label> |
					<input type="checkbox" name="ResetAuthkey" id="ResetAuthkey" /> <label for="ResetAuthkey">Authkey</label> |
					<input type="checkbox" name="ResetIPHistory" id="ResetIPHistory" /> <label for="ResetIPHistory">IP history</label> |
					<input type="checkbox" name="ResetEmailHistory" id="ResetEmailHistory" /> <label for="ResetEmailHistory">Email history</label>
					<br />
					<input type="checkbox" name="ResetSnatchList" id="ResetSnatchList" /> <label for="ResetSnatchList">Snatch list</label> |
					<input type="checkbox" name="ResetDownloadList" id="ResetDownloadList" /> <label for="ResetDownloadList">Download list</label>
				</td>
			</tr>
<?
	}

	if (check_perms('users_mod')) {
?>
		<tr>
			<td class="label">Reset all EAC v0.95 logs to:</td>
			<td>
				<select name="095logs">
					<option value=""></option>
					<option value="99">99</option>
					<option value="100">100</option>
				</select>
			</td>
		</tr>
<?
	}

	if (check_perms('users_edit_password')) {
?>
			<tr>
				<td class="label">New password:</td>
				<td>
					<input type="text" size="30" id="change_password" name="ChangePassword" />
					<button type="button" id="random_password">Generate</button>
				</td>
			</tr>
<?	} ?>
		</table>

<?	if (check_perms('users_warn')) { ?>
		<table class="layout" id="warn_user_box">
			<tr class="colhead">
				<td colspan="2">
					Warnings
				</td>
			</tr>
			<tr>
				<td class="label">Warned:</td>
				<td>
					<input type="checkbox" name="Warned"<? if ($Warned != '0000-00-00 00:00:00') { ?> checked="checked"<? } ?> />
				</td>
			</tr>
<?		if ($Warned == '0000-00-00 00:00:00') { // user is not warned ?>
			<tr>
				<td class="label">Expiration:</td>
				<td>
					<select name="WarnLength">
						<option value="">---</option>
						<option value="1">1 week</option>
						<option value="2">2 weeks</option>
						<option value="4">4 weeks</option>
						<option value="8">8 weeks</option>
					</select>
				</td>
			</tr>
<?		} else { // user is warned ?>
			<tr>
				<td class="label">Extension:</td>
				<td>
					<select name="ExtendWarning" onchange="ToggleWarningAdjust(this);">
						<option>---</option>
						<option value="1">1 week</option>
						<option value="2">2 weeks</option>
						<option value="4">4 weeks</option>
						<option value="8">8 weeks</option>
					</select>
				</td>
			</tr>
			<tr id="ReduceWarningTR">
				<td class="label">Reduction:</td>
				<td>
					<select name="ReduceWarning">
						<option>---</option>
						<option value="1">1 week</option>
						<option value="2">2 weeks</option>
						<option value="4">4 weeks</option>
						<option value="8">8 weeks</option>
					</select>
				</td>
			</tr>
<?		} ?>
			<tr>
				<td class="label tooltip" title="This message *will* be sent to the user in the warning PM!">Warning reason:</td>
				<td>
					<input type="text" class="wide_input_text" name="WarnReason" />
				</td>
			</tr>
<?	} ?>
		</table>
		<table class="layout" id="user_privs_box">
			<tr class="colhead">
				<td colspan="2">
					User Privileges
				</td>
			</tr>
<?	if (check_perms('users_disable_posts') || check_perms('users_disable_any')) {
		$DB->query("
			SELECT DISTINCT Email, IP
			FROM users_history_emails
			WHERE UserID = $UserID
			ORDER BY Time ASC");
		$Emails = $DB->to_array();
?>
			<tr>
				<td class="label">Disable:</td>
				<td>
					<input type="checkbox" name="DisablePosting" id="DisablePosting"<? if ($DisablePosting == 1) { ?> checked="checked"<? } ?> /> <label for="DisablePosting">Posting</label>
<?		if (check_perms('users_disable_any')) { ?> |
					<input type="checkbox" name="DisableAvatar" id="DisableAvatar"<? if ($DisableAvatar == 1) { ?> checked="checked"<? } ?> /> <label for="DisableAvatar">Avatar</label> |
					<input type="checkbox" name="DisableForums" id="DisableForums"<? if ($DisableForums == 1) { ?> checked="checked"<? } ?> /> <label for="DisableForums">Forums</label> |
					<input type="checkbox" name="DisableIRC" id="DisableIRC"<? if ($DisableIRC == 1) { ?> checked="checked"<? } ?> /> <label for="DisableIRC">IRC</label> |
					<input type="checkbox" name="DisablePM" id="DisablePM"<? if ($DisablePM == 1) { ?> checked="checked"<? } ?> /> <label for="DisablePM">PM</label> |
					<br /><br />

					<input type="checkbox" name="DisableLeech" id="DisableLeech"<? if ($DisableLeech == 0) { ?> checked="checked"<? } ?> /> <label for="DisableLeech">Leech</label> |
					<input type="checkbox" name="DisableRequests" id="DisableRequests"<? if ($DisableRequests == 1) { ?> checked="checked"<? } ?> /> <label for="DisableRequests">Requests</label> |
					<input type="checkbox" name="DisableUpload" id="DisableUpload"<? if ($DisableUpload == 1) { ?> checked="checked"<? } ?> /> <label for="DisableUpload">Torrent upload</label>
					<br /><br />

					<input type="checkbox" name="DisableTagging" id="DisableTagging"<? if ($DisableTagging == 1) { ?> checked="checked"<? } ?> /> <label for="DisableTagging" class="tooltip" title="This only disables a user's ability to delete tags.">Tagging</label> |
					<input type="checkbox" name="DisableWiki" id="DisableWiki"<? if ($DisableWiki == 1) { ?> checked="checked"<? } ?> /> <label for="DisableWiki">Wiki</label>
					<br /><br />

					<input type="checkbox" name="DisableInvites" id="DisableInvites"<? if ($DisableInvites == 1) { ?> checked="checked"<? } ?> /> <label for="DisableInvites">Invites</label>
				</td>
			</tr>
			<tr>
				<td class="label">Hacked:</td>
				<td>
					<input type="checkbox" name="SendHackedMail" id="SendHackedMail" /> <label for="SendHackedMail">Send hacked account email</label> to
					<select name="HackedEmail">
<?
			foreach ($Emails as $Email) {
				list($Address, $IP) = $Email;
?>
						<option value="<?=display_str($Address)?>"><?=display_str($Address)?> - <?=display_str($IP)?></option>
<?			} ?>
					</select>
				</td>
			</tr>

<?
		}
	}

	if (check_perms('users_disable_any')) {
?>
			<tr>
				<td class="label">Account:</td>
				<td>
					<select name="UserStatus">
						<option value="0"<? if ($Enabled == '0') { ?> selected="selected"<? } ?>>Unconfirmed</option>
						<option value="1"<? if ($Enabled == '1') { ?> selected="selected"<? } ?>>Enabled</option>
						<option value="2"<? if ($Enabled == '2') { ?> selected="selected"<? } ?>>Disabled</option>
<?		if (check_perms('users_delete_users')) { ?>
						<optgroup label="-- WARNING --">
							<option value="delete">Delete account</option>
						</optgroup>
<?		} ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">User reason:</td>
				<td>
					<input type="text" class="wide_input_text" name="UserReason" />
				</td>
			</tr>
			<tr>
				<td class="label tooltip" title="Enter a comma-delimited list of forum IDs.">Restricted forums:</td>
				<td>
					<input type="text" class="wide_input_text" name="RestrictedForums" value="<?=display_str($RestrictedForums)?>" />
				</td>
			</tr>
			<tr>
				<td class="label tooltip" title="Enter a comma-delimited list of forum IDs.">Extra forums:</td>
				<td>
					<input type="text" class="wide_input_text" name="PermittedForums" value="<?=display_str($PermittedForums)?>" />
				</td>
			</tr>

<?	} ?>
		</table>
<?	if (check_perms('users_logout')) { ?>
		<table class="layout" id="session_box">
			<tr class="colhead">
				<td colspan="2">
					Session
				</td>
			</tr>
			<tr>
				<td class="label">Reset session:</td>
				<td><input type="checkbox" name="ResetSession" id="ResetSession" /></td>
			</tr>
			<tr>
				<td class="label">Log out:</td>
				<td><input type="checkbox" name="LogOut" id="LogOut" /></td>
			</tr>
		</table>
<?
	}
	if (check_perms('users_mod')) {
		DonationsView::render_mod_donations($UserID);
	}
?>
		<table class="layout" id="submit_box">
			<tr class="colhead">
				<td colspan="2">
					Submit
				</td>
			</tr>
			<tr>
				<td class="label tooltip" title="This message will be entered into staff notes only.">Reason:</td>
				<td>
					<textarea rows="1" cols="35" class="wide_input_text" name="Reason" id="Reason" onkeyup="resize('Reason');"></textarea>
				</td>
			</tr>
			<tr>
				<td class="label">Paste user stats:</td>
				<td>
					<button type="button" id="paster">Paste</button>
				</td>
			</tr>

			<tr>
				<td align="right" colspan="2">
					<input type="submit" value="Save changes" />
				</td>
			</tr>
		</table>
		</form>
<?
}
?>
	</div>
</div>
<? View::show_footer(); ?>
