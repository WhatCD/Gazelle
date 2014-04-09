<?php

/*
 * Yeah, that's right, edit and new are the same place again.
 * It makes the page uglier to read but ultimately better as the alternative means
 * maintaining 2 copies of almost identical files.
 */


$NewRequest = $_GET['action'] === 'new';

if (!$NewRequest) {
	$RequestID = $_GET['id'];
	if (!is_number($RequestID)) {
		error(404);
	}
}


if ($NewRequest && ($LoggedUser['BytesUploaded'] < 250 * 1024 * 1024 || !check_perms('site_submit_requests'))) {
	error('You do not have enough uploaded to make a request.');
}

if (!$NewRequest) {
	if (empty($ReturnEdit)) {

		$Request = Requests::get_request($RequestID);
		if ($Request === false) {
			error(404);
		}

		// Define these variables to simplify _GET['groupid'] requests later on
		$CategoryID = $Request['CategoryID'];
		$Title = $Request['Title'];
		$Year = $Request['Year'];
		$Image = $Request['Image'];
		$ReleaseType = $Request['ReleaseType'];
		$GroupID = $Request['GroupID'];

		$VoteArray = Requests::get_votes_array($RequestID);
		$VoteCount = count($VoteArray['Voters']);

		$LogCue = $Request['LogCue'];
		$NeedCue = (strpos($LogCue, 'Cue') !== false);
		$NeedLog = (strpos($LogCue, 'Log') !== false);
		if ($NeedLog) {
			if (strpos($LogCue, '%') !== false) {
				preg_match('/\d+/', $LogCue, $Matches);
				$MinLogScore = (int)$Matches[0];
			}
		}

		$IsFilled = !empty($Request['TorrentID']);
		$CategoryName = $Categories[$CategoryID - 1];

		$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && ($CategoryID === '0' || ($CategoryName === 'Music' && $Request['Year'] === '0')));
		$CanEdit = ((!$IsFilled && $LoggedUser['ID'] === $Request['UserID'] && $VoteCount < 2) || $ProjectCanEdit || check_perms('site_moderate_requests'));

		if (!$CanEdit) {
			error(403);
		}

		if ($CategoryName === 'Music') {
			$ArtistForm = Requests::get_artists($RequestID);

			$BitrateArray = array();
			if ($Request['BitrateList'] == 'Any') {
				$BitrateArray = array_keys($Bitrates);
			} else {
				$BitrateArray = array_keys(array_intersect($Bitrates, explode('|', $Request['BitrateList'])));
			}

			$FormatArray = array();
			if ($Request['FormatList'] == 'Any') {
				$FormatArray = array_keys($Formats);
			} else {
				foreach ($Formats as $Key => $Val) {
					if (strpos($Request['FormatList'], $Val) !== false) {
						$FormatArray[] = $Key;
					}
				}
			}

			$MediaArray = array();
			if ($Request['MediaList'] == 'Any') {
				$MediaArray = array_keys($Media);
			} else {
				$MediaTemp = explode('|', $Request['MediaList']);
				foreach ($Media as $Key => $Val) {
					if (in_array($Val, $MediaTemp)) {
						$MediaArray[] = $Key;
					}
				}
			}
		}

		$Tags = implode(', ', $Request['Tags']);
	}
}

if ($NewRequest && !empty($_GET['artistid']) && is_number($_GET['artistid'])) {
	$DB->query("
		SELECT Name
		FROM artists_group
		WHERE artistid = ".$_GET['artistid']."
		LIMIT 1");
	list($ArtistName) = $DB->next_record();
	$ArtistForm = array(
		1 => array(array('name' => trim($ArtistName))),
		2 => array(),
		3 => array()
	);
} elseif ($NewRequest && !empty($_GET['groupid']) && is_number($_GET['groupid'])) {
	$ArtistForm = Artists::get_artist($_GET['groupid']);
	$DB->query("
		SELECT
			tg.Name,
			tg.Year,
			tg.ReleaseType,
			tg.WikiImage,
			GROUP_CONCAT(t.Name SEPARATOR ', '),
			tg.CategoryID
		FROM torrents_group AS tg
			JOIN torrents_tags AS tt ON tt.GroupID = tg.ID
			JOIN tags AS t ON t.ID = tt.TagID
		WHERE tg.ID = ".$_GET['groupid']);
	if (list($Title, $Year, $ReleaseType, $Image, $Tags, $CategoryID) = $DB->next_record()) {
		$GroupID = trim($_REQUEST['groupid']);
	}
}

View::show_header(($NewRequest ? 'Create a request' : 'Edit a request'), 'requests,form_validate');
?>
<div class="thin">
	<div class="header">
		<h2><?=($NewRequest ? 'Create a request' : 'Edit a request')?></h2>
	</div>

	<div class="box pad">
		<form action="" method="post" id="request_form" onsubmit="Calculate();">
			<div>
<?	if (!$NewRequest) { ?>
				<input type="hidden" name="requestid" value="<?=$RequestID?>" />
<?	} ?>
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="action" value="<?=($NewRequest ? 'takenew' : 'takeedit')?>" />
			</div>

			<table class="layout">
				<tr>
					<td colspan="2" class="center">Please make sure your request follows <a href="rules.php?p=requests">the request rules</a>!</td>
				</tr>
<?	if ($NewRequest || $CanEdit) { ?>
				<tr>
					<td class="label">
						Type
					</td>
					<td>
						<select id="categories" name="type" onchange="Categories();">
<?		foreach (Misc::display_array($Categories) as $Cat) { ?>
							<option value="<?=$Cat?>"<?=(!empty($CategoryName) && ($CategoryName === $Cat) ? ' selected="selected"' : '')?>><?=$Cat?></option>
<?		} ?>
						</select>
					</td>
				</tr>
				<tr id="artist_tr">
					<td class="label">Artist(s)</td>
					<td id="artistfields">
						<p id="vawarning" class="hidden">Please use the multiple artists feature rather than adding "Various Artists" as an artist; read <a href="wiki.php?action=article&amp;id=369">this</a> for more information.</p>
<?
		if (!empty($ArtistForm)) {
			$First = true;
			foreach ($ArtistForm as $Importance => $ArtistNames) {
				foreach ($ArtistNames as $Artist) {
?>
						<input type="text" id="artist" name="artists[]"<? Users::has_autocomplete_enabled('other'); ?> size="45" value="<?=display_str($Artist['name']) ?>" />
						<select id="importance" name="importance[]">
							<option value="1"<?=($Importance == '1' ? ' selected="selected"' : '')?>>Main</option>
							<option value="2"<?=($Importance == '2' ? ' selected="selected"' : '')?>>Guest</option>
							<option value="4"<?=($Importance == '4' ? ' selected="selected"' : '')?>>Composer</option>
							<option value="5"<?=($Importance == '5' ? ' selected="selected"' : '')?>>Conductor</option>
							<option value="6"<?=($Importance == '6' ? ' selected="selected"' : '')?>>DJ / Compiler</option>
							<option value="3"<?=($Importance == '3' ? ' selected="selected"' : '')?>>Remixer</option>
							<option value="7"<?=($Importance == '7' ? ' selected="selected"' : '')?>>Producer</option>
						</select>
						<? if ($First) { ?><a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a><? } $First = false; ?>
						<br />
<?
				}
			}
		} else {
?>						<input type="text" id="artist" name="artists[]"<? Users::has_autocomplete_enabled('other'); ?> size="45" onblur="CheckVA();" />
						<select id="importance" name="importance[]">
							<option value="1">Main</option>
							<option value="2">Guest</option>
							<option value="4">Composer</option>
							<option value="5">Conductor</option>
							<option value="6">DJ / Compiler</option>
							<option value="3">Remixer</option>
							<option value="7">Producer</option>
						</select>
						<a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
<?
		}
?>
					</td>
				</tr>
				<tr>
					<td class="label">Title</td>
					<td>
						<input type="text" name="title" size="45" value="<?=(!empty($Title) ? $Title : '')?>" />
					</td>
				</tr>
				<tr id="recordlabel_tr">
					<td class="label">Record label</td>
					<td>
						<input type="text" name="recordlabel" size="45" value="<?=(!empty($Request['RecordLabel']) ? $Request['RecordLabel'] : '')?>" />
					</td>
				</tr>
				<tr id="cataloguenumber_tr">
					<td class="label">Catalogue number</td>
					<td>
						<input type="text" name="cataloguenumber" size="15" value="<?=(!empty($Request['CatalogueNumber']) ? $Request['CatalogueNumber'] : '')?>" />
					</td>
				</tr>
				<tr id="oclc_tr">
					<td class="label">WorldCat (OCLC) ID</td>
					<td>
						<input type="text" name="oclc" size="15" value="<?=(!empty($Request['OCLC']) ? $Request['OCLC'] : '')?>" />
					</td>
				</tr>
<?	} ?>
				<tr id="year_tr">
					<td class="label">Year</td>
					<td>
						<input type="text" name="year" size="5" value="<?=(!empty($Year) ? $Year : '')?>" />
					</td>
				</tr>
<?	if ($NewRequest || $CanEdit) { ?>
				<tr id="image_tr">
					<td class="label">Image</td>
					<td>
						<input type="text" name="image" size="45" value="<?=(!empty($Image) ? $Image : '')?>" />
					</td>
				</tr>
<?	} ?>
				<tr>
					<td class="label">Tags</td>
					<td>
<?
	$GenreTags = $Cache->get_value('genre_tags');
	if (!$GenreTags) {
		$DB->query('
			SELECT Name
			FROM tags
			WHERE TagType = \'genre\'
			ORDER BY Name');
		$GenreTags = $DB->collect('Name');
		$Cache->cache_value('genre_tags', $GenreTags, 3600 * 6);
	}
?>
						<select id="genre_tags" name="genre_tags" onchange="add_tag(); return false;">
							<option>---</option>
<?	foreach (Misc::display_array($GenreTags) as $Genre) { ?>
							<option value="<?=$Genre?>"><?=$Genre?></option>
<?	} ?>
						</select>
						<input type="text" id="tags" name="tags" size="45" value="<?=(!empty($Tags) ? display_str($Tags) : '')?>"<? Users::has_autocomplete_enabled('other'); ?> />
						<br />
						Tags should be comma-separated, and you should use a period (".") to separate words inside a tag&#8202;&mdash;&#8202;e.g. "<strong class="important_text_alt">hip.hop</strong>".
						<br /><br />
						There is a list of official tags to the left of the text box. Please use these tags instead of "unofficial" tags (e.g. use the official "<strong class="important_text_alt">drum.and.bass</strong>" tag, instead of an unofficial "<strong class="important_text">dnb</strong>" tag.).
					</td>
				</tr>
<?	if ($NewRequest || $CanEdit) { ?>
				<tr id="releasetypes_tr">
					<td class="label">Release type</td>
					<td>
						<select id="releasetype" name="releasetype">
							<option value="0">---</option>
<?
		foreach ($ReleaseTypes as $Key => $Val) {
							//echo '<h1>'.$ReleaseType.'</h1>'; die();
?>							<option value="<?=$Key?>"<?=!empty($ReleaseType) ? ($Key == $ReleaseType ? ' selected="selected"' : '') : '' ?>><?=$Val?></option>
<?
		}
?>
						</select>
					</td>
				</tr>
				<tr id="formats_tr">
					<td class="label">Allowed formats</td>
					<td>
						<input type="checkbox" name="all_formats" id="toggle_formats" onchange="Toggle('formats', <?=($NewRequest ? 1 : 0)?>);"<?=!empty($FormatArray) && (count($FormatArray) === count($Formats)) ? ' checked="checked"' : ''; ?> /><label for="toggle_formats"> All</label>
						<span style="float: right;"><strong>NB: You cannot require a log or cue unless FLAC is an allowed format</strong></span>
<?		foreach ($Formats as $Key => $Val) {
			if ($Key % 8 === 0) {
				echo '<br />';
			} ?>
						<input type="checkbox" name="formats[]" value="<?=$Key?>" onchange="ToggleLogCue(); if (!this.checked) { $('#toggle_formats').raw().checked = false; }" id="format_<?=$Key?>"
							<?=(!empty($FormatArray) && in_array($Key, $FormatArray) ? ' checked="checked"' : '')?> /><label for="format_<?=$Key?>"> <?=$Val?></label>
<?		} ?>
					</td>
				</tr>
				<tr id="bitrates_tr">
					<td class="label">Allowed bitrates</td>
					<td>
						<input type="checkbox" name="all_bitrates" id="toggle_bitrates" onchange="Toggle('bitrates', <?=($NewRequest ? 1 : 0)?>);"<?=(!empty($BitrateArray) && (count($BitrateArray) === count($Bitrates)) ? ' checked="checked"' : '')?> /><label for="toggle_bitrates"> All</label>
<?		foreach ($Bitrates as $Key => $Val) {
			if ($Key % 8 === 0) {
				echo '<br />';
			} ?>
						<input type="checkbox" name="bitrates[]" value="<?=$Key?>" id="bitrate_<?=$Key?>"
							<?=(!empty($BitrateArray) && in_array($Key, $BitrateArray) ? ' checked="checked" ' : '')?>
						onchange="if (!this.checked) { $('#toggle_bitrates').raw().checked = false; }" /><label for="bitrate_<?=$Key?>"> <?=$Val?></label>
<?		} ?>
					</td>
				</tr>
				<tr id="media_tr">
					<td class="label">Allowed media</td>
					<td>
						<input type="checkbox" name="all_media" id="toggle_media" onchange="Toggle('media', <?=($NewRequest ? 1 : 0)?>);"<?=(!empty($MediaArray) && (count($MediaArray) === count($Media)) ? ' checked="checked"' : '')?> /><label for="toggle_media"> All</label>
<?		foreach ($Media as $Key => $Val) {
			if ($Key % 8 === 0) {
				echo '<br />';
			} ?>
						<input type="checkbox" name="media[]" value="<?=$Key?>" id="media_<?=$Key?>"
							<?=(!empty($MediaArray) && in_array($Key, $MediaArray) ? ' checked="checked" ' : '')?>
						onchange="if (!this.checked) { $('#toggle_media').raw().checked = false; }" /><label for="media_<?=$Key?>"> <?=$Val?></label>
<?		} ?>
					</td>
				</tr>
				<tr id="logcue_tr" class="hidden">
					<td class="label">Log / Cue (CD FLAC only)</td>
					<td>
						<input type="checkbox" id="needlog" name="needlog" onchange="ToggleLogScore()" <?=(!empty($NeedLog) ? 'checked="checked" ' : '')?>/><label for="needlog"> Require log</label>
						<span id="minlogscore_span" class="hidden">&nbsp;<input type="text" name="minlogscore" id="minlogscore" size="4" value="<?=(!empty($MinLogScore) ? $MinLogScore : '')?>" /> Minimum log score</span>
						<br />
						<input type="checkbox" id="needcue" name="needcue" <?=(!empty($NeedCue) ? 'checked="checked" ' : '')?>/><label for="needcue"> Require cue</label>
						<br />
					</td>
				</tr>
<?	} ?>
				<tr>
					<td class="label">Description</td>
					<td>
						<textarea name="description" cols="70" rows="7"><?=(!empty($Request['Description']) ? $Request['Description'] : '')?></textarea> <br />
					</td>
				</tr>
<?	if (check_perms('site_moderate_requests')) { ?>
				<tr>
					<td class="label">Torrent group</td>
					<td>
						<?=site_url()?>torrents.php?id=<input type="text" name="groupid" value="<?=$GroupID?>" size="15" /><br />
						If this request matches a torrent group <span style="font-weight: bold;">already existing</span> on the site, please indicate that here.
					</td>
				</tr>
<?	} elseif ($GroupID && ($CategoryID == 1)) { ?>
				<tr>
					<td class="label">Torrent group</td>
					<td>
						<a href="torrents.php?id=<?=$GroupID?>"><?=site_url()?>torrents.php?id=<?=$GroupID?></a><br />
						This request <?=($NewRequest ? 'will be' : 'is')?> associated with the above torrent group.
<?		if (!$NewRequest) {	?>
						If this is incorrect, please <a href="reports.php?action=report&amp;type=request&amp;id=<?=$RequestID?>">report this request</a> so that staff can fix it.
<? 		}	?>
						<input type="hidden" name="groupid" value="<?=$GroupID?>" />
					</td>
				</tr>
<?	}
	if ($NewRequest) { ?>
				<tr id="voting">
					<td class="label">Bounty (MB)</td>
					<td>
						<input type="text" id="amount_box" size="8" value="<?=(!empty($Bounty) ? $Bounty : '100')?>" onchange="Calculate();" />
						<select id="unit" name="unit" onchange="Calculate();">
							<option value="mb"<?=(!empty($_POST['unit']) && $_POST['unit'] === 'mb' ? ' selected="selected"' : '') ?>>MB</option>
							<option value="gb"<?=(!empty($_POST['unit']) && $_POST['unit'] === 'gb' ? ' selected="selected"' : '') ?>>GB</option>
						</select>
						<input type="button" value="Preview" onclick="Calculate();" />
						<strong><?=($RequestTax * 100)?>% of this is deducted as tax by the system.</strong>
					</td>
				</tr>
				<tr>
					<td class="label">Post request information</td>
					<td>
						<input type="hidden" id="amount" name="amount" value="<?=(!empty($Bounty) ? $Bounty : '100')?>" />
						<input type="hidden" id="current_uploaded" value="<?=$LoggedUser['BytesUploaded']?>" />
						<input type="hidden" id="current_downloaded" value="<?=$LoggedUser['BytesDownloaded']?>" />
						Bounty after tax: <strong><span id="bounty_after_tax">90.00 MB</span></strong><br />
						If you add the entered <strong><span id="new_bounty">100.00 MB</span></strong> of bounty, your new stats will be: <br />
						Uploaded: <span id="new_uploaded"><?=Format::get_size($LoggedUser['BytesUploaded'])?></span><br />
						Ratio: <span id="new_ratio"><?=Format::get_ratio_html($LoggedUser['BytesUploaded'], $LoggedUser['BytesDownloaded'])?></span>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="center">
						<input type="submit" id="button" value="Create request" disabled="disabled" />
					</td>
				</tr>
<?	} else { ?>
				<tr>
					<td colspan="2" class="center">
						<input type="submit" id="button" value="Edit request" />
					</td>
				</tr>
<?	} ?>
			</table>
		</form>
		<script type="text/javascript">ToggleLogCue();<?=$NewRequest ? " Calculate();" : '' ?></script>
		<script type="text/javascript">Categories();</script>
	</div>
</div>
<?
View::show_footer();
?>
