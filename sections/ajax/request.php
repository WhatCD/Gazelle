<?

authorize(true);

$RequestTax = 0.1;

// Minimum and default amount of upload to remove from the user when they vote.
// Also change in static/functions/requests.js
$MinimumVote = 20*1024*1024;

/*
 * This is the page that displays the request to the end user after being created.
 */

include(SERVER_ROOT.'/sections/requests/functions.php');

include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
include(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

if(empty($_GET['id']) || !is_number($_GET['id'])) { 
	print
		json_encode(
			array(
				'status' => 'failure'
			)
		);
	die();
}

$RequestID = $_GET['id'];

//First things first, lets get the data for the request.

$Request = get_requests(array($RequestID));	
$Request = $Request['matches'][$RequestID];
if(empty($Request)) {
	print
		json_encode(
			array(
				'status' => 'failure'
			)
		);
	die();
}

list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Year, $Image, $Description, $CatalogueNumber, $ReleaseType,
	$BitrateList, $FormatList, $MediaList, $LogCue, $FillerID, $FillerName, $TorrentID, $TimeFilled) = $Request;

//Convenience variables
$IsFilled = !empty($TorrentID);
$CanVote = (empty($TorrentID) && check_perms('site_vote'));

if($CategoryID == 0) {
	$CategoryName = "Unknown";
} else {
	$CategoryName = $Categories[$CategoryID - 1];
}

//Do we need to get artists?
if($CategoryName == "Music") {
	$ArtistForm = get_request_artists($RequestID);
	$ArtistName = display_artists($ArtistForm, false, true);
	$ArtistLink = display_artists($ArtistForm, true, true);
	
	if($IsFilled) {
		$DisplayLink = $ArtistLink."<a href='torrents.php?torrentid=".$TorrentID."'>".$Title."</a> [".$Year."]";
	} else {
		$DisplayLink = $ArtistLink.$Title." [".$Year."]";
	}
	$FullName = $ArtistName.$Title." [".$Year."]";
	
	if($BitrateList != "") {
		$BitrateString = implode(", ", explode("|", $BitrateList));
		$FormatString = implode(", ", explode("|", $FormatList));
		$MediaString = implode(", ", explode("|", $MediaList));
	} else {
		$BitrateString = "Unknown, please read the description.";
		$FormatString = "Unknown, please read the description.";
		$MediaString = "Unknown, please read the description.";
	}
	
	if(empty($ReleaseType)) {
		$ReleaseName = "Unknown";
	} else {
		$ReleaseName = $ReleaseTypes[$ReleaseType];
	}
	
} else if($CategoryName == "Audiobooks" || $CategoryName == "Comedy") {
	$FullName = $Title." [".$Year."]";
	$DisplayLink = $Title." [".$Year."]";
} else {
	$FullName = $Title;
	$DisplayLink = $Title;
}

//Votes time
$RequestVotes = get_votes_array($RequestID);
$VoteCount = count($RequestVotes['Voters']);
$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0) || ($CategoryName == "Music" && $Year == 0)));
$UserCanEdit = (!$IsFilled && $LoggedUser['ID'] == $RequestorID && $VoteCount < 2);
$CanEdit = ($UserCanEdit || $ProjectCanEdit || check_perms('site_moderate_requests'));

$JsonMusicInfo = array();
if ($CategoryName == "Music") {
	$JsonMusicInfo = array(
		/*'composers' => $ArtistForm[4] != null ? $ArtistForm[4] : array(),
		'dj' => $ArtistForm[6] != null ? $ArtistForm[6] : array(),
		'artists' => $ArtistForm[1] != null ? $ArtistForm[1] : array(),
		'with' => $ArtistForm[2] != null ? $ArtistForm[2] : array(),
		'conductor' => $ArtistForm[5] != null ? $ArtistForm[5] : array(),
		'remixedBy' => $ArtistForm[3] != null ? $ArtistForm[3] : array()*/
		'composers' => $ArtistForm[4] == null ? array() : pullmediainfo($ArtistForm[4]),
		'dj' => $ArtistForm[6] == null ? array() : pullmediainfo($ArtistForm[6]),
		'artists' => $ArtistForm[1] == null ? array() : pullmediainfo($ArtistForm[1]),
		'with' => $ArtistForm[2] == null ? array() : pullmediainfo($ArtistForm[2]),
		'conductor' => $ArtistForm[5] == null ? array() : pullmediainfo($ArtistForm[5]),
		'remixedBy' => $ArtistForm[3] == null ? array() : pullmediainfo($ArtistForm[3]),
		'producer' => $ArtistForm[7] == null ? array() : pullmediainfo($ArtistForm[7])
	);
}

$JsonTopContributors = array();
$VoteMax = ($VoteCount < 5 ? $VoteCount : 5);
for($i = 0; $i < $VoteMax; $i++) { 
	$User = array_shift($RequestVotes['Voters']);
	$JsonTopContributors[] = array(
		'userId' => (int) $User['UserID'],
		'userName' => $User['Username'],
		'bounty' => (int) $User['Bounty']
	);
}
reset($RequestVotes['Voters']);

$Results = $Cache->get_value('request_comments_'.$RequestID);
if($Results === false) {
	$DB->query("SELECT
			COUNT(c.ID)
			FROM requests_comments as c
			WHERE c.RequestID = '$RequestID'");
	list($Results) = $DB->next_record();
	$Cache->cache_value('request_comments_'.$RequestID, $Results, 0);
}

list($Page,$Limit) = page_limit(TORRENT_COMMENTS_PER_PAGE,$Results);

//Get the cache catalogue
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
$CatalogueLimit=$CatalogueID*THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
$Catalogue = $Cache->get_value('request_comments_'.$RequestID.'_catalogue_'.$CatalogueID);
if($Catalogue === false) {
	$DB->query("SELECT
			c.ID,
			c.AuthorID,
			c.AddedTime,
			c.Body,
			c.EditedUserID,
			c.EditedTime,
			u.Username
			FROM requests_comments as c
			LEFT JOIN users_main AS u ON u.ID=c.EditedUserID
			WHERE c.RequestID = '$RequestID'
			ORDER BY c.ID
			LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array(false,MYSQLI_ASSOC);
	$Cache->cache_value('request_comments_'.$RequestID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
}

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue,((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)%THREAD_CATALOGUE),TORRENT_COMMENTS_PER_PAGE,true);

$JsonRequestComments = array();
foreach($Thread as $Key => $Post){
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(user_info($AuthorID));
	$JsonRequestComments[] = array(
		'postId' => (int) $PostID,
		'authorId' => (int) $AuthorID,
		'name' => $Username,
		'donor' => $Donor == 1,
		'warned' => ($Warned!='0000-00-00 00:00:00'),
		'enabled' => ($Enabled == 2 ? false : true),
		'class' => make_class_string($PermissionID),
		'addedTime' => $AddedTime,
		'avatar' => $Avatar,
		'comment' => $Text->full_format($Body),
		'editedUserId' => (int) $EditedUserID,
		'editedUsername' => $EditedUsername,
		'editedTime' => $EditedTime
	);
}

$JsonTags = array();
foreach($Request['Tags'] as $Tag) {
	$JsonTags[] = $Tag;
}

print
	json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'requestId' => (int) $RequestID,
				'requestorId' => (int) $RequestorID,
				'requestorName' => $RequestorName,
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
				'description' => $Text->full_format($Description),
				'musicInfo' => $JsonMusicInfo,
				'catalogueNumber' => $CatalogueNumber,
				'releaseType' => (int) $ReleaseType,
				'releaseName' => $ReleaseName,
				'bitrateList' => $BitrateList,
				'formatList' => $FormatList,
				'mediaList' => $MediaList,
				'logCue' => $LogCue,
				'isFilled' => $IsFilled,
				'fillerId' => (int) $FillerID,
				'fillerName' => $FillerName,
				'torrentId' => (int) $TorrentID,
				'timeFilled' => $TimeFilled,
				'tags' => $JsonTags,
				'comments' => $JsonRequestComments,
				'commentPage' => (int) $Page,
				'commentPages' => (int) ceil($Results / TORRENT_COMMENTS_PER_PAGE)
			)
		)
	);

function pullmediainfo($Array) {
	$NewArray = array();
	foreach ($Array as $Item) {
		$NewArray[] = array(
			'id' => (int) $Item['id'],
			'name' => $Item['name']
		);
	}
	return $NewArray;
}
?>
