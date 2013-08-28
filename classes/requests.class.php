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
			REPLACE INTO sphinx_requests_delta (
				ID, UserID, TimeAdded, LastVote, CategoryID, Title,
				Year, ReleaseType, CatalogueNumber, BitrateList,
				FormatList, MediaList, LogCue, FillerID, TorrentID,
				TimeFilled, Visible, Votes, Bounty)
			SELECT
				ID, r.UserID, UNIX_TIMESTAMP(TimeAdded) AS TimeAdded,
				UNIX_TIMESTAMP(LastVote) AS LastVote, CategoryID,
				Title, Year, ReleaseType, CatalogueNumber, BitrateList,
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

		G::$Cache->delete_value("requests_$RequestID");
	}



	/**
	 * Function to get data from an array of $RequestIDs.
	 * In places where the output from this is merged with sphinx filters, it will be in a different order.
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

		// Try to fetch the requests from the cache first.
		$Found = array_flip($RequestIDs);
		$NotFound = array_flip($RequestIDs);

		foreach ($RequestIDs as $RequestID) {
			$Data = G::$Cache->get_value("request_$RequestID");
			if (!empty($Data)) {
				unset($NotFound[$RequestID]);
				$Found[$RequestID] = $Data;
			}
		}

		$IDs = implode(',', array_flip($NotFound));

		/*
			Don't change without ensuring you change everything else that uses get_requests()
		*/

		if (count($NotFound) > 0) {
			$QueryID = G::$DB->get_query_id();

			G::$DB->query("
				SELECT
					r.ID AS ID,
					r.UserID,
					u.Username,
					r.TimeAdded,
					r.LastVote,
					r.CategoryID,
					r.Title,
					r.Year,
					r.Image,
					r.Description,
					r.CatalogueNumber,
					r.RecordLabel,
					r.ReleaseType,
					r.BitrateList,
					r.FormatList,
					r.MediaList,
					r.LogCue,
					r.FillerID,
					filler.Username,
					r.TorrentID,
					r.TimeFilled,
					r.GroupID,
					r.OCLC
				FROM requests AS r
					LEFT JOIN users_main AS u ON u.ID = r.UserID
					LEFT JOIN users_main AS filler ON filler.ID = FillerID AND FillerID != 0
				WHERE r.ID IN ($IDs)
				ORDER BY ID");
			$Requests = G::$DB->to_array();
			G::$DB->set_query_id($QueryID);

			foreach ($Requests as $Request) {
				unset($NotFound[$Request['ID']]);
				$Request['Tags'] = self::get_tags($Request['ID']);
				$Found[$Request['ID']] = $Request;
				G::$Cache->cache_value('request_'.$Request['ID'], $Request, 0);
			}
		}

		if ($Return) { // If we're interested in the data, and not just caching it
			$Matches = array('matches' => $Found, 'notfound' => array_flip($NotFound));
			return $Matches;
		}
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

	public static function get_tags($RequestID) {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT
				rt.TagID,
				t.Name
			FROM requests_tags AS rt
				JOIN tags AS t ON rt.TagID = t.ID
			WHERE rt.RequestID = $RequestID
			ORDER BY rt.TagID ASC");
		$Tags = G::$DB->to_array();
		G::$DB->set_query_id($QueryID);
		$Results = array();
		foreach ($Tags as $TagsRow) {
			list($TagID, $TagName) = $TagsRow;
			$Results[$TagID] = $TagName;
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
				FROM requests_votes as rv
					LEFT JOIN users_main AS u ON u.ID = rv.UserID
				WHERE rv.RequestID = $RequestID
				ORDER BY rv.Bounty DESC");
			if (!G::$DB->has_results()) {
				error(0);
			} else {
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
			}
			G::$DB->set_query_id($QueryID);
		}
		return $RequestVotes;
	}

}
?>
