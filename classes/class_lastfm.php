<?
define('LASTFM_API_URL', 'http://ws.audioscrobbler.com/2.0/?method=');
class LastFM {
	
	public static function get_artist_events($ArtistID, $Artist, $Limit = 15) {
		global $Cache;
		$ArtistEvents = $Cache->get_value('artist_events_'.$ArtistID);
		if(empty($ArtistEvents)) {
			$ArtistEvents = self::lastfm_request("artist.getEvents", array("artist" => $Artist, "limit" => $Limit));	
			$Cache->cache_value('artist_events_'.$ArtistID, $ArtistEvents, 432000);
		}
		return $ArtistEvents;
	}

	private static function lastfm_request($Method, $Args) {
		if (!defined('LASTFM_API_KEY')) {
			return false;
		}
		$Url = LASTFM_API_URL.$Method;
		if(is_array($Args)) {
			foreach ($Args as $Key => $Value) {
				$Url .= "&".$Key."=".urlencode($Value); 
			}
			$Url .= "&format=json&api_key=".LASTFM_API_KEY;

			$Curl=curl_init();
			curl_setopt($Curl,CURLOPT_HEADER,0);
			curl_setopt($Curl,CURLOPT_CONNECTTIMEOUT,30);
			curl_setopt($Curl,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($Curl,CURLOPT_URL,$Url);
			$Return=curl_exec($Curl);
			curl_close($Curl);
			return json_decode($Return, true);
		}
	}
}

	