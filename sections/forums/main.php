<?
if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

//We have to iterate here because if one is empty it breaks the query
$TopicIDs = array();
foreach($Forums as $Forum) {
	if (!empty($Forum['LastPostTopicID'])) {
		$TopicIDs[]=$Forum['LastPostTopicID'];
	}
}

//Now if we have IDs' we run the query
if(!empty($TopicIDs)) {
	$DB->query("SELECT
		l.TopicID,
		l.PostID,
		CEIL((SELECT COUNT(ID) FROM forums_posts WHERE forums_posts.TopicID = l.TopicID AND forums_posts.ID<=l.PostID)/$PerPage) AS Page
		FROM forums_last_read_topics AS l
		WHERE TopicID IN(".implode(',',$TopicIDs).") AND
		UserID='$LoggedUser[ID]'");
	$LastRead = $DB->to_array('TopicID', MYSQLI_ASSOC);
} else {
	$LastRead = array();
}

show_header('Forums');
?>
<div class="thin">
	<h2>Forums</h2>
<?

$Row = 'a';
$LastCategoryID=0;
$OpenTable = false;
$DB->query("SELECT RestrictedForums FROM users_info WHERE UserID = ".$LoggedUser['ID']);
list($RestrictedForums) = $DB->next_record();
$RestrictedForums = explode(',', $RestrictedForums);

foreach ($Forums as $Forum) {
	list($ForumID, $CategoryID, $ForumName, $ForumDescription, $MinRead, $MinWrite, $MinCreate, $NumTopics, $NumPosts, $LastPostID, $LastAuthorID, $LastPostAuthorName, $LastTopicID, $LastTime, $SpecificRules, $LastTopic, $Locked, $Sticky) = array_values($Forum);
	if ($MinRead>$LoggedUser['Class'] || array_search($ForumID, $RestrictedForums) !== FALSE) {
		continue;
	}
	$Row = ($Row == 'a') ? 'b' : 'a';
	$ForumDescription = display_str($ForumDescription);

	if($CategoryID!=$LastCategoryID) {
		$LastCategoryID=$CategoryID;
		if($OpenTable) { ?>
	</table>
<? 		} ?>
<h3><?=$ForumCats[$CategoryID]?></h3>
	<table class="forum_index">
		<tr class="colhead">
			<td style="width:2%;"></td>
			<td style="width:25%;">Forum</td>
			<td>Last Post</td>
			<td style="width:7%;">Topics</td>
			<td style="width:7%;">Posts</td>
		</tr>
<?
		$OpenTable = true;
	}

	if((!$Locked || $Sticky) && $LastPostID != 0 && ((empty($LastRead[$LastTopicID]) || $LastRead[$LastTopicID]['PostID'] < $LastPostID) && strtotime($LastTime)>$LoggedUser['CatchupTime'])) {
		$Read = 'unread';
	} else {
		$Read = 'read';
	}
/* Removed per request, as distracting
	if($Locked) { $Read .= "_locked"; }
	if($Sticky) { $Read .= "_sticky"; }
*/
?>
	<tr class="row<?=$Row?>">
		<td class="<?=$Read?>" title="<?=ucfirst($Read)?>"></td>
		<td>
			<h4 class="min_padding">
				<a href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>" title="<?=display_str($ForumDescription)?>"><?=display_str($ForumName)?></a>
			</h4>
		</td>
<? if ($NumPosts == 0) { ?>
		<td colspan="3">
			There are no topics here<?=($MinCreate<=$LoggedUser['Class']) ? ', <a href="forums.php?action=new&amp;forumid='.$ForumID.'">'.'create one'.'</a>' : ''?>.
		</td>
<? } else { ?>
		<td>
			<span style="float:left;" class="last_topic">
				<a href="forums.php?action=viewthread&amp;threadid=<?=$LastTopicID?>" title="<?=display_str($LastTopic)?>"><?=display_str(cut_string($LastTopic, 50, 1))?></a>
			</span>
<? if (!empty($LastRead[$LastTopicID])) { ?>
			<span style="float: left;" class="last_read" title="Jump to last read">
				<a href="forums.php?action=viewthread&amp;threadid=<?=$LastTopicID?>&amp;page=<?=$LastRead[$LastTopicID]['Page']?>#post<?=$LastRead[$LastTopicID]['PostID']?>"></a>
			</span>
<? } ?>
			<span style="float:right;" class="last_poster">by <?=format_username($LastAuthorID, $LastPostAuthorName)?> <?=time_diff($LastTime,1)?></span>
		</td>
		<td><?=number_format($NumTopics)?></td>
		<td><?=number_format($NumPosts)?></td>
<? } ?>
	</tr>
<? } ?>
	</table>
	<div class="linkbox"><a href="forums.php?action=catchup&amp;forumid=all&amp;auth=<?=$LoggedUser['AuthKey']?>">Catch up</a></div>
</div>
<? show_footer();
