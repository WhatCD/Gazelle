<?
authorize();

$GroupID = $_REQUEST['groupid'];
if (!is_number($GroupID)) {
	echo 'Invalid Group';
	die();
}

// What groups has this guy voted?
$UserVotes = Votes::get_user_votes($LoggedUser['ID']);

// What are the votes for this group?
$GroupVotes = Votes::get_group_votes($GroupID);

$UserID = $LoggedUser['ID'];
if ($_REQUEST['do'] == 'vote') {
	if (isset($UserVotes[$GroupID]) || !check_perms('site_album_votes')) {
		echo 'noaction';
		die();
	}
	if ($_REQUEST['vote'] != 'up' && $_REQUEST['vote'] != 'down') {
		echo 'badvote';
		die();
	}
	$Type = ($_REQUEST['vote'] == 'up') ? 'Up' : 'Down';

	// Update the two votes tables if needed
	$DB->query("
		INSERT IGNORE INTO users_votes (UserID, GroupID, Type)
		VALUES ($UserID, $GroupID, '$Type')");
	if ($DB->affected_rows() == 0) {
		echo 'noaction';
		die();
	}

	// Update the group's cache key
	$GroupVotes['Total'] += 1;
	if ($Type == 'Up') {
		$GroupVotes['Ups'] += 1;
	}
	$Cache->cache_value("votes_$GroupID", $GroupVotes);

	// If the group has no votes yet, we need an insert, otherwise an update
	// so we can cut corners and use the magic of INSERT...ON DUPLICATE KEY UPDATE...
	// to accomplish both in one query
	$DB->query("
		INSERT INTO torrents_votes
			(GroupID, Total, Ups, Score)
		VALUES
			($GroupID, 1, ".($Type == 'Up' ? 1 : 0).", 0)
		ON DUPLICATE KEY UPDATE
			Total = Total + 1,
			Score = IFNULL(binomial_ci(Ups".($Type == 'Up' ? '+1' : '').", Total), 0)".
				($Type == 'Up' ? ', Ups = Ups + 1' : ''));

	$UserVotes[$GroupID] = array('GroupID' => $GroupID, 'Type' => $Type);

	// Update this guy's cache key
	$Cache->cache_value('voted_albums_'.$LoggedUser['ID'], $UserVotes);

	// Update the paired cache keys for "people who liked"
	// First update this album's paired votes. If this keys is magically not set,
	// our life just got a bit easier. We're only tracking paired votes on upvotes.
	if ($Type == 'Up') {
		$VotePairs = $Cache->get_value("vote_pairs_$GroupID", true);
		if ($VotePairs !== false) {
			foreach ($UserVotes as $Vote) {
				if ($Vote['GroupID'] == $GroupID) {
					continue;
				}
				// Go through each of his other votes, incrementing the
				// corresponding keys in this groups vote_pairs array
				if (isset($VotePairs[$Vote['GroupID']])) {
					$VotePairs[$Vote['GroupID']]['Total'] += 1;
					if ($Vote['Type'] == 'Up') {
						$VotePairs[$Vote['GroupID']]['Ups'] += 1;
					}
				} else {
					$VotePairs[$Vote['GroupID']] = array(
								'GroupID' => $Vote['GroupID'],
								'Total' => 1,
								'Ups' => ($Type == 'Up') ? 1 : 0);
				}
			}
		}
		$Cache->cache_value("vote_pairs_$GroupID", $VotePairs, 21600);
	}

	// Now do the paired votes keys for all of this guy's other votes
	foreach ($UserVotes as $VGID => $Vote) {
		if ($Vote['Type'] != 'Up') {
			// We're only track paired votes on upvotes
			continue;
		}
		if ($VGID == $GroupID) {
			continue;
		}
		// Again, if the cache key is not set, move along
		$VotePairs = $Cache->get_value("vote_pairs_$VGID", true);
		if ($VotePairs !== false) {
			// Go through all of the other albums paired to this one, and update
			// this group's entry in their vote_pairs keys
			if (isset($VotePairs[$GroupID])) {
				$VotePairs[$GroupID]['Total']++;
				if ($Type == 'Up') {
					$VotePairs[$GroupID]['Ups']++;
				}
			} else {
				$VotePairs[$GroupID] = array(
							'GroupID' => $GroupID,
							'Total' => 1,
							'Ups' => ($Type == 'Up') ? 1 : 0);
			}
			$Cache->cache_value("vote_pairs_$VGID", $VotePairs, 21600);
		}
	}

	echo 'success';
} elseif ($_REQUEST['do'] == 'unvote') {
	if (!isset($UserVotes[$GroupID])) {
		echo 'noaction';
		die();
	}
	$Type = $UserVotes[$GroupID]['Type'];

	$DB->query("
		DELETE FROM users_votes
		WHERE UserID = $UserID
			AND GroupID = $GroupID");

	// Update personal cache key
	unset($UserVotes[$GroupID]);
	$Cache->cache_value('voted_albums_'.$LoggedUser['ID'], $UserVotes);

	// Update the group's cache key
	$GroupVotes['Total'] -= 1;
	if ($Type == 'Up') {
		$GroupVotes['Ups'] -= 1;
	}
	$Cache->cache_value("votes_$GroupID", $GroupVotes);

	$DB->query('
		UPDATE torrents_votes
		SET
			Total = GREATEST(0, Total - 1),
			Score = IFNULL(binomial_ci(GREATEST(0, Ups'.($Type == 'Up' ? '-1' : '').'), GREATEST(0, Total)), 0)'.
			($Type == 'Up' ? ', Ups = GREATEST(0, Ups - 1)' : '')."
		WHERE GroupID=$GroupID");
	// Update paired cache keys
	// First update this album's paired votes. If this keys is magically not set,
	// our life just got a bit easier. We're only tracking paired votes on upvotes.
	if ($Type == 'Up') {
		$VotePairs = $Cache->get_value("vote_pairs_$GroupID", true);
		if ($VotePairs !== false) {
			foreach ($UserVotes as $Vote) {
				if (isset($VotePairs[$Vote['GroupID']])) {
					if ($VotePairs[$Vote['GroupID']]['Total'] == 0) {
						// Something is screwy
						$Cache->delete_value("vote_pairs_$GroupID");
						continue;
					}
					$VotePairs[$Vote['GroupID']]['Total'] -= 1;
					if ($Vote['Type'] == 'Up') {
						$VotePairs[$Vote['GroupID']]['Ups'] -= 1;
					}
				} else {
					// Something is screwy, kill the key and move on
					$Cache->delete_value("vote_pairs_$GroupID");
					break;
				}
			}
		}
		$Cache->cache_value("vote_pairs_$GroupID", $VotePairs, 21600);
	}

	// Now do the paired votes keys for all of this guy's other votes
	foreach ($UserVotes as $VGID => $Vote) {
		if ($Vote['Type'] != 'Up') {
			// We're only track paired votes on upvotes
			continue;
		}
		if ($VGID == $GroupID) {
			continue;
		}
		// Again, if the cache key is not set, move along
		$VotePairs = $Cache->get_value("vote_pairs_$VGID", true);
		if ($VotePairs !== false) {
			if (isset($VotePairs[$GroupID])) {
				if ($VotePairs[$GroupID]['Total'] == 0) {
					// Something is screwy
					$Cache->delete_value("vote_pairs_$VGID");
					continue;
				}
				$VotePairs[$GroupID]['Total'] -= 1;
				if ($Type == 'Up') {
					$VotePairs[$GroupID]['Ups'] -= 1;
				}
				$Cache->cache_value("vote_pairs_$VGID", $VotePairs, 21600);
			} else {
				// Something is screwy, kill the key and move on
				$Cache->delete_value("vote_pairs_$VGID");
			}
		}
	}

	// Let the script know what happened
	if ($Type == 'Up') {
		echo 'success-up';
	} else {
		echo 'success-down';
	}
}
?>
