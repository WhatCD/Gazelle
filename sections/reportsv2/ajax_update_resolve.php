<?
// perform the back end of updating a resolve type

if (!check_perms('admin_reports')) {
	error(403);
}

if (empty($_GET['newresolve'])) {
	echo "No new resolve";
	die();
}

$ReportID = (int) $_GET['reportid'];
$CategoryID = (int) $_GET['categoryid'];
$NewType = $_GET['newresolve'];

if (!empty($Types[$CategoryID])) {
	$TypeList = $Types['master'] + $Types[$CategoryID];
	$Priorities = array();
	foreach ($TypeList as $Key => $Value) {
		$Priorities[$Key] = $Value['priority'];
	}
	array_multisort($Priorities, SORT_ASC, $TypeList);
} else {
	$TypeList = $Types['master'];
}

if (!array_key_exists($NewType, $TypeList)) {
	echo "No resolve from that category";
	die();
}

$DB->query("
	UPDATE reportsv2
	SET Type = '$NewType'
	WHERE ID = $ReportID");
