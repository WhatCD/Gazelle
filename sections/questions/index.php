<?

enforce_login();

if (!isset($_REQUEST['action'])) {
	if (check_perms('users_mod')) {
		include(SERVER_ROOT.'/sections/questions/questions.php');
	}
	else {
		include(SERVER_ROOT.'/sections/questions/ask_question.php');
	}
} else {
	switch ($_REQUEST['action']) {
		case 'take_ask_question':
			include(SERVER_ROOT.'/sections/questions/take_ask_question.php');
			break;
		case 'answer_question':
			include(SERVER_ROOT.'/sections/questions/answer_question.php');
			break;
		case 'take_answer_question':
			include(SERVER_ROOT.'/sections/questions/take_answer_question.php');
			break;
		case 'take_remove_question':
			include(SERVER_ROOT.'/sections/questions/take_remove_question.php');
			break;
		case 'take_remove_answer':
			include(SERVER_ROOT.'/sections/questions/take_remove_answer.php');
			break;
		case 'questions':
			include(SERVER_ROOT.'/sections/questions/questions.php');
			break;
		case 'answers':
			include(SERVER_ROOT.'/sections/questions/answers.php');
			break;
		case 'view_answers':
			include(SERVER_ROOT.'/sections/questions/view_answers.php');
			break;
		case 'popular_questions':
			include(SERVER_ROOT.'/sections/questions/popular_questions.php');
			break;
		case 'ajax_get_answers':
			include(SERVER_ROOT.'/sections/questions/ajax_get_answers.php');
			break;
		case 'take_ignore_question':
			include(SERVER_ROOT.'/sections/questions/take_ignore_question.php');
			break;
		case 'edit':
			include(SERVER_ROOT.'/sections/questions/edit.php');
			break;
		case 'take_edit_answer':
			include(SERVER_ROOT.'/sections/questions/take_edit_answer.php');
			break;
		default:
			error(404);
			break;
	}
}
