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
			include(SERVER_ROOT.'/sections/requests/take_vote.php');
			break;
		case 'takefill':
			include(SERVER_ROOT.'/sections/requests/take_fill.php');
			break;
		case 'takenew':
		case 'takeedit':
			include(SERVER_ROOT.'/sections/requests/take_new_edit.php');
			break;
		case 'delete':
		case 'unfill':
			include(SERVER_ROOT.'/sections/requests/interim.php');
			break;
		case 'takeunfill':
			include(SERVER_ROOT.'/sections/requests/take_unfill.php');
			break;
		case 'takedelete':
			include(SERVER_ROOT.'/sections/requests/take_delete.php');
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
