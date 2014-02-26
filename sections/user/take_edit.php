<?
authorize();

$UserID = $_REQUEST['userid'];
if (!is_number($UserID)) {
	error(404);
}

//For this entire page, we should generally be using $UserID not $LoggedUser['ID'] and $U[] not $LoggedUser[]
$U = Users::user_info($UserID);

if (!$U) {
	error(404);
}

$Permissions = Permissions::get_permissions($U['PermissionID']);
if ($UserID != $LoggedUser['ID'] && !check_perms('users_edit_profiles', $Permissions['Class'])) {
	send_irc('PRIVMSG '.ADMIN_CHAN.' :User '.$LoggedUser['Username'].' ('.site_url().'user.php?id='.$LoggedUser['ID'].') just tried to edit the profile of '.site_url().'user.php?id='.$_REQUEST['userid']);
	error(403);
}

$Val->SetFields('stylesheet', 1, "number", "You forgot to select a stylesheet.");
$Val->SetFields('styleurl', 0, "regex", "You did not enter a valid stylesheet URL.", array('regex' => '/^'.CSS_REGEX.'$/i'));
// The next two are commented out because the drop-down menus were replaced with a check box and radio buttons
//$Val->SetFields('disablegrouping', 0, "number", "You forgot to select your torrent grouping option.");
//$Val->SetFields('torrentgrouping', 0, "number", "You forgot to select your torrent grouping option.");
$Val->SetFields('discogview', 1, "number", "You forgot to select your discography view option.", array('minlength' => 0, 'maxlength' => 1));
$Val->SetFields('postsperpage', 1, "number", "You forgot to select your posts per page option.", array('inarray' => array(25, 50, 100)));
//$Val->SetFields('hidecollage', 1, "number", "You forgot to select your collage option.", array('minlength' => 0, 'maxlength' => 1));
$Val->SetFields('collagecovers', 1, "number", "You forgot to select your collage option.");
$Val->SetFields('avatar', 0, "regex", "You did not enter a valid avatar URL.", array('regex' => "/^".IMAGE_REGEX."$/i"));
$Val->SetFields('email', 1, "email", "You did not enter a valid email address.");
$Val->SetFields('irckey', 0, "string", "You did not enter a valid IRC key. An IRC key must be between 6 and 32 characters long.", array('minlength' => 6, 'maxlength' => 32));
$Val->SetFields('new_pass_1', 0, "regex", "You did not enter a valid password. A strong password is 8 characters or longer, contains at least 1 lowercase and uppercase letter, and contains at least a number or symbol.", array('regex' => '/(?=^.{8,}$)(?=.*[^a-zA-Z])(?=.*[A-Z])(?=.*[a-z]).*$/'));
$Val->SetFields('new_pass_2', 1, "compare", "Your passwords do not match.", array('comparefield' => 'new_pass_1'));
if (check_perms('site_advanced_search')) {
	$Val->SetFields('searchtype', 1, "number", "You forgot to select your default search preference.", array('minlength' => 0, 'maxlength' => 1));
}

$Err = $Val->ValidateForm($_POST);
if ($Err) {
	error($Err);
	header("Location: user.php?action=edit&userid=$UserID");
	die();
}

// Begin building $Paranoia
// Reduce the user's input paranoia until it becomes consistent
if (isset($_POST['p_uniquegroups_l'])) {
	$_POST['p_uploads_l'] = 'on';
	$_POST['p_uploads_c'] = 'on';
}

if (isset($_POST['p_uploads_l'])) {
	$_POST['p_uniquegroups_l'] = 'on';
	$_POST['p_uniquegroups_c'] = 'on';
	$_POST['p_perfectflacs_l'] = 'on';
	$_POST['p_perfectflacs_c'] = 'on';
	$_POST['p_artistsadded'] = 'on';
}

if (isset($_POST['p_collagecontribs_l'])) {
	$_POST['p_collages_l'] = 'on';
	$_POST['p_collages_c'] = 'on';
}

if (isset($_POST['p_snatched_c']) && isset($_POST['p_seeding_c']) && isset($_POST['p_downloaded'])) {
	$_POST['p_requiredratio'] = 'on';
}

// if showing exactly 2 of stats, show all 3 of stats
$StatsShown = 0;
$Stats = array('downloaded', 'uploaded', 'ratio');
foreach ($Stats as $S) {
	if (isset($_POST["p_$S"])) {
		$StatsShown++;
	}
}

if ($StatsShown == 2) {
	foreach ($Stats as $S) {
		$_POST["p_$S"] = 'on';
	}
}

$Paranoia = array();
$Checkboxes = array('downloaded', 'uploaded', 'ratio', 'lastseen', 'requiredratio', 'invitedcount', 'artistsadded', 'notifications');
foreach ($Checkboxes as $C) {
	if (!isset($_POST["p_$C"])) {
		$Paranoia[] = $C;
	}
}

$SimpleSelects = array('torrentcomments', 'collages', 'collagecontribs', 'uploads', 'uniquegroups', 'perfectflacs', 'seeding', 'leeching', 'snatched');
foreach ($SimpleSelects as $S) {
	if (!isset($_POST["p_$S".'_c']) && !isset($_POST["p_$S".'_l'])) {
		// Very paranoid - don't show count or list
		$Paranoia[] = "$S+";
	} elseif (!isset($_POST["p_$S".'_l'])) {
		// A little paranoid - show count, don't show list
		$Paranoia[] = $S;
	}
}

$Bounties = array('requestsfilled', 'requestsvoted');
foreach ($Bounties as $B) {
	if (isset($_POST["p_$B".'_list'])) {
		$_POST["p_$B".'_count'] = 'on';
		$_POST["p_$B".'_bounty'] = 'on';
	}
	if (!isset($_POST["p_$B".'_list'])) {
		$Paranoia[] = $B.'_list';
	}
	if (!isset($_POST["p_$B".'_count'])) {
		$Paranoia[] = $B.'_count';
	}
	if (!isset($_POST["p_$B".'_bounty'])) {
		$Paranoia[] = $B.'_bounty';
	}
}

if (!isset($_POST['p_donor_heart'])) {
	$Paranoia[] = 'hide_donor_heart';
}

if (isset($_POST['p_donor_stats'])) {
	Donations::show_stats($UserID);
} else {
	Donations::hide_stats($UserID);
}

// End building $Paranoia


// Email change
$DB->query("
	SELECT Email
	FROM users_main
	WHERE ID = $UserID");
list($CurEmail) = $DB->next_record();
if ($CurEmail != $_POST['email']) {
	if (!check_perms('users_edit_profiles')) { // Non-admins have to authenticate to change email
		$DB->query("
			SELECT PassHash, Secret
			FROM users_main
			WHERE ID = '".db_string($UserID)."'");
		list($PassHash,$Secret)=$DB->next_record();
		if (!Users::check_password($_POST['cur_pass'], $PassHash, $Secret)) {
			$Err = 'You did not enter the correct password.';
		}
	}
	if (!$Err) {
		$NewEmail = db_string($_POST['email']);


		//This piece of code will update the time of their last email change to the current time *not* the current change.
		$ChangerIP = db_string($LoggedUser['IP']);
		$DB->query("
			UPDATE users_history_emails
			SET Time = '".sqltime()."'
			WHERE UserID = '$UserID'
				AND Time = '0000-00-00 00:00:00'");
		$DB->query("
			INSERT INTO users_history_emails
				(UserID, Email, Time, IP)
			VALUES
				('$UserID', '$NewEmail', '0000-00-00 00:00:00', '".db_string($_SERVER['REMOTE_ADDR'])."')");

	} else {
		error($Err);
		header("Location: user.php?action=edit&userid=$UserID");
		die();
	}


}
//End email change

if (!$Err && ($_POST['cur_pass'] || $_POST['new_pass_1'] || $_POST['new_pass_2'])) {
	$DB->query("
		SELECT PassHash, Secret
		FROM users_main
		WHERE ID = '".db_string($UserID)."'");
	list($PassHash, $Secret) = $DB->next_record();

	if (Users::check_password($_POST['cur_pass'], $PassHash, $Secret)) {
		if ($_POST['new_pass_1'] && $_POST['new_pass_2']) {
			$ResetPassword = true;
		}
	} else {
		$Err = 'You did not enter the correct password.';
	}
}

if ($LoggedUser['DisableAvatar'] && $_POST['avatar'] != $U['Avatar']) {
	$Err = 'Your avatar privileges have been revoked.';
}

if ($Err) {
	error($Err);
	header("Location: user.php?action=edit&userid=$UserID");
	die();
}

if (!empty($LoggedUser['DefaultSearch'])) {
	$Options['DefaultSearch'] = $LoggedUser['DefaultSearch'];
}
$Options['DisableGrouping2']    = (!empty($_POST['disablegrouping']) ? 0 : 1);
$Options['TorrentGrouping']     = (!empty($_POST['torrentgrouping']) ? 1 : 0);
$Options['DiscogView']          = (!empty($_POST['discogview']) ? 1 : 0);
$Options['PostsPerPage']        = (int)$_POST['postsperpage'];
//$Options['HideCollage']         = (!empty($_POST['hidecollage']) ? 1 : 0);
$Options['CollageCovers']       = (empty($_POST['collagecovers']) ? 0 : $_POST['collagecovers']);
$Options['ShowTorFilter']       = (empty($_POST['showtfilter']) ? 0 : 1);
$Options['ShowTags']            = (!empty($_POST['showtags']) ? 1 : 0);
$Options['AutoSubscribe']       = (!empty($_POST['autosubscribe']) ? 1 : 0);
$Options['DisableSmileys']      = (!empty($_POST['disablesmileys']) ? 1 : 0);
$Options['EnableMatureContent'] = (!empty($_POST['enablematurecontent']) ? 1 : 0);
$Options['UseOpenDyslexic']     = (!empty($_POST['useopendyslexic']) ? 1 : 0);
$Options['Tooltipster']         = (!empty($_POST['usetooltipster']) ? 1 : 0);
$Options['AutoloadCommStats']   = (check_perms('users_mod') && !empty($_POST['autoload_comm_stats']) ? 1 : 0);
$Options['DisableAvatars']      = db_string($_POST['disableavatars']);
$Options['Identicons']          = (!empty($_POST['identicons']) ? (int)$_POST['identicons'] : 0);
$Options['DisablePMAvatars']    = (!empty($_POST['disablepmavatars']) ? 1 : 0);
$Options['NotifyOnQuote']       = (!empty($_POST['notifications_Quotes_popup']) ? 1 : 0);
$Options['ListUnreadPMsFirst']  = (!empty($_POST['list_unread_pms_first']) ? 1 : 0);
$Options['ShowSnatched']        = (!empty($_POST['showsnatched']) ? 1 : 0);
$Options['DisableAutoSave']     = (!empty($_POST['disableautosave']) ? 1 : 0);
$Options['NoVoteLinks']         = (!empty($_POST['novotelinks']) ? 1 : 0);
$Options['CoverArt']            = (int)!empty($_POST['coverart']);
$Options['ShowExtraCovers']     = (int)!empty($_POST['show_extra_covers']);
$Options['AutoComplete']        = (int)$_POST['autocomplete'];

if (isset($LoggedUser['DisableFreeTorrentTop10'])) {
	$Options['DisableFreeTorrentTop10'] = $LoggedUser['DisableFreeTorrentTop10'];
}

if (!empty($_POST['sorthide'])) {
	$JSON = json_decode($_POST['sorthide']);
	foreach ($JSON as $J) {
		$E = explode('_', $J);
		$Options['SortHide'][$E[0]] = $E[1];
	}
} else {
	$Options['SortHide'] = array();
}

if (check_perms('site_advanced_search')) {
	$Options['SearchType'] = $_POST['searchtype'];
} else {
	unset($Options['SearchType']);
}

//TODO: Remove the following after a significant amount of time
unset($Options['ArtistNoRedirect']);
unset($Options['ShowQueryList']);
unset($Options['ShowCacheList']);

$DownloadAlt = isset($_POST['downloadalt']) ? 1 : 0;
$UnseededAlerts = isset($_POST['unseededalerts']) ? 1 : 0;


$LastFMUsername = db_string($_POST['lastfm_username']);
$OldLastFMUsername = '';
$DB->query("
	SELECT username
	FROM lastfm_users
	WHERE ID = '$UserID'");
if ($DB->has_results()) {
	list($OldLastFMUsername) = $DB->next_record();
	if ($OldLastFMUsername != $LastFMUsername) {
		if (empty($LastFMUsername)) {
			$DB->query("
				DELETE FROM lastfm_users
				WHERE ID = '$UserID'");
		} else {
			$DB->query("
				UPDATE lastfm_users
				SET Username = '$LastFMUsername'
				WHERE ID = '$UserID'");
		}
	}
} elseif (!empty($LastFMUsername)) {
	$DB->query("
		INSERT INTO lastfm_users (ID, Username)
		VALUES ('$UserID', '$LastFMUsername')");
}
G::$Cache->delete_value("lastfm_username_$UserID");

Donations::update_rewards($UserID);
NotificationsManager::save_settings($UserID);

// Information on how the user likes to download torrents is stored in cache
if ($DownloadAlt != $LoggedUser['DownloadAlt']) {
	$Cache->delete_value('user_'.$LoggedUser['torrent_pass']);
}

$Cache->begin_transaction("user_info_$UserID");
$Cache->update_row(false, array(
		'Avatar' => display_str($_POST['avatar']),
		'Paranoia' => $Paranoia
));
$Cache->commit_transaction(0);

$Cache->begin_transaction("user_info_heavy_$UserID");
$Cache->update_row(false, array(
		'StyleID' => $_POST['stylesheet'],
		'StyleURL' => display_str($_POST['styleurl']),
		'DownloadAlt' => $DownloadAlt
		));
$Cache->update_row(false, $Options);
$Cache->commit_transaction(0);


$SQL = "
	UPDATE users_main AS m
		JOIN users_info AS i ON m.ID = i.UserID
	SET
		i.StyleID = '".db_string($_POST['stylesheet'])."',
		i.StyleURL = '".db_string($_POST['styleurl'])."',
		i.Avatar = '".db_string($_POST['avatar'])."',
		i.SiteOptions = '".db_string(serialize($Options))."',
		i.NotifyOnQuote = '".db_string($Options['NotifyOnQuote'])."',
		i.Info = '".db_string($_POST['info'])."',
		i.InfoTitle = '".db_string($_POST['profile_title'])."',
		i.DownloadAlt = '$DownloadAlt',
		i.UnseededAlerts = '$UnseededAlerts',
		m.Email = '".db_string($_POST['email'])."',
		m.IRCKey = '".db_string($_POST['irckey'])."',
		m.Paranoia = '".db_string(serialize($Paranoia))."'";

if ($ResetPassword) {
	$ChangerIP = db_string($LoggedUser['IP']);
	$PassHash = Users::make_crypt_hash($_POST['new_pass_1']);
	$SQL.= ",m.PassHash = '".db_string($PassHash)."'";
	$DB->query("
		INSERT INTO users_history_passwords
			(UserID, ChangerIP, ChangeTime)
		VALUES
			('$UserID', '$ChangerIP', '".sqltime()."')");

}

if (isset($_POST['resetpasskey'])) {

	$UserInfo = Users::user_heavy_info($UserID);
	$OldPassKey = db_string($UserInfo['torrent_pass']);
	$NewPassKey = db_string(Users::make_secret());
	$ChangerIP = db_string($LoggedUser['IP']);
	$SQL .= ",m.torrent_pass = '$NewPassKey'";
	$DB->query("
		INSERT INTO users_history_passkeys
			(UserID, OldPassKey, NewPassKey, ChangerIP, ChangeTime)
		VALUES
			('$UserID', '$OldPassKey', '$NewPassKey', '$ChangerIP', '".sqltime()."')");
	$Cache->begin_transaction("user_info_heavy_$UserID");
	$Cache->update_row(false, array('torrent_pass' => $NewPassKey));
	$Cache->commit_transaction(0);
	$Cache->delete_value("user_$OldPassKey");

	Tracker::update_tracker('change_passkey', array('oldpasskey' => $OldPassKey, 'newpasskey' => $NewPassKey));
}

$SQL .= "WHERE m.ID = '".db_string($UserID)."'";
$DB->query($SQL);

if ($ResetPassword) {
	logout();
}

header("Location: user.php?action=edit&userid=$UserID");

?>
