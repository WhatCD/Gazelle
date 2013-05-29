<?php
if (!empty($LoggedUser['DisableForums'])) {
	error(403);
}

$UnreadSQL = 'AND q.UnRead';
if ($_GET['showall']) {
	$UnreadSQL = '';
}

if ($_GET['catchup']) {
	$DB->query("UPDATE users_notify_quoted SET UnRead = '0' WHERE UserID = '$LoggedUser[ID]'");
}

list($Page, $Limit) = Format::page_limit(TOPICS_PER_PAGE);

if ($LoggedUser['CustomForums']) {
	unset($LoggedUser['CustomForums']['']);
	$RestrictedForums = implode("','", array_keys($LoggedUser['CustomForums'], 0));
	$PermittedForums = implode("','", array_keys($LoggedUser['CustomForums'], 1));
}
$sql = "
	SELECT
		SQL_CALC_FOUND_ROWS
		f.ID as ForumID,
		f.Name as ForumName,
		t.Title,
		q.PageID,
		q.PostID,
		q.QuoterID
	FROM users_notify_quoted AS q
		LEFT JOIN forums_topics AS t ON t.ID = q.PageID
		LEFT JOIN forums AS f ON f.ID = t.ForumID
	WHERE q.UserID = $LoggedUser[ID]
		AND q.Page = 'forums'
		AND ((f.MinClassRead <= '$LoggedUser[Class]'";

if (!empty($RestrictedForums)) {
	$sql .= ' AND f.ID NOT IN (\'' . $RestrictedForums . '\')';
}
$sql .= ')';
if (!empty($PermittedForums)) {
	$sql .= ' OR f.ID IN (\'' . $PermittedForums . '\')';
}
$sql .= ") $UnreadSQL ORDER BY q.Date DESC LIMIT $Limit";
$DB->query($sql);
$Results = $DB->to_array(false, MYSQLI_ASSOC, false);
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();

//Start printing page
View::show_header('Quote Notifications');
?>
<div class="thin">
	<div class="header">
		<h2>
			Quote notifications
			<?=$NumResults && !empty($UnreadSQL) ? " ($NumResults new)" : '' ?>
		</h2>
		<div class="linkbox pager">
			<br />
<?		if ($UnreadSQL) { ?>
			<a href="userhistory.php?action=quote_notifications&amp;showall=1" class="brackets">Show all quotes</a>&nbsp;&nbsp;&nbsp;
<?		} else { ?>
			<a href="userhistory.php?action=quote_notifications" class="brackets">Show unread quotes</a>&nbsp;&nbsp;&nbsp;
<?		} ?>
			<a href="userhistory.php?action=subscriptions" class="brackets">Show subscriptions</a>&nbsp;&nbsp;&nbsp;
			<a href="userhistory.php?action=quote_notifications&amp;catchup=1" class="brackets">Catch up</a>&nbsp;&nbsp;&nbsp;
			<br /> <br />
<?
			$Pages = Format::get_pages($Page, $NumResults, TOPICS_PER_PAGE, 9);
			echo $Pages;
			?>
		</div>
	</div>
<?
	if (!$NumResults) { ?>
	<div class="center">No<?=($UnreadSQL ? ' new' : '')?> quotes.</div>
<?	} ?>
	<br />
<?
	foreach ($Results as $Result) {
	?>
	<table class="forum_post box vertical_margin noavatar">
		<tr class="colhead_dark">
			<td colspan="2">
				<span style="float: left;">
					<a href="forums.php?action=viewforum&amp;forumid=<?=$Result['ForumID'] ?>"><?=$Result['ForumName'] ?></a>
					&gt;
					<a href="forums.php?action=viewthread&amp;threadid=<?=$Result['PageID'] ?>" title="<?=display_str($Result['Title']) ?>"><?=Format::cut_string($Result['Title'], 75) ?></a>
					&gt; Quoted by <?=Users::format_username($Result['QuoterID'], false, false, false, false) ?>
				</span>
				<span style="float: left;" class="last_read" title="Jump to quote">
					<a href="forums.php?action=viewthread&amp;threadid=<?=$Result['PageID'].($Result['PostID'] ? '&amp;postid=' . $Result['PostID'].'#post'.$Result['PostID'] : '') ?>"></a>
				</span>
				<span id="bar<?=$Result['PostID'] ?>" style="float: right;">
					<a href="#">&uarr;</a>
				</span>
			</td>
		</tr>
	</table>
<?	} ?>
</div>
<? View::show_footer(); ?>
