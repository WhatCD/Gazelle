<?
/*
 * This is the AJAX backend for the SendNow() function.
 */

authorize();

if (!check_perms('admin_reports')) {
	die();
}

$Recipient = $_POST['pm_type'];
$TorrentID = $_POST['torrentid'];

if (isset($_POST['uploader_pm']) && $_POST['uploader_pm'] != '') {
	$Message = $_POST['uploader_pm'];
} else {
	//No message given
	die();
}

if (!is_number($_POST['categoryid']) || !is_number($TorrentID)) {
	echo 'Hax on category ID!';
	die();
} else {
	$CategoryID = $_POST['categoryid'];
}

if (array_key_exists($_POST['type'], $Types[$CategoryID])) {
	$ReportType = $Types[$CategoryID][$_POST['type']];
} elseif (array_key_exists($_POST['type'], $Types['master'])) {
	$ReportType = $Types['master'][$_POST['type']];
} else {
	//There was a type but it wasn't an option!
	echo 'Hax on section type';
	die();
}

if (!isset($_POST['from_delete'])) {
	$Report = true;
} elseif (!is_number($_POST['from_delete'])) {
	echo 'Hax occurred in from_delete';
}

if ($Recipient == 'Uploader') {
	$ToID = $_POST['uploaderid'];
	if ($Report) {
		$Message = "You uploaded [url=".site_url()."torrents.php?torrentid=$TorrentID]the above torrent[/url]. It has been reported for the reason: ".$ReportType['title']."\n\n$Message";
	} else {
		$Message = "I am PMing you as you are the uploader of [url=".site_url()."torrents.php?torrentid=$TorrentID]the above torrent[/url].\n\n$Message";
	}
} elseif ($Recipient == 'Reporter') {
	$ToID = $_POST['reporterid'];
	$Message = "You reported [url=".site_url()."torrents.php?torrentid=$TorrentID]the above torrent[/url] for the reason ".$ReportType['title'].":\n[quote]".$_POST['report_reason']."[/quote]\n$Message";
} else {
	$Err = "Something went horribly wrong";
}

$Subject = $_POST['raw_name'];

if (!is_number($ToID)) {
	$Err = "Haxx occurring, non-number present";
}

if ($ToID == $LoggedUser['ID']) {
	$Err = "That's you!";
}

if (isset($Err)) {
	echo $Err;
} else {
	Misc::send_pm($ToID, $LoggedUser['ID'], $Subject, $Message);
}
