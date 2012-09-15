<?
if (!check_perms('users_view_email')) { error(403);
}

show_header('Manage email blacklist');
$DB -> query("SELECT 
	eb.ID,
	eb.UserID,
	eb.Time,
	eb.Email,
	eb.Comment
	FROM email_blacklist AS eb 
	ORDER BY eb.Time DESC");
?>
<div class="header">
	<h2>Email Blacklist</h2>
</div>
<table>
	<tr class="colhead">
		<td>Email</td>
		<td>Comment</td>
		<td>Added</td>
		<td>Submit</td>
	</tr>
	<tr class="colhead">
		<td colspan="4">Add To Email or Domain to Blacklist</td>
	</tr>
	<tr class="rowa">
		<form class="add_form" name="email_blacklist" action="tools.php" method="post">
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
	<? while(list($ID, $UserID, $Time, $Email, $Comment) = $DB->next_record()) {
	?>
	<tr>
		<form class="manage_form" name="email_blacklist" action="tools.php" method="post">
			<td>
			<input type="hidden" name="action" value="eb_alter" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="id" value="<?=$ID?>" />
			<input type="text" name="email" value="<?=display_str($Email)?>" size="30" />
			</td>
			<td>
			<input type="text" name="comment" value="<?=display_str($Comment)?>" size="60" />
			</td>
			<td><?=format_username($UserID, false, false, false)
			?><br /><?=time_diff($Time, 1)
			?></td>
			<td>
			<input type="submit" name="submit" value="Edit" />
			<input type="submit" name="submit" value="Delete" />
			</td>
		</form>
	</tr>
	<?  }?>
</table>
<? show_footer();?>