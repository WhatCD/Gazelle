<?

enforce_login();

if(!empty($LoggedUser['DisableForums'])) {
	error(403);
}

//This variable contains all our lovely forum data
if(!$Forums = $Cache->get_value('forums_list')) {
	$DB->query("SELECT
		f.ID,
		f.CategoryID,
		f.Name,
		f.Description,
		f.MinClassRead,
		f.MinClassWrite,
		f.MinClassCreate,
		f.NumTopics,
		f.NumPosts,
		f.LastPostID,
		f.LastPostAuthorID,
		um.Username,
		f.LastPostTopicID,
		f.LastPostTime,
		COUNT(sr.ThreadID) AS SpecificRules,
		t.Title,
		t.IsLocked,
		t.IsSticky
		FROM forums AS f
		LEFT JOIN forums_topics as t ON t.ID = f.LastPostTopicID
		LEFT JOIN users_main AS um ON um.ID=f.LastPostAuthorID
		LEFT JOIN forums_specific_rules AS sr ON sr.ForumID = f.ID
		GROUP BY f.ID
		ORDER BY f.CategoryID, f.Sort");
	$Forums = $DB->to_array('ID', MYSQLI_ASSOC, false);
	foreach($Forums as $ForumID => $Forum) {
		if(count($Forum['SpecificRules'])) {
			$DB->query("SELECT ThreadID FROM forums_specific_rules WHERE ForumID = ".$ForumID);
			$ThreadIDs = $DB->collect('ThreadID');
			$Forums[$ForumID]['SpecificRules'] = $ThreadIDs;
		}
	}
	$Cache->cache_value('forums_list', $Forums, 0); //Inf cache.
}

if(!empty($_POST['action'])){
	switch ($_POST['action']) {
		case 'reply':
			require(SERVER_ROOT.'/sections/forums/take_reply.php');
			break;
		case 'new':
			require(SERVER_ROOT.'/sections/forums/take_new_thread.php');
			break;
		case 'mod_thread':
			require(SERVER_ROOT.'/sections/forums/mod_thread.php');
			break;
		case 'poll_mod':
			require(SERVER_ROOT.'/sections/forums/poll_mod.php');
			break;
		case 'add_poll_option':
			require(SERVER_ROOT.'/sections/forums/add_poll_option.php');
			break;
		default:
			error(0);
	}
} elseif(!empty($_GET['action'])) {
	switch ($_GET['action']) {
		case 'viewforum':
			// Page that lists all the topics in a forum
			require(SERVER_ROOT.'/sections/forums/forum.php');
			break;
		case 'viewthread':
		case 'viewtopic':
			// Page that displays threads	
			require(SERVER_ROOT.'/sections/forums/thread.php');
			break;
		case 'ajax_get_edit':
			// Page that switches edits for mods
			require(SERVER_ROOT.'/sections/forums/ajax_get_edit.php');
			break;
		case 'new':
			// Create a new thread
			require(SERVER_ROOT.'/sections/forums/newthread.php');
			break;
		case 'takeedit':
			// Edit posts
			require(SERVER_ROOT.'/sections/forums/takeedit.php');
			break;
		case 'get_post':
			// Get posts
			require(SERVER_ROOT.'/sections/forums/get_post.php');
			break;
		case 'delete':
			// Delete posts
			require(SERVER_ROOT.'/sections/forums/delete.php');
			break;
		case 'catchup':
			// Catchup
			require(SERVER_ROOT.'/sections/forums/catchup.php');
			break;
		case 'search':
			// Search posts
			require(SERVER_ROOT.'/sections/forums/search.php');
			break;
		case 'change_vote':
			// Change poll vote
			require(SERVER_ROOT.'/sections/forums/change_vote.php');
			break;
		case 'sticky_post':
			require(SERVER_ROOT.'/sections/forums/sticky_post.php');
			break;
		case 'edit_rules':
			require(SERVER_ROOT.'/sections/forums/edit_rules.php');
			break;
		default:
			error(404);
	}
} else {
	require(SERVER_ROOT.'/sections/forums/main.php');
}

// Function to get basic information on a forum
// Uses class CACHE
function get_forum_info($ForumID) {
	global $DB, $Cache;
	$Forum = $Cache->get_value('ForumInfo_'.$ForumID);
	if(!$Forum) {
		$DB->query("SELECT
			Name,
			MinClassRead,
			MinClassWrite,
			MinClassCreate,
			COUNT(forums_topics.ID) AS Topics
			FROM forums
			LEFT JOIN forums_topics ON forums_topics.ForumID=forums.ID
			WHERE forums.ID='$ForumID'
			GROUP BY ForumID");
		if($DB->record_count() == 0) {
			return false;
		}
		// Makes an array, with $Forum['Name'], etc.
		$Forum = $DB->next_record(MYSQLI_ASSOC);
		
		$Cache->cache_value('ForumInfo_'.$ForumID, $Forum, 86400); // Cache for a day
	}
	return $Forum;
}
