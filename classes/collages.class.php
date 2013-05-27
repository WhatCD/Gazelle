<?
class Collages {
	public static function get_comment_count($CollageID) {
		global $DB, $Cache;
		$NumComments = $Cache->get_value('collage_comments_'.$CollageID);
		if ($NumComments === false) {
			$DB->query("SELECT COUNT(ID) FROM collages_comments WHERE CollageID = '$CollageID'");
			list($NumComments) = $DB->next_record();
			$Cache->cache_value('collage_comments_'.$CollageID, $NumComments, 0);
		}
		return $NumComments;
	}

	public static function get_comment_catalogue($CollageID, $CatalogueID) {
		global $DB, $Cache;
		$Catalogue = $Cache->get_value('collage_comments_'.$CollageID.'_catalogue_'.$CatalogueID);
		if ($Catalogue === false) {
			$CatalogueLimit = $CatalogueID * THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;
			$DB->query("
				SELECT
					ID,
					UserID,
					Time,
					Body
				FROM collages_comments
				WHERE CollageID = '$CollageID'
				LIMIT $CatalogueLimit");
			$Catalogue = $DB->to_array(false, MYSQLI_ASSOC);
			$Cache->cache_value('collage_comments_'.$CollageID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
		}
		return $Catalogue;
	}

	public static function increase_subscriptions($CollageID) {
		global $DB;
		$DB->query("UPDATE collages SET Subscribers = Subscribers + 1 WHERE ID = '$CollageID'");

	}

	public static function decrease_subscriptions($CollageID) {
		global $DB;
		$DB->query("UPDATE collages SET Subscribers = IF(Subscribers < 1, 0, Subscribers - 1) WHERE ID = '$CollageID'");
	}

}