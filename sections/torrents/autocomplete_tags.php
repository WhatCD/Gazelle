<?
header('Content-Type: application/json; charset=utf-8');

$FullName = rawurldecode($_GET['query']);

$MaxKeySize = 4;
$KeySize = min($MaxKeySize, max(1, strlen($FullName)));

$Letters = strtolower(substr($FullName, 0, $KeySize));
$AutoSuggest = $Cache->get("autocomplete_tags_{$KeySize}_$Letters");

if (!$AutoSuggest) {
	$Limit = (($KeySize === $MaxKeySize) ? 250 : 10);
	$DB->query("
		SELECT Name
		FROM tags
		WHERE Name != ''
			AND Name LIKE '".db_string(str_replace('\\', '\\\\', $Letters), true)."%'
			AND (Uses > 700 OR TagType = 'genre')
		ORDER BY TagType = 'genre' DESC, Uses DESC
		LIMIT $Limit");
	$AutoSuggest = $DB->to_array(false, MYSQLI_NUM, false);
	$Cache->cache_value("autocomplete_tags_{$KeySize}_$Letters", $AutoSuggest, 1800 + 7200 * ($MaxKeySize - $KeySize)); // Can't cache things for too long in case names are edited
}

$Matched = 0;
$ArtistIDs = array();
$Response = array(
	'query' => $FullName,
	'suggestions' => array()
);
foreach ($AutoSuggest as $Suggestion) {
	list($Name) = $Suggestion;
	if (stripos($Name, $FullName) === 0) {
		$Response['suggestions'][] = array('value' => $Name);
		if (++$Matched > 9) {
			break;
		}
	}
}
echo json_encode($Response);
