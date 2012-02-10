<?
/*****************************************************************
 Tools switch center

 This page acts as a switch for the tools pages.

 TODO!
 -Unify all the code standards and file names (tool_list.php,tool_add.php,tool_alter.php)

 *****************************************************************/

if(isset($argv[1])) {
	if($argv[1] == "cli_sandbox") {
		include("misc/cli_sandbox.php");
		die();
	}

	$_REQUEST['action'] = $argv[1];
} else {
	if(empty($_REQUEST['action']) || ($_REQUEST['action'] != "public_sandbox" && $_REQUEST['action'] != "ocelot")) {
		enforce_login();
	}
}

if(!isset($_REQUEST['action'])) {
	include(SERVER_ROOT.'/sections/tools/tools.php');
	die();
}

if (substr($_REQUEST['action'],0,7) == 'sandbox' && !isset($argv[1])) {
	if (!check_perms('site_debug')) {
		error(403);
	}
}

if (substr($_REQUEST['action'],0,12) == 'update_geoip' && !isset($argv[1])) {
	if (!check_perms('site_debug')) {
		error(403);
	}
}

include(SERVER_ROOT."/classes/class_validate.php");
$Val=NEW VALIDATE;

include(SERVER_ROOT.'/classes/class_feed.php');
$Feed = new FEED;

switch ($_REQUEST['action']){
	case 'phpinfo':
		if (!check_perms('site_debug')) error(403);
		phpinfo();
		break;
	//Services
	case 'get_host':
		include('services/get_host.php');
		break;
	case 'get_cc':
		include('services/get_cc.php');
		break;
	//Managers
	case 'forum':
		include('managers/forum_list.php');
		break;

	case 'forum_alter':
		include('managers/forum_alter.php');
		break;

	case 'whitelist':
		include('managers/whitelist_list.php');
		break;

	case 'whitelist_alter':
		include('managers/whitelist_alter.php');
		break;

	case 'login_watch':
		include('managers/login_watch.php');
		break;

	case 'recommend':
		include('managers/recommend_list.php');
		break;

	case 'recommend_add':
		include('managers/recommend_add.php');
		break;

	case 'recommend_alter':
		include('managers/recommend_alter.php');
		break;

	case 'email_blacklist':
		include('managers/eb.php');
		break;

	case 'eb_alter':
		include('managers/eb_alter.php');
		break;

	case 'dnu':
		include('managers/dnu_list.php');
		break;

	case 'dnu_alter':
		include('managers/dnu_alter.php');
		break;

	case 'editnews':
	case 'news':
		include('managers/news.php');
		break;

	case 'takeeditnews':
		if(!check_perms('admin_manage_news')){ error(403); }
		if(is_number($_POST['newsid'])){
			$DB->query("UPDATE news SET Title='".db_string($_POST['title'])."', Body='".db_string($_POST['body'])."' WHERE ID='".db_string($_POST['newsid'])."'");
			$Cache->delete_value('news');
			$Cache->delete_value('feed_news');
		}
		header('Location: index.php');
		break;

	case 'deletenews':
		if(!check_perms('admin_manage_news')){ error(403); }
		if(is_number($_GET['id'])){
			authorize();
			$DB->query("DELETE FROM news WHERE ID='".db_string($_GET['id'])."'");
			$Cache->delete_value('news');
			$Cache->delete_value('feed_news');

			// Deleting latest news
			$LatestNews = $Cache->get_value('news_latest_id');
			if ($LatestNews !== FALSE && $LatestNews == $_GET['id']) {
				$Cache->delete_value('news_latest_id');
			}
		}
		header('Location: index.php');
		break;

	case 'takenewnews':
		if(!check_perms('admin_manage_news')){ error(403); }

		$DB->query("INSERT INTO news (UserID, Title, Body, Time) VALUES ('$LoggedUser[ID]', '".db_string($_POST['title'])."', '".db_string($_POST['body'])."', '".sqltime()."')");
		$Cache->cache_value('news_latest_id', $DB->inserted_id(), 0);
		$Cache->delete_value('news');

		header('Location: index.php');
		break;
		
	case 'tokens':
		include('managers/tokens.php');
		break;
	case 'ocelot':
		include('managers/ocelot.php');
		break;
	case 'official_tags':
		include('managers/official_tags.php');
		break;

	case 'permissions':
		if (!check_perms('admin_manage_permissions')) { error(403); }

		if (!empty($_REQUEST['id'])) {
			$Val->SetFields('name',true,'string','You did not enter a valid name for this permission set.');
			$Val->SetFields('level',true,'number','You did not enter a valid level for this permission set.');
			$Val->SetFields('maxcollages',true,'number','You did not enter a valid number of personal collages.');
			//$Val->SetFields('test',true,'number','You did not enter a valid level for this permission set.');

			$Values=array();
			if (is_numeric($_REQUEST['id'])) {
				$DB->query("SELECT p.ID,p.Name,p.Level,p.Values,p.DisplayStaff,COUNT(u.ID) FROM permissions AS p LEFT JOIN users_main AS u ON u.PermissionID=p.ID WHERE p.ID='".db_string($_REQUEST['id'])."' GROUP BY p.ID");
				list($ID,$Name,$Level,$Values,$DisplayStaff,$UserCount)=$DB->next_record(MYSQLI_NUM, array(3));

				if($Level > $LoggedUser['Class']  || $_REQUEST['level'] > $LoggedUser['Class']) {
					error(403);
				}


				$Values=unserialize($Values);
			}
			
		

			if (!empty($_POST['submit'])) {
				$Err = $Val->ValidateForm($_POST);

				if (!is_numeric($_REQUEST['id'])) {
					$DB->query("SELECT ID FROM permissions WHERE Level='".db_string($_REQUEST['level'])."'");
					list($DupeCheck)=$DB->next_record();

					if ($DupeCheck) {
						$Err = "There is already a permission class with that level.";
					}
				}

				$Values=array();
				foreach ($_REQUEST as $Key => $Perms) {
					if (substr($Key,0,5)=="perm_") { $Values[substr($Key,5)]= (int)$Perms; }
				}

				$Name=$_REQUEST['name'];
				$Level=$_REQUEST['level'];
				$DisplayStaff=$_REQUEST['displaystaff'];
				$Values['MaxCollages']=$_REQUEST['maxcollages'];

				if (!$Err) {
					if (!is_numeric($_REQUEST['id'])) {
						$DB->query("INSERT INTO permissions (Level,Name,`Values`,DisplayStaff) VALUES ('".db_string($Level)."','".db_string($Name)."','".db_string(serialize($Values))."','".db_string($DisplayStaff)."')");
					} else {
						$DB->query("UPDATE permissions SET Level='".db_string($Level)."',Name='".db_string($Name)."',`Values`='".db_string(serialize($Values))."',DisplayStaff='".db_string($DisplayStaff)."' WHERE ID='".db_string($_REQUEST['id'])."'");
						$Cache->delete_value('perm_'.$_REQUEST['id']);
					}
					$Cache->delete_value('classes');
				} else {
					error($Err);
				}
			}

			include('managers/permissions_alter.php');

		} else {
			if (!empty($_REQUEST['removeid'])) {
				$DB->query("DELETE FROM permissions WHERE ID='".db_string($_REQUEST['removeid'])."'");
				$DB->query("UPDATE users_main SET PermissionID='".USER."' WHERE PermissionID='".db_string($_REQUEST['removeid'])."'");

				$Cache->delete_value('classes');
			}

			include('managers/permissions_list.php');
		}

		break;

	case 'ip_ban':
		//TODO: Clean up db table ip_bans.
		include("managers/bans.php");
		break;

	//Data
	case 'registration_log':
		include('data/registration_log.php');
		break;

	case 'donation_log':
		include('data/donation_log.php');
		break;

	
	case 'upscale_pool':
		include('data/upscale_pool.php');
		break;

	case 'invite_pool':
		include('data/invite_pool.php');
		break;

	case 'torrent_stats':
		include('data/torrent_stats.php');
		break;

	case 'user_flow':
		include('data/user_flow.php');
		break;

	case 'economic_stats':
		include('data/economic_stats.php');
		break;

	case 'opcode_stats':
		include('data/opcode_stats.php');
		break;

	case 'service_stats':
		include('data/service_stats.php');
		break;

	case 'database_specifics':
		include('data/database_specifics.php');
		break;

	case 'special_users':
		include('data/special_users.php');
		break;


	case 'browser_support':
		include('data/browser_support.php');
		break;
		//END Data

		//Misc
	case 'update_geoip':
		include('misc/update_geoip.php');
		break;

	case 'dupe_ips':
		include('misc/dupe_ip.php');
		break;

	case 'clear_cache':
		include('misc/clear_cache.php');
		break;

	case 'create_user':
		include('misc/create_user.php');
		break;

	case 'manipulate_tree':
		include('misc/manipulate_tree.php');
		break;

	case 'recommendations':
		include('misc/recommendations.php');
		break;

	case 'analysis':
		include('misc/analysis.php');
		break;

	case 'sandbox1':
		include('misc/sandbox1.php');
		break;

	case 'sandbox2':
		include('misc/sandbox2.php');
		break;
		
	case 'sandbox3':
		include('misc/sandbox3.php');
		break;
		
	case 'sandbox4':
		include('misc/sandbox4.php');
		break;
		
	case 'sandbox5':
		include('misc/sandbox5.php');
		break;
		
	case 'sandbox6':
		include('misc/sandbox6.php');
		break;
		
	case 'sandbox7':
		include('misc/sandbox7.php');
		break;
		
	case 'sandbox8':
		include('misc/sandbox8.php');
		break;
		
	case 'public_sandbox':
		include('misc/public_sandbox.php');
		break;

	case 'mod_sandbox':
		if(check_perms('users_mod')) {
			include('misc/mod_sandbox.php');
		} else {
			error(403);
		}
		break;

	default:
		include(SERVER_ROOT.'/sections/tools/tools.php');
}
?>
