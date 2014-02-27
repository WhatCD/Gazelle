<?
/*
 * This page handles the backend from when a user submits a report.
 * It checks for (in order):
 * 1. The usual POST injections, then checks that things.
 * 2. Things that are required by the report type are filled
 * 	('1' in the report_fields array).
 * 3. Things that are filled are filled with correct things.
 * 4. That the torrent you're reporting still exists.
 *
 * Then it just inserts the report to the DB and increments the counter.
 */

authorize();

if (!is_number($_POST['torrentid'])) {
	error(404);
} else {
	$TorrentID = $_POST['torrentid'];
}

if (!is_number($_POST['categoryid'])) {
	error(404);
} else {
	$CategoryID = $_POST['categoryid'];
}

if (!isset($_POST['type'])) {
	error(404);
} elseif (array_key_exists($_POST['type'], $Types[$CategoryID])) {
	$Type = $_POST['type'];
	$ReportType = $Types[$CategoryID][$Type];
} elseif (array_key_exists($_POST['type'], $Types['master'])) {
	$Type = $_POST['type'];
	$ReportType = $Types['master'][$Type];
} else {
	//There was a type but it wasn't an option!
	error(403);
}


foreach ($ReportType['report_fields'] as $Field => $Value) {
	if ($Value == '1') {
		if (empty($_POST[$Field])) {
			$Err = "You are missing a required field ($Field) for a ".$ReportType['title'].' report.';
		}
	}
}

if (!empty($_POST['sitelink'])) {
	if (preg_match_all('/'.TORRENT_REGEX.'/i', $_POST['sitelink'], $Matches)) {
		$ExtraIDs = implode(' ', $Matches[4]);
		if (in_array($TorrentID, $Matches[4])) {
			$Err = "The extra permalinks you gave included the link to the torrent you're reporting!";
		}
	} else {
		$Err = 'The permalink was incorrect. It should look like '.site_url().'torrents.php?torrentid=12345';
	}
} else {
	$ExtraIDs = '';
}

if (!empty($_POST['link'])) {
	//resource_type://domain:port/filepathname?query_string#anchor
	//					http://		www			.foo.com								/bar
	if (preg_match_all('/'.URL_REGEX.'/is', $_POST['link'], $Matches)) {
		$Links = implode(' ', $Matches[0]);
	} else {
		$Err = "The extra links you provided weren't links...";
	}
} else {
	$Links = '';
}

if (!empty($_POST['image'])) {
	if (preg_match("/^(".IMAGE_REGEX.")( ".IMAGE_REGEX.")*$/is", trim($_POST['image']), $Matches)) {
		$Images = $Matches[0];
	} else {
		$Err = "The extra image links you provided weren't links to images...";
	}
} else {
	$Images = '';
}

if (!empty($_POST['track'])) {
	if (preg_match('/([0-9]+( [0-9]+)*)|All/is', $_POST['track'], $Matches)) {
		$Tracks = $Matches[0];
	} else {
		$Err = 'Tracks should be given in a space-separated list of numbers with no other characters.';
	}
} else {
	$Tracks = '';
}

if (!empty($_POST['extra'])) {
	$Extra = db_string($_POST['extra']);
} else {
	$Err = 'As useful as blank reports are, could you be a tiny bit more helpful? (Leave a comment)';
}

$DB->query("
	SELECT GroupID
	FROM torrents
	WHERE ID = $TorrentID");
if (!$DB->has_results()) {
	$Err = "A torrent with that ID doesn't exist!";
}
list($GroupID) = $DB->next_record();

if (!empty($Err)) {
	error($Err);
	include(SERVER_ROOT.'/sections/reportsv2/report.php');
	die();
}

$DB->query("
	SELECT ID
	FROM reportsv2
	WHERE TorrentID = $TorrentID
		AND ReporterID = ".db_string($LoggedUser['ID'])."
		AND ReportedTime > '".time_minus(3)."'");
if ($DB->has_results()) {
	header("Location: torrents.php?torrentid=$TorrentID");
	die();
}

$DB->query("
	INSERT INTO reportsv2
		(ReporterID, TorrentID, Type, UserComment, Status, ReportedTime, Track, Image, ExtraID, Link)
	VALUES
		(".db_string($LoggedUser['ID']).", $TorrentID, '".db_string($Type)."', '$Extra', 'New', '".sqltime()."', '".db_string($Tracks)."', '".db_string($Images)."', '".db_string($ExtraIDs)."', '".db_string($Links)."')");

$ReportID = $DB->inserted_id();


$Cache->delete_value("reports_torrent_$TorrentID");

$Cache->increment('num_torrent_reportsv2');
header("Location: torrents.php?torrentid=$TorrentID");
?>
