<?

authorize();

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