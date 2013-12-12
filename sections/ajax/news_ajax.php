<?
//Don't allow bigger queries than specified below regardless of called function
$SizeLimit = 10;

$Count = (int)$_GET['count'];
$Offset = (int)$_GET['offset'];

if (!isset($_GET['count']) || !isset($_GET['offset']) || $Count <= 0 || $Offset < 0 || $Count > $SizeLimit) {
	json_die('failure');
}

Text::$TOC = true;

global $DB;
$DB->query("
		SELECT
			ID,
			Title,
			Body,
			Time
		FROM news
		ORDER BY Time DESC
		LIMIT $Offset, $Count");
$News = $DB->to_array(false, MYSQLI_NUM, false);

$NewsResponse = array();
foreach ($News as $NewsItem) {
	list($NewsID, $Title, $Body, $NewsTime) = $NewsItem;
	array_push(
		$NewsResponse,
		array(
			$NewsID,
			Text::full_format($Title),
			time_diff($NewsTime),
			Text::full_format($Body)
		)
	);
}

json_die('success', json_encode($NewsResponse));
