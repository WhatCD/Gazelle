<?

if (!empty($_GET['userid'])) {
	if (!check_perms('users_override_paranoia')) {
		error(403);
	}
	$UserID = $_GET['userid'];
	$Sneaky = $UserID !== $LoggedUser['ID'];
	if (!is_number($UserID)) {
		error(404);
	}
	$DB->query("
		SELECT Username
		FROM users_main
		WHERE ID = '$UserID'");
	list($Username) = $DB->next_record();
} else {
	$UserID = $LoggedUser['ID'];
}

$Sneaky = $UserID !== $LoggedUser['ID'];

//$ArtistList = Bookmarks::all_bookmarks('artist', $UserID);

$DB->query("
	SELECT ag.ArtistID, ag.Name
	FROM bookmarks_artists AS ba
		INNER JOIN artists_group AS ag ON ba.ArtistID = ag.ArtistID
	WHERE ba.UserID = $UserID
	ORDER BY ag.Name");

$ArtistList = $DB->to_array();

$Title = $Sneaky ? "$Username's bookmarked artists" : 'Your bookmarked artists';

View::show_header($Title, 'browse');

?>
<div class="thin">
	<div class="header">
		<h2><?=$Title?></h2>
		<div class="linkbox">
			<a href="bookmarks.php?type=torrents" class="brackets">Torrents</a>
			<a href="bookmarks.php?type=artists" class="brackets">Artists</a>
			<a href="bookmarks.php?type=collages" class="brackets">Collages</a>
			<a href="bookmarks.php?type=requests" class="brackets">Requests</a>
		</div>
	</div>
	<div class="box pad" align="center">
<? if (count($ArtistList) === 0) { ?>
		<h2>You have not bookmarked any artists.</h2>
	</div>
</div><!--content-->
<?
	View::show_footer();
	die();
} ?>
	<table width="100%" class="artist_table">
		<tr class="colhead">
			<td>Artist</td>
		</tr>
<?
$Row = 'a';
foreach ($ArtistList as $Artist) {
	$Row = $Row === 'a' ? 'b' : 'a';
	list($ArtistID, $Name) = $Artist;
?>
		<tr class="row<?=$Row?> bookmark_<?=$ArtistID?>">
			<td>
				<a href="artist.php?id=<?=$ArtistID?>"><?=$Name?></a>
				<span style="float: right;">
<?
	if (check_perms('site_torrents_notify')) {
		if (($Notify = $Cache->get_value('notify_artists_'.$LoggedUser['ID'])) === false) {
			$DB->query("
				SELECT ID, Artists
				FROM users_notify_filters
				WHERE UserID = '$LoggedUser[ID]'
					AND Label = 'Artist notifications'
				LIMIT 1");
			$Notify = $DB->next_record(MYSQLI_ASSOC);
			$Cache->cache_value('notify_artists_'.$LoggedUser['ID'], $Notify, 0);
		}
		if (stripos($Notify['Artists'], "|$Name|") === false) {
?>
					<a href="artist.php?action=notify&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Notify of new uploads</a>
<?
		} else {
?>
					<a href="artist.php?action=notifyremove&amp;artistid=<?=$ArtistID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Do not notify of new uploads</a>
<?
		}
	}
?>
					<a href="#" id="bookmarklink_artist_<?=$ArtistID?>" onclick="Unbookmark('artist', <?=$ArtistID?>, 'Bookmark'); return false;" class="brackets">Remove bookmark</a>
				</span>
			</td>
		</tr>
<?
}
?>
	</table>
	</div>
</div>
<?
View::show_footer();
$Cache->cache_value('bookmarks_'.$UserID, serialize(array(array($Username, $TorrentList, $CollageDataList))), 3600);
?>
