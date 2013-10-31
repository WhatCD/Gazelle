<?
//******************************************************************************//
//--------------- Vote on a request --------------------------------------------//
//This page is ajax!

if (!check_perms('site_vote')) {
	error(403);
}

authorize();

if (empty($_GET['id']) || !is_number($_GET['id'])) {
	error(0);
}

$RequestID = $_GET['id'];

if (empty($_GET['amount']) || !is_number($_GET['amount']) || $_GET['amount'] < $MinimumVote) {
	$Amount = $MinimumVote;
} else {
	$Amount = $_GET['amount'];
}

$Bounty = ($Amount * (1 - $RequestTax));

$DB->query("
	SELECT TorrentID
	FROM requests
	WHERE ID = $RequestID");
list($Filled) = $DB->next_record();

if ($LoggedUser['BytesUploaded'] >= $Amount && $Filled === '0') {

	// Create vote!
	$DB->query("
		INSERT IGNORE INTO requests_votes
			(RequestID, UserID, Bounty)
		VALUES
			($RequestID, ".$LoggedUser['ID'].", $Bounty)");

	if ($DB->affected_rows() < 1) {
		//Insert failed, probably a dupe vote, just increase their bounty.
			$DB->query("
				UPDATE requests_votes
				SET Bounty = (Bounty + $Bounty)
				WHERE UserID = ".$LoggedUser['ID']."
					AND RequestID = $RequestID");
		echo 'dupe';
	}



	$DB->query("
		UPDATE requests
		SET LastVote = NOW()
		WHERE ID = $RequestID");

	$Cache->delete_value("request_$RequestID");
	$Cache->delete_value("request_votes_$RequestID");

	$ArtistForm = Requests::get_artists($RequestID);
	foreach ($ArtistForm as $Importance) {
		foreach ($Importance as $Artist) {
			$Cache->delete_value('artists_requests_'.$Artist['id']);
		}
	}

	// Subtract amount from user
	$DB->query("
		UPDATE users_main
		SET Uploaded = (Uploaded - $Amount)
		WHERE ID = ".$LoggedUser['ID']);
	$Cache->delete_value('user_stats_'.$LoggedUser['ID']);

	Requests::update_sphinx_requests($RequestID);
	echo 'success';
	$DB->query("
		SELECT UserID
		FROM requests_votes
		WHERE RequestID = '$RequestID'
			AND UserID != '$LoggedUser[ID]'");
	$UserIDs = array();
	while (list($UserID) = $DB->next_record()) {
		$UserIDs[] = $UserID;
	}
	NotificationsManager::notify_users($UserIDs, NotificationsManager::REQUESTALERTS, Format::get_size($Amount) . " of bounty has been added to a request you've voted on!", "requests.php?action=view&id=" . $RequestID);

} elseif ($LoggedUser['BytesUploaded'] < $Amount) {
	echo 'bankrupt';
}

?>
