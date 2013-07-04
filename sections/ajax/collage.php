<?
include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class
$Text = new TEXT;

if (empty($_GET['id'])) {
	json_die("failure", "bad parameters");
}

$CollageID = $_GET['id'];
if ($CollageID && !is_number($CollageID)) {
	json_die("failure");
}

$CacheKey = "collage_$CollageID";
$Data = $Cache->get_value($CacheKey);
if ($Data) {
	list($K, list($Name, $Description, , , , $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser)) = each($Data);
} else {
	$sql = "
		SELECT
			Name,
			Description,
			UserID,
			Deleted,
			CategoryID,
			Locked,
			MaxGroups,
			MaxGroupsPerUser,
			Subscribers
		FROM collages
		WHERE ID='$CollageID'";
	$DB->query($sql);

	if ($DB->record_count() == 0) {
		json_die("failure");
	}

	list($Name, $Description, $CreatorID, $Deleted, $CollageCategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser) = $DB->next_record();
}


$Cache->cache_value($CacheKey, array(array($Name, $Description, array(), array(), array(), $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser)), 3600);

json_die("success", array(
	'id' => (int) $CollageID,
	'name' => $Name,
	'description' => $Text->full_format($Description),
	'creatorID' => (int) $CreatorID,
	'deleted' => (bool) $Deleted,
	'collageCategoryID' => (int) $CollageCategoryID,
	'locked' => (bool) $Locked,
	'categoryID' => (int) $CategoryID,
	'maxGroups' => (int) $MaxGroups,
	'maxGroupsPerUser' => (int) $MaxGroupsPerUser,
	'hasBookmarked' => Bookmarks::has_bookmarked('collage', $CollageID),
	'cached' => (bool) $Cached,
));

?>