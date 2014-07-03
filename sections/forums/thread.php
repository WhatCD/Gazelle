<?php
//TODO: Normalize thread_*_info don't need to waste all that ram on things that are already in other caches
/**********|| Page to show individual threads || ********************************\

Things to expect in $_GET:
	ThreadID: ID of the forum curently being browsed
	page:	The page the user's on.
	page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

// Enable TOC
Text::$TOC = true;

// Check for lame SQL injection attempts
if (!isset($_GET['threadid']) || !is_number($_GET['threadid'])) {
	if (isset($_GET['topicid']) && is_number($_GET['topicid'])) {
		$ThreadID = $_GET['topicid'];
	} elseif (isset($_GET['postid']) && is_number($_GET['postid'])) {
		$DB->query("
			SELECT TopicID
			FROM forums_posts
			WHERE ID = $_GET[postid]");
		list($ThreadID) = $DB->next_record();
		if ($ThreadID) {
			header("Location: forums.php?action=viewthread&threadid=$ThreadID&postid=$_GET[postid]#post$_GET[postid]");
			die();
		} else {
			error(404);
		}
	} else {
		error(404);
	}
} else {
	$ThreadID = $_GET['threadid'];
}

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

//---------- Get some data to start processing

// Thread information, constant across all pages
$ThreadInfo = Forums::get_thread_info($ThreadID, true, true);
if ($ThreadInfo === null) {
	error(404);
}
$ForumID = $ThreadInfo['ForumID'];

$IsDonorForum = $ForumID == DONOR_FORUM ? true : false;

// Make sure they're allowed to look at the page
if (!Forums::check_forumperm($ForumID)) {
	error(403);
}
//Escape strings for later display
$ThreadTitle = display_str($ThreadInfo['Title']);
$ForumName = display_str($Forums[$ForumID]['Name']);

//Post links utilize the catalogue & key params to prevent issues with custom posts per page
if ($ThreadInfo['Posts'] > $PerPage) {
	if (isset($_GET['post']) && is_number($_GET['post'])) {
		$PostNum = $_GET['post'];
	} elseif (isset($_GET['postid']) && is_number($_GET['postid']) && $_GET['postid'] != $ThreadInfo['StickyPostID']) {
		$SQL = "
			SELECT COUNT(ID)
			FROM forums_posts
			WHERE TopicID = $ThreadID
				AND ID <= $_GET[postid]";
		if ($ThreadInfo['StickyPostID'] < $_GET['postid']) {
			$SQL .= " AND ID != $ThreadInfo[StickyPostID]";
		}
		$DB->query($SQL);
		list($PostNum) = $DB->next_record();
	} else {
		$PostNum = 1;
	}
} else {
	$PostNum = 1;
}
list($Page, $Limit) = Format::page_limit($PerPage, min($ThreadInfo['Posts'],$PostNum));
if (($Page - 1) * $PerPage > $ThreadInfo['Posts']) {
	$Page = ceil($ThreadInfo['Posts'] / $PerPage);
}
list($CatalogueID,$CatalogueLimit) = Format::catalogue_limit($Page, $PerPage, THREAD_CATALOGUE);

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
if (!$Catalogue = $Cache->get_value("thread_{$ThreadID}_catalogue_$CatalogueID")) {
	$DB->query("
		SELECT
			p.ID,
			p.AuthorID,
			p.AddedTime,
			p.Body,
			p.EditedUserID,
			p.EditedTime,
			ed.Username
		FROM forums_posts AS p
			LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
		WHERE p.TopicID = '$ThreadID'
			AND p.ID != '".$ThreadInfo['StickyPostID']."'
		LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array(false, MYSQLI_ASSOC);
	if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
		$Cache->cache_value("thread_{$ThreadID}_catalogue_$CatalogueID", $Catalogue, 0);
	}
}
$Thread = Format::catalogue_select($Catalogue, $Page, $PerPage, THREAD_CATALOGUE);
$LastPost = end($Thread);
$LastPost = $LastPost['ID'];
$FirstPost = reset($Thread);
$FirstPost = $FirstPost['ID'];
if ($ThreadInfo['Posts'] <= $PerPage*$Page && $ThreadInfo['StickyPostID'] > $LastPost) {
	$LastPost = $ThreadInfo['StickyPostID'];
}

//Handle last read

if (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {

	$DB->query("
		SELECT PostID
		FROM forums_last_read_topics
		WHERE UserID = '$LoggedUser[ID]'
			AND TopicID = '$ThreadID'");
	list($LastRead) = $DB->next_record();
	if ($LastRead < $LastPost) {
		$DB->query("
			INSERT INTO forums_last_read_topics
				(UserID, TopicID, PostID)
			VALUES
				('$LoggedUser[ID]', '$ThreadID', '".db_string($LastPost)."')
			ON DUPLICATE KEY UPDATE
				PostID = '$LastPost'");
	}
}

//Handle subscriptions
$UserSubscriptions = Subscriptions::get_subscriptions();

if (empty($UserSubscriptions)) {
	$UserSubscriptions = array();
}

if (in_array($ThreadID, $UserSubscriptions)) {
	$Cache->delete_value('subscriptions_user_new_'.$LoggedUser['ID']);
}


$QuoteNotificationsCount = $Cache->get_value('notify_quoted_' . $LoggedUser['ID']);
if ($QuoteNotificationsCount === false || $QuoteNotificationsCount > 0) {
	$DB->query("
		UPDATE users_notify_quoted
		SET UnRead = false
		WHERE UserID = '$LoggedUser[ID]'
			AND Page = 'forums'
			AND PageID = '$ThreadID'
			AND PostID >= '$FirstPost'
			AND PostID <= '$LastPost'");
	$Cache->delete_value('notify_quoted_' . $LoggedUser['ID']);
}

// Start printing
View::show_header($ThreadInfo['Title'] . ' &lt; '.$Forums[$ForumID]['Name'].' &lt; Forums','comments,subscriptions,bbcode', $IsDonorForum ? 'donor' : '');
?>
<div class="thin">
	<h2>
		<a href="forums.php">Forums</a> &gt;
		<a href="forums.php?action=viewforum&amp;forumid=<?=$ThreadInfo['ForumID']?>"><?=$ForumName?></a> &gt;
		<?=$ThreadTitle?>
	</h2>
	<div class="linkbox">
		<div class="center">
			<a href="reports.php?action=report&amp;type=thread&amp;id=<?=$ThreadID?>" class="brackets">Report thread</a>
			<a href="#" onclick="Subscribe(<?=$ThreadID?>);return false;" id="subscribelink<?=$ThreadID?>" class="brackets"><?=(in_array($ThreadID, $UserSubscriptions) ? 'Unsubscribe' : 'Subscribe')?></a>
			<a href="#" onclick="$('#searchthread').gtoggle(); this.innerHTML = (this.innerHTML == 'Search this thread' ? 'Hide search' : 'Search this thread'); return false;" class="brackets">Search this thread</a>
		</div>
		<div id="searchthread" class="hidden center">
			<div style="display: inline-block;">
				<h3>Search this thread:</h3>
				<form class="search_form" name="forum_thread" action="forums.php" method="get">
					<input type="hidden" name="action" value="search" />
					<input type="hidden" name="threadid" value="<?=$ThreadID?>" />
					<table cellpadding="6" cellspacing="1" border="0" class="layout border">
						<tr>
							<td><strong>Search for:</strong></td>
							<td><input type="search" id="searchbox" name="search" size="70" /></td>
						</tr>
						<tr>
							<td><strong>Posted by:</strong></td>
							<td><input type="search" id="username" name="user" placeholder="Username" size="70" /></td>
						</tr>
						<tr>
							<td colspan="2" style="text-align: center;">
								<input type="submit" name="submit" value="Search" />
							</td>
						</tr>
					</table>
				</form>
				<br />
			</div>
		</div>
<?
$Pages = Format::get_pages($Page, $ThreadInfo['Posts'], $PerPage, 9);
echo $Pages;
?>
	</div>
<?
if ($ThreadInfo['NoPoll'] == 0) {
	if (!list($Question, $Answers, $Votes, $Featured, $Closed) = $Cache->get_value("polls_$ThreadID")) {
		$DB->query("
			SELECT Question, Answers, Featured, Closed
			FROM forums_polls
			WHERE TopicID = '$ThreadID'");
		list($Question, $Answers, $Featured, $Closed) = $DB->next_record(MYSQLI_NUM, array(1));
		$Answers = unserialize($Answers);
		$DB->query("
			SELECT Vote, COUNT(UserID)
			FROM forums_polls_votes
			WHERE TopicID = '$ThreadID'
			GROUP BY Vote");
		$VoteArray = $DB->to_array(false, MYSQLI_NUM);

		$Votes = array();
		foreach ($VoteArray as $VoteSet) {
			list($Key,$Value) = $VoteSet;
			$Votes[$Key] = $Value;
		}

		foreach (array_keys($Answers) as $i) {
			if (!isset($Votes[$i])) {
				$Votes[$i] = 0;
			}
		}
		$Cache->cache_value("polls_$ThreadID", array($Question, $Answers, $Votes, $Featured, $Closed), 0);
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
	$DB->query("
		SELECT Vote
		FROM forums_polls_votes
		WHERE UserID = '".$LoggedUser['ID']."'
			AND TopicID = '$ThreadID'");
	list($UserResponse) = $DB->next_record();
	if (!empty($UserResponse) && $UserResponse != 0) {
		$Answers[$UserResponse] = '&raquo; '.$Answers[$UserResponse];
	} else {
		if (!empty($UserResponse) && $RevealVoters) {
			$Answers[$UserResponse] = '&raquo; '.$Answers[$UserResponse];
		}
	}

?>
	<div class="box thin clear">
		<div class="head colhead_dark"><strong>Poll<? if ($Closed) { echo ' [Closed]'; } ?><? if ($Featured && $Featured !== '0000-00-00 00:00:00') { echo ' [Featured]'; } ?></strong> <a href="#" onclick="$('#threadpoll').gtoggle(); log_hit(); return false;" class="brackets">View</a></div>
		<div class="pad<? if (/*$LastRead !== null || */$ThreadInfo['IsLocked']) { echo ' hidden'; } ?>" id="threadpoll">
			<p><strong><?=display_str($Question)?></strong></p>
<?	if ($UserResponse !== null || $Closed || $ThreadInfo['IsLocked'] || !Forums::check_forumperm($ForumID)) { ?>
			<ul class="poll nobullet">
<?
		if (!$RevealVoters) {
			foreach ($Answers as $i => $Answer) {
				if (!empty($Votes[$i]) && $TotalVotes > 0) {
					$Ratio = $Votes[$i] / $MaxVotes;
					$Percent = $Votes[$i] / $TotalVotes;
				} else {
					$Ratio = 0;
					$Percent = 0;
				}
?>
					<li><?=display_str($Answer)?> (<?=number_format($Percent * 100, 2)?>%)</li>
					<li class="graph">
						<span class="left_poll"></span>
						<span class="center_poll" style="width: <?=round($Ratio * 750)?>px;"></span>
						<span class="right_poll"></span>
					</li>
<?			}
			if ($Votes[0] > 0) {
?>
				<li><?=($UserResponse == '0' ? '&raquo; ' : '')?>(Blank) (<?=number_format((float)($Votes[0] / $TotalVotes * 100), 2)?>%)</li>
				<li class="graph">
					<span class="left_poll"></span>
					<span class="center_poll" style="width: <?=round(($Votes[0] / $MaxVotes) * 750)?>px;"></span>
					<span class="right_poll"></span>
				</li>
<?			} ?>
			</ul>
			<br />
			<strong>Votes:</strong> <?=number_format($TotalVotes)?><br /><br />
<?
		} else {
			//Staff forum, output voters, not percentages
			include(SERVER_ROOT.'/sections/staff/functions.php');
			$Staff = get_staff();

			$StaffNames = array();
			foreach ($Staff as $Staffer) {
				$StaffNames[] = $Staffer['Username'];
			}

			$DB->query("
				SELECT
					fpv.Vote AS Vote,
					GROUP_CONCAT(um.Username SEPARATOR ', ')
				FROM users_main AS um
					LEFT JOIN forums_polls_votes AS fpv ON um.ID = fpv.UserID
				WHERE TopicID = $ThreadID
				GROUP BY fpv.Vote");

			$StaffVotesTmp = $DB->to_array();
			$StaffCount = count($StaffNames);

			$StaffVotes = array();
			foreach ($StaffVotesTmp as $StaffVote) {
				list($Vote, $Names) = $StaffVote;
				$StaffVotes[$Vote] = $Names;
				$Names = explode(', ', $Names);
				$StaffNames = array_diff($StaffNames, $Names);
			}
?>			<ul style="list-style: none;" id="poll_options">
<?
			foreach ($Answers as $i => $Answer) {
?>
				<li>
					<a href="forums.php?action=change_vote&amp;threadid=<?=$ThreadID?>&amp;auth=<?=$LoggedUser['AuthKey']?>&amp;vote=<?=(int)$i?>"><?=display_str($Answer == '' ? 'Blank' : $Answer)?></a>
					 - <?=$StaffVotes[$i]?>&nbsp;(<?=number_format(((float)$Votes[$i] / $TotalVotes) * 100, 2)?>%)
					<a href="forums.php?action=delete_poll_option&amp;threadid=<?=$ThreadID?>&amp;auth=<?=$LoggedUser['AuthKey']?>&amp;vote=<?=(int)$i?>" class="brackets tooltip" title="Delete poll option">X</a>
				</li>
<?			} ?>
				<li>
					<a href="forums.php?action=change_vote&amp;threadid=<?=$ThreadID?>&amp;auth=<?=$LoggedUser['AuthKey']?>&amp;vote=0"><?=($UserResponse == '0' ? '&raquo; ' : '')?>Blank</a> - <?=$StaffVotes[0]?>&nbsp;(<?=number_format(((float)$Votes[0] / $TotalVotes) * 100, 2)?>%)
				</li>
			</ul>
<?
			if ($ForumID == STAFF_FORUM) {
?>
			<br />
			<strong>Votes:</strong> <?=number_format($StaffCount - count($StaffNames))?> / <?=$StaffCount?> current staff, <?=number_format($TotalVotes)?> total
			<br />
			<strong>Missing votes:</strong> <?=implode(", ", $StaffNames); echo "\n";?>
			<br /><br />
<?
			}
?>
			<a href="#" onclick="AddPollOption(<?=$ThreadID?>); return false;" class="brackets">+</a>
<?
		}

	} else {
	//User has not voted
?>
			<div id="poll_container">
				<form class="vote_form" name="poll" id="poll" action="">
					<input type="hidden" name="action" value="poll" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="large" value="1" />
					<input type="hidden" name="topicid" value="<?=$ThreadID?>" />
					<ul style="list-style: none;" id="poll_options">
<?		foreach ($Answers as $i => $Answer) { //for ($i = 1, $il = count($Answers); $i <= $il; $i++) { ?>
						<li>
							<input type="radio" name="vote" id="answer_<?=$i?>" value="<?=$i?>" />
							<label for="answer_<?=$i?>"><?=display_str($Answer)?></label>
						</li>
<?		} ?>
						<li>
							<br />
							<input type="radio" name="vote" id="answer_0" value="0" /> <label for="answer_0">Blank&#8202;&mdash;&#8202;Show the results!</label><br />
						</li>
					</ul>
<?		if ($ForumID == STAFF_FORUM) { ?>
					<a href="#" onclick="AddPollOption(<?=$ThreadID?>); return false;" class="brackets">+</a>
					<br />
					<br />
<?		} ?>
					<input type="button" style="float: left;" onclick="ajax.post('index.php','poll',function(response) { $('#poll_container').raw().innerHTML = response});" value="Vote" />
				</form>
			</div>
<?	}
	if (check_perms('forums_polls_moderate') && !$RevealVoters) {
		if (!$Featured || $Featured == '0000-00-00 00:00:00') {
?>
			<form class="manage_form" name="poll" action="forums.php" method="post">
				<input type="hidden" name="action" value="poll_mod" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="topicid" value="<?=$ThreadID?>" />
				<input type="hidden" name="feature" value="1" />
				<input type="submit" style="float: left;" onclick="return confirm('Are you sure you want to feature this poll?');" value="Feature" />
			</form>
<?		} ?>
			<form class="manage_form" name="poll" action="forums.php" method="post">
				<input type="hidden" name="action" value="poll_mod" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="topicid" value="<?=$ThreadID?>" />
				<input type="hidden" name="close" value="1" />
				<input type="submit" style="float: left;" value="<?=(!$Closed ? 'Close' : 'Open')?>" />
			</form>
<?	} ?>
		</div>
	</div>
<?
} //End Polls

//Sqeeze in stickypost
if ($ThreadInfo['StickyPostID']) {
	if ($ThreadInfo['StickyPostID'] != $Thread[0]['ID']) {
		array_unshift($Thread, $ThreadInfo['StickyPost']);
	}
	if ($ThreadInfo['StickyPostID'] != $Thread[count($Thread) - 1]['ID']) {
		$Thread[] = $ThreadInfo['StickyPost'];
	}
}

foreach ($Thread as $Key => $Post) {
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(Users::user_info($AuthorID));

?>
<table class="forum_post wrap_overflow box vertical_margin<?
	if (((!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky'])
			&& $PostID > $LastRead
			&& strtotime($AddedTime) > $LoggedUser['CatchupTime']
			) || (isset($RequestKey) && $Key == $RequestKey)
		) {
		echo ' forum_unread';
	}
	if (!Users::has_avatars_enabled()) {
		echo ' noavatar';
	}
	if ($ThreadInfo['OP'] == $AuthorID) {
		echo ' important_user';
	}
	if ($PostID == $ThreadInfo['StickyPostID']) {
		echo ' sticky_post';
	} ?>" id="post<?=$PostID?>">
	<colgroup>
<?	if (Users::has_avatars_enabled()) { ?>
		<col class="col_avatar" />
<? 	} ?>
		<col class="col_post_body" />
	</colgroup>
	<tr class="colhead_dark">
		<td colspan="<?=Users::has_avatars_enabled() ? 2 : 1?>">
			<div style="float: left;"><a class="post_id" href="forums.php?action=viewthread&amp;threadid=<?=$ThreadID?>&amp;postid=<?=$PostID?>#post<?=$PostID?>">#<?=$PostID?></a>
				<?=Users::format_username($AuthorID, true, true, true, true, true, $IsDonorForum); echo "\n";?>
				<?=time_diff($AddedTime, 2); echo "\n";?>
				- <a href="#quickpost" id="quote_<?=$PostID?>" onclick="Quote('<?=$PostID?>', '<?=$Username?>', true);" class="brackets">Quote</a>
<?	if ((!$ThreadInfo['IsLocked'] && Forums::check_forumperm($ForumID, 'Write') && $AuthorID == $LoggedUser['ID']) || check_perms('site_moderate_forums')) { ?>
				- <a href="#post<?=$PostID?>" onclick="Edit_Form('<?=$PostID?>', '<?=$Key?>');" class="brackets">Edit</a>
<?
	}
	if (check_perms('site_admin_forums') && $ThreadInfo['Posts'] > 1) { ?>
				- <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');" class="brackets">Delete</a>
<?
	}
	if ($PostID == $ThreadInfo['StickyPostID']) { ?>
				<strong><span class="sticky_post_label" class="brackets">Sticky</span></strong>
<?		if (check_perms('site_moderate_forums')) { ?>
				- <a href="forums.php?action=sticky_post&amp;threadid=<?=$ThreadID?>&amp;postid=<?=$PostID?>&amp;remove=true&amp;auth=<?=$LoggedUser['AuthKey']?>" title="Unsticky this post" class="brackets tooltip">X</a>
<?
		}
	} else {
		if (check_perms('site_moderate_forums')) {
?>
				- <a href="forums.php?action=sticky_post&amp;threadid=<?=$ThreadID?>&amp;postid=<?=$PostID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" title="Sticky this post" class="brackets tooltip">&#x21d5;</a>
<?
		}
	}
?>
			</div>
			<div id="bar<?=$PostID?>" style="float: right;">
				<a href="reports.php?action=report&amp;type=post&amp;id=<?=$PostID?>" class="brackets">Report</a>
<?
	if (check_perms('users_warn') && $AuthorID != $LoggedUser['ID']) {
		$AuthorInfo = Users::user_info($AuthorID);
		if ($LoggedUser['Class'] >= $AuthorInfo['Class']) {
?>
				<form class="manage_form hidden" name="user" id="warn<?=$PostID?>" action="" method="post">
					<input type="hidden" name="action" value="warn" />
					<input type="hidden" name="postid" value="<?=$PostID?>" />
					<input type="hidden" name="userid" value="<?=$AuthorID?>" />
					<input type="hidden" name="key" value="<?=$Key?>" />
				</form>
				- <a href="#" onclick="$('#warn<?=$PostID?>').raw().submit(); return false;" class="brackets">Warn</a>
<?		}
	}
?>
				&nbsp;
				<a href="#">&uarr;</a>
			</div>
		</td>
	</tr>
	<tr>
<?	if (Users::has_avatars_enabled()) { ?>
		<td class="avatar" valign="top">
		<?=Users::show_avatar($Avatar, $AuthorID, $Username, $HeavyInfo['DisableAvatars'], 150, true)?>
		</td>
<?	} ?>
		<td class="body" valign="top"<? if (!Users::has_avatars_enabled()) { echo ' colspan="2"'; } ?>>
			<div id="content<?=$PostID?>">
				<?=Text::full_format($Body) ?>
<?	if ($EditedUserID) { ?>
				<br />
				<br />
<?		if (check_perms('site_admin_forums')) { ?>
				<a href="#content<?=$PostID?>" onclick="LoadEdit('forums', <?=$PostID?>, 1); return false;">&laquo;</a>
<?		} ?>
				Last edited by
				<?=Users::format_username($EditedUserID, false, false, false, false, false, $IsDonorForum) ?> <?=time_diff($EditedTime, 2, true, true)?>
<?	} ?>
			</div>
		</td>
	</tr>
</table>
<? } ?>
<div class="breadcrumbs">
	<a href="forums.php">Forums</a> &gt;
	<a href="forums.php?action=viewforum&amp;forumid=<?=$ThreadInfo['ForumID']?>"><?=$ForumName?></a> &gt;
	<?=$ThreadTitle?>
</div>
<div class="linkbox">
	<?=$Pages?>
</div>
<?
if (!$ThreadInfo['IsLocked'] || check_perms('site_moderate_forums')) {
	if (Forums::check_forumperm($ForumID, 'Write') && !$LoggedUser['DisablePosting']) {
		View::parse('generic/reply/quickreply.php', array(
			'InputTitle' => 'Post reply',
			'InputName' => 'thread',
			'InputID' => $ThreadID,
			'ForumID' => $ForumID,
			'TextareaCols' => 90
		));
	}
}
if (check_perms('site_moderate_forums')) {
	G::$DB->query("
			SELECT ID, AuthorID, AddedTime, Body
			FROM forums_topic_notes
			WHERE TopicID = $ThreadID
			ORDER BY ID ASC");
	$Notes = G::$DB->to_array();
?>
	<br />
	<h3 id="thread_notes">Thread notes</h3> <a href="#" onclick="$('#thread_notes_table').gtoggle(); return false;" class="brackets">Toggle</a>
	<form action="forums.php" method="post">
		<input type="hidden" name="action" value="take_topic_notes" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="topicid" value="<?=$ThreadID?>" />
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border hidden" id="thread_notes_table">
<?
	foreach ($Notes as $Note) {
?>
			<tr><td><?=Users::format_username($Note['AuthorID'])?> (<?=time_diff($Note['AddedTime'], 2, true, true)?>)</td><td><?=Text::full_format($Note['Body'])?></td></tr>
<?
	}
?>
			<tr>
				<td colspan="2" class="center">
					<div class="field_div textarea_wrap"><textarea id="topic_notes" name="body" cols="90" rows="3" onkeyup="resize('threadnotes');" style=" margin: 0px; width: 735px;"></textarea></div>
					<input type="submit" value="Save" />
				</td>
			</tr>
		</table>
	</form>
	<br />
	<h3>Edit thread</h3>
	<form class="edit_form" name="forum_thread" action="forums.php" method="post">
		<div>
		<input type="hidden" name="action" value="mod_thread" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="threadid" value="<?=$ThreadID?>" />
		<input type="hidden" name="page" value="<?=$Page?>" />
		</div>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border">
			<tr>
				<td class="label"><label for="sticky_thread_checkbox">Sticky</label></td>
				<td>
					<input type="checkbox" id="sticky_thread_checkbox" onclick="$('#ranking_row').gtoggle();" name="sticky"<? if ($ThreadInfo['IsSticky']) { echo ' checked="checked"'; } ?> tabindex="2" />
				</td>
			</tr>
			<tr id="ranking_row"<?=!$ThreadInfo['IsSticky'] ? ' class="hidden"' : ''?>>
				<td class="label"><label for="thread_ranking_textbox">Ranking</label></td>
				<td>
					<input type="text" id="thread_ranking_textbox" name="ranking" value="<?=$ThreadInfo['Ranking']?>" tabindex="2" />
				</td>
			</tr>
			<tr>
				<td class="label"><label for="locked_thread_checkbox">Locked</label></td>
				<td>
					<input type="checkbox" id="locked_thread_checkbox" name="locked"<? if ($ThreadInfo['IsLocked']) { echo ' checked="checked"'; } ?> tabindex="2" />
				</td>
			</tr>
			<tr>
				<td class="label"><label for="thread_title_textbox">Title</label></td>
				<td>
					<input type="text" id="thread_title_textbox" name="title" style="width: 75%;" value="<?=display_str($ThreadInfo['Title'])?>" tabindex="2" />
				</td>
			</tr>
			<tr>
				<td class="label"><label for="move_thread_selector">Move thread</label></td>
				<td>
					<select name="forumid" id="move_thread_selector" tabindex="2">
<?
	$OpenGroup = false;
	$LastCategoryID = -1;

	foreach ($Forums as $Forum) {
		if ($Forum['MinClassRead'] > $LoggedUser['Class']) {
			continue;
		}

		if ($Forum['CategoryID'] != $LastCategoryID) {
			$LastCategoryID = $Forum['CategoryID'];
			if ($OpenGroup) { ?>
					</optgroup>
<?			} ?>
					<optgroup label="<?=$ForumCats[$Forum['CategoryID']]?>">
<?			$OpenGroup = true;
		}
?>
						<option value="<?=$Forum['ID']?>"<? if ($ThreadInfo['ForumID'] == $Forum['ID']) { echo ' selected="selected"';} ?>><?=display_str($Forum['Name'])?></option>
<?	} ?>
					</optgroup>
					</select>
				</td>
			</tr>
<?	if (check_perms('site_admin_forums')) { ?>
			<tr>
				<td class="label"><label for="delete_thread_checkbox">Delete thread</label></td>
				<td>
					<input type="checkbox" id="delete_thread_checkbox" name="delete" tabindex="2" />
				</td>
			</tr>
<?	} ?>
			<tr>
				<td colspan="2" class="center">
					<input type="submit" value="Edit thread" tabindex="2" />
					<span style="float: right;">
						<input type="submit" name="trash" value="Trash" tabindex="2" />
					</span>
				</td>
			</tr>

		</table>
	</form>
<?
} // If user is moderator
?>
</div>
<? View::show_footer();
