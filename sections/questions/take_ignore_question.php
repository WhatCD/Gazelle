<?
authorize();

if (!check_perms("users_mod")) {
	error(404);
}
$ID = $_POST['id'];

if (!is_number($ID)) {
	error(404);
}

G::$DB->query("
	INSERT INTO staff_ignored_questions
		(QuestionID, UserID)
	VALUES
		('$ID', '$LoggedUser[ID]')");

