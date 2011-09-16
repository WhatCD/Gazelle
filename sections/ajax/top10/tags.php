<?

authorize();

// error out on invalid requests (before caching)
if(isset($_GET['details'])) {
	if(in_array($_GET['details'],array('ut','ur','v'))) {
		$Details = $_GET['details'];
	} else {
		print json_encode(array('status' => 'failure'));
		die();
	}
} else {
	$Details = 'all';
}

// defaults to 10 (duh)
$Limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$Limit = in_array($Limit, array(10, 100, 250)) ? $Limit : 10;
$OuterResults = array();

if ($Details == 'all' || $Details == 'ut') {
	if (!$TopUsedTags = $Cache->get_value('topusedtag_'.$Limit)) {
		$DB->query("SELECT
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
		$Cache->cache_value('topusedtag_'.$Limit,$TopUsedTags,3600*12);
	}

	$OuterResults[] = generate_tag_json('Most Used Torrent Tags', 'ut', $TopUsedTags, $Limit);
}

if ($Details == 'all' || $Details == 'ur') {
	if (!$TopRequestTags = $Cache->get_value('toprequesttag_'.$Limit)) {
		$DB->query("SELECT
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
		$Cache->cache_value('toprequesttag_'.$Limit,$TopRequestTags,3600*12);
	}

	$OuterResults[] = generate_tag_json('Most Used Request Tags', 'ur', $TopRequestTags, $Limit);
}

if ($Details == 'all' || $Details == 'v') {
	if (!$TopVotedTags = $Cache->get_value('topvotedtag_'.$Limit)) {
		$DB->query("SELECT
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
		$Cache->cache_value('topvotedtag_'.$Limit,$TopVotedTags,3600*12);
	}

	$OuterResults[] = generate_tag_json('Most Highly Voted Tags', 'v', $TopVotedTags, $Limit);
}

print
	json_encode(
		array(
			'status' => 'success',
			'response' => $OuterResults
		)
	);

function generate_tag_json($Caption, $Tag, $Details, $Limit) {
	$results = array();
	foreach ($Details as $Detail) {
		$results[] = array(
			'name' => $Detail['Name'],
			'uses' => $Detail['Uses'],
			'posVotes' => $Detail['PosVotes'],
			'negVotes' => $Detail['NegVotes']
		);
	}

	return array(
		'caption' => $Caption,
		'tag' => $Tag,
		'limit' => $Limit,
		'results' => $results
		);
}
