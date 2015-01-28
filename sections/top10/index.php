<?
enforce_login();

if (!check_perms('site_top10')) {
	View::show_header();
?>
<div class="content_basiccontainer">
	You do not have access to view this feature.
</div>
<?
	View::show_footer();
	die();
}

include(SERVER_ROOT.'/sections/torrents/functions.php'); //Has get_reports($TorrentID);
if (empty($_GET['type']) || $_GET['type'] == 'torrents') {
	include(SERVER_ROOT.'/sections/top10/torrents.php');
} else {
	switch ($_GET['type']) {
		case 'users':
			include(SERVER_ROOT.'/sections/top10/users.php');
			break;
		case 'tags':
			include(SERVER_ROOT.'/sections/top10/tags.php');
			break;
		case 'history':
			include(SERVER_ROOT.'/sections/top10/history.php');
			break;
		case 'votes':
			include(SERVER_ROOT.'/sections/top10/votes.php');
			break;
		case 'donors':
			include(SERVER_ROOT.'/sections/top10/donors.php');
			break;
		case 'lastfm':
			include(SERVER_ROOT.'/sections/top10/lastfm.php');
			break;
		
		default:
			error(404);
			break;
	}
}
?>
