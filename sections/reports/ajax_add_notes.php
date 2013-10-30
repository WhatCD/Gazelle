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

$Notes = str_replace("<br />", "\n", $_POST['notes']);
$Notes = db_string($Notes);

$DB->query("
	UPDATE reports
	SET Notes = '$Notes'
	WHERE ID = '$ID'");
print
	json_encode(
		array(
			'status' => 'success'
		)
	);
die();
