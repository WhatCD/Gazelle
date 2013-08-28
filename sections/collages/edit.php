<?
if (!empty($_GET['collageid']) && is_number($_GET['collageid'])) {
	$CollageID = $_GET['collageid'];
}
if (!is_number($CollageID)) {
	error(0);
}

$DB->query("
	SELECT Name, Description, TagList, UserID, CategoryID, Locked, MaxGroups, MaxGroupsPerUser, Featured
	FROM collages
	WHERE ID = '$CollageID'");
list($Name, $Description, $TagList, $UserID, $CategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser, $Featured) = $DB->next_record();
$TagList = implode(', ', explode(' ', $TagList));

if ($CategoryID == 0 && $UserID != $LoggedUser['ID'] && !check_perms('site_collages_delete')) {
	error(403);
}

View::show_header('Edit collage');

if (!empty($Err)) {
	if (isset($ErrNoEscape)) {
		echo '<div class="save_message error">'.$Err.'</div>';
	} else {
		echo '<div class="save_message error">'.display_str($Err).'</div>';
	}
}
?>
<div class="thin">
	<div class="header">
		<h2>Edit collage <a href="collages.php?id=<?=$CollageID?>"><?=$Name?></a></h2>
	</div>
	<form class="edit_form" name="collage" action="collages.php" method="post">
		<input type="hidden" name="action" value="edit_handle" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="collageid" value="<?=$CollageID?>" />
		<table id="edit_collage" class="layout">
<?	if (check_perms('site_collages_delete') || ($CategoryID == 0 && $UserID == $LoggedUser['ID'] && check_perms('site_collages_renamepersonal'))) { ?>
			<tr>
				<td class="label">Name</td>
				<td><input type="text" name="name" size="60" value="<?=$Name?>" /></td>
			</tr>
<?
	}
if ($CategoryID > 0 || check_perms('site_collages_delete')) { ?>
			<tr>
				<td class="label"><strong>Category</strong></td>
				<td>
					<select name="category">
<?
	foreach ($CollageCats as $CatID => $CatName) {
		if (!check_perms('site_collages_delete') && $CatID == 0) {
			// Only mod-type get to make things personal
			continue;
		}
?>
		<option value="<?=$CatID?>"<?=$CatID == $CategoryID ? ' selected="selected"' : ''?>><?=$CatName?></option>
<?	} ?>
					</select>
				</td>
			</tr>
<?	} ?>
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
<?	if ($CategoryID == 0) { // CategoryID == 0 is for "personal" collages ?>
			<tr>
				<td class="label"><span class="tooltip" title="A &quot;featured&quot; personal collage will be listed first on your profile, along with a preview of the included torrents.">Featured</span></td>
				<td><input type="checkbox" name="featured"<?=($Featured ? ' checked="checked"' : '')?> /></td>
			</tr>
<?
	}
if (check_perms('site_collages_delete')) { ?>
			<tr>
				<td class="label">Locked</td>
				<td><input type="checkbox" name="locked" <?=$Locked ? 'checked="checked" ' : ''?>/></td>
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
<? View::show_footer(); ?>
