<?
if (!check_perms('admin_whitelist')) {
	error(403);
}

View::show_header('Client Whitelist Manager');
$DB->query('
	SELECT id, vstring, peer_id
	FROM xbt_client_whitelist
	ORDER BY peer_id ASC');
?>
<div class="header">
	<h2>Client Whitelist</h2>
</div>
<div class="box2 pad thin">
<form class="add_form" name="clients" action="" method="post">
	<input type="hidden" name="action" value="whitelist_alter" />
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<table>
		<tr class="colhead">
			<td colspan="4">Add client</td>
		</tr>
		<tr class="rowa">
			<td>
				<input type="text" size="60" name="client" placeholder="Client name" />
			</td>
			<td>
				<input type="text" size="10" name="peer_id" placeholder="Peer ID" />
			</td>
			<td>
				<input type="submit" value="Create" />
			</td>
		</tr>
	</table>
</form>
<table width="100%">
	<tr class="colhead">
		<td>Client</td>
		<td>Peer ID</td>
		<td>Submit</td>
	</tr>
</table>
<?
$Row = 'b';
while (list($ID, $Client, $Peer_ID) = $DB->next_record()) {
	$Row = $Row === 'a' ? 'b' : 'a';
?>
<form class="manage_form" name="clients" action="" method="post">
	<input type="hidden" name="action" value="whitelist_alter" />
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<table>
		<tr class="row<?=$Row?>">
			<td>
				<input type="hidden" name="id" value="<?=$ID?>" />
				<input type="text" size="60" name="client" value="<?=$Client?>" />
			</td>
			<td>
				<input type="text" size="10" name="peer_id" value="<?=$Peer_ID?>" />
			</td>
			<td>
				<input type="submit" name="submit" value="Edit" />
				<input type="submit" name="submit" value="Delete" />
			</td>
		</tr>
	</table>
</form>
<? } ?>
</div>
<? View::show_footer(); ?>
