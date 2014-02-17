<?
Text::$TOC = true;

$UserID = $_GET['userid'];

if (!is_number($UserID)) {
	error("No UserID");
}

$UserInfo = Users::user_info($UserID);

G::$DB->query("
		SELECT
			u.Username,
			q.ID,
			q.Question,
			q.UserID,
			q.Date AS QuestionDate,
			a.Answer,
			a.Date AS AnswerDate,
			(
				SELECT COUNT(1)
				FROM staff_answers
				WHERE QuestionID = q.ID
					AND UserID != '$UserID'
			) AS Responses
		FROM user_questions AS q
			JOIN staff_answers AS a ON q.ID = a.QuestionID
			JOIN users_main AS u ON u.ID = q.UserID
		WHERE a.UserID = '$UserID'
		ORDER BY AnswerDate DESC");

$Questions = G::$DB->to_array();

View::show_header($UserInfo['Username'] . "'s answers", 'questions,bbcode');

?>
<div class="thin">
	<div class="header">
		<h2><?=$UserInfo['Username']?>'s Answers</h2>
	</div>
	<div class="linkbox">
<?	if (check_perms("users_mod")) { ?>
		<a class="brackets" href="questions.php">View questions</a>
<?	} else { ?>
		<a class="brackets" href="questions.php">Ask question</a>
<?	} ?>
		<a class="brackets" href="questions.php?action=answers">View staff answers</a>
		<a class="brackets" href="questions.php?action=popular_questions">Popular questions</a>
	</div>
<?	foreach($Questions as $Question) { ?>
	<div id="question<?=$Question['ID']?>" class="box box2">
		<div class="head">
			<span>
				<a class="post_id" href="questions.php?action=view_answers&amp;userid=<?=$UserID?>#question<?=$Question['ID']?>">#<?=$Question['ID']?></a>
				Question by <?=Users::format_username($Question['UserID'])?> - <?=time_diff($Question['QuestionDate'])?>
			</span>
			<span style="float: right;">
<?		if ($Question['Responses'] > 0) { ?>
				<a href="#" data-gazelle-userid="<?=$UserID?>" id="<?=$Question['ID']?>" class="view_responses brackets"><?=$Question['Responses'] == 1 ? ("View " . $Question['Responses'] . " other response") : ("View " . $Question['Responses'] . " other responses")?></a>
				-
<?
		}
		if (check_perms("users_mod")) {
?>
				<form class="hidden" id="delete_<?=$Question['ID']?>" method="post">
					<input type="hidden" name="action" value="take_remove_answer" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="question_id" value="<?=$Question['ID']?>" />
				</form>
				<a href="#" onclick="if (confirm('Are you sure?') == true) { $('#delete_<?=$Question['ID']?>').raw().submit(); } return false;" class="brackets">Delete</a>
<?
		}
		if ($LoggedUser['ID'] == $UserID) {
?>
				<a href="questions.php?action=edit&amp;id=<?=$Question['ID']?>&amp;userid=<?=$UserID?>" class="brackets">Edit</a>
<?		} ?>
			</span>
		</div>
		<div class="pad">
<?=			Text::full_format("[quote=" . $Question['Username'] . "]". $Question['Question'] . "[/quote]\n". $Question['Answer'])?>
		</div>
	</div>
<?	} ?>
</div>
<?
View::show_footer();
