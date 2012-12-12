<?php

if (!check_perms('site_moderate_forums') || empty($_GET['id']) || empty($_GET['remove'])) {
	print
		json_encode(
			array(
				'status' => 'failure'
			)
		);
	die();
}
$ID = (int)$_GET['id'];
$DB->query("UPDATE reports SET ClaimerID = '0' WHERE ID = '$ID'");
print
	json_encode(
		array(
			'status' => 'success',
		)
	);
die();
