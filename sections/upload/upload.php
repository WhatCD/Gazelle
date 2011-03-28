<?
//*********************************************************************//
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~ Upload form ~~~~~~~~~~~~~~~~~~~~~~~~~~~~//
// This page relies on the TORRENT_FORM class. All it does is call	 //
// the necessary functions.											//
//---------------------------------------------------------------------//
// $Properties, $Err and $UploadForm are set in takeupload.php, and	//
// are only used when the form doesn't validate and this page must be  //
// called again.													   //
//*********************************************************************//

ini_set('max_file_uploads','100');
show_header('Upload','upload');

if(!empty($_GET['groupid']) && is_number($_GET['groupid'])) {
	$DB->query("SELECT 
		tg.ID as GroupID,
		tg.CategoryID,
		tg.Name AS Title,
		tg.Year,
		tg.RecordLabel,
		tg.CatalogueNumber,
		tg.WikiImage AS Image,
		tg.WikiBody AS GroupDescription,
		tg.ReleaseType
		FROM torrents_group AS tg
		LEFT JOIN torrents AS t ON t.GroupID = tg.ID
		WHERE tg.ID=".$_GET['groupid']."
		GROUP BY tg.ID");
	
	list($Properties) = $DB->to_array(false,MYSQLI_BOTH);
	$UploadForm = $Categories[$Properties['CategoryID']-1];
	$Properties['CategoryName'] = $Categories[$Properties['CategoryID']-1];
	$Properties['Artists'] = get_artist($_GET['groupid']);
	
	$DB->query("SELECT 
		GROUP_CONCAT(tags.Name SEPARATOR ', ') AS TagList 
		FROM torrents_tags AS tt JOIN tags ON tags.ID=tt.TagID
		WHERE tt.GroupID='$_GET[groupid]'");
	
	list($Properties['TagList']) = $DB->next_record();
}

if(!empty($_GET['requestid']) && is_number($_GET['requestid'])) {
	include(SERVER_ROOT.'/sections/requests/functions.php');	
	$DB->query("SELECT
		r.ID AS RequestID,
		r.CategoryID,
		r.Title AS Title,
		r.Year,
		r.CatalogueNumber,
		r.ReleaseType,
		r.Image
		FROM requests AS r
		WHERE r.ID=".$_GET['requestid']);
	
	list($Properties) = $DB->to_array(false,MYSQLI_BOTH);
	$UploadForm = $Categories[$Properties['CategoryID']-1];
	$Properties['CategoryName'] = $Categories[$Properties['CategoryID']-1];
	$Properties['Artists'] = get_request_artists($_GET['requestid']);
	$Properties['TagList'] = implode(", ", get_request_tags($_GET['requestid']));
}

if(!empty($ArtistForm)) {
	$Properties['Artists'] = $ArtistForm;
}

require(SERVER_ROOT.'/classes/class_torrent_form.php');
$TorrentForm = new TORRENT_FORM($Properties, $Err);

if(!isset($Text)) {
	include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
	$Text = new TEXT;
}

$GenreTags = $Cache->get_value('genre_tags');
if(!$GenreTags) {
	$DB->query("SELECT Name FROM tags WHERE TagType='genre' ORDER BY Name");
	$GenreTags =  $DB->collect('Name');
	$Cache->cache_value('genre_tags', $GenreTags, 3600*6);
}
?>
<div style="margin:0px auto;width:700px">
	<h3 id="dnu_header">Do not upload</h3>
	<p>The following releases are currently forbidden from being uploaded from the site. Do not upload them unless your torrent meets a condition specified in the comment.</p>
<?
$DB->query("SELECT 
	d.Name, 
	d.Comment
	FROM do_not_upload as d
	ORDER BY d.Time");
?>
	<table style="">
		<tr class="colhead">
			<td width="50%"><strong>Name</strong></td>
			<td><strong>Comment</strong></td>
		</tr>
<? while(list($Name, $Comment) = $DB->next_record()){ ?>
		<tr>
			<td><?=$Text->full_format($Name)?></td>
			<td><?=$Text->full_format($Comment)?></td>
		</tr>
<? } ?>
	</table>
</div>
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
	Media();
</script>

<?
show_footer();
?>
