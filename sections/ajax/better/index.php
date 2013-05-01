<?
//Include all the basic stuff...

enforce_login();
if (isset($_GET['method'])) {
	switch ($_GET['method']) {
		case 'transcode':
			include(SERVER_ROOT.'/sections/ajax/better/transcode.php');
			break;
		case 'single':
			include(SERVER_ROOT.'/sections/ajax/better/single.php');
			break;
		case 'snatch':
			include(SERVER_ROOT.'/sections/ajax/better/snatch.php');
			break;
		case 'artistless':
			include(SERVER_ROOT.'/sections/ajax/better/artistless.php');
			break;
		case 'tags':
			include(SERVER_ROOT.'/sections/ajax/better/tags.php');
			break;
		case 'folders':
			include(SERVER_ROOT.'/sections/ajax/better/folders.php');
			break;
		case 'files':
			include(SERVER_ROOT.'/sections/ajax/better/files.php');
			break;
		case 'upload':
			include(SERVER_ROOT.'/sections/ajax/better/upload.php');
			break;
		default:
			print json_encode(array('status' => 'failure'));
			break;
	}
} else {
	print json_encode(array('status' => 'failure'));
}
?>
