<?php

if (!check_perms('site_moderate_forums') || empty($_POST['id'])) {
	print
		json_encode(
			array(
				'status' => 'failure'
			)
		);
	die();
}

$ID = (int)$_POST['id'];
$DB->query("
	SELECT ClaimerID
	FROM reports
	WHERE ID = '$ID'");
list($ClaimerID) = $DB->next_record();
if ($ClaimerID) {
	print
		json_encode(
			array(
				'status' => 'dupe'
			)
		);
	die();
} else {
	$UserID = $LoggedUser['ID'];
	$DB->query("
		UPDATE reports
		SET ClaimerID = '$UserID'
		WHERE ID = '$ID'");
	print
		json_encode(
			array(
				'status' => 'success',
				'username' => $LoggedUser['Username']
			)
		);
	die();
}
