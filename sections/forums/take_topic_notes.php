<?
authorize();

if (!check_perms('site_moderate_forums')) {
	error(403);
}

if (!isset($_POST['topicid'], $_POST['body']) || !is_number($_POST['topicid']) || $_POST['body'] == '') {
	error(404);
}

$TopicID = (int)$_POST['topicid'];

Forums::add_topic_note($TopicID, $_POST['body']);

header("Location: forums.php?action=viewthread&threadid=$TopicID#thread_notes");
die();
