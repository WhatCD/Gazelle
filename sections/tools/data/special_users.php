<?
if(!check_perms('admin_manage_permissions')) { error(403); }
show_header('Special Users List');
?>
<div class="thin">
<?
$DB->query("SELECT 
	m.ID,
	m.Username,
	m.PermissionID,
	m.Enabled,
	i.Donor,
	i.Warned
	FROM users_main AS m
	LEFT JOIN users_info AS i ON i.UserID=m.ID
	WHERE m.CustomPermissions != ''
	AND m.CustomPermissions != 'a:0:{}'");
if($DB->record_count()) {
?>
	<table width="100%">
		<tr class="colhead">
			<td>User</td>
			<td>Access</td>
		</tr>
<?
	while(list($UserID, $Username, $PermissionID, $Enabled, $Donor, $Warned)=$DB->next_record()) {
?>
		<tr>
			<td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?></td>
			<td><a href="user.php?action=permissions&amp;userid=<?=$UserID?>">Manage</a></td>
		</tr>
<?	} ?>
	</table>
<? } else { ?>
	<h2 align="center">There are no special users.</h2>
<? } ?>
</div>
<? show_footer(); ?>
