<?
// Props to Leto of StC.
if (!check_perms('users_view_invites') && !check_perms('users_disable_users') && !check_perms('users_edit_invites') && !check_perms('users_disable_any')) {
	error(404);
}
View::show_header('Manipulate Invite Tree');

if ($_POST['id']) {
	authorize();

	if (!is_number($_POST['id'])) {
		error(403);
	}
	if (!$_POST['comment']) {
		error('Please enter a comment to add to the users affected.');
	}
	else {
		$Comment = db_string($_POST['comment']);
		$Comment .= "\n" . "Manipulate Tree used by " . $LoggedUser['Username'];
	}
	$UserID = $_POST['id'];
	$DB->query("
			SELECT
				t1.TreePosition,
				t1.TreeID,
				t1.TreeLevel,
				(
					SELECT
						t2.TreePosition
					FROM invite_tree AS t2
					WHERE t2.TreeID = t1.TreeID
						AND t2.TreeLevel = t1.TreeLevel
						AND t2.TreePosition > t1.TreePosition
					ORDER BY t2.TreePosition
					LIMIT 1
				) AS MaxPosition
			FROM invite_tree AS t1
			WHERE t1.UserID = $UserID");
	list ($TreePosition, $TreeID, $TreeLevel, $MaxPosition) = $DB->next_record();
	if (!$MaxPosition) {
		$MaxPosition = 1000000;
	} // $MaxPermission is null if the user is the last one in that tree on that level
	if (!$TreeID) {
		return;
	}
	$DB->query("
			SELECT
				UserID
			FROM invite_tree
			WHERE TreeID = $TreeID
				AND TreePosition > $TreePosition
				AND TreePosition < $MaxPosition
				AND TreeLevel > $TreeLevel
			ORDER BY TreePosition");
	$BanList = array();

	while (list ($Invitee) = $DB->next_record()) {
		$BanList[] = $Invitee;
	}

	foreach ($BanList as $Key => $InviteeID) {
		if ($_POST['perform'] === 'nothing') {
			Tools::update_user_notes($InviteeID, $Comment . "\n\n");
			$Msg = "Successfully commented on entire invite tree!";
		} elseif ($_POST['perform'] === 'disable') {
			Tools::disable_users($InviteeID, $Comment);
			$Msg = "Successfully banned entire invite tree!";
		} elseif ($_POST['perform'] === 'inviteprivs') { // DisableInvites =1
			Tools::update_user_notes($InviteeID, $Comment . "\n\n");
			$DB->query("
				UPDATE users_info
				SET DisableInvites = '1'
				WHERE UserID = '$InviteeID'");
			$Msg = "Successfully removed invite privileges from entire tree!";
		}
		else {
			error(403);
		}
	}
}
?>

<div class="thin">
<?	if ($Msg) { ?>
	<div class="center">
		<p style="color: red; text-align: center;"><?=$Msg?></p>
	</div>
<?	} ?>
	<form class="manage_form" name="user" action="" method="post">
		<input type="hidden" id="action" name="action" value="manipulate_tree" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<table class="layout">
			<tr>
				<td class="label"><strong>UserID</strong></td>
				<td><input type="search" size="10" name="id" id="id" /></td>
				<td class="label"><strong>Mandatory comment!</strong></td>
				<td><input type="search" size="40" name="comment" id="comment" /></td>
			</tr>
			<tr>
				<td class="label"><strong>Action: </strong></td>
				<td colspan="2">
					<select name="perform">
						<option value="nothing"<?
					if ($_POST['perform'] === 'nothing') { echo ' selected="selected"'; } ?>>Do nothing</option>
						<option value="disable"<?
					if ($_POST['perform'] === 'disable') { echo ' selected="selected"'; } ?>>Disable entire tree</option>
						<option value="inviteprivs"<?
					if ($_POST['perform'] === 'inviteprivs') { echo ' selected="selected"'; } ?>>Disable invites privileges</option>
					</select>
				</td>
				<td align="left"><input type="submit" value="Go" /></td>
			</tr>
		</table>
	</form>
</div>

<? View::show_footer(); ?>
