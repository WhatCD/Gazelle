<?
enforce_login();

$RequestTax = 0.1;

// Minimum and default amount of upload to remove from the user when they vote.
// Also change in static/functions/requests.js
$MinimumVote = 20 * 1024 * 1024;

if (!empty($LoggedUser['DisableRequests'])) {
	error('Your request privileges have been removed.');
}

if (!isset($_REQUEST['action'])) {
	include(SERVER_ROOT.'/sections/requests/requests.php');
} else {
	switch ($_REQUEST['action']) {
		case 'new':
		case 'edit':
			include(SERVER_ROOT.'/sections/requests/new_edit.php');
			break;
		case 'takevote':
			include(SERVER_ROOT.'/sections/requests/takevote.php');
			break;
		case 'takefill':
			include(SERVER_ROOT.'/sections/requests/takefill.php');
			break;
		case 'takenew':
		case 'takeedit':
			include(SERVER_ROOT.'/sections/requests/takenew_edit.php');
			break;
		case 'delete':
		case 'unfill':
			include(SERVER_ROOT.'/sections/requests/interim.php');
			break;
		case 'takeunfill':
			include(SERVER_ROOT.'/sections/requests/takeunfill.php');
			break;
		case 'takedelete':
			include(SERVER_ROOT.'/sections/requests/takedelete.php');
			break;
		case 'view':
		case 'viewrequest':
			include(SERVER_ROOT.'/sections/requests/request.php');
			break;
		default:
			error(0);
	}
}
?>
