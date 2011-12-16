<?
show_header('Create a collage');

if (!check_perms('site_collages_renamepersonal')) {
	$ChangeJS = "OnChange=\"if ( this.options[this.selectedIndex].value == '0') { $('#namebox').hide(); $('#personal').show(); } else { $('#namebox').show(); $('#personal').hide(); }\"";
}

$Name        = $_REQUEST['name'];
$Category    = $_REQUEST['cat'];
$Description = $_REQUEST['descr'];
$Tags        = $_REQUEST['tags'];
$Error       = $_REQUEST['err'];

if (!check_perms('site_collages_renamepersonal') && $Category === '0') {
	$NoName = true;
}
?>
<div class="thin">
<?
if (!empty($Error)) { ?>
	<div class="save_message error"><?=display_str($Error)?></div>
	<br />
<? } ?>
	<form action="collages.php" method="post" name="newcollage">
		<input type="hidden" name="action" value="new_handle" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<table>
			<tr id="collagename">
				<td class="label"><strong>Name</strong></td>
				<td>
					<input type="text" class="<?=$NoName?'hidden':''?>" name="name" size="60" id="namebox" value="<?=display_str($Name)?>" />
					<span id="personal" class="<?=$NoName?'':'hidden'?>" style="font-style: oblique"><strong><?=$LoggedUser['Username']?>'s personal collage</strong></span>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Category</strong></td>
				<td>
					<select name="category" <?=$ChangeJS?>>
<?
array_shift($CollageCats);
		
foreach($CollageCats as $CatID=>$CatName) { ?>
						<option value="<?=$CatID+1?>"<?=(($CatID+1 == $Category)?' selected':'')?>><?=$CatName?></option>
<? } 
$DB->query("SELECT COUNT(ID) FROM collages WHERE UserID='$LoggedUser[ID]' AND CategoryID='0' AND Deleted='0'");
list($CollageCount) = $DB->next_record();
if(($CollageCount < $LoggedUser['Permissions']['MaxCollages']) && check_perms('site_collages_personal')) { ?>
						<option value="0"<?=(($Category === '0')?' selected':'')?>>Personal</option>
<? } ?>
					</select>
					<br />
					<ul>
						<li><strong>Theme</strong> - A collage containing releases that all relate to a certain theme (Searching for the perfect beat, for instance)</li>	
						<li><strong>Genre introduction</strong> - A subjective introduction to a Genre composed by our own users</li>
						<li><strong>Discography</strong> - A collage containing all the releases of an artist, when that artist has a multitude of side projects</li>
						<li><strong>Label</strong> - A collage containing all the releases of a particular record label</li>
						<li><strong>Staff picks</strong> - A list of recommendations picked by the staff on special occasions</li>
						<li><strong>Charts</strong> - A collage containing all the releases that comprise a certain chart (Billboard Top 100, Pitchfork Top 100, What.cd Top 10 for a certain week)</li>
<?
   if(($CollageCount < $LoggedUser['Permissions']['MaxCollages']) && check_perms('site_collages_personal')) { ?>
						<li><strong>Personal</strong> - You can put whatever your want here.  It's your personal collage.</li>	
<? } ?>					
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
					<strong>Please ensure your collage will be allowed under the <a href="rules.php?p=collages">rules</a></strong>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center"><input type="submit" value="Create collage" /></td>
			</tr>
		</table>
	</form>
</div>
<? show_footer(); ?>
