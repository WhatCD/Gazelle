<?
//**********************************************************************//
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~ Upload form ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//
// This page relies on the TORRENT_FORM class. All it does is call		//
// the necessary functions.												//
//----------------------------------------------------------------------//
// $Properties, $Err and $UploadForm are set in takeupload.php, and		//
// are only used when the form doesn't validate and this page must be	//
// called again.														//
//**********************************************************************//

ini_set('max_file_uploads', '100');
View::show_header('Upload', 'upload,validate_upload,valid_tags,musicbrainz,multiformat_uploader');

if (empty($Properties) && !empty($_GET['groupid']) && is_number($_GET['groupid'])) {
	$DB->query('
		SELECT
			tg.ID as GroupID,
			tg.CategoryID,
			tg.Name AS Title,
			tg.Year,
			tg.RecordLabel,
			tg.CatalogueNumber,
			tg.WikiImage AS Image,
			tg.WikiBody AS GroupDescription,
			tg.ReleaseType,
			tg.VanityHouse
		FROM torrents_group AS tg
			LEFT JOIN torrents AS t ON t.GroupID = tg.ID
		WHERE tg.ID = '.$_GET['groupid'].'
		GROUP BY tg.ID');
	if ($DB->has_results()) {
		list($Properties) = $DB->to_array(false, MYSQLI_BOTH);
		$UploadForm = $Categories[$Properties['CategoryID'] - 1];
		$Properties['CategoryName'] = $Categories[$Properties['CategoryID'] - 1];
		$Properties['Artists'] = Artists::get_artist($_GET['groupid']);

		$DB->query("
			SELECT
				GROUP_CONCAT(tags.Name SEPARATOR ', ') AS TagList
			FROM torrents_tags AS tt
				JOIN tags ON tags.ID = tt.TagID
			WHERE tt.GroupID = '$_GET[groupid]'");

		list($Properties['TagList']) = $DB->next_record();
	} else {
		unset($_GET['groupid']);
	}
	if (!empty($_GET['requestid']) && is_number($_GET['requestid'])) {
		$Properties['RequestID'] = $_GET['requestid'];
	}
} elseif (empty($Properties) && !empty($_GET['requestid']) && is_number($_GET['requestid'])) {
	$DB->query('
		SELECT
			r.ID AS RequestID,
			r.CategoryID,
			r.Title AS Title,
			r.Year,
			r.RecordLabel,
			r.CatalogueNumber,
			r.ReleaseType,
			r.Image
		FROM requests AS r
		WHERE r.ID = '.$_GET['requestid']);

	list($Properties) = $DB->to_array(false, MYSQLI_BOTH);
	$UploadForm = $Categories[$Properties['CategoryID'] - 1];
	$Properties['CategoryName'] = $Categories[$Properties['CategoryID'] - 1];
	$Properties['Artists'] = Requests::get_artists($_GET['requestid']);
	$Properties['TagList'] = implode(', ', Requests::get_tags($_GET['requestid'])[$_GET['requestid']]);
}

if (!empty($ArtistForm)) {
	$Properties['Artists'] = $ArtistForm;
}

require(SERVER_ROOT.'/classes/torrent_form.class.php');
$TorrentForm = new TORRENT_FORM($Properties, $Err);

if (!isset($Text)) {
	include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class
	$Text = new TEXT;
}

$GenreTags = $Cache->get_value('genre_tags');
if (!$GenreTags) {
	$DB->query("
		SELECT Name
		FROM tags
		WHERE TagType = 'genre'
		ORDER BY Name");
	$GenreTags = $DB->collect('Name');
	$Cache->cache_value('genre_tags', $GenreTags, 3600 * 6);
}

$DB->query('
	SELECT
		d.Name,
		d.Comment,
		d.Time
	FROM do_not_upload as d
	ORDER BY d.Sequence');
$DNU = $DB->to_array();
list($Name, $Comment, $Updated) = reset($DNU);
reset($DNU);
$DB->query("
	SELECT IF(MAX(t.Time) < '$Updated' OR MAX(t.Time) IS NULL, 1, 0)
	FROM torrents AS t
	WHERE UserID = ".$LoggedUser['ID']);
list($NewDNU) = $DB->next_record();
$HideDNU = check_perms('torrents_hide_dnu') && !$NewDNU;
?>
<div class="<?=(check_perms('torrents_hide_dnu') ? 'box pad' : '')?>" style="margin: 0px auto; width: 700px;">
	<h3 id="dnu_header">Do Not Upload List</h3>
	<p><?=$NewDNU ? '<strong class="important_text">' : '' ?>Last updated: <?=time_diff($Updated)?><?=$NewDNU ? '</strong>' : '' ?></p>
	<p>The following releases are currently forbidden from being uploaded to the site. Do not upload them unless your torrent meets a condition specified in the comment.
<?	if ($HideDNU) { ?>
	<span id="showdnu"><a href="#" onclick="$('#dnulist').gtoggle(); this.innerHTML = (this.innerHTML == 'Hide' ? 'Show' : 'Hide'); return false;" class="brackets">Show</a></span>
<?	} ?>
	</p>
	<table id="dnulist" class="<?=($HideDNU ? 'hidden' : '')?>">
		<tr class="colhead">
			<td width="50%"><strong>Name</strong></td>
			<td><strong>Comment</strong></td>
		</tr>
<? 	$TimeDiff = strtotime('-1 month', strtotime('now'));
	foreach ($DNU as $BadUpload) {
		list($Name, $Comment, $Updated) = $BadUpload;
?>
		<tr>
			<td>
				<?=$Text->full_format($Name) . "\n" ?>
<?		if ($TimeDiff < strtotime($Updated)) { ?>
				<strong class="important_text">(New!)</strong>
<?		} ?>
			</td>
			<td><?=$Text->full_format($Comment)?></td>
		</tr>
<? } ?>
	</table>
</div><?=($HideDNU ? '<br />' : '')?>
<?
$TorrentForm->head();
switch ($UploadForm) {
	case 'Music':
		$TorrentForm->music_form($GenreTags);
		break;

	case 'Audiobooks':
	case 'Comedy':
		$TorrentForm->audiobook_form();
		break;

	case 'Applications':
	case 'Comics':
	case 'E-Books':
	case 'E-Learning Videos':
		$TorrentForm->simple_form($Properties['CategoryID']);
		break;
	default:
		$TorrentForm->music_form($GenreTags);
}
$TorrentForm->foot();
?>
<script type="text/javascript">
	Format();
	Bitrate();
</script>
<?
View::show_footer();
?>
