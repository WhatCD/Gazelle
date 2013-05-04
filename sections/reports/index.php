<?
enforce_login();

if (empty($_REQUEST['action'])) {
	$_REQUEST['action'] = '';
}

switch ($_REQUEST['action']) {
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
	case 'add_notes':
		include(SERVER_ROOT.'/sections/reports/ajax_add_notes.php');
		break;
	case 'claim':
		include(SERVER_ROOT.'/sections/reports/ajax_claim_report.php');
		break;
	case 'unclaim':
		include(SERVER_ROOT.'/sections/reports/ajax_unclaim_report.php');
		break;
	case 'resolve':
		include(SERVER_ROOT.'/sections/reports/ajax_resolve_report.php');
		break;
	default:
		include(SERVER_ROOT.'/sections/reports/reports.php');
		break;
}
?>
