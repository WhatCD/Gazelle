<?
$Concerts = '';
ob_start();

$ArtistEvents = LastFM::get_artist_events($ArtistID, $Name);
$Hidden = false;
if ($ArtistEvents === false) { // Something went wrong
	echo '<br />An error occurred when retrieving concert info.<br />';
} elseif (!isset($ArtistEvents['events']['event'])) { // No upcoming events
	echo '<br />This artist has no upcoming concerts.<br />';
	$Hidden = true;
} else {
	echo '<ul>';
	if (isset($ArtistEvents['events']['event'][0])) { // Multiple events
		foreach ($ArtistEvents['events']['event'] as $Event) {
			make_concert_link($Event);
		}
	} else { // Single event
		make_concert_link($ArtistEvents['events']['event'], $Name);
	}
	echo '</ul>';
}
$Concerts .= ob_get_clean();
?>

<div class="box">
	<div id="concerts" class="head">
		<a href="#">&uarr;</a>&nbsp;<strong>Upcoming concerts</strong>
		<a href="#" class="brackets" onclick="$('#concertsbody').gtoggle(); return false;">Toggle</a>
	</div>
	<div id="concertsbody"<?=$Hidden ? ' class="hidden"' : '' ?>>
	<?=$Concerts?>
	</div>
</div>

<?
function make_concert_link($Event, $Name) {
	// The event doesn't have a start date (this should never happen)
	if ($Event['startDate'] == '') {
		return;
	}
	$Date = get_date_title($Event['startDate']);
	$ConcertTitle = $Date . ' - ' . $Event['venue']['name'] . ' at ' .
	$Event['venue']['location']['city'] . ', ' . $Event['venue']['location']['country'];
	$Concert = "<a href=\"" . $Event['url'] . "\">$ConcertTitle</a>";
?>
	<form class="hidden" action="" id="concert<?=$Event['id']?>" method="post">
		<input type="hidden" name="action" value="concert_thread" />
		<input type="hidden" name="concert_title" value="<?='[Concert] ' . $Event['artists']['artist'] . " - $ConcertTitle"?>" />
		<input type="hidden" name="concert_id" value="<?=$Event['id']?>" />
		<input type="hidden" name="concert_template" value="<?=get_concert_post_template($Name, $Event)?>" />
	</form>
	<li><?=$Concert?> - <a href="#" class="brackets" onclick="$('#concert<?=$Event['id']?>').raw().submit(); return false;">Go to thread</a></li>
<?
}

function get_concert_post_template($Artist, $Event) {
	$With = '';
	$EventTitle = '';
	$Location = '';
	$Directions = '';
	$Website = '';
	if (!empty($Event['venue']['website'])) {
		$Url = $Event['venue']['website'];
		if (strpos ($Url, '://') === false) {
			$Url = 'http://' . $Url;
		}
		$EventTitle = "[url=" . $Event['venue']['website'] . "]" . $Event['venue']['name'] . "[/url]";
	} else {
		$EventTitle = $Event['venue']['name'];
	}
	if (!empty($Event['venue']['location']['street']) && !empty($Event['venue']['location']['street']) && !empty($Event['venue']['location']['street'])) {
		$Location = $Event['venue']['location']['street'] . "\n" . $Event['venue']['location']['city'] . ", " . $Event['venue']['location']['country'];
	}
	if (!empty($Event['venue']['name']) && !empty($Event['venue']['city'])) {
		$Directions = "[b]Directions:[/b] [url=https://maps.google.com/maps?f=q&q=" . urlencode($Event['venue']['name'] . "," . $Event['venue']['location']['city']) . "&ie=UTF8&om=1&iwloc=addr]Show on Map[/url]";
	}
	if (!empty($Event['venue']['website'])) {
		$Url = $Event['venue']['website'];
		if (strpos ($Url, '://') === false) {
			$Url = 'http://' . $Url;
		}
		$Website = '[b]Website:[/b] ' . $Url;
	}
	if (isset($Event['artists']['artist']) && (count($Event['artists']['artist']) === 1 && strtolower($Event['artists']['artist'][1]) == strtolower($Artist))) {
		$i = 0;
		$j = count($Event['artists']['artist']) - 1;
		foreach ($Event['artists']['artist'] as $WithArtist) {
			if ($i === $j) {
				$With .= " and [artist]" . $WithArtist . "[/artist]";
			} elseif ($i == 0) {
				$With .= "[artist]" . $WithArtist . "[/artist]";
			} else {
				$With .= ", [artist]" . $WithArtist . "[/artist]";
			}
			$i++;
		}
	}
	return "[align=center][size=6][artist]" . $Artist . "[/artist] at " . $EventTitle . "[/size]
[size=4]$With
[b]" . get_date_post($Event['startDate']) . "[/b][/size]

[size=3]$Location" . "[/align]

$Directions
$Website
[b]Last.fm Listing:[/b] [url=" . $Event['venue']['url'] . "]Visit Last.fm[/url]

[align=center]. . . . . . . . . .[/align]";
}

function get_date_title($Str) {
	$Exploded = explode(' ', $Str);
	$Date = $Exploded[2] . ' ' . $Exploded[1] . ', ' . $Exploded[3];
	return $Date;
}

function get_date_post($Str) {
	$Exploded = explode(' ', $Str);
	$Date = $Exploded[2] . ' ' . $Exploded[1] . ', ' . $Exploded[3] . ' (' . rtrim($Exploded[0], ',') . ')';
	return $Date;
}

?>
