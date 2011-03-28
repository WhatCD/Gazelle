<?
if(!check_perms('users_view_invites')) { error(403); }
show_header('Invite Pool');
define('INVITES_PER_PAGE', 50);
list($Page,$Limit) = page_limit(INVITES_PER_PAGE);

if(!empty($_POST['invitekey']) && check_perms('users_edit_invites')) {
	authorize();

	$DB->query("DELETE FROM invites WHERE InviteKey='".db_string($_POST['invitekey'])."'");
}

if(!empty($_GET['search'])) {
	$Search = db_string($_GET['search']);
} else {
	$Search = "";
}

$sql = "SELECT 
	SQL_CALC_FOUND_ROWS
	um.ID,
	um.Username,
	um.PermissionID,
	um.Enabled,
	ui.Donor,
	ui.Warned,
	i.InviteKey,
	i.Expires,
	i.Email
	FROM invites as i
	JOIN users_main AS um ON um.ID=i.InviterID
	JOIN users_info AS ui ON ui.UserID=um.ID ";
if($Search) {
	$sql .= "WHERE i.Email LIKE '%$Search%' ";
}
$sql .= "ORDER BY i.Expires DESC LIMIT $Limit";
$RS = $DB->query($sql);

$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();

$DB->set_query_id($RS);
?>
	<div class="box pad">
		<p><?=number_format($Results)?> unused invites have been sent. </p>
	</div>
	<br />
	<div>
		<form action="" method="get">
			<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
				<tr>
					<td class="label"><strong>Email:</strong></td>
					<td>
						<input type="hidden" name="action" value="invite_pool" />
						<input type="text" name="search" size="60" value="<?=display_str($Search)?>" />
						&nbsp;
						<input type="submit" value="Search log" />
					</td>
				</tr>
			</table>	
		</form>
	</div>
	<div class="linkbox">
<?
	$Pages=get_pages($Page,$Results,INVITES_PER_PAGE,11) ;
	echo $Pages;
?>
	</div>
	<table width="100%">
		<tr class="colhead">
			<td>Inviter</td>
			<td>Email</td>
			<td>InviteCode</td>
			<td>Expires</td>
<? if(check_perms('users_edit_invites')){ ?>
			<td>Controls</td>
<? } ?>
		</tr>
<?
	$Row = 'b';
	while(list($UserID, $Username, $PermissionID, $Enabled, $Donor, $Warned, $InviteKey, $Expires, $Email)=$DB->next_record()) {
	$Row = ($Row == 'b') ? 'a' : 'b';
?>
		<tr class="row<?=$Row?>">
			<td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?></td>
			<td><?=display_str($Email)?></td>
			<td><?=display_str($InviteKey)?></td>
			<td><?=time_diff($Expires)?></td>
<? if(check_perms('users_edit_invites')){ ?>
			<td>
				<form action="" method="post">
					<input type="hidden" name="action" value="invite_pool" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="invitekey" value="<?=display_str($InviteKey)?>" />
					<input type="submit" value="Delete" />
				</form>
			</td>
<? } ?>
		</tr>
<?	} ?>
	</table>
	<div class="linkbox">
<? echo $Pages; ?>
	</div>
<? show_footer(); ?>
