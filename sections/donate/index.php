<?
//Module mini-config
include(SERVER_ROOT.'/sections/donate/config.php');

if (!isset($_REQUEST['action'])) {
	include(SERVER_ROOT.'/sections/donate/donate.php');
} else {
	switch ($_REQUEST['action']) {
		case 'ipn': // PayPal hits this page when a donation is received
			include(SERVER_ROOT.'/sections/donate/ipn.php');
			break;
		case 'complete':
			include(SERVER_ROOT.'/sections/donate/complete.php');
			break;
		case 'cancel':
			include(SERVER_ROOT.'/sections/donate/cancel.php');
			break;
	}
}
?>
