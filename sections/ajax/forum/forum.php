<?

authorize();

/**********|| Page to show individual forums || ********************************\

Things to expect in $_GET:
	ForumID: ID of the forum curently being browsed
	page:	The page the user's on.
	page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

// Check for lame SQL injection attempts
$ForumID = $_GET['forumid'];
if(!is_number($ForumID)) {
	print json_encode(array('status' => 'failure'));
	die();
}

if (isset($_GET['pp'])) {
	$PerPage = $_GET['pp'];
}
else if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

list($Page,$Limit) = page_limit(TOPICS_PER_PAGE);

//---------- Get some data to start processing

// Caching anything beyond the first page of any given forum is just wasting ram
// users are more likely to search then to browse to page 2
if($Page==1) {
	list($Forum,,,$Stickies) = $Cache->get_value('forums_'.$ForumID);
}
if(!isset($Forum) || !is_array($Forum)) {
	$DB->query("SELECT
		t.ID,
		t.Title,
		t.AuthorID,
		author.Username AS AuthorUsername,
		t.IsLocked,
		t.IsSticky,
		t.NumPosts,
		t.LastPostID,
		t.LastPostTime,
		t.LastPostAuthorID,
		last_author.Username AS LastPostUsername
		FROM forums_topics AS t
		LEFT JOIN users_main AS last_author ON last_author.ID = t.LastPostAuthorID
		LEFT JOIN users_main AS author ON author.ID = t.AuthorID
		WHERE t.ForumID = '$ForumID'
		ORDER BY t.IsSticky DESC, t.LastPostTime DESC
		LIMIT $Limit"); // Can be cached until someone makes a new post
	$Forum = $DB->to_array('ID',MYSQLI_ASSOC);
	if($Page==1) {
		$DB->query("SELECT COUNT(ID) FROM forums_topics WHERE ForumID='$ForumID' AND IsSticky='1'");
		list($Stickies) = $DB->next_record();
		$Cache->cache_value('forums_'.$ForumID, array($Forum,'',0,$Stickies), 0);
	}
}

if(!isset($Forums[$ForumID])) {
	print json_encode(array('status' => 'failure'));
	die();
}
// Make sure they're allowed to look at the page
if (!check_perms('site_moderate_forums')) {
	if (isset($LoggedUser['CustomForums'][$ForumID]) && $LoggedUser['CustomForums'][$ForumID] === 0) { error(403); }
}
if($LoggedUser['CustomForums'][$ForumID] != 1 && $Forums[$ForumID]['MinClassRead'] > $LoggedUser['Class']) { error(403); }

$JsonSpecificRules = array();
foreach ($Forums[$ForumID]['SpecificRules'] as $ThreadIDs) {
	$Thread = get_thread_info($ThreadIDs);
	$JsonSpecificRules[] = array(
		'threadId' => $ThreadIDs,
		'thread' => $Thread['Title']
	);
}

$Pages=get_pages($Page,$Forums[$ForumID]['NumTopics'],TOPICS_PER_PAGE,9);

if (count($Forum) == 0) {
	print
		json_encode(
			array(
				'status' => 'success',
				'forumName' => $Forums[$ForumID]['Name'],
				'threads' => array()
			)
		);
}
else {
	// forums_last_read_topics is a record of the last post a user read in a topic, and what page that was on
	$DB->query('SELECT
		l.TopicID,
		l.PostID,
		CEIL((SELECT COUNT(ID) FROM forums_posts WHERE forums_posts.TopicID = l.TopicID AND forums_posts.ID<=l.PostID)/'.$PerPage.') AS Page
		FROM forums_last_read_topics AS l
		WHERE TopicID IN('.implode(', ', array_keys($Forum)).') AND
		UserID=\''.$LoggedUser['ID'].'\'');

	// Turns the result set into a multi-dimensional array, with
	// forums_last_read_topics.TopicID as the key.
	// This is done here so we get the benefit of the caching, and we
	// don't have to make a database query for each topic on the page
	$LastRead = $DB->to_array('TopicID');
	
	$JsonTopics = array();
	foreach ($Forum as $Topic) {
		list($TopicID, $Title, $AuthorID, $AuthorName, $Locked, $Sticky, $PostCount, $LastID, $LastTime, $LastAuthorID, $LastAuthorName) = array_values($Topic);

		// handle read/unread posts - the reason we can't cache the whole page
		if((!$Locked || $Sticky) && ((empty($LastRead[$TopicID]) || $LastRead[$TopicID]['PostID']<$LastID) && strtotime($LastTime)>$LoggedUser['CatchupTime'])) {
			$Read = 'unread';
		} else {
			$Read = 'read';
		}
		
		$JsonTopics[] = array(
			'topicId' => $TopicID,
			'title' => $Title,
			'authorId' => $AuthorID,
			'authorName' => $AuthorName,
			'locked' => $Locked,
			'sticky' => $Sticky,
			'postCount' => $PostCount,
			'lastID' => $LastID,
			'lastTime' => $LastTime,
			'lastAuthorId' => $LastAuthorID,
			'lastAuthorName' => $LastAuthorName,
			'lastReadPage' => $LastRead[$TopicID]['Page'],
			'lastReadPostId' => $LastRead[$TopicID]['PostID']
		);
	}

	print
		json_encode(
			array(
				'status' => 'success',
				'response' => array(
					'forumName' => $Forums[$ForumID]['Name'],
					'specificRules' => $JsonSpecificRules,
					'currentPage' => intval($Page),
					'pages' => ceil($Forums[$ForumID]['NumTopics']/TOPICS_PER_PAGE),
					'threads' => $JsonTopics
				)
			)
		);
}
?>