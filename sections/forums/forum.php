<?php
/**********|| Page to show individual forums || ********************************\

Things to expect in $_GET:
	ForumID: ID of the forum curently being browsed
	page:	The page the user's on.
	page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

// Check for lame SQL injection attempts
$ForumID = $_GET['forumid'];
if (!is_number($ForumID)) {
	error(0);
}

$IsDonorForum = $ForumID == DONOR_FORUM ? true : false;
$Tooltip = $ForumID == DONOR_FORUM ? "tooltip_gold" : "tooltip";

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

list($Page, $Limit) = Format::page_limit(TOPICS_PER_PAGE);

//---------- Get some data to start processing

// Caching anything beyond the first page of any given forum is just wasting RAM.
// Users are more likely to search than to browse to page 2.
if ($Page == 1) {
	list($Forum,,,$Stickies) = $Cache->get_value("forums_$ForumID");
}
if (!isset($Forum) || !is_array($Forum)) {
	$DB->query("
		SELECT
			ID,
			Title,
			AuthorID,
			IsLocked,
			IsSticky,
			NumPosts,
			LastPostID,
			LastPostTime,
			LastPostAuthorID
		FROM forums_topics
		WHERE ForumID = '$ForumID'
		ORDER BY Ranking = 0, Ranking ASC, IsSticky DESC, LastPostTime DESC
		LIMIT $Limit"); // Can be cached until someone makes a new post
	$Forum = $DB->to_array('ID', MYSQLI_ASSOC, false);

	if ($Page == 1) {
		$DB->query("
			SELECT COUNT(ID)
			FROM forums_topics
			WHERE ForumID = '$ForumID'
				AND IsSticky = '1'");
		list($Stickies) = $DB->next_record();
		$Cache->cache_value("forums_$ForumID", array($Forum, '', 0, $Stickies), 0);
	}
}

if (!isset($Forums[$ForumID])) {
	error(404);
}
// Make sure they're allowed to look at the page
if (!check_perms('site_moderate_forums')) {
	if (isset($LoggedUser['CustomForums'][$ForumID]) && $LoggedUser['CustomForums'][$ForumID] === 0) {
		error(403);
	}
}


$ForumName = display_str($Forums[$ForumID]['Name']);
if (!Forums::check_forumperm($ForumID)) {
	error(403);
}

// Start printing
View::show_header('Forums &gt; '. $Forums[$ForumID]['Name'], '', $IsDonorForum ? 'donor' : '');
?>
<div class="thin">
	<h2><a href="forums.php">Forums</a> &gt; <?=$ForumName?></h2>
	<div class="linkbox">
<? if (Forums::check_forumperm($ForumID, 'Write') && Forums::check_forumperm($ForumID, 'Create')) { ?>
		<a href="forums.php?action=new&amp;forumid=<?=$ForumID?>" class="brackets">New thread</a>
<? } ?>
		<a href="#" onclick="$('#searchforum').gtoggle(); this.innerHTML = (this.innerHTML == 'Search this forum' ? 'Hide search' : 'Search this forum'); return false;" class="brackets">Search this forum</a>
		<div id="searchforum" class="hidden center">
			<div style="display: inline-block;">
				<h3>Search this forum:</h3>
				<form class="search_form" name="forum" action="forums.php" method="get">
					<table cellpadding="6" cellspacing="1" border="0" class="layout border">
						<tr>
							<td>
								<input type="hidden" name="action" value="search" />
								<input type="hidden" name="forums[]" value="<?=$ForumID?>" />
								<strong>Search for:</strong>
							</td>
							<td>
								<input type="search" id="searchbox" name="search" size="70" />
							</td>
						</tr>
						<tr>
							<td><strong>Search in:</strong></td>
							<td>
								<input type="radio" name="type" id="type_title" value="title" checked="checked" />
								<label for="type_title">Titles</label>
								<input type="radio" name="type" id="type_body" value="body" />
								<label for="type_body">Post bodies</label>
							</td>
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
	</div>
<?	if (check_perms('site_moderate_forums')) { ?>
	<div class="linkbox">
		<a href="forums.php?action=edit_rules&amp;forumid=<?=$ForumID?>" class="brackets">Change specific rules</a>
	</div>
<?	} ?>
<?	if (!empty($Forums[$ForumID]['SpecificRules'])) { ?>
	<div class="linkbox">
			<strong>Forum Specific Rules</strong>
<?		foreach ($Forums[$ForumID]['SpecificRules'] as $ThreadIDs) {
			$Thread = Forums::get_thread_info($ThreadIDs);
			if ($Thread === null) {
				error(404);
			}
?>
		<br />
		<a href="forums.php?action=viewthread&amp;threadid=<?=$ThreadIDs?>" class="brackets"><?=display_str($Thread['Title'])?></a>
<?		} ?>
	</div>
<?	} ?>
	<div class="linkbox pager">
<?
$Pages = Format::get_pages($Page, $Forums[$ForumID]['NumTopics'], TOPICS_PER_PAGE, 9);
echo $Pages;
?>
	</div>
	<table class="forum_index" width="100%">
		<tr class="colhead">
			<td style="width: 2%;"></td>
			<td>Latest</td>
			<td style="width: 7%;">Replies</td>
			<td style="width: 14%;">Author</td>
		</tr>
<?
// Check that we have content to process
if (count($Forum) === 0) {
?>
		<tr>
			<td colspan="4">
				No threads to display in this forum!
			</td>
		</tr>
<?
} else {
	// forums_last_read_topics is a record of the last post a user read in a topic, and what page that was on
	$DB->query("
		SELECT
			l.TopicID,
			l.PostID,
			CEIL((
					SELECT COUNT(p.ID)
					FROM forums_posts AS p
					WHERE p.TopicID = l.TopicID
						AND p.ID <= l.PostID
				) / $PerPage
			) AS Page
		FROM forums_last_read_topics AS l
		WHERE l.TopicID IN (".implode(', ', array_keys($Forum)).')
			AND l.UserID = \''.$LoggedUser['ID'].'\'');

	// Turns the result set into a multi-dimensional array, with
	// forums_last_read_topics.TopicID as the key.
	// This is done here so we get the benefit of the caching, and we
	// don't have to make a database query for each topic on the page
	$LastRead = $DB->to_array('TopicID');

	//---------- Begin printing

	$Row = 'a';
	foreach ($Forum as $Topic) {
		list($TopicID, $Title, $AuthorID, $Locked, $Sticky, $PostCount, $LastID, $LastTime, $LastAuthorID) = array_values($Topic);
		$Row = $Row === 'a' ? 'b' : 'a';
		// Build list of page links
		// Only do this if there is more than one page
		$PageLinks = array();
		$ShownEllipses = false;
		$PagesText = '';
		$TopicPages = ceil($PostCount / $PerPage);

		if ($TopicPages > 1) {
			$PagesText = ' (';
			for ($i = 1; $i <= $TopicPages; $i++) {
				if ($TopicPages > 4 && ($i > 2 && $i <= $TopicPages - 2)) {
					if (!$ShownEllipses) {
						$PageLinks[] = '-';
						$ShownEllipses = true;
					}
					continue;
				}
				$PageLinks[] = "<a href=\"forums.php?action=viewthread&amp;threadid=$TopicID&amp;page=$i\">$i</a>";
			}
			$PagesText .= implode(' ', $PageLinks);
			$PagesText .= ')';
		}

		// handle read/unread posts - the reason we can't cache the whole page
		if ((!$Locked || $Sticky) && ((empty($LastRead[$TopicID]) || $LastRead[$TopicID]['PostID'] < $LastID) && strtotime($LastTime) > $LoggedUser['CatchupTime'])) {
			$Read = 'unread';
		} else {
			$Read = 'read';
		}
		if ($Locked) {
			$Read .= '_locked';
		}
		if ($Sticky) {
			$Read .= '_sticky';
		}
?>
	<tr class="row<?=$Row?>">
		<td class="<?=$Read?> <?=$Tooltip?>" title="<?=ucwords(str_replace('_', ' ', $Read))?>"></td>
		<td>
			<span style="float: left;" class="last_topic">
<?
		$TopicLength = 75 - (2 * count($PageLinks));
		unset($PageLinks);
		$Title = display_str($Title);
		$DisplayTitle = $Title;

?>
				<strong>
					<a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>" class="tooltip" data-title-plain="<?=$Title?>"><?=Format::cut_string($DisplayTitle, $TopicLength) ?></a>
				</strong>
				<?=$PagesText?>
			</span>
<?		if (!empty($LastRead[$TopicID])) { ?>
			<span style="float: left;" class="<?=$Tooltip?> last_read" title="Jump to last read">
				<a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>&amp;page=<?=$LastRead[$TopicID]['Page']?>#post<?=$LastRead[$TopicID]['PostID']?>"></a>
			</span>
<?		} ?>
			<span style="float: right;" class="last_poster">
				by <?=Users::format_username($LastAuthorID, false, false, false, false, false, $IsDonorForum)?> <?=time_diff($LastTime,1)?>
			</span>
		</td>
		<td class="number_column"><?=number_format($PostCount - 1)?></td>
		<td><?=Users::format_username($AuthorID, false, false, false, false, false, $IsDonorForum)?></td>
	</tr>
<?	}
} ?>
</table>
<!--<div class="breadcrumbs">
	<a href="forums.php">Forums</a> &gt; <?=$ForumName?>
</div>-->
	<div class="linkbox pager">
		<?=$Pages?>
	</div>
	<div class="linkbox"><a href="forums.php?action=catchup&amp;forumid=<?=$ForumID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Catch up</a></div>
</div>
<? View::show_footer(); ?>
