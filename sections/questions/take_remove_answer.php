<?
authorize();

if (!check_perms("users_mod")) {
	error(404);
}

$ID = $_POST['question_id'];

if (!is_number($ID)) {
	error(404);
}

G::$DB->query("DELETE FROM staff_answers WHERE QuestionID = '$ID' AND UserID = '$LoggedUser[ID]'");

header("Location: questions.php");
