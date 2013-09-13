<?
$GroupID = $_GET['groupid'];
if (!is_number($GroupID)) {
	error(404);
}

View::show_header("History for Group $GroupID");

$Groups = Torrents::get_groups(array($GroupID), true, true, false);
if (!empty($Groups[$GroupID])) {
	$Group = $Groups[$GroupID];
	$Title = Artists::display_artists($Group['ExtendedArtists']).'<a href="torrents.php?id='.$GroupID.'">'.$Group['Name'].'</a>';
} else {
	$Title = "Group $GroupID";
}
?>

<div class="thin">
	<div class="header">
		<h2>History for <?=$Title?></h2>
	</div>
	<table>
		<tr class="colhead">
			<td>Date</td>
			<td>Torrent</td>
			<td>User</td>
			<td>Info</td>
		</tr>
<?
	$Log = $DB->query("
			SELECT TorrentID, UserID, Info, Time
			FROM group_log
			WHERE GroupID = $GroupID
			ORDER BY Time DESC");
	$LogEntries = $DB->to_array(false, MYSQL_NUM);
	foreach ($LogEntries AS $LogEntry) {
		list($TorrentID, $UserID, $Info, $Time) = $LogEntry;
?>
		<tr class="rowa">
			<td><?=$Time?></td>
<?
			if ($TorrentID != 0) {
				$DB->query("
					SELECT Media, Format, Encoding
					FROM torrents
					WHERE ID = $TorrentID");
				list($Media, $Format, $Encoding) = $DB->next_record();
				if (!$DB->has_results()) { ?>
					<td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> (Deleted)</td><?
				} elseif ($Media == '') { ?>
					<td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a></td><?
				} else { ?>
					<td><a href="torrents.php?torrentid=<?=$TorrentID?>"><?=$TorrentID?></a> (<?=$Format?>/<?=$Encoding?>/<?=$Media?>)</td>
<?				}
			} else { ?>
				<td></td>
<?			}	?>
			<td><?=Users::format_username($UserID, false, false, false)?></td>
			<td><?=$Info?></td>
		</tr>
<?
	}
?>
	</table>
</div>
<?
View::show_footer();
?>
