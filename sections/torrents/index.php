<?

//Function used for pagination of peer/snatch/download lists on details.php
function js_pages($Action, $TorrentID, $NumResults, $CurrentPage) {
	$NumPages = ceil($NumResults/100);
	$PageLinks = array();
	for($i = 1; $i<=$NumPages; $i++) {
		if($i == $CurrentPage) {
			$PageLinks[]=$i;
		} else {
			$PageLinks[]='<a href="#" onclick="'.$Action.'('.$TorrentID.', '.$i.')">'.$i.'</a>';
		}
	}
	return implode(' | ',$PageLinks);
}

// This gets used in a few places
$ArtistTypes = array(1 => 'Main', 2 => 'Guest', 3 => 'Remixer', 4 => 'Composer', 5 => 'Conductor', 6 => 'DJ/Compiler', 7 => 'Producer');

if(!empty($_REQUEST['action'])) {
	switch($_REQUEST['action']){
		case 'edit':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/edit.php');
			break;
		
		case 'editgroup':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/editgroup.php');
			break;
		
		case 'editgroupid':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/editgroupid.php');
			break;
		
		case 'takeedit':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/takeedit.php');
			break;
		
		case 'newgroup':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/takenewgroup.php');
			break;
		
		case 'peerlist':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/peerlist.php');
			break;
		
		case 'snatchlist':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/snatchlist.php');
			break;
	
		case 'downloadlist':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/downloadlist.php');
			break;
	
		case 'redownload':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/redownload.php');
			break;
	
		case 'revert':
		case 'takegroupedit':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/takegroupedit.php');
			break;
		
		case 'nonwikiedit':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/nonwikiedit.php');
			break;
		
		case 'rename':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/rename.php');
			break;
		
		case 'merge':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/merge.php');
			break;
			
		case 'add_alias':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/add_alias.php');
			break;
			
		case 'delete_alias':
			enforce_login();
			authorize();
			include(SERVER_ROOT.'/sections/torrents/delete_alias.php');
			break;
			
			
		case 'history':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/history.php');
			break;
		
		case 'delete':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/delete.php');
			break;
		
		case 'takedelete':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/takedelete.php');
			break;
	
		case 'masspm':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/masspm.php');
			break;
	
		case 'reseed':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/reseed.php');
			break;
	
		case 'takemasspm':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/takemasspm.php');
			break;
	 	
		case 'vote_tag':
			enforce_login();
			authorize();
			include(SERVER_ROOT.'/sections/torrents/vote_tag.php');
			break;
		
		case 'add_tag':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/add_tag.php');
			break;
		
		case 'delete_tag':
			enforce_login();
			authorize();
			include(SERVER_ROOT.'/sections/torrents/delete_tag.php');
			break;
	
		case 'notify':
			enforce_login();
			include(SERVER_ROOT.'/sections/torrents/notify.php');
			break;

		case 'manage_artists':
			enforce_login();
			require(SERVER_ROOT.'/sections/torrents/manage_artists.php');			
			break;
		case 'notify_clear':
			enforce_login();
			authorize();
			if(!check_perms('site_torrents_notify')) { 
			 	$DB->query("DELETE FROM users_notify_filters WHERE UserID='$LoggedUser[ID]'");
			}
			$DB->query("DELETE FROM users_notify_torrents WHERE UserID='$LoggedUser[ID]' AND UnRead='0'");
			$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
			header('Location: torrents.php?action=notify');
			break;

		case 'notify_cleargroup':
			enforce_login();
			authorize();
			if(!isset($_GET['filterid']) || !is_number($_GET['filterid'])) {
				error(0);
			}
			if(!check_perms('site_torrents_notify')) { 
			 	$DB->query("DELETE FROM users_notify_filters WHERE UserID='$LoggedUser[ID]'");
			}
			$DB->query("DELETE FROM users_notify_torrents WHERE UserID='$LoggedUser[ID]' AND FilterID='$_GET[filterid]' AND UnRead='0'");
			$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
			header('Location: torrents.php?action=notify');
			break;
		
		case 'notify_clearitem':
			enforce_login();
			authorize();
			if(!isset($_GET['torrentid']) || !is_number($_GET['torrentid'])) {
				error(0);
			}
			if(!check_perms('site_torrents_notify')) { 
			 	$DB->query("DELETE FROM users_notify_filters WHERE UserID='$LoggedUser[ID]'");
			}
			$DB->query("DELETE FROM users_notify_torrents WHERE UserID='$LoggedUser[ID]' AND TorrentID='$_GET[torrentid]'");
			$Cache->delete_value('notifications_new_'.$LoggedUser['ID']);
			break;
			
		case 'download':
			require(SERVER_ROOT.'/sections/torrents/download.php');
			break;
			
		case 'reply':
			enforce_login();
			authorize();

			if (!isset($_POST['groupid']) || !is_number($_POST['groupid']) || empty($_POST['body'])) { 
				error(0);
			}
			if($LoggedUser['DisablePosting']) {
				error('Your posting rights have been removed.');
			}
			
			$GroupID = $_POST['groupid'];
			if(!$GroupID) { error(404); }
		
			$DB->query("SELECT CEIL((SELECT COUNT(ID)+1 FROM torrents_comments AS tc WHERE tc.GroupID='".db_string($GroupID)."')/".TORRENT_COMMENTS_PER_PAGE.") AS Pages");
			list($Pages) = $DB->next_record();
		
			$DB->query("INSERT INTO torrents_comments (GroupID,AuthorID,AddedTime,Body) VALUES (
				'".db_string($GroupID)."', '".db_string($LoggedUser['ID'])."','".sqltime()."','".db_string($_POST['body'])."')");
			$PostID=$DB->inserted_id();
		
			$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE*$Pages-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
			$Cache->begin_transaction('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID);
			$Post = array(
				'ID'=>$PostID,
				'AuthorID'=>$LoggedUser['ID'],
				'AddedTime'=>sqltime(),
				'Body'=>$_POST['body'],
				'EditedUserID'=>0,
				'EditedTime'=>'0000-00-00 00:00:00',
				'Username'=>''
				);
			$Cache->insert('', $Post);
			$Cache->commit_transaction(0);
			$Cache->increment('torrent_comments_'.$GroupID);
			
			header('Location: torrents.php?id='.$GroupID.'&page='.$Pages);
			break;
		
		case 'get_post':
			enforce_login();
			if (!$_GET['post'] || !is_number($_GET['post'])) { error(0); }
			$DB->query("SELECT Body FROM torrents_comments WHERE ID='".db_string($_GET['post'])."'");
			list($Body) = $DB->next_record(MYSQLI_NUM);
		
			echo trim($Body);
			break;
		
		case 'takeedit_post':
			enforce_login();
			authorize();

			include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
			$Text = new TEXT;
		
			// Quick SQL injection check
			if(!$_POST['post'] || !is_number($_POST['post'])) { error(0); }
			
			// Mainly
			$DB->query("SELECT
				tc.Body,
				tc.AuthorID,
				tc.GroupID,
				tc.AddedTime
				FROM torrents_comments AS tc
				WHERE tc.ID='".db_string($_POST['post'])."'");
			list($OldBody, $AuthorID,$GroupID,$AddedTime)=$DB->next_record();
			
			$DB->query("SELECT ceil(COUNT(ID) / ".TORRENT_COMMENTS_PER_PAGE.") AS Page FROM torrents_comments WHERE GroupID = $GroupID AND ID <= $_POST[post]");
			list($Page) = $DB->next_record();
			
			if ($LoggedUser['ID']!=$AuthorID && !check_perms('site_moderate_forums')) { error(404); }
			if ($DB->record_count()==0) { error(404); }
		
			// Perform the update
			$DB->query("UPDATE torrents_comments SET
				Body = '".db_string($_POST['body'])."',
				EditedUserID = '".db_string($LoggedUser['ID'])."',
				EditedTime = '".sqltime()."'
				WHERE ID='".db_string($_POST['post'])."'");
		
			// Update the cache
			$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
			$Cache->begin_transaction('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID);
			
			$Cache->update_row($_POST['key'], array(
				'ID'=>$_POST['post'],
				'AuthorID'=>$AuthorID,
				'AddedTime'=>$AddedTime,
				'Body'=>$_POST['body'],
				'EditedUserID'=>db_string($LoggedUser['ID']),
				'EditedTime'=>sqltime(),
				'Username'=>$LoggedUser['Username']
			));
			$Cache->commit_transaction(0);
		
			$DB->query("INSERT INTO comments_edits (Page, PostID, EditUser, EditTime, Body)
									VALUES ('torrents', ".db_string($_POST['post']).", ".db_string($LoggedUser['ID']).", '".sqltime()."', '".db_string($OldBody)."')");
			
			// This gets sent to the browser, which echoes it in place of the old body
			echo $Text->full_format($_POST['body']);
			break;
		
		case 'delete_post':
			enforce_login();
			authorize();
		
			// Quick SQL injection check
			if (!$_GET['postid'] || !is_number($_GET['postid'])) { error(0); }
		
			// Make sure they are moderators
			if (!check_perms('site_moderate_forums')) { error(403); }
		
			// Get topicid, forumid, number of pages
			$DB->query("SELECT DISTINCT
				GroupID,
				CEIL((SELECT COUNT(tc1.ID) FROM torrents_comments AS tc1 WHERE tc1.GroupID=tc.GroupID)/".TORRENT_COMMENTS_PER_PAGE.") AS Pages,
				CEIL((SELECT COUNT(tc2.ID) FROM torrents_comments AS tc2 WHERE tc2.ID<'".db_string($_GET['postid'])."')/".TORRENT_COMMENTS_PER_PAGE.") AS Page
				FROM torrents_comments AS tc
				WHERE tc.GroupID=(SELECT GroupID FROM torrents_comments WHERE ID='".db_string($_GET['postid'])."')");
			list($GroupID,$Pages,$Page)=$DB->next_record();
		
			// $Pages = number of pages in the thread
			// $Page = which page the post is on
			// These are set for cache clearing.
		
			$DB->query("DELETE FROM torrents_comments WHERE ID='".db_string($_GET['postid'])."'");
		
			//We need to clear all subsequential catalogues as they've all been bumped with the absence of this post
			$ThisCatalogue = floor((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
			$LastCatalogue = floor((TORRENT_COMMENTS_PER_PAGE*$Pages-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
			for($i=$ThisCatalogue;$i<=$LastCatalogue;$i++) {
				$Cache->delete('torrent_comments_'.$GroupID.'_catalogue_'.$i);
			}
			
			// Delete thread info cache (eg. number of pages)
			$Cache->delete('torrent_comments_'.$GroupID);
			
			break;
		case 'regen_filelist' :
			if(check_perms('users_mod') && !empty($_GET['torrentid']) && is_number($_GET['torrentid'])) {
				$TorrentID = $_GET['torrentid'];
				$DB->query("SELECT tg.ID, 
						tf.File 
					FROM torrents_files AS tf 
						JOIN torrents AS t ON t.ID=tf.TorrentID 
						JOIN torrents_group AS tg ON tg.ID=t.GroupID 
						WHERE tf.TorrentID = ".$TorrentID);
				if($DB->record_count() > 0) {
					require(SERVER_ROOT.'/classes/class_torrent.php');
					list($GroupID, $Contents) = $DB->next_record(MYSQLI_NUM, false);
					$Contents = unserialize(base64_decode($Contents));
					$Tor = new TORRENT($Contents, true);
					list($TotalSize, $FileList) = $Tor->file_list();
					foreach($FileList as $File) {
						list($Size, $Name) = $File;
						$TmpFileList []= $Name .'{{{'.$Size.'}}}'; // Name {{{Size}}}
					}
					$FilePath = $Tor->Val['info']->Val['files'] ? db_string($Tor->Val['info']->Val['name']) : "";
					$FileString = db_string(implode('|||', $TmpFileList));
					$DB->query("UPDATE torrents SET Size = ".$TotalSize.", FilePath = '".db_string($FilePath)."', FileList = '".db_string($FileString)."' WHERE ID = ".$TorrentID);
					$Cache->delete_value('torrents_details_'.$GroupID);
				}
				header('Location: torrents.php?torrentid='.$TorrentID);
				die();
			} else {
				error(403);
			}
			break;
		case 'fix_group' :
			if(check_perms('users_mod') && authorize() && !empty($_GET['groupid']) && is_number($_GET['groupid'])) {
				$DB->query("SELECT COUNT(ID) FROM torrents WHERE GroupID = ".$_GET['groupid']);
				list($Count) = $DB->next_record();
				if($Count == 0) {
					delete_group($_GET['groupid']);
				} else {
				}
				if(!empty($_GET['artistid']) && is_number($_GET['artistid'])) {
					header('Location: artist.php?id='.$_GET['artistid']);
				} else {
					header('Location: torrents.php?id='.$_GET['groupid']);
				}
			} else {
				error(403);
			}
			break;
		default:
			enforce_login();
		
			if(!empty($_GET['id'])) {
				include(SERVER_ROOT.'/sections/torrents/details.php');
			} elseif(isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
				$DB->query("SELECT GroupID FROM torrents WHERE ID=".$_GET['torrentid']);
				list($GroupID) = $DB->next_record();
				if($GroupID) {
					header("Location: torrents.php?id=".$GroupID."&torrentid=".$_GET['torrentid']);
				}
			} else {
				include(SERVER_ROOT.'/sections/torrents/browse2.php');
			}
			break;
	}
} else {
	enforce_login();

	if(!empty($_GET['id'])) {
		include(SERVER_ROOT.'/sections/torrents/details.php');
	} elseif(isset($_GET['torrentid']) && is_number($_GET['torrentid'])) {
		$DB->query("SELECT GroupID FROM torrents WHERE ID=".$_GET['torrentid']);
		list($GroupID) = $DB->next_record();
		if($GroupID) {
			header("Location: torrents.php?id=".$GroupID."&torrentid=".$_GET['torrentid']."#torrent".$_GET['torrentid']);
		} else {
			header("Location: log.php?search=Torrent+".$_GET['torrentid']);
		}
	} elseif(!empty($_GET['type'])) {
		include(SERVER_ROOT.'/sections/torrents/user.php');
	} elseif(!empty($_GET['groupname']) && !empty($_GET['forward'])) {
		$DB->query("SELECT ID FROM torrents_group WHERE Name LIKE '".db_string($_GET['groupname'])."'");
		list($GroupID) = $DB->next_record();
		if($GroupID) {
			header("Location: torrents.php?id=".$GroupID);
		} else {
			include(SERVER_ROOT.'/sections/torrents/browse2.php');
		}
	} else {
		include(SERVER_ROOT.'/sections/torrents/browse2.php');
	}
	
}
?>
