<?

if (!isset($_POST['topicid']) || !is_number($_POST['topicid'])) {
	error(0, true);
}
$TopicID = $_POST['topicid'];

if (!empty($_POST['large'])) {
	$Size = 750;
} else {
	$Size = 140;
}

if (!$ThreadInfo = $Cache->get_value("thread_$TopicID".'_info')) {
	$DB->query("
		SELECT
			t.Title,
			t.ForumID,
			t.IsLocked,
			t.IsSticky,
			COUNT(fp.id) AS Posts,
			t.LastPostAuthorID,
			ISNULL(p.TopicID) AS NoPoll
		FROM forums_topics AS t
			JOIN forums_posts AS fp ON fp.TopicID = t.ID
			LEFT JOIN forums_polls AS p ON p.TopicID = t.ID
		WHERE t.ID = '$TopicID'
		GROUP BY fp.TopicID");
	if (!$DB->has_results()) {
		die();
	}
	$ThreadInfo = $DB->next_record(MYSQLI_ASSOC);
	if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
		$Cache->cache_value("thread_$TopicID".'_info', $ThreadInfo, 0);
	}
}
$ForumID = $ThreadInfo['ForumID'];

if (!list($Question, $Answers, $Votes, $Featured, $Closed) = $Cache->get_value("polls_$TopicID")) {
	$DB->query("
		SELECT
			Question,
			Answers,
			Featured,
			Closed
		FROM forums_polls
		WHERE TopicID = '$TopicID'");
	list($Question, $Answers, $Featured, $Closed) = $DB->next_record(MYSQLI_NUM, array(1));
	$Answers = unserialize($Answers);
	$DB->query("
		SELECT Vote, COUNT(UserID)
		FROM forums_polls_votes
		WHERE TopicID = '$TopicID'
			AND Vote != '0'
		GROUP BY Vote");
	$VoteArray = $DB->to_array(false, MYSQLI_NUM);

	$Votes = array();
	foreach ($VoteArray as $VoteSet) {
		list($Key,$Value) = $VoteSet;
		$Votes[$Key] = $Value;
	}

	for ($i = 1, $il = count($Answers); $i <= $il; ++$i) {
		if (!isset($Votes[$i])) {
			$Votes[$i] = 0;
		}
	}
	$Cache->cache_value("polls_$TopicID", array($Question, $Answers, $Votes, $Featured, $Closed), 0);
}


if ($Closed) {
	error(403,true);
}

if (!empty($Votes)) {
	$TotalVotes = array_sum($Votes);
	$MaxVotes = max($Votes);
} else {
	$TotalVotes = 0;
	$MaxVotes = 0;
}

if (!isset($_POST['vote']) || !is_number($_POST['vote'])) {
?>
<span class="error">Please select an option.</span><br />
<form class="vote_form" name="poll" id="poll" action="">
	<input type="hidden" name="action" value="poll" />
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<input type="hidden" name="large" value="<?=display_str($_POST['large'])?>" />
	<input type="hidden" name="topicid" value="<?=$TopicID?>" />
<?	for ($i = 1, $il = count($Answers); $i <= $il; $i++) { ?>
	<input type="radio" name="vote" id="answer_<?=$i?>" value="<?=$i?>" />
	<label for="answer_<?=$i?>"><?=display_str($Answers[$i])?></label><br />
<?	} ?>
	<br /><input type="radio" name="vote" id="answer_0" value="0" /> <label for="answer_0">Blank&#8202;&mdash;&#8202;Show the results!</label><br /><br />
	<input type="button" onclick="ajax.post('index.php', 'poll', function(response) { $('#poll_container').raw().innerHTML = response });" value="Vote" />
</form>
<?
} else {
	authorize();
	$Vote = $_POST['vote'];
	if (!isset($Answers[$Vote]) && $Vote != 0) {
		error(0,true);
	}

	//Add our vote
	$DB->query("
		INSERT IGNORE INTO forums_polls_votes
			(TopicID, UserID, Vote)
		VALUES
			($TopicID, " . $LoggedUser['ID'] . ", $Vote)");
	if ($DB->affected_rows() == 1 && $Vote != 0) {
		$Cache->begin_transaction("polls_$TopicID");
		$Cache->update_row(2, array($Vote => '+1'));
		$Cache->commit_transaction(0);
		$Votes[$Vote]++;
		$TotalVotes++;
		$MaxVotes++;
	}

	if ($Vote != 0) {
		$Answers[$Vote] = '=> '.$Answers[$Vote];
	}

?>
		<ul class="poll nobullet">
<?
		if ($ForumID != STAFF_FORUM) {
			for ($i = 1, $il = count($Answers); $i <= $il; $i++) {
				if (!empty($Votes[$i]) && $TotalVotes > 0) {
					$Ratio = $Votes[$i] / $MaxVotes;
					$Percent = $Votes[$i] / $TotalVotes;
				} else {
					$Ratio = 0;
					$Percent = 0;
				}
?>
					<li><?=display_str($Answers[$i])?> (<?=number_format($Percent * 100, 2)?>%)</li>
					<li class="graph">
						<span class="left_poll"></span>
						<span class="center_poll" style="width: <?=round($Ratio * $Size)?>px;"></span>
						<span class="right_poll"></span>
					</li>
<?
			}
		} else {
			//Staff forum, output voters, not percentages
			$DB->query("
				SELECT GROUP_CONCAT(um.Username SEPARATOR ', '),
					fpv.Vote
				FROM users_main AS um
					JOIN forums_polls_votes AS fpv ON um.ID = fpv.UserID
				WHERE TopicID = $TopicID
				GROUP BY fpv.Vote");

			$StaffVotes = $DB->to_array();
			foreach ($StaffVotes as $StaffVote) {
				list($StaffString, $StaffVoted) = $StaffVote;
?>
				<li><a href="forums.php?action=change_vote&amp;threadid=<?=$TopicID?>&amp;auth=<?=$LoggedUser['AuthKey']?>&amp;vote=<?=(int)$StaffVoted?>"><?=display_str(empty($Answers[$StaffVoted]) ? 'Blank' : $Answers[$StaffVoted])?></a> - <?=$StaffString?></li>
<?
			}
		}
?>
		</ul>
		<br /><strong>Votes:</strong> <?=number_format($TotalVotes)?>
<?
}
