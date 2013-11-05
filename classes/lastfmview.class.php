<?

class LastFMView {

	/* Renders the sidebar shown on user profiles
	 *
	 * @param $LastFMUsername
	 * @param $UserID
	 * @param $OwnProfile
	 */
	public static function render_sidebar($LastFMUsername, $UserID, $OwnProfile) {
		$LastFMInfo = LastFM::get_user_info($LastFMUsername);
?>
		<div class="box box_info box_lastfm">
			<div class="head colhead_dark">Last.fm</div>
			<ul class="stats nobullet">
				<li>
					Username: <a id="lastfm_username" href="<?=$LastFMInfo['user']['url']?>" target="_blank" class="tooltip" title="<?=$LastFMInfo['user']['name']?> on Last.fm: <?=number_format($LastFMInfo['user']['playcount'])?> plays, <?=number_format($LastFMInfo['user']['playlists'])?> playlist<?=$LastFMInfo['user']['playlists'] != 1 ? 's' : ''?>."><?=$LastFMUsername?></a>
				</li>
				<div id="lastfm_stats"<?=$OwnProfile ? ' data-uid="1"' : ''?>>
				</div>
				<li>
					<a href="#" id="lastfm_expand" onclick="return false;" class="brackets">Show more info</a>
<?
		//Append the reload stats button only if allowed on the current user page.
		$Response = G::$Cache->get_value("lastfm_clear_cache_$UserID");
		if (empty($Response)) {
?>
					<span id="lastfm_reload_container">
						<a href="#" id="lastfm_reload" onclick="return false;" class="brackets">Reload stats</a>
					</span>
<?		} ?>
				</li>
			</ul>
		</div>
<?
	}
}
