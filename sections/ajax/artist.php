<?
authorize(true);

//For sorting tags
function compare($X, $Y){
	return($Y['count'] - $X['count']);
}

include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
include(SERVER_ROOT.'/sections/requests/functions.php');

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

// Similar artist map
include(SERVER_ROOT.'/classes/class_artist.php');
include(SERVER_ROOT.'/classes/class_artists_similar.php');

$ArtistID = $_GET['id'];
if(!is_number($ArtistID)) {
	print json_encode(array('status' => 'failure'));
}

if (empty($ArtistID)) {
	if (!empty($_GET['artistname'])) {
		$Name = db_string(trim($_GET['artistname']));
		$DB->query("SELECT ArtistID FROM artists_alias WHERE Name LIKE '$Name'");
		if (!(list($ArtistID) = $DB->next_record(MYSQLI_NUM, false))) {
		//if (list($ID) = $DB->next_record(MYSQLI_NUM, false)) {
			print json_encode(array('status' => 'failure'));
			die();
		}
		// If we get here, we got the ID!
	}
}

if(!empty($_GET['revisionid'])) { // if they're viewing an old revision
	$RevisionID=$_GET['revisionid'];
	if(!is_number($RevisionID)){ error(0); }
	$Data = $Cache->get_value("artist_$ArtistID"."_revision_$RevisionID");
} else { // viewing the live version
	$Data = $Cache->get_value('artist_'.$ArtistID);
	$RevisionID = false;
}
if($Data) {
	$Data = unserialize($Data);
	list($K, list($Name, $Image, $Body, $NumSimilar, $SimilarArray, $TorrentList, $Importances)) = each($Data);

} else {
	if ($RevisionID) {
		$sql = "SELECT
			a.Name,
			wiki.Image,
			wiki.body,
			a.VanityHouse
			FROM wiki_artists AS wiki 
			LEFT JOIN artists_group AS a ON wiki.RevisionID=a.RevisionID
			WHERE wiki.RevisionID='$RevisionID' ";
	} else {
		$sql = "SELECT
			a.Name,
			wiki.Image,
			wiki.body,
			a.VanityHouse
			FROM artists_group AS a
			LEFT JOIN wiki_artists AS wiki ON wiki.RevisionID=a.RevisionID
			WHERE a.ArtistID='$ArtistID' ";
	}
	$sql .= " GROUP BY a.ArtistID";
	$DB->query($sql);

	if($DB->record_count()==0) {
		print json_encode(array('status' => 'failure'));
	}

	list($Name, $Image, $Body, $VanityHouseArtist) = $DB->next_record(MYSQLI_NUM, array(0));
}

ob_start();

// Requests
$Requests = $Cache->get_value('artists_requests_'.$ArtistID);
if(!is_array($Requests)) {
	$DB->query("SELECT
			r.ID,
			r.CategoryID,
			r.Title,
			r.Year,
			r.TimeAdded,
			COUNT(rv.UserID) AS Votes,
			SUM(rv.Bounty) AS Bounty
		FROM requests AS r
			LEFT JOIN requests_votes AS rv ON rv.RequestID=r.ID
			LEFT JOIN requests_artists AS ra ON r.ID=ra.RequestID 
		WHERE ra.ArtistID = ".$ArtistID."
			AND r.TorrentID = 0
		GROUP BY r.ID
		ORDER BY Votes DESC");

	if($DB->record_count() > 0) {
		$Requests = $DB->to_array();
	} else {
		$Requests = array();
	}
	$Cache->cache_value('artists_requests_'.$ArtistID, $Requests);
}
$NumRequests = count($Requests);

$LastReleaseType = 0;
if(empty($Importances) || empty($TorrentList)) {
	$DB->query("SELECT 
			DISTINCT ta.GroupID, ta.Importance, tg.VanityHouse
			FROM torrents_artists AS ta
			JOIN torrents_group AS tg ON tg.ID=ta.GroupID
			WHERE ta.ArtistID='$ArtistID'
			ORDER BY ta.Importance, tg.ReleaseType ASC, tg.Year DESC, tg.Name DESC");

	$GroupIDs = $DB->collect('GroupID');
	$Importances = $DB->to_array('GroupID', MYSQLI_BOTH, false);
	if(count($GroupIDs)>0) {
		$TorrentList = get_groups($GroupIDs, true,true);
		$TorrentList = $TorrentList['matches'];
	} else {
		$TorrentList = array();
	}
}
$NumGroups = count($TorrentList);

//Get list of used release types
$UsedReleases = array();
foreach($TorrentList as $GroupID=>$Group) {
	if($Importances[$GroupID]['Importance'] == '2') {
		$TorrentList[$GroupID]['ReleaseType'] = 1024;
		$GuestAlbums = true;
	}
	if($Importances[$GroupID]['Importance'] == '3') {
		$TorrentList[$GroupID]['ReleaseType'] = 1023;
		$RemixerAlbums = true;
	}
	if(!in_array($TorrentList[$GroupID]['ReleaseType'], $UsedReleases)) {
		$UsedReleases[] = $TorrentList[$GroupID]['ReleaseType'];
	}
}

if(!empty($GuestAlbums)) {
	$ReleaseTypes[1024] = "Guest Appearance";
}
if(!empty($RemixerAlbums)) {
	$ReleaseTypes[1023] = "Remixed By";
}


reset($TorrentList);

$JsonTorrents = array();
foreach ($TorrentList as $GroupID=>$Group) {
	list($GroupID, $GroupName, $GroupYear, $GroupRecordLabel, $GroupCatalogueNumber, $TagList, $ReleaseType, $GroupVanityHouse, $Torrents, $Artists) = array_values($Group);
	$GroupVanityHouse = $Importances[$GroupID]['VanityHouse'];

	$TagList = explode(' ',str_replace('_','.',$TagList));

	// $Tags array is for the sidebar on the right
	foreach($TagList as $Tag) {
		if(!isset($Tags[$Tag])) {
			$Tags[$Tag] = array('name'=>$Tag, 'count'=>1);
		} else {
			$Tags[$Tag]['count']++;
		}
	}
	
	

	$DisplayName ='<a href="torrents.php?id='.$GroupID.'" title="View Torrent">'.$GroupName.'</a>';
	if(check_perms('users_mod')) {
		$DisplayName .= ' [<a href="torrents.php?action=fix_group&amp;groupid='.$GroupID.'&amp;artistid='.$ArtistID.'&amp;auth='.$LoggedUser['AuthKey'].'">Fix</a>]';
	}

	if (($ReleaseType == 1023) || ($ReleaseType == 1024)) {
		$DisplayName = display_artists(array(1 => $Artists), true, true).$DisplayName;
	}

	if($GroupYear>0) { $DisplayName = $GroupYear. ' - '.$DisplayName; }

	if($GroupVanityHouse) { $DisplayName .= ' [<abbr title="This is a vanity house release">VH</abbr>]'; }

?>
			<tr class="releases_<?=$ReleaseType?> group discog<?=$HideDiscog?>">
				<td class="center">
					<div title="View" id="showimg_<?=$GroupID?>" class="<?=($ShowGroups ? 'hide' : 'show')?>_torrents">
						<a href="#" class="show_torrents_link" onclick="toggle_group(<?=$GroupID?>, this, event)" title="Collapse this group"></a>
					</div>
				</td>
				<td colspan="5">
					<strong><?=$DisplayName?></strong>
					<?=$TorrentTags?>
				</td>
			</tr>
<?
	$LastRemasterYear = '-';
	$LastRemasterTitle = '';
	$LastRemasterRecordLabel = '';
	$LastRemasterCatalogueNumber = '';
	$LastMedia = '';

	$EditionID = 0;
	unset($FirstUnknown);

	$InnerTorrents = array();
	foreach ($Torrents as $TorrentID => $Torrent) {
		$NumTorrents++;

		if ($Torrent['Remastered'] && !$Torrent['RemasterYear']) {
			$FirstUnknown = !isset($FirstUnknown);
		}

		$Torrent['Seeders'] = (int)$Torrent['Seeders'];
		$Torrent['Leechers'] = (int)$Torrent['Leechers'];
		$Torrent['Snatched'] = (int)$Torrent['Snatched'];

		$NumSeeders+=$Torrent['Seeders'];
		$NumLeechers+=$Torrent['Leechers'];
		$NumSnatches+=$Torrent['Snatched'];
	}
	$JsonTorrents[] = array(
		'groupId' => $GroupID,
		'groupName' => $GroupName,
		'groupYear' => $GroupYear,
		'groupRecordLabel' => $GroupRecordLabel,
		'groupCatalogueNumber' => $GroupCatalogueNumber,
		'tags' => $TagList,
		'releaseType' => $ReleaseType,
		'groupVanityHouse' => $GroupVanityHouse,
		'hasBookmarked' => $hasBookmarked = has_bookmarked('torrent', $GroupID),
		'torrent' => array_values($Torrents)
	);
}

$TorrentDisplayList = ob_get_clean();

$JsonSimilar = array();
if(empty($SimilarArray)) {
	$DB->query("
		SELECT
		s2.ArtistID,
		a.Name,
		ass.Score,
		ass.SimilarID
		FROM artists_similar AS s1
		JOIN artists_similar AS s2 ON s1.SimilarID=s2.SimilarID AND s1.ArtistID!=s2.ArtistID
		JOIN artists_similar_scores AS ass ON ass.SimilarID=s1.SimilarID
		JOIN artists_group AS a ON a.ArtistID=s2.ArtistID
		WHERE s1.ArtistID='$ArtistID'
		ORDER BY ass.Score DESC
		LIMIT 30
	");
	$SimilarArray = $DB->to_array();
	foreach ($SimilarArray as $Similar) {
		$JsonSimilar[] = array(
			'artistId' => $Similar['ArtistID'],
			'name' => $Similar['Name'],
			'score' => $Similar['Score'],
			'similarId' => $Similar['SimilarID']
		);
	}
	$NumSimilar = count($SimilarArray);
}

$JsonRequests = array();
foreach ($Requests as $Request) {
	list($RequestID, $CategoryID, $Title, $Year, $TimeAdded, $Votes, $Bounty) = $Request;
	$JsonRequests[] = array(
		'requestId' => $RequestID,
		'categoryId' => $CategoryID,
		'title' => $Title,
		'year' => $Year,
		'timeAdded' => $TimeAdded,
		'votes' => $Votes,
		'bounty' => $Bounty
	);
}

//notifications disabled by default
$notificationsEnabled = False;
if (check_perms('site_torrents_notify')) {
        if (($Notify = $Cache->get_value('notify_artists_'.$LoggedUser['ID'])) === false) {
                $DB->query("SELECT ID, Artists FROM users_notify_filters WHERE UserID='$LoggedUser[ID]' AND Label='Artist notifications' LIMIT 1");
                $Notify = $DB->next_record(MYSQLI_ASSOC, false);
                $Cache->cache_value('notify_artists_'.$LoggedUser['ID'], $Notify, 0);
        }
        if (stripos($Notify['Artists'], '|'.$Name.'|') === false) {
		$notificationsEnabled = False;
        } else {
		$notificationsEnabled = True;
        }
}


print
	json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'id' => $ArtistID,
				'name' => $Name,
				'notificationsEnabled' => $notificationsEnabled,
				'hasBookmarked' => has_bookmarked('artist', $ArtistID),
				'image' => $Image,
				'body' => $Text->full_format($Body),
				'vanityHouse' => $VanityHouseArtist,
				'tags' => array_values($Tags),
				'similarArtists' => $JsonSimilar,
				'statistics' => array(
					'numGroups' => $NumGroups,
					'numTorrents' => $NumTorrents,
					'numSeeders' => $NumSeeders,
					'numLeechers' => $NumLeechers,
					'numSnatches' => $NumSnatches
					),
				'torrentgroup' => $JsonTorrents,
				'requests' => $JsonRequests
			)
		)
	)
?>
