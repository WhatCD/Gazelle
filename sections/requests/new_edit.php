<?

/*
 * Yeah, that's right, edit and new are the same place again.
 * It makes the page uglier to read but ultimately better as the alternative means
 * maintaining 2 copies of almost identical files. 
 */


$NewRequest = ($_GET['action'] == "new" ? true : false);

if(!$NewRequest) {
	$RequestID = $_GET['id'];
	if(!is_number($RequestID)) {
		error(404);
	}
}


if($NewRequest && ($LoggedUser['BytesUploaded'] < 250*1024*1024 || !check_perms('site_submit_requests'))) {
	error('You do not have enough uploaded to make a request.');
}

if(!$NewRequest) {
	if(empty($ReturnEdit)) {
		
		$Request = get_requests(array($RequestID));
		$Request = $Request['matches'][$RequestID];
		if(empty($Request)) {
			error(404);
		}
		
		list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Year, $Image, $Description, $CatalogueNumber, $ReleaseType,
		$BitrateList, $FormatList, $MediaList, $LogCue, $FillerID, $FillerName, $TorrentID, $TimeFilled) = $Request;
		$VoteArray = get_votes_array($RequestID);
		$VoteCount = count($VoteArray['Voters']);
		
		$NeedCue = (strpos($LogCue, "Cue") !== false);
		$NeedLog = (strpos($LogCue, "Log") !== false);
		if($NeedLog) {
			if(strpos($LogCue, "%")) {
				preg_match("/\d+/", $LogCue, $Matches);
				$MinLogScore = (int) $Matches[0];
			}
		}
		
		$IsFilled = !empty($TorrentID);
		$CategoryName = $Categories[$CategoryID - 1];
		
		$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0) || ($CategoryName == "Music" && $Year == 0)));
		$CanEdit = ((!$IsFilled && $LoggedUser['ID'] == $RequestorID && $VoteCount < 2) || $ProjectCanEdit || check_perms('site_moderate_requests'));
		
		if(!$CanEdit) {
			error(403);
		}
		
		if($CategoryName == "Music") {
			$ArtistForm = get_request_artists($RequestID);
			
			$BitrateArray = array();
			if($BitrateList == "Any") {
				$BitrateArray = array_keys($Bitrates);
			} else {
				foreach ($Bitrates as $Key => $Val) {
					if(strpos($BitrateList, $Val) !== false) {
						$BitrateArray[] = $Key;
					}
				}
			}
			
			$FormatArray = array();
			if($FormatList == "Any") {
				$FormatArray = array_keys($Formats);
			} else {
				foreach ($Formats as $Key => $Val) {
					if(strpos($FormatList, $Val) !== false) {
						$FormatArray[] = $Key;
					}
				}
			}
			
			
			$MediaArray = array();
			if($MediaList == "Any") {
				$MediaArray = array_keys($Media);
			} else {
				foreach ($Media as $Key => $Val) {
					if(strpos($MediaList, $Val) !== false) {
						$MediaArray[] = $Key;
					}
				}
			}
		}
		
		$Tags = implode(", ", $Request['Tags']);
	}
}

if($NewRequest && !empty($_GET['artistid']) && is_number($_GET['artistid'])) {
	$DB->query("SELECT Name FROM artists_group WHERE artistid = ".$_GET['artistid']." LIMIT 1");
	list($ArtistName) = $DB->next_record();
	$ArtistForm = array(
		1 => array(array('name' => trim($ArtistName))),
		2 => array(),
		3 => array()
	);
} elseif($NewRequest && !empty($_GET['groupid']) && is_number($_GET['groupid'])) {
	$ArtistForm = get_artist($_GET['groupid']);
	$DB->query("SELECT tg.Name, 
					tg.Year, 
					tg.ReleaseType, 
					tg.WikiImage,
					GROUP_CONCAT(t.Name SEPARATOR ', ') 
				FROM torrents_group AS tg 
					JOIN torrents_tags AS tt ON tt.GroupID=tg.ID
					JOIN tags AS t ON t.ID=tt.TagID
				WHERE tg.ID = ".$_GET['groupid']);
	list($Title, $Year, $ReleaseType, $Image, $Tags) = $DB->next_record();
}

show_header(($NewRequest ? "Create a request" : "Edit a request"), 'requests');
?>
<div class="thin">
	<h2><?=($NewRequest ? "Create a request" : "Edit a request")?></h2>
	
	<div class="box pad">
		<form action="" method="post" id="request_form" onsubmit="Calculate();">
			<div>
<? if(!$NewRequest) { ?>
				<input type="hidden" name="requestid" value="<?=$RequestID?>" /> 
<? } ?>
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="action" value="<?=$NewRequest ? 'takenew' : 'takeedit'?>" />
			</div>
			
			<table>
				<tr>
					<td colspan="2" class="center">Please make sure your request follows <a href="rules.php?p=requests">the request rules!</a></td>
				</tr>
<?	if($NewRequest || $CanEdit) { ?>
				<tr>
					<td class="label">
						Type
					</td>
					<td>
						<select id="categories" name="type" onchange="Categories()">
<?		foreach(display_array($Categories) as $Cat){ ?>
							<option value='<?=$Cat?>' <?=(!empty($CategoryName) && ($CategoryName ==  $Cat) ? 'selected="selected"' : '')?>><?=$Cat?></option>
<?		} ?>
						</select>
					</td>
				</tr>
				<tr id="artist_tr">
					<td class="label">Artist(s)</td>		
					<td id="artistfields">
						<p id="vawarning" class="hidden">Please use the multiple artists feature rather than adding 'Various Artists' as an artist, read <a href='wiki.php?action=article&id=369'>this</a> for more information on why.</p>
<?

		if(!empty($ArtistForm)) {
			$First = true;
			foreach($ArtistForm as $Importance => $ArtistNames) {
				foreach($ArtistNames as $Artist) {
?>
						<input type="text" id="artist" name="artists[]" size="45" value="<?=display_str($Artist['name']) ?>" />
						<select id="importance" name="importance[]" >
								<option value="1"<?=($Importance == '1' ? ' selected="selected"' : '')?>>Main</option>
								<option value="2"<?=($Importance == '2' ? ' selected="selected"' : '')?>>Guest</option>
								<option value="3"<?=($Importance == '3' ? ' selected="selected"' : '')?>>Remixer</option>
						</select>
						<?if($First) { ?>[<a href="#" onclick="AddArtistField();return false;">+</a>] [<a href="#" onclick="RemoveArtistField();return false;">-</a>] <? } $First = false;?>
						<br />
<?				}
			}
		} else {
?>						<input type="text" id="artist" name="artists[]" size="45" onblur="CheckVA();" />
						<select id="importance" name="importance[]" >
								<option value="1">Main</option>
								<option value="2">Guest</option>
								<option value="3">Remixer</option>
						</select>
						[<a href="#" onclick="AddArtistField();return false;">+</a>] [<a href="#" onclick="RemoveArtistField();return false;">-</a>]
<?
		}
?>	
					</td>
				</tr>

				<tr>
					<td class="label">Title</td>
					<td>
						<input type="text" name="title" size="45" value="<?=(!empty($Title) ? display_str($Title) : '')?>" />
					</td>
				</tr>
				<tr id="cataloguenumber_tr">
					<td class="label">Catalogue Number</td>
					<td>
						<input type="text" name="cataloguenumber" size="15" value="<?=(!empty($CatalogueNumber) ? display_str($CatalogueNumber) : '')?>" />
					</td>
				</tr>
<?	} ?>
				<tr id="year_tr">
					<td class="label">Year</td>
					<td>
						<input type="text" name="year" size="5" value="<?=(!empty($Year) ? display_str($Year) : '')?>" />
					</td>
				</tr>
<?	if($NewRequest || $CanEdit) { ?>
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
	if(!$GenreTags) {
		$DB->query('SELECT Name FROM tags WHERE TagType=\'genre\' ORDER BY Name');
		$GenreTags =  $DB->collect('Name');
		$Cache->cache_value('genre_tags', $GenreTags, 3600*6);
	}
?>
						<select id="genre_tags" name="genre_tags" onchange="add_tag();return false;" >
							<option>---</option>
<?	foreach(display_array($GenreTags) as $Genre){ ?>
							<option value="<?=$Genre ?>"><?=$Genre ?></option>
<?	} ?>
						</select>
						<input type="text" id="tags" name="tags" size="45" value="<?=(!empty($Tags) ? display_str($Tags) : '')?>" />
						<br />
						Tags should be comma separated, and you should use a period ('.') to separate words inside a tag - eg. '<strong style="color:green;">hip.hop</strong>'. 
						<br /><br />
						There is a list of official tags to the left of the text box. Please use these tags instead of 'unofficial' tags (eg. use the official '<strong style="color:green;">drum.and.bass</strong>' tag, instead of an unofficial '<strong style="color:red;">dnb</strong>' tag.)
					</td>
				</tr>
<?	if($NewRequest || $CanEdit) { ?>
				<tr id="releasetypes_tr">
					<td class="label">Release Type</td>
					<td>
						<select id="releasetype" name="releasetype">
							<option value='0'>---</option>
<?		
		foreach ($ReleaseTypes as $Key => $Val) {
							//echo '<h1>'.$ReleaseType.'</h1>'; die();
?>							<option value='<?=$Key?>' <?=(!empty($ReleaseType) ? ($Key == $ReleaseType ?" selected='selected'" : "") : '') ?>><?=$Val?></option>
<?			
		}
?>
						</select>
					</td>
				</tr>
				<tr id="formats_tr">
					<td class="label">Allowed Formats</td>
					<td>
						<input type="checkbox" name="all_formats" id="toggle_formats" onchange="Toggle('formats', <?=($NewRequest ? 1 : 0)?>);"<?=(!empty($FormatArray) && (count($FormatArray) == count($Formats)) ? ' checked="checked"' : '')?> /><label for="toggle_formats"> All</label>
						<span style="float: right;"><strong>NB: You cannot require a log or cue unless FLAC is an allowed format</strong></span>
<?		foreach ($Formats as $Key => $Val) {
			if($Key % 8 == 0) echo "<br />";?>
						<input type="checkbox" name="formats[]" value="<?=$Key?>" onchange="ToggleLogCue(); if(!this.checked) { $('#toggle_formats').raw().checked = false; }" id="format_<?=$Key?>"
							<?=(!empty($FormatArray) && in_array($Key, $FormatArray) ? ' checked="checked" ' : '')?>
						/><label for="format_<?=$Key?>"> <?=$Val?></label>
<?		}?>
					</td>
				</tr>
				<tr id="bitrates_tr">
					<td class="label">Allowed Bitrates</td>
					<td>
						<input type="checkbox" name="all_bitrates" id="toggle_bitrates" onchange="Toggle('bitrates', <?=($NewRequest ? 1 : 0)?>);"<?=(!empty($BitrateArray) && (count($BitrateArray) == count($Bitrates)) ? ' checked="checked"' : '')?> /><label for="toggle_bitrates"> All</label>
<?		foreach ($Bitrates as $Key => $Val) {
			if($Key % 8 == 0) echo "<br />";?>
						<input type="checkbox" name="bitrates[]" value="<?=$Key?>" id="bitrate_<?=$Key?>" 
							<?=(!empty($BitrateArray) && in_array($Key, $BitrateArray) ? ' checked="checked" ' : '')?>
						onchange="if(!this.checked) { $('#toggle_bitrates').raw().checked = false; }"/><label for="bitrate_<?=$Key?>"> <?=$Val?></label>
<?		}?>
					</td>
				</tr>
				<tr id="media_tr">
					<td class="label">Allowed Media</td>
					<td>
						<input type="checkbox" name="all_media" id="toggle_media" onchange="Toggle('media', <?=($NewRequest ? 1 : 0)?>);"<?=(!empty($MediaArray) && (count($MediaArray) == count($Media)) ? ' checked="checked"' : '')?> /><label for="toggle_media"> All</label>
<?		foreach ($Media as $Key => $Val) { 
			if($Key % 8 == 0) echo "<br />";?>	
						<input type="checkbox" name="media[]" value="<?=$Key?>" id="media_<?=$Key?>" 
							<?=(!empty($MediaArray) && in_array($Key, $MediaArray) ? ' checked="checked" ' : '')?>
						onchange="if(!this.checked) { $('#toggle_media').raw().checked = false; }"/><label for="media_<?=$Key?>"> <?=$Val?></label>
<?		}?>
					</td>
				</tr>
				<tr id="logcue_tr" class="hidden">
					<td class="label">Log / Cue (FLAC only)</td>
					<td>
						<input type="checkbox" id="needlog" name="needlog" onchange="ToggleLogScore()" <?=(!empty($NeedLog) ? 'checked="checked" ' : '')?>/><label for="needlog"> Require Log</label>
						<span id="minlogscore_span" class="hidden">&nbsp;<input type="text" name="minlogscore" id="minlogscore"  size="4" value="<?=(!empty($MinLogScore) ? $MinLogScore : '')?>"/> Minimum Log Score</span>
						<br />
						<input type="checkbox" id="needcue" name="needcue" <?=(!empty($NeedCue) ? 'checked="checked" ' : '')?>/><label for="needcue"> Require Cue</label>
						<br />
					</td>
				</tr>
<?	} ?>
				<tr>
					<td class="label">Description</td>
					<td>
						<textarea name="description" cols="70" rows="7"><?=(!empty($Description) ? $Description : '')?></textarea> <br />
					</td>
				</tr>
<?	if($NewRequest) { ?>
				<tr id="voting">
					<td class="label">Bounty (MB)</td>
					<td>
						<input type="text" id="amount_box" size="8" value="<?=(!empty($Bounty) ? $Bounty : '100')?>" onchange="Calculate();" />
						<select id="unit" name="unit" onchange="Calculate();">
							<option value='mb'<?=(!empty($_POST['unit']) && $_POST['unit'] == 'mb' ? ' selected="selected"' : '') ?>>MB</option>
							<option value='gb'<?=(!empty($_POST['unit']) && $_POST['unit'] == 'gb' ? ' selected="selected"' : '') ?>>GB</option>
						</select>
						<input type="button" value="Preview" onclick="Calculate();"/>
						<strong><?=($RequestTax * 100)?>% of this is deducted as tax by the system.</strong>
					</td>
				</tr>
				<tr>
					<td class="label">Post request information</td>
					<td>
						<input type="hidden" id="amount" name="amount" value="<?=(!empty($Bounty) ? $Bounty : '100')?>" />
						<input type="hidden" id="current_uploaded" value="<?=$LoggedUser['BytesUploaded']?>" />
						<input type="hidden" id="current_downloaded" value="<?=$LoggedUser['BytesDownloaded']?>" />
						If you add the entered <strong><span id="new_bounty">100.00 MB</span></strong> of bounty, your new stats will be: <br/>
						Uploaded: <span id="new_uploaded"><?=get_size($LoggedUser['BytesUploaded'])?></span>
						Ratio: <span id="new_ratio"><?=ratio($LoggedUser['BytesUploaded'],$LoggedUser['BytesDownloaded'])?></span>
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
		<script type="text/javascript" >ToggleLogCue(); <?=$NewRequest ? "Calculate();" : '' ?></script>
		<script type="text/javascript">Categories();</script>
	</div>
</div>
<?
show_footer(); 
?>
