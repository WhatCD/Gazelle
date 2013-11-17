<?
authorize();
if (!empty($LoggedUser['DisableTagging'])) {
	error(403);
}

$UserID = $LoggedUser['ID'];
$GroupID = $_POST['groupid'];

if (!is_number($GroupID) || !$GroupID) {
	error(0);
}

//Delete cached tag used for undos
if (isset($_POST['undo'])) {
	$Cache->delete_value("deleted_tags_$GroupID".'_'.$LoggedUser['ID']);
}

$Tags = explode(',', $_POST['tagname']);
foreach ($Tags as $TagName) {
	$TagName = Misc::sanitize_tag($TagName);

	if (!empty($TagName)) {
		$TagName = Misc::get_alias_tag($TagName);
		// Check DB for tag matching name
		$DB->query("
			SELECT ID
			FROM tags
			WHERE Name LIKE '$TagName'");
		list($TagID) = $DB->next_record();

		if (!$TagID) { // Tag doesn't exist yet - create tag
			$DB->query("
				INSERT INTO tags (Name, UserID)
				VALUES ('$TagName', $UserID)");
			$TagID = $DB->inserted_id();
		} else {
			$DB->query("
				SELECT TagID
				FROM torrents_tags_votes
				WHERE GroupID = '$GroupID'
					AND TagID = '$TagID'
					AND UserID = '$UserID'");
			if ($DB->has_results()) { // User has already voted on this tag, and is trying hax to make the rating go up
				header('Location: '.$_SERVER['HTTP_REFERER']);
				die();
			}
		}

		$DB->query("
			INSERT INTO torrents_tags
				(TagID, GroupID, PositiveVotes, UserID)
			VALUES
				('$TagID', '$GroupID', '3', '$UserID')
			ON DUPLICATE KEY UPDATE
				PositiveVotes = PositiveVotes + 2");

		$DB->query("
			INSERT INTO torrents_tags_votes
				(GroupID, TagID, UserID, Way)
			VALUES
				('$GroupID', '$TagID', '$UserID', 'up')");

		$DB->query("
			INSERT INTO group_log
				(GroupID, UserID, Time, Info)
			VALUES
				('$GroupID', ".$LoggedUser['ID'].", '".sqltime()."', '".db_string("Tag \"$TagName\" added to group")."')");
	}
}

Torrents::update_hash($GroupID); // Delete torrent group cache
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
