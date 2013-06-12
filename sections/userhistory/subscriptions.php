<?php
/*
User topic subscription page
*/

if (!empty($LoggedUser['DisableForums'])) {
	error(403);
}

include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class
$Text = new TEXT;

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}
list($Page, $Limit) = Format::page_limit($PerPage);

View::show_header('Subscribed topics','subscriptions,bbcode');

if ($LoggedUser['CustomForums']) {
	unset($LoggedUser['CustomForums']['']);
	$RestrictedForums = implode("','", array_keys($LoggedUser['CustomForums'], 0));
	$PermittedForums = implode("','", array_keys($LoggedUser['CustomForums'], 1));
}

$ShowUnread = (!isset($_GET['showunread']) && !isset($HeavyInfo['SubscriptionsUnread']) || isset($HeavyInfo['SubscriptionsUnread']) && !!$HeavyInfo['SubscriptionsUnread'] || isset($_GET['showunread']) && !!$_GET['showunread']);
$ShowCollapsed = (!isset($_GET['collapse']) && !isset($HeavyInfo['SubscriptionsCollapse']) || isset($HeavyInfo['SubscriptionsCollapse']) && !!$HeavyInfo['SubscriptionsCollapse'] || isset($_GET['collapse']) && !!$_GET['collapse']);
$sql = '
	SELECT
		SQL_CALC_FOUND_ROWS
		MAX(p.ID) AS ID
	FROM (	SELECT TopicID
			FROM users_subscriptions
			WHERE UserID = '.$LoggedUser['ID'].'
		) AS s
		LEFT JOIN forums_last_read_topics AS l ON s.TopicID = l.TopicID AND l.UserID = '.$LoggedUser['ID'].'
		JOIN forums_topics AS t ON t.ID = s.TopicID
		JOIN forums_posts AS p ON t.ID = p.TopicID
		JOIN forums AS f ON f.ID = t.ForumID
	WHERE p.ID <= IFNULL(l.PostID,t.LastPostID)
		AND ((f.MinClassRead <= '.$LoggedUser['Class'];
if (!empty($RestrictedForums)) {
	$sql.=' AND f.ID NOT IN (\''.$RestrictedForums.'\')';
}
$sql .= ')';
if (!empty($PermittedForums)) {
	$sql.=' OR f.ID IN (\''.$PermittedForums.'\')';
}
$sql .= ')';
if ($ShowUnread) {

	$sql .= '
		AND IF(l.PostID IS NULL OR (t.IsLocked = \'1\' && t.IsSticky = \'0\'), t.LastPostID, l.PostID) < t.LastPostID';
	$sql .= ' OR (t.AuthorID != '.$LoggedUser['ID'].' AND l.PostID IS NULL)';

}
$sql .= '
	GROUP BY t.ID
	ORDER BY t.LastPostID DESC
	LIMIT '.$Limit;
$PostIDs = $DB->query($sql);
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();

if ($NumResults > $PerPage * ($Page - 1)) {
	$DB->set_query_id($PostIDs);
	$PostIDs = $DB->collect('ID');
	$sql = '
		SELECT
			f.ID AS ForumID,
			f.Name AS ForumName,
			p.TopicID,
			t.Title,
			p.Body,
			t.LastPostID,
			t.IsLocked,
			t.IsSticky,
			p.ID,
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
?>
<div class="thin">
	<div class="header">
		<h2><?='Subscribed topics'.($ShowUnread ? ' with unread posts' : '')?></h2>

		<div class="linkbox">
<?
if (!$ShowUnread) {
?>
			<br /><br />
			<a href="userhistory.php?action=subscriptions&amp;showunread=1" class="brackets">Only display topics with unread replies</a>&nbsp;&nbsp;&nbsp;
<?
} else {
?>
			<br /><br />
			<a href="userhistory.php?action=subscriptions&amp;showunread=0" class="brackets">Show all subscribed topics</a>&nbsp;&nbsp;&nbsp;
<?
}
if ($NumResults) {
?>
			<a href="#" onclick="Collapse();return false;" id="collapselink" class="brackets"><?=$ShowCollapsed ? 'Show' : 'Hide' ?> post bodies</a>&nbsp;&nbsp;&nbsp;
<?
}
?>
			<a href="userhistory.php?action=catchup&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Catch up</a>&nbsp;&nbsp;&nbsp;
			<a href="userhistory.php?action=posts&amp;userid=<?=$LoggedUser['ID']?>" class="brackets">Go to post history</a>&nbsp;&nbsp;&nbsp;
			<a href="userhistory.php?action=quote_notifications" class="brackets">Quote notifications</a>&nbsp;&nbsp;&nbsp;
		</div>
	</div>
<?
if (!$NumResults) {
?>
	<div class="center">
		No subscribed topics<?=$ShowUnread ? ' with unread posts' : '' ?>
	</div>
<?
} else {
?>
	<div class="linkbox">
<?
	$Pages = Format::get_pages($Page, $NumResults, $PerPage, 11);
	echo $Pages;
?>
	</div>
<?
	while (list($ForumID, $ForumName, $TopicID, $ThreadTitle, $Body, $LastPostID, $Locked, $Sticky, $PostID, $AuthorID, $AuthorName, $AuthorAvatar, $EditedUserID, $EditedTime, $EditedUsername) = $DB->next_record()) {
?>
	<table class="forum_post box vertical_margin<?=!Users::has_avatars_enabled() ? ' noavatar' : '' ?>">
		<colgroup>
<?		if (Users::has_avatars_enabled()) { ?>
			<col class="col_avatar" />
<? 		} ?>
			<col class="col_post_body" />
		</colgroup>
		<tr class="colhead_dark">
			<td colspan="<?=Users::has_avatars_enabled() ? 2 : 1 ?>">
				<span style="float: left;">
					<a href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>"><?=$ForumName?></a> &gt;
					<a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>" title="<?=display_str($ThreadTitle)?>"><?=Format::cut_string($ThreadTitle, 75)?></a>
<?		if ($PostID < $LastPostID && !$Locked) { ?>
					<span class="new">(New!)</span>
<?		} ?>
				</span>
				<span style="float: left;" class="last_read" title="Jump to last read">
					<a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID.($PostID ? '&amp;postid='.$PostID.'#post'.$PostID : '')?>"></a>
				</span>
				<span id="bar<?=$PostID ?>" style="float: right;">
					<a href="#" onclick="Subscribe(<?=$TopicID?>);return false;" id="subscribelink<?=$TopicID?>" class="brackets">Unsubscribe</a>
					&nbsp;
					<a href="#">&uarr;</a>
				</span>
			</td>
		</tr>
		<tr class="row<?=$ShowCollapsed ? ' hidden' : '' ?>">
<?		if (Users::has_avatars_enabled()) { ?>
			<td class="avatar" valign="top">
				<?=Users::show_avatar($AuthorAvatar, $AuthorName, $HeavyInfo['DisableAvatars'])?>
			</td>
<?		} ?>
			<td class="body" valign="top">
				<div class="content3">
					<?=$Text->full_format($Body) ?>
<?		if ($EditedUserID) { ?>
					<br /><br />
					Last edited by
					<?=Users::format_username($EditedUserID, false, false, false) ?> <?=time_diff($EditedTime)?>
<?		} ?>
				</div>
			</td>
		</tr>
	</table>
	<? } // while (list(...)) ?>
	<div class="linkbox">
<?=$Pages?>
	</div>
<? } // else -- if (empty($NumResults)) ?>
</div>
<?
View::show_footer();
?>
