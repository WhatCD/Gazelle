<?php
list($Page, $Limit) = Format::page_limit(LOG_ENTRIES_PER_PAGE);

if (!empty($_GET['search'])) {
	$Search = db_string($_GET['search']);
} else {
	$Search = false;
}
$Words = explode(' ', $Search);
$sql = '
	SELECT
		SQL_CALC_FOUND_ROWS
		ID,
		Message,
		Time
	FROM log ';
if ($Search) {
	$sql .= "WHERE Message LIKE '%";
	$sql .= implode("%' AND Message LIKE '%", $Words);
	$sql .= "%' ";
}
if (!check_perms('site_view_full_log')) {
	if ($Search) {
		$sql.=' AND ';
	} else {
		$sql.=' WHERE ';
	}
	$sql .= " Time>'".time_minus(3600 * 24 * 28)."' ";
}

$sql .= "
	ORDER BY ID DESC
	LIMIT $Limit";

$Log = $DB->query($sql);
$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();
$TotalMatches = $NumResults;
$DB->set_query_id($Log);
