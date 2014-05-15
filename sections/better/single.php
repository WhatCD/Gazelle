<?
if (($Results = $Cache->get_value('better_single_groupids')) === false) {
	$DB->query("
		SELECT
			t.ID AS TorrentID,
			t.GroupID AS GroupID
		FROM xbt_files_users AS x
			JOIN torrents AS t ON t.ID=x.fid
		WHERE t.Format='FLAC'
		GROUP BY x.fid
		HAVING COUNT(x.uid) = 1
		ORDER BY t.LogScore DESC, t.Time ASC
		LIMIT 30");

	$Results = $DB->to_pair('GroupID', 'TorrentID', false);
	$Cache->cache_value('better_single_groupids', $Results, 30 * 60);
}

$Groups = Torrents::get_groups(array_keys($Results));

View::show_header('Single seeder FLACs');
?>
<div class="linkbox">
	<a href="better.php" class="brackets">Back to better.php list</a>
</div>
<div class="thin">
	<table width="100%" class="torrent_table">
		<tr class="colhead">
			<td>Torrent</td>
		</tr>
<?
foreach ($Results as $GroupID => $FlacID) {
	if (!isset($Groups[$GroupID])) {
		continue;
	}
	$Group = $Groups[$GroupID];
	extract(Torrents::array_group($Group));
	$TorrentTags = new Tags($TagList);

	if (!empty($ExtendedArtists[1]) || !empty($ExtendedArtists[4]) || !empty($ExtendedArtists[5]) || !empty($ExtendedArtists[6])) {
		unset($ExtendedArtists[2]);
		unset($ExtendedArtists[3]);
		$DisplayName = Artists::display_artists($ExtendedArtists);
	} else {
		$DisplayName = '';
	}

	$DisplayName .= "<a href=\"torrents.php?id=$GroupID&amp;torrentid=$FlacID\" class=\"tooltip\" title=\"View torrent\" dir=\"ltr\">$GroupName</a>";
	if ($GroupYear > 0) {
		$DisplayName .= " [$GroupYear]";
	}
	if ($ReleaseType > 0) {
		$DisplayName .= " [".$ReleaseTypes[$ReleaseType]."]";
	}

	$ExtraInfo = Torrents::torrent_info($Torrents[$FlacID]);
	if ($ExtraInfo) {
		$DisplayName .= ' - '.$ExtraInfo;
	}
?>
		<tr class="torrent torrent_row<?=$Torrents[$FlacID]['IsSnatched'] ? ' snatched_torrent' : ''?>">
			<td>
				<span class="torrent_links_block">
					<a href="torrents.php?action=download&amp;id=<?=$FlacID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>&amp;torrent_pass=<?=$LoggedUser['torrent_pass']?>" title="Download" class="brackets tooltip">DL</a>
				</span>
				<?=$DisplayName?>
				<div class="tags"><?=$TorrentTags->format()?></div>
			</td>
		</tr>
<?	} ?>
	</table>
</div>
<?
View::show_footer();
?>
