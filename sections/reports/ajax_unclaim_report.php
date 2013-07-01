<?php

if (!check_perms('site_moderate_forums') || empty($_POST['id']) || empty($_POST['remove'])) {
	print
		json_encode(
			array(
				'status' => 'failure'
			)
		);
	die();
}
$ID = (int)$_POST['id'];
$DB->query("UPDATE reports SET ClaimerID = '0' WHERE ID = '$ID'");
print
	json_encode(
		array(
			'status' => 'success',
		)
	);
die();
