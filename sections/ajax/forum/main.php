<?

authorize();

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

//We have to iterate here because if one is empty it breaks the query
$TopicIDs = array();
foreach($Forums as $Forum) {
	if (!empty($Forum['LastPostTopicID'])) {
		$TopicIDs[]=$Forum['LastPostTopicID'];
	}
}

//Now if we have IDs' we run the query
if(!empty($TopicIDs)) {
	$DB->query("SELECT
		l.TopicID,
		l.PostID,
		CEIL((SELECT COUNT(ID) FROM forums_posts WHERE forums_posts.TopicID = l.TopicID AND forums_posts.ID<=l.PostID)/$PerPage) AS Page
		FROM forums_last_read_topics AS l
		WHERE TopicID IN(".implode(',',$TopicIDs).") AND
		UserID='$LoggedUser[ID]'");
	$LastRead = $DB->to_array('TopicID', MYSQLI_ASSOC);
} else {
	$LastRead = array();
}

$DB->query("SELECT RestrictedForums FROM users_info WHERE UserID = ".$LoggedUser['ID']);
list($RestrictedForums) = $DB->next_record();
$RestrictedForums = explode(',', $RestrictedForums);
$PermittedForums = array_keys($LoggedUser['PermittedForums']);

$JsonCategories = array();
$JsonCategory = array();
$JsonForums = array();
foreach ($Forums as $Forum) {
	list($ForumID, $CategoryID, $ForumName, $ForumDescription, $MinRead, $MinWrite, $MinCreate, $NumTopics, $NumPosts, $LastPostID, $LastAuthorID, $LastPostAuthorName, $LastTopicID, $LastTime, $SpecificRules, $LastTopic, $Locked, $Sticky) = array_values($Forum);
	if ($LoggedUser['CustomForums'][$ForumID] != 1 && ($MinRead>$LoggedUser['Class'] || array_search($ForumID, $RestrictedForums) !== FALSE)) {
		continue;
	}
	$ForumDescription = display_str($ForumDescription);

	if($CategoryID!=$LastCategoryID) {
		if (!empty($JsonForums) && !empty($JsonCategory)) {
			$JsonCategory['forums'] = $JsonForums;
			$JsonCategories[] = $JsonCategory;
		}
		$LastCategoryID = $CategoryID;
		$JsonCategory = array(
			'categoryID' => $CategoryID,
			'categoryName' => $ForumCats[$CategoryID]
		);
		$JsonForums = array();
	}
	
	if((!$Locked || $Sticky) && $LastPostID != 0 && ((empty($LastRead[$LastTopicID]) || $LastRead[$LastTopicID]['PostID'] < $LastPostID) && strtotime($LastTime)>$LoggedUser['CatchupTime'])) {
		$Read = 'unread';
	} else {
		$Read = 'read';
	}

	$JsonForums[] = array(
		'forumId' => $ForumID,
		'forumName' => $ForumName,
		'forumDescription' => $ForumDescription,
		'numTopics' => $NumTopics,
		'numPosts' => $NumPosts,
		'lastPostId' => $LastPostID,
		'lastAuthorId' => $LastAuthorID,
		'lastPostAuthorName' => $LastPostAuthorName,
		'lastTopicId' => $LastTopicID,
		'lastTime' => $LastTime,
		'specificRules' => $SpecificRules,
		'lastTopic' => $LastTopic,
		'read' => $Read,
		'locked' => $Locked,
		'sticky' => $Sticky
	);
}

print json_encode(
	array(
		'status' => 'success',
		'response' => array(
			'categories' => $JsonCategories
		)
	)
);
