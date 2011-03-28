<?
if(!check_perms('admin_login_watch')) { error(403); }

if(isset($_POST['submit']) && isset($_POST['id']) && $_POST['submit'] == 'Unban' && is_number($_POST['id'])){
	authorize();
	$DB->query('DELETE FROM login_attempts WHERE ID='.$_POST['id']);
}

$DB->query('SELECT 
	l.ID,
	l.IP,
	l.UserID,
	l.LastAttempt,
	l.Attempts,
	l.BannedUntil,
	l.Bans,
	m.Username,
	m.PermissionID,
	m.Enabled,
	i.Donor,
	i.Warned
	FROM login_attempts AS l
	LEFT JOIN users_main AS m ON m.ID=l.UserID
	LEFT JOIN users_info AS i ON i.UserID=l.UserID
	WHERE l.BannedUntil > "'.sqltime().'"
	ORDER BY l.BannedUntil ASC');


show_header('Login Watch');
?>
<div class="thin">
<h2>Login Watch Management</h2>
<table width="100%">
	<tr class="colhead">
		<td>IP</td>
		<td>User</td>
		<td>Bans</td>
		<td>Remaining</td>
		<td>Submit</td>
		<? if(check_perms('admin_manage_ipbans')) { ?>		<td>Submit</td><? } ?>
	</tr>
<?
$Row = 'b';
while(list($ID, $IP, $UserID, $LastAttempt, $Attempts, $BannedUntil, $Bans, $Username, $PermissionID, $Enabled, $Donor, $Warned) = $DB->next_record()){
	$Row = ($Row === 'a' ? 'b' : 'a');
?>
	<tr class="row<?=$Row?>">
			<td>
				<?=$IP?>
			</td>
			<td>
				<? if ($UserID != 0) { echo format_username($UserID, $Username, $Donor, $Warned, $Enabled, $PermissionID); } ?>
			</td>
			<td>
				<?=$Bans?>
			</td>
			<td>
				<?=time_diff($BannedUntil)?>
			</td>	
			<td>
				<form action="" method="post">
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="id" value="<?=$ID?>" />
					<input type="hidden" name="action" value="login_watch" />
					<input type="submit" name="submit" value="Unban" />
				</form>
			</td>
<? if(check_perms('admin_manage_ipbans')) { ?>
			<td>
				<form action="" method="post">
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="id" value="<?=$ID?>" />
					<input type="hidden" name="action" value="ip_ban" />
					<input type="hidden" name="start" value="<?=$IP?>" />
					<input type="hidden" name="end" value="<?=$IP?>" />
					<input type="hidden" name="notes" value="Banned per <?=$Bans?> bans on login watch." />
					<input type="submit" name="submit" value="IP Ban" />
				</form>
			</td>
<? } ?>
	</tr>
<?
}
?>
</table>
</div>
<? show_footer(); ?>
