<?
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
$ThreadInfo = get_thread_info($ThreadID, true, true);
$ForumID = $ThreadInfo['ForumID'];

// Make sure they're allowed to look at the page
if(!check_forumperm($ForumID)) {
	error(403);
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

if ($_GET['updatelastread'] != '0') {
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

// Start printing
show_header('Forums'.' > '.$Forums[$ForumID]['Name'].' > '.$ThreadInfo['Title'],'comments,subscriptions,bbcode');
?>
<div class="thin">
	<h2>
		<a href="forums.php">Forums</a> &gt;
		<a href="forums.php?action=viewforum&amp;forumid=<?=$ThreadInfo['ForumID']?>"><?=$Forums[$ForumID]['Name']?></a> &gt;
		<?=display_str($ThreadInfo['Title'])?>
	</h2>
	<div class="linkbox">
		<div class="center">
			<a href="reports.php?action=report&amp;type=thread&amp;id=<?=$ThreadID?>">[Report Thread]</a>
			<a href="#" onclick="Subscribe(<?=$ThreadID?>);return false;" id="subscribelink<?=$ThreadID?>">[<?=(in_array($ThreadID, $UserSubscriptions) ? 'Unsubscribe' : 'Subscribe')?>]</a>
			<a href="#" onclick="$('#searchthread').toggle(); this.innerHTML = (this.innerHTML == '[Search this Thread]'?'[Hide Search]':'[Search this Thread]'); return false;">[Search this Thread]</a>
		</div>
		<div id="searchthread" class="hidden center">
			<div style="display: inline-block;">
				<h3>Search this thread:</h3>
				<form action="forums.php" method="get">
					<table cellpadding="6" cellspacing="1" border="0" class="border">	
						<input type="hidden" name="action" value="search" />
						<input type="hidden" name="threadid" value="<?=$ThreadID?>" />
						<tr>
							<td><strong>Search for:</strong></td><td><input type="text" id="searchbox" name="search" size="70" /></td>
						</tr>
						<tr>
							<td><strong>Username:</strong></td><td><input type="text" id="username" name="user" size="70" /></td>
						</tr>
						<tr><td colspan="2" style="text-align: center"><input type="submit" name="submit" value="Search" /></td></tr>
					</table>
				</form>
				<br />
			</div>
		</div>
<?
$Pages=get_pages($Page,$ThreadInfo['Posts'],$PerPage,9);
echo $Pages;
?>
	</div>
<?
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

?>
	<div class="box thin clear">
		<div class="head colhead_dark"><strong>Poll<? if ($Closed) { echo ' [Closed]'; } ?><? if ($Featured && $Featured !== '0000-00-00 00:00:00') { echo ' [Featured]'; } ?></strong> <a href="#" onclick="$('#threadpoll').toggle();log_hit();return false;">(View)</a></div>
		<div class="pad<? if (/*$LastRead !== null || */$ThreadInfo['IsLocked']) { echo ' hidden'; } ?>" id="threadpoll">
			<p><strong><?=display_str($Question)?></strong></p>
<?	if ($UserResponse !== null || $Closed || $ThreadInfo['IsLocked'] || !check_forumperm($ForumID)) { ?>
			<ul class="poll nobullet">
<?		
		if(!$RevealVoters) {
			foreach($Answers as $i => $Answer) {
				if (!empty($Votes[$i]) && $TotalVotes > 0) {
					$Ratio = $Votes[$i]/$MaxVotes;
					$Percent = $Votes[$i]/$TotalVotes;
				} else {
					$Ratio=0;
					$Percent=0;
				}
?>
					<li><?=display_str($Answer)?> (<?=number_format($Percent*100,2)?>%)</li>
					<li class="graph">
						<span class="left_poll"></span>
						<span class="center_poll" style="width:<?=round($Ratio*750)?>px;"></span>
						<span class="right_poll"></span>
					</li>
<?			}
			if ($Votes[0] > 0) {
?>
				<li>(Blank) (<?=number_format((float)($Votes[0]/$TotalVotes*100),2)?>%)</li>
				<li class="graph">
					<span class="left_poll"></span>
					<span class="center_poll" style="width:<?=round(($Votes[0]/$MaxVotes)*750)?>px;"></span>
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
			foreach($Staff as $Staffer) {
				$StaffNames[] = $Staffer['Username'];
			}

			$DB->query("SELECT fpv.Vote AS Vote,
						GROUP_CONCAT(um.Username SEPARATOR ', ')
						FROM users_main AS um 
							LEFT JOIN forums_polls_votes AS fpv ON um.ID = fpv.UserID
						WHERE TopicID = ".$ThreadID."
						GROUP BY fpv.Vote");
			
			$StaffVotesTmp = $DB->to_array();
			$StaffCount = count($StaffNames);

			$StaffVotes = array();
			foreach($StaffVotesTmp as $StaffVote) {
				list($Vote, $Names) = $StaffVote;
				$StaffVotes[$Vote] = $Names;
				$Names = explode(", ", $Names);
				$StaffNames = array_diff($StaffNames, $Names);
			}
?>			<ul style="list-style: none;" id="poll_options">
<?

			foreach($Answers as $i => $Answer) {
?>
				<li>
					<a href="forums.php?action=change_vote&amp;threadid=<?=$ThreadID?>&amp;auth=<?=$LoggedUser['AuthKey']?>&amp;vote=<?=(int) $i?>"><?=display_str($Answer == '' ? "Blank" : $Answer)?></a>
					 - <?=$StaffVotes[$i]?>&nbsp;(<?=number_format(((float) $Votes[$i]/$TotalVotes)*100, 2)?>%)
					 <a href="forums.php?action=delete_poll_option&amp;threadid=<?=$ThreadID?>&amp;auth=<?=$LoggedUser['AuthKey']?>&amp;vote=<?=(int) $i?>">[X]</a>
</li>
<?			} ?>
				<li><a href="forums.php?action=change_vote&amp;threadid=<?=$ThreadID?>&amp;auth=<?=$LoggedUser['AuthKey']?>&amp;vote=0">Blank</a> - <?=$StaffVotes[0]?>&nbsp;(<?=number_format(((float) $Votes[0]/$TotalVotes)*100, 2)?>%)</li>
			</ul>
<?
			if($ForumID == STAFF_FORUM) {
?>
			<br />
			<strong>Votes:</strong> <?=number_format($TotalVotes)?> / <?=$StaffCount ?>
			<br />
			<strong>Missing Votes:</strong> <?=implode(", ", $StaffNames)?>
			<br /><br />
<?
			}
?>
			<a href="#" onclick="AddPollOption(<?=$ThreadID?>); return false;">[+]</a>
<?
		}

	} else { 
	//User has not voted
?>
			<div id="poll_results">
				<form id="polls">
					<input type="hidden" name="action" value="poll"/>
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="large" value="1"/>
					<input type="hidden" name="topicid" value="<?=$ThreadID?>" />
					<ul style="list-style: none;" id="poll_options">
<? foreach($Answers as $i => $Answer) { //for ($i = 1, $il = count($Answers); $i <= $il; $i++) { ?>
						<li>
							<input type="radio" name="vote" id="answer_<?=$i?>" value="<?=$i?>" />
							<label for="answer_<?=$i?>"><?=display_str($Answer)?></label>
						</li>
<? } ?>
						<li>
							<br />
							<input type="radio" name="vote" id="answer_0" value="0" /> <label for="answer_0">Blank - Show the results!</label><br />
						</li>
					</ul>
<? if($ForumID == STAFF_FORUM) { ?>
					<a href="#" onclick="AddPollOption(<?=$ThreadID?>); return false;">[+]</a>
					<br />
					<br />
<? } ?>
					<input type="button" style="float: left;" onclick="ajax.post('index.php','polls',function(response){$('#poll_results').raw().innerHTML = response});" value="Vote">
				</form>
			</div>
<? } ?>
<? if(check_perms('forums_polls_moderate') && !$RevealVoters) { ?>
	<? if (!$Featured || $Featured == '0000-00-00 00:00:00') { ?>
			<form action="forums.php" method="post">
				<input type="hidden" name="action" value="poll_mod"/>
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="topicid" value="<?=$ThreadID?>" />
				<input type="hidden" name="feature" value="1">
				<input type="submit" style="float: left;" onclick="return confirm('Are you sure you want to feature this poll?');"; value="Feature">
			</form>
	<? } ?>
			<form action="forums.php" method="post">
				<input type="hidden" name="action" value="poll_mod"/>
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="topicid" value="<?=$ThreadID?>" />
				<input type="hidden" name="close" value="1">
				<input type="submit" style="float: left;" value="<?=(!$Closed ? 'Close' : 'Open')?>">
			</form>
<? } ?>
		</div>
	</div>
<? 
} //End Polls

//Sqeeze in stickypost
if($ThreadInfo['StickyPostID']) {
	if($ThreadInfo['StickyPostID'] != $Thread[0]['ID']) {
		array_unshift($Thread, $ThreadInfo['StickyPost']);
	}
	if($ThreadInfo['StickyPostID'] != $Thread[count($Thread)-1]['ID']) {
		$Thread[] = $ThreadInfo['StickyPost'];
	}
}

foreach($Thread as $Key => $Post){
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(user_info($AuthorID));
	
	// Image proxy CTs
	if(check_perms('site_proxy_images') && !empty($UserTitle)) {
		$UserTitle = preg_replace_callback('~src=("?)(http.+?)(["\s>])~', function($Matches) {
																		return 'src='.$Matches[1].'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?c=1&amp;i='.urlencode($Matches[2]).$Matches[3];
																	  }, $UserTitle);
	}
?>
<table class="forum_post box vertical_margin<? if (((!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) && $PostID>$LastRead && strtotime($AddedTime)>$LoggedUser['CatchupTime']) || (isset($RequestKey) && $Key==$RequestKey)) { echo ' forum_unread'; } if($HeavyInfo['DisableAvatars']) { echo ' noavatar'; } ?>" id="post<?=$PostID?>">
	<tr class="colhead_dark">
		<td colspan="2">
			<span style="float:left;"><a class="post_id" href='forums.php?action=viewthread&amp;threadid=<?=$ThreadID?>&amp;postid=<?=$PostID?>#post<?=$PostID?>'>#<?=$PostID?></a>
				<strong><?=format_username($AuthorID, $Username, $Donor, $Warned, $Enabled == 2 ? false : true, $PermissionID)?></strong> 
				<span class="user_title"><?=!empty($UserTitle) ? '('.$UserTitle.')' : '' ?></span> 
				<?=time_diff($AddedTime,2)?> 
<? if(!$ThreadInfo['IsLocked'] || check_perms('site_moderate_forums')){ ?> 
				- <a href="#quickpost" onclick="Quote('<?=$PostID?>','<?=$Username?>');">[Quote]</a> 
<? }
if (((!$ThreadInfo['IsLocked'] && check_forumperm($ForumID, 'Write')) && ($AuthorID == $LoggedUser['ID']) || check_perms('site_moderate_forums'))) { ?>
				- <a href="#post<?=$PostID?>" onclick="Edit_Form('<?=$PostID?>','<?=$Key?>');">[Edit]</a> 
<? }
if(check_perms('site_admin_forums') && $ThreadInfo['Posts'] > 1) { ?> 
				- <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');">[Delete]</a> 
<? }
if($PostID == $ThreadInfo['StickyPostID']) { ?>
				<strong><span class="sticky_post">[Sticky]</span></strong>
<?	if(check_perms('site_moderate_forums')) { ?>
				- <a href="forums.php?action=sticky_post&amp;threadid=<?=$ThreadID?>&amp;postid=<?=$PostID?>&amp;remove=true&amp;auth=<?=$LoggedUser['AuthKey']?>" >[X]</a>
<?	}
} else {
	if(check_perms('site_moderate_forums')) { ?>
				- <a href="forums.php?action=sticky_post&amp;threadid=<?=$ThreadID?>&amp;postid=<?=$PostID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" >[&#x21d5;]</a>
<? 	}
}
?>
			</span>
			<span id="bar<?=$PostID?>" style="float:right;">
				<a href="reports.php?action=report&amp;type=post&amp;id=<?=$PostID?>">[Report]</a>
				&nbsp;
				<a href="#">&uarr;</a>
			</span>
		</td>
	</tr>
	<tr>
<? if(empty($HeavyInfo['DisableAvatars'])) { ?>
		<td class="avatar" valign="top">
	<? if ($Avatar) { ?>
			<img src="<?=$Avatar?>" width="150" style="max-height:400px;" alt="<?=$Username ?>'s avatar" />
	<? } else { ?>
			<img src="<?=STATIC_SERVER?>common/avatars/default.png" width="150" alt="Default avatar" />
	<? } ?>
	</td>
<? } ?>
		<td class="body" valign="top"<? if(!empty($HeavyInfo['DisableAvatars'])) { echo ' colspan="2"'; } ?>>
			<div id="content<?=$PostID?>">
				<?=$Text->full_format($Body) ?>
<? if($EditedUserID){ ?>
				<br />
				<br />
<?	if(check_perms('site_admin_forums')) { ?>
				<a href="#content<?=$PostID?>" onclick="LoadEdit('forums', <?=$PostID?>, 1); return false;">&laquo;</a> 
<? 	} ?>
				Last edited by
				<?=format_username($EditedUserID, $EditedUsername) ?> <?=time_diff($EditedTime,2,true,true)?>
<? } ?>
			</div>
		</td>
	</tr>
</table>
<?	} ?>
<div class="breadcrumbs">
	<a href="forums.php">Forums</a> &gt;
	<a href="forums.php?action=viewforum&amp;forumid=<?=$ThreadInfo['ForumID']?>"><?=$Forums[$ForumID]['Name']?></a> &gt;
	<?=display_str($ThreadInfo['Title'])?>
</div>
<div class="linkbox">
	<?=$Pages?>
</div>
<?
if(!$ThreadInfo['IsLocked'] || check_perms('site_moderate_forums')) {
	if(check_forumperm($ForumID, 'Write') && !$LoggedUser['DisablePosting']) {
	//TODO: Preview, come up with a standard, make it look like post or just a block of formatted bbcode, but decide and write some proper html
?>
			<br />
			<h3>Post reply</h3>
			<div class="box pad">
				<table id="quickreplypreview" class="forum_post box vertical_margin hidden" style="text-align:left;">
					<tr class="colhead_dark">
						<td colspan="2">
							<span style="float:left;"><a href='#quickreplypreview'>#XXXXXX</a>
								by <strong><?=format_username($LoggedUser['ID'], $LoggedUser['Username'], $LoggedUser['Donor'], $LoggedUser['Warned'], $LoggedUser['Enabled'] == 2 ? false : true, $LoggedUser['PermissionID'])?></strong> <? if (!empty($LoggedUser['Title'])) { echo '('.$LoggedUser['Title'].')'; }?>
							Just now
							</span>
							<span id="barpreview" style="float:right;">
								<a href="#quickreplypreview">[Report Post]</a>
								&nbsp;
								<a href="#">&uarr;</a>
							</span>
						</td>
					</tr>
					<tr>
						<td class="avatar" valign="top">
				<? if (!empty($LoggedUser['Avatar'])) { ?>
							<img src="<?=$LoggedUser['Avatar']?>" width="150" alt="<?=$LoggedUser['Username']?>'s avatar" />
				<? } else { ?>
							<img src="<?=STATIC_SERVER?>common/avatars/default.png" width="150" alt="Default avatar" />
				<? } ?>
						</td>
						<td class="body" valign="top">
							<div id="contentpreview" style="text-align:left;"></div>
						</td>
					</tr>
				</table>
				<form id="quickpostform" action="" method="post" style="display: block; text-align: center;">
					<input type="hidden" name="action" value="reply" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="thread" value="<?=$ThreadID?>" />

					<div id="quickreplytext">
						<textarea id="quickpost" style="width: 95%;" tabindex="1" onkeyup="resize('quickpost');" name="body" cols="90" rows="8"></textarea> <br />
					</div>
					<div>
<? if(!in_array($ThreadID, $UserSubscriptions)) { ?>
						<input id="subscribebox" type="checkbox" name="subscribe"<?=!empty($HeavyInfo['AutoSubscribe'])?' checked="checked"':''?> tabindex="2" />
						<label for="subscribebox">Subscribe</label>
<?
}
	if($ThreadInfo['LastPostAuthorID']==$LoggedUser['ID'] && (check_perms('site_forums_double_post') || in_array($ForumID, $ForumsDoublePost))) {
?>
						<input id="mergebox" type="checkbox" name="merge" checked="checked" tabindex="2" />
						<label for="mergebox">Merge</label>
<? } ?>
						<input id="post_preview" type="button" value="Preview" tabindex="1" onclick="if(this.preview){Quick_Edit();}else{Quick_Preview();}" />
						<input type="submit" value="Post reply" tabindex="1" />
					</div>
				</form>
			</div>
<?
	}
}
if(check_perms('site_moderate_forums')) {
?>
	<br />
	<h3>Edit thread</h3>
	<form action="forums.php" method="post">
		<div>
		<input type="hidden" name="action" value="mod_thread" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="threadid" value="<?=$ThreadID?>" />
		<input type="hidden" name="page" value="<?=$Page?>" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		</div>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="border">
			<tr>
				<td class="label">Sticky</td>
				<td>
					<input type="checkbox" name="sticky"<? if($ThreadInfo['IsSticky']) { echo ' checked="checked"'; } ?> tabindex="2" />
				</td>
			</tr>
			<tr>
				<td class="label">Locked</td>
				<td>
					<input type="checkbox" name="locked"<? if($ThreadInfo['IsLocked']) { echo ' checked="checked"'; } ?> tabindex="2" />
				</td>
			</tr>
			<tr>
				<td class="label">Title</td>
				<td>
					<input type="text" name="title" style="width: 75%;" value="<?=display_str($ThreadInfo['Title'])?>" tabindex="2" />
				</td>
			</tr>
			<tr>
				<td class="label">Move thread</td>
				<td>
					<select name="forumid" tabindex="2">
<? 
$OpenGroup = false;
$LastCategoryID=-1;

foreach ($Forums as $Forum) {
	if ($Forum['MinClassRead'] > $LoggedUser['Class']) {
		continue;
	}

	if ($Forum['CategoryID'] != $LastCategoryID) {
		$LastCategoryID = $Forum['CategoryID'];
		if($OpenGroup) { ?>
					</optgroup>
<?		} ?>
					<optgroup label="<?=$ForumCats[$Forum['CategoryID']]?>">
<?		$OpenGroup = true;
	}
?>
						<option value="<?=$Forum['ID']?>"<? if($ThreadInfo['ForumID'] == $Forum['ID']) { echo ' selected="selected"';} ?>><?=$Forum['Name']?></option>
<? } ?>
					</optgroup>
					</select>
				</td>
			</tr>
<? if(check_perms('site_admin_forums')) { ?>
			<tr>
				<td class="label">Delete thread</td>
				<td>
					<input type="checkbox" name="delete" tabindex="2" />
				</td>
			</tr>
<? } ?>
			<tr>
				<td colspan="2" class="center">
					<input type="submit" value="Edit thread" tabindex="2" />
				</td>
			</tr>

		</table>
	</form>
<?
} // If user is moderator
?>
</div>
<? show_footer();
