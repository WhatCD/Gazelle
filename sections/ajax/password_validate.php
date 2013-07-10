<?php
$Password = db_string($_POST['password']);
$IsGoodPassword = false;

$DB->query("
	SELECT Password
	FROM bad_passwords
	WHERE Password='$Password'");

if (!$DB->has_results()) {
	$IsGoodPassword = true;
}

echo ($IsGoodPassword ? 'true' : 'false');
exit();
?>


