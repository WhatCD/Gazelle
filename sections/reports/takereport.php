<?
authorize();

if (empty($_POST['id']) || !is_number($_POST['id']) || empty($_POST['type']) || ($_POST['type'] !== 'request_update' && empty($_POST['reason']))) {
	error(404);
}

include(SERVER_ROOT.'/sections/reports/array.php');

if (!array_key_exists($_POST['type'], $Types)) {
	error(403);
}
$Short = $_POST['type'];
$Type = $Types[$Short];
$ID = $_POST['id'];
if ($Short === 'request_update') {
	if (empty($_POST['year']) || !is_number($_POST['year'])) {
		error('Year must be specified.');
		header("Location: reports.php?action=report&type=request_update&id=$ID");
		die();
	}
	$Reason = '[b]Year[/b]: '.$_POST['year'].".\n\n";
	// If the release type is somehow invalid, return "Not given"; otherwise, return the release type.
	$Reason .= '[b]Release type[/b]: '.((empty($_POST['releasetype']) || !is_number($_POST['releasetype']) || $_POST['releasetype'] === '0') ? 'Not given' : $ReleaseTypes[$_POST['releasetype']]).". \n\n";
	$Reason .= '[b]Additional comments[/b]: '.$_POST['comment'];
} else {
	$Reason = $_POST['reason'];
}

switch ($Short) {
	case 'request':
	case 'request_update':
		$Link = "requests.php?action=view&id=$ID";
		break;
	case 'user':
		$Link = "user.php?id=$ID";
		break;
	case 'collage':
		$Link = "collages.php?id=$ID";
		break;
	case 'thread':
		$Link = "forums.php?action=viewthread&threadid=$ID";
		break;
	case 'post':
		$DB->query("
			SELECT
				p.ID,
				p.TopicID,
				(
					SELECT COUNT(p2.ID)
					FROM forums_posts AS p2
					WHERE p2.TopicID = p.TopicID
						AND p2.ID <= p.ID
				) AS PostNum
			FROM forums_posts AS p
			WHERE p.ID = $ID");
		list($PostID, $TopicID, $PostNum) = $DB->next_record();
		$Link = "forums.php?action=viewthread&threadid=$TopicID&post=$PostNum#post$PostID";
		break;
	case 'comment':
		$Link = "comments.php?action=jump&postid=$ID";
		break;
}

$DB->query('
	INSERT INTO reports
		(UserID, ThingID, Type, ReportedTime, Reason)
	VALUES
		('.db_string($LoggedUser['ID']).", $ID, '$Short', '".sqltime()."', '".db_string($Reason)."')");
$ReportID = $DB->inserted_id();

$Channels = array();

if ($Short === 'request_update') {
	$Channels[] = '#requestedits';
	$Cache->increment('num_update_reports');
}
if (in_array($Short, array('comment', 'post', 'thread'))) {
	$Channels[] = '#forumreports';
}


foreach ($Channels as $Channel) {
	send_irc("PRIVMSG $Channel :$ReportID - ".$LoggedUser['Username']." just reported a $Short: ".site_url()."$Link : ".strtr($Reason, "\n", ' '));
}

$Cache->delete_value('num_other_reports');

header("Location: $Link");
?>
