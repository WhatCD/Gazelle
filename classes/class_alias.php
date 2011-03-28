<?
class ALIAS {
	function convert($str) {
		return trim(substr(preg_replace('/[^a-z0-9]/', '', strtolower(htmlentities($str))), 0, 50));
	}

	//Alternative approach with potential.
	function flush() {
		global $Cache, $DB;
		$DB->query("SELECT Alias, ArticleID FROM wiki_aliases");
		$Aliases = $DB->to_array('Alias');
		$Cache->cache_value('wiki_aliases', $Aliases, 3600*24*14);
	}
	
	function to_id($Alias) {
		global $Cache, $DB;
		$Aliases = $Cache->get_value('wiki_aliases');
		if(!$Aliases){
			$DB->query("SELECT Alias, ArticleID FROM wiki_aliases");
			$Aliases = $DB->to_array('Alias');
			$Cache->cache_value('wiki_aliases', $Aliases, 3600*24*14);
		}
		return $Aliases[$this->convert($Alias)]['ArticleID'];
	}
/*
	function flush() {

	}
	
	function to_id($Alias) {
		global $DB;
		$Alias = $this->convert($Alias);
		$DB->query("SELECT ArticleID FROM wiki_aliases WHERE Alias LIKE '$Alias'");
		list($ArticleID) = $DB->next_record();
		return $ArticleID;
	}
*/
	function article($ArticleID) {
		global $Cache, $DB;
		$Contents = $Cache->get_value('wiki_article_'.$ArticleID);
		if(!$Contents){
			$DB->query("SELECT
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
			if(!$DB->record_count()) { error(404); }
			$Contents = $DB->to_array();
			$Cache->cache_value('wiki_article_'.$ArticleID, $Contents, 3600*24*14);
		}
		return $Contents;
	}
}
?>
