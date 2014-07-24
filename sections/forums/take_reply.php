<?
authorize();

//TODO: Remove all the stupid queries that could get their information just as easily from the cache
/*********************************************************************\
//--------------Take Post--------------------------------------------//

This page takes a forum post submission, validates it (TODO), and
enters it into the database. The user is then redirected to their
post.

$_POST['action'] is what the user is trying to do. It can be:

'reply' if the user is replying to a thread
	It will be accompanied with:
	$_POST['thread']
	$_POST['body']


\*********************************************************************/

// Quick SQL injection checks

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
if ($_POST['body'] === '' || !isset($_POST['body'])) {
	header('Location: '.$_SERVER['HTTP_REFERER']);
	die();
}

$Body = $_POST['body'];

if (!empty($LoggedUser['DisablePosting'])) {
	error('Your posting privileges have been removed.');
}

$TopicID = $_POST['thread'];
$ThreadInfo = Forums::get_thread_info($TopicID);
if ($ThreadInfo === null) {
	error(404);
}
$ForumID = $ThreadInfo['ForumID'];
$SQLTime = sqltime();

if (!Forums::check_forumperm($ForumID)) {
	error(403);
}
if (!Forums::check_forumperm($ForumID, 'Write') || $LoggedUser['DisablePosting'] || $ThreadInfo['IsLocked'] == '1' && !check_perms('site_moderate_forums')) {
	error(403);
}

if (isset($_POST['subscribe']) && Subscriptions::has_subscribed($TopicID) === false) {
	Subscriptions::subscribe($TopicID);
}

//Now lets handle the special case of merging posts, we can skip bumping the thread and all that fun
if ($ThreadInfo['LastPostAuthorID'] == $LoggedUser['ID'] && ((!check_perms('site_forums_double_post') && !in_array($ForumID, $ForumsDoublePost)) || isset($_POST['merge']))) {
	//Get the id for this post in the database to append
	$DB->query("
		SELECT ID, Body
		FROM forums_posts
		WHERE TopicID = '$TopicID'
			AND AuthorID = '".$LoggedUser['ID']."'
		ORDER BY ID DESC
		LIMIT 1");
	list($PostID, $OldBody) = $DB->next_record(MYSQLI_NUM, false);

	//Edit the post
	$DB->query("
		UPDATE forums_posts
		SET
			Body = CONCAT(Body,'\n\n".db_string($Body)."'),
			EditedUserID = '".$LoggedUser['ID']."',
			EditedTime = '$SQLTime'
		WHERE ID = '$PostID'");

	//Store edit history
	$DB->query("
		INSERT INTO comments_edits
			(Page, PostID, EditUser, EditTime, Body)
		VALUES
			('forums', $PostID, ".$LoggedUser['ID'].", '$SQLTime', '".db_string($OldBody)."')");
	$Cache->delete_value("forums_edits_$PostID");

	//Get the catalogue it is in
	$CatalogueID = floor((POSTS_PER_PAGE * ceil($ThreadInfo['Posts'] / POSTS_PER_PAGE) - POSTS_PER_PAGE) / THREAD_CATALOGUE);

	//Get the catalogue value for the post we're appending to
	if ($ThreadInfo['Posts'] % THREAD_CATALOGUE == 0) {
		$Key = THREAD_CATALOGUE - 1;
	} else {
		$Key = ($ThreadInfo['Posts'] % THREAD_CATALOGUE) - 1;
	}
	if ($ThreadInfo['StickyPostID'] == $PostID) {
		$ThreadInfo['StickyPost']['Body'] .= "\n\n$Body";
		$ThreadInfo['StickyPost']['EditedUserID'] = $LoggedUser['ID'];
		$ThreadInfo['StickyPost']['EditedTime'] = $SQLTime;
		$Cache->cache_value("thread_$TopicID".'_info', $ThreadInfo, 0);
	}

	//Edit the post in the cache
	$Cache->begin_transaction("thread_$TopicID"."_catalogue_$CatalogueID");
	$Cache->update_row($Key, array(
			'Body' => $Cache->MemcacheDBArray[$Key]['Body']."\n\n$Body",
			'EditedUserID' => $LoggedUser['ID'],
			'EditedTime' => $SQLTime,
			'Username' => $LoggedUser['Username']
			));
	$Cache->commit_transaction(0);

//Now we're dealing with a normal post
} else {
	//Insert the post into the posts database
	$DB->query("
		INSERT INTO forums_posts (TopicID, AuthorID, AddedTime, Body)
		VALUES ('$TopicID', '".$LoggedUser['ID']."', '$SQLTime', '".db_string($Body)."')");

	$PostID = $DB->inserted_id();

	//This updates the root index
	$DB->query("
		UPDATE forums
		SET
			NumPosts = NumPosts + 1,
			LastPostID = '$PostID',
			LastPostAuthorID = '".$LoggedUser['ID']."',
			LastPostTopicID = '$TopicID',
			LastPostTime = '$SQLTime'
		WHERE ID = '$ForumID'");

	//Update the topic
	$DB->query("
		UPDATE forums_topics
		SET
			NumPosts = NumPosts + 1,
			LastPostID = '$PostID',
			LastPostAuthorID = '".$LoggedUser['ID']."',
			LastPostTime = '$SQLTime'
		WHERE ID = '$TopicID'");

	// if cache exists modify it, if not, then it will be correct when selected next, and we can skip this block
	if ($Forum = $Cache->get_value("forums_$ForumID")) {
		list($Forum,,,$Stickies) = $Forum;

		// if the topic is already on this page
		if (array_key_exists($TopicID, $Forum)) {
			$Thread = $Forum[$TopicID];
			unset($Forum[$TopicID]);
			$Thread['NumPosts'] = $Thread['NumPosts'] + 1; // Increment post count
			$Thread['LastPostID'] = $PostID; // Set post ID for read/unread
			$Thread['LastPostTime'] = $SQLTime; // Time of last post
			$Thread['LastPostAuthorID'] = $LoggedUser['ID']; // Last poster ID
			$Part2 = array($TopicID => $Thread); // Bumped thread

		// if we're bumping from an older page
		} else {
			// Remove the last thread from the index
			if (count($Forum) == TOPICS_PER_PAGE && $Stickies < TOPICS_PER_PAGE) {
				array_pop($Forum);
			}
			// Never know if we get a page full of stickies...
			if ($Stickies < TOPICS_PER_PAGE || $ThreadInfo['IsSticky'] == 1) {
				//Pull the data for the thread we're bumping
				$DB->query("
					SELECT
						f.AuthorID,
						f.IsLocked,
						f.IsSticky,
						f.NumPosts,
						ISNULL(p.TopicID) AS NoPoll
					FROM forums_topics AS f
						LEFT JOIN forums_polls AS p ON p.TopicID = f.ID
					WHERE f.ID = '$TopicID'");
				list($AuthorID, $IsLocked, $IsSticky, $NumPosts, $NoPoll) = $DB->next_record();
				$Part2 = array($TopicID => array(
					'ID' => $TopicID,
					'Title' => $ThreadInfo['Title'],
					'AuthorID' => $AuthorID,
					'IsLocked' => $IsLocked,
					'IsSticky' => $IsSticky,
					'NumPosts' => $NumPosts,
					'LastPostID' => $PostID,
					'LastPostTime' => $SQLTime,
					'LastPostAuthorID' => $LoggedUser['ID'],
					'NoPoll' => $NoPoll
				)); //Bumped
			} else {
				$Part2 = array();
			}
		}
		if ($Stickies > 0) {
			$Part1 = array_slice($Forum, 0, $Stickies, true); //Stickies
			$Part3 = array_slice($Forum, $Stickies, TOPICS_PER_PAGE - $Stickies - 1, true); //Rest of page
		} else {
			$Part1 = array();
			$Part3 = $Forum;
		}
		if (is_null($Part1)) {
			$Part1 = array();
		}
		if (is_null($Part3)) {
			$Part3 = array();
		}
		if ($ThreadInfo['IsSticky'] == 1) {
			$Forum = $Part2 + $Part1 + $Part3; //Merge it
		} else {
			$Forum = $Part1 + $Part2 + $Part3; //Merge it
		}
		$Cache->cache_value("forums_$ForumID", array($Forum, '', 0, $Stickies), 0);

		//Update the forum root
		$Cache->begin_transaction('forums_list');
		$Cache->update_row($ForumID, array(
			'NumPosts'=>'+1',
			'LastPostID'=>$PostID,
			'LastPostAuthorID'=>$LoggedUser['ID'],
			'LastPostTopicID'=>$TopicID,
			'LastPostTime'=>$SQLTime,
			'Title'=>$ThreadInfo['Title'],
			'IsLocked'=>$ThreadInfo['IsLocked'],
			'IsSticky'=>$ThreadInfo['IsSticky']
			));
		$Cache->commit_transaction(0);
	} else {
		//If there's no cache, we have no data, and if there's no data
		$Cache->delete_value('forums_list');
	}


	//This calculates the block of 500 posts that this one will fall under
	$CatalogueID = floor((POSTS_PER_PAGE * ceil($ThreadInfo['Posts'] / POSTS_PER_PAGE) - POSTS_PER_PAGE) / THREAD_CATALOGUE);

	//Insert the post into the thread catalogue (block of 500 posts)
	$Cache->begin_transaction("thread_$TopicID"."_catalogue_$CatalogueID");
	$Cache->insert('', array(
		'ID'=>$PostID,
		'AuthorID'=>$LoggedUser['ID'],
		'AddedTime'=>$SQLTime,
		'Body'=>$Body,
		'EditedUserID'=>0,
		'EditedTime'=>'0000-00-00 00:00:00',
		'Username'=>$LoggedUser['Username'] //TODO: Remove, it's never used?
		));
	$Cache->commit_transaction(0);

	//Update the thread info
	$Cache->begin_transaction("thread_$TopicID".'_info');
	$Cache->update_row(false, array('Posts' => '+1', 'LastPostAuthorID' => $LoggedUser['ID']));
	$Cache->commit_transaction(0);

	//Increment this now to make sure we redirect to the correct page
	$ThreadInfo['Posts']++;
}

Subscriptions::flush_subscriptions('forums', $TopicID);
Subscriptions::quote_notify($Body, $PostID, 'forums', $TopicID);

header("Location: forums.php?action=viewthread&threadid=$TopicID&page=".ceil($ThreadInfo['Posts'] / $PerPage));
die();
