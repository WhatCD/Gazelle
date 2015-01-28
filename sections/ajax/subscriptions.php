<?php
/*
User topic subscription page
*/

if (!empty($LoggedUser['DisableForums'])) {
	json_die('failure');
}

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}
list($Page, $Limit) = Format::page_limit($PerPage);

$ShowUnread = (!isset($_GET['showunread']) && !isset($HeavyInfo['SubscriptionsUnread']) || isset($HeavyInfo['SubscriptionsUnread']) && !!$HeavyInfo['SubscriptionsUnread'] || isset($_GET['showunread']) && !!$_GET['showunread']);
$ShowCollapsed = (!isset($_GET['collapse']) && !isset($HeavyInfo['SubscriptionsCollapse']) || isset($HeavyInfo['SubscriptionsCollapse']) && !!$HeavyInfo['SubscriptionsCollapse'] || isset($_GET['collapse']) && !!$_GET['collapse']);
$sql = '
	SELECT
		SQL_CALC_FOUND_ROWS
		MAX(p.ID) AS ID
	FROM forums_posts AS p
		LEFT JOIN forums_topics AS t ON t.ID = p.TopicID
		JOIN users_subscriptions AS s ON s.TopicID = t.ID
		LEFT JOIN forums AS f ON f.ID = t.ForumID
		LEFT JOIN forums_last_read_topics AS l ON p.TopicID = l.TopicID AND l.UserID = s.UserID
	WHERE s.UserID = '.$LoggedUser['ID'].'
		AND p.ID <= IFNULL(l.PostID, t.LastPostID)
		AND ' . Forums::user_forums_sql();
if ($ShowUnread) {
	$sql .= '
		AND IF(l.PostID IS NULL OR (t.IsLocked = \'1\' && t.IsSticky = \'0\'), t.LastPostID, l.PostID) < t.LastPostID';
}
$sql .= "
	GROUP BY t.ID
	ORDER BY t.LastPostID DESC
	LIMIT $Limit";
$PostIDs = $DB->query($sql);
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();

if ($NumResults > $PerPage * ($Page - 1)) {
	$DB->set_query_id($PostIDs);
	$PostIDs = $DB->collect('ID');
	$sql = '
		SELECT
			f.ID AS ForumID,
			f.Name AS ForumName,
			p.TopicID,
			t.Title,
			p.Body,
			t.LastPostID,
			t.IsLocked,
			t.IsSticky,
			p.ID,
			um.ID,
			um.Username,
			ui.Avatar,
			p.EditedUserID,
			p.EditedTime,
			ed.Username AS EditedUsername
		FROM forums_posts AS p
			LEFT JOIN forums_topics AS t ON t.ID = p.TopicID
			LEFT JOIN forums AS f ON f.ID = t.ForumID
			LEFT JOIN users_main AS um ON um.ID = p.AuthorID
			LEFT JOIN users_info AS ui ON ui.UserID = um.ID
			LEFT JOIN users_main AS ed ON ed.ID = um.ID
		WHERE p.ID IN ('.implode(',', $PostIDs).')
		ORDER BY f.Name ASC, t.LastPostID DESC';
	$DB->query($sql);
}

$JsonPosts = array();
while (list($ForumID, $ForumName, $TopicID, $ThreadTitle, $Body, $LastPostID, $Locked, $Sticky, $PostID, $AuthorID, $AuthorName, $AuthorAvatar, $EditedUserID, $EditedTime, $EditedUsername) = $DB->next_record()) {
	$JsonPost = array(
		'forumId' => (int)$ForumID,
		'forumName' => $ForumName,
		'threadId' => (int)$TopicID,
		'threadTitle' => $ThreadTitle,
		'postId' => (int)$PostID,
		'lastPostId' => (int)$LastPostID,
		'locked' => $Locked == 1,
		'new' => ($PostID < $LastPostID && !$Locked)
	);
	$JsonPosts[] = $JsonPost;
}

json_print('success', array(
	'threads' => $JsonPosts
));
?>
