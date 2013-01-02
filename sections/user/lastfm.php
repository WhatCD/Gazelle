<?
$DB->query("SELECT username FROM lastfm_users WHERE ID = '$UserID'");
if ($DB->record_count()) {
	list($LastFMUsername) = $DB->next_record();
	$LastFMInfo = LastFM::get_user_info($LastFMUsername);
	$RecentTracks = LastFM::get_recent_tracks($LastFMUsername);
	?>
<div class="box">
    <div class="head">
        <span style="float:left;">Last.FM
        <span id="tabs" class="tabs">
            <a href="#" style="font-weight: bold;">[Info]</a>
            <a href="#">[Top Artists]</a>
            <a href="#">[Top Albums]</a>
            <a href="#">[Top Tracks]</a>
			<a href="#">[Tasteometer]</a>
        </span>
        </span>
        <span style="float:right;"><a href="#" onclick="$('#lastfm_div').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(Show)':'(Hide)'); return false;">(Hide)</a></span>&nbsp;
    </div>
    <div class="pad" id="lastfm_div">
        <div id="contents_div">
            <div id="tab_0_contents">
                <div class="lastfm_user_info">
                    <strong><a id="lastfm_username" href="<?=$LastFMInfo['user']['url']?>"><?=$LastFMInfo['user']['name']?></a></strong>
                    <br/>Number of plays: <?=$LastFMInfo['user']['playcount']?>
                    <br/>Playlists: <?=$LastFMInfo['user']['playlists']?>
                </div>
                <br/>

                <div class="lastfm_recent_tracks">
                    <strong>Recently Played</strong>
                    <ul class="nobullet">
						<?
						foreach ($RecentTracks['recenttracks']['track'] as $Track) {
							?>
                            <li>
                                <a href="torrents.php?searchstr=<?=$Track['artist']['#text']?>"><?=$Track['artist']['#text']?></a> - <a href="<?=$Track['url']?>"><?=$Track['name']?></a> - <a href="torrents.php?searchstr=<?=$Track['album']['#text']?>"><?=$Track['album']['#text']?></a>
                            </li>
							<?
						}
						?>
                    </ul>
                </div>
            </div>
            <div id="tab_1_contents">
            </div>
            <div id="tab_2_contents">
            </div>
            <div id="tab_3_contents">
            </div>
            <div id="tab_4_contents">
            </div>
        </div>
    </div>
</div>

<? } ?>
