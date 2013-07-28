<?
authorize();
if (!check_perms('users_mod') ) {
	error(403);
}

SiteHistory::add_event($_POST['date'], $_POST['title'], $_POST['url'], $_POST['category'], $_POST['sub_category'], $_POST['tags'], $_POST['body'], $LoggedUser['ID']);

header("Location: sitehistory.php");