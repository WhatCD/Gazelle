<?
class ALIAS {
	function convert($str) {
		return trim(substr(preg_replace('/[^a-z0-9]/', '', strtolower(htmlentities($str))), 0, 50));
	}

	//Alternative approach with potential.
	function flush() {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT Alias, ArticleID
			FROM wiki_aliases");
		$Aliases = G::$DB->to_array('Alias');
		G::$DB->set_query_id($QueryID);
		G::$Cache->cache_value('wiki_aliases', $Aliases, 3600 * 24 * 14); // 2 weeks
	}

	function to_id($Alias) {
		$Aliases = G::$Cache->get_value('wiki_aliases');
		if (!$Aliases) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT Alias, ArticleID
				FROM wiki_aliases");
			$Aliases = G::$DB->to_array('Alias');
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value('wiki_aliases', $Aliases, 3600 * 24 * 14); // 2 weeks
		}
		return $Aliases[$this->convert($Alias)]['ArticleID'];
	}
/*
	function flush() {

	}

	function to_id($Alias) {
		$Alias = $this->convert($Alias);
		G::$DB->query("
			SELECT ArticleID
			FROM wiki_aliases
			WHERE Alias LIKE '$Alias'");
		list($ArticleID) = G::$DB->next_record();
		return $ArticleID;
	}
*/
	function article($ArticleID, $Error = true) {
		$Contents = G::$Cache->get_value('wiki_article_'.$ArticleID);
		if (!$Contents) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT
					w.Revision,
					w.Title,
					w.Body,
					w.MinClassRead,
					w.MinClassEdit,
					w.Date,
					w.Author,
					u.Username,
					GROUP_CONCAT(a.Alias),
					GROUP_CONCAT(a.UserID)
				FROM wiki_articles AS w
					LEFT JOIN wiki_aliases AS a ON w.ID=a.ArticleID
					LEFT JOIN users_main AS u ON u.ID=w.Author
				WHERE w.ID='$ArticleID'
				GROUP BY w.ID");
			if (!G::$DB->has_results() && $Error) {
				error(404);
			}
			$Contents = G::$DB->to_array();
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value('wiki_article_'.$ArticleID, $Contents, 3600 * 24 * 14); // 2 weeks
		}
		return $Contents;
	}
}
?>
