<?php
if (!check_perms('users_warn')) {
	error(404);
}
Misc::assert_isset_request($_POST, array('reason', 'privatemessage', 'body', 'length', 'groupid', 'postid', 'userid'));

$Reason = $_POST['reason'];
$PrivateMessage = $_POST['privatemessage'];
$Body = $_POST['body'];
$Length = $_POST['length'];
$GroupID = (int) $_POST['groupid'];
$PostID = (int) $_POST['postid'];
$UserID = (int) $_POST['userid'];
$Key = (int) $_POST['key'];
$SQLTime = sqltime();
$UserInfo = Users::user_info($UserID);
if ($UserInfo['Class'] > $LoggedUser['Class']) {
	error(403);
}
$URL = 'https://' . SSL_SITE_URL . "/torrents.php?id=$GroupID&postid=$PostID#post$PostID";
if ($Length != 'verbal') {
	$Time = ((int) $Length) * (7 * 24 * 60 * 60);
	Tools::warn_user($UserID, $Time, "$URL - " . $Reason);
	$Subject = 'You have received a warning';
	$PrivateMessage = "You have received a $Length week warning for [url=$URL]this post.[/url]\n\n" . $PrivateMessage;
	$WarnTime = time_plus($Time);
	$AdminComment = date('Y-m-d') . ' - Warned until ' . $WarnTime . ' by ' . $LoggedUser['Username'] . " for $URL \nReason: $Reason\n\n";
} else {
	$Subject = 'You have received a verbal warning';
	$PrivateMessage = "You have received a verbal warning for [url=$URL]this post.[/url]\n\n" . $PrivateMessage;
	$AdminComment = date('Y-m-d') . ' - Verbally warned by ' . $LoggedUser['Username'] . " for $URL \nReason: $Reason\n\n";
}
$DB->query("INSERT INTO users_warnings_forums (UserID, Comment)
			VALUES('$UserID', '" . db_string($AdminComment)	. "')
			ON DUPLICATE KEY UPDATE Comment = CONCAT('" . db_string($AdminComment) . "', Comment)");
Tools::update_user_notes($UserID, $AdminComment);
Misc::send_pm($UserID, $LoggedUser['ID'], $Subject, $PrivateMessage);

// Mainly
$DB->query("SELECT
				tc.Body,
				tc.AuthorID,
				tc.GroupID,
				tc.AddedTime
			FROM torrents_comments AS tc
			WHERE tc.ID='$PostID'");
list($OldBody, $AuthorID, $GroupID, $AddedTime) = $DB->next_record();

$DB->query("SELECT ceil(COUNT(ID) / " . TORRENT_COMMENTS_PER_PAGE . ") AS Page
			FROM torrents_comments
			WHERE GroupID = $GroupID AND ID <= $PostID");
list($Page) = $DB->next_record();

// Perform the update
$DB->query("UPDATE torrents_comments
			SET Body = '" . db_string($Body) . "',
				EditedUserID = '" . db_string($LoggedUser['ID']) . "',
				EditedTime = '" . sqltime() . "'
			WHERE ID='$PostID'");

// Update the cache
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $Page - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
$Cache->begin_transaction('torrent_comments_' . $GroupID . '_catalogue_' . $CatalogueID);

$Cache->update_row($_POST['key'], array('ID' => $_POST['postid'], 'AuthorID' => $AuthorID, 'AddedTime' => $AddedTime, 'Body' => $Body,
				'EditedUserID' => db_string($LoggedUser['ID']), 'EditedTime' => sqltime(), 'Username' => $LoggedUser['Username']));
$Cache->commit_transaction(0);

$DB->query("INSERT INTO comments_edits (Page, PostID, EditUser, EditTime, Body)
			VALUES ('torrents', " . db_string($_POST['postid']) . ", " . db_string($LoggedUser['ID']) . ", '" . sqltime() . "', '" . db_string($OldBody) . "')");

header("Location: torrents.php?id=$GroupID&postid=$PostID#post$PostID");
?>
;
