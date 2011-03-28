<?
/*
AJAX Switch Center

This page acts as an AJAX "switch" - it's called by scripts, and it includes the required pages. 

The required page is determined by $_GET['action']. 

*/

enforce_login();

switch ($_GET['action']){
	case 'upload_section':
		// Gets one of the upload forms
		require(SERVER_ROOT.'/sections/ajax/upload.php');
		break;
	case 'preview':
		require('preview.php');
		break;
	case 'torrent_info':
		require('torrent_info.php');
		break;
	case 'giveback_report':
		require('giveback_report.php');
		break;
	case 'grab_report':
		require('grab_report.php');
		break;
	case 'stats':
		require(SERVER_ROOT.'/sections/ajax/stats.php');
		break;
	
	default:
		// If they're screwing around with the query string
		error(403);
}

?>
