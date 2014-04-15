<?
if (isset($_GET['userid']) && check_perms('users_view_invites')) {
	if (!is_number($_GET['userid'])) {
		error(403);
	}

	$UserID=$_GET['userid'];
	$Sneaky = true;
} else {
	if (!$UserCount = $Cache->get_value('stats_user_count')) {
		$DB->query("
			SELECT COUNT(ID)
			FROM users_main
			WHERE Enabled = '1'");
		list($UserCount) = $DB->next_record();
		$Cache->cache_value('stats_user_count', $UserCount, 0);
	}

	$UserID = $LoggedUser['ID'];
	$Sneaky = false;
}

list($UserID, $Username, $PermissionID) = array_values(Users::user_info($UserID));


$DB->query("
	SELECT InviteKey, Email, Expires
	FROM invites
	WHERE InviterID = '$UserID'
	ORDER BY Expires");
$Pending = 	$DB->to_array();

$OrderWays = array('username', 'email',	'joined', 'lastseen', 'uploaded', 'downloaded', 'ratio');

if (empty($_GET['order'])) {
	$CurrentOrder = 'id';
	$CurrentSort = 'asc';
	$NewSort = 'desc';
} else {
	if (in_array($_GET['order'], $OrderWays)) {
		$CurrentOrder = $_GET['order'];
		if ($_GET['sort'] == 'asc' || $_GET['sort'] == 'desc') {
			$CurrentSort = $_GET['sort'];
			$NewSort = ($_GET['sort'] == 'asc' ? 'desc' : 'asc');
		} else {
			error(404);
		}
	} else {
		error(404);
	}
}

switch ($CurrentOrder) {
	case 'username':
		$OrderBy = "um.Username";
		break;
	case 'email':
		$OrderBy = "um.Email";
		break;
	case 'joined':
		$OrderBy = "ui.JoinDate";
		break;
	case 'lastseen':
		$OrderBy = "um.LastAccess";
		break;
	case 'uploaded':
		$OrderBy = "um.Uploaded";
		break;
	case 'downloaded':
		$OrderBy = "um.Downloaded";
		break;
	case 'ratio':
		$OrderBy = "(um.Uploaded / um.Downloaded)";
		break;
	default:
		$OrderBy = "um.ID";
		break;
}

$CurrentURL = Format::get_url(array('action', 'order', 'sort'));

$DB->query("
	SELECT
		ID,
		Email,
		Uploaded,
		Downloaded,
		JoinDate,
		LastAccess
	FROM users_main AS um
		LEFT JOIN users_info AS ui ON ui.UserID = um.ID
	WHERE ui.Inviter = '$UserID'
	ORDER BY $OrderBy $CurrentSort");

$Invited = $DB->to_array();

$JSIncludes = '';
if (check_perms('users_mod') || check_perms('admin_advanced_user_search')) {
	$JSIncludes = 'invites';
}

View::show_header('Invites', $JSIncludes);
?>
<div class="thin">
	<div class="header">
		<h2><?=Users::format_username($UserID, false, false, false)?> &gt; Invites</h2>
		<div class="linkbox">
			<a href="user.php?action=invitetree<? if ($Sneaky) { echo '&amp;userid='.$UserID; } ?>" class="brackets">Invite tree</a>
		</div>
	</div>
<? if ($UserCount >= USER_LIMIT && !check_perms('site_can_invite_always')) { ?>
	<div class="box pad notice">
		<p>Because the user limit has been reached you are unable to send invites at this time.</p>
	</div>
<? }

/*
	Users cannot send invites if they:
		-Are on ratio watch
		-Have disabled leeching
		-Have disabled invites
		-Have no invites (Unless have unlimited)
		-Cannot 'invite always' and the user limit is reached
*/

$DB->query("
	SELECT can_leech
	FROM users_main
	WHERE ID = $UserID");
list($CanLeech) = $DB->next_record();

if (!$Sneaky
	&& !$LoggedUser['RatioWatch']
	&& $CanLeech
	&& empty($LoggedUser['DisableInvites'])
	&& ($LoggedUser['Invites'] > 0 || check_perms('site_send_unlimited_invites'))
	&& ($UserCount <= USER_LIMIT || USER_LIMIT == 0 || check_perms('site_can_invite_always'))
	) { ?>
	<div class="box pad">
		<p>Please note that the selling, trading, or publicly giving away our invitations&#8202;&mdash;&#8202;or responding to public invite requests&#8202;&mdash;&#8202;is strictly forbidden, and may result in you and your entire invite tree being banned. This includes offering to give away our invitations on any forum which is not a class-restricted forum on another private tracker.</p>
		<p>Remember that you are responsible for ALL invitees, and your account and/or privileges may be disabled due to your invitees' actions. You should know the person you're inviting. If you aren't familiar enough with the user to trust them, we suggest not inviting them.</p>
		<p><em>Do not send an invite if you have not read or do not understand the information above.</em></p>
	</div>
	<div class="box box2">
		<form class="send_form pad" name="invite" action="user.php" method="post">
			<input type="hidden" name="action" value="take_invite" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<div class="field_div">
				<div class="label">Email address:</div>
				<div class="input">
					<input type="email" name="email" size="60" />
					<input type="submit" value="Invite" />
				</div>
			</div>
<?	if (check_perms('users_invite_notes')) { ?>
			<div class="field_div">
				<div class="label">Staff Note:</div>
				<div class="input">
					<input type="text" name="reason" size="60" maxlength="255" />
				</div>
			</div>
<?	} ?>
		</form>
	</div>

<?
} elseif (!empty($LoggedUser['DisableInvites'])) { ?>
	<div class="box pad" style="text-align: center;">
		<strong class="important_text">Your invites have been disabled. Please read <a href="wiki.php?action=article&amp;id=310">this article</a> for more information.</strong>
	</div>
<?
} elseif ($LoggedUser['RatioWatch'] || !$CanLeech) { ?>
	<div class="box pad" style="text-align: center;">
		<strong class="important_text">You may not send invites while on Ratio Watch or while your leeching privileges are disabled. Please read <a href="wiki.php?action=article&amp;id=310">this article</a> for more information.</strong>
	</div>
<?
}

if (!empty($Pending)) {
?>
	<h3>Pending invites</h3>
	<div class="box pad">
		<table width="100%">
			<tr class="colhead">
				<td>Email address</td>
				<td>Expires in</td>
				<td>Delete invite</td>
			</tr>
<?
	$Row = 'a';
	foreach ($Pending as $Invite) {
		list($InviteKey, $Email, $Expires) = $Invite;
		$Row = $Row === 'a' ? 'b' : 'a';
?>
			<tr class="row<?=$Row?>">
				<td><?=display_str($Email)?></td>
				<td><?=time_diff($Expires)?></td>
				<td><a href="user.php?action=delete_invite&amp;invite=<?=$InviteKey?>&amp;auth=<?=$LoggedUser['AuthKey']?>" onclick="return confirm('Are you sure you want to delete this invite?');">Delete invite</a></td>
			</tr>
<?	} ?>
		</table>
	</div>
<?
}

?>
	<h3>Invitee list</h3>
	<div class="box pad">
		<table width="100%">
			<tr class="colhead">
				<td><a href="user.php?action=invite&amp;order=username&amp;sort=<?=(($CurrentOrder == 'username') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>">Username</a></td>
				<td><a href="user.php?action=invite&amp;order=email&amp;sort=<?=(($CurrentOrder == 'email') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>">Email</a></td>
				<td><a href="user.php?action=invite&amp;order=joined&amp;sort=<?=(($CurrentOrder == 'joined') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>">Joined</a></td>
				<td><a href="user.php?action=invite&amp;order=lastseen&amp;sort=<?=(($CurrentOrder == 'lastseen') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>">Last Seen</a></td>
				<td><a href="user.php?action=invite&amp;order=uploaded&amp;sort=<?=(($CurrentOrder == 'uploaded') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>">Uploaded</a></td>
				<td><a href="user.php?action=invite&amp;order=downloaded&amp;sort=<?=(($CurrentOrder == 'downloaded') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>">Downloaded</a></td>
				<td><a href="user.php?action=invite&amp;order=ratio&amp;sort=<?=(($CurrentOrder == 'ratio') ? $NewSort : 'desc')?>&amp;<?=$CurrentURL ?>">Ratio</a></td>
			</tr>
<?
	$Row = 'a';
	foreach ($Invited as $User) {
		list($ID, $Email, $Uploaded, $Downloaded, $JoinDate, $LastAccess) = $User;
		$Row = $Row === 'a' ? 'b' : 'a';
?>
			<tr class="row<?=$Row?>">
				<td><?=Users::format_username($ID, true, true, true, true)?></td>
				<td><?=display_str($Email)?></td>
				<td><?=time_diff($JoinDate, 1)?></td>
				<td><?=time_diff($LastAccess, 1);?></td>
				<td><?=Format::get_size($Uploaded)?></td>
				<td><?=Format::get_size($Downloaded)?></td>
				<td><?=Format::get_ratio_html($Uploaded, $Downloaded)?></td>
			</tr>
<?	} ?>
		</table>
	</div>
</div>
<? View::show_footer(); ?>
