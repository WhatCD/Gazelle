<?
class Requests {
	/**
	 * Update the sphinx requests delta table for a request.
	 *
	 * @param $RequestID
	 */
	public static function update_sphinx_requests($RequestID) {
		global $DB, $Cache;

		$DB->query("REPLACE INTO sphinx_requests_delta (
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
				FROM requests AS r LEFT JOIN requests_votes AS rv ON rv.RequestID=r.ID
					wHERE ID = ".$RequestID."
					GROUP BY r.ID");

		$DB->query("UPDATE sphinx_requests_delta
						SET ArtistList = (SELECT
							GROUP_CONCAT(aa.Name SEPARATOR ' ')
						FROM requests_artists AS ra
							JOIN artists_alias AS aa ON aa.AliasID=ra.AliasID
						WHERE ra.RequestID = ".$RequestID."
						GROUP BY NULL)
					WHERE ID = ".$RequestID);

		$Cache->delete_value('requests_'.$RequestID);
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
		global $DB, $Cache;

		// Try to fetch the requests from the cache first.
		$Found = array_flip($RequestIDs);
		$NotFound = array_flip($RequestIDs);

		foreach ($RequestIDs as $RequestID) {
			$Data = $Cache->get_value('request_'.$RequestID);
			if (!empty($Data)) {
				unset($NotFound[$RequestID]);
				$Found[$RequestID] = $Data;
			}
		}

		$IDs = implode(',',array_flip($NotFound));

		/*
			Don't change without ensuring you change everything else that uses get_requests()
		*/

		if (count($NotFound) > 0) {
			$DB->query("SELECT
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
						LEFT JOIN users_main AS u ON u.ID=r.UserID
						LEFT JOIN users_main AS filler ON filler.ID=FillerID AND FillerID!=0
					WHERE r.ID IN (".$IDs.")
					ORDER BY ID");

			$Requests = $DB->to_array();
			foreach ($Requests as $Request) {
				unset($NotFound[$Request['ID']]);
				$Request['Tags'] = get_request_tags($Request['ID']);
				$Found[$Request['ID']] = $Request;
				$Cache->cache_value('request_'.$Request['ID'], $Request, 0);
			}
		}

		if ($Return) { // If we're interested in the data, and not just caching it
			$Matches = array('matches'=>$Found, 'notfound'=>array_flip($NotFound));
			return $Matches;
		}
	}

}
?>
