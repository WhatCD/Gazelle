<?
enforce_login();

if(empty($_REQUEST['action'])) { $_REQUEST['action']=''; }

switch($_REQUEST['action']) {
	case 'new':
		if(!check_perms('site_collages_create')) { error(403); }
		require(SERVER_ROOT.'/sections/collages/new.php');
		break;
	case 'new_handle':
		if(!check_perms('site_collages_create')) { error(403); }
		require(SERVER_ROOT.'/sections/collages/new_handle.php');
		break;
	case 'add_torrent':
		if(!check_perms('site_collages_manage')) { error(403); }
		require(SERVER_ROOT.'/sections/collages/add_torrent.php');
		break;
	case 'manage':
		if(!check_perms('site_collages_manage')) { error(403); }
		require(SERVER_ROOT.'/sections/collages/manage.php');
		break;
	case 'manage_handle':
		if(!check_perms('site_collages_manage')) { error(403); }
		require(SERVER_ROOT.'/sections/collages/manage_handle.php');
		break;
	case 'edit':
		if(!check_perms('site_edit_wiki')) { error(403); }
		require(SERVER_ROOT.'/sections/collages/edit.php');
		break;
	case 'edit_handle':
		if(!check_perms('site_edit_wiki')) { error(403); }
		require(SERVER_ROOT.'/sections/collages/edit_handle.php');
		break;
	case 'delete':
		authorize();
		require(SERVER_ROOT.'/sections/collages/delete.php');
		break;
	case 'take_delete':
		require(SERVER_ROOT.'/sections/collages/take_delete.php');
		break;
	case 'add_comment':
		require(SERVER_ROOT.'/sections/collages/add_comment.php');
		break;
	case 'comments':
		require(SERVER_ROOT.'/sections/collages/all_comments.php');
		break;
	case 'takeedit_comment':
		require(SERVER_ROOT.'/sections/collages/takeedit_comment.php');
		break;
	case 'delete_comment':
		require(SERVER_ROOT.'/sections/collages/delete_comment.php');
		break;
	case 'get_post':
		require(SERVER_ROOT.'/sections/collages/get_post.php');
		break;
	case 'download':
		require(SERVER_ROOT.'/sections/collages/download.php');
		break;
	case 'recover':
		//if(!check_perms('')) { error(403); }
		require(SERVER_ROOT.'/sections/collages/recover.php');
		break;
	case 'create_personal':
		if(!check_perms('site_collages_personal')) {
			error(403);
		}
		$DB->query("SELECT ID FROM collages WHERE UserID='$LoggedUser[ID]' AND CategoryID='0' AND Deleted='0'");
 		if($DB->record_count() > 0) { 
			list($CollageID) = $DB->next_record();
			header('Location: collage.php?id='.$CollageID);
			die();
 		}
		
		$DB->query("INSERT INTO collages (Name, Description, CategoryID, UserID) VALUES ('$LoggedUser[Username]\'s personal collage', 'Personal collage for $LoggedUser[Username]. The first 5 albums will appear on his or her [url=http:\/\/".NONSSL_SITE_URL."\/user.php?id=$LoggedUser[ID]]profile[\/url].', '0', $LoggedUser[ID])");
		$CollageID = $DB->inserted_id();
		header('Location: collage.php?id='.$CollageID);
		die();

	default:
		if(!empty($_GET['id'])) {
			require(SERVER_ROOT.'/sections/collages/collage.php');
		} else {
			require(SERVER_ROOT.'/sections/collages/browse.php');
		}
		break;
}

?>
