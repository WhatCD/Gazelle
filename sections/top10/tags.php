<?
// error out on invalid requests (before caching)
if (isset($_GET['details'])) {
	if (in_array($_GET['details'],array('ut','ur','v'))) {
		$Details = $_GET['details'];
	} else {
		error(404);
	}
} else {
	$Details = 'all';
}

View::show_header('Top 10 Tags');
?>
<div class="thin">
	<div class="header">
		<h2>Top 10 Tags</h2>
		<? Top10View::render_linkbox("tags"); ?>
	</div>

<?

// defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, array(10,100,250)) ? $Limit : 10;

if ($Details == 'all' || $Details == 'ut') {
	if (!$TopUsedTags = $Cache->get_value('topusedtag_'.$Limit)) {
		$DB->query("
			SELECT
				t.ID,
				t.Name,
				COUNT(tt.GroupID) AS Uses,
				SUM(tt.PositiveVotes-1) AS PosVotes,
				SUM(tt.NegativeVotes-1) AS NegVotes
			FROM tags AS t
				JOIN torrents_tags AS tt ON tt.TagID=t.ID
			GROUP BY tt.TagID
			ORDER BY Uses DESC
			LIMIT $Limit");
		$TopUsedTags = $DB->to_array();
		$Cache->cache_value('topusedtag_'.$Limit, $TopUsedTags, 3600 * 12);
	}

	generate_tag_table('Most Used Torrent Tags', 'ut', $TopUsedTags, $Limit);
}

if ($Details == 'all' || $Details == 'ur') {
	if (!$TopRequestTags = $Cache->get_value('toprequesttag_'.$Limit)) {
		$DB->query("
			SELECT
				t.ID,
				t.Name,
				COUNT(r.RequestID) AS Uses,
				'',''
			FROM tags AS t
				JOIN requests_tags AS r ON r.TagID=t.ID
			GROUP BY r.TagID
			ORDER BY Uses DESC
			LIMIT $Limit");
		$TopRequestTags = $DB->to_array();
		$Cache->cache_value('toprequesttag_'.$Limit, $TopRequestTags, 3600 * 12);
	}

	generate_tag_table('Most Used Request Tags', 'ur', $TopRequestTags, $Limit, false, true);
}

if ($Details == 'all' || $Details == 'v') {
	if (!$TopVotedTags = $Cache->get_value('topvotedtag_'.$Limit)) {
		$DB->query("
			SELECT
				t.ID,
				t.Name,
				COUNT(tt.GroupID) AS Uses,
				SUM(tt.PositiveVotes-1) AS PosVotes,
				SUM(tt.NegativeVotes-1) AS NegVotes
			FROM tags AS t
				JOIN torrents_tags AS tt ON tt.TagID=t.ID
			GROUP BY tt.TagID
			ORDER BY PosVotes DESC
			LIMIT $Limit");
		$TopVotedTags = $DB->to_array();
		$Cache->cache_value('topvotedtag_'.$Limit, $TopVotedTags, 3600 * 12);
	}

	generate_tag_table('Most Highly Voted Tags', 'v', $TopVotedTags, $Limit);
}

echo '</div>';
View::show_footer();
exit;

// generate a table based on data from most recent query to $DB
function generate_tag_table($Caption, $Tag, $Details, $Limit, $ShowVotes = true, $RequestsTable = false) {
	if ($RequestsTable) {
		$URLString = 'requests.php?tags=';
	} else {
		$URLString = 'torrents.php?taglist=';
	}
?>
	<h3>Top <?=$Limit.' '.$Caption?>
		<small class="top10_quantity_links">
<?
	switch ($Limit) {
		case 100: ?>
			- <a href="top10.php?type=tags&amp;details=<?=$Tag?>" class="brackets">Top 10</a>
			- <span class="brackets">Top 100</span>
			- <a href="top10.php?type=tags&amp;limit=250&amp;details=<?=$Tag?>" class="brackets">Top 250</a>
		<?	break;
		case 250: ?>
			- <a href="top10.php?type=tags&amp;details=<?=$Tag?>" class="brackets">Top 10</a>
			- <a href="top10.php?type=tags&amp;limit=100&amp;details=<?=$Tag?>" class="brackets">Top 100</a>
			- <span class="brackets">Top 250</span>
		<?	break;
		default: ?>
			- <span class="brackets">Top 10</span>
			- <a href="top10.php?type=tags&amp;limit=100&amp;details=<?=$Tag?>" class="brackets">Top 100</a>
			- <a href="top10.php?type=tags&amp;limit=250&amp;details=<?=$Tag?>" class="brackets">Top 250</a>
<?	} ?>
		</small>
	</h3>
	<table class="border">
	<tr class="colhead">
		<td class="center">Rank</td>
		<td>Tag</td>
		<td style="text-align: right;">Uses</td>
<?	if ($ShowVotes) {	?>
		<td style="text-align: right;">Pos. votes</td>
		<td style="text-align: right;">Neg. votes</td>
<?	}	?>
	</tr>
<?
	// in the unlikely event that query finds 0 rows...
	if (empty($Details)) {
		echo '
		<tr class="rowb">
			<td colspan="9" class="center">
				Found no tags matching the criteria
			</td>
		</tr>
		</table><br />';
		return;
	}
	$Rank = 0;
	foreach ($Details as $Detail) {
		$Rank++;
		$Highlight = ($Rank % 2 ? 'a' : 'b');

		// print row
?>
	<tr class="row<?=$Highlight?>">
		<td class="center"><?=$Rank?></td>
		<td><a href="<?=$URLString?><?=$Detail['Name']?>"><?=$Detail['Name']?></a></td>
		<td class="number_column"><?=number_format($Detail['Uses'])?></td>
<?		if ($ShowVotes) { ?>
		<td class="number_column"><?=number_format($Detail['PosVotes'])?></td>
		<td class="number_column"><?=number_format($Detail['NegVotes'])?></td>
<?		} ?>
	</tr>
<?
	}
	echo '</table><br />';
}
?>
