<?
if (!isset($_GET['torrentid']) || !is_number($_GET['torrentid']) || !check_perms('site_view_torrent_snatchlist')) {
	error(404);
}
$TorrentID = $_GET['torrentid'];

if (!empty($_GET['page']) && is_number($_GET['page'])) {
	$Page = $_GET['page'];
	$Limit = (string)(($Page - 1) * 100) .', 100';
} else {
	$Page = 1;
	$Limit = 100;
}

$DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		UserID,
		Time
	FROM users_downloads
	WHERE TorrentID = '$TorrentID'
	ORDER BY Time DESC
	LIMIT $Limit");
$UserIDs = $DB->collect('UserID');
$Results = $DB->to_array('UserID', MYSQLI_ASSOC);

$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();

if (count($UserIDs) > 0) {
	$UserIDs = implode(',', $UserIDs);
	$DB->query("
		SELECT uid
		FROM xbt_snatched
		WHERE fid = '$TorrentID'
			AND uid IN($UserIDs)");
	$Snatched = $DB->to_array('uid');

	$DB->query("
		SELECT uid
		FROM xbt_files_users
		WHERE fid = '$TorrentID'
			AND Remaining = 0
			AND uid IN($UserIDs)");
	$Seeding = $DB->to_array('uid');
}
?>
<h4 class="tooltip" title="List of users that have clicked the &quot;DL&quot; button">List of Downloaders</h4>
<? if ($NumResults > 100) { ?>
<div class="linkbox"><?=js_pages('show_downloads', $_GET['torrentid'], $NumResults, $Page)?></div>
<? } ?>
<table>
	<tr class="colhead_dark" style="font-weight: bold;">
		<td>User</td>
		<td>Time</td>

		<td>User</td>
		<td>Time</td>
	</tr>
	<tr>
<?
$i = 0;

foreach ($Results as $ID=>$Data) {
	list($SnatcherID, $Timestamp) = array_values($Data);

	$User = Users::format_username($SnatcherID, true, true, true, true);

	if (!array_key_exists($SnatcherID, $Snatched) && $SnatcherID != $UserID) {
		$User = '<span style="font-style: italic;">'.$User.'</span>';
		if (array_key_exists($SnatcherID, $Seeding)) {
			$User = '<strong>'.$User.'</strong>';
		}
	}
	if ($i % 2 == 0 && $i > 0) { ?>
	</tr>
	<tr>
<?
	}
?>
		<td><?=$User?></td>
		<td><?=time_diff($Timestamp)?></td>
<?
	$i++;
}
?>
	</tr>
</table>
<? if ($NumResults > 100) { ?>
<div class="linkbox"><?=js_pages('show_downloads', $_GET['torrentid'], $NumResults, $Page)?></div>
<? } ?>
