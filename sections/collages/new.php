<?
View::show_header('Create a collage');

if (!check_perms('site_collages_renamepersonal')) {
	$ChangeJS = " onchange=\"if ( this.options[this.selectedIndex].value == '0') { $('#namebox').ghide(); $('#personal').gshow(); } else { $('#namebox').gshow(); $('#personal').ghide(); }\"";
}

if (!check_perms('site_collages_renamepersonal') && $Category === '0') {
	$NoName = true;
}
?>
<div class="thin">
<?
if (isset($Err)) { ?>
	<div class="save_message error"><?=$Err?></div>
	<br />
<?
} ?>
	<form class="create_form" name="collage" action="collages.php" method="post">
		<input type="hidden" name="action" value="new_handle" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<table class="layout">
			<tr id="collagename">
				<td class="label"><strong>Name</strong></td>
				<td>
					<input type="text"<?=$NoName ? ' class="hidden"' : ''; ?> name="name" size="60" id="namebox" value="<?=display_str($Name)?>" />
					<span id="personal"<?=$NoName ? '' : ' class="hidden"'; ?> style="font-style: oblique;"><strong><?=$LoggedUser['Username']?>'s personal collage</strong></span>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Category</strong></td>
				<td>
					<select name="category"<?=$ChangeJS?>>
<?
array_shift($CollageCats);

foreach ($CollageCats as $CatID=>$CatName) { ?>
						<option value="<?=$CatID + 1 ?>"<?=(($CatID + 1 == $Category) ? ' selected="selected"' : '')?>><?=$CatName?></option>
<?
}

$DB->query("
	SELECT COUNT(ID)
	FROM collages
	WHERE UserID = '$LoggedUser[ID]'
		AND CategoryID = '0'
		AND Deleted = '0'");
list($CollageCount) = $DB->next_record();
if (($CollageCount < $LoggedUser['Permissions']['MaxCollages']) && check_perms('site_collages_personal')) { ?>
						<option value="0"<?=(($Category === '0') ? ' selected="selected"' : '')?>>Personal</option>
<?
} ?>
					</select>
					<br />
					<ul>
						<li><strong>Theme</strong> - A collage containing releases that all relate to a certain theme (e.g. "Searching for the Perfect Beat", "Concept Albums", "Funky Groove", etc.).</li>
						<li><strong>Genre introduction</strong> - A subjective introduction to a genre composed by our own users.</li>
						<li><strong>Discography</strong> - A collage containing all the releases of an artist, which can be useful for keeping track of side projects.</li>
						<li><strong>Label</strong> - A collage containing all the releases of a particular record label.</li>
						<li><strong>Staff picks</strong> - A listing of recommendations picked by the staff on special occasions.</li>
						<li><strong>Charts</strong> - Contains all the releases that comprise a certain type of chart (e.g. Billboard Top 100, Pitchfork Top 100, <?=SITE_NAME?> Top 10, etc.).</li>
<?
	if (($CollageCount < $LoggedUser['Permissions']['MaxCollages']) && check_perms('site_collages_personal')) { ?>
						<li><strong>Personal</strong> - You can put whatever you want here. It is your own personal collage.</li>
<?	} ?>
					</ul>
				</td>
			</tr>
			<tr>
				<td class="label">Description</td>
				<td>
					<textarea name="description" id="description" cols="60" rows="10"><?=display_str($Description)?></textarea>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Tags (comma-separated)</strong></td>
				<td>
					<input type="text" id="tags" name="tags" size="60" value="<?=display_str($Tags)?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
					<strong>Please ensure your collage will be allowed under the <a href="rules.php?p=collages">Collage Rules</a>.</strong>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center"><input type="submit" value="Create collage" /></td>
			</tr>
		</table>
	</form>
</div>
<? View::show_footer(); ?>
