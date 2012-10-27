<?
authorize();

$GroupID = $_REQUEST['groupid'];
if (!is_number($GroupID)) {
	echo 'Invalid Group';
	die();
}

// What groups has this guy voted?
$UserVotes = $Cache->get_value('voted_albums_'.$LoggedUser['ID']);
if ($UserVotes === FALSE) {
	$DB->query('SELECT GroupID, Type FROM users_votes WHERE UserID='.$LoggedUser['ID']);
	$UserVotes = $DB->to_array('GroupID', MYSQL_ASSOC, false);
	$Cache->cache_value('voted_albums_'.$LoggedUser['ID'], $UserVotes);
}

// What are the votes for this group?
$GroupVotes = $Cache->get_value('votes_'.$GroupID);
if ($GroupVotes === FALSE) {
	$DB->query("SELECT Ups AS Ups, Total AS Total FROM torrents_votes WHERE GroupID=$GroupID");
	if ($DB->record_count() == 0) {
		$GroupVotes = array('Ups'=>0, 'Total'=>0);
	} else {
		$GroupVotes = $DB->next_record(MYSQLI_ASSOC, false);
	}
	$Cache->cache_value('votes_'.$GroupID, $GroupVotes);
}

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
	$Type = ($_REQUEST['vote'] == 'up')?"Up":"Down";
	
	// Update the group's cache key
	$GroupVotes['Total'] += 1;
	if ($Type == "Up") {
		$GroupVotes['Ups'] += 1;
	}
	$Cache->cache_value('votes_'.$GroupID, $GroupVotes);

	// Update the two votes tables
	$DB->query("INSERT INTO users_votes (UserID, GroupID, Type) VALUES ($UserID, $GroupID, '$Type') ON DUPLICATE KEY UPDATE Type = '$Type'");
	// If the group has no votes yet, we need an insert, otherwise an update
	// so we can cut corners and use the magic of INSERT...ON DUPLICATE KEY UPDATE...
	// to accomplish both in one query
	$DB->query("INSERT INTO torrents_votes (GroupID, Total, Ups, Score)
				VALUES ($GroupID, 1, ".($Type=='Up'?1:0).", 0)
				ON DUPLICATE KEY UPDATE Total = Total + 1, 
				Score = binomial_ci(".$GroupVotes['Ups'].",". $GroupVotes['Total'].")".
				($Type=='Up'?', Ups = Ups+1':''));
	
	$UserVotes[$GroupID] = array('GroupID' => $GroupID, 'Type' => $Type);
	
	// Update this guy's cache key
	$Cache->cache_value('voted_albums_'.$LoggedUser['ID'], $UserVotes);
		
	// Update the paired cache keys for "people who liked"
	// First update this album's paired votes.  If this keys is magically not set, 
	// our life just got a bit easier.  We're only tracking paired votes on upvotes.
	if ($Type == 'Up') {
		$VotePairs = $Cache->get_value('vote_pairs_'.$GroupID);
		if ($VotePairs !== FALSE) {
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
					$VotePairs[$Vote['GroupID']] = array('GroupID'=>$Vote['GroupID'],
					                                     'Total' => 1,
														 'Ups'=>($Type == 'Up')?1:0);
				}
			}
		}
		$Cache->cache_value('vote_pairs_'.$GroupID, $VotePairs);
	}

	// Now do the paired votes keys for all of this guy's other votes
	foreach ($UserVotes as $VGID => $Vote) {
		if ($Vote['Type'] != 'Up') {
			// We're only track paired votes on upvotes
			continue;
		}
		// Again, if the cache key is not set, move along
		$VotePairs = $Cache->get_value('vote_pairs_'.$VGID);
		if ($VotePairs !== FALSE) {
			// Go through all of the other albums paired to this one, and update
			// this group's entry in their vote_pairs keys
			if (isset($VotePairs[$GroupID])) {
				$VotePairs[$GroupID]['Total']++;
				if ($Type == 'Up') {
					$VotePairs[$GroupID]['Ups']++;
				}
			} else {
				$VotePairs[$GroupID] = array('GroupID' => $GroupID,
				                             'Total' => 1,
											 'Ups'=>($Type == 'Up')?1:0);
			}
			$Cache->cache_value('vote_pairs_'.$VGID, $VotePairs);
		}
	}

	
	echo 'success';
} elseif ($_REQUEST['do'] == 'unvote') {
	if (!isset($UserVotes[$GroupID])) {
		echo 'noaction';
		die();
	}
	$Type = $UserVotes[$GroupID]['Type'];
	
	$DB->query("DELETE FROM users_votes WHERE UserID=$UserID AND GroupID=$GroupID");
	
	// Update personal cache key
	unset($UserVotes[$GroupID]);
	$Cache->cache_value('voted_albums_'.$LoggedUser['ID'], $UserVotes);
	
	// Update the group's cache key
	$GroupVotes['Total'] -= 1;
	if ($Type == "Up") {
		$GroupVotes['Ups'] -= 1;
	}
	$Cache->cache_value('votes_'.$GroupID, $GroupVotes);

	$DB->query("UPDATE torrents_votes SET Total = GREATEST(0, Total - 1),
				Score = binomial_ci(".$GroupVotes['Ups'].",".$GroupVotes['Total'].")".
			    ($Type=='Up'?', Ups = GREATEST(0, Ups - 1)':'')."
				WHERE GroupID=$GroupID");
	// Update paired cache keys
	// First update this album's paired votes.  If this keys is magically not set, 
	// our life just got a bit easier.  We're only tracking paired votes on upvotes.
	if ($Type == 'Up') {
		$VotePairs = $Cache->get_value('vote_pairs_'.$GroupID);
		if ($VotePairs !== FALSE) {
			foreach ($UserVotes as $Vote) {
				if (isset($VotePairs[$Vote['GroupID']])) {
					if ($VotePairs[$Vote['GroupID']]['Total'] == 0) {
						// Something is screwy
						$Cache->delete_value('vote_pairs_'.$GroupID);
						continue;
					}
					$VotePairs[$Vote['GroupID']]['Total'] -= 1;
					if ($Vote['Type'] == 'Up') {
						$VotePairs[$Vote['GroupID']]['Ups'] -= 1;
					}
				} else {
					// Something is screwy, kill the key and move on
					$Cache->delete_value('vote_pairs_'.$GroupID);
					break;
				}
			}
		}
		$Cache->cache_value('vote_pairs_'.$GroupID, $VotePairs);
	}
	
	// Now do the paired votes keys for all of this guy's other votes
	foreach ($UserVotes as $VGID => $Vote) {
		if ($Vote['Type'] != 'Up') {
			// We're only track paired votes on upvotes
			continue;
		}
		// Again, if the cache key is not set, move along
		$VotePairs = $Cache->get_value('vote_pairs_'.$VGID);
		if ($VotePairs !== FALSE) {
			if (isset($VotePairs[$GroupID])) {
				if ($VotePairs[$GroupID]['Total'] == 0) {
					// Something is screwy
					$Cache->delete_value('vote_pairs_'.$VGID);
					continue;
				}
				$VotePairs[$GroupID]['Total'] -= 1;
				if ($Type == 'Up') {
					$VotePairs[$GroupID]['Ups'] -= 1;
				}
				$Cache->cache_value('vote_pairs_'.$VGID, $VotePairs);
			} else {
				// Something is screwy, kill the key and move on
				$Cache->delete_value('vote_pairs_'.$VGID);
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