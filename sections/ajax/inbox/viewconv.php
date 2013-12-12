<?
$ConvID = $_GET['id'];
if (!$ConvID || !is_number($ConvID)) {
	print json_encode(array('status' => 'failure'));
	die();
}



$UserID = $LoggedUser['ID'];
$DB->query("
	SELECT InInbox, InSentbox
	FROM pm_conversations_users
	WHERE UserID = '$UserID'
		AND ConvID = '$ConvID'");
if (!$DB->has_results()) {
	print json_encode(array('status' => 'failure'));
	die();
}
list($InInbox, $InSentbox) = $DB->next_record();




if (!$InInbox && !$InSentbox) {
	print json_encode(array('status' => 'failure'));
	die();
}

// Get information on the conversation
$DB->query("
	SELECT
		c.Subject,
		cu.Sticky,
		cu.UnRead,
		cu.ForwardedTo,
		um.Username
	FROM pm_conversations AS c
		JOIN pm_conversations_users AS cu ON c.ID = cu.ConvID
		LEFT JOIN users_main AS um ON um.ID = cu.ForwardedTo
	WHERE c.ID = '$ConvID'
		AND UserID = '$UserID'");
list($Subject, $Sticky, $UnRead, $ForwardedID, $ForwardedName) = $DB->next_record();

$DB->query("
	SELECT um.ID, Username
	FROM pm_messages AS pm
		JOIN users_main AS um ON um.ID = pm.SenderID
	WHERE pm.ConvID = '$ConvID'");

while (list($PMUserID, $Username) = $DB->next_record()) {
	$PMUserID = (int)$PMUserID;
	$Users[$PMUserID]['UserStr'] = Users::format_username($PMUserID, true, true, true, true);
	$Users[$PMUserID]['Username'] = $Username;
	$UserInfo = Users::user_info($PMUserID);
	$Users[$PMUserID]['Avatar'] = $UserInfo['Avatar'];
}
$Users[0]['UserStr'] = 'System'; // in case it's a message from the system
$Users[0]['Username'] = 'System';
$Users[0]['Avatar'] = '';

if ($UnRead == '1') {
	$DB->query("
		UPDATE pm_conversations_users
		SET UnRead = '0'
		WHERE ConvID = '$ConvID'
			AND UserID = '$UserID'");
	// Clear the caches of the inbox and sentbox
	$Cache->decrement("inbox_new_$UserID");
}

// Get messages
$DB->query("
	SELECT SentDate, SenderID, Body, ID
	FROM pm_messages
	WHERE ConvID = '$ConvID'
	ORDER BY ID");

$JsonMessages = array();
while (list($SentDate, $SenderID, $Body, $MessageID) = $DB->next_record()) {
	$JsonMessage = array(
		'messageId' => (int)$MessageID,
		'senderId' => (int)$SenderID,
		'senderName' => $Users[(int)$SenderID]['Username'],
		'sentDate' => $SentDate,
		'avatar' => $Users[(int)$SenderID]['Avatar'],
		'bbBody' => $Body,
		'body' => Text::full_format($Body)
	);
	$JsonMessages[] = $JsonMessage;
}

print
	json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'convId' => (int)$ConvID,
				'subject' => $Subject.($ForwardedID > 0 ? " (Forwarded to $ForwardedName)" : ''),
				'sticky' => $Sticky == 1,
				'messages' => $JsonMessages
			)
		)
	);
?>
