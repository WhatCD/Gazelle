<?
class Bookmarks {

	/**
	 * Check if can bookmark
	 *
	 * @param string $Type
	 * @return boolean
	 */
	public static function can_bookmark($Type) {
		return in_array($Type, array(
				'torrent',
				'artist',
				'collage',
				'request'
		));
	}

	/**
	 * Get the bookmark schema.
	 * Recommended usage:
	 * list($Table, $Col) = bookmark_schema('torrent');
	 *
	 * @param string $Type the type to get the schema for
	 */
	public static function bookmark_schema($Type) {
		switch ($Type) {
			case 'torrent':
				return array(
						'bookmarks_torrents',
						'GroupID'
				);
				break;
			case 'artist':
				return array(
						'bookmarks_artists',
						'ArtistID'
				);
				break;
			case 'collage':
				return array(
						'bookmarks_collages',
						'CollageID'
				);
				break;
			case 'request':
				return array(
						'bookmarks_requests',
						'RequestID'
				);
				break;
			default:
				die('HAX');
		}
	}

	/**
	 * Check if something is bookmarked
	 *
	 * @param string $Type
	 *        	type of bookmarks to check
	 * @param int $ID
	 *        	bookmark's id
	 * @return boolean
	 */
	public static function has_bookmarked($Type, $ID) {
		return in_array($ID, self::all_bookmarks($Type));
	}

	/**
	 * Fetch all bookmarks of a certain type for a user.
	 * If UserID is false than defaults to G::$LoggedUser['ID']
	 *
	 * @param string $Type
	 *        	type of bookmarks to fetch
	 * @param int $UserID
	 *        	userid whose bookmarks to get
	 * @return array the bookmarks
	 */
	public static function all_bookmarks($Type, $UserID = false) {
		if ($UserID === false) {
			$UserID = G::$LoggedUser['ID'];
		}
		$CacheKey = 'bookmarks_' . $Type . '_' . $UserID;
		if (($Bookmarks = G::$Cache->get_value($CacheKey)) === false) {
			list ($Table, $Col) = self::bookmark_schema($Type);
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT $Col
				FROM $Table
				WHERE UserID = '$UserID'");
			$Bookmarks = G::$DB->collect($Col);
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value($CacheKey, $Bookmarks, 0);
		}
		return $Bookmarks;
	}
}
?>
