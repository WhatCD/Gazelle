<?
class Forums {
	/**
	 * @param string $Body
	 * @param int $PostID
	 * @param string $Page
	 * @param int $PageID
	 */
	public static function quote_notify($Body, $PostID, $Page, $PageID) {
		/*
		 * Explanation of the parameters PageID and Page:
		 * Page contains where this quote comes from and can be forums, artist,
		 * collages, requests or torrents. The PageID contains the additional
		 * value that is necessary for the users_notify_quoted table.
		 * The PageIDs for the different Page are:
		 *  forums: TopicID
		 *  artist: ArtistID
		 *  collages: CollageID
		 *  requests: RequestID
		 *  torrents: GroupID
		 */
		global $LoggedUser, $Cache, $DB;

		$Matches = array();
		preg_match_all('/\[quote(?:=(.*)(?:\|.*)?)?]|\[\/quote]/iU', $Body, $Matches, PREG_SET_ORDER);

		if (count($Matches)) {
			$Usernames = array();
			$Level = 0;
			foreach ($Matches as $M) {
				if ($M[0] != '[/quote]') {
					if ($Level == 0 && isset($M[1]) && strlen($M[1]) > 0 && preg_match(USERNAME_REGEX, $M[1])) {
						$Usernames[] = preg_replace('/(^[.,]*)|([.,]*$)/', '', $M[1]); // wut?
					}
					++$Level;
				} else {
					--$Level;
				}
			}
		}
		//remove any dupes in the array (the fast way)
		$Usernames = array_flip(array_flip($Usernames));

		$DB->query("
			SELECT m.ID, p.PushService
			FROM users_main AS m
				LEFT JOIN users_info AS i ON i.UserID = m.ID
				LEFT JOIN users_push_notifications AS p ON p.UserID = m.ID
			WHERE m.Username IN ('" . implode("', '", $Usernames) . "')
				AND i.NotifyOnQuote = '1'
				AND i.UserID != $LoggedUser[ID]");

		$Results = $DB->to_array();
		foreach ($Results as $Result) {
			$UserID = db_string($Result['ID']);
			$PushService = $Result['PushService'];
			$QuoterID = db_string($LoggedUser['ID']);
			$Page = db_string($Page);
			$PageID = db_string($PageID);
			$PostID = db_string($PostID);

			$DB->query("
				INSERT IGNORE INTO users_notify_quoted
					(UserID, QuoterID, Page, PageID, PostID, Date)
				VALUES
					('$UserID', '$QuoterID', '$Page', '$PageID', '$PostID', '" . sqltime() . "')");
			$Cache->delete_value('notify_quoted_' . $UserID);

		}
	}
}
