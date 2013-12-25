<?
/*********************************************************************\
//--------------Mod thread-------------------------------------------//

This page gets called if we're editing a thread.

Known issues:
If multiple threads are moved before forum activity occurs then
threads will linger with the 'Moved' flag until they're knocked off
the front page.

\*********************************************************************/
define('TRASH_FORUM_ID', 12);

// Quick SQL injection check
if (!is_number($_POST['threadid'])) {
	error(404);
}
if ($_POST['title'] == '') {
	error(0);
}
// End injection check
// Make sure they are moderators
if (!check_perms('site_moderate_forums')) {
	error(403);
}
authorize();

// Variables for database input

$TopicID = (int)$_POST['threadid'];
$Sticky = isset($_POST['sticky']) ? 1 : 0;
$Locked = isset($_POST['locked']) ? 1 : 0;
$Ranking = (int)$_POST['ranking'];
if (!$Sticky && $Ranking > 0) {
	$Ranking = 0;
} elseif (0 > $Ranking) {
	error('Ranking cannot be a negative value');
}
$Title = db_string($_POST['title']);
$RawTitle = $_POST['title'];
$ForumID = (int)$_POST['forumid'];
$Page = (int)$_POST['page'];
$Action = '';


if ($Locked == 1) {

	$DB->query("
		DELETE FROM forums_last_read_topics
		WHERE TopicID = '$TopicID'");
}

$DB->query("
	SELECT
		t.ForumID,
		f.Name,
		f.MinClassWrite,
		COUNT(p.ID) AS Posts,
		t.AuthorID,
		t.Title,
		t.IsLocked,
		t.IsSticky,
		t.Ranking
	FROM forums_topics AS t
		LEFT JOIN forums_posts AS p ON p.TopicID = t.ID
		LEFT JOIN forums AS f ON f.ID = t.ForumID
	WHERE t.ID = '$TopicID'
	GROUP BY p.TopicID");
list($OldForumID, $OldForumName, $MinClassWrite, $Posts, $ThreadAuthorID, $OldTitle, $OldLocked, $OldSticky, $OldRanking) = $DB->next_record(MYSQLI_BOTH, false);

if ($MinClassWrite > $LoggedUser['Class']) {
	error(403);
}

// If we're deleting a thread
if (isset($_POST['delete'])) {
	if (!check_perms('site_admin_forums')) {
		error(403);
	}

	$DB->query("
		DELETE FROM forums_posts
		WHERE TopicID = '$TopicID'");
	$DB->query("
		DELETE FROM forums_topics
		WHERE ID = '$TopicID'");

	$DB->query("
		SELECT
			t.ID,
			t.LastPostID,
			t.Title,
			p.AuthorID,
			um.Username,
			p.AddedTime,
			(
				SELECT COUNT(pp.ID)
				FROM forums_posts AS pp
					JOIN forums_topics AS tt ON pp.TopicID = tt.ID
				WHERE tt.ForumID = '$ForumID'
			),
			t.IsLocked,
			t.IsSticky
		FROM forums_topics AS t
			JOIN forums_posts AS p ON p.ID = t.LastPostID
			LEFT JOIN users_main AS um ON um.ID = p.AuthorID
		WHERE t.ForumID = '$ForumID'
		GROUP BY t.ID
		ORDER BY t.LastPostID DESC
		LIMIT 1");
	list($NewLastTopic, $NewLastPostID, $NewLastTitle, $NewLastAuthorID, $NewLastAuthorName, $NewLastAddedTime, $NumPosts, $NewLocked, $NewSticky) = $DB->next_record(MYSQLI_NUM, false);

	$DB->query("
		UPDATE forums
		SET
			NumTopics = NumTopics - 1,
			NumPosts = NumPosts - '$Posts',
			LastPostTopicID = '$NewLastTopic',
			LastPostID = '$NewLastPostID',
			LastPostAuthorID = '$NewLastAuthorID',
			LastPostTime = '$NewLastAddedTime'
		WHERE ID = '$ForumID'");
	$Cache->delete_value("forums_$ForumID");

	$Cache->delete_value("thread_$TopicID");

	$Cache->begin_transaction('forums_list');
	$UpdateArray = array(
		'NumPosts' => $NumPosts,
		'NumTopics' => '-1',
		'LastPostID' => $NewLastPostID,
		'LastPostAuthorID' => $NewLastAuthorID,
		'LastPostTopicID' => $NewLastTopic,
		'LastPostTime' => $NewLastAddedTime,
		'Title' => $NewLastTitle,
		'IsLocked' => $NewLocked,
		'IsSticky' => $NewSticky
		);

	$Cache->update_row($ForumID, $UpdateArray);
	$Cache->commit_transaction(0);
	$Cache->delete_value("thread_{$TopicID}_info");

	// subscriptions
	Subscriptions::move_subscriptions('forums', $TopicID, null);

	// quote notifications
	Subscriptions::flush_quote_notifications('forums', $TopicID);
	$DB->query("
		DELETE FROM users_notify_quoted
		WHERE Page = 'forums'
			AND PageID = '$TopicID'");

	header("Location: forums.php?action=viewforum&forumid=$ForumID");
} else { // If we're just editing it
	$Action = 'editing';

	if (isset($_POST['trash'])) {
		$ForumID = TRASH_FORUM_ID;
		$Action = 'trashing';
	}

	$Cache->begin_transaction("thread_{$TopicID}_info");
	$UpdateArray = array(
		'IsSticky' => $Sticky,
		'Ranking' => $Ranking,
		'IsLocked' => $Locked,
		'Title' => Format::cut_string($RawTitle, 150, 1, 0),
		'ForumID' => $ForumID
		);
	$Cache->update_row(false, $UpdateArray);
	$Cache->commit_transaction(0);

	$DB->query("
		UPDATE forums_topics
		SET
			IsSticky = '$Sticky',
			Ranking = '$Ranking',
			IsLocked = '$Locked',
			Title = '$Title',
			ForumID = '$ForumID'
		WHERE ID = '$TopicID'");

	// always clear cache when editing a thread.
	// if a thread title, etc. is changed, this cache key must be cleared so the thread listing
	//		properly shows the new thread title.
	$Cache->delete_value("forums_$ForumID");

	if ($ForumID != $OldForumID) { // If we're moving a thread, change the forum stats
		$Cache->delete_value("forums_$OldForumID");

		$DB->query("
			SELECT MinClassRead, MinClassWrite, Name
			FROM forums
			WHERE ID = '$ForumID'");
		list($MinClassRead, $MinClassWrite, $ForumName) = $DB->next_record(MYSQLI_NUM, false);
		$Cache->begin_transaction("thread_{$TopicID}_info");
		$UpdateArray = array(
			'ForumName' => $ForumName,
			'MinClassRead' => $MinClassRead,
			'MinClassWrite' => $MinClassWrite
			);
		$Cache->update_row(false, $UpdateArray);
		$Cache->commit_transaction(3600 * 24 * 5);

		$Cache->begin_transaction('forums_list');

		// Forum we're moving from
		$DB->query("
			SELECT
				t.ID,
				t.LastPostID,
				t.Title,
				p.AuthorID,
				um.Username,
				p.AddedTime,
				(
					SELECT COUNT(pp.ID)
					FROM forums_posts AS pp
						JOIN forums_topics AS tt ON pp.TopicID = tt.ID
					WHERE tt.ForumID = '$OldForumID'
				),
				t.IsLocked,
				t.IsSticky,
				t.Ranking
			FROM forums_topics AS t
				JOIN forums_posts AS p ON p.ID = t.LastPostID
				LEFT JOIN users_main AS um ON um.ID = p.AuthorID
			WHERE t.ForumID = '$OldForumID'
			ORDER BY t.LastPostID DESC
			LIMIT 1");
		list($NewLastTopic, $NewLastPostID, $NewLastTitle, $NewLastAuthorID, $NewLastAuthorName, $NewLastAddedTime, $NumPosts, $NewLocked, $NewSticky, $NewRanking) = $DB->next_record(MYSQLI_NUM, false);

		$DB->query("
			UPDATE forums
			SET
				NumTopics = NumTopics - 1,
				NumPosts = NumPosts - '$Posts',
				LastPostTopicID = '$NewLastTopic',
				LastPostID = '$NewLastPostID',
				LastPostAuthorID = '$NewLastAuthorID',
				LastPostTime = '$NewLastAddedTime'
			WHERE ID = '$OldForumID'");


		$UpdateArray = array(
			'NumPosts' => $NumPosts,
			'NumTopics' => '-1',
			'LastPostID' => $NewLastPostID,
			'LastPostAuthorID' => $NewLastAuthorID,
			'LastPostTopicID' => $NewLastTopic,
			'LastPostTime' => $NewLastAddedTime,
			'Title' => $NewLastTitle,
			'IsLocked' => $NewLocked,
			'IsSticky' => $NewSticky,
			'Ranking' => $NewRanking
			);


		$Cache->update_row($OldForumID, $UpdateArray);

		// Forum we're moving to

		$DB->query("
			SELECT
				t.ID,
				t.LastPostID,
				t.Title,
				p.AuthorID,
				um.Username,
				p.AddedTime,
				(
					SELECT COUNT(pp.ID)
					FROM forums_posts AS pp
						JOIN forums_topics AS tt ON pp.TopicID = tt.ID
					WHERE tt.ForumID = '$ForumID'
				)
				FROM forums_topics AS t
					JOIN forums_posts AS p ON p.ID = t.LastPostID
					LEFT JOIN users_main AS um ON um.ID = p.AuthorID
				WHERE t.ForumID = '$ForumID'
				ORDER BY t.LastPostID DESC
				LIMIT 1");
		list($NewLastTopic, $NewLastPostID, $NewLastTitle, $NewLastAuthorID, $NewLastAuthorName, $NewLastAddedTime, $NumPosts) = $DB->next_record(MYSQLI_NUM, false);

		$DB->query("
			UPDATE forums
			SET
				NumTopics = NumTopics + 1,
				NumPosts = NumPosts + '$Posts',
				LastPostTopicID = '$NewLastTopic',
				LastPostID = '$NewLastPostID',
				LastPostAuthorID = '$NewLastAuthorID',
				LastPostTime = '$NewLastAddedTime'
			WHERE ID = '$ForumID'");


		$UpdateArray = array(
			'NumPosts' => ($NumPosts + $Posts),
			'NumTopics' => '+1',
			'LastPostID' => $NewLastPostID,
			'LastPostAuthorID' => $NewLastAuthorID,
			'LastPostTopicID' => $NewLastTopic,
			'LastPostTime' => $NewLastAddedTime,
			'Title' => $NewLastTitle
			);

		$Cache->update_row($ForumID, $UpdateArray);

		$Cache->commit_transaction(0);

		if ($ForumID == TRASH_FORUM_ID) {
			$Action = 'trashing';
		}
	} else { // Editing
		$DB->query("
			SELECT LastPostTopicID
			FROM forums
			WHERE ID = '$ForumID'");
		list($LastTopicID) = $DB->next_record();
		if ($LastTopicID == $TopicID) {
			$UpdateArray = array(
				'Title' => $RawTitle,
				'IsLocked' => $Locked,
				'IsSticky' => $Sticky,
				'Ranking' => $Ranking
			);
			$Cache->begin_transaction('forums_list');
			$Cache->update_row($ForumID, $UpdateArray);
			$Cache->commit_transaction(0);
		}
	}
	if ($Locked) {
		$CatalogueID = floor($NumPosts / THREAD_CATALOGUE);
		for ($i = 0; $i <= $CatalogueID; $i++) {
			$Cache->expire_value("thread_{$TopicID}_catalogue_$i", 3600 * 24 * 7); // 7 days
		}
		$Cache->expire_value("thread_{$TopicID}_info", 3600 * 24 * 7); // 7 days

		$DB->query("
			UPDATE forums_polls
			SET Closed = '0'
			WHERE TopicID = '$TopicID'");
		$Cache->delete_value("polls_$TopicID");
	}

	// topic notes and notifications
	$TopicNotes = [];
	switch ($Action) {
		case 'editing':
			if ($OldTitle != $RawTitle) {
				// title edited
				$TopicNotes[] = "Title edited from \"$OldTitle\" to \"$RawTitle\"";
			}
			if ($OldLocked != $Locked) {
				if (!$OldLocked) {
					$TopicNotes[] = 'Locked';
				} else {
					$TopicNotes[] = 'Unlocked';
				}
			}
			if ($OldSticky != $Sticky) {
				if (!$OldSticky) {
					$TopicNotes[] = 'Stickied';
				} else {
					$TopicNotes[] = 'Unstickied';
				}
			}
			if ($OldRanking != $Ranking) {
				$TopicNotes[] = "Ranking changed from \"$OldRanking\" to \"$Ranking\"";
			}
			if ($ForumID != $OldForumID) {
				$TopicNotes[] = "Moved from [url=" . site_url() . "forums.php?action=viewforum&forumid=$OldForumID]{$OldForumName}[/url] to [url=" . site_url() . "forums.php?action=viewforum&forumid=$ForumID]{$ForumName}[/url]";
			}
			break;
		case 'trashing':
			$TopicNotes[] = "Trashed (moved from [url=" . site_url() . "forums.php?action=viewforum&forumid=$OldForumID]{$OldForumName}[/url] to [url=" . site_url() . "forums.php?action=viewforum&forumid=$ForumID]{$ForumName}[/url])";
			$Notification = "Your thread \"$NewLastTitle\" has been trashed";
			break;
		default:
			break;
	}
	if (isset($Notification)) {
		NotificationsManager::notify_user($ThreadAuthorID, NotificationsManager::FORUMALERTS, $Notification, "forums.php?action=viewthread&threadid=$TopicID");
	}
	if (count($TopicNotes) > 0) {
		Forums::add_topic_note($TopicID, implode("\n", $TopicNotes));
	}
	header("Location: forums.php?action=viewthread&threadid=$TopicID&page=$Page");
}
