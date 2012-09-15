<?
if(!check_perms('admin_dnu')) { error(403); }

show_header('Manage do not upload list');
$DB->query("SELECT 
	d.ID,
	d.Name, 
	d.Comment, 
	d.UserID, 
	d.Time 
	FROM do_not_upload as d
	LEFT JOIN users_main AS um ON um.ID=d.UserID
	ORDER BY d.Time DESC");
?>
<div class="header">
	<h2>Do Not Uploads</h2>
</div>
<table>
	<tr class="colhead">
		<td>Name</td>
		<td>Comment</td>
		<td>Added</td>
		<td>Submit</td>
	</tr>
<? while(list($ID, $Name, $Comment, $UserID, $DNUTime) = $DB->next_record()){ ?>
	<tr>
		<form class="manage_form" class="dnu" action="tools.php" method="post">
			<td>
				<input type="hidden" name="action" value="dnu_alter" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="id" value="<?=$ID?>" />
				<input type="text" name="name" value="<?=display_str($Name)?>" size="30" />
			</td>
			<td>
				<input type="text" name="comment" value="<?=display_str($Comment)?>" size="60" />
			</td>
			<td>
				<?=format_username($UserID, false, false, false)?><br />
				<?=time_diff($DNUTime, 1)?></td>
			<td>
				<input type="submit" name="submit" value="Edit" />
				<input type="submit" name="submit" value="Delete" />
			</td>
		</form>
	</tr>
<? } ?>
<tr class="colhead">
	<td colspan="4">Add Do Not Upload</td>
</tr>
<tr class="rowa">
	<form class="add_form" name="dnu" action="tools.php" method="post">
		<input type="hidden" name="action" value="dnu_alter" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<td>
			<input type="text" name="name" size="30" />
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