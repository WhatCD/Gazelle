<?

if ($_GET['type']) {
	switch ($_GET['type']) {
		case 'posts':
			// Load post history page
			include('post_history.php');
			break;
		default:
			print json_encode(
				array('status' => 'failure')
				);
	}
} else {
	print json_encode(
		array('status' => 'failure')
		);
}

?>
