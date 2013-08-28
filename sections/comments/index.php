<?
enforce_login();

// fix old links
if ($_REQUEST['action'] == 'artists') {
	$_REQUEST['action'] = 'artist';
} elseif ($_REQUEST['action'] == 'my_torrents') {
	$_REQUEST['action'] = 'torrents';
	$_REQUEST['type'] = 'uploaded';
}

$Action = '';
if (!empty($_REQUEST['action'])) {
	$Action = $_REQUEST['action'];
}

switch ($Action) {
	case 'take_post':
		require SERVER_ROOT . '/sections/comments/take_post.php';
		break;
	case 'take_edit':
		require SERVER_ROOT . '/sections/comments/take_edit.php';
		break;
	case 'take_delete':
		require SERVER_ROOT . '/sections/comments/take_delete.php';
		break;
	case 'warn':
		require SERVER_ROOT . '/sections/comments/warn.php';
		break;
	case 'take_warn':
		require SERVER_ROOT . '/sections/comments/take_warn.php';
		break;
	case 'get':
		require SERVER_ROOT . '/sections/comments/get.php';
		break;
	case 'jump':
		require SERVER_ROOT . '/sections/comments/jump.php';
		break;
	case 'artist':
	case 'collages':
	case 'requests':
	case 'torrents':
	default:
		require SERVER_ROOT . '/sections/comments/comments.php';
		break;
}
