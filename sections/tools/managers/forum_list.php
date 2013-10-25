<?
function class_list($Selected = 0) {
	global $Classes;
	$Return = '';
	foreach ($Classes as $ID => $Class) {
		if ($Class['Secondary']) {
			continue;
		}

		$Name = $Class['Name'];
		$Level = $Class['Level'];
		$Return .= "<option value=\"$Level\"";
		if ($Selected == $Level) {
			$Return .= ' selected="selected"';
		}
		$Return .= '>'.Format::cut_string($Name, 20, 1)."</option>\n";
	}
	reset($Classes);
	return $Return;
}

if (!check_perms('admin_manage_forums')) {
	error(403);
}

View::show_header('Forum Management');
$DB->query('
	SELECT ID, Name
	FROM forums
	ORDER BY Sort');
$ForumArray = $DB->to_array(); // used for generating the 'parent' drop down list

// Replace the old hard-coded forum categories
unset($ForumCats);
$ForumCats = $Cache->get_value('forums_categories');
if ($ForumCats === false) {
	$DB->query('
		SELECT ID, Name
		FROM forums_categories');
	$ForumCats = array();
	while (list($ID, $Name) = $DB->next_record()) {
		$ForumCats[$ID] = $Name;
	}
	$Cache->cache_value('forums_categories', $ForumCats, 0); //Inf cache.
}

$DB->query('
	SELECT
		ID,
		CategoryID,
		Sort,
		Name,
		Description,
		MinClassRead,
		MinClassWrite,
		MinClassCreate,
		AutoLock,
		AutoLockWeeks
	FROM forums
	ORDER BY CategoryID, Sort ASC');
?>
<div class="header">
	<script type="text/javacript">document.getElementByID('content').style.overflow = 'visible';</script>
	<h2>Forum control panel</h2>
</div>
<table width="100%">
	<tr class="colhead">
		<td>Category</td>
		<td>Sort</td>
		<td>Name</td>
		<td>Description</td>
		<td>Min class read</td>
		<td>Min class write</td>
		<td>Min class create</td>
		<td>Auto-lock</td>
		<td>Auto-lock weeks</td>
		<td>Submit</td>
	</tr>
<?
$Row = 'b';
while (list($ID, $CategoryID, $Sort, $Name, $Description, $MinClassRead, $MinClassWrite, $MinClassCreate, $AutoLock, $AutoLockWeeks) = $DB->next_record()) {
	$Row = $Row === 'a' ? 'b' : 'a';
?>
	<tr class="row<?=$Row?>">
		<form class="manage_form" name="forums" action="" method="post">
			<input type="hidden" name="id" value="<?=$ID?>" />
			<input type="hidden" name="action" value="forum_alter" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<td>
				<select name="categoryid">
<?	reset($ForumCats);
	foreach ($ForumCats as $CurCat => $CatName) {
?>
					<option value="<?=$CurCat?>"<? if ($CurCat == $CategoryID) { echo ' selected="selected"'; } ?>><?=$CatName?></option>
<?	} ?>
				</select>
			</td>
			<td>
				<input type="text" size="3" name="sort" value="<?=$Sort?>" />
			</td>
			<td>
				<input type="text" size="10" name="name" value="<?=$Name?>" />
			</td>
			<td>
				<input type="text" size="20" name="description" value="<?=$Description?>" />
			</td>
			<td>
				<select name="minclassread">
					<?=class_list($MinClassRead)?>
				</select>
			</td>
			<td>
				<select name="minclasswrite">
					<?=class_list($MinClassWrite)?>
				</select>
			</td>
			<td>
				<select name="minclasscreate">
					<?=class_list($MinClassCreate)?>
				</select>
			</td>
			<td>
				<input type="checkbox" name="autolock"<?=($AutoLock == '1') ? ' checked="checked"' : ''?> />
			</td>
			<td>
				<input type="text" name="autolockweeks" value="<?=$AutoLockWeeks?>" />
			</td>
			<td>
				<input type="submit" name="submit" value="Edit" />
				<input type="submit" name="submit" value="Delete" />
			</td>

		</form>
	</tr>
<?
}
?>
	<tr class="colhead">
		<td colspan="8">Create forum</td>
	</tr>
	<tr class="rowa">
		<form class="create_form" name="forum" action="" method="post">
			<input type="hidden" name="action" value="forum_alter" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<td>
				<select name="categoryid">
<?	reset($ForumCats);
	while (list($CurCat, $CatName) = each($ForumCats)) { ?>
					<option value="<?=$CurCat?>"<? if ($CurCat == $CategoryID) { echo ' selected="selected"'; } ?>><?=$CatName?></option>
<?	} ?>
				</select>
			</td>
			<td>
				<input type="text" size="3" name="sort" />
			</td>
			<td>
				<input type="text" size="10" name="name" />
			</td>
			<td>
				<input type="text" size="20" name="description" />
			</td>
			<td>
				<select name="minclassread">
					<?=class_list()?>
				</select>
			</td>
			<td>
				<select name="minclasswrite">
					<?=class_list()?>
				</select>
			</td>
			<td>
				<select name="minclasscreate">
					<?=class_list()?>
				</select>
			</td>
			<td>
				<input type="checkbox" name="autolock" checked="checked" />
			</td>
			<td>
				<input type="text" name="autolockweeks" value="4" />
			</td>
			<td>
				<input type="submit" value="Create" />
			</td>

		</form>
	</tr>
</table>
<? View::show_footer(); ?>
