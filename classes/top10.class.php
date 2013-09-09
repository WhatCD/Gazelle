<?

class Top10 {

	public static function get_top_artists($Limit = '100') {
		$Artists = G::$Cache->get_value("top_artists_$Limit");
		if ($Artists === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT
					a.ArtistID,
					a.Name,
					aw.Image
				FROM torrents AS t
					LEFT JOIN torrents_artists AS ta ON ta.GroupID = t.GroupID
					LEFT JOIN artists_group AS a ON a.ArtistID = ta.ArtistID
					LEFT JOIN wiki_artists AS aw ON aw.RevisionID = a.RevisionID
				WHERE
					(t.Snatched + t.Seeders) > (SELECT GREATEST(MAX(Snatched), MAX(Seeders)) / 2 FROM torrents)
				GROUP BY a.ArtistID
				ORDER BY (t.Snatched + t.Seeders) DESC
				LIMIT $Limit");
			$Artists = G::$DB->to_array();
			G::$Cache->cache_value($Artists, "top_artists_$Limit", 86400);
			G::$DB->set_query_id($QueryID);
		}
		return $Artists;
	}

}
