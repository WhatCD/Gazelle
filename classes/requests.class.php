<?
class Requests {
	/**
	 * Update the sphinx requests delta table for a request.
	 *
	 * @param $RequestID
	 */
	public static function update_sphinx_requests($RequestID) {
		$QueryID = G::$DB->get_query_id();

		G::$DB->query("
			SELECT REPLACE(t.Name, '.', '_')
			FROM tags AS t
				JOIN requests_tags AS rt ON t.ID = rt.TagID
			WHERE rt.RequestID = $RequestID");
		$TagList = G::$DB->collect(0, false);
		$TagList = db_string(implode(' ', $TagList));

		G::$DB->query("
			REPLACE INTO sphinx_requests_delta (
				ID, UserID, TimeAdded, LastVote, CategoryID, Title, TagList,
				Year, ReleaseType, CatalogueNumber, RecordLabel, BitrateList,
				FormatList, MediaList, LogCue, FillerID, TorrentID,
				TimeFilled, Visible, Votes, Bounty)
			SELECT
				ID, r.UserID, UNIX_TIMESTAMP(TimeAdded) AS TimeAdded,
				UNIX_TIMESTAMP(LastVote) AS LastVote, CategoryID, Title, '$TagList',
				Year, ReleaseType, CatalogueNumber, RecordLabel, BitrateList,
				FormatList, MediaList, LogCue, FillerID, TorrentID,
				UNIX_TIMESTAMP(TimeFilled) AS TimeFilled, Visible,
				COUNT(rv.UserID) AS Votes, SUM(rv.Bounty) >> 10 AS Bounty
			FROM requests AS r
				LEFT JOIN requests_votes AS rv ON rv.RequestID = r.ID
			WHERE ID = $RequestID
			GROUP BY r.ID");
		G::$DB->query("
			UPDATE sphinx_requests_delta
			SET ArtistList = (
					SELECT GROUP_CONCAT(aa.Name SEPARATOR ' ')
					FROM requests_artists AS ra
						JOIN artists_alias AS aa ON aa.AliasID = ra.AliasID
					WHERE ra.RequestID = $RequestID
					GROUP BY NULL
					)
			WHERE ID = $RequestID");
		G::$DB->set_query_id($QueryID);

		G::$Cache->delete_value("request_$RequestID");
	}



	/**
	 * Function to get data from an array of $RequestIDs. Order of keys doesn't matter (let's keep it that way).
	 *
	 * @param array $RequestIDs
	 * @param boolean $Return if set to false, data won't be returned (ie. if we just want to prime the cache.)
	 * @return The array of requests.
	 * Format: array(RequestID => Associative array)
	 * To see what's exactly inside each associate array, peek inside the function. It won't bite.
	 */
	//
	//In places where the output from this is merged with sphinx filters, it will be in a different order.
	public static function get_requests($RequestIDs, $Return = true) {
		$Found = $NotFound = array_fill_keys($RequestIDs, false);
		// Try to fetch the requests from the cache first.
		foreach ($RequestIDs as $i => $RequestID) {
			if (!is_number($RequestID)) {
				unset($RequestIDs[$i], $Found[$GroupID], $NotFound[$GroupID]);
				continue;
			}
			$Data = G::$Cache->get_value("request_$RequestID");
			if (!empty($Data)) {
				unset($NotFound[$RequestID]);
				$Found[$RequestID] = $Data;
			}
		}
		// Make sure there's something in $RequestIDs, otherwise the SQL will break
		if (count($RequestIDs) === 0) {
			return array();
		}
		$IDs = implode(',', array_keys($NotFound));

		/*
			Don't change without ensuring you change everything else that uses get_requests()
		*/

		if (count($NotFound) > 0) {
			$QueryID = G::$DB->get_query_id();

			G::$DB->query("
				SELECT
					ID,
					UserID,
					TimeAdded,
					LastVote,
					CategoryID,
					Title,
					Year,
					Image,
					Description,
					CatalogueNumber,
					RecordLabel,
					ReleaseType,
					BitrateList,
					FormatList,
					MediaList,
					LogCue,
					FillerID,
					TorrentID,
					TimeFilled,
					GroupID,
					OCLC
				FROM requests
				WHERE ID IN ($IDs)
				ORDER BY ID");
			$Requests = G::$DB->to_array(false, MYSQLI_ASSOC, true);
			$Tags = self::get_tags(G::$DB->collect('ID', false));
			foreach ($Requests as $Request) {
				unset($NotFound[$Request['ID']]);
				$Request['Tags'] = isset($Tags[$Request['ID']]) ? $Tags[$Request['ID']] : array();
				$Found[$Request['ID']] = $Request;
				G::$Cache->cache_value('request_'.$Request['ID'], $Request, 0);
			}
			G::$DB->set_query_id($QueryID);

			// Orphan requests. There shouldn't ever be any
			if (count($NotFound) > 0) {
				foreach (array_keys($NotFound) as $GroupID) {
					unset($Found[$GroupID]);
				}
			}
		}

		if ($Return) { // If we're interested in the data, and not just caching it
			return $Found;
		}
	}

	/**
	 * Return a single request. Wrapper for get_requests
	 *
	 * @param int $RequestID
	 * @return request array or false if request doesn't exist. See get_requests for a description of the format
	 */
	public static function get_request($RequestID) {
		$Request = self::get_requests(array($RequestID));
		if (isset($Request[$RequestID])) {
			return $Request[$RequestID];
		}
		return false;
	}

	public static function get_artists($RequestID) {
		$Artists = G::$Cache->get_value("request_artists_$RequestID");
		if (is_array($Artists)) {
			$Results = $Artists;
		} else {
			$Results = array();
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT
					ra.ArtistID,
					aa.Name,
					ra.Importance
				FROM requests_artists AS ra
					JOIN artists_alias AS aa ON ra.AliasID = aa.AliasID
				WHERE ra.RequestID = $RequestID
				ORDER BY ra.Importance ASC, aa.Name ASC;");
			$ArtistRaw = G::$DB->to_array();
			G::$DB->set_query_id($QueryID);
			foreach ($ArtistRaw as $ArtistRow) {
				list($ArtistID, $ArtistName, $ArtistImportance) = $ArtistRow;
				$Results[$ArtistImportance][] = array('id' => $ArtistID, 'name' => $ArtistName);
			}
			G::$Cache->cache_value("request_artists_$RequestID", $Results);
		}
		return $Results;
	}

	public static function get_tags($RequestIDs) {
		if (empty($RequestIDs)) {
			return array();
		}
		if (is_array($RequestIDs)) {
			$RequestIDs = implode(',', $RequestIDs);
		}
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT
				rt.RequestID,
				rt.TagID,
				t.Name
			FROM requests_tags AS rt
				JOIN tags AS t ON rt.TagID = t.ID
			WHERE rt.RequestID IN ($RequestIDs)
			ORDER BY rt.TagID ASC");
		$Tags = G::$DB->to_array(false, MYSQLI_NUM, false);
		G::$DB->set_query_id($QueryID);
		$Results = array();
		foreach ($Tags as $TagsRow) {
			list($RequestID, $TagID, $TagName) = $TagsRow;
			$Results[$RequestID][$TagID] = $TagName;
		}
		return $Results;
	}

	public static function get_votes_array($RequestID) {
		$RequestVotes = G::$Cache->get_value("request_votes_$RequestID");
		if (!is_array($RequestVotes)) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT
					rv.UserID,
					rv.Bounty,
					u.Username
				FROM requests_votes AS rv
					LEFT JOIN users_main AS u ON u.ID = rv.UserID
				WHERE rv.RequestID = $RequestID
				ORDER BY rv.Bounty DESC");
			if (!G::$DB->has_results()) {
				return array(
					'TotalBounty' => 0,
					'Voters' => array());
			}
			$Votes = G::$DB->to_array();

			$RequestVotes = array();
			$RequestVotes['TotalBounty'] = array_sum(G::$DB->collect('Bounty'));

			foreach ($Votes as $Vote) {
				list($UserID, $Bounty, $Username) = $Vote;
				$VoteArray = array();
				$VotesArray[] = array('UserID' => $UserID, 'Username' => $Username, 'Bounty' => $Bounty);
			}

			$RequestVotes['Voters'] = $VotesArray;
			G::$Cache->cache_value("request_votes_$RequestID", $RequestVotes);
			G::$DB->set_query_id($QueryID);
		}
		return $RequestVotes;
	}

}
