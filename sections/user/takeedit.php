<?
authorize();

$UserID = $_REQUEST['userid'];
if(!is_number($UserID)) {
	error(404);
}

//For the entire of this page we should in general be using $UserID not $LoggedUser['ID'] and $U[] not $LoggedUser[]	
$U = user_info($UserID);

if (!$U) {
	error(404);
}

$Permissions = get_permissions($U['PermissionID']);
if ($UserID != $LoggedUser['ID'] && !check_perms('users_edit_profiles', $Permissions['Class'])) {
	send_irc("PRIVMSG ".ADMIN_CHAN." :User ".$LoggedUser['Username']." (http://".NONSSL_SITE_URL."/user.php?id=".$LoggedUser['ID'].") just tried to edit the profile of http://".NONSSL_SITE_URL."/user.php?id=".$_REQUEST['userid']);
	error(403);
}

$Val->SetFields('stylesheet',1,"number","You forgot to select a stylesheet.");
$Val->SetFields('styleurl',0,"regex","You did not enter a valid stylesheet url.",array('regex'=>'/^https?:\/\/(localhost(:[0-9]{2,5})?|[0-9]{1,3}(\.[0-9]{1,3}){3}|([a-zA-Z0-9\-\_]+\.)+([a-zA-Z]{1,5}[^\.]))(:[0-9]{2,5})?(\/[^<>]+)+\.css$/i'));
$Val->SetFields('disablegrouping',1,"number","You forgot to select your torrent grouping option.",array('minlength'=>0,'maxlength'=>1));
$Val->SetFields('torrentgrouping',1,"number","You forgot to select your torrent grouping option.",array('minlength'=>0,'maxlength'=>1));
$Val->SetFields('discogview',1,"number","You forgot to select your discography view option.",array('minlength'=>0,'maxlength'=>1));
$Val->SetFields('postsperpage',1,"number","You forgot to select your posts per page option.",array('inarray'=>array(25,50,100)));
//$Val->SetFields('hidecollage',1,"number","You forgot to select your collage option.",array('minlength'=>0,'maxlength'=>1));
$Val->SetFields('collagecovers',1,"number","You forgot to select your collage option.");
$Val->SetFields('showtags',1,"number","You forgot to select your show tags option.",array('minlength'=>0,'maxlength'=>1));
$Val->SetFields('avatar',0,"regex","You did not enter a valid avatar url.",array('regex'=>"/^".IMAGE_REGEX."$/i"));
$Val->SetFields('email',1,"email","You did not enter a valid email address.");
$Val->SetFields('irckey',0,"string","You did not enter a valid IRCKey, must be between 6 and 32 characters long.",array('minlength'=>6,'maxlength'=>32));
$Val->SetFields('cur_pass',0,"string","You did not enter a valid password, must be between 6 and 40 characters long.",array('minlength'=>6,'maxlength'=>40));
$Val->SetFields('new_pass_1',0,"string","You did not enter a valid password, must be between 6 and 40 characters long.",array('minlength'=>6,'maxlength'=>40));
$Val->SetFields('new_pass_2',1,"compare","Your passwords do not match.",array('comparefield'=>'new_pass_1'));
if (check_perms('site_advanced_search')) {
	$Val->SetFields('searchtype',1,"number","You forgot to select your default search preference.",array('minlength'=>0,'maxlength'=>1));
}

$Err = $Val->ValidateForm($_POST);

if($Err) {
	error($Err);
	header('Location: user.php?action=edit&userid='.$UserID);
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
foreach($Stats as $S) {
	if(isset($_POST['p_'.$S])) {
		$StatsShown++;
	}
}

if($StatsShown == 2) {
	foreach($Stats as $S) {
		$_POST['p_'.$S] = 'on';
	}
}

$Paranoia = array();
$Checkboxes = array('downloaded', 'uploaded', 'ratio', 'lastseen', 'requiredratio', 'invitedcount', 'artistsadded');
foreach($Checkboxes as $C) {
	if(!isset($_POST['p_'.$C])) {
		$Paranoia[] = $C;
	}
}

$SimpleSelects = array('torrentcomments', 'collages', 'collagecontribs', 'uploads', 'uniquegroups', 'perfectflacs', 'seeding', 'leeching', 'snatched');
foreach ($SimpleSelects as $S) {
	if(!isset($_POST['p_'.$S.'_c']) && !isset($_POST['p_'.$S.'_l'])) {
		// Very paranoid - don't show count or list
		$Paranoia[] = $S . '+';
	} elseif (!isset($_POST['p_'.$S.'_l'])) {
		// A little paranoid - show count, don't show list
		$Paranoia[] = $S;
	}
}

$Bounties = array('requestsfilled', 'requestsvoted');
foreach ($Bounties as $B) {
	if (isset($_POST['p_'.$B.'_list'])) {
		$_POST['p_'.$B.'_count'] = 'on';
		$_POST['p_'.$B.'_bounty'] = 'on';
	}
	if (!isset($_POST['p_'.$B.'_list'])) {
		$Paranoia[] = $B.'_list';
	}
	if (!isset($_POST['p_'.$B.'_count'])) {
		$Paranoia[] = $B.'_count';
	}
	if (!isset($_POST['p_'.$B.'_bounty'])) {
		$Paranoia[] = $B.'_bounty';
	}
}
// End building $Paranoia


//Email change
$DB->query("SELECT Email FROM users_main WHERE ID=".$UserID);
list($CurEmail) = $DB->next_record();
if ($CurEmail != $_POST['email']) {
	if(!check_perms('users_edit_profiles')) { // Non-admins have to authenticate to change email
		$DB->query("SELECT PassHash,Secret FROM users_main WHERE ID='".db_string($UserID)."'");
		list($PassHash,$Secret)=$DB->next_record();
		if ($PassHash!=make_hash($_POST['cur_pass'],$Secret)) {
			$Err = "You did not enter the correct password.";
		}
	}
	if(!$Err) {
		$NewEmail = db_string($_POST['email']);		

	
		//This piece of code will update the time of their last email change to the current time *not* the current change.
		$ChangerIP = db_string($LoggedUser['IP']);
		$DB->query("UPDATE users_history_emails SET Time='".sqltime()."' WHERE UserID='$UserID' AND Time='0000-00-00 00:00:00'");
		$DB->query("INSERT INTO users_history_emails
				(UserID, Email, Time, IP) VALUES
				('$UserID', '$NewEmail', '0000-00-00 00:00:00', '".db_string($_SERVER['REMOTE_ADDR'])."')");
		
	} else {
		error($Err);
		header('Location: user.php?action=edit&userid='.$UserID);
		die();
	}
	
	
}
//End Email change

if (!$Err && ($_POST['cur_pass'] || $_POST['new_pass_1'] || $_POST['new_pass_2'])) {
	$DB->query("SELECT PassHash,Secret FROM users_main WHERE ID='".db_string($UserID)."'");
	list($PassHash,$Secret)=$DB->next_record();

	if ($PassHash == make_hash($_POST['cur_pass'],$Secret)) {
		if ($_POST['new_pass_1'] && $_POST['new_pass_2']) { 
			$ResetPassword = true; 
		}
	} else { 
		$Err = "You did not enter the correct password.";
	}
}

if($LoggedUser['DisableAvatar'] && $_POST['avatar'] != $U['Avatar']) {
	$Err = "Your avatar rights have been removed.";
}

if ($Err) {
	error($Err);
	header('Location: user.php?action=edit&userid='.$UserID);
	die();
}

if(!empty($LoggedUser['DefaultSearch'])) {
	$Options['DefaultSearch'] = $LoggedUser['DefaultSearch'];
}
$Options['DisableGrouping'] = (!empty($_POST['disablegrouping']) ? 1 : 0);
$Options['TorrentGrouping'] = (!empty($_POST['torrentgrouping']) ? 1 : 0);
$Options['DiscogView'] = (!empty($_POST['discogview']) ? 1 : 0);
$Options['PostsPerPage'] = (int) $_POST['postsperpage'];
//$Options['HideCollage'] = (!empty($_POST['hidecollage']) ? 1 : 0);
$Options['CollageCovers'] = empty($_POST['collagecovers']) ? 0 : $_POST['collagecovers'];
$Options['ShowTags'] = (!empty($_POST['showtags']) ? 1 : 0);
$Options['AutoSubscribe'] = (!empty($_POST['autosubscribe']) ? 1 : 0);
$Options['DisableSmileys'] = (!empty($_POST['disablesmileys']) ? 1 : 0);
$Options['DisableAvatars'] = (!empty($_POST['disableavatars']) ? 1 : 0);
$Options['DisablePMAvatars'] = (!empty($_POST['disablepmavatars']) ? 1 : 0);


if(isset($LoggedUser['DisableFreeTorrentTop10'])) {
	$Options['DisableFreeTorrentTop10'] = $LoggedUser['DisableFreeTorrentTop10'];
}

if(!empty($_POST['hidetypes'])) {
	foreach($_POST['hidetypes'] as $Type) {
		$Options['HideTypes'][] = (int) $Type;
	}
} else {
	$Options['HideTypes'] = array();
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

$DownloadAlt = (isset($_POST['downloadalt']))? 1:0;
$UnseededAlerts = (isset($_POST['unseededalerts']))? 1:0;

// Information on how the user likes to download torrents is stored in cache
if($DownloadAlt != $LoggedUser['DownloadAlt']) {
	$Cache->delete_value('user_'.$LoggedUser['torrent_pass']);
}

$Cache->begin_transaction('user_info_'.$UserID);
$Cache->update_row(false, array(
		'Avatar'=>$_POST['avatar'],
		'Paranoia'=>$Paranoia

));
$Cache->commit_transaction(0);

$Cache->begin_transaction('user_info_heavy_'.$UserID);
$Cache->update_row(false, array(
		'StyleID'=>$_POST['stylesheet'],
		'StyleURL'=>$_POST['styleurl'],
		'DownloadAlt'=>$DownloadAlt
		));
$Cache->update_row(false, $Options);
$Cache->commit_transaction(0);



$SQL="UPDATE users_main AS m JOIN users_info AS i ON m.ID=i.UserID SET
	i.StyleID='".db_string($_POST['stylesheet'])."',
	i.StyleURL='".db_string($_POST['styleurl'])."',
	i.Avatar='".db_string($_POST['avatar'])."',
	i.SiteOptions='".db_string(serialize($Options))."',
	i.Info='".db_string($_POST['info'])."',
	i.DownloadAlt='$DownloadAlt',
	i.UnseededAlerts='$UnseededAlerts',
	m.Email='".db_string($_POST['email'])."',
	m.IRCKey='".db_string($_POST['irckey'])."',";

$SQL .= "m.Paranoia='".db_string(serialize($Paranoia))."'";

if($ResetPassword) {
	$ChangerIP = db_string($LoggedUser['IP']);
	$Secret=make_secret();
	$PassHash=make_hash($_POST['new_pass_1'],$Secret);
	$SQL.=",m.Secret='".db_string($Secret)."',m.PassHash='".db_string($PassHash)."'";
	$DB->query("INSERT INTO users_history_passwords
		(UserID, ChangerIP, ChangeTime) VALUES
		('$UserID', '$ChangerIP', '".sqltime()."')");

	
}

if (isset($_POST['resetpasskey'])) {
	
	
	
	$UserInfo = user_heavy_info($UserID);
	$OldPassKey = db_string($UserInfo['torrent_pass']);
	$NewPassKey = db_string(make_secret());
	$ChangerIP = db_string($LoggedUser['IP']);
	$SQL.=",m.torrent_pass='$NewPassKey'";
	$DB->query("INSERT INTO users_history_passkeys
			(UserID, OldPassKey, NewPassKey, ChangerIP, ChangeTime) VALUES
			('$UserID', '$OldPassKey', '$NewPassKey', '$ChangerIP', '".sqltime()."')");
	$Cache->begin_transaction('user_info_heavy_'.$UserID);
	$Cache->update_row(false, array('torrent_pass'=>$NewPassKey));
	$Cache->commit_transaction(0);
	$Cache->delete_value('user_'.$OldPassKey);
	
	update_tracker('change_passkey', array('oldpasskey' => $OldPassKey, 'newpasskey' => $NewPassKey));
}

$SQL.="WHERE m.ID='".db_string($UserID)."'";
$DB->query($SQL);

if ($ResetPassword) {
	logout();
}

header('Location: user.php?action=edit&userid='.$UserID);

?>
