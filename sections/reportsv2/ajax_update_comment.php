<?
// perform the back end of updating a report comment

authorize();

if (!check_perms('admin_reports')) {
	error(403);
}

$ReportID = (int) $_POST['reportid'];

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
