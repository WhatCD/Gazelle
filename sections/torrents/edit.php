<?
//*********************************************************************//
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~ Edit form ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~//
// This page relies on the TORRENT_FORM class. All it does is call     //
// the necessary functions.                                            //
//---------------------------------------------------------------------//
// At the bottom, there are grouping functions which are off limits to //
// most members.                                                       //
//*********************************************************************//

require(SERVER_ROOT.'/classes/class_torrent_form.php');

if(!is_number($_GET['id']) || !$_GET['id']) { error(0); }

$TorrentID = $_GET['id'];

$DB->query("SELECT 
	t.Media, 
	t.Format, 
	t.Encoding AS Bitrate, 
	t.RemasterYear, 
	t.Remastered, 
	t.RemasterTitle, 
	t.RemasterCatalogueNumber,
	t.RemasterRecordLabel,
	t.Scene, 
	t.FreeTorrent, 
	t.Dupable, 
	t.DupeReason, 
	t.Description AS TorrentDescription, 
	tg.CategoryID,
	tg.Name AS Title,
	tg.Year,
	tg.ArtistID,
	ag.Name AS ArtistName,
	t.GroupID,
	t.UserID,
	t.HasLog,
	t.HasCue,
	t.LogScore,
	t.ExtendedGrace,
	bt.TorrentID AS BadTags,
	bf.TorrentID AS BadFolders,
	bfi.TorrentID AS BadFiles
	FROM torrents AS t 
	LEFT JOIN torrents_group AS tg ON tg.ID=t.GroupID
	LEFT JOIN artists_group AS ag ON ag.ArtistID=tg.ArtistID
	LEFT JOIN torrents_bad_tags AS bt ON bt.TorrentID=t.ID
	LEFT JOIN torrents_bad_folders AS bf ON bf.TorrentID=t.ID
	LEFT JOIN torrents_bad_files AS bfi ON bfi.TorrentID=t.ID
	WHERE t.ID='$TorrentID'");

list($Properties) = $DB->to_array(false,MYSQLI_BOTH);
if(!$Properties) { error(404); }

$UploadForm = $Categories[$Properties['CategoryID']-1];

if(($LoggedUser['ID']!=$Properties['UserID'] && !check_perms('torrents_edit')) || $LoggedUser['DisableWiki']) {
	error(403);
}


show_header('Edit torrent', 'upload');


if(!($Properties['Remastered'] && !$Properties['RemasterYear']) || check_perms('edit_unknowns')) {
	$TorrentForm = new TORRENT_FORM($Properties, $Err, false);
	
	$TorrentForm->head();
	switch ($UploadForm) {
		case 'Music':
			$TorrentForm->music_form('');
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
			$TorrentForm->music_form('');
	}
	$TorrentForm->foot();
}


if(check_perms('torrents_edit') && $Properties['CategoryID'] == 1) {
?>
<div class="thin">
<?

?>
	<h2>Change Group</h2>
	<form action="torrents.php" method="post">
		<input type="hidden" name="action" value="editgroupid" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
		<input type="hidden" name="oldgroupid" value="<?=$Properties['GroupID']?>" />
		<table>
			<tr>
				<td class="label">Group ID</td>
				<td>
						<input type="text" name="groupid" value="<?=$Properties['GroupID']?>" size="10" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
						<input type="submit" value="Change group ID" />
				</td>
			</tr>
		</table>
	</form>
	<h2>Split off into new group</h2>
	<form action="torrents.php" method="post">
		<input type="hidden" name="action" value="newgroup" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
		<input type="hidden" name="oldgroupid" value="<?=$Properties['GroupID']?>" />
		<input type="hidden" name="oldartistid" value="<?=$Properties['ArtistID']?>" />
		<table>
			<tr>
				<td class="label">Artist</td>
				<td>
						<input type="text" name="artist" value="<?=$Properties['ArtistName']?>" size="50" />
				</td>
			</tr>
			<tr>
				<td class="label">Title</td>
				<td>
						<input type="text" name="title" value="<?=$Properties['Title']?>" size="50" />
				</td>
			</tr>
			<tr>
				<td class="label">Year</td>
				<td>
						<input type="text" name="year" value="<?=$Properties['Year']?>" size="10" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="center">
						<input type="submit" value="Split into new group" />
				</td>
			</tr>
		</table>
	</form>
	<br />
<?

?>
</div>
<?
} // if check_perms('torrents_edit')

show_footer();
?>
