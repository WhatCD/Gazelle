<?php

$UserID = $LoggedUser['ID'];


if (empty($_GET['type'])) {
	$Section = 'inbox';
} else {
	$Section = $_GET['type']; // either 'inbox' or 'sentbox'
}
if (!in_array($Section, array('inbox', 'sentbox'))) {
	print
		json_encode(
			array(
				'status' => 'failure'
			)
		);
	die();
}

list($Page, $Limit) = Format::page_limit(MESSAGES_PER_PAGE);

$Sort = empty($_GET['sort']) || $_GET['sort'] != "unread" ? "Date DESC" : "cu.Unread = '1' DESC, DATE DESC";

$sql = "
	SELECT
		SQL_CALC_FOUND_ROWS
		c.ID,
		c.Subject,
		cu.Unread,
		cu.Sticky,
		cu.ForwardedTo,
		um2.Username AS ForwardedName,
		cu2.UserID,
		um.Username,
		ui.Donor,
		ui.Warned,
		um.Enabled,
		ui.Avatar,";
$sql .= $Section === 'sentbox' ? ' cu.SentDate ' : ' cu.ReceivedDate ';
$sql .= "AS Date
	FROM pm_conversations AS c
		LEFT JOIN pm_conversations_users AS cu ON cu.ConvID = c.ID AND cu.UserID = '$UserID'
		LEFT JOIN pm_conversations_users AS cu2 ON cu2.ConvID = c.ID AND cu2.UserID != '$UserID' AND cu2.ForwardedTo = 0
		LEFT JOIN users_main AS um ON um.ID = cu2.UserID
		LEFT JOIN users_info AS ui ON ui.UserID = um.ID
		LEFT JOIN users_main AS um2 ON um2.ID = cu.ForwardedTo";

if (!empty($_GET['search']) && $_GET['searchtype'] === 'message') {
	$sql .=	' JOIN pm_messages AS m ON c.ID = m.ConvID';
}
$sql .= " WHERE ";
if (!empty($_GET['search'])) {
	$Search = db_string($_GET['search']);
	if ($_GET['searchtype'] === 'user') {
		$sql .= "um.Username LIKE '$Search' AND ";
	} elseif ($_GET['searchtype'] === 'subject') {
		$Words = explode(' ', $Search);
		$sql .= "c.Subject LIKE '%".implode("%' AND c.Subject LIKE '%", $Words)."%' AND ";
	} elseif ($_GET['searchtype'] === 'message') {
		$Words = explode(' ', $Search);
		$sql .= "m.Body LIKE '%".implode("%' AND m.Body LIKE '%", $Words)."%' AND ";
	}
}
$sql .= $Section === 'sentbox' ? ' cu.InSentbox' : ' cu.InInbox';
$sql .= " = '1'";

$sql .= "
	GROUP BY c.ID
	ORDER BY cu.Sticky, $Sort
	LIMIT $Limit";
$Results = $DB->query($sql);
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();
$DB->set_query_id($Results);

$CurURL = Format::get_url(array('sort'));
if (empty($CurURL)) {
	$CurURL = "inbox.php?";
} else {
	$CurURL = "inbox.php?".$CurURL."&";
}

$Pages = Format::get_pages($Page, $NumResults, MESSAGES_PER_PAGE, 9);

$JsonMessages = array();
while (list($ConvID, $Subject, $Unread, $Sticky, $ForwardedID, $ForwardedName, $SenderID, $Username, $Donor, $Warned, $Enabled, $Avatar, $Date) = $DB->next_record()) {
	$JsonMessage = array(
		'convId' => (int)$ConvID,
		'subject' => $Subject,
		'unread' => $Unread == 1,
		'sticky' => $Sticky == 1,
		'forwardedId' => (int)$ForwardedID,
		'forwardedName' => $ForwardedName,
		'senderId' => (int)$SenderID,
		'username' => $Username,
		'avatar' => $Avatar,
		'donor' => $Donor == 1,
		'warned' => $Warned == 1,
		'enabled' => $Enabled == 2 ? false : true,
		'date' => $Date
	);
	$JsonMessages[] = $JsonMessage;
}

print
	json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'currentPage' => (int)$Page,
				'pages' => ceil($NumResults / MESSAGES_PER_PAGE),
				'messages' => $JsonMessages
			)
		)
	);
?>
