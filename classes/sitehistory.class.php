<?

class SiteHistory {
	private static $Categories = array(1 => "Code", "Event", "Milestone", "Policy", "Release", "Staff Change");
	private static $SubCategories = array(1 => "Announcement", "Blog Post", "Change Log", "Forum Post", "Wiki", "Other", "External Source");
	private static $Tags = array(
								"api",
								"celebration",
								"class.primary",
								"class.secondary",
								"collage",
								"community",
								"conclusion",
								"contest",
								"design",
								"donate",
								"editing",
								"editorial",
								"feature",
								"featured.article",
								"featured.album",
								"featured.product",
								"finances",
								"format",
								"forum",
								"freeleech",
								"freeleech.tokens",
								"gazelle",
								"hierarchy",
								"inbox",
								"infrastructure",
								"interview",
								"irc",
								"log",
								"neutral.leech",
								"notifications",
								"ocelot",
								"paranoia",
								"picks.guest",
								"picks.staff",
								"promotion",
								"ratio",
								"record",
								"report",
								"request",
								"requirement",
								"retirement",
								"rippy",
								"search",
								"settings",
								"start",
								"stats",
								"store",
								"stylesheet",
								"tagging",
								"transcode",
								"toolbox",
								"top.10",
								"torrent",
								"torrent.group",
								"upload",
								"vanity.house",
								"voting",
								"whitelist",
								"wiki");

	public static function get_months() {
		$Results = G::$Cache->get_value("site_history_months");
		if (!$Results) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
					SELECT DISTINCT
						YEAR(DATE) AS Year, MONTH(Date) AS Month, MONTHNAME(Date) AS MonthName
					FROM site_history
					ORDER BY Date DESC");
			$Results = G::$DB->to_array();
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value("site_history_months", $Results, 0);
		}
		return $Results;
	}

	public static function get_event($ID) {
		if (!empty($ID)) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
					SELECT
						ID, Title, Url, Category, SubCategory, Tags, Body, AddedBy, Date
					FROM site_history
					WHERE ID = '$ID'
					ORDER BY Date DESC");
			$Event = G::$DB->next_record();
			G::$DB->set_query_id($QueryID);
			return $Event;
		}
	}

	public static function get_latest_events($Limit) {
		self::get_events(null, null, null, null, null, null, $Limit);
	}

	public static function get_events($Month, $Year, $Title, $Category, $SubCategory, $Tags, $Limit) {
		$Month = (int)$Month;
		$Year = (int)$Year;
		$Title = db_string($Title);
		$Category = (int)$Category;
		$SubCategory = (int)$SubCategory;
		$Tags = db_string($Tags);
		$Limit = (int)$Limit;
		$Where = array();
		if (!empty($Month)) {
			$Where[] = " MONTH(Date) = '$Month' ";
		}
		if (!empty($Year)) {
			$Where[] = " YEAR(Date) = '$Year' ";
		}
		if (!empty($Title)) {
			$Where[] = " Title LIKE '%$Title%' ";
		}
		if (!empty($Category)) {
			$Where[] = " Category = '$Category '";
		}
		if (!empty($SubCategory)) {
			$Where[] = " SubCategory = '$SubCategory '";
		}
		if (!empty($Tags)) {
			$Tags = explode(',', $Tags);
			$Or = '(';
			foreach ($Tags as $Tag) {
				$Tag = trim($Tag);
				$Or .= " Tags LIKE '%$Tag%' OR ";
			}
			if (strlen($Or) > 1) {
				$Or = rtrim($Or, 'OR ');
				$Or .= ')';
				$Where[] = $Or;
			}
		}
		if (!empty($Limit)) {
			$Limit = " LIMIT $Limit";
		} else {
			$Limit = '';
		}
		if (count($Where) > 0) {
			$Query = ' WHERE ' . implode('AND', $Where);
		} else {
			$Query = '';
		}

		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
				SELECT
					ID, Title, Url, Category, SubCategory, Tags, Body, AddedBy, Date
				FROM site_history
				$Query
				ORDER BY Date DESC
				$Limit");
		$Events = G::$DB->to_array();
		G::$DB->set_query_id($QueryID);
		return $Events;
	}

	public static function add_event($Date, $Title, $Link, $Category, $SubCategory, $Tags, $Body, $UserID) {
		if (empty($Date)) {
			$Date = sqltime();
		} else {
			list($Y, $M, $D) = explode('-', $Date);
			if (!checkdate($M, $D, $Y)) {
				error("Error");
			}
		}
		$Title = db_string($Title);
		$Link = db_string($Link);
		$Category = (int)$Category;
		$SubCategory = (int)$SubCategory;
		$Tags = db_string(strtolower((preg_replace('/\s+/', '', $Tags))));
		$ExplodedTags = explode(',', $Tags);
		foreach ($ExplodedTags as $Tag) {
			if (!in_array($Tag, self::get_tags())) {
				error("Invalid tag");
			}
		}
		$Body = db_string($Body);
		$UserID = (int)$UserID;

		if (empty($Title) || empty($Category) || empty($SubCategory)) {
			error("Error");
		}

		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
				INSERT INTO site_history
					(Title, Url, Category, SubCategory, Tags, Body, AddedBy, Date)
				VALUES
					('$Title', '$Link', '$Category', '$SubCategory', '$Tags', '$Body', '$UserID', '$Date')");
		G::$DB->set_query_id($QueryID);
		G::$Cache->delete_value("site_history_months");
	}

	public static function update_event($ID, $Date, $Title, $Link, $Category, $SubCategory, $Tags, $Body, $UserID) {
		if (empty($Date)) {
			$Date = sqltime();
		} else {
			$Date = db_string($Date);
			list($Y, $M, $D) = explode('-', $Date);
			if (!checkdate($M, $D, $Y)) {
				error("Error");
			}
		}
		$ID = (int)$ID;
		$Title = db_string($Title);
		$Link = db_string($Link);
		$Category = (int)$Category;
		$SubCategory = (int)$SubCategory;
		$Tags = db_string(strtolower((preg_replace('/\s+/', '', $Tags))));
		$ExplodedTags = explode(",", $Tags);
		foreach ($ExplodedTags as $Tag) {
			if (!in_array($Tag, self::get_tags())) {
				error("Invalid tag");
			}
		}
		$Body = db_string($Body);
		$UserID = (int)$UserID;

		if (empty($ID) || empty($Title) || empty($Category) || empty($SubCategory)) {
			error("Error");
		}

		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
				UPDATE site_history
				SET
					Title = '$Title',
					Url = '$Link',
					Category = '$Category',
					SubCategory = '$SubCategory',
					Tags = '$Tags',
					Body = '$Body',
					AddedBy = '$UserID',
					Date = '$Date'
				WHERE ID = '$ID'");
		G::$DB->set_query_id($QueryID);
		G::$Cache->delete_value("site_history_months");
	}

	public static function delete_event($ID) {
		if (!is_numeric($ID)) {
			error(404);
		}
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
				DELETE FROM site_history
				WHERE ID = '$ID'");
		G::$DB->set_query_id($QueryID);
		G::$Cache->delete_value("site_history_months");
	}

	public static function get_categories() {
		return self::$Categories;
	}

	public static function get_sub_categories() {
		return self::$SubCategories;
	}

	public static function get_tags() {
		return self::$Tags;
	}
}
