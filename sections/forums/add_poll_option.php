<?
authorize();

if(!check_perms("site_moderate_forums")) {
	error(403);
}

$ThreadID = $_POST['threadid'];
$NewOption = $_POST['new_option'];

if(!is_number($ThreadID)) {
	error(404);
}

$DB->query("SELECT Answers FROM forums_polls WHERE TopicID = ".$ThreadID);
if($DB->record_count() < 1) {
	error(404);
}

list($Answers) = $DB->next_record(MYSQLI_NUM, false);
$Answers = unserialize($Answers);
$Answers[] = $NewOption;
$Answers = serialize($Answers);

$DB->query("UPDATE forums_polls SET Answers = '".db_string($Answers)."' WHERE TopicID = ".$ThreadID);
$Cache->delete_value('polls_'.$ThreadID);

header("Location: forums.php?action=viewthread&threadid=".$ThreadID);
