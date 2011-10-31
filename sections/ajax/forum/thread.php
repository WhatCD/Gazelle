<?

authorize();

//TODO: Normalize thread_*_info don't need to waste all that ram on things that are already in other caches
/**********|| Page to show individual threads || ********************************\

Things to expect in $_GET:
	ThreadID: ID of the forum curently being browsed
	page:	The page the user's on.
	page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

include(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

// Check for lame SQL injection attempts
if(!isset($_GET['threadid']) || !is_number($_GET['threadid'])) {
	if(isset($_GET['topicid']) && is_number($_GET['topicid'])) {
		$ThreadID = $_GET['topicid'];
	}
	elseif(isset($_GET['postid']) && is_number($_GET['postid'])) {
		$DB->query("SELECT TopicID FROM forums_posts WHERE ID = $_GET[postid]");
		list($ThreadID) = $DB->next_record();
		if($ThreadID) {
			header("Location: ajax.php?action=forum&type=viewthread&threadid=$ThreadID&postid=$_GET[postid]");
			die();
		} else {
			print json_encode(array('status' => 'failure'));
		}
	} else {
		print json_encode(array('status' => 'failure'));
	}
} else {
	$ThreadID = $_GET['threadid'];
}

if (isset($_GET['pp'])) {
	$PerPage = $_GET['pp'];
}
else if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

//---------- Get some data to start processing

// Thread information, constant across all pages
$ThreadInfo = get_thread_info($ThreadID, true, true);
$ForumID = $ThreadInfo['ForumID'];

// Make sure they're allowed to look at the page
if(!check_forumperm($ForumID)) {
	print json_encode(array('status' => 'failure'));
}

//Post links utilize the catalogue & key params to prevent issues with custom posts per page
if($ThreadInfo['Posts'] > $PerPage) {
	if(isset($_GET['post']) && is_number($_GET['post'])) {
		$PostNum = $_GET['post'];
	} elseif(isset($_GET['postid']) && is_number($_GET['postid'])) {
		$DB->query("SELECT COUNT(ID) FROM forums_posts WHERE TopicID = $ThreadID AND ID <= $_GET[postid]");
		list($PostNum) = $DB->next_record();
	} else {
		$PostNum = 1;
	}
} else {
	$PostNum = 1;
}
list($Page,$Limit) = page_limit($PerPage, min($ThreadInfo['Posts'],$PostNum));
list($CatalogueID,$CatalogueLimit) = catalogue_limit($Page,$PerPage,THREAD_CATALOGUE);

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
if(!$Catalogue = $Cache->get_value('thread_'.$ThreadID.'_catalogue_'.$CatalogueID)) {
	$DB->query("SELECT
		p.ID,
		p.AuthorID,
		p.AddedTime,
		p.Body,
		p.EditedUserID,
		p.EditedTime,
		ed.Username
		FROM forums_posts as p
		LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
		WHERE p.TopicID = '$ThreadID' AND p.ID != '".$ThreadInfo['StickyPostID']."'
		LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array(false,MYSQLI_ASSOC);
	if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
		$Cache->cache_value('thread_'.$ThreadID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
	}
}
$Thread = catalogue_select($Catalogue,$Page,$PerPage,THREAD_CATALOGUE);

$LastPost = end($Thread);
$LastPost = $LastPost['ID'];
reset($Thread);

//Handle last read
if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
	$DB->query("SELECT PostID From forums_last_read_topics WHERE UserID='$LoggedUser[ID]' AND TopicID='$ThreadID'");
	list($LastRead) = $DB->next_record();
	if($LastRead < $LastPost) {
		$DB->query("INSERT INTO forums_last_read_topics
			(UserID, TopicID, PostID) VALUES
			('$LoggedUser[ID]', '".$ThreadID ."', '".db_string($LastPost)."')
			ON DUPLICATE KEY UPDATE PostID='$LastPost'");
	}
}

//Handle subscriptions
if(($UserSubscriptions = $Cache->get_value('subscriptions_user_'.$LoggedUser['ID'])) === FALSE) {
	$DB->query("SELECT TopicID FROM users_subscriptions WHERE UserID = '$LoggedUser[ID]'");
	$UserSubscriptions = $DB->collect(0);
	$Cache->cache_value('subscriptions_user_'.$LoggedUser['ID'],$UserSubscriptions,0);
}

if(empty($UserSubscriptions)) {
	$UserSubscriptions = array();
}

if(in_array($ThreadID, $UserSubscriptions)) {
	$Cache->delete_value('subscriptions_user_new_'.$LoggedUser['ID']);
}

$JsonPoll = array();
if ($ThreadInfo['NoPoll'] == 0) {
	if (!list($Question,$Answers,$Votes,$Featured,$Closed) = $Cache->get_value('polls_'.$ThreadID)) {
		$DB->query("SELECT Question, Answers, Featured, Closed FROM forums_polls WHERE TopicID='".$ThreadID."'");
		list($Question, $Answers, $Featured, $Closed) = $DB->next_record(MYSQLI_NUM, array(1));
		$Answers = unserialize($Answers);
		$DB->query("SELECT Vote, COUNT(UserID) FROM forums_polls_votes WHERE TopicID='$ThreadID' GROUP BY Vote");
		$VoteArray = $DB->to_array(false, MYSQLI_NUM);
		
		$Votes = array();
		foreach ($VoteArray as $VoteSet) {
			list($Key,$Value) = $VoteSet; 
			$Votes[$Key] = $Value;
		}
		
		foreach(array_keys($Answers) as $i) {
			if (!isset($Votes[$i])) {
				$Votes[$i] = 0;
			}
		}
		$Cache->cache_value('polls_'.$ThreadID, array($Question,$Answers,$Votes,$Featured,$Closed), 0);
	}
	
	if (!empty($Votes)) {
		$TotalVotes = array_sum($Votes);
		$MaxVotes = max($Votes);
	} else {
		$TotalVotes = 0;
		$MaxVotes = 0;
	}
	
	$RevealVoters = in_array($ForumID, $ForumsRevealVoters);
	//Polls lose the you voted arrow thingy
	$DB->query("SELECT Vote FROM forums_polls_votes WHERE UserID='".$LoggedUser['ID']."' AND TopicID='$ThreadID'");
	list($UserResponse) = $DB->next_record();
	if (!empty($UserResponse) && $UserResponse != 0) {
		$Answers[$UserResponse] = '&raquo; '.$Answers[$UserResponse];
	} else {
		if(!empty($UserResponse) && $RevealVoters) {
			$Answers[$UserResponse] = '&raquo; '.$Answers[$UserResponse];
		}
	}
	
	$JsonPoll['closed'] = $Closed;
	$JsonPoll['featured'] = $Featured;
	$JsonPoll['question'] = $Question;
	$JsonPoll['maxVotes'] = $MaxVotes;
	$JsonPoll['totalVotes'] = $TotalVotes;
	$JsonPollAnswers = array();
	
	foreach($Answers as $i => $Answer) {
		if (!empty($Votes[$i]) && $TotalVotes > 0) {
			$Ratio = $Votes[$i]/$MaxVotes;
			$Percent = $Votes[$i]/$TotalVotes;
		} else {
			$Ratio=0;
			$Percent=0;
		}
		$JsonPollAnswers[] = array(
			'answer' => $Answer,
			'ratio' => $Ratio,
			'percent' => $Percent
		);
	}
	
	if ($UserResponse !== null || $Closed || $ThreadInfo['IsLocked'] || $LoggedUser['Class'] < $Forums[$ForumID]['MinClassWrite']) {
		$JsonPoll['voted'] = True;	
	} else {
		$JsonPoll['voted'] = False;
	}
	
	$JsonPoll['answers'] = $JsonPollAnswers;
}

//Sqeeze in stickypost
if($ThreadInfo['StickyPostID']) {
	if($ThreadInfo['StickyPostID'] != $Thread[0]['ID']) {
		array_unshift($Thread, $ThreadInfo['StickyPost']);
	}
	if($ThreadInfo['StickyPostID'] != $Thread[count($Thread)-1]['ID']) {
		$Thread[] = $ThreadInfo['StickyPost'];
	}
}

$JsonPosts = array();
foreach ($Thread as $Key => $Post) {
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(user_info($AuthorID));
	$JsonPosts[] = array(
		'postId' => $PostID,
		'addedTime' => $AddedTime,
		'body' => $Text->full_format($Body),
		'editedUserId' => $EditedUserID,
		'editedTime' => $EditedTime,
		'editedUsername' => $EditedUsername,
		'author' => array(
			'authorId' => $AuthorID,
			'authorName' => $Username,
			'paranoia' => $Paranoia,
			'artist' => $Artist,
			'donor' => $Donor,
			'warned' => $Warned,
			'avatar' => $Avatar,
			'enabled' => $Enabled == 2 ? false : true,
			'userTitle' => $UserTitle
		),
		
	);
}

print
	json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'forumId' => $ForumID,
				'forumName' => $Forums[$ForumID]['Name'],
				'threadId' => $ThreadID,
				'threadTitle' => $ThreadInfo['Title'],
				'subscribed' => in_array($ThreadID, $UserSubscriptions),
				'locked' => $ThreadInfo['IsLocked'],
				'sticky' => $ThreadInfo['IsSticky'],
				'currentPage' => intval($Page),
				'pages' => ceil($ThreadInfo['Posts']/$PerPage),
				'poll' => $JsonPoll,
				'posts' => $JsonPosts
			)
		)
	);