<?
if (!check_perms("users_mod")) {
	error(404);
}
include(SERVER_ROOT.'/classes/text.class.php');
$Text = new TEXT(true);

$DB->query("
			SELECT
				uq.ID, uq.Question, uq.UserID, uq.Date
			FROM user_questions AS uq
			WHERE uq.ID NOT IN
				(SELECT sq.QuestionID FROM staff_answers AS sq WHERE sq.UserID = '$LoggedUser[ID]')
			ORDER BY uq.Date DESC");
$Questions = $DB->to_array();

$DB->query("SELECT COUNT(1) FROM user_questions");
list($TotalQuestions) = $DB->next_record();

View::show_header("Ask the Staff", "questions");

?>

<div class="thin">
	<h2>
		User Questions
		<span style="float: right;">
			<?=$TotalQuestions?> questions asked, <?=count($Questions)?> left to answer
		</span>
	</h2>
	<div class="linkbox">
		<a class="brackets" href="questions.php?action=answers">View staff answers</a>
	</div>
<?	foreach($Questions as $Question) { ?>
	<div id="question<?=$Question['ID']?>" class="box box2">
		<div class="head">
			<span>
				<a class="post_id" href="questions.php#question<?=$Question['ID']?>">#<?=$Question['ID']?></a>
				<?=Users::format_username($Question['UserID'])?> - <?=time_diff($Question['Date'])?>
			</span>
			<span style="float: right;">
				<form class="hidden" id="delete_<?=$Question['ID']?>" method="POST">
					<input type="hidden" name="action" value="take_remove_question" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="question_id" value="<?=$Question['ID']?>" />
				</form>
				<a href="#" onclick="$('#delete_<?=$Question['ID']?>').raw().submit(); return false;" class="brackets">Delete</a>
				-
				<a href="#" id="<?=$Question['ID']?>" class="answer_link brackets">Answer</a>
			</span>
		</div>
		<div class="pad">
			<?=$Text->full_format($Question['Question'])?>
		</div>
	</div>
	<div id="answer<?=$Question['ID']?>" class="hidden center box pad">
		<textarea id="replybox_<?=$Question['ID']?>" class="required" onkeyup="resize('replybox_<?=$Question['ID']?>');" name="answer" cols="90" rows="8"></textarea>
		<div id="buttons" class="center">
			<input type="submit" class="submit submit_button" id="<?=$Question['ID']?>" value="Answer" />
		</div>
	</div>
<?	} ?>
</div>

<?
View::show_footer();