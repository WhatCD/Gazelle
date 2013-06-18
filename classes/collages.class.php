<?
class Collages {
	public static function get_comment_count($CollageID) {
		global $DB, $Cache;
		$NumComments = $Cache->get_value('collage_comments_'.$CollageID);
		if ($NumComments === false) {
			$DB->query("
				SELECT COUNT(ID)
				FROM collages_comments
				WHERE CollageID = '$CollageID'");
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
		$DB->query("
			UPDATE collages
			SET Subscribers = Subscribers + 1
			WHERE ID = '$CollageID'");

	}

	public static function decrease_subscriptions($CollageID) {
		global $DB;
		$DB->query("
			UPDATE collages
			SET Subscribers = IF(Subscribers < 1, 0, Subscribers - 1)
			WHERE ID = '$CollageID'");
	}

	public static function create_personal_collage() {
		global $DB, $LoggedUser;

		$DB->query("
			SELECT
				COUNT(ID)
			FROM collages
			WHERE UserID = '$LoggedUser[ID]'
				AND CategoryID = '0'
				AND Deleted = '0'");
		list($CollageCount) = $DB->next_record();

		if ($CollageCount >= $LoggedUser['Permissions']['MaxCollages']) {
			list($CollageID) = $DB->next_record();
			header('Location: collage.php?id='.$CollageID);
			die();
		}
		$NameStr = ($CollageCount > 0) ? ' no. ' . ($CollageCount + 1) : '';
		$DB->query("
			INSERT INTO collages
				(Name, Description, CategoryID, UserID)
			VALUES
				('$LoggedUser[Username]\'s personal collage$NameStr', 'Personal collage for $LoggedUser[Username]. The first 5 albums will appear on his or her [url=https:\/\/".SSL_SITE_URL."\/user.php?id=$LoggedUser[ID]]profile[\/url].', '0', $LoggedUser[ID])");
		$CollageID = $DB->inserted_id();
		header('Location: collage.php?id='.$CollageID);
		die();
	}

}
