<?
/*
User topic subscription page
*/

if(!empty($LoggedUser['DisableForums'])) {
	error(403);
}

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}
list($Page,$Limit) = page_limit($PerPage);

show_header('Subscribed topics','subscriptions,bbcode');
if(($UserSubscriptions = $Cache->get_value('subscriptions_user_'.$LoggedUser['ID'])) === FALSE) {
	$DB->query('SELECT TopicID FROM users_subscriptions WHERE UserID = '.$LoggedUser['ID']);
	if($UserSubscriptions = $DB->collect(0)) {
		$Cache->cache_value('subscriptions_user_'.$LoggedUser['ID'],$UserSubscriptions,0);
	}
}
$ShowUnread = (!isset($_GET['showunread']) && !isset($HeavyInfo['SubscriptionsUnread']) || isset($HeavyInfo['SubscriptionsUnread']) && !!$HeavyInfo['SubscriptionsUnread'] || isset($_GET['showunread']) && !!$_GET['showunread']);
$ShowCollapsed = (!isset($_GET['collapse']) && !isset($HeavyInfo['SubscriptionsCollapse']) || isset($HeavyInfo['SubscriptionsCollapse']) && !!$HeavyInfo['SubscriptionsCollapse'] || isset($_GET['collapse']) && !!$_GET['collapse']);
if(!empty($UserSubscriptions)) {
	$sql = "SELECT
		SQL_CALC_FOUND_ROWS
		MAX(p.ID) AS ID
		FROM forums_posts AS p
		LEFT JOIN forums_last_read_topics AS l ON l.UserID = ".$LoggedUser['ID']." AND p.TopicID = l.TopicID
		LEFT JOIN forums_topics AS t ON t.ID = p.TopicID
		LEFT JOIN forums AS f ON f.ID = t.ForumID
		WHERE t.ID IN (".implode(',',$UserSubscriptions).")
		AND p.ID <= IF(l.PostID IS NULL
				OR l.PostID>t.LastPostID,
			t.LastPostID,
			l.PostID)
		AND f.MinClassRead<=".$LoggedUser['Class'];
	if($ShowUnread) {
		$sql .= "
		AND IF(l.PostID IS NULL OR (t.IsLocked = '1' && t.IsSticky = '0'), t.LastPostID, l.PostID)<t.LastPostID";
	}
	$sql .= "
		GROUP BY t.ID
		ORDER BY t.LastPostID DESC
		LIMIT ".$Limit;
	$PostIDs = $DB->query($sql);
	$DB->query("SELECT FOUND_ROWS()");
	list($NumResults) = $DB->next_record();

	if($NumResults > $PerPage*($Page-1)) {
		$DB->set_query_id($PostIDs);
		$PostIDs = $DB->collect('ID');
		$sql = 'SELECT
			f.ID AS ForumID,
			f.Name AS ForumName,
			p.TopicID,
			t.Title,
			p.Body,
			t.LastPostID,
			t.IsLocked,
			t.IsSticky,
			p.ID,
			IFNULL((SELECT COUNT(ID)
				FROM forums_posts
				WHERE forums_posts.TopicID=p.TopicID
				AND forums_posts.ID <= p.ID),1)
				AS LastReadNum,
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
			WHERE p.ID IN ('.implode(',',$PostIDs).')
			ORDER BY f.Name ASC, t.LastPostID DESC';
		$DB->query($sql);
	}
} else {
	$NumResults = 0;
}
?>
<div class="thin">
	<h2><?='Subscribed topics'.($ShowUnread?' with unread posts':'')?></h2>

	<div class="linkbox">
<?
if(!$ShowUnread) {
?>
			<br /><br />
			<a href="userhistory.php?action=subscriptions&amp;showunread=1">Only display topics with unread replies</a>&nbsp;&nbsp;&nbsp;
<?
} else {
?>
			<br /><br />
			<a href="userhistory.php?action=subscriptions&amp;showunread=0">Show all subscribed topics</a>&nbsp;&nbsp;&nbsp;
<?
}
if($NumResults) {
?>
			<a href="#" onclick="Collapse();return false;" id="collapselink"><?=$ShowCollapsed?'Show':'Hide'?> post bodies</a>&nbsp;&nbsp;&nbsp;
<?
}
?>
			<a href="userhistory.php?action=catchup&amp;auth=<?=$LoggedUser['AuthKey']?>">Catch up</a>&nbsp;&nbsp;&nbsp;
			<a href="userhistory.php?action=posts&amp;userid=<?=$LoggedUser['ID']?>">Go to post history</a>&nbsp;&nbsp;&nbsp;
	</div>
<?
if(!$NumResults) {
?>
	<div class="center">
		No subscribed topics<?=$ShowUnread?' with unread posts':''?>
	</div>
<?
} else {
?>
	<div class="linkbox">
<?
	$Pages=get_pages($Page,$NumResults,$PerPage, 11);
	echo $Pages;
?>
	</div>
<?
	while(list($ForumID, $ForumName, $TopicID, $ThreadTitle, $Body, $LastPostID, $Locked, $Sticky, $PostID, $LastReadNum, $AuthorID, $AuthorName, $AuthorAvatar, $EditedUserID, $EditedTime, $EditedUsername) = $DB->next_record()){
?>
	<table class='forum_post box vertical_margin<?=$HeavyInfo['DisableAvatars'] ? ' noavatar' : ''?>'>
		<tr class='colhead_dark'>
			<td colspan="2">
				<span style="float:left;">
					<a href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>"><?=$ForumName?></a> &gt;
					<a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>" title="<?=display_str($ThreadTitle)?>"><?=cut_string($ThreadTitle, 75)?></a>
		<? if($PostID<$LastPostID && !$Locked) { ?>
					<span style="color: red;">(New!)</span>
		<? } ?>
				</span>
				<span style="float:left;" class="last_read" title="Jump to last read">
					<a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID.($PostID?'&amp;post='.$LastReadNum.'#post'.$PostID:'')?>"></a>
				</span>
				<span id="bar<?=$PostID ?>" style="float:right;">
					<a href="#" onclick="Subscribe(<?=$TopicID?>);return false;" id="subscribelink<?=$TopicID?>">[Unsubscribe]</a>
					&nbsp;
					<a href="#">&uarr;</a>
				</span>
			</td>
		</tr>
		<tr class="row<?=$ShowCollapsed?' hidden':''?>">
		<? if(empty($HeavyInfo['DisableAvatars'])) { ?>
			<td class='avatar' valign="top">
			<? if(check_perms('site_proxy_images') && preg_match('/^https?:\/\/(localhost(:[0-9]{2,5})?|[0-9]{1,3}(\.[0-9]{1,3}){3}|([a-zA-Z0-9\-\_]+\.)+([a-zA-Z]{1,5}[^\.]))(:[0-9]{2,5})?(\/[^<>]+)+\.(jpg|jpeg|gif|png|tif|tiff|bmp)$/is',$AuthorAvatar)) { ?>
				<img src="<?='http://'.SITE_URL.'/image.php?c=1&i='.urlencode($AuthorAvatar)?>" width="150" style="max-height:400px;" alt="<?=$AuthorName?>'s avatar" />
			<? } elseif(!$AuthorAvatar) { ?>
				<img src="<?=STATIC_SERVER.'common/avatars/default.png'?>" width="150" style="max-height:400px;" alt="Default avatar" />
			<? } else { ?>
				<img src="<?=$AuthorAvatar?>" width="150" style="max-height:400px;" alt="<?=$AuthorName?>'s avatar" />
			<? } ?>
			</td>
		<? } ?>
			<td class='body' valign="top">
				<div class="content3">
					<?=$Text->full_format($Body) ?>
		<? if($EditedUserID) { ?>
					<br /><br />
					Last edited by
					<?=format_username($EditedUserID, $EditedUsername) ?> <?=time_diff($EditedTime)?>
		<? } ?>
				</div>
			</td>
		</tr>
	</table>
	<? } // while(list(...)) ?>
	<div class="linkbox">
<?=$Pages?>
	</div>
<? } // else -- if(empty($NumResults)) ?>
</div>
<?

show_footer();

?>
