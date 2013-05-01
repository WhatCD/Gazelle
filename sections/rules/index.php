<?
//Include all the basic stuff...
enforce_login();
if (!isset($_GET['p'])) {
	require(SERVER_ROOT.'/sections/rules/rules.php');
} else {
	switch ($_GET['p']) {
		case 'ratio':
			require(SERVER_ROOT.'/sections/rules/ratio.php');
			break;
		case 'clients':
			require(SERVER_ROOT.'/sections/rules/clients.php');
			break;
		case 'chat':
			require(SERVER_ROOT.'/sections/rules/chat.php');
			break;
		case 'upload':
			require(SERVER_ROOT.'/sections/rules/upload.php');
			break;
		case 'requests';
			require(SERVER_ROOT.'/sections/rules/requests.php');
			break;
		case 'collages';
			require(SERVER_ROOT.'/sections/rules/collages.php');
			break;
		case 'tag':
			require(SERVER_ROOT.'/sections/rules/tag.php');
			break;
		default:
			error(0);
	}
}
