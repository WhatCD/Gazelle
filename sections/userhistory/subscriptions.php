<?
/*
User subscription page
*/

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}
list($Page, $Limit) = Format::page_limit($PerPage);

View::show_header('Subscriptions','subscriptions,comments,bbcode');

$ShowUnread = (!isset($_GET['showunread']) && !isset($HeavyInfo['SubscriptionsUnread']) || isset($HeavyInfo['SubscriptionsUnread']) && !!$HeavyInfo['SubscriptionsUnread'] || isset($_GET['showunread']) && !!$_GET['showunread']);
$ShowCollapsed = (!isset($_GET['collapse']) && !isset($HeavyInfo['SubscriptionsCollapse']) || isset($HeavyInfo['SubscriptionsCollapse']) && !!$HeavyInfo['SubscriptionsCollapse'] || isset($_GET['collapse']) && !!$_GET['collapse']);

// The monster sql query:
/*
 * Fields:
 * Page (artist, collages, requests, torrents or forums)
 * PageID (ArtistID, CollageID, RequestID, GroupID, TopicID)
 * PostID (of the last read post)
 * ForumID
 * ForumName
 * Name (for artists and collages; carries the topic title for forum subscriptions)
 * LastPost (PostID of the last post)
 * LastPostTime
 * LastReadBody
 * LastReadEditedTime
 * LastReadUserID
 * LastReadUsername
 * LastReadAvatar
 * LastReadEditedUserID
 */
$DB->query("
	(SELECT
		SQL_CALC_FOUND_ROWS
		s.Page,
		s.PageID,
		lr.PostID,
		null AS ForumID,
		null AS ForumName,
		IF(s.Page = 'artist', a.Name, co.Name) AS Name,
		c.ID AS LastPost,
		c.AddedTime AS LastPostTime,
		c_lr.Body AS LastReadBody,
		c_lr.EditedTime AS LastReadEditedTime,
		um.ID AS LastReadUserID,
		um.Username AS LastReadUsername,
		ui.Avatar AS LastReadAvatar,
		c_lr.EditedUserID AS LastReadEditedUserID
	FROM users_subscriptions_comments AS s
		LEFT JOIN users_comments_last_read AS lr ON lr.UserID = $LoggedUser[ID] AND lr.Page = s.Page AND lr.PageID = s.PageID
		LEFT JOIN artists_group AS a ON s.Page = 'artist' AND a.ArtistID = s.PageID
		LEFT JOIN collages AS co ON s.Page = 'collages' AND co.ID = s.PageID
		LEFT JOIN comments AS c ON c.ID = (
					SELECT MAX(ID)
					FROM comments
					WHERE Page = s.Page
						AND PageID = s.PageID
				)
		LEFT JOIN comments AS c_lr ON c_lr.ID = lr.PostID
		LEFT JOIN users_main AS um ON um.ID = c_lr.AuthorID
		LEFT JOIN users_info AS ui ON ui.UserID = um.ID
	WHERE s.UserID = $LoggedUser[ID] AND s.Page IN ('artist', 'collages', 'requests', 'torrents') AND (s.Page != 'collages' OR co.Deleted = '0')" . ($ShowUnread ? ' AND c.ID > IF(lr.PostID IS NULL, 0, lr.PostID)' : '') . "
	GROUP BY s.PageID)
	UNION ALL
	(SELECT 'forums', s.TopicID, lr.PostID, f.ID, f.Name, t.Title, p.ID, p.AddedTime, p_lr.Body, p_lr.EditedTime, um.ID, um.Username, ui.Avatar, p_lr.EditedUserID
	FROM users_subscriptions AS s
		LEFT JOIN forums_last_read_topics AS lr ON lr.UserID = $LoggedUser[ID] AND s.TopicID = lr.TopicID
		LEFT JOIN forums_topics AS t ON t.ID = s.TopicID
		LEFT JOIN forums AS f ON f.ID = t.ForumID
		LEFT JOIN forums_posts AS p ON p.ID = (
					SELECT MAX(ID)
					FROM forums_posts
					WHERE TopicID = s.TopicID
				)
		LEFT JOIN forums_posts AS p_lr ON p_lr.ID = lr.PostID
		LEFT JOIN users_main AS um ON um.ID = p_lr.AuthorID
		LEFT JOIN users_info AS ui ON ui.UserID = um.ID
	WHERE s.UserID = $LoggedUser[ID]" .
		($ShowUnread ? " AND p.ID > IF(t.IsLocked = '1' AND t.IsSticky = '0'" . ", p.ID, IF(lr.PostID IS NULL, 0, lr.PostID))" : '') .
		' AND ' . Forums::user_forums_sql() . "
	GROUP BY t.ID)
	ORDER BY LastPostTime DESC
	LIMIT $Limit");
$Results = $DB->to_array(false, MYSQLI_ASSOC, false);
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();

$Debug->log_var($Results, 'Results');

$TorrentGroups = $Requests = array();
foreach ($Results as $Result) {
	if ($Result['Page'] == 'torrents') {
		$TorrentGroups[] = $Result['PageID'];
	} elseif ($Result['Page'] == 'requests') {
		$Requests[] = $Result['PageID'];
	}
}

$TorrentGroups = Torrents::get_groups($TorrentGroups, true, true, false);
$Requests = Requests::get_requests($Requests);

?>
<div class="thin">
	<div class="header">
		<h2>Subscriptions<?=$ShowUnread ? ' with unread posts' . ($NumResults ? ' (' . $NumResults . ' new)' : '') : ''?></h2>

		<div class="linkbox">
<?
if (!$ShowUnread) {
?>
			<br /><br />
			<a href="userhistory.php?action=subscriptions&amp;showunread=1" class="brackets">Only display subscriptions with unread replies</a>&nbsp;&nbsp;&nbsp;
<?
} else {
?>
			<br /><br />
			<a href="userhistory.php?action=subscriptions&amp;showunread=0" class="brackets">Show all subscriptions</a>&nbsp;&nbsp;&nbsp;
<?
}
if ($NumResults) {
?>
			<a href="#" onclick="Collapse(); return false;" id="collapselink" class="brackets"><?=$ShowCollapsed ? 'Show' : 'Hide' ?> post bodies</a>&nbsp;&nbsp;&nbsp;
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
		No subscriptions<?=$ShowUnread ? ' with unread posts' : ''?>
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
	foreach ($Results as $Result) {
		switch ($Result['Page']) {
			case 'artist':
				$Links = 'Artist: <a href="artist.php?id=' . $Result['PageID'] . '">' . display_str($Result['Name']) . '</a>';
				$JumpLink = 'artist.php?id=' . $Result['PageID'] . '&amp;postid=' . $Result['PostID'] . '#post' . $Result['PostID'];
				break;
			case 'collages':
				$Links = 'Collage: <a href="collages.php?id=' . $Result['PageID'] . '">' . display_str($Result['Name']) . '</a>';
				$JumpLink = 'collages.php?action=comments&collageid=' . $Result['PageID'] . '&amp;postid=' . $Result['PostID'] . '#post' . $Result['PostID'];
				break;
			case 'requests':
				if (!isset($Requests[$Result['PageID']])) {
					continue;
				}
				$Request = $Requests[$Result['PageID']];
				$CategoryName = $Categories[$CategoryID - 1];

				$Links = 'Request: ';
				if ($CategoryName == 'Music' || $CategoryName == 'Audiobooks' || $CategoryName == 'Comedy') {
					$Links .= ($CategoryName == 'Music' ? Artists::display_artists(Requests::get_artists($Result['PageID'])) : '') . '<a href="requests.php?action=view&amp;id=' . $Result['PageID'] . '" dir="ltr">' . $Request['Title'] . " [" . $Request['Year'] . "]</a>";
				} else {
					$Links .= '<a href="requests.php?action=view&amp;id=' . $Result['PageID'] . '">' . $Request['Title'] . "</a>";
				}
				$JumpLink = 'requests.php?action=view&amp;id=' . $Result['PageID'] . '&amp;postid=' . $Result['PostID'] . '#post' . $Result['PostID'];
				break;
			case 'torrents':
				if (!isset($TorrentGroups[$Result['PageID']])) {
					continue;
				}
				$GroupInfo = $TorrentGroups[$Result['PageID']];
				$Links = 'Torrent: ' . Artists::display_artists($GroupInfo['ExtendedArtists']) . '<a href="torrents.php?id=' . $GroupInfo['ID'] . '" dir="ltr">' . $GroupInfo['Name'] . '</a>';
				if ($GroupInfo['Year'] > 0) {
					$Links .= " [" . $GroupInfo['Year'] . "]";
				}
				if ($GroupInfo['ReleaseType'] > 0) {
					$Links .= " [" . $ReleaseTypes[$GroupInfo['ReleaseType']] . "]";
				}
				$JumpLink = 'torrents.php?id=' . $GroupInfo['ID'] . '&amp;postid=' . $Result['PostID'] . '#post' . $Result['PostID'];
				break;
			case 'forums':
				$Links = 'Forums: <a href="forums.php?action=viewforum&amp;forumid=' . $Result['ForumID'] . '">' . display_str($Result['ForumName']) . '</a> &gt; ' .
					'<a href="forums.php?action=viewthread&amp;threadid=' . $Result['PageID'] .
						'" class="tooltip" title="' . display_str($Result['Name']) . '">' .
						display_str(Format::cut_string($Result['Name'], 75)) .
					'</a>';
				$JumpLink = 'forums.php?action=viewthread&amp;threadid=' . $Result['PageID'] . '&amp;postid=' . $Result['PostID'] . '#post' . $Result['PostID'];
				break;
			default:
				error(0);
		}
?>
	<table class="forum_post box vertical_margin<?=(!Users::has_avatars_enabled() ? ' noavatar' : '')?>">
		<colgroup>
<?		if (Users::has_avatars_enabled()) { ?>
			<col class="col_avatar" />
<?		} ?>
			<col class="col_post_body" />
		</colgroup>
		<tr class="colhead_dark notify_<?=$Result['Page']?>">
			<td colspan="<?=Users::has_avatars_enabled() ? 2 : 1 ?>">
				<span style="float: left;">
					<?=$Links . ($Result['PostID'] < $Result['LastPost'] ? ' <span class="new">(New!)</span>' : '')?>
				</span>
				<span style="float: left;" class="tooltip last_read" title="Jump to last read">
					<a href="<?=$JumpLink?>"></a>
				</span>
<?		if ($Result['Page'] == 'forums') { ?>
				<span id="bar<?=$Result['PostID'] ?>" style="float: right;">
					<a href="#" onclick="Subscribe(<?=$Result['PageID']?>); return false;" id="subscribelink<?=$Result['PageID']?>" class="brackets">Unsubscribe</a>
<?		} else { ?>
				<span id="bar_<?=$Result['Page'] . $Result['PostID'] ?>" style="float: right;">
					<a href="#" onclick="SubscribeComments('<?=$Result['Page']?>', <?=$Result['PageID']?>); return false;" id="subscribelink_<?=$Result['Page'] . $Result['PageID']?>" class="brackets">Unsubscribe</a>
<?		} ?>
					&nbsp;
					<a href="#">&uarr;</a>
				</span>
			</td>
		</tr>
<?		if (!empty($Result['LastReadBody'])) { // if a user is subscribed to a topic/comments but hasn't accessed the site ever, LastReadBody will be null - in this case we don't display a post. ?>
		<tr class="row<?=$ShowCollapsed ? ' hidden' : '' ?>">
<?			if (Users::has_avatars_enabled()) { ?>
			<td class="avatar" valign="top">
				<?=Users::show_avatar($Result['LastReadAvatar'], $Result['LastReadUserID'], $Result['LastReadUsername'], $HeavyInfo['DisableAvatars'])?>
			</td>
<?			} ?>
			<td class="body" valign="top">
				<div class="content3">
					<?=Text::full_format($Result['LastReadBody']) ?>
<?			if ($Result['LastReadEditedUserID']) { ?>
					<br /><br />
					Last edited by <?=Users::format_username($Result['LastReadEditedUserID'], false, false, false) ?> <?=time_diff($Result['LastReadEditedTime'])?>
<?			} ?>
				</div>
			</td>
		</tr>
<?		} ?>
	</table>
<?	} ?>
	<div class="linkbox">
<?=$Pages?>
	</div>
<? }?>
</div>
<?
View::show_footer();
