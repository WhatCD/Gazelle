<?
enforce_login();

if(!check_perms('site_top10')){
	show_header();
?>
<div class="content_basiccontainer">
	You do not have access to view this feature
</div>
<?
	show_footer();
	die();
}


if(empty($_GET['type']) || $_GET['type'] == 'torrents') {
	include(SERVER_ROOT.'/sections/top10/torrents.php');
} else {
	switch($_GET['type']) {
		case 'users' :
			include(SERVER_ROOT.'/sections/top10/users.php');
			break;
		case 'tags' :
			include(SERVER_ROOT.'/sections/top10/tags.php');
			break;
		case 'history' :
			include(SERVER_ROOT.'/sections/top10/history.php');
			break;
		default :
			error(404);
			break;
	}
}
?>
