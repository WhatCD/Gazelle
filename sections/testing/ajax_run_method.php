<?
authorize();
if (!check_perms('users_mod')) {
	error(404);
}

$Class = $_POST['class'];
$Method = $_POST['method'];
$Params = json_decode($_POST['params'], true);

if (!empty($Class) && !empty($Method) && Testing::has_testable_method($Class, $Method)) {
	if (count($Params)) {
		$Results = call_user_func_array(array($Class, $Method), array_values($Params));
	} else {
		$Results = call_user_func(array($Class, $Method));
	}
	TestingView::render_results($Results);
}
