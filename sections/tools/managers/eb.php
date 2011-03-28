<?
if(!check_perms('users_mod')) { error(403); }

show_header('Manage email blacklist');
$DB->query("SELECT 
	eb.ID,
	eb.UserID,
	eb.Time,
	eb.Email,
	eb.Comment,
	um.Username 
	FROM email_blacklist AS eb 
	LEFT JOIN users_main AS um ON um.ID=eb.UserID
	ORDER BY eb.Time DESC");
?>
<h2>Email Blacklist</h2>
<table>
	<tr class="colhead">
		<td>Email</td>
		<td>Comment</td>
		<td>Added</td>
		<td>Submit</td>
	</tr>
<? while(list($ID, $UserID, $Time, $Email, $Comment, $Username) = $DB->next_record()) { ?>
	<tr>
		<form action="tools.php" method="post">
			<td>
				<input type="hidden" name="action" value="eb_alter" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="id" value="<?=$ID?>" />
				<input type="text" name="email" value="<?=display_str($Email)?>" size="30" />
			</td>
			<td>
				<input type="text" name="comment" value="<?=display_str($Comment)?>" size="60" />
			</td>
			<td>
				<?=format_username($UserID, $Username)?><br />
				<?=time_diff($Time, 1)?></td>
			<td>
				<input type="submit" name="submit" value="Edit" />
				<input type="submit" name="submit" value="Delete" />
			</td>
		</form>
	</tr>
<? } ?>
<tr>
	<td colspan="4" class="colhead">Add To Email Blacklist</td>
</tr>
<tr class="rowa">
	<form action="tools.php" method="post">
		<input type="hidden" name="action" value="eb_alter" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<td>
			<input type="text" name="email" size="30" />
		</td>
		<td colspan="2">
			<input type="text" name="comment" size="60" />
		</td>
		<td>
			<input type="submit" value="Create" />
		</td>
	</form>
</tr>
</table>
<? show_footer(); ?>
