<?
Text::$TOC = true;

G::$DB->query("
		SELECT
			q.ID,
			q.Question,
			q.UserID,
			q.Date AS QuestionDate,
			(
				SELECT COUNT(1)
				FROM staff_answers
				WHERE QuestionID = q.ID
			) AS Responses
		FROM user_questions AS q
			JOIN staff_answers AS a ON q.ID = a.QuestionID
		GROUP BY q.ID
		ORDER BY Responses DESC");

$Questions = G::$DB->to_array();

View::show_header('Popular Questions', 'questions,bbcode');

?>
<div class="thin">
	<div class="header">
		<h2>Popular Questions</h2>
	</div>
	<div class="linkbox">
<?		if (check_perms("users_mod")) { ?>
			<a class="brackets" href="questions.php">View questions</a>
<?		} else { ?>
			<a class="brackets" href="questions.php">Ask question</a>
<?		} ?>
		<a class="brackets" href="questions.php?action=answers">View staff answers</a>
	</div>
<?	foreach($Questions as $Question) { ?>
		<div id="question<?=$Question['ID']?>" class="box box2">
			<div class="head">
				<span>
					<a class="post_id" href="questions.php?action=popular_questions#question<?=$Question['ID']?>">#<?=$Question['ID']?></a>
					Question by <?=Users::format_username($Question['UserID'])?> - <?=time_diff($Question['QuestionDate'])?>
				</span>
				<span style="float: right;">
					<a href="#" id="<?=$Question['ID']?>" class="view_responses brackets"><?=$Question['Responses'] == 1 ? ("View " . $Question['Responses'] . " response") : ("View " . $Question['Responses'] . " responses")?></a>
<?		if (check_perms("users_mod")) { ?>
					-
					<form class="hidden" id="delete_<?=$Question['ID']?>" method="post" action="">
						<input type="hidden" name="action" value="take_remove_answer" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="question_id" value="<?=$Question['ID']?>" />
					</form>
					<a href="#" onclick="if (confirm('Are you sure?') == true) { $('#delete_<?=$Question['ID']?>').raw().submit(); } return false;" class="brackets">Delete</a>
<?		} ?>
				</span>
			</div>
			<div class="pad">
<?=				Text::full_format($Question['Question'])?>
			</div>
		</div>
<?	} ?>
</div>
<?
View::show_footer();
