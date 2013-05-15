<?
/*
 * $_REQUEST['type']:
 *	created = comments left on one's requests
 *  voted = comments left on requests one voted on
 *  * = one's request comments
 */

$Mode = 'normal';

$ExtraJoin = '';
if (!empty($_REQUEST['type'])) {
	if ($_REQUEST['type'] == 'created') {
		$Conditions = "WHERE r.UserID = $UserID AND rc.AuthorID != r.UserID";
		$Title = 'Comments left on requests ' . ($Self ? 'you' : $Username) . ' created';
		$Header = 'Comments left on requests ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false)) . ' created';
		$Mode = 'created';
	} elseif ($_REQUEST['type'] == 'voted') {
		$Conditions = "WHERE rv.UserID = $UserID AND rc.AuthorID != $UserID";
		$ExtraJoin = 'JOIN requests_votes as rv ON rv.RequestID = r.ID';
		$Title = 'Comments left on requests ' . ($Self ? 'you\'ve' : $Username . ' has') . ' voted on';
		$Header = 'Comments left on requests ' . ($Self ? 'you\'ve' : Users::format_username($UserID, false, false, false).' has') . ' voted on';
		$Mode = 'voted';
	}
}
if (!isset($Title)) {
	$Conditions = "WHERE rc.AuthorID = $UserID";
	$Title = 'Request comments made by ' . ($Self ? 'you' : $Username);
	$Header = 'Request comments made by ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false));
}

$Comments = $DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		rc.AuthorID,
		r.ID as RequestID,
		r.Title,
		rc.ID as PostID,
		rc.Body,
		rc.AddedTime,
		rc.EditedTime,
		rc.EditedUserID as EditorID
	FROM requests as r
		JOIN requests_comments as rc ON rc.RequestID = r.ID
		$ExtraJoin
	$Conditions
	GROUP BY rc.ID
	ORDER BY rc.AddedTime DESC
	LIMIT $Limit;");
$Count = $DB->record_count();

$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$Pages = Format::get_pages($Page, $Results, $PerPage, 11);

View::show_header($Title,'bbcode');
$DB->set_query_id($Comments);

$Links = array();
$BaseLink = 'comments.php?action=requests' . (!$Self ? '&amp;id='.$UserID : '');
if ($Mode != 'normal') {
	$Links[] = '<a href="' . $BaseLink . '" class="brackets">Display request comments you\'ve made</a>';
}
if ($Mode != 'created') {
	$Links[] = '<a href="' . $BaseLink . '&amp;type=created" class="brackets">Display comments left on your requests</a>';
}
if ($Mode != 'voted') {
	$Links[] = '<a href="' . $BaseLink . '&amp;type=voted" class="brackets">Display comments left on requests you\'ve voted on</a>';
}
$Links = implode(' ', $Links);

?><div class="thin">
	<div class="header">
		<h2><?=$Header?></h2>
<? if ($Links !== '') { ?>
		<div class="linkbox">
			<?=$Links?>
		</div>
<? } ?>
	</div>
	<div class="linkbox">
		<?=$Pages?>
	</div>
<?
if ($Count > 0) {
	while (list($UserID, $RequestID, $Title, $PostID, $Body, $AddedTime, $EditedTime, $EditorID) = $DB->next_record()) {
		$Artists = Requests::get_artists($RequestID);
		$permalink = "requests.php?action=view&id=$RequestID&amp;postid=$PostID#post$PostID";
		$postheader = " on " . Artists::display_artists($Artists) . " <a href=\"requests.php?action=view&id=$RequestID\">$Title</a>";
		comment_body($UserID, $PostID, $postheader, $permalink, $Body, $EditorID, $AddedTime, $EditedTime);
		$DB->set_query_id($Comments);
	}
} else { ?>
	<div class="center">No results.</div>
<? } ?>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>
<?
View::show_footer();
