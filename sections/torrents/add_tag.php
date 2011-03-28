<?
authorize();

$UserID = $LoggedUser['ID'];
$GroupID = db_string($_POST['groupid']);

if(!is_number($GroupID) || !$GroupID) {
	error(0);
}

$Tags = explode(',', $_POST['tagname']);
foreach($Tags as $TagName) {
	$TagName = sanitize_tag($TagName);
	if(!empty($TagName)) {
		// Check DB for tag matching name
		$DB->query("SELECT t.ID FROM tags AS t WHERE t.Name LIKE '".$TagName."'");
		list($TagID) = $DB->next_record();
	
		if(!$TagID) { // Tag doesn't exist yet - create tag
			$DB->query("INSERT INTO tags (Name, UserID) VALUES ('".$TagName."', ".$UserID.")");
			$TagID = $DB->inserted_id();
		} else {
			$DB->query("SELECT TagID FROM torrents_tags_votes WHERE GroupID='$GroupID' AND TagID='$TagID' AND UserID='$UserID'");
			if($DB->record_count()!=0) { // User has already voted on this tag, and is trying hax to make the rating go up
				header('Location: '.$_SERVER['HTTP_REFERER']);
				die();
			}
		}

		
	
		$DB->query("INSERT INTO torrents_tags 
			(TagID, GroupID, PositiveVotes, UserID) VALUES 
			('$TagID', '$GroupID', '3', '$UserID') 
			ON DUPLICATE KEY UPDATE PositiveVotes=PositiveVotes+2");
	
		$DB->query("INSERT INTO torrents_tags_votes (GroupID, TagID, UserID, Way) VALUES ('$GroupID', '$TagID', '$UserID', 'up')");
		
		
	}
}

update_hash($GroupID); // Delete torrent group cache
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
