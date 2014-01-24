<?
enforce_login();
if (!check_perms('users_mod')) {
	error(404);
} else {
	Testing::init();

	if (!empty($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'class':
				include(SERVER_ROOT.'/sections/testing/class.php');
				break;
			case 'ajax_run_method':
				include(SERVER_ROOT.'/sections/testing/ajax_run_method.php');
				break;
			case 'comments':
				include(SERVER_ROOT.'/sections/testing/comments.php');
				break;
			default:
				include(SERVER_ROOT.'/sections/testing/classes.php');
				break;
		}
	} else {
		include(SERVER_ROOT.'/sections/testing/classes.php');
	}
}
