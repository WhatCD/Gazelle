<?
Text::$TOC = true;
if (!check_perms("users_mod")) {
	error(403);
}

$ID = $_GET['id'];

if (!is_number($ID)) {
	error(404);
}

$DB->query("
	SELECT ID, Question, UserID, Date
	FROM user_questions
	WHERE ID = '$ID'");
$Question = $DB->next_record();

View::show_header('Ask the Staff', 'bbcode');
?>
<div class="thin">
	<h2>
		Answer Question
	</h2>
	<div class="linkbox">
		<a class="brackets" href="questions.php">View questions</a>
		<a class="brackets" href="questions.php?action=answers">View staff answers</a>
		<a class="brackets" href="questions.php?action=popular_questions">Popular questions</a>
	<div class="box box2">
		<div class="head">
			<span>
				#<?=$Question['ID']?>
				<?=Users::format_username($Question['UserID'])?> - <?=time_diff($Question['Date'])?>
			</span>
		</div>
		<div class="pad">
<?=			Text::full_format($Question['Question'])?>
		</div>
	</div>
	<div class="center box pad">
		<form method="post">
			<input type="hidden" name="action" value="take_answer_question" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="id" value="<?=$ID?>" />
			<textarea id="answer" class="required" onkeyup="resize('answer');" name="answer" cols="90" rows="8"></textarea>
			<div id="buttons" class="center">
				<input type="submit" class="submit" id="submit_button" value="Answer" />
			</div>
		</form>
	</div>
</div>
<?
View::show_footer();
