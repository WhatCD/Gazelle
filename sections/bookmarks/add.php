<?
include(SERVER_ROOT.'/classes/feed.class.php'); // RSS feeds

authorize();

if (!Bookmarks::can_bookmark($_GET['type'])) {
	error(404);
}
$Feed = new FEED;

$Type = $_GET['type'];

list($Table, $Col) = Bookmarks::bookmark_schema($Type);

if (!is_number($_GET['id'])) {
	error(0);
}
$PageID = $_GET['id'];

$DB->query("
	SELECT UserID
	FROM $Table
	WHERE UserID = '$LoggedUser[ID]'
		AND $Col = $PageID");
if (!$DB->has_results()) {
	if ($Type === 'torrent') {
		$DB->query("
			SELECT MAX(Sort)
			FROM `bookmarks_torrents`
			WHERE UserID = $LoggedUser[ID]");
		list($Sort) = $DB->next_record();
		if (!$Sort) {
			$Sort = 0;
		}
		$Sort += 1;
		$DB->query("
			INSERT IGNORE INTO $Table (UserID, $Col, Time, Sort)
			VALUES ('$LoggedUser[ID]', $PageID, '".sqltime()."', $Sort)");
	} else {
		$DB->query("
			INSERT IGNORE INTO $Table (UserID, $Col, Time)
			VALUES ('$LoggedUser[ID]', $PageID, '".sqltime()."')");
	}
	$Cache->delete_value('bookmarks_'.$Type.'_'.$LoggedUser['ID']);
	if ($Type == 'torrent') {
		$Cache->delete_value("bookmarks_group_ids_$UserID");

		$DB->query("
			SELECT Name, Year, WikiBody, TagList
			FROM torrents_group
			WHERE ID = $PageID");
		list($GroupTitle, $Year, $Body, $TagList) = $DB->next_record();
		$TagList = str_replace('_', '.', $TagList);

		$DB->query("
			SELECT ID, Format, Encoding, HasLog, HasCue, LogScore, Media, Scene, FreeTorrent, UserID
			FROM torrents
			WHERE GroupID = $PageID");
		// RSS feed stuff
		while ($Torrent = $DB->next_record()) {
			$Title = $GroupTitle;
			list($TorrentID, $Format, $Bitrate, $HasLog, $HasCue, $LogScore, $Media, $Scene, $Freeleech, $UploaderID) = $Torrent;
			$Title .= " [$Year] - ";
			$Title .= "$Format / $Bitrate";
			if ($HasLog == "'1'") {
				$Title .= ' / Log';
			}
			if ($HasLog) {
				$Title .= " / $LogScore%";
			}
			if ($HasCue == "'1'") {
				$Title .= ' / Cue';
			}
			$Title .= ' / '.trim($Media);
			if ($Scene == '1') {
				$Title .= ' / Scene';
			}
			if ($Freeleech == '1') {
				$Title .= ' / Freeleech!';
			}
			if ($Freeleech == '2') {
				$Title .= ' / Neutral leech!';
			}

			$UploaderInfo = Users::user_info($UploaderID);
			$Item = $Feed->item($Title,
								Text::strip_bbcode($Body),
								'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id='.$TorrentID,
								$UploaderInfo['Username'],
								"torrents.php?id=$PageID",
								trim($TagList));
			$Feed->populate('torrents_bookmarks_t_'.$LoggedUser['torrent_pass'], $Item);
		}
	} elseif ($Type == 'request') {
		$DB->query("
			SELECT UserID
			FROM $Table
			WHERE $Col = '".db_string($PageID)."'");
		if ($DB->record_count() < 100) {
			// Sphinx doesn't like huge MVA updates. Update sphinx_requests_delta
			// and live with the <= 1 minute delay if we have more than 100 bookmarkers
			$Bookmarkers = implode(',', $DB->collect('UserID'));
			$SphQL = new SphinxqlQuery();
			$SphQL->raw_query("UPDATE requests, requests_delta SET bookmarker = ($Bookmarkers) WHERE id = $PageID");
		} else {
			Requests::update_sphinx_requests($PageID);
		}
	}
}
