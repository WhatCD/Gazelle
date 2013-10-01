<?
$P = db_array($_POST);
enforce_login();
if (!empty($_REQUEST['friendid']) && !is_number($_REQUEST['friendid'])) {
	error(404);
}

if (!empty($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'add':
			include(SERVER_ROOT.'/sections/friends/add.php');
			break;
		case 'Remove friend':
			authorize();
			include(SERVER_ROOT.'/sections/friends/remove.php');
			break;
		case 'Update':
			authorize();
			include(SERVER_ROOT.'/sections/friends/comment.php');
			break;
		case 'whois':
			include(SERVER_ROOT.'/sections/friends/whois.php');
			break;
		case 'Contact':
			header('Location: inbox.php?action=compose&to='.$_POST['friendid']);
			break;
		default:
			error(404);
	}
} else {
	include(SERVER_ROOT.'/sections/friends/friends.php');
}
?>
