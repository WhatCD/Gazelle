<?
authorize();

/*
'new' if the user is creating a new thread
	It will be accompanied with:
	$_POST['forum']
	$_POST['title']
	$_POST['body']

	and optionally include:
	$_POST['question']
	$_POST['answers']
	the latter of which is an array
*/

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}


if (isset($_POST['thread']) && !is_number($_POST['thread'])) {
	error(0);
}

if (isset($_POST['forum']) && !is_number($_POST['forum'])) {
	error(0);
}

// If you're not sending anything, go back
if (empty($_POST['body']) || empty($_POST['title'])) {
	header('Location: '.$_SERVER['HTTP_REFERER']);
	die();
}

$Body = $_POST['body'];

if ($LoggedUser['DisablePosting']) {
	error('Your posting privileges have been removed.');
}

$Title = Format::cut_string(trim($_POST['title']), 150, 1, 0);


$ForumID = $_POST['forum'];

if (!isset($Forums[$ForumID])) {
	error(404);
}

if (!Forums::check_forumperm($ForumID, 'Write') || !Forums::check_forumperm($ForumID, 'Create')) {
	error(403);
}

if (empty($_POST['question']) || empty($_POST['answers']) || !check_perms('forums_polls_create')) {
	$NoPoll = 1;
} else {
	$NoPoll = 0;
	$Question = trim($_POST['question']);
	$Answers = array();
	$Votes = array();

	//This can cause polls to have answer IDs of 1 3 4 if the second box is empty
	foreach ($_POST['answers'] as $i => $Answer) {
		if ($Answer == '') {
			continue;
		}
		$Answers[$i + 1] = $Answer;
		$Votes[$i + 1] = 0;
	}

	if (count($Answers) < 2) {
		error('You cannot create a poll with only one answer.');
	} elseif (count($Answers) > 25) {
		error('You cannot create a poll with greater than 25 answers.');
	}
}

$DB->query("
	INSERT INTO forums_topics
		(Title, AuthorID, ForumID, LastPostTime, LastPostAuthorID, CreatedTime)
	Values
		('".db_string($Title)."', '".$LoggedUser['ID']."', '$ForumID', '".sqltime()."', '".$LoggedUser['ID']."', '".sqltime()."')");
$TopicID = $DB->inserted_id();

$DB->query("
	INSERT INTO forums_posts
		(TopicID, AuthorID, AddedTime, Body)
	VALUES
		('$TopicID', '".$LoggedUser['ID']."', '".sqltime()."', '".db_string($Body)."')");

$PostID = $DB->inserted_id();

$DB->query("
	UPDATE forums
	SET
		NumPosts         = NumPosts + 1,
		NumTopics        = NumTopics + 1,
		LastPostID       = '$PostID',
		LastPostAuthorID = '".$LoggedUser['ID']."',
		LastPostTopicID  = '$TopicID',
		LastPostTime     = '".sqltime()."'
	WHERE ID = '$ForumID'");

$DB->query("
	UPDATE forums_topics
	SET
		NumPosts         = NumPosts + 1,
		LastPostID       = '$PostID',
		LastPostAuthorID = '".$LoggedUser['ID']."',
		LastPostTime     = '".sqltime()."'
	WHERE ID = '$TopicID'");

if (isset($_POST['subscribe'])) {
	Subscriptions::subscribe($TopicID);
}

if (!$NoPoll) { // god, I hate double negatives...
	$DB->query("
		INSERT INTO forums_polls
			(TopicID, Question, Answers)
		VALUES
			('$TopicID', '".db_string($Question)."', '".db_string(serialize($Answers))."')");
	$Cache->cache_value("polls_$TopicID", array($Question, $Answers, $Votes, '0000-00-00 00:00:00', '0'), 0);

	if ($ForumID == STAFF_FORUM) {
		send_irc('PRIVMSG '.ADMIN_CHAN.' :!mod Poll created by '.$LoggedUser['Username'].": \"$Question\" ".site_url()."forums.php?action=viewthread&threadid=$TopicID");
	}
}

// if cache exists modify it, if not, then it will be correct when selected next, and we can skip this block
if ($Forum = $Cache->get_value("forums_$ForumID")) {
	list($Forum,,,$Stickies) = $Forum;

	// Remove the last thread from the index
	if (count($Forum) == TOPICS_PER_PAGE && $Stickies < TOPICS_PER_PAGE) {
		array_pop($Forum);
	}

	if ($Stickies > 0) {
		$Part1 = array_slice($Forum, 0, $Stickies, true); // Stickies
		$Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE - $Stickies - 1, true); // Rest of page
	} else {
		$Part1 = array();
		$Part3 = $Forum;
	}
	$Part2 = array($TopicID => array(
		'ID' => $TopicID,
		'Title' => $Title,
		'AuthorID' => $LoggedUser['ID'],
		'IsLocked' => 0,
		'IsSticky' => 0,
		'NumPosts' => 1,
		'LastPostID' => $PostID,
		'LastPostTime' => sqltime(),
		'LastPostAuthorID' => $LoggedUser['ID'],
		'NoPoll' => $NoPoll
	)); // Bumped
	$Forum = $Part1 + $Part2 + $Part3;

	$Cache->cache_value("forums_$ForumID", array($Forum, '', 0, $Stickies), 0);

	// Update the forum root
	$Cache->begin_transaction('forums_list');
	$Cache->update_row($ForumID, array(
		'NumPosts' => '+1',
		'NumTopics' => '+1',
		'LastPostID' => $PostID,
		'LastPostAuthorID' => $LoggedUser['ID'],
		'LastPostTopicID' => $TopicID,
		'LastPostTime' => sqltime(),
		'Title' => $Title,
		'IsLocked' => 0,
		'IsSticky' => 0
		));
	$Cache->commit_transaction(0);
} else {
	// If there's no cache, we have no data, and if there's no data
	$Cache->delete_value('forums_list');
}

$Cache->begin_transaction("thread_$TopicID".'_catalogue_0');
$Post = array(
	'ID' => $PostID,
	'AuthorID' => $LoggedUser['ID'],
	'AddedTime' => sqltime(),
	'Body' => $Body,
	'EditedUserID' => 0,
	'EditedTime' => '0000-00-00 00:00:00'
	);
$Cache->insert('', $Post);
$Cache->commit_transaction(0);

$Cache->begin_transaction("thread_$TopicID".'_info');
$Cache->update_row(false, array('Posts' => '+1', 'LastPostAuthorID' => $LoggedUser['ID']));
$Cache->commit_transaction(0);


header("Location: forums.php?action=viewthread&threadid=$TopicID");
die();
