<?
// skipfile
if (!check_perms('users_mod') && !check_perms("site_mark_suggestions")) {
	error(403);
}

View::show_header('Suggestions');

$Categories = array(
		"Dupe Suggestion",
		"Already Implemented",
		"Already Rejected Suggestion/Against The Rules",
		"Awful Suggestion",
		"Suggestion" 
)?>


<h2>Suggestions</h2>

<div class="linkbox">
	<a href="tools.php?action=suggestions&amp;view=scoreboard" class="brackets">Scoreboard</a>
	<a href="tools.php?action=suggestions&amp;view=marked" class="brackets">Open suggestions</a>
	<a href="tools.php?action=suggestions&amp;view=marked&amp;closed=1" class="brackets">Closed suggestions</a>
	<a href="tools.php?action=suggestions&amp;view=marked&amp;all=1" class="brackets">All suggestions</a>
</div>

<?if($_GET['view'] == "scoreboard" || empty($_GET['view'])) { ?>
<div id="marked_suggestion">
<h3>Marked</h3>
<table>
	<tr class="colhead">
		<td>Username</td>
		<td>Count</td>
	</tr>
	<?
	$DB->query("SELECT 
				s.UserID, count(s.ThreadID) AS C 
				FROM marked_suggestions AS s 
				GROUP BY s.UserID ORDER BY c DESC LIMIT 15");
	$Row = 'b';
	while ( list ($UserID, $Count) = $DB->next_record() ) {
		$Row = ($Row == 'a') ? 'b' : 'a';
		?>
        <tr class="row<?=$Row?>">
		<td><?=Users::format_username($UserID)?></td>
		<td><?=number_format($Count)?></td>
	</tr>
		<? }?>
</table>
</div>
<div id="implemented_suggestion">
<h3>Implemented</h3>
<table>
	<tr class="colhead">
		<td>Username</td>
		<td>Count</td>
	</tr>
	<?
	$DB->query("SELECT 
				LastPostAuthorID, count(LastPostAuthorID) AS C 
				FROM forums_topics AS f 
				WHERE ForumID = 63 AND f.Title LIKE '[implemented]%'
				GROUP BY LastPostAuthorID ORDER BY c DESC LIMIT 15");
	$Row = 'b';
	while ( list ($UserID, $Count) = $DB->next_record() ) {
		$Row = ($Row == 'a') ? 'b' : 'a';
		?>
        <tr class="row<?=$Row?>">
		<td><?=Users::format_username($UserID)?></td>
		<td><?=number_format($Count)?></td>
	</tr>
		<? }?>
</table>
</div>
<div id="rejected_suggestion">
<h3>Rejected</h3>
<table>
	<tr class="colhead">
		<td>Username</td>
		<td>Count</td>
	</tr>
	<?
	$DB->query("SELECT
				LastPostAuthorID, count(LastPostAuthorID) AS C 
				FROM forums_topics AS f 
				WHERE ForumID = 63 AND f.Title LIKE '[rejected]%'
				GROUP BY LastPostAuthorID ORDER BY c DESC LIMIT 15");
	$Row = 'b';
	while ( list ($UserID, $Count) = $DB->next_record() ) {
		$Row = ($Row == 'a') ? 'b' : 'a';
		?>
        <tr class="row<?=$Row?>">
		<td><?=Users::format_username($UserID)?></td>
		<td><?=number_format($Count)?></td>
	</tr>
		<? }?>
</table>
</div>
<?
}
elseif ($_GET['view'] == "marked") {
	if(((int) $_GET['all']) == 1) {
		$Forums = "9, 63, 13";
	} elseif(((int) $_GET['closed']) == 1) {
		$Forums = "63, 13";
	}
	else {
		$Forums = 9;
	}
	foreach ($Categories as $Key => $Value) {
$DB->query("SELECT
		f.Title, s.ThreadID, s.URL, s.UserID, s.Notes
		FROM marked_suggestions AS s
		LEFT JOIN forums_topics AS f ON s.ThreadID = f.ID
		WHERE f.ForumID IN ($Forums) AND s.Category = $Key
		ORDER BY f.Title ASC");
		?>
<h3><a href="#" onclick="$('#category_<?=$Key?>').toggle();"><?=$Value?></a> (<?=$DB->record_count()?>)</h3>
<div class="hidden" id="category_<?=$Key?>">
	<table>
		<tr class="colhead">
			<td>Thread title</td>
			<td>Username</td>
			<td>Links</td>
			<td>Notes</td>
		</tr>
<?
		$Row = 'b';
		while ( list ($Title, $ThreadID, $URL, $UserID, $Notes) = $DB->next_record() ) {
			$Row = ($Row == 'a') ? 'b' : 'a';
			?>
        <tr class="row<?=$Row?>">
			<td><a href="forums.php?action=viewthread&amp;threadid=<?=$ThreadID?>"><?=$Title?></a></td>
			<td><?=Users::format_username($UserID)?></td>
			<td><a href="<?=$URL?>"><?=$URL?></a></td>
			<td><?=$Notes?></td>
		</tr>
		<? }?>
</table>
</div>
<?
	}
}
?>
<?

View::show_footer();
?>
