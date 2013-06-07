<?
/*
 * $_REQUEST['type']:
 *  created = comments left on one's collages
 *  contributed = comments left on collages one contributed to
 *  * = one's request comments
 */

$Mode = 'normal';

$ExtraJoin = $Conditions = '';
$Conditions = "c.Deleted = '0' AND ";
if (!empty($_REQUEST['type'])) {
	if ($_REQUEST['type'] == 'created') {
		$Conditions .= "c.UserID = $UserID AND cc.UserID != $UserID";
		$Title = 'Comments left on collages ' . ($Self ? 'you' : $Username) . ' created';
		$Header = 'Comments left on collages ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false)) . ' created';
		$Mode = 'created';
	} elseif ($_REQUEST['type'] == 'contributed') {
		$Conditions .= "IF(c.CategoryID = " . array_search('Artists', $CollageCats) . ", ca.ArtistID, ct.GroupID) IS NOT NULL AND cc.UserID != $UserID";
		$ExtraJoin .=
		"LEFT JOIN collages_torrents as ct ON ct.CollageID = c.ID AND ct.UserID = $UserID
		LEFT JOIN collages_artists as ca ON ca.CollageID = c.ID AND ca.UserID = $UserID";
		$Title = 'Comments left on collages ' . ($Self ? 'you\'ve' : $Username . ' has') . ' contributed to';
		$Header = 'Comments left on collages ' . ($Self ? 'you\'ve' : Users::format_username($UserID, false, false, false).' has') . ' contributed to';
		$Mode = 'contributed';
	}
}
if (!isset($Title)) {
	$Conditions .= "cc.UserID = $UserID";
	$Title = 'Collage comments made by ' . ($Self ? 'you' : $Username);
	$Header = 'Collage comments made by ' . ($Self ? 'you' : Users::format_username($UserID, false, false, false));
}

$Comments = $DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		cc.UserID,
		c.ID as CollageID,
		c.Name,
		cc.ID as PostID,
		cc.Body,
		cc.Time
	FROM collages as c
		JOIN collages_comments as cc ON cc.CollageID = c.ID
		$ExtraJoin
	WHERE $Conditions
	GROUP BY cc.ID
	ORDER BY cc.ID DESC
	LIMIT $Limit;");
$Count = $DB->record_count();

$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$Pages = Format::get_pages($Page, $Results, $PerPage, 11);

View::show_header($Title,'bbcode');
$DB->set_query_id($Comments);

$Links = array();
$BaseLink = 'comments.php?action=collages' . (!$Self ? '&amp;id='.$UserID : '');
if ($Mode != 'normal') {
	$Links[] = '<a href="' . $BaseLink . '" class="brackets">Display collage comments ' . ($Self ? 'you\'ve' : $Username . ' has') . ' made</a>';
}
if ($Mode != 'created') {
	$Links[] = '<a href="' . $BaseLink . '&amp;type=created" class="brackets">Display comments left on ' . ($Self ? 'your collages' : 'collages created by ' .$Username) . '</a>';
}
if ($Mode != 'contributed') {
	$Links[] = '<a href="' . $BaseLink . '&amp;type=contributed" class="brackets">Display comments left on collages ' . ($Self ? 'you\'ve' : $Username . ' has') . ' contributed to</a>';
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
	while (list($UserID, $CollageID, $Name, $PostID, $Body, $AddedTime) = $DB->next_record()) {
		$permalink = "collages.php?action=comments&collageid=$CollageID&amp;postid=$PostID#post$PostID";
		$postheader = " on <a href=\"collages.php?id=$CollageID\">$Name</a>";
		comment_body($UserID, $PostID, $postheader, $permalink, $Body, 0, $AddedTime, 0);
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
