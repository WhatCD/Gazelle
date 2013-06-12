<?php

if (!empty($_REQUEST['action'])) {
	if ($_REQUEST['action'] == 'my_torrents') {
		$MyTorrents = true;
	} elseif ($_REQUEST['action'] == 'torrents') {
		$MyTorrents = false;
	} else {
		error(404);
	}
} else {
	$MyTorrents = false;
}

$OtherLink = '<a href="comments.php?action=artists" class="brackets">Artist comments</a> <a href="comments.php?action=collages" class="brackets">Collage comments</a> <a href="comments.php?action=requests" class="brackets">Request comments</a><br />';

if ($MyTorrents) {
	$Conditions = "WHERE t.UserID = $UserID AND tc.AuthorID != t.UserID AND tc.AddedTime > t.Time";
	$Title = 'Comments left on your torrents';
	$Header = 'Comments left on your uploads';
	if ($Self) {
		$OtherLink .= '<a href="comments.php?action=torrents" class="brackets">Display comments you have made</a>';
	}
} else {
	$Conditions = "WHERE tc.AuthorID = $UserID";
	$Title = 'Comments made by '.($Self?'you':$Username);
	$Header = 'Torrent comments left by '.($Self?'you':Users::format_username($UserID, false, false, false)).'';
	if ($Self) {
		$OtherLink .= '<a href="comments.php?action=my_torrents" class="brackets">Display comments left on your uploads</a>';
	}
}

$Comments = $DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		tc.AuthorID,
		t.ID,
		t.GroupID,
		tg.Name,
		tc.ID,
		tc.Body,
		tc.AddedTime,
		tc.EditedTime,
		tc.EditedUserID as EditorID
	FROM torrents as t
		JOIN torrents_comments as tc ON tc.GroupID = t.GroupID
		JOIN torrents_group as tg ON t.GroupID = tg.ID
	$Conditions
	GROUP BY tc.ID
	ORDER BY tc.AddedTime DESC
	LIMIT $Limit;
");

$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$Pages = Format::get_pages($Page, $Results, $PerPage, 11);

$DB->set_query_id($Comments);
$GroupIDs = $DB->collect('GroupID');

$Artists = Artists::get_artists($GroupIDs);

View::show_header($Title,'bbcode');
$DB->set_query_id($Comments);

?><div class="thin">
	<div class="header">
		<h2><?=$Header?></h2>
<? if ($OtherLink !== '') { ?>
		<div class="linkbox">
			<?=$OtherLink?>
		</div>
<? } ?>
	</div>
	<div class="linkbox">
		<?=$Pages?>
	</div>
<?
while (list($UserID, $TorrentID, $GroupID, $Title, $PostID, $Body, $AddedTime, $EditedTime, $EditorID) = $DB->next_record()) {
	$permalink = "torrents.php?id=$GroupID&amp;postid=$PostID#post$PostID";
	$postheader = ' on ' . Artists::display_artists($Artists[$GroupID]) . " <a href=\"torrents.php?id=$GroupID\">$Title</a>";

	comment_body($UserID, $PostID, $postheader, $permalink, $Body, $EditorID, $AddedTime, $EditedTime);

} /* end while loop*/ ?>
	<div class="linkbox"><?=($Pages)?></div>
</div>
<?

View::show_footer();

