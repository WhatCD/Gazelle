<?php

$OtherLink = '';

$Title = 'Artist comments made by '.($Self ? 'you' : $Username);
$Header = 'Artist comments left by '.($Self ? 'you' : Users::format_username($UserID, false, false, false)).'';

$Comments = $DB->query("
				SELECT
					SQL_CALC_FOUND_ROWS
					ac.AuthorID,
					a.ArtistID,
					a.Name,
					ac.ID,
					ac.Body,
					ac.AddedTime,
					ac.EditedTime,
					ac.EditedUserID as EditorID
				FROM artists_group as a
					JOIN artist_comments as ac ON ac.ArtistID = a.ArtistID
				WHERE ac.AuthorId = $UserID
				GROUP BY ac.ID
				ORDER BY ac.AddedTime DESC
				LIMIT $Limit;
");

$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$Pages = Format::get_pages($Page, $Results, $PerPage, 11);

$DB->set_query_id($Comments);
$GroupIDs = $DB->collect('GroupID');


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

while (list($UserID, $ArtistID, $ArtistName, $PostID, $Body, $AddedTime, $EditedTime, $EditorID) = $DB->next_record()) {
	$permalink = "artist.php?id=$ArtistID&amp;postid=$PostID#post$PostID";
	$postheader = ' on ' . "<a href=\"artist.php?id=$ArtistID\">$ArtistName</a>";

	comment_body($UserID, $PostID, $postheader, $permalink, $Body, $EditorID, $AddedTime, $EditedTime);

} /* end while loop*/ ?>
	<div class="linkbox"><?=($Pages)?></div>
</div>
<?
View::show_footer();
