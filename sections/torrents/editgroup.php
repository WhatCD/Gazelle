<?
/************************************************************************
||------------|| Edit torrent group wiki page ||-----------------------||

This page is the page that is displayed when someone feels like editing
a torrent group's wiki page.

It is called when $_GET['action'] == 'edit'. $_GET['groupid'] is the
ID of the torrent group and must be set.

The page inserts a new revision into the wiki_torrents table, and clears
the cache for the torrent group page.

************************************************************************/

$GroupID = $_GET['groupid'];
if (!is_number($GroupID) || !$GroupID) {
	error(0);
}

// Get the torrent group name and the body of the last revision
$DB->query("
	SELECT
		tg.Name,
		wt.Image,
		wt.Body,
		tg.WikiImage,
		tg.WikiBody,
		tg.Year,
		tg.RecordLabel,
		tg.CatalogueNumber,
		tg.ReleaseType,
		tg.CategoryID,
		tg.VanityHouse
	FROM torrents_group AS tg
		LEFT JOIN wiki_torrents AS wt ON wt.RevisionID = tg.RevisionID
	WHERE tg.ID = '$GroupID'");
if (!$DB->has_results()) {
	error(404);
}
list($Name, $Image, $Body, $WikiImage, $WikiBody, $Year, $RecordLabel, $CatalogueNumber, $ReleaseType, $CategoryID, $VanityHouse) = $DB->next_record();

if (!$Body) {
	$Body = $WikiBody;
	$Image = $WikiImage;
}

View::show_header('Edit torrent group');

// Start printing form
?>
<div class="thin">
	<div class="header">
		<h2>Edit <a href="torrents.php?id=<?=$GroupID?>"><?=$Name?></a></h2>
	</div>
	<div class="box pad">
		<form class="edit_form" name="torrent_group" action="torrents.php" method="post">
			<div>
				<input type="hidden" name="action" value="takegroupedit" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="groupid" value="<?=$GroupID?>" />
				<h3>Image:</h3>
				<input type="text" name="image" size="92" value="<?=$Image?>" /><br />
				<h3>Torrent group description:</h3>
				<textarea name="body" cols="91" rows="20"><?=$Body?></textarea><br />
<?	if ($CategoryID == 1) { ?>
				<select id="releasetype" name="releasetype">
<?		foreach ($ReleaseTypes as $Key => $Val) { ?>
					<option value="<?=$Key?>"<?=($Key == $ReleaseType ? ' selected="selected"' : '')?>><?=$Val?></option>
<?		} ?>
				</select>
<?		if (check_perms('torrents_edit_vanityhouse')) { ?>
				<br />
				<h3>
					<label>Vanity House: <input type="checkbox" name="vanity_house" value="1" <?=($VanityHouse ? 'checked="checked" ' : '')?>/></label>
				</h3>
<?
		}
	}
?>
				<h3>Edit summary:</h3>
				<input type="text" name="summary" size="92" /><br />
				<div style="text-align: center;">
					<input type="submit" value="Submit" />
				</div>
			</div>
		</form>
	</div>
<?
	$DB->query("
		SELECT UserID
		FROM torrents
		WHERE GroupID = $GroupID");
	//Users can edit the group info if they've uploaded a torrent to the group or have torrents_edit
	if (in_array($LoggedUser['ID'], $DB->collect('UserID')) || check_perms('torrents_edit')) { ?>
	<h3>Non-wiki torrent group editing</h3>
	<div class="box pad">
		<form class="edit_form" name="torrent_group" action="torrents.php" method="post">
			<input type="hidden" name="action" value="nonwikiedit" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="groupid" value="<?=$GroupID?>" />
			<table cellpadding="3" cellspacing="1" border="0" class="layout border" width="100%">
				<tr>
					<td colspan="2" class="center">This is for editing the information related to the <strong>original release</strong> only.</td>
				</tr>
				<tr>
					<td class="label">Year</td>
					<td>
						<input type="text" name="year" size="10" value="<?=$Year?>" />
					</td>
				</tr>
				<tr>
					<td class="label">Record label</td>
					<td>
						<input type="text" name="record_label" size="40" value="<?=$RecordLabel?>" />
					</td>
				</tr>
				<tr>
					<td class="label">Catalogue number</td>
					<td>
						<input type="text" name="catalogue_number" size="40" value="<?=$CatalogueNumber?>" />
					</td>
				</tr>
<?	if (check_perms('torrents_freeleech')) { ?>
				<tr>
					<td class="label">Torrent <strong>group</strong> leech status</td>
					<td>
						<input type="checkbox" id="unfreeleech" name="unfreeleech" /><label for="unfreeleech"> Reset</label>
						<input type="checkbox" id="freeleech" name="freeleech" /><label for="freeleech"> Freeleech</label>
						<input type="checkbox" id="neutralleech" name="neutralleech" /><label for="neutralleech"> Neutral Leech</label>
						 because
						<select name="freeleechtype">
<?		$FL = array('N/A', 'Staff Pick', 'Perma-FL', 'Vanity House');
		foreach ($FL as $Key => $FLType) { ?>
							<option value="<?=$Key?>"<?=($Key == $Torrent['FreeLeechType'] ? ' selected="selected"' : '')?>><?=$FLType?></option>
<?		} ?>
						</select>
					</td>
				</tr>
<?	} ?>
			</table>
			<input type="submit" value="Edit" />
		</form>
	</div>
<?
	}
	if (check_perms('torrents_edit')) {
?>
	<h3>Rename (will not merge)</h3>
	<div class="box pad">
		<form class="rename_form" name="torrent_group" action="torrents.php" method="post">
			<div>
				<input type="hidden" name="action" value="rename" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="groupid" value="<?=$GroupID?>" />
				<input type="text" name="name" size="92" value="<?=$Name?>" />
				<div style="text-align: center;">
					<input type="submit" value="Rename" />
				</div>
			</div>
		</form>
	</div>
	<h3>Merge with another group</h3>
	<div class="box pad">
		<form class="merge_form" name="torrent_group" action="torrents.php" method="post">
			<div>
				<input type="hidden" name="action" value="merge" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="groupid" value="<?=$GroupID?>" />
				<h3>Target torrent group ID</h3>
				<input type="text" name="targetgroupid" size="10" />
				<div style="text-align: center;">
					<input type="submit" value="Merge" />
				</div>
			</div>
		</form>
	</div>
<?	} ?>
</div>
<? View::show_footer(); ?>
