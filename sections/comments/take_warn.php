<?php
if (!check_perms('users_warn')) {
	error(404);
}
Misc::assert_isset_request($_POST, array('reason', 'privatemessage', 'body', 'length', 'postid'));

$Reason = $_POST['reason'];
$PrivateMessage = $_POST['privatemessage'];
$Body = $_POST['body'];
$Length = $_POST['length'];
$PostID = (int)$_POST['postid'];

$DB->query("
	SELECT AuthorID
	FROM comments
	WHERE ID = $PostID");
if (!$DB->has_results()) {
	error(404);
}
list($AuthorID) = $DB->next_record();

$UserInfo = Users::user_info($AuthorID);
if ($UserInfo['Class'] > $LoggedUser['Class']) {
	error(403);
}

$URL = site_url() . Comments::get_url_query($PostID);
if ($Length !== 'verbal') {
	$Time = (int)$Length * (7 * 24 * 60 * 60);
	Tools::warn_user($AuthorID, $Time, "$URL - $Reason");
	$Subject = 'You have received a warning';
	$PrivateMessage = "You have received a $Length week warning for [url=$URL]this comment[/url].\n\n[quote]{$PrivateMessage}[/quote]";
	$WarnTime = time_plus($Time);
	$AdminComment = date('Y-m-d') . " - Warned until $WarnTime by " . $LoggedUser['Username'] . "\nReason: $URL - $Reason\n\n";
} else {
	$Subject = 'You have received a verbal warning';
	$PrivateMessage = "You have received a verbal warning for [url=$URL]this comment[/url].\n\n[quote]{$PrivateMessage}[/quote]";
	$AdminComment = date('Y-m-d') . ' - Verbally warned by ' . $LoggedUser['Username'] . " for $URL\nReason: $Reason\n\n";
	Tools::update_user_notes($AuthorID, $AdminComment);
}
$DB->query("
	INSERT INTO users_warnings_forums
		(UserID, Comment)
	VALUES
		('$AuthorID', '" . db_string($AdminComment) . "')
	ON DUPLICATE KEY UPDATE
		Comment = CONCAT('" . db_string($AdminComment) . "', Comment)");
Misc::send_pm($AuthorID, $LoggedUser['ID'], $Subject, $PrivateMessage);

Comments::edit($PostID, $Body);

header("Location: $URL");
