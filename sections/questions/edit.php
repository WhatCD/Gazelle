<?

$ID = $_GET['id'];

if (!is_number($ID)) {
	error(404);
}

$UserID = $_GET['userid'];

if ($UserID != $LoggedUser['ID']) {
	error(403);
}

$DB->query("
	SELECT Answer
	FROM staff_answers
	WHERE QuestionID = '$ID' AND UserID = '$UserID'");

if (!$DB->has_results()) {
	error("Question not found");
}

list($Answer) = $DB->next_record();

View::show_header('Ask the Staff', 'bbcode');
?>
<div class="thin">
	<h2>
		Edit Answer
	</h2>
	<div class="linkbox">
		<a class="brackets" href="questions.php">View questions</a>
		<a class="brackets" href="questions.php?action=answers">View staff answers</a>
		<a class="brackets" href="questions.php?action=popular_questions">Popular questions</a>
	</div>
	<form method="post" class="box box2 center" action="">
		<input type="hidden" name="action" value="take_edit_answer" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="id" value="<?=$ID?>" />
		<input type="hidden" name="userid" value="<?=$UserID?>" />
		<? new TEXTAREA_PREVIEW("edit", "edit", $Answer, 40, 8); ?>
		<input type="submit" class="submit" value="Answer" />
	</form>
</div>
<?
View::show_footer();
