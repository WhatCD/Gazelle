<?
$CollageID = $_GET['collageid'];
if(!is_number($CollageID)) { error(0); }

$DB->query("SELECT Name, Description, TagList, UserID, CategoryID, Locked, MaxGroups, MaxGroupsPerUser FROM collages WHERE ID='$CollageID'");
list($Name, $Description, $TagList, $UserID, $CategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser) = $DB->next_record();
$TagList = implode(', ', explode(' ', $TagList));

if($CategoryID == 0 && $UserID!=$LoggedUser['ID'] && !check_perms('site_collages_delete')) { error(403); }

show_header('Edit collage');
?>
<div class="thin">
	<h2>Edit collage <a href="collages.php?id=<?=$CollageID?>"><?=$Name?></a></h2>
	<form action="collages.php" method="post">
		<input type="hidden" name="action" value="edit_handle" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="collageid" value="<?=$CollageID?>" />
		<table id="edit_collage">
<? if (check_perms('site_collages_delete')) { ?>
			<tr>
				<td class="label">Name</td>
				<td><input type="text" name="name" size="60" value="<?=$Name?>" /></td>
			</tr>
<? } ?>
<? if($CategoryID>0) { ?>
			<tr>
				<td class="label"><strong>Category</strong></td>
				<td>
					<select name="category">
<?
	array_shift($CollageCats);
	foreach($CollageCats as $CatID=>$CatName) { ?>
						<option value="<?=$CatID+1?>" <? if($CatID+1 == $CategoryID) { echo ' selected="selected"'; }?>><?=$CatName?></option>
<?	} ?>
					</select>
				</td>
			</tr>
<? } ?>
			<tr>
				<td class="label">Description</td>
				<td>
					<textarea name="description" id="description" cols="60" rows="10"><?=$Description?></textarea>
				</td>
			</tr>
			<tr>
				<td class="label">Tags</td>
				<td><input type="text" name="tags" size="60" value="<?=$TagList?>" /></td>
			</tr>
<? if(check_perms('site_collages_delete')) { ?>
			<tr>
				<td class="label">Locked</td>
				<td><input type="checkbox" name="locked" <?if($Locked) { ?>checked="checked" <? }?>/></td>
			</tr>
			<tr>
				<td class="label">Max groups</td>
				<td><input type="text" name="maxgroups" size="5" value="<?=$MaxGroups?>" /></td>
			</tr>
			<tr>
				<td class="label">Max groups per user</td>
				<td><input type="text" name="maxgroupsperuser" size="5" value="<?=$MaxGroupsPerUser?>" /></td>
			</tr>
			
<? } ?>
			<tr>
				<td colspan="2" class="center"><input type="submit" value="Edit collage" /></td>
			</tr>
		</table>
	</form>
</div>
<? show_footer(); ?>
