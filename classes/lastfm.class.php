<?

define('LASTFM_API_URL', 'http://ws.audioscrobbler.com/2.0/?method=');
class LastFM {

	public static function get_lastfm_username($UserID) {
		$Username = G::$Cache->get_value("lastfm_username_$UserID");
		if ($Username === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT Username
				FROM lastfm_users
				WHERE ID = $UserID");
			list($Username) = G::$DB->next_record();
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value("lastfm_username_$UserID", $Username, 0);
		}
		return $Username;
	}

	public static function get_artist_events($ArtistID, $Artist, $Limit = 15) {
		$ArtistEvents = G::$Cache->get_value("artist_events_$ArtistID");
		if (empty($ArtistEvents)) {
			$ArtistEvents = self::lastfm_request("artist.getEvents", array("artist" => $Artist, "limit" => $Limit));
			G::$Cache->cache_value("artist_events_$ArtistID", $ArtistEvents, 432000);
		}
		return $ArtistEvents;
	}

	public static function get_user_info($Username) {
		$Response = G::$Cache->get_value("lastfm_user_info_$Username");
		if (empty($Response)) {
			$Response = self::lastfm_request("user.getInfo", array("user" => $Username));
			G::$Cache->cache_value("lastfm_user_info_$Username", $Response, 86400);
		}
		return $Response;
	}

	public static function compare_user_with($Username1, $Limit = 15) {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT username
			FROM lastfm_users
			WHERE ID = '" . G::$LoggedUser['ID'] . "'");
		if (G::$DB->has_results()) {
			list($Username2) = G::$DB->next_record();
			//Make sure the usernames are in the correct order to avoid dupe cache keys.
			if (strcasecmp($Username1, $Username2)) {
				$Temp = $Username1;
				$Username1 = $Username2;
				$Username2 = $Temp;
			}
			$Response = G::$Cache->get_value("lastfm_compare_$Username1" . "_$Username2");
			if (empty($Response)) {
				$Response = self::lastfm_request("tasteometer.compare", array("type1" => "user", "type2" => "user", "value1" => $Username1, "value2" => $Username2, "limit" => $Limit));
				$Response = json_encode($Response);
				G::$Cache->cache_value("lastfm_compare_$Username1" . "_$Username2", $Response, 86400);
			}
		} else {
			$Response = null;
		}
		G::$DB->set_query_id($QueryID);
		return $Response;
	}

	public static function get_last_played_track($Username) {
		$Response = G::$Cache->get_value("lastfm_last_played_track_$Username");
		if (empty($Response)) {
			$Response = self::lastfm_request("user.getRecentTracks", array("user" => $Username, "limit" => 1));
			// Take the single last played track out of the response.
			$Response = $Response['recenttracks']['track'];
			$Response = json_encode($Response);
			G::$Cache->cache_value("lastfm_last_played_track_$Username", $Response, 7200);
		}
		return $Response;
	}

	public static function get_top_artists($Username, $Limit = 15) {
		$Response = G::$Cache->get_value("lastfm_top_artists_$Username");
		if (empty($Response)) {
			sleep(1);
			$Response = self::lastfm_request("user.getTopArtists", array("user" => $Username, "limit" => $Limit));
			$Response = json_encode($Response);
			G::$Cache->cache_value("lastfm_top_artists_$Username", $Response, 86400);
		}
		return $Response;
	}

	public static function get_top_albums($Username, $Limit = 15) {
		$Response = G::$Cache->get_value("lastfm_top_albums_$Username");
		if (empty($Response)) {
			sleep(2);
			$Response = self::lastfm_request("user.getTopAlbums", array("user" => $Username, "limit" => $Limit));
			$Response = json_encode($Response);
			G::$Cache->cache_value("lastfm_top_albums_$Username", $Response, 86400);
		}
		return $Response;
	}

	public static function get_top_tracks($Username, $Limit = 15) {
		$Response = G::$Cache->get_value("lastfm_top_tracks_$Username");
		if (empty($Response)) {
			sleep(3);
			$Response = self::lastfm_request("user.getTopTracks", array("user" => $Username, "limit" => $Limit));
			$Response = json_encode($Response);
			G::$Cache->cache_value("lastfm_top_tracks_$Username", $Response, 86400);
		}
		return $Response;
	}

	public static function get_user_artist_chart($Username, $From = '', $To = '') {
		$Response = G::$Cache->get_value("lastfm_artist_chart_$Username");
		if (empty($Response)) {
			$Response = self::lastfm_request("user.getWeeklyArtistChart", array("user" => $Username));
			$Response = json_encode($Response);
			G::$Cache->cache_value("lastfm_artist_chart_$Username", $Response, 86400);
		}
		return $Response;
	}

	public static function get_weekly_artists($Limit = 100) {
		$Response = G::$Cache->get_value("lastfm_top_artists_$Limit");
		if (empty($Response)) {
			$Response = self::lastfm_request("chart.getTopArtists", array("limit" => $Limit));
			$Response = json_encode($Response);
			G::$Cache->cache_value("lastfm_top_artists_$Limit", $Response, 86400);
		}
		return $Response;
	}

	public static function get_hyped_artists($Limit = 100) {
		$Response = G::$Cache->get_value("lastfm_hyped_artists_$Limit");
		if (empty($Response)) {
			$Response = self::lastfm_request("chart.getHypedArtists", array("limit" => $Limit));
			$Response = json_encode($Response);
			G::$Cache->cache_value("lastfm_hyped_artists_$Limit", $Response, 86400);
		}
		return $Response;
	}

	public static function clear_cache($Username, $UserID) {
		$Response = G::$Cache->get_value("lastfm_clear_cache_$UserID");
		if (empty($Response)) {
			// Prevent clearing the cache on the same uid page for the next 10 minutes.
			G::$Cache->cache_value("lastfm_clear_cache_$UserID", 1, 600);
			G::$Cache->delete_value("lastfm_user_info_$Username");
			G::$Cache->delete_value("lastfm_last_played_track_$Username");
			G::$Cache->delete_value("lastfm_top_artists_$Username");
			G::$Cache->delete_value("lastfm_top_albums_$Username");
			G::$Cache->delete_value("lastfm_top_tracks_$Username");
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT username
				FROM lastfm_users
				WHERE ID = " . G::$LoggedUser['ID']);
			if (G::$DB->has_results()) {
				list($Username2) = G::$DB->next_record();
				//Make sure the usernames are in the correct order to avoid dupe cache keys.
				if (strcasecmp($Username, $Username2)) {
					$Temp = $Username;
					$Username = $Username2;
					$Username2 = $Temp;
				}
				G::$Cache->delete_value("lastfm_compare_{$Username}_$Username2");
			}
			G::$DB->set_query_id($QueryID);
		}
	}

	private static function lastfm_request($Method, $Args) {
		if (!defined('LASTFM_API_KEY')) {
			return false;
		}
		$RecentFailsKey = 'lastfm_api_fails';
		$RecentFails = (int)G::$Cache->get_value($RecentFailsKey);
		if ($RecentFails > 5) {
			// Take a break if last.fm's API is down/nonfunctional
			return false;
		}
		$Url = LASTFM_API_URL . $Method;
		if (is_array($Args)) {
			foreach ($Args as $Key => $Value) {
				$Url .= "&$Key=" . urlencode($Value);
			}
			$Url .= "&format=json&api_key=" . LASTFM_API_KEY;

			$Curl = curl_init();
			curl_setopt($Curl, CURLOPT_HEADER, 0);
			curl_setopt($Curl, CURLOPT_TIMEOUT, 3);
			curl_setopt($Curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($Curl, CURLOPT_URL, $Url);
			$Return = curl_exec($Curl);
			$Errno = curl_errno($Curl);
			curl_close($Curl);
			if ($Errno) {
				G::$Cache->cache_value($RecentFailsKey, $RecentFails + 1, 1800);
				return false;
			}
			return json_decode($Return, true);
		}
	}
}
