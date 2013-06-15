<?
$PerPage = POSTS_PER_PAGE;
list($Page, $Limit) = Format::page_limit($PerPage);

$CanEdit = check_perms('users_mod');

if ($CanEdit && isset($_POST['perform'])) {
	authorize();
	if ($_POST['perform'] == 'add' && !empty($_POST['message'])) {
		$Message = db_string($_POST['message']);
		$Author = db_string($_POST['author']);
		$DB->query("
			INSERT INTO changelog (Message, Author, Time)
			VALUES ('$Message', '$Author', NOW())");
	}
	if ($_POST['perform'] == 'remove' && !empty($_POST['change_id'])) {
		$ID = (int) $_POST['change_id'];
		$DB->query("
			DELETE FROM changelog
			WHERE ID = '$ID'");
	}
}

$DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		ID,
		Message,
		Author,
		Date(Time) as Time
	FROM changelog
	ORDER BY ID DESC
	LIMIT $Limit");
$ChangeLog = $DB->to_array();
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();

View::show_header('Gazelle Change Log');
?>
<div class="thin">
	<h2>Gazelle Change Log</h2>
	<div class="linkbox">
<?
	$Pages = Format::get_pages($Page, $NumResults, $PerPage, 11);
	echo "\t\t$Pages\n";
?>
	</div>
	<div class="main_column">
		<br />
<?		if ($CanEdit) { ?>
		<form method="post">
			<div class="box">
				<div class="head">
					Manually submit a new change to the change log
				</div>
				<div class="pad">
					<input type="hidden" name="perform" value="add" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<label><strong>Commit message: </strong><input type="text" name="message" size="50" value="" /></label>
					<br /><br />
					<label><strong>Author: </strong><input type="text" name="author" value="<?=$LoggedUser['Username']?>" /></label>
					<br /><br />
					<input type="submit" value="Submit" />
				</div>
			</div>
		</form>
		<br />
<?		}

		foreach ($ChangeLog as $Change) { ?>
		<div class="box">
			<div class="head">
				<span style="float: left;"><?=$Change['Time']?> by <?=$Change['Author']?></span>
<?				if ($CanEdit) { ?>
					<span style="float: right;">
						<form id="delete_<?=$Change['ID']?>" method="POST">
							<input type="hidden" name="perform" value="remove" />
							<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
							<input type="hidden" name="change_id" value="<?=$Change['ID']?>" />
						</form>
					<a href="#" onclick="$('#delete_<?=$Change['ID']?>').raw().submit(); return false;" class="brackets">Delete</a>
					</span>
<?				} ?>
				<br />
			</div>
			<div class="pad">
				<?=$Change['Message']?>
			</div>
		</div>
<?		} ?>
	</div>
</div>
<? View::show_footer(); ?>
