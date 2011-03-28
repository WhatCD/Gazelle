<?
/**********|| Page to show individual forums || ********************************\

Things to expect in $_GET:
	ForumID: ID of the forum curently being browsed
	page:	The page the user's on.
	page = 1 is the same as no page

********************************************************************************/

include(SERVER_ROOT.'/sections/forums/functions.php');

//---------- Things to sort out before it can start printing/generating content

// Check for lame SQL injection attempts
$ForumID = $_GET['forumid'];
if(!is_number($ForumID)) {
	error(0);
}

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

list($Page,$Limit) = page_limit(TOPICS_PER_PAGE);

//---------- Get some data to start processing

// Caching anything beyond the first page of any given forum is just wasting ram
// users are more likely to search then to browse to page 2
if($Page==1) {
	list($Forum,,,$Stickies) = $Cache->get_value('forums_'.$ForumID);
}
if(!isset($Forum) || !is_array($Forum)) {
	$DB->query("SELECT
		t.ID,
		t.Title,
		t.AuthorID,
		author.Username AS AuthorUsername,
		t.IsLocked,
		t.IsSticky,
		t.NumPosts,
		t.LastPostID,
		t.LastPostTime,
		t.LastPostAuthorID,
		last_author.Username AS LastPostUsername
		FROM forums_topics AS t
		LEFT JOIN users_main AS last_author ON last_author.ID = t.LastPostAuthorID
		LEFT JOIN users_main AS author ON author.ID = t.AuthorID
		WHERE t.ForumID = '$ForumID'
		ORDER BY t.IsSticky DESC, t.LastPostTime DESC
		LIMIT $Limit"); // Can be cached until someone makes a new post
	$Forum = $DB->to_array('ID',MYSQLI_ASSOC);
	if($Page==1) {
		$DB->query("SELECT COUNT(ID) FROM forums_topics WHERE ForumID='$ForumID' AND IsSticky='1'");
		list($Stickies) = $DB->next_record();
		$Cache->cache_value('forums_'.$ForumID, array($Forum,'',0,$Stickies), 0);
	}
}

if(!isset($Forums[$ForumID])) { error(404); }

// Make sure they're allowed to look at the page
if (!check_perms('site_moderate_forums')) {
	$DB->query("SELECT RestrictedForums FROM users_info WHERE UserID = ".$LoggedUser['ID']);
	list($RestrictedForums) = $DB->next_record();
	$RestrictedForums = explode(',', $RestrictedForums);
	if (array_search($ForumID, $RestrictedForums) !== FALSE) { error(403); }
}
if($Forums[$ForumID]['MinClassRead'] > $LoggedUser['Class']) { error(403); }

// Start printing
show_header('Forums > '. $Forums[$ForumID]['Name']);
?>
<div class="thin">
	<h2><a href="forums.php">Forums</a> &gt; <?=$Forums[$ForumID]['Name']?></h2>
<? if($LoggedUser['Class'] >= $Forums[$ForumID]['MinClassCreate']){ ?>
	<div class="linkbox">
		[<a href="forums.php?action=new&amp;forumid=<?=$ForumID?>">New Thread</a>]
	</div>
<? } ?>
<? if(check_perms('site_moderate_forums')) { ?>
	<div class="linkbox">
		<a href="forums.php?action=edit_rules&amp;forumid=<?=$ForumID?>">Change specific rules</a>
	</div>
<? } ?>
<? if(!empty($Forums[$ForumID]['SpecificRules'])) { ?>
	<div class="linkbox">
			<strong>Forum Specific Rules</strong>
<? foreach($Forums[$ForumID]['SpecificRules'] as $ThreadIDs) {
	$Thread = get_thread_info($ThreadIDs);
?>
		<br />
		[<a href="forums.php?action=viewthread&amp;threadid=<?=$ThreadIDs?>"><?=$Thread['Title']?></a>]
<? } ?>
	</div>
<? } ?>
	<div class="linkbox pager">
<?
$Pages=get_pages($Page,$Forums[$ForumID]['NumTopics'],TOPICS_PER_PAGE,9);
echo $Pages;
?>
	</div>
	<table class="forum_list" width="100%">
		<tr class="colhead">
			<td style="width:2%;"></td>
			<td>Latest</td>
			<td style="width:7%;">Replies</td>
			<td style="width:14%;">Author</td>
		</tr>
<?
// Check that we have content to process
if (count($Forum) == 0) {
?>
		<tr>
			<td colspan="4">
				No threads to display in this forum!
			</td>
		</tr>
<?
} else {
	// forums_last_read_topics is a record of the last post a user read in a topic, and what page that was on
	$DB->query('SELECT
		l.TopicID,
		l.PostID,
		CEIL((SELECT COUNT(ID) FROM forums_posts WHERE forums_posts.TopicID = l.TopicID AND forums_posts.ID<=l.PostID)/'.$PerPage.') AS Page
		FROM forums_last_read_topics AS l
		WHERE TopicID IN('.implode(', ', array_keys($Forum)).') AND
		UserID=\''.$LoggedUser['ID'].'\'');

	// Turns the result set into a multi-dimensional array, with
	// forums_last_read_topics.TopicID as the key.
	// This is done here so we get the benefit of the caching, and we
	// don't have to make a database query for each topic on the page
	$LastRead = $DB->to_array('TopicID');

	//---------- Begin printing

	$Row='a';
	foreach($Forum as $Topic){
		list($TopicID, $Title, $AuthorID, $AuthorName, $Locked, $Sticky, $PostCount, $LastID, $LastTime, $LastAuthorID, $LastAuthorName) = array_values($Topic);
		$Row = ($Row == 'a') ? 'b' : 'a';

			// Build list of page links
		// Only do this if there is more than one page
		$PageLinks = array();
		$ShownEllipses = false;
		$PagesText = '';
		$TopicPages = ceil($PostCount/$PerPage);

		if($TopicPages > 1){
			$PagesText=' (';
			for($i = 1; $i <= $TopicPages; $i++){
				if($TopicPages>4 && ($i > 2 && $i <= $TopicPages-2)) {
					if(!$ShownEllipses) {
						$PageLinks[]='-';
						$ShownEllipses = true;
					}
					continue;
				}
				$PageLinks[]='<a href="forums.php?action=viewthread&amp;threadid='.$TopicID.'&amp;page='.$i.'">'.$i.'</a>';
			}
			$PagesText.=implode(' ', $PageLinks);
			$PagesText.=')';
		}

		// handle read/unread posts - the reason we can't cache the whole page
		if((!$Locked || $Sticky) && ((empty($LastRead[$TopicID]) || $LastRead[$TopicID]['PostID']<$LastID) && strtotime($LastTime)>$LoggedUser['CatchupTime'])) {
			$Read = 'unread';
		} else {
			$Read = 'read';
		}
		if($Locked) { $Read .= "_locked"; }
		if($Sticky) { $Read .= "_sticky"; }
?>
	<tr class="row<?=$Row?>">
		<td class="<?=$Read?>" title="<?=ucwords(str_replace('_',' ',$Read))?>"></td>
		<td>
			<span style="float:left;" class="last_topic">
<?
		$TopicLength=75-(2*count($PageLinks));
		unset($PageLinks);
?>
				<strong>
					<a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>" title="<?=display_str($Title)?>"><?=display_str(cut_string($Title, $TopicLength)) ?></a>
				</strong>
				<?=$PagesText?>
			</span>
<?		if(!empty($LastRead[$TopicID])) { ?>
			<span style="float: left;" class="last_read" title="Jump to last read">
				<a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>&amp;page=<?=$LastRead[$TopicID]['Page']?>#post<?=$LastRead[$TopicID]['PostID']?>"></a>
			</span>
<?		} ?>
			<span style="float:right;" class="last_poster">
				by <?=format_username($LastAuthorID, $LastAuthorName)?> <?=time_diff($LastTime,1)?>
			</span>
		</td>
		<td><?=number_format($PostCount-1)?></td>
		<td><?=format_username($AuthorID, $AuthorName)?></td>
	</tr>
<?	}
} ?>
</table>
	<div class="linkbox pager">
		<?=$Pages?>
	</div>
	<div class="linkbox">[<a href="forums.php?action=catchup&amp;forumid=<?=$ForumID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">Catch up</a>]</div>
</div>
<? show_footer(); ?>
