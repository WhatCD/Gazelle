<?
if (!check_perms('users_mod')) {
	error(403);
}

if (isset($_POST['doit'])) {
	authorize();

	if (isset($_POST['oldtags'])) {
		$OldTagIDs = $_POST['oldtags'];
		foreach ($OldTagIDs AS $OldTagID) {
			if (!is_number($OldTagID)) {
				error(403);
			}
		}
		$OldTagIDs = implode(', ', $OldTagIDs);

		$DB->query("
			UPDATE tags
			SET TagType = 'other'
			WHERE ID IN ($OldTagIDs)");
	}

	if ($_POST['newtag']) {
		$TagName = Misc::sanitize_tag($_POST['newtag']);

		$DB->query("
			SELECT ID
			FROM tags
			WHERE Name LIKE '$TagName'");
		list($TagID) = $DB->next_record();

		if ($TagID) {
			$DB->query("
				UPDATE tags
				SET TagType = 'genre'
				WHERE ID = $TagID");
		} else { // Tag doesn't exist yet - create tag
			$DB->query("
				INSERT INTO tags
					(Name, UserID, TagType, Uses)
				VALUES
					('$TagName', ".$LoggedUser['ID'].", 'genre', 0)");
			$TagID = $DB->inserted_id();
		}
	}

	$Cache->delete_value('genre_tags');
}

View::show_header('Official Tags Manager');
?>
<div class="header">
	<h2>Official Tags Manager</h2>
</div>
<div style="text-align: center;">
	<div style="display: inline-block;">
		<form class="manage_form" name="tags" method="post" action="">
			<input type="hidden" name="action" value="official_tags" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="doit" value="1" />
			<table class="tags_table layout">
				<tr class="colhead_dark">
					<td style="font-weight: bold; text-align: center;">Remove</td>
					<td style="font-weight: bold;">Tag</td>
					<td style="font-weight: bold;">Uses</td>
					<td>&nbsp;&nbsp;&nbsp;</td>
					<td style="font-weight: bold; text-align: center;">Remove</td>
					<td style="font-weight: bold;">Tag</td>
					<td style="font-weight: bold;">Uses</td>
					<td>&nbsp;&nbsp;&nbsp;</td>
					<td style="font-weight: bold; text-align: center;">Remove</td>
					<td style="font-weight: bold;">Tag</td>
					<td style="font-weight: bold;">Uses</td>
				</tr>
<?
$i = 0;
$DB->query("
	SELECT ID, Name, Uses
	FROM tags
	WHERE TagType = 'genre'
	ORDER BY Name ASC");
$TagCount = $DB->record_count();
$Tags = $DB->to_array();
for ($i = 0; $i < $TagCount / 3; $i++) {
	list($TagID1, $TagName1, $TagUses1) = $Tags[$i];
	list($TagID2, $TagName2, $TagUses2) = $Tags[ceil($TagCount / 3) + $i];
	list($TagID3, $TagName3, $TagUses3) = $Tags[2 * ceil($TagCount / 3) + $i];
?>
				<tr class="<?=(($i % 2) ? 'rowa' : 'rowb')?>">
					<td style="text-align: center;"><input type="checkbox" name="oldtags[]" value="<?=$TagID1?>" /></td>
					<td><?=$TagName1?></td>
					<td style="text-align: center;"><?=number_format($TagUses1)?></td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td style="text-align: center;">
<?		if ($TagID2) { ?>
						<input type="checkbox" name="oldtags[]" value="<?=$TagID2?>" />
<?		} ?>
					</td>
					<td><?=$TagName2?></td>
					<td style="text-align: center;"><?=number_format($TagUses2)?></td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td style="text-align: center;">
<?		if ($TagID3) { ?>
						<input type="checkbox" name="oldtags[]" value="<?=$TagID3?>" />
<?		} ?>
					</td>
					<td><?=$TagName3?></td>
					<td style="text-align: center;"><?=number_format($TagUses3)?></td>
				</tr>
<?
}
?>
				<tr class="<?=(($i % 2) ? 'rowa' : 'rowb')?>">
					<td colspan="11">
						<label for="newtag">New official tag: </label><input type="text" name="newtag" />
					</td>
				</tr>
				<tr style="border-top: thin solid;">
					<td colspan="11" style="text-align: center;">
						<input type="submit" value="Submit changes" />
					</td>
				</tr>

			</table>
		</form>
	</div>
</div>
<? View::show_footer(); ?>
