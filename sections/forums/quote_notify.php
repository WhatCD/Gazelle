<?php
$Matches = array();
preg_match_all('/\[quote(.*?)]|\[\/quote]/', $Body, $Matches);

if (array_key_exists(0, $Matches)) {
	$Usernames = array();
	$Level = 0;
	foreach ($Matches[0] as $M) {
		if ($M != '[/quote]') {
			if ($Level == 0) {
				add_username($M);
			}
			$Level++;
		} else {
			$Level--;
		}
	}
}
//remove any dupes in the array
$Usernames = array_unique($Usernames);

$DB->query("SELECT m.ID, p.PushService
			FROM users_main AS m
			LEFT JOIN users_info AS i ON i.UserID = m.ID
			LEFT JOIN users_push_notifications AS p ON p.UserID = m.ID
			WHERE m.Username IN " . "('" . implode("', '", $Usernames)
	. "')
	AND i.NotifyOnQuote = '1' AND i.UserID != $LoggedUser[ID]");

$Results = $DB->to_array();
foreach($Results as $Result) {
	$UserID = $Result['ID'];
	$PushService = $Result['PushService'];
	$QuoterID = db_string($LoggedUser['ID']);
	$UserID = db_string($UserID);
	$ForumID = db_string($ForumID);
	$TopicID = db_string($TopicID);
	$PostID = db_string($PostID);

	$DB->query("INSERT IGNORE INTO users_notify_quoted (UserID, QuoterID, ForumID, TopicID, PostID, Date)
		VALUES ('$UserID', '$QuoterID', '$ForumID', '$TopicID', '$PostID', '" . sqltime() . "')");
	$Cache->delete_value('forums_quotes_' . $UserID);

	
}


/*
 * Validate the username and add it into the $Usernames array
 */
function add_username($Str)
{
	global $Usernames;
	$Matches = array();
	if (preg_match('/\[quote=(.*)]/', $Str, $Matches)) {
		$Username = $Matches[1];
		$Username = trim($Username);
		if (strlen($Username) > 0 && !preg_match('/[^a-zA-Z0-9|]/i', $Username)) {
			$Exploded = explode('|', $Username);
			$Username = $Exploded[0];
			$Username = preg_replace('/(^[.,]*)|([.,]*$)/', '', $Username);
			$Usernames[] = $Username;
		}
	}
}

?>
