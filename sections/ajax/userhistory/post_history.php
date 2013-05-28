<?php
/*
User post history page
*/

function error_out($reason = '') {
	$error = array('status' => 'failure');
	if ($reason != '')
		$error['reason'] = $reason;
	print $error;
	die();
}

if (!empty($LoggedUser['DisableForums'])) {
	error_out('You do not have access to the forums!');
}


include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class
$Text = new TEXT;


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

if ($LoggedUser['CustomForums']) {
	unset($LoggedUser['CustomForums']['']);
	$RestrictedForums = implode("','", array_keys($LoggedUser['CustomForums'], 0));
	$PermittedForums = implode("','", array_keys($LoggedUser['CustomForums'], 1));
}
$ViewingOwn = ($UserID == $LoggedUser['ID']);
$ShowUnread = ($ViewingOwn && (!isset($_GET['showunread']) || !!$_GET['showunread']));
$ShowGrouped = ($ViewingOwn && (!isset($_GET['group']) || !!$_GET['group']));
if ($ShowGrouped) {
	$sql = 'SELECT
				SQL_CALC_FOUND_ROWS
				MAX(p.ID) AS ID
		FROM forums_posts AS p
			LEFT JOIN forums_topics AS t ON t.ID = p.TopicID';
	if ($ShowUnread) {
		$sql.='
			LEFT JOIN forums_last_read_topics AS l ON l.TopicID = t.ID AND l.UserID = '.$LoggedUser['ID'];
	}
	$sql .= '
			LEFT JOIN forums AS f ON f.ID = t.ForumID
		WHERE p.AuthorID = '.$UserID.'
			AND ((f.MinClassRead <= '.$LoggedUser['Class'];
	if (!empty($RestrictedForums)) {
		$sql.='
			AND f.ID NOT IN (\''.$RestrictedForums.'\')';
	}
	$sql .= ')';
	if (!empty($PermittedForums)) {
		$sql.='
			OR f.ID IN (\''.$PermittedForums.'\')';
	}
	$sql .= ')';
	if ($ShowUnread) {
		$sql .= '
			AND ((t.IsLocked=\'0\' OR t.IsSticky=\'1\')
			AND (l.PostID<t.LastPostID OR l.PostID IS NULL))';
	}
	$sql .= '
		GROUP BY t.ID
		ORDER BY p.ID DESC LIMIT '.$Limit;
	$PostIDs = $DB->query($sql);
	$DB->query("SELECT FOUND_ROWS()");
	list($Results) = $DB->next_record();

	if ($Results > $PerPage * ($Page - 1)) {
		$DB->set_query_id($PostIDs);
		$PostIDs = $DB->collect('ID');
		$sql = 'SELECT
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
			FROM forums_posts as p
				LEFT JOIN users_main AS um ON um.ID = p.AuthorID
				LEFT JOIN users_info AS ui ON ui.UserID = p.AuthorID
				LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
				JOIN forums_topics AS t ON t.ID = p.TopicID
				JOIN forums AS f ON f.ID = t.ForumID
				LEFT JOIN forums_last_read_topics AS l ON l.UserID = '.$UserID.' AND l.TopicID = t.ID
			WHERE p.ID IN ('.implode(',',$PostIDs).')
			ORDER BY p.ID DESC';
		$Posts = $DB->query($sql);
	}
} else {
	$sql = 'SELECT
		SQL_CALC_FOUND_ROWS';
	if ($ShowGrouped) {
		$sql.=' * FROM (SELECT';
	}
	$sql .= '
		p.ID,
		p.AddedTime,
		p.Body,
		p.EditedUserID,
		p.EditedTime,
		ed.Username,
		p.TopicID,
		t.Title,
		t.LastPostID,';
	if ($UserID == $LoggedUser['ID']) {
		$sql .= '
		l.PostID AS LastRead,';
	}
	$sql .= '
		t.IsLocked,
		t.IsSticky
		FROM forums_posts as p
			LEFT JOIN users_main AS um ON um.ID = p.AuthorID
			LEFT JOIN users_info AS ui ON ui.UserID = p.AuthorID
			LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
			JOIN forums_topics AS t ON t.ID = p.TopicID
			JOIN forums AS f ON f.ID = t.ForumID
			LEFT JOIN forums_last_read_topics AS l ON l.UserID = '.$UserID.' AND l.TopicID = t.ID
		WHERE p.AuthorID = '.$UserID.'
			AND ((f.MinClassRead <= '.$LoggedUser['Class'];

	if (!empty($RestrictedForums)) {
		$sql.='
			AND f.ID NOT IN (\''.$RestrictedForums.'\')';
	}
	$sql .= ')';

	if (!empty($PermittedForums)) {
		$sql.='
			OR f.ID IN (\''.$PermittedForums.'\')';
	}
	$sql .= ')';

	if ($ShowUnread) {
		$sql.='
			AND ((t.IsLocked=\'0\' OR t.IsSticky=\'1\') AND (l.PostID<t.LastPostID OR l.PostID IS NULL)) ';
	}

	$sql .= '
		ORDER BY p.ID DESC';

	if ($ShowGrouped) {
		$sql.='
		) AS sub
		GROUP BY TopicID ORDER BY ID DESC';
	}

	$sql.=' LIMIT '.$Limit;
	$Posts = $DB->query($sql);

	$DB->query("SELECT FOUND_ROWS()");
	list($Results) = $DB->next_record();

	$DB->set_query_id($Posts);
}

$JsonResults = array();
while (list($PostID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername, $TopicID, $ThreadTitle, $LastPostID, $LastRead, $Locked, $Sticky) = $DB->next_record()) {
	$JsonResults[] = array(
		'postId' => (int) $PostID,
		'topicId' => (int) $TopicID,
		'threadTitle' => $ThreadTitle,
		'lastPostId' => (int) $LastPostID,
		'lastRead' => (int) $LastRead,
		'locked' => $Locked == 1,
		'sticky' => $Sticky == 1,
		'addedTime' => $AddedTime,
		'body' => $Text->full_format($Body),
		'bbbody' => $Body,
		'editedUserId' => (int) $EditedUserID,
		'editedTime' => $EditedTime,
		'editedUsername' => $EditedUsername
		);
}

print json_encode(
	array(
		'status' => 'success',
		'response' => array(
			'currentPage' => (int) $Page,
			'pages' => ceil($Results/$PerPage),
			'threads' => $JsonResults
			)
		)
	);
