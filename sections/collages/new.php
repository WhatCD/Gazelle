<?
show_header('Create a collage');
?>
<div class="thin">
	<form action="collages.php" method="post">
		<input type="hidden" name="action" value="new_handle" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<table id="new_collage">
			<tr>
				<td class="label"><strong>Name</strong></td>
				<td>
					<input type="text" id="name" name="name" size="60" />
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Category</strong></td>
				<td>
					<select name="category">
<?
array_shift($CollageCats);
foreach($CollageCats as $CatID=>$CatName) { ?>
						<option value="<?=$CatID+1?>"><?=$CatName?></option>
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

					</ul>
				</td>
			</tr>
			<tr>
				<td class="label">Description</td>
				<td>
					<textarea name="description" id="description" cols="60" rows="10"></textarea>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Tags (comma-separated)</strong></td>
				<td>
					<input type="text" id="tags" name="tags" size="60" />
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
