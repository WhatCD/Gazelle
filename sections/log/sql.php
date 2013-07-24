<?php
list($Page, $Limit) = Format::page_limit(LOG_ENTRIES_PER_PAGE);

if (!empty($_GET['search'])) {
	$Search = db_string($_GET['search']);
} else {
	$Search = false;
}
$Words = explode(' ', $Search);
$SQL = '
	SELECT
		SQL_CALC_FOUND_ROWS
		ID,
		Message,
		Time
	FROM log ';
if ($Search) {
	$SQL .= "WHERE Message LIKE '%";
	$SQL .= implode("%' AND Message LIKE '%", $Words);
	$SQL .= "%' ";
}
if (!check_perms('site_view_full_log')) {
	if ($Search) {
		$SQL .= ' AND ';
	} else {
		$SQL .= ' WHERE ';
	}
	$SQL .= " Time>'".time_minus(3600 * 24 * 28)."' ";
}

$SQL .= "
	ORDER BY ID DESC
	LIMIT $Limit";

$Log = $DB->query($SQL);
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();
$TotalMatches = $NumResults;
$DB->set_query_id($Log);
