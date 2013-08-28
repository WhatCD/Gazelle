<?
$RequestTax = 0.1;

// Minimum and default amount of upload to remove from the user when they vote.
// Also change in static/functions/requests.js
$MinimumVote = 20 * 1024 * 1024;

/*
 * This is the page that displays the request to the end user after being created.
 */

include(SERVER_ROOT.'/classes/text.class.php');
$Text = new TEXT;

if (empty($_GET['id']) || !is_number($_GET['id'])) {
	json_die("failure");
}

$RequestID = (int)$_GET['id'];

//First things first, lets get the data for the request.

$Request = Requests::get_requests(array($RequestID));
$Request = $Request['matches'][$RequestID];
if (empty($Request)) {
	json_die("failure");
}

list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Year, $Image, $Description, $CatalogueNumber, $RecordLabel, $ReleaseType,
	$BitrateList, $FormatList, $MediaList, $LogCue, $FillerID, $FillerName, $TorrentID, $TimeFilled, $GroupID, $OCLC) = $Request;

//Convenience variables
$IsFilled = !empty($TorrentID);
$CanVote = (empty($TorrentID) && check_perms('site_vote'));

if ($CategoryID == 0) {
	$CategoryName = 'Unknown';
} else {
	$CategoryName = $Categories[$CategoryID - 1];
}

//Do we need to get artists?
if ($CategoryName == 'Music') {
	$ArtistForm = Requests::get_artists($RequestID);
	$ArtistName = Artists::display_artists($ArtistForm, false, true);
	$ArtistLink = Artists::display_artists($ArtistForm, true, true);

	if (empty($ReleaseType)) {
		$ReleaseName = 'Unknown';
	} else {
		$ReleaseName = $ReleaseTypes[$ReleaseType];
	}
}

//Votes time
$RequestVotes = Requests::get_votes_array($RequestID);
$VoteCount = count($RequestVotes['Voters']);
$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0) || ($CategoryName == 'Music' && $Year == 0)));
$UserCanEdit = (!$IsFilled && $LoggedUser['ID'] == $RequestorID && $VoteCount < 2);
$CanEdit = ($UserCanEdit || $ProjectCanEdit || check_perms('site_moderate_requests'));

if ($CategoryName == "Music") {
	$JsonMusicInfo = array(
		/*'composers' => $ArtistForm[4] != null ? $ArtistForm[4] : array(),
		'dj'        => $ArtistForm[6] != null ? $ArtistForm[6] : array(),
		'artists'   => $ArtistForm[1] != null ? $ArtistForm[1] : array(),
		'with'      => $ArtistForm[2] != null ? $ArtistForm[2] : array(),
		'conductor' => $ArtistForm[5] != null ? $ArtistForm[5] : array(),
		'remixedBy' => $ArtistForm[3] != null ? $ArtistForm[3] : array()*/
		'composers' => $ArtistForm[4] == null ? array() : pullmediainfo($ArtistForm[4]),
		'dj'        => $ArtistForm[6] == null ? array() : pullmediainfo($ArtistForm[6]),
		'artists'   => $ArtistForm[1] == null ? array() : pullmediainfo($ArtistForm[1]),
		'with'      => $ArtistForm[2] == null ? array() : pullmediainfo($ArtistForm[2]),
		'conductor' => $ArtistForm[5] == null ? array() : pullmediainfo($ArtistForm[5]),
		'remixedBy' => $ArtistForm[3] == null ? array() : pullmediainfo($ArtistForm[3]),
		'producer'  => $ArtistForm[7] == null ? array() : pullmediainfo($ArtistForm[7])
	);
} else {
	$JsonMusicInfo = new stdClass; //json_encodes into an empty object: {}
}

$JsonTopContributors = array();
$VoteMax = ($VoteCount < 5 ? $VoteCount : 5);
for ($i = 0; $i < $VoteMax; $i++) {
	$User = array_shift($RequestVotes['Voters']);
	$JsonTopContributors[] = array(
		'userId' => (int) $User['UserID'],
		'userName' => $User['Username'],
		'bounty' => (int) $User['Bounty']
	);
}
reset($RequestVotes['Voters']);

list($NumComments, $Page, $Thread) = Comments::load('requests', $RequestID, false);

$JsonRequestComments = array();
foreach ($Thread as $Key => $Post) {
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(Users::user_info($AuthorID));
	$JsonRequestComments[] = array(
		'postId' => (int) $PostID,
		'authorId' => (int) $AuthorID,
		'name' => $Username,
		'donor' => $Donor == 1,
		'warned' => ($Warned!='0000-00-00 00:00:00'),
		'enabled' => ($Enabled == 2 ? false : true),
		'class' => Users::make_class_string($PermissionID),
		'addedTime' => $AddedTime,
		'avatar' => $Avatar,
		'comment' => $Text->full_format($Body),
		'editedUserId' => (int) $EditedUserID,
		'editedUsername' => $EditedUsername,
		'editedTime' => $EditedTime
	);
}

$JsonTags = array();
foreach ($Request['Tags'] as $Tag) {
	$JsonTags[] = $Tag;
}

json_die("success", array(
	'requestId' => (int) $RequestID,
	'requestorId' => (int) $RequestorID,
	'requestorName' => $RequestorName,
	'isBookmarked' => Bookmarks::has_bookmarked('request', $RequestID),
	'requestTax' => $RequestTax,
	'timeAdded' => $TimeAdded,
	'canEdit' => $CanEdit,
	'canVote' => $CanVote,
	'minimumVote' => $MinimumVote,
	'voteCount' => $VoteCount,
	'lastVote' => $LastVote,
	'topContributors' => $JsonTopContributors,
	'totalBounty' => (int) $RequestVotes['TotalBounty'],
	'categoryId' => (int) $CategoryID,
	'categoryName' => $CategoryName,
	'title' => $Title,
	'year' => (int) $Year,
	'image' => $Image,
	'bbDescription' => $Description,
	'description' => $Text->full_format($Description),
	'musicInfo' => $JsonMusicInfo,
	'catalogueNumber' => $CatalogueNumber,
	'releaseType' => (int) $ReleaseType,
	'releaseName' => $ReleaseName,
	'bitrateList' => preg_split('/\|/', $BitrateList, NULL, PREG_SPLIT_NO_EMPTY),
	'formatList' => preg_split('/\|/', $FormatList, NULL, PREG_SPLIT_NO_EMPTY),
	'mediaList' => preg_split('/\|/', $MediaList, NULL, PREG_SPLIT_NO_EMPTY),
	'logCue' => html_entity_decode($LogCue),
	'isFilled' => $IsFilled,
	'fillerId' => (int) $FillerID,
	'fillerName' => $FillerName,
	'torrentId' => (int) $TorrentID,
	'timeFilled' => $TimeFilled,
	'tags' => $JsonTags,
	'comments' => $JsonRequestComments,
	'commentPage' => (int) $Page,
	'commentPages' => (int) ceil($NumComments / TORRENT_COMMENTS_PER_PAGE),
	'recordLabel' => $RecordLabel,
	'oclc' => $OCLC
));

?>
