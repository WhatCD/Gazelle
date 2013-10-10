<?

/*
 * This is the index page, it is pretty much reponsible only for the switch statement.
 */

enforce_login();

include(SERVER_ROOT.'/sections/reportsv2/array.php');

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'report':
			include(SERVER_ROOT.'/sections/reportsv2/report.php');
			break;
		case 'takereport':
			include(SERVER_ROOT.'/sections/reportsv2/takereport.php');
			break;
		case 'takeresolve':
			include(SERVER_ROOT.'/sections/reportsv2/takeresolve.php');
			break;
		case 'take_pm':
			include(SERVER_ROOT.'/sections/reportsv2/take_pm.php');
			break;
		case 'search':
			include(SERVER_ROOT.'/sections/reportsv2/search.php');
			break;
		case 'new':
			include(SERVER_ROOT.'/sections/reportsv2/reports.php');
			break;
		case 'ajax_new_report':
			include(SERVER_ROOT.'/sections/reportsv2/ajax_new_report.php');
			break;
		case 'ajax_report':
			include(SERVER_ROOT.'/sections/reportsv2/ajax_report.php');
			break;
		case 'ajax_change_resolve':
			include(SERVER_ROOT.'/sections/reportsv2/ajax_change_resolve.php');
			break;
		case 'ajax_take_pm':
			include(SERVER_ROOT.'/sections/reportsv2/ajax_take_pm.php');
			break;
		case 'ajax_grab_report':
			include(SERVER_ROOT.'/sections/reportsv2/ajax_grab_report.php');
			break;
		case 'ajax_giveback_report':
			include(SERVER_ROOT.'/sections/reportsv2/ajax_giveback_report.php');
			break;
		case 'ajax_update_comment':
			include(SERVER_ROOT.'/sections/reportsv2/ajax_update_comment.php');
			break;
		case 'ajax_update_resolve':
			include(SERVER_ROOT.'/sections/reportsv2/ajax_update_resolve.php');
			break;
		case 'ajax_create_report':
			include(SERVER_ROOT.'/sections/reportsv2/ajax_create_report.php');
			break;
	}
} else {
	if (isset($_GET['view'])) {
		include(SERVER_ROOT.'/sections/reportsv2/static.php');
	} else {
		include(SERVER_ROOT.'/sections/reportsv2/views.php');
	}
}
?>
