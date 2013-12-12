<?
enforce_login();

// Get user level
$DB->query("
	SELECT
		i.SupportFor,
		p.DisplayStaff
	FROM users_info AS i
		JOIN users_main AS m ON m.ID = i.UserID
		JOIN permissions AS p ON p.ID = m.PermissionID
	WHERE i.UserID = ".$LoggedUser['ID']
);
list($SupportFor, $DisplayStaff) = $DB->next_record();

if (!$IsFLS) {
	// Logged in user is not FLS or Staff
	error(403);
}

if ($ID = (int)$_GET['id']) {
	$DB->query("
		SELECT Message
		FROM staff_pm_responses
		WHERE ID = $ID");
	list($Message) = $DB->next_record();
	if ($_GET['plain'] == 1) {
		echo $Message;
	} else {
		echo Text::full_format($Message);
	}

} else {
	// No ID
	echo '-1';
}
?>
