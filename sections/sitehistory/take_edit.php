<?
authorize();
if (!check_perms('users_mod') ) {
	error(403);
}

if ($_POST['submit']) {
	SiteHistory::update_event($_POST['id'], $_POST['date'], $_POST['title'], $_POST['url'], $_POST['category'], $_POST['sub_category'], $_POST['tags'], $_POST['body'], $LoggedUser['ID']);
} elseif ($_POST['delete']) {
	SiteHistory::delete_event($_POST['id']);
}
header("Location: sitehistory.php");
