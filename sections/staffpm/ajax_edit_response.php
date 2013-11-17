<?
enforce_login();
// Get user level
$DB->query('
	SELECT
		i.SupportFor,
		p.DisplayStaff
	FROM users_info AS i
		JOIN users_main AS m ON m.ID = i.UserID
		JOIN permissions AS p ON p.ID = m.PermissionID
	WHERE i.UserID = '.$LoggedUser['ID']
);
list($SupportFor, $DisplayStaff) = $DB->next_record();

if (!($SupportFor != '' || $DisplayStaff == '1')) {
	// Logged in user is not FLS or Staff
	error(403);
}

if (($Message = db_string($_POST['message'])) && ($Name = db_string($_POST['name']))) {
	$ID = (int)$_POST['id'];
	if (is_numeric($ID)) {
		if ($ID == 0) {
			// Create new response
			$DB->query("
				INSERT INTO staff_pm_responses (Message, Name)
				VALUES ('$Message', '$Name')");
			echo '1';
		} else {
			$DB->query("
				SELECT *
				FROM staff_pm_responses
				WHERE ID = $ID");
			if ($DB->has_results()) {
				// Edit response
				$DB->query("
					UPDATE staff_pm_responses
					SET Message = '$Message', Name = '$Name'
					WHERE ID = $ID");
				echo '2';
			} else {
				// Create new response
				$DB->query("
					INSERT INTO staff_pm_responses (Message, Name)
					VALUES ('$Message', '$Name')");
				echo '1';
			}
		}
	} else {
		// No ID
		echo '-2';
	}

} else {
	// No message/name
	echo '-1';
}
?>
