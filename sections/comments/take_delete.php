<?
authorize();

// Quick SQL injection check
if (!$_GET['postid'] || !is_number($_GET['postid'])) {
	error(0);
}

// Make sure they are moderators
if (!check_perms('site_moderate_forums')) {
	error(403);
}

Comments::delete((int)$_GET['postid']);
