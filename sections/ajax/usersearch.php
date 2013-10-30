<?php
/**********************************************************************
 *>>>>>>>>>>>>>>>>>>>>>>>>>>> User search <<<<<<<<<<<<<<<<<<<<<<<<<<<<*
 **********************************************************************/

if (empty($_GET['search'])) {
	json_die("failure", "no search terms");
} else {
	$_GET['username'] = $_GET['search'];
}

define('USERS_PER_PAGE', 30);

if (isset($_GET['username'])) {
	$_GET['username'] = trim($_GET['username']);

	list($Page, $Limit) = Format::page_limit(USERS_PER_PAGE);
	$DB->query("
		SELECT
			SQL_CALC_FOUND_ROWS
			ID,
			Username,
			Enabled,
			PermissionID,
			Donor,
			Warned,
			Avatar
		FROM users_main AS um
			JOIN users_info AS ui ON ui.UserID = um.ID
		WHERE Username LIKE '%".db_string($_GET['username'])."%'
		ORDER BY Username
		LIMIT $Limit");
	$Results = $DB->to_array();
	$DB->query('SELECT FOUND_ROWS();');
	list($NumResults) = $DB->next_record();

}

$JsonUsers = array();
foreach ($Results as $Result) {
	list($UserID, $Username, $Enabled, $PermissionID, $Donor, $Warned, $Avatar) = $Result;

	$JsonUsers[] = array(
		'userId' => (int)$UserID,
		'username' => $Username,
		'donor' => $Donor == 1,
		'warned' => ($Warned != '0000-00-00 00:00:00'),
		'enabled' => ($Enabled == 2 ? false : true),
		'class' => Users::make_class_string($PermissionID),
		'avatar' => $Avatar
	);
}

json_die("success", array(
	'currentPage' => (int)$Page,
	'pages' => ceil($NumResults / USERS_PER_PAGE),
	'results' => $JsonUsers
));
