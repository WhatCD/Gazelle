<?php
$Password = db_string($_POST['password']);
$IsGoodPassword = false;

$DB->query("
	SELECT Password
	FROM bad_passwords
	WHERE Password='$Password'");

if ($DB->record_count() == 0) {
	$IsGoodPassword = true;
}

echo ($IsGoodPassword ? 'true' : 'false');
exit();
?>


