<?

enforce_login();

if (!empty($LoggedUser['DisableForums'])) {
	error(403);
}

$Forums = Forums::get_forums();
$ForumCats = Forums::get_forum_categories();

if (!empty($_POST['action'])) {
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
		case 'warn':
			require(SERVER_ROOT.'/sections/forums/warn.php');
			break;
		case 'take_warn':
			require(SERVER_ROOT.'/sections/forums/take_warn.php');
			break;
		case 'take_topic_notes':
			require(SERVER_ROOT.'/sections/forums/take_topic_notes.php');
			break;

		default:
			error(0);
	}
} elseif (!empty($_GET['action'])) {
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
		case 'delete_poll_option':
			require(SERVER_ROOT.'/sections/forums/delete_poll_option.php');
			break;
		case 'sticky_post':
			require(SERVER_ROOT.'/sections/forums/sticky_post.php');
			break;
		case 'edit_rules':
			require(SERVER_ROOT.'/sections/forums/edit_rules.php');
			break;
		case 'thread_subscribe':
			break;
		case 'warn':
			require(SERVER_ROOT.'/sections/forums/warn.php');
			break;
		default:
			error(404);
	}
} else {
	require(SERVER_ROOT.'/sections/forums/main.php');
}

