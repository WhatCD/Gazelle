<?
authorize();

if (!check_perms("users_mod")) {
	error(404);
}

if (!empty($_POST['create'])) {
	Calendar::create_event($_POST['title'], $_POST['body'], $_POST['category'], $_POST['importance'], $_POST['team'], $LoggedUser['ID'], $_POST['start_date'], $_POST['end_date']);
} elseif (!empty($_POST['update'])) {
	Calendar::update_event($_POST['id'], $_POST['title'], $_POST['body'], $_POST['category'], $_POST['importance'], $_POST['team'], $_POST['start_date'], $_POST['end_date']);
} elseif (!empty($_POST['delete'])) {
	Calendar::remove_event($_POST['id']);
}

header("Location: tools.php?action=calendar");