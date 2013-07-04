<?
/*
Tools necessary for economic management
1. Current overall stats (!economy)
2. Statistical traffic trends in a graph
	a. All time / 1 year (whichever is smaller)
	b. 1 month
	c. 1 week
	d. 1 day
3. Freeleech analysis
	a. total download average during freeleech vs. normal conditions
	b. total stats of a freeleech - uploaded torrents, upload amount, download amount, snatches, etc.
4. Traffic trends over an account's life, on average
	a. at one week, one month, whatever (selectable range in weeks) - averages (up/down/ratio)
	b. given a selected timespan, average ratio (users who are 4-5 months old have X ratio)
	c. average date at which >50% of accounts with ratios >1 reach 1.0 and never dip below, stockpiling buffer
5. Raw numbers
	a. total torrents, seeders, leechers
	b. average seeds/leechs per torrent
	c. average snatches/user
	d. average seeding torrents/user
	e. users on ratio watch
6. Distribution graph of seedership vs. torrent percentage
	a. graph showing that the top 1% of torrents has 50% of seeders or whatever the numbers might be
7. Effects of economic changes
	a. number of users changed by ratio being changed
	b. project effects with intelligent mathematical analysis of a 24, 48 or 72 hour freeleech
*/
if (!check_perms('site_view_flow')) {
	error(403);
}
View::show_header('Economy');

if (!$EconomicStats = $Cache->get_value('new_economic_stats')) {
	$DB->query("
		SELECT SUM(Uploaded), SUM(Downloaded), COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'");
	list($TotalUpload, $TotalDownload, $NumUsers) = $DB->next_record();
	$DB->query("
		SELECT SUM(Bounty)
		FROM requests_votes");
	list($TotalBounty) = $DB->next_record();
	$DB->query("
		SELECT SUM(rv.Bounty)
		FROM requests_votes AS rv
			JOIN requests AS r ON r.ID = rv.RequestID
		WHERE TorrentID > 0");
	list($AvailableBounty) = $DB->next_record();
	$DB->query("
		SELECT SUM(Snatched), COUNT(ID)
		FROM torrents");
	list($TotalSnatches, $TotalTorrents) = $DB->next_record(); // This is the total number of snatches for torrents that still exist

	$DB->query("
		SELECT COUNT(uid)
		FROM xbt_snatched");
	list($TotalOverallSnatches) = $DB->next_record();

	if (($PeerStats = $Cache->get_value('stats_peers')) === false) {
		$DB->query("
			SELECT COUNT(fid)
			FROM xbt_files_users
			WHERE remaining = 0");
		list($TotalSeeders) = $DB->next_record();
		$DB->query("
			SELECT COUNT(fid)
			FROM xbt_files_users
			WHERE remaining > 0");
		list($TotalLeechers) = $DB->next_record();
	} else {
		list($TotalLeechers,$TotalSeeders) = $PeerStats;
	}
	$TotalPeers = $TotalLeechers + $TotalSeeders;
	$DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE (
				SELECT COUNT(uid)
				FROM xbt_files_users
				WHERE uid = users_main.ID
				) > 0");
	list($TotalPeerUsers) = $DB->next_record();
	$Cache->cache_value('new_economic_stats',
				array($TotalUpload, $TotalDownload, $NumUsers, $TotalBounty,
					$AvailableBounty, $TotalSnatches, $TotalTorrents,
					$TotalOverallSnatches, $TotalSeeders, $TotalPeers,
					$TotalPeerUsers), 3600);
} else {
	list($TotalUpload, $TotalDownload, $NumUsers, $TotalBounty, $AvailableBounty,
		$TotalSnatches, $TotalTorrents, $TotalOverallSnatches, $TotalSeeders,
		$TotalPeers, $TotalPeerUsers) = $EconomicStats;
}

$TotalLeechers = $TotalPeers - $TotalSeeders;

?>
<div class="thin">
	<div class="box">
		<div class="head">Overall stats</div>
		<div class="pad">
			<ul class="stats nobullet">
				<li><strong>Total upload: </strong><?=Format::get_size($TotalUpload)?></li>
				<li><strong>Total download: </strong><?=Format::get_size($TotalDownload)?></li>
				<li><strong>Total buffer: </strong><?=Format::get_size($TotalUpload - $TotalDownload)?></li>
				<br />
				<li><strong>Mean ratio: </strong><?=Format::get_ratio_html($TotalUpload, $TotalDownload)?></li>
				<li><strong>Mean upload: </strong><?=Format::get_size($TotalUpload / $NumUsers)?></li>
				<li><strong>Mean download: </strong><?=Format::get_size($TotalDownload / $NumUsers)?></li>
				<li><strong>Mean buffer: </strong><?=Format::get_size(($TotalUpload - $TotalDownload) / $NumUsers)?></li>
				<br />
				<li><strong>Total request bounty: </strong><?=Format::get_size($TotalBounty)?></li>
				<li><strong>Available request bounty: </strong><?=Format::get_size($AvailableBounty)?></li>
			</ul>
		</div>
	</div>
	<br />
	<div class="box">
		<div class="head">Swarms and snatches</div>
		<div class="pad">
			<ul class="stats nobullet">
				<li><strong>Total seeders: </strong><?=number_format($TotalSeeders)?></li>
				<li><strong>Total leechers: </strong><?=number_format($TotalLeechers)?></li>
				<li><strong>Total peers: </strong><?=number_format($TotalSeeders + $TotalLeechers)?></li>
				<li><strong>Total snatches: </strong><?=number_format($TotalOverallSnatches)?></li>
				<li><strong>Seeder/leecher ratio: </strong><?=Format::get_ratio_html($TotalSeeders, $TotalLeechers)?></li>
				<li><strong>Seeder/snatch ratio: </strong><?=Format::get_ratio_html($TotalSeeders, $TotalOverallSnatches)?></li>
				<br />
				<li><strong>Mean seeders per torrent: </strong><?=number_format($TotalSeeders / $TotalTorrents, 2)?></li>
				<li><strong>Mean leechers per torrent: </strong><?=number_format($TotalLeechers / $TotalTorrents, 2)?></li>
				<li><strong>Mean snatches per torrent: </strong><?=number_format($TotalSnatches / $TotalTorrents, 2)?></li>
				<br />
				<li><strong>Mean seeding per user: </strong><?=number_format($TotalSeeders / $NumUsers, 2)?></li>
				<li><strong>Mean leeching per user: </strong><?=number_format($TotalLeechers / $NumUsers, 2)?></li>
				<li><strong>Mean snatches per user: </strong><?=number_format($TotalOverallSnatches / $NumUsers, 2)?></li>
				<br />
				<li><strong>Total users in at least 1 swarm: </strong><?=number_format($TotalPeerUsers)?></li>
				<li><strong>Mean seeding per user in at least 1 swarm: </strong><?=number_format($TotalSeeders / $TotalPeerUsers, 2)?></li>
				<li><strong>Mean leeching per user in at least 1 swarm: </strong><?=number_format($TotalLeechers / $TotalPeerUsers, 2)?></li>
				<li><strong>Mean snatches per user in at least 1 swarm: </strong><?=number_format($TotalSnatches / $TotalPeerUsers, 2)?></li>
			</ul>
		</div>
	</div>
</div>
<?
View::show_footer();
?>
