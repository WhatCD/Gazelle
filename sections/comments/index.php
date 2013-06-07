<?php

enforce_login();

include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class
$Text = new TEXT;

require(SERVER_ROOT.'/sections/comments/post.php'); // Post formatting function.

$action = '';
if (!empty($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
}

/**
 * Getting a userid if applicable
 */
if (isset($_GET['id'])) {
	$UserID = $_GET['id'];
	if (!is_number($UserID)) {
		error(404);
	}

	$UserInfo = Users::user_info($UserID);

	$Username = $UserInfo['Username'];
	if ($LoggedUser['ID'] == $UserID) {
		$Self = true;
	} else {
		$Self = false;
	}
	$Perms = Permissions::get_permissions($UserInfo['PermissionID']);
	$UserClass = $Perms['Class'];
	if (!check_paranoia('torrentcomments', $UserInfo['Paranoia'], $UserClass, $UserID))
		error(403);
} else {
	$UserID = $LoggedUser['ID'];
	$Username = $LoggedUser['Username'];
	$Self = true;
}

/**
 * Posts per page limit stuff
 */
if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}
list($Page, $Limit) = Format::page_limit($PerPage);

switch ($action) {
	case 'requests':
		require (SERVER_ROOT.'/sections/comments/requestcomments.php');
		break;
	case 'artists':
		require (SERVER_ROOT.'/sections/comments/artistcomments.php');
		break;
	case 'collages':
		require (SERVER_ROOT.'/sections/comments/collagecomments.php');
		break;
	case 'torrents':
	case 'my_torrents':
	default:
		require(SERVER_ROOT.'/sections/comments/torrentcomments.php');
		break;
}
