<?
authorize();
// Quick SQL injection check
if (!isset($_GET['postid']) || !is_number($_GET['postid'])) {
	error(0);
}
$PostID = $_GET['postid'];

// Make sure they are moderators
if (!check_perms('site_admin_forums')) {
	error(403);
}

// Get topic ID, forum ID, number of pages
$DB->query("
	SELECT
		TopicID,
		ForumID,
		CEIL(COUNT(p.ID) / ".POSTS_PER_PAGE.") AS Pages,
		CEIL(SUM(IF(p.ID <= '$PostID', 1, 0)) / ".POSTS_PER_PAGE.") AS Page,
		StickyPostID
	FROM forums_posts AS p
		JOIN forums_topics AS t ON t.ID = p.TopicID
	WHERE p.TopicID = (
			SELECT TopicID
			FROM forums_posts
			WHERE ID = '$PostID'
			)
	GROUP BY t.ID");
list($TopicID, $ForumID, $Pages, $Page, $StickyPostID) = $DB->next_record();
if (!$TopicID) {
	// Post is deleted or thread doesn't exist
	error(0); // This is evil, but the ajax call doesn't check the response
}

// $Pages = number of pages in the thread
// $Page = which page the post is on
// These are set for cache clearing.

$DB->query("
	DELETE FROM forums_posts
	WHERE ID = '$PostID'");

$DB->query("
	SELECT MAX(ID)
	FROM forums_posts
	WHERE TopicID = '$TopicID'");
list($LastID) = $DB->next_record();
$DB->query("
	UPDATE forums AS f, forums_topics AS t
	SET f.NumPosts = f.NumPosts - 1,
		t.NumPosts = t.NumPosts - 1
	WHERE f.ID = '$ForumID'
		AND t.ID = '$TopicID'");

if ($LastID < $PostID) { // Last post in a topic was removed
	$DB->query("
		SELECT p.AuthorID, u.Username, p.AddedTime
		FROM forums_posts AS p
			LEFT JOIN users_main AS u ON u.ID = p.AuthorID
		WHERE p.ID = '$LastID'");
	list($LastAuthorID, $LastAuthorName, $LastTime) = $DB->next_record();
	$DB->query("
		UPDATE forums_topics
		SET
			LastPostID = '$LastID',
			LastPostAuthorID = '$LastAuthorID',
			LastPostTime = '$LastTime'
		WHERE ID = '$TopicID'");
	$DB->query("
		SELECT
			t.ID,
			t.Title,
			t.LastPostID,
			t.LastPostTime,
			t.LastPostAuthorID,
			u.Username
		FROM forums_topics AS t
			LEFT JOIN users_main AS u ON u.ID = t.LastPostAuthorID
		WHERE ForumID = '$ForumID'
			AND t.ID != '$TopicID'
		ORDER BY LastPostID DESC
		LIMIT 1");
	list($LastTopicID, $LastTopicTitle, $LastTopicPostID, $LastTopicPostTime, $LastTopicAuthorID, $LastTopicAuthorName) = $DB->next_record(MYSQLI_BOTH, false);

	if ($LastID < $LastTopicPostID) { // Topic is no longer the most recent in its forum
		$DB->query("
			UPDATE forums
			SET
				LastPostTopicID = '$LastTopicID',
				LastPostID = '$LastTopicPostID',
				LastPostAuthorID = '$LastTopicAuthorID',
				LastPostTime = '$LastTopicPostTime'
			WHERE ID = '$ForumID'
				AND LastPostTopicID = '$TopicID'");
		$UpdateArrayForums = array(
			'NumPosts' => '-1',
			'LastPostID' => $LastTopicPostID,
			'LastPostAuthorID' => $LastTopicAuthorID,
			'LastPostTime' => $LastTopicPostTime,
			'LastPostTopicID' => $LastTopicID,
			'Title' => $LastTopicTitle);
	} else { // Topic is still the most recent in its forum
		$DB->query("
			UPDATE forums
			SET
				LastPostID = '$LastID',
				LastPostAuthorID = '$LastAuthorID',
				LastPostTime = '$LastTime'
			WHERE ID = '$ForumID'
				AND LastPostTopicID = '$TopicID'");
		$UpdateArrayForums = array(
			'NumPosts' => '-1',
			'LastPostID' => $LastID,
			'LastPostAuthorID' => $LastAuthorID,
			'LastPostTime' => $LastTime);
	}
	$UpdateArrayThread = array('Posts' => '-1', 'LastPostAuthorID' => $LastAuthorID);
} else {
	$UpdateArrayForums = array('NumPosts' => '-1');
	$UpdateArrayThread = array('Posts' => '-1');
}

if ($StickyPostID == $PostID) {
	$DB->query("
		UPDATE forums_topics
		SET StickyPostID = 0
		WHERE ID = $TopicID");
}

//We need to clear all subsequential catalogues as they've all been bumped with the absence of this post
$ThisCatalogue = floor((POSTS_PER_PAGE * $Page - POSTS_PER_PAGE) / THREAD_CATALOGUE);
$LastCatalogue = floor((POSTS_PER_PAGE * $Pages - POSTS_PER_PAGE) / THREAD_CATALOGUE);
for ($i = $ThisCatalogue; $i <= $LastCatalogue; $i++) {
	$Cache->delete_value("thread_$TopicID"."_catalogue_$i");
}

$Cache->begin_transaction("thread_$TopicID".'_info');
$Cache->update_row(false, $UpdateArrayThread);
$Cache->commit_transaction();

$Cache->begin_transaction('forums_list');
$Cache->update_row($ForumID, $UpdateArrayForums);
$Cache->commit_transaction();

$Cache->delete_value("forums_$ForumID");

Subscriptions::flush_subscriptions('forums', $TopicID);

// quote notifications
Subscriptions::flush_quote_notifications('forums', $TopicID);
$DB->query("
	DELETE FROM users_notify_quoted
	WHERE Page = 'forums'
		AND PostID = '$PostID'");
