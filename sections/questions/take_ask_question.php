<?
DEFINE('MAX_QUESTIONS', 50);

authorize();

$DB->query("SELECT COUNT(1) FROM user_questions WHERE UserID = '$LoggedUser[ID]'");
if ($DB->record_count() >= MAX_QUESTIONS) {
	error("You have asked too many questions for the time being.");
}

$Question = db_string($_POST['question']);

if (empty($Question)) {
	error("No question asked");
}

$UserID = $LoggedUser['ID'];
$Date = sqltime();

$DB->query("
	INSERT INTO user_questions
		(Question, UserID, Date)
	VALUES
		('$Question', '$UserID', '$Date')");

header("Location: questions.php");
