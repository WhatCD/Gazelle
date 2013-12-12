<?php
/*
User post history page
*/

function error_out($reason = '') {
	$error = array('status' => 'failure');
	if ($reason !== '')
		$error['reason'] = $reason;
	print $error;
	die();
}

if (!empty($LoggedUser['DisableForums'])) {
	error_out('You do not have access to the forums!');
}

$UserID = empty($_GET['userid']) ? $LoggedUser['ID'] : $_GET['userid'];
if (!is_number($UserID)) {
	error_out('User does not exist!');
}

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

list($Page, $Limit) = Format::page_limit($PerPage);

$UserInfo = Users::user_info($UserID);
extract(array_intersect_key($UserInfo, array_flip(array('Username', 'Enabled', 'Title', 'Avatar', 'Donor', 'Warned'))));

$ViewingOwn = ($UserID === $LoggedUser['ID']);
$ShowUnread = ($ViewingOwn && (!isset($_GET['showunread']) || !!$_GET['showunread']));
$ShowGrouped = ($ViewingOwn && (!isset($_GET['group']) || !!$_GET['group']));
if ($ShowGrouped) {
	$SQL = '
		SELECT
			SQL_CALC_FOUND_ROWS
			MAX(p.ID) AS ID
		FROM forums_posts AS p
			LEFT JOIN forums_topics AS t ON t.ID = p.TopicID';
	if ($ShowUnread) {
		$SQL .= '
			LEFT JOIN forums_last_read_topics AS l ON l.TopicID = t.ID AND l.UserID = '.$LoggedUser['ID'];
	}
	$SQL .= '
			LEFT JOIN forums AS f ON f.ID = t.ForumID
		WHERE p.AuthorID = '.$UserID.'
			AND ' . Forums::user_forums_sql();
	if ($ShowUnread) {
		$SQL .= '
			AND ((t.IsLocked = \'0\' OR t.IsSticky = \'1\')
			AND (l.PostID < t.LastPostID OR l.PostID IS NULL))';
	}
	$SQL .= "
		GROUP BY t.ID
		ORDER BY p.ID DESC
		LIMIT $Limit";
	$PostIDs = $DB->query($SQL);
	$DB->query('SELECT FOUND_ROWS()');
	list($Results) = $DB->next_record();

	if ($Results > $PerPage * ($Page - 1)) {
		$DB->set_query_id($PostIDs);
		$PostIDs = $DB->collect('ID');
		$SQL = "
			SELECT
				p.ID,
				p.AddedTime,
				p.Body,
				p.EditedUserID,
				p.EditedTime,
				ed.Username,
				p.TopicID,
				t.Title,
				t.LastPostID,
				l.PostID AS LastRead,
				t.IsLocked,
				t.IsSticky
			FROM forums_posts AS p
				LEFT JOIN users_main AS um ON um.ID = p.AuthorID
				LEFT JOIN users_info AS ui ON ui.UserID = p.AuthorID
				LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
				JOIN forums_topics AS t ON t.ID = p.TopicID
				JOIN forums AS f ON f.ID = t.ForumID
				LEFT JOIN forums_last_read_topics AS l ON l.UserID = $UserID AND l.TopicID = t.ID
			WHERE p.ID IN (".implode(',', $PostIDs).')
			ORDER BY p.ID DESC';
		$Posts = $DB->query($SQL);
	}
} else {
	$SQL = '
		SELECT
			SQL_CALC_FOUND_ROWS';
	if ($ShowGrouped) {
		$SQL .= '
			*
		FROM (
			SELECT';
	}
	$SQL .= '
				p.ID,
				p.AddedTime,
				p.Body,
				p.EditedUserID,
				p.EditedTime,
				ed.Username,
				p.TopicID,
				t.Title,
				t.LastPostID,';
	if ($UserID === $LoggedUser['ID']) {
		$SQL .= '
				l.PostID AS LastRead,';
	}
	$SQL .= "
				t.IsLocked,
				t.IsSticky
			FROM forums_posts AS p
				LEFT JOIN users_main AS um ON um.ID = p.AuthorID
				LEFT JOIN users_info AS ui ON ui.UserID = p.AuthorID
				LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
				JOIN forums_topics AS t ON t.ID = p.TopicID
				JOIN forums AS f ON f.ID = t.ForumID
				LEFT JOIN forums_last_read_topics AS l ON l.UserID = $UserID AND l.TopicID = t.ID
			WHERE p.AuthorID = $UserID
				AND " . Forums::user_forums_sql();

	if ($ShowUnread) {
		$SQL .= '
				AND ((t.IsLocked = \'0\' OR t.IsSticky = \'1\')
					AND (l.PostID < t.LastPostID OR l.PostID IS NULL)
				) ';
	}

	$SQL .= '
			ORDER BY p.ID DESC';

	if ($ShowGrouped) {
		$SQL .= '
		) AS sub
		GROUP BY TopicID
		ORDER BY ID DESC';
	}

	$SQL .= "
		LIMIT $Limit";
	$Posts = $DB->query($SQL);

	$DB->query('SELECT FOUND_ROWS()');
	list($Results) = $DB->next_record();

	$DB->set_query_id($Posts);
}

$JsonResults = array();
while (list($PostID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername, $TopicID, $ThreadTitle, $LastPostID, $LastRead, $Locked, $Sticky) = $DB->next_record()) {
	$JsonResults[] = array(
		'postId' => (int)$PostID,
		'topicId' => (int)$TopicID,
		'threadTitle' => $ThreadTitle,
		'lastPostId' => (int)$LastPostID,
		'lastRead' => (int)$LastRead,
		'locked' => $Locked === '1',
		'sticky' => $Sticky === '1',
		'addedTime' => $AddedTime,
		'body' => Text::full_format($Body),
		'bbbody' => $Body,
		'editedUserId' => (int)$EditedUserID,
		'editedTime' => $EditedTime,
		'editedUsername' => $EditedUsername
		);
}

print json_encode(
	array(
		'status' => 'success',
		'response' => array(
			'currentPage' => (int)$Page,
			'pages' => ceil($Results / $PerPage),
			'threads' => $JsonResults
			)
		)
	);
