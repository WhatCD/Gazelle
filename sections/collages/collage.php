<?
ini_set('max_execution_time',600);

//~~~~~~~~~~~ Main collage page ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//

function compare($X, $Y) {
	return($Y['count'] - $X['count']);
}

include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class

$Text = new TEXT;

$UserVotes = Votes::get_user_votes($LoggedUser['ID']);

$CollageID = $_GET['id'];
if (!is_number($CollageID)) {
	error(0);
}

$Data = $Cache->get_value('collage_'.$CollageID);

if ($Data) {
	list($K, list($Name, $Description, , , $CommentList, $Deleted, $CollageCategoryID, $CreatorID, $Locked, $MaxGroups, $MaxGroupsPerUser, $Updated, $Subscribers)) = each($Data);
} else {
	$DB->query("SELECT Name, Description, UserID, Deleted, CategoryID, Locked, MaxGroups, MaxGroupsPerUser, Updated, Subscribers FROM collages WHERE ID='$CollageID'");
	if ($DB->record_count() > 0) {
		list($Name, $Description, $CreatorID, $Deleted, $CollageCategoryID, $Locked, $MaxGroups, $MaxGroupsPerUser, $Updated, $Subscribers) = $DB->next_record();
		$TorrentList = '';
		$CollageList = '';
	} else {
		$Deleted = '1';
	}
}

if ($Deleted == '1') {
	header('Location: log.php?search=Collage+'.$CollageID);
	die();
}

if ($CollageCategoryID == 0 && !check_perms('site_collages_delete')) {
	if (!check_perms('site_collages_personal') || $CreatorID != $LoggedUser['ID']) {
		$PreventAdditions = true;
	}
}

//Handle subscriptions
if (($CollageSubscriptions = $Cache->get_value('collage_subs_user_'.$LoggedUser['ID'])) === false) {
	$DB->query("SELECT CollageID FROM users_collage_subs WHERE UserID = '$LoggedUser[ID]'");
	$CollageSubscriptions = $DB->collect(0);
	$Cache->cache_value('collage_subs_user_'.$LoggedUser['ID'],$CollageSubscriptions,0);
}

if (empty($CollageSubscriptions)) {
	$CollageSubscriptions = array();
}

if (in_array($CollageID, $CollageSubscriptions)) {
	$Cache->delete_value('collage_subs_user_new_'.$LoggedUser['ID']);
}
$DB->query("UPDATE users_collage_subs SET LastVisit=NOW() WHERE UserID = ".$LoggedUser['ID']." AND CollageID=$CollageID");

if ($CollageCategoryID == array_search(ARTIST_COLLAGE, $CollageCats)) {
	include(SERVER_ROOT.'/sections/collages/artist_collage.php');
} else {
	include(SERVER_ROOT.'/sections/collages/torrent_collage.php');
}

