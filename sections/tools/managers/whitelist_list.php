<?
if(!check_perms('admin_whitelist')) { error(403); }

show_header('Whitelist Management');
$DB->query('SELECT id, vstring, peer_id FROM xbt_client_whitelist ORDER BY peer_id ASC');
?>
<h2>Allowed Clients</h2>
<table width="100%">
	<tr class="colhead">
		<td>Client</td>
		<td>Peer ID</td>
		<td>Submit</td>
	</tr>
</table>
<?
$Row = 'b';
while(list($ID, $Client, $Peer_ID) = $DB->next_record()){
	$Row = ($Row === 'a' ? 'b' : 'a');
?>
<form action="" method="post">
	<input type="hidden" name="action" value="whitelist_alter" />
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<table>
		<tr class="row<?=$Row?>">
			<td>
				<input type="hidden" name="id" value="<?=$ID?>" />
				<input type="text" size="100" name="client" value="<?=$Client?>" />
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
<form action="" method="post">
	<input type="hidden" name="action" value="whitelist_alter" />
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<table>
		<tr>
			<td colspan="4" class="colhead">Add Client</td>
		</tr>
		<tr class="rowa">		
			
			<td>
				<input type="text" size="100" name="client" />
			</td>
			<td>
				<input type="text" size="10" name="peer_id" />
			</td>
			<td>
				<input type="submit" value="Create" />
			</td>
		</tr>
	</table>
</form>
<? show_footer(); ?>
