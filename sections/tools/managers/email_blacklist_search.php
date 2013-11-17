<?
$Search = db_string($_GET['email']);
$JSON = array();
if (!check_perms('users_view_email') || empty($Search)) {
	$JSON['status'] = 'error';
	echo json_encode($JSON);
	exit();
}
else {
	$JSON['status'] = 'success';
}

$DB->query("
	SELECT
		ID,
		UserID,
		Time,
		Email,
		Comment
	FROM email_blacklist
	WHERE Email LIKE '%$Search%'");

$EmailResults = $DB->to_array(false, MYSQLI_ASSOC, false);

$Results = array();
$Count = $DB->record_count();
$Results['count'] = $Count;

$Emails = array();

if ($Count > 0) {
	foreach ($EmailResults as $Email) {
		$Emails[] = array(
						'id' => (int)$Email['ID'],
						'email' => $Email['Email'],
						'comment' => $Email['Comment'],
						'userid' => (int)$Email['UserID'],
						'time' => $Email['Time']);
	}
}
$Results['emails'] = $Emails;
$JSON['results'] = $Results;

echo json_encode($JSON);
exit();
