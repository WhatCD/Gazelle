<?
if (!isset($_GET['id']) || !is_number($_GET['id']) || !isset($_GET['torrentid']) || !is_number($_GET['torrentid'])) {
	error(0);
}
$GroupID = $_GET['id'];
$TorrentID = $_GET['torrentid'];

$DB->query("
	SELECT
		t.Media,
		t.Format,
		t.Encoding AS Bitrate,
		t.RemasterYear,
		t.Remastered,
		t.RemasterTitle,
		t.Scene,
		t.FreeTorrent,
		t.Description AS TorrentDescription,
		tg.CategoryID,
		tg.Name AS Title,
		tg.Year,
		tg.ArtistID,
		ag.Name AS ArtistName,
		t.GroupID,
		t.UserID,
		t.FreeTorrent
	FROM torrents AS t
		JOIN torrents_group AS tg ON tg.ID=t.GroupID
		LEFT JOIN artists_group AS ag ON ag.ArtistID=tg.ArtistID
	WHERE t.ID='$TorrentID'");

list($Properties) = $DB->to_array(false,MYSQLI_BOTH);

if (!$Properties) {
	error(404);
}

View::show_header('Edit torrent', 'upload');

if (!check_perms('site_moderate_requests')) {
	error(403);
}

?>
<div class="thin">
	<div class="header">
		<h2>Send PM To All Snatchers Of "<?=$Properties['ArtistName']?> - <?=$Properties['Title']?>"</h2>
	</div>
	<form class="send_form" name="mass_message" action="torrents.php" method="post">
		<input type="hidden" name="action" value="takemasspm" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
		<input type="hidden" name="groupid" value="<?=$GroupID?>" />
		<table class="layout">
			<tr>
				<td class="label">Subject</td>
				<td>
					<input type="text" name="subject" value="" size="60" />
				</td>
			</tr>
			<tr>
				<td class="label">Message</td>
				<td>
					<textarea name="message" id="message" cols="60" rows="8"></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
					<input type="submit" value="Send Mass PM" />
				</td>
			</tr>
		</table>
	</form>
</div>
<? View::show_footer(); ?>
