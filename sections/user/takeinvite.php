<?

if(!$UserCount = $Cache->get_value('stats_user_count')){
	$DB->query("SELECT COUNT(ID) FROM users_main WHERE Enabled='1'");
	list($UserCount) = $DB->next_record();
	$Cache->cache_value('stats_user_count', $UserCount, 0);
}

$UserID = $LoggedUser['ID'];

//This is where we handle things passed to us
authorize();

$DB->query("SELECT can_leech FROM users_main WHERE ID = ".$UserID);
list($CanLeech) = $DB->next_record();

if($LoggedUser['RatioWatch'] ||
	!$CanLeech ||
	$LoggedUser['DisableInvites'] == '1'||
	$LoggedUser['Invites']==0 && !check_perms('site_send_unlimited_invites') ||
	($UserCount >= USER_LIMIT && USER_LIMIT != 0 && !check_perms('site_can_invite_always'))) {
		
		error(403);
}

$Email = $_POST['email'];
$Username = $LoggedUser['Username'];
$SiteName = SITE_NAME;
$SiteURL = NONSSL_SITE_URL;
$InviteExpires = time_plus(60*60*24*3); // 3 days

//MultiInvite
if(strpos($Email, '|') && check_perms('site_send_unlimited_invites')) {
	$Emails = explode('|', $Email);
} else {
	$Emails = array($Email);
}

foreach($Emails as $CurEmail){
	if (!preg_match("/^".EMAIL_REGEX."$/i", $CurEmail)) {
		if(count($Emails) > 1) {
			continue;
		} else {
			error('Invalid email.');
			header('Location: user.php?action=invite');
			die();
		}
	}
	$DB->query("SELECT Expires FROM invites WHERE InviterID = ".$LoggedUser['ID']." AND Email LIKE '".$CurEmail."'");
	if($DB->record_count() > 0) {
		error("You already have a pending invite to that address!");
		header('Location: user.php?action=invite');
		die();
	}
	$InviteKey = db_string(make_secret());
		
$Message = <<<EOT
The user $Username has invited you to join $SiteName, and has specified this address ($CurEmail) as your email address. If you do not know this person, please ignore this email, and do not reply.

Please note that selling invites, trading invites, and giving invites away publicly (eg. on a forum) is strictly forbidden. If you have received your invite as a result of any of these things, do not bother signing up - you will be banned and lose your chances of ever signing up legitimately.

To confirm your invite, click on the following link:

http://$SiteURL/register.php?invite=$InviteKey

After you register, you will be able to use your account. Please take note that if you do not use this invite in the next 3 days, it will expire. We urge you to read the RULES and the wiki immediately after you join. 

Thank you,
$SiteName Staff
EOT;
	
	$DB->query("INSERT INTO invites
		(InviterID, InviteKey, Email, Expires) VALUES
		('$LoggedUser[ID]', '$InviteKey', '".db_string($CurEmail)."', '$InviteExpires')");

	if (!check_perms('site_send_unlimited_invites')) {
		$DB->query("UPDATE users_main SET Invites=GREATEST(Invites,1)-1 WHERE ID='$LoggedUser[ID]'");
		$Cache->begin_transaction('user_info_heavy_'.$LoggedUser['ID']);
		$Cache->update_row(false, array('Invites'=>'-1'));
		$Cache->commit_transaction(0);
	}
	
	send_email($CurEmail, 'You have been invited to '.SITE_NAME, $Message,'noreply');

	
}

header('Location: user.php?action=invite');
?>
