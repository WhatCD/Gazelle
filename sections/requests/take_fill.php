<?
//******************************************************************************//
//--------------- Fill a request -----------------------------------------------//

$RequestID = $_REQUEST['requestid'];
if (!is_number($RequestID)) {
	error(0);
}

authorize();

//VALIDATION
if (!empty($_GET['torrentid']) && is_number($_GET['torrentid'])) {
	$TorrentID = $_GET['torrentid'];
} else {
	if (empty($_POST['link'])) {
		error('You forgot to supply a link to the filling torrent');
	} else {
		$Link = $_POST['link'];
		if (!preg_match('/'.TORRENT_REGEX.'/i', $Link, $Matches)) {
			error('Your link didn\'t seem to be a valid torrent link');
		} else {
			$TorrentID = $Matches[4];
		}
	}
	if (!$TorrentID || !is_number($TorrentID)) {
		error(404);
	}
}

//Torrent exists, check it's applicable
$DB->query("
	SELECT
		t.UserID,
		t.Time,
		tg.ReleaseType,
		t.Encoding,
		t.Format,
		t.Media,
		t.HasLog,
		t.HasCue,
		t.LogScore,
		tg.CategoryID,
		IF(t.Remastered = '1', t.RemasterCatalogueNumber, tg.CatalogueNumber)
	FROM torrents AS t
		LEFT JOIN torrents_group AS tg ON t.GroupID = tg.ID
	WHERE t.ID = $TorrentID
	LIMIT 1");

if (!$DB->has_results()) {
	error(404);
}
list($UploaderID, $UploadTime, $TorrentReleaseType, $Bitrate, $Format, $Media, $HasLog, $HasCue, $LogScore, $TorrentCategoryID, $TorrentCatalogueNumber) = $DB->next_record();

$FillerID = $LoggedUser['ID'];
$FillerUsername = $LoggedUser['Username'];

if (!empty($_POST['user']) && check_perms('site_moderate_requests')) {
	$FillerUsername = $_POST['user'];
	$DB->query("
		SELECT ID
		FROM users_main
		WHERE Username LIKE '".db_string($FillerUsername)."'");
	if (!$DB->has_results()) {
		$Err = 'No such user to fill for!';
	} else {
		list($FillerID) = $DB->next_record();
	}
}

if (time_ago($UploadTime) < 3600 && $UploaderID !== $FillerID && !check_perms('site_moderate_requests')) {
	$Err = 'There is a one hour grace period for new uploads to allow the torrent\'s uploader to fill the request.';
}


$DB->query("
	SELECT
		Title,
		UserID,
		TorrentID,
		CategoryID,
		ReleaseType,
		CatalogueNumber,
		BitrateList,
		FormatList,
		MediaList,
		LogCue
	FROM requests
	WHERE ID = $RequestID");
list($Title, $RequesterID, $OldTorrentID, $RequestCategoryID, $RequestReleaseType, $RequestCatalogueNumber, $BitrateList, $FormatList, $MediaList, $LogCue) = $DB->next_record();


if (!empty($OldTorrentID)) {
	$Err = 'This request has already been filled.';
}
if ($RequestCategoryID !== '0' && $TorrentCategoryID !== $RequestCategoryID) {
	$Err = 'This torrent is of a different category than the request. If the request is actually miscategorized, please contact staff.';
}

$CategoryName = $Categories[$RequestCategoryID - 1];

if ($CategoryName === 'Music') {
	//Commenting out as it's causing some issues with some users being unable to fill, unsure what it is, etc
	/*if ($RequestCatalogueNumber) {
		if ($TorrentCatalogueNumber !== $RequestCatalogueNumber) {
			$Err = "This request requires the catalogue number $RequestCatalogueNumber";
		}
	}*/

	//WEB has no ripping log. Ditto Vinyl - Actually ditto everything but CD
	//$WEBOverride = (strpos($MediaList, 'WEB') !== false && $Media === 'WEB');
	//$VinylOverride = (strpos($MediaList, 'Vinyl') !== false && $Media === 'Vinyl');
	//if ($Format === 'FLAC' && $LogCue && !$WEBOverride && !$VinylOverride) {
	if ($Format === 'FLAC' && $LogCue && $Media === 'CD') {
		if (strpos($LogCue, 'Log') !== false && !$HasLog) {
			$Err = 'This request requires a log.';
		}

		/*
		 * Removed due to rule 2.2.15.6 rendering some requests unfillable
		 */

		//if (strpos($LogCue, 'Cue') !== false && !$HasCue) {
		//	$Err = 'This request requires a cue.';
		//}

		if (strpos($LogCue, '%') !== false) {
			preg_match('/\d+/', $LogCue, $Matches);
			if ((int)$LogScore < (int)$Matches[0]) {
				$Err = 'This torrent\'s log score is too low.';
			}
		}
	}

	if ($BitrateList === 'Other') {
		if ($Bitrate === 'Lossless'
				|| $Bitrate === 'APS (VBR)'
				|| $Bitrate === 'V2 (VBR)'
				|| $Bitrate === 'V1 (VBR)'
				|| $Bitrate === '256'
				|| $Bitrate === 'APX (VBR)'
				|| $Bitrate === 'V0 (VBR)'
				|| $Bitrate === '320'
				|| $Bitrate === '24bit Lossless'
		) {
			$Err = "$Bitrate is not an allowed bitrate for this request.";
		}
	} elseif ($BitrateList && $BitrateList != 'Any' && !Misc::search_joined_string($BitrateList, $Bitrate)) {
		$Err = "$Bitrate is not an allowed bitrate for this request.";
	}
	if ($FormatList && $FormatList != 'Any' && !Misc::search_joined_string($FormatList, $Format)) {
		$Err = "$Format is not an allowed format for this request.";
	}
	if ($MediaList && $MediaList != 'Any' && !Misc::search_joined_string($MediaList, $Media)) {
		$Err = "$Media is not allowed media for this request.";
	}
}

// Fill request
if (!empty($Err)) {
	error($Err);
}

//We're all good! Fill!
$DB->query("
	UPDATE requests
	SET FillerID = $FillerID,
		TorrentID = $TorrentID,
		TimeFilled = '".sqltime()."'
	WHERE ID = $RequestID");

if ($CategoryName === 'Music') {
	$ArtistForm = Requests::get_artists($RequestID);
	$ArtistName = Artists::display_artists($ArtistForm, false, true);
	$FullName = $ArtistName.$Title;
} else {
	$FullName = $Title;
}

$DB->query("
	SELECT UserID
	FROM requests_votes
	WHERE RequestID = $RequestID");
$UserIDs = $DB->to_array();
foreach ($UserIDs as $User) {
	list($VoterID) = $User;
	Misc::send_pm($VoterID, 0, "The request \"$FullName\" has been filled", 'One of your requests&#8202;&mdash;&#8202;[url='.site_url()."requests.php?action=view&amp;id=$RequestID]$FullName".'[/url]&#8202;&mdash;&#8202;has been filled. You can view it here: [url]'.site_url()."torrents.php?torrentid=$TorrentID".'[/url]');
}

$RequestVotes = Requests::get_votes_array($RequestID);
Misc::write_log("Request $RequestID ($FullName) was filled by user $FillerID ($FillerUsername) with the torrent $TorrentID for a ".Format::get_size($RequestVotes['TotalBounty']).' bounty.');

// Give bounty
$DB->query("
	UPDATE users_main
	SET Uploaded = (Uploaded + ".$RequestVotes['TotalBounty'].")
	WHERE ID = $FillerID");



$Cache->delete_value("user_stats_$FillerID");
$Cache->delete_value("request_$RequestID");
if ($GroupID) {
	$Cache->delete_value("requests_group_$GroupID");
}



$DB->query("
	SELECT ArtistID
	FROM requests_artists
	WHERE RequestID = $RequestID");
$ArtistIDs = $DB->to_array();
foreach ($ArtistIDs as $ArtistID) {
	$Cache->delete_value("artists_requests_$ArtistID");
}

Requests::update_sphinx_requests($RequestID);
$SphQL = new SphinxqlQuery();
$SphQL->raw_query("UPDATE requests, requests_delta SET torrentid = $TorrentID, fillerid = $FillerID WHERE id = $RequestID", false);




header("Location: requests.php?action=view&id=$RequestID");
?>
