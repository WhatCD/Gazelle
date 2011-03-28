<?

/*
 * This is the index page, it is pretty much reponsible only for the switch statement.
 */

enforce_login();

include('array.php');

if(isset($_REQUEST['action'])) {
	switch($_REQUEST['action']){
		case 'report':
			include('report.php');
			break;
		case 'takereport':
			include('takereport.php');
			break;
		case 'takeresolve':
			include('takeresolve.php');
			break;
		case 'take_pm':
			include('take_pm.php');
			break;
		case 'search':
			include('search.php');
			break;
		case 'new':
			include(SERVER_ROOT.'/sections/reportsv2/reports.php');
			break;	
		case 'ajax_new_report':
			include('ajax_new_report.php');
			break;
		case 'ajax_report':
			include('ajax_report.php');
			break;
		case 'ajax_change_resolve':
			include('ajax_change_resolve.php');
			break;
		case 'ajax_taste':
			include('ajax_taste.php');
			break;
		case 'ajax_take_pm':
			include('ajax_take_pm.php');
			break;
		case 'ajax_grab_report':
			include('ajax_grab_report.php');
			break;
		case 'ajax_update_comment':
			require('ajax_update_comment.php');
			break;
		case 'ajax_update_resolve':
			require('ajax_update_resolve.php');
			break;
		case 'ajax_create_report':
			require('ajax_create_report.php');
			break;
	}
} else {
	if(isset($_GET['view'])) {
		include(SERVER_ROOT.'/sections/reportsv2/static.php');
	} else {
		include(SERVER_ROOT.'/sections/reportsv2/views.php');
	}
}
?>
