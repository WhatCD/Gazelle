<?
enforce_login();

if (empty($_REQUEST['action'])) {
	$_REQUEST['action'] = '';
}

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
	case 'stats':
		include(SERVER_ROOT.'/sections/reports/stats.php');
		break;
	case 'compose':
		include(SERVER_ROOT.'/sections/reports/compose.php');
		break;
	case 'takecompose':
		include(SERVER_ROOT.'/sections/reports/takecompose.php');
		break;
	default:
		include(SERVER_ROOT.'/sections/reports/reports.php');
		break;
}
?>
