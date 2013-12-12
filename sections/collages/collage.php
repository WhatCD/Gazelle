<?
ini_set('max_execution_time', 600);

//~~~~~~~~~~~ Main collage page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

if (empty($_GET['id']) || !is_number($_GET['id'])) {
	error(0);
}
$CollageID = $_GET['id'];

$CollageData = $Cache->get_value("collage_$CollageID");
if ($CollageData) {
	list($Name, $Description, $CommentList, $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser, $Updated, $Subscribers) = $CollageData;
} else {
	$DB->query("
		SELECT
			Name,
			Description,
			UserID,
			Deleted,
			CategoryID,
			Locked,
			MaxGroups,
			MaxGroupsPerUser,
			Updated,
			Subscribers
		FROM collages
		WHERE ID = '$CollageID'");
	if ($DB->has_results()) {
		list($Name, $Description, $CreatorID, $Deleted, $CollageCategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser, $Updated, $Subscribers) = $DB->next_record(MYSQLI_NUM);
		$CommentList = null;
	} else {
		$Deleted = '1';
	}
	$SetCache = true;
}

if ($Deleted === '1') {
	header("Location: log.php?search=Collage+$CollageID");
	die();
}

// Handle subscriptions
if (($CollageSubscriptions = $Cache->get_value('collage_subs_user_'.$LoggedUser['ID'])) === false) {
	$DB->query("
		SELECT CollageID
		FROM users_collage_subs
		WHERE UserID = '$LoggedUser[ID]'");
	$CollageSubscriptions = $DB->collect(0);
	$Cache->cache_value('collage_subs_user_'.$LoggedUser['ID'], $CollageSubscriptions, 0);
}

if (!empty($CollageSubscriptions) && in_array($CollageID, $CollageSubscriptions)) {
	$DB->query("
		UPDATE users_collage_subs
		SET LastVisit = NOW()
		WHERE UserID = ".$LoggedUser['ID']."
			AND CollageID = $CollageID");
	$Cache->delete_value('collage_subs_user_new_'.$LoggedUser['ID']);
}

if ($CollageCategoryID == array_search(ARTIST_COLLAGE, $CollageCats)) {
	include(SERVER_ROOT.'/sections/collages/artist_collage.php');
} else {
	include(SERVER_ROOT.'/sections/collages/torrent_collage.php');
}

if (isset($SetCache)) {
	$CollageData = array(
		$Name,
		$Description,
		$CommentList,
		(bool)$Deleted,
		(int)$CollageCategoryID,
		(int)$CreatorID,
		(bool)$Locked,
		(int)$MaxGroups,
		(int)$MaxGroupsPerUser,
		$Updated,
		(int)$Subscribers);
	$Cache->cache_value("collage_$CollageID", $CollageData, 3600);
}
