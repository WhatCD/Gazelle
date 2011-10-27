<?
include(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

$ConvID = $_GET['id'];
if(!$ConvID || !is_number($ConvID)) {
	print json_encode(array('status' => 'failure'));
	die();
}



$UserID = $LoggedUser['ID'];
$DB->query("SELECT InInbox, InSentbox FROM pm_conversations_users WHERE UserID='$UserID' AND ConvID='$ConvID'");
if($DB->record_count() == 0) {
	print json_encode(array('status' => 'failure'));
	die();
}
list($InInbox, $InSentbox) = $DB->next_record();




if (!$InInbox && !$InSentbox) {
	print json_encode(array('status' => 'failure'));
	die();
}

// Get information on the conversation
$DB->query("SELECT
	c.Subject,
	cu.Sticky,
	cu.UnRead,
	cu.ForwardedTo,
	um.Username
	FROM pm_conversations AS c
	JOIN pm_conversations_users AS cu ON c.ID=cu.ConvID
	LEFT JOIN users_main AS um ON um.ID=cu.ForwardedTo
	WHERE c.ID='$ConvID' AND UserID='$UserID'");
list($Subject, $Sticky, $UnRead, $ForwardedID, $ForwardedName) = $DB->next_record();

$DB->query("SELECT UserID, Username, PermissionID, Enabled, Donor, Warned
	FROM pm_messages AS pm
	JOIN users_info AS ui ON ui.UserID=pm.SenderID
	JOIN users_main AS um ON um.ID=pm.SenderID
	WHERE pm.ConvID='$ConvID'");

while(list($PMUserID, $Username, $PermissionID, $Enabled, $Donor, $Warned) = $DB->next_record()) {
	$PMUserID = (int)$PMUserID;
	$Users[$PMUserID]['UserStr'] = format_username($PMUserID, $Username, $Donor, $Warned, $Enabled == 2 ? false : true, $PermissionID);
	$Users[$PMUserID]['Username'] = $Username;
}
$Users[0]['UserStr'] = 'System'; // in case it's a message from the system
$Users[0]['Username'] = 'System';



if($UnRead=='1') {

	$DB->query("UPDATE pm_conversations_users SET UnRead='0' WHERE ConvID='$ConvID' AND UserID='$UserID'");
	// Clear the caches of the inbox and sentbox
	$Cache->decrement('inbox_new_'.$UserID);
}

// Get messages
$DB->query("SELECT SentDate, SenderID, Body, ID FROM pm_messages AS m WHERE ConvID='$ConvID' ORDER BY ID");

$JsonMessages = array();
while(list($SentDate, $SenderID, $Body, $MessageID) = $DB->next_record()) {
	$JsonMessage = array(
		'messageId' => $MessageID,
		'senderId' => $SenderID,
		'senderName' => $Users[(int)$SenderID]['Username'],
		'sentDate' => $SentDate,
		'body' => $Text->full_format($Body)
	);
	$JsonMessages[] = $JsonMessage;
}

print
	json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'convId' => $ConvID,
				'subject' => $Subject.($ForwardedID > 0 ? ' (Forwarded to '.$ForwardedName.')':''),
				'sticky' => $Sticky,
				'messages' => $JsonMessages
			)
		)
	);