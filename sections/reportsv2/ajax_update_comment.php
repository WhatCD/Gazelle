<?
// perform the back end of updating a report comment

authorize();

if (!check_perms('admin_reports')) {
	error(403);
}

if (empty($_POST['reportid']) || !is_number($_POST['reportid'])) {
	echo 'HAX ATTEMPT!'.$_GET['reportid'];
	die();
}

$ReportID = $_POST['reportid'];

$Message = db_string($_POST['comment']);
//Message can be blank!

$DB->query("
	SELECT ModComment
	FROM reportsv2
	WHERE ID = $ReportID");
list($ModComment) = $DB->next_record();
if (isset($ModComment)) {
	$DB->query("
		UPDATE reportsv2
		SET ModComment = '$Message'
		WHERE ID = $ReportID");
}
