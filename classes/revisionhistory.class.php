<?
class RevisionHistory {
	/**
	 * Read the revision history of an artist or torrent page
	 * @param string $Page artists or torrents
	 * @param in $PageID
	 * @return array
	 */
	public static function get_revision_history($Page, $PageID) {
		if ($Page == 'artists') {
			$Table = 'wiki_artists';
		} else {
			$Table = 'wiki_torrents';
		}
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT
				RevisionID,
				Summary,
				Time,
				UserID
			FROM $Table
			WHERE PageID = $PageID
			ORDER BY RevisionID DESC");
		$Ret = G::$DB->to_array();
		G::$DB->set_query_id($QueryID);
		return $Ret;
	}
}
