<?php
$FriendID = (int)$_POST['friend'];
$Type = $_POST['type'];
$ID = (int)$_POST['id'];
$Note = $_POST['note'];

if (empty($FriendID) || empty($Type) || empty($ID)) {
	echo json_encode(array('status' => 'error', 'response' => 'Error.'));
	die();
}
// Make sure the recipient is on your friends list and not some random dude.
$DB->query("
	SELECT f.FriendID, u.Username
	FROM friends AS f
		RIGHT JOIN users_enable_recommendations AS r
			ON r.ID = f.FriendID AND r.Enable = 1
		RIGHT JOIN users_main AS u
			ON u.ID = f.FriendID
	WHERE f.UserID = '$LoggedUser[ID]'
		AND f.FriendID = '$FriendID'");

if (!$DB->has_results()) {
	echo json_encode(array('status' => 'error', 'response' => 'Not on friend list.'));
	die();
}

$Type = strtolower($Type);
$Link = '';
// "a" vs "an", english language is so confusing.
// https://en.wikipedia.org/wiki/English_articles#Distinction_between_a_and_an
$Article = 'a';
switch ($Type) {
	case 'torrent':
		$Link = "torrents.php?id=$ID";
		$DB->query("
			SELECT Name
			FROM torrents_group
			WHERE ID = '$ID'");
		break;
	case 'artist':
		$Article = 'an';
		$Link = "artist.php?id=$ID";
		$DB->query("
			SELECT Name
			FROM artists_group
			WHERE ArtistID = '$ID'");
		break;
	case 'collage':
		$Link = "collages.php?id=$ID";
		$DB->query("
			SELECT Name
			FROM collages
			WHERE ID = '$ID'");
		break;
}
list($Name) = $DB->next_record();
$Subject = $LoggedUser['Username'] . " recommended you $Article $Type!";
$Body = $LoggedUser['Username'] . " recommended you the $Type [url=".site_url()."$Link]$Name".'[/url].';
if (!empty($Note)) {
	$Body = "$Body\n\n$Note";
}

Misc::send_pm($FriendID, $LoggedUser['ID'], $Subject, $Body);
echo json_encode(array('status' => 'success', 'response' => 'Sent!'));
die();
