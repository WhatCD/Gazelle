<?
$RequestTax = 0.1;

// Minimum and default amount of upload to remove from the user when they vote.
// Also change in static/functions/requests.js
$MinimumVote = 20 * 1024 * 1024;

/*
 * This is the page that displays the request to the end user after being created.
 */

if (empty($_GET['id']) || !is_number($_GET['id'])) {
	json_die("failure");
}

$RequestID = (int)$_GET['id'];

//First things first, lets get the data for the request.

$Request = Requests::get_request($RequestID);
if ($Request === false) {
	json_die("failure");
}

$CategoryID = $Request['CategoryID'];
$Requestor = Users::user_info($Request['UserID']);
$Filler = $Request['FillerID'] ? Users::user_info($Request['FillerID']) : null;
//Convenience variables
$IsFilled = !empty($Request['TorrentID']);
$CanVote = !$IsFilled && check_perms('site_vote');

if ($CategoryID == 0) {
	$CategoryName = 'Unknown';
} else {
	$CategoryName = $Categories[$CategoryID - 1];
}

//Do we need to get artists?
if ($CategoryName == 'Music') {
	$ArtistForm = Requests::get_artists($RequestID);

	if (empty($Request['ReleaseType'])) {
		$ReleaseName = 'Unknown';
	} else {
		$ReleaseName = $ReleaseTypes[$Request['ReleaseType']];
	}
}

//Votes time
$RequestVotes = Requests::get_votes_array($RequestID);
$VoteCount = count($RequestVotes['Voters']);
$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0) || ($CategoryName == 'Music' && $Request['Year'] == 0)));
$UserCanEdit = (!$IsFilled && $LoggedUser['ID'] == $Request['UserID'] && $VoteCount < 2);
$CanEdit = ($UserCanEdit || $ProjectCanEdit || check_perms('site_moderate_requests'));

if ($CategoryName == "Music") {
	$JsonMusicInfo = array(
		/*'composers' => $ArtistForm[4] != null ? $ArtistForm[4] : array(),
		'dj'        => $ArtistForm[6] != null ? $ArtistForm[6] : array(),
		'artists'   => $ArtistForm[1] != null ? $ArtistForm[1] : array(),
		'with'      => $ArtistForm[2] != null ? $ArtistForm[2] : array(),
		'conductor' => $ArtistForm[5] != null ? $ArtistForm[5] : array(),
		'remixedBy' => $ArtistForm[3] != null ? $ArtistForm[3] : array()*/
		'composers' => isset($ArtistForm[4]) ? pullmediainfo($ArtistForm[4]) : array(),
		'dj'        => isset($ArtistForm[6]) ? pullmediainfo($ArtistForm[6]) : array(),
		'artists'   => isset($ArtistForm[1]) ? pullmediainfo($ArtistForm[1]) : array(),
		'with'      => isset($ArtistForm[2]) ? pullmediainfo($ArtistForm[2]) : array(),
		'conductor' => isset($ArtistForm[5]) ? pullmediainfo($ArtistForm[5]) : array(),
		'remixedBy' => isset($ArtistForm[3]) ? pullmediainfo($ArtistForm[3]) : array(),
		'producer'  => isset($ArtistForm[7]) ? pullmediainfo($ArtistForm[7]) : array()
	);
} else {
	$JsonMusicInfo = new stdClass; //json_encodes into an empty object: {}
}

$JsonTopContributors = array();
$VoteMax = ($VoteCount < 5 ? $VoteCount : 5);
for ($i = 0; $i < $VoteMax; $i++) {
	$User = array_shift($RequestVotes['Voters']);
	$JsonTopContributors[] = array(
		'userId' => (int)$User['UserID'],
		'userName' => $User['Username'],
		'bounty' => (int)$User['Bounty']
	);
}
reset($RequestVotes['Voters']);

list($NumComments, $Page, $Thread) = Comments::load('requests', $RequestID, false);

$JsonRequestComments = array();
foreach ($Thread as $Key => $Post) {
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(Users::user_info($AuthorID));
	$JsonRequestComments[] = array(
		'postId' => (int)$PostID,
		'authorId' => (int)$AuthorID,
		'name' => $Username,
		'donor' => $Donor == 1,
		'warned' => ($Warned != '0000-00-00 00:00:00'),
		'enabled' => ($Enabled == 2 ? false : true),
		'class' => Users::make_class_string($PermissionID),
		'addedTime' => $AddedTime,
		'avatar' => $Avatar,
		'comment' => Text::full_format($Body),
		'editedUserId' => (int)$EditedUserID,
		'editedUsername' => $EditedUsername,
		'editedTime' => $EditedTime
	);
}

$JsonTags = array();
foreach ($Request['Tags'] as $Tag) {
	$JsonTags[] = $Tag;
}
json_print('success', array(
	'requestId' => (int)$RequestID,
	'requestorId' => (int)$Request['UserID'],
	'requestorName' => $Requestor['Username'],
	'isBookmarked' => Bookmarks::has_bookmarked('request', $RequestID),
	'requestTax' => $RequestTax,
	'timeAdded' => $Request['TimeAdded'],
	'canEdit' => $CanEdit,
	'canVote' => $CanVote,
	'minimumVote' => $MinimumVote,
	'voteCount' => $VoteCount,
	'lastVote' => $Request['LastVote'],
	'topContributors' => $JsonTopContributors,
	'totalBounty' => (int)$RequestVotes['TotalBounty'],
	'categoryId' => (int)$CategoryID,
	'categoryName' => $CategoryName,
	'title' => $Request['Title'],
	'year' => (int)$Request['Year'],
	'image' => $Request['Image'],
	'bbDescription' => $Request['Description'],
	'description' => Text::full_format($Request['Description']),
	'musicInfo' => $JsonMusicInfo,
	'catalogueNumber' => $Request['CatalogueNumber'],
	'releaseType' => (int)$Request['ReleaseType'],
	'releaseName' => $ReleaseName,
	'bitrateList' => preg_split('/\|/', $Request['BitrateList'], null, PREG_SPLIT_NO_EMPTY),
	'formatList' => preg_split('/\|/', $Request['FormatList'], null, PREG_SPLIT_NO_EMPTY),
	'mediaList' => preg_split('/\|/', $Request['MediaList'], null, PREG_SPLIT_NO_EMPTY),
	'logCue' => html_entity_decode($Request['LogCue']),
	'isFilled' => $IsFilled,
	'fillerId' => (int)$Request['FillerID'],
	'fillerName' => ($Filler ? $Filler['Username'] : ''),
	'torrentId' => (int)$Request['TorrentID'],
	'timeFilled' => $Request['TimeFilled'],
	'tags' => $JsonTags,
	'comments' => $JsonRequestComments,
	'commentPage' => (int)$Page,
	'commentPages' => (int)ceil($NumComments / TORRENT_COMMENTS_PER_PAGE),
	'recordLabel' => $Request['RecordLabel'],
	'oclc' => $Request['OCLC']
));
?>
