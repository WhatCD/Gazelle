<?
if (!check_perms('torrents_edit')) {
	error(403);
}

$GroupID = $_POST['groupid'];
$OldGroupID = $GroupID;
$NewGroupID = db_string($_POST['targetgroupid']);

if (!$GroupID || !is_number($GroupID)) {
	error(404);
}
if (!$NewGroupID || !is_number($NewGroupID)) {
	error(404);
}
if ($NewGroupID == $GroupID) {
	error('Old group ID is the same as new group ID!');
}
$DB->query("
	SELECT CategoryID, Name
	FROM torrents_group
	WHERE ID = '$NewGroupID'");
if (!$DB->has_results()) {
	error('Target group does not exist.');
}
list($CategoryID, $NewName) = $DB->next_record();
if ($Categories[$CategoryID - 1] != 'Music') {
	error('Only music groups can be merged.');
}

$DB->query("
	SELECT Name
	FROM torrents_group
	WHERE ID = $GroupID");
list($Name) = $DB->next_record();

// Everything is legit, let's just confim they're not retarded
if (empty($_POST['confirm'])) {
	$Artists = Artists::get_artists(array($GroupID, $NewGroupID));

	View::show_header();
?>
	<div class="center thin">
	<div class="header">
		<h2>Merge Confirm!</h2>
	</div>
	<div class="box pad">
		<form class="confirm_form" name="torrent_group" action="torrents.php" method="post">
			<input type="hidden" name="action" value="merge" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="confirm" value="true" />
			<input type="hidden" name="groupid" value="<?=$GroupID?>" />
			<input type="hidden" name="targetgroupid" value="<?=$NewGroupID?>" />
			<h3>You are attempting to merge the group:</h3>
			<ul>
				<li><?= Artists::display_artists($Artists[$GroupID], true, false)?> - <a href="torrents.php?id=<?=$GroupID?>"><?=$Name?></a></li>
			</ul>
			<h3>Into the group:</h3>
			<ul>
				<li><?= Artists::display_artists($Artists[$NewGroupID], true, false)?> - <a href="torrents.php?id=<?=$NewGroupID?>"><?=$NewName?></a></li>
			</ul>
			<input type="submit" value="Confirm" />
		</form>
	</div>
	</div>
<?
	View::show_footer();
} else {
	authorize();

	// Votes ninjutsu. This is so annoyingly complicated.
	// 1. Get a list of everybody who voted on the old group and clear their cache keys
	$DB->query("
		SELECT UserID
		FROM users_votes
		WHERE GroupID = '$GroupID'");
	while (list($UserID) = $DB->next_record()) {
		$Cache->delete_value("voted_albums_$UserID");
	}
	// 2. Update the existing votes where possible, clear out the duplicates left by key
	// conflicts, and update the torrents_votes table
	$DB->query("
		UPDATE IGNORE users_votes
		SET GroupID = '$NewGroupID'
		WHERE GroupID = '$GroupID'");
	$DB->query("
		DELETE FROM users_votes
		WHERE GroupID = '$GroupID'");
	$DB->query("
		INSERT INTO torrents_votes (GroupID, Ups, Total, Score)
			SELECT $NewGroupID, UpVotes, TotalVotes, VoteScore
			FROM (
				SELECT
					IFNULL(SUM(IF(Type = 'Up', 1, 0)), 0) As UpVotes,
					COUNT(1) AS TotalVotes,
					binomial_ci(IFNULL(SUM(IF(Type = 'Up', 1, 0)), 0), COUNT(1)) AS VoteScore
				FROM users_votes
				WHERE GroupID = $NewGroupID
				GROUP BY GroupID
			) AS a
		ON DUPLICATE KEY UPDATE
			Ups = a.UpVotes,
			Total = a.TotalVotes,
			Score = a.VoteScore;");
	// 3. Clear the votes_pairs keys!
	$DB->query("
		SELECT v2.GroupID
		FROM users_votes AS v1
			INNER JOIN users_votes AS v2 USING (UserID)
		WHERE (v1.Type = 'Up' OR v2.Type = 'Up')
			AND (v1.GroupID IN($GroupID, $NewGroupID))
			AND (v2.GroupID NOT IN($GroupID, $NewGroupID));");
	while (list($CacheGroupID) = $DB->next_record()) {
		$Cache->delete_value("vote_pairs_$CacheGroupID");
	}
	// 4. Clear the new groups vote keys
	$Cache->delete_value("votes_$NewGroupID");

	$DB->query("
		UPDATE torrents
		SET GroupID = '$NewGroupID'
		WHERE GroupID = '$GroupID'");
	$DB->query("
		UPDATE wiki_torrents
		SET PageID = '$NewGroupID'
		WHERE PageID = '$GroupID'");

	//Comments
	Comments::merge('torrents', $OldGroupID, $NewGroupID);

	//Collages
	$DB->query("
		SELECT CollageID
		FROM collages_torrents
		WHERE GroupID = '$OldGroupID'"); // Select all collages that contain edited group
	while (list($CollageID) = $DB->next_record()) {
		$DB->query("
			UPDATE IGNORE collages_torrents
			SET GroupID = '$NewGroupID'
			WHERE GroupID = '$OldGroupID'
				AND CollageID = '$CollageID'"); // Change collage group ID to new ID
		$DB->query("
			DELETE FROM collages_torrents
			WHERE GroupID = '$OldGroupID'
				AND CollageID = '$CollageID'");
		$Cache->delete_value("collage_$CollageID");
	}
	$Cache->delete_value("torrent_collages_$NewGroupID");
	$Cache->delete_value("torrent_collages_personal_$NewGroupID");

	// Requests
	$DB->query("
		SELECT ID
		FROM requests
		WHERE GroupID = '$OldGroupID'");
	$Requests = $DB->collect('ID');
	$DB->query("
		UPDATE requests
		SET GroupID = '$NewGroupID'
		WHERE GroupID = '$OldGroupID'");
	foreach ($Requests as $RequestID) {
		$Cache->delete_value("request_$RequestID");
	}
	$Cache->delete_value('requests_group_'.$NewGroupID);

	Torrents::delete_group($GroupID);

	Torrents::write_group_log($NewGroupID, 0, $LoggedUser['ID'], "Merged Group $GroupID ($Name) to $NewGroupID ($NewName)", 0);
	$DB->query("
		UPDATE group_log
		SET GroupID = $NewGroupID
		WHERE GroupID = $GroupID");

	$GroupID = $NewGroupID;

	$DB->query("
		SELECT ID
		FROM torrents
		WHERE GroupID = '$OldGroupID'");
	while (list($TorrentID) = $DB->next_record()) {
		$Cache->delete_value("torrent_download_$TorrentID");
	}
	$Cache->delete_value("torrents_details_$GroupID");
	$Cache->delete_value("groups_artists_$GroupID");
	Torrents::update_hash($GroupID);

	header("Location: torrents.php?id=" . $GroupID);
}
?>
