<?

/********************************************************************************
 ************ Torrent form class *************** upload.php and torrents.php ****
 ********************************************************************************
 ** This class is used to create both the upload form, and the 'edit torrent'  **
 ** form. It is broken down into several functions - head(), foot(),           **
 ** music_form() [music], audiobook_form() [Audiobooks and comedy], and	       **
 ** simple_form() [everything else].                                           **
 **                                                                            **
 ** When it is called from the edit page, the forms are shortened quite a bit. **
 **                                                                            **
 ********************************************************************************/

class TORRENT_FORM {
	var $UploadForm = '';
	var $Categories = array();
	var $Formats = array();
	var $Bitrates = array();
	var $Media = array();
	var $NewTorrent = false;
	var $Torrent = array();
	var $Error = false;
	var $TorrentID = false;
	var $Disabled = '';
	var $DisabledFlag = false;

	function TORRENT_FORM($Torrent = false, $Error = false, $NewTorrent = true) {

		$this->NewTorrent = $NewTorrent;
		$this->Torrent = $Torrent;
		$this->Error = $Error;

		global $UploadForm, $Categories, $Formats, $Bitrates, $Media, $TorrentID;

		$this->UploadForm = $UploadForm;
		$this->Categories = $Categories;
		$this->Formats = $Formats;
		$this->Bitrates = $Bitrates;
		$this->Media = $Media;
		$this->TorrentID = $TorrentID;

		if ($this->Torrent && $this->Torrent['GroupID']) {
			$this->Disabled = ' disabled="disabled"';
			$this->DisabledFlag = true;
		}
	}

	function head() {
?>

<div class="thin">
<?		if ($this->NewTorrent) { ?>
	<p style="text-align: center;">
		Your personal announce URL is:<br />
		<input type="text" value="<?= ANNOUNCE_URL . '/' . G::$LoggedUser['torrent_pass'] . '/announce'?>" size="71" onclick="this.select();" readonly="readonly" />
	</p>
<?		}
		if ($this->Error) {
			echo "\t".'<p style="color: red; text-align: center;">'.$this->Error."</p>\n";
		}
?>
	<form class="create_form" name="torrent" action="" enctype="multipart/form-data" method="post" id="upload_table" onsubmit="$('#post').raw().disabled = 'disabled';">
		<div>
			<input type="hidden" name="submit" value="true" />
			<input type="hidden" name="auth" value="<?=G::$LoggedUser['AuthKey']?>" />
<?		if (!$this->NewTorrent) { ?>
			<input type="hidden" name="action" value="takeedit" />
			<input type="hidden" name="torrentid" value="<?=display_str($this->TorrentID)?>" />
			<input type="hidden" name="type" value="<?=display_str($this->Torrent['CategoryID'])?>" />
<?
		} else {
			if ($this->Torrent && $this->Torrent['GroupID']) {
?>
			<input type="hidden" name="groupid" value="<?=display_str($this->Torrent['GroupID'])?>" />
			<input type="hidden" name="type" value="<?=array_search($this->UploadForm, $this->Categories)?>" />
<?
			}
			if ($this->Torrent && $this->Torrent['RequestID']) {
?>
			<input type="hidden" name="requestid" value="<?=display_str($this->Torrent['RequestID'])?>" />
<?
			}
		}
?>
		</div>
<?		if ($this->NewTorrent) { ?>
		<table cellpadding="3" cellspacing="1" border="0" class="layout border" width="100%">
			<tr>
				<td class="label">Torrent file:</td>
				<td><input id="file" type="file" name="file_input" size="50" /></td>
			</tr>
			<tr>
				<td class="label">Type:</td>
				<td>
					<select id="categories" name="type" onchange="Categories()"<?=$this->Disabled?>>
<?
			foreach (Misc::display_array($this->Categories) as $Index => $Cat) {
				echo "\t\t\t\t\t\t<option value=\"$Index\"";
				if ($Cat == $this->Torrent['CategoryName']) {
					echo ' selected="selected"';
				}
				echo ">$Cat</option>\n";
			}
?>
					</select>
				</td>
			</tr>
		</table>
<?		}//if ?>
		<div id="dynamic_form">
<?
	} // function head


	function foot() {
		$Torrent = $this->Torrent;
?>
		</div>
		<table cellpadding="3" cellspacing="1" border="0" class="layout border slice" width="100%">
<?
		if (!$this->NewTorrent) {
			if (check_perms('torrents_freeleech')) {
?>
			<tr id="freetorrent">
				<td class="label">Freeleech</td>
				<td>
					<select name="freeleech">
<?
				$FL = array("Normal", "Free", "Neutral");
				foreach ($FL as $Key => $Name) {
?>
						<option value="<?=$Key?>"<?=($Key == $Torrent['FreeTorrent'] ? ' selected="selected"' : '')?>><?=$Name?></option>
<?				} ?>
					</select>
					because
					<select name="freeleechtype">
<?
				$FL = array("N/A", "Staff Pick", "Perma-FL", "Vanity House");
				foreach ($FL as $Key => $Name) {
?>
						<option value="<?=$Key?>"<?=($Key == $Torrent['FreeLeechType'] ? ' selected="selected"' : '')?>><?=$Name?></option>
<?				} ?>
					</select>
				</td>
			</tr>
<?
			}
		}
?>
			<tr>
				<td colspan="2" style="text-align: center;">
					<p>Be sure that your torrent is approved by the <a href="rules.php?p=upload" target="_blank">rules</a>. Not doing this will result in a <strong class="important_text">warning</strong> or <strong class="important_text">worse</strong>.</p>
<?		if ($this->NewTorrent) { ?>
					<p>After uploading the torrent, you will have a one hour grace period during which no one other than you can fill requests with this torrent. Make use of this time wisely, and <a href="requests.php">search the list of requests</a>.</p>
<?		} ?>
					<input id="post" type="submit"<? if ($this->NewTorrent) { echo ' value="Upload torrent"'; } else { echo ' value="Edit torrent"';} ?> />
				</td>
			</tr>
		</table>
	</form>
</div>
<?
	} //function foot


	function music_form($GenreTags) {
		$QueryID = G::$DB->get_query_id();
		$Torrent = $this->Torrent;
		$IsRemaster = !empty($Torrent['Remastered']);
		$UnknownRelease = !$this->NewTorrent && $IsRemaster && !$Torrent['RemasterYear'];

		if ($Torrent['GroupID']) {
			G::$DB->query('
				SELECT
					ID,
					RemasterYear,
					RemasterTitle,
					RemasterRecordLabel,
					RemasterCatalogueNumber
				FROM torrents
				WHERE GroupID = '.$Torrent['GroupID']."
					AND Remastered = '1'
					AND RemasterYear != 0
				ORDER BY RemasterYear DESC,
					RemasterTitle DESC,
					RemasterRecordLabel DESC,
					RemasterCatalogueNumber DESC");

			if (G::$DB->has_results()) {
				$GroupRemasters = G::$DB->to_array(false, MYSQLI_BOTH, false);
			}
		}

		$HasLog = $Torrent['HasLog'];
		$HasCue = $Torrent['HasCue'];
		$BadTags = $Torrent['BadTags'];
		$BadFolders = $Torrent['BadFolders'];
		$BadFiles = $Torrent['BadFiles'];
		$CassetteApproved = $Torrent['CassetteApproved'];
		$LossymasterApproved = $Torrent['LossymasterApproved'];
		$LossywebApproved = $Torrent['LossywebApproved'];
		global $ReleaseTypes;
?>
		<table cellpadding="3" cellspacing="1" border="0" class="layout border<? if ($this->NewTorrent) { echo ' slice'; } ?>" width="100%">
<?		if ($this->NewTorrent) { ?>
			<tr id="artist_tr">
			<td class="label">Artist(s):</td>
			<td id="artistfields">
				<p id="vawarning" class="hidden">Please use the multiple artists feature rather than adding "Various Artists" as an artist; read <a href="wiki.php?action=article&amp;id=369" target="_blank">this</a> for more information.</p>
<?
			if (!empty($Torrent['Artists'])) {
				$FirstArtist = true;
				foreach ($Torrent['Artists'] as $Importance => $Artists) {
					foreach ($Artists as $Artist) {
?>
					<input type="text" id="artist" name="artists[]" size="45" value="<?=display_str($Artist['name']) ?>" onblur="CheckVA();"<? Users::has_autocomplete_enabled('other'); ?><?=$this->Disabled?> />
					<select id="importance" name="importance[]"<?=$this->Disabled?>>
						<option value="1"<?=($Importance == '1' ? ' selected="selected"' : '')?>>Main</option>
						<option value="2"<?=($Importance == '2' ? ' selected="selected"' : '')?>>Guest</option>
						<option value="4"<?=($Importance == '4' ? ' selected="selected"' : '')?>>Composer</option>
						<option value="5"<?=($Importance == '5' ? ' selected="selected"' : '')?>>Conductor</option>
						<option value="6"<?=($Importance == '6' ? ' selected="selected"' : '')?>>DJ / Compiler</option>
						<option value="3"<?=($Importance == '3' ? ' selected="selected"' : '')?>>Remixer</option>
						<option value="7"<?=($Importance == '7' ? ' selected="selected"' : '')?>>Producer</option>
					</select>
<?
						if ($FirstArtist) {
							if (!$this->DisabledFlag) {
?>
					<a href="javascript:AddArtistField()" class="brackets">+</a> <a href="javascript:RemoveArtistField()" class="brackets">&minus;</a>
<?
							}
							$FirstArtist = false;
						}
?>
					<br />
<?
					}
				}
			} else {
?>
					<input type="text" id="artist" name="artists[]" size="45" onblur="CheckVA();"<? Users::has_autocomplete_enabled('other'); ?><?=$this->Disabled?> />
					<select id="importance" name="importance[]"<?=$this->Disabled?>>
						<option value="1">Main</option>
						<option value="2">Guest</option>
						<option value="4">Composer</option>
						<option value="5">Conductor</option>
						<option value="6">DJ / Compiler</option>
						<option value="3">Remixer</option>
						<option value="7">Producer</option>
					</select>
					<a href="#" onclick="AddArtistField(); return false;" class="brackets">+</a> <a href="#" onclick="RemoveArtistField(); return false;" class="brackets">&minus;</a>
<?			} ?>
				</td>
			</tr>
			<tr id="title_tr">
				<td class="label">Album title:</td>
				<td>
					<input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title'])?>"<?=$this->Disabled?> />
					<p class="min_padding">Do not include the words remaster, re-issue, MFSL Gold, limited edition, bonus tracks, bonus disc or country-specific information in this field. That belongs in the edition information fields below; see <a href="wiki.php?action=article&amp;id=159" target="_blank">this</a> for further information. Also remember to use the correct capitalization for your upload. See the <a href="wiki.php?action=article&amp;id=317" target="_blank">Capitalization Guidelines</a> for more information.</p>
				</td>
			</tr>
			<tr id="musicbrainz_tr">
				<td class="label tooltip" title="Click the &quot;Find Info&quot; button to automatically fill out parts of the upload form by selecting an entry in MusicBrainz">MusicBrainz:</td>
				<td><input type="button" value="Find Info" id="musicbrainz_button" /></td>
			</tr>
			<div id="musicbrainz_popup">
				<a href="#null" id="popup_close">x</a>
				<h1 id="popup_title"></h1>
				<h2 id="popup_back"></h2>
				<div id="results1"></div>
				<div id="results2"></div>
			</div>
			<div id="popup_background"></div>

<script type="text/javascript">
//<![CDATA[
hide();
if (document.getElementById("categories").disabled == false) {
	if (navigator.appName == 'Opera') {
		var useragent = navigator.userAgent;
		var match = useragent.split('Version/');
		var version = parseFloat(match[1]);
		if (version >= 12.00) {
			show();
		}
	} else if (navigator.appName != 'Microsoft Internet Explorer') {
		show();
	}
}

function hide() {
	document.getElementById("musicbrainz_tr").style.display = "none";
	document.getElementById("musicbrainz_popup").style.display = "none";
	document.getElementById("popup_background").style.display = "none";
}

function show() {
	document.getElementById("musicbrainz_tr").style.display = "";
	document.getElementById("musicbrainz_popup").style.display = "";
	document.getElementById("popup_background").style.display = "";
}
//]]>
</script>

			<tr id="year_tr">
				<td class="label">
					<span id="year_label_not_remaster"<? if ($IsRemaster) { echo ' class="hidden"';} ?>>Year:</span>
					<span id="year_label_remaster"<? if (!$IsRemaster) { echo ' class="hidden"';} ?>>Year of original release:</span>
				</td>
				<td>
					<p id="yearwarning" class="hidden">You have entered a year for a release which predates the medium's availability. You will need to change the year and enter additional edition information. If this information cannot be provided, check the &quot;Unknown Release&quot; check box below.</p>
					<input type="text" id="year" name="year" size="5" value="<?=display_str($Torrent['Year']) ?>"<?=$this->Disabled?> onblur="CheckYear();" /> This is the year of the original release.
				</td>
			</tr>
			<tr id="label_tr">
				<td class="label">Record label (optional):</td>
				<td><input type="text" id="record_label" name="record_label" size="40" value="<?=display_str($Torrent['RecordLabel']) ?>"<?=$this->Disabled?> /></td>
			</tr>
			<tr id="catalogue_tr">
				<td class="label">Catalogue number (optional):</td>
				<td>
					<input type="text" id="catalogue_number" name="catalogue_number" size="40" value="<?=display_str($Torrent['CatalogueNumber']) ?>"<?=$this->Disabled?> />
					<br />
					Please double-check the record label and catalogue number when using MusicBrainz. See <a href="wiki.php?action=article&amp;id=688" target="_blank">this guide</a> for more details.
				</td>
			</tr>
			<tr id="releasetype_tr">
				<td class="label">
					<span id="releasetype_label">Release type:</span>
				</td>
				<td>
					<select id="releasetype" name="releasetype"<?=$this->Disabled?>>
						<option>---</option>
<?
			foreach ($ReleaseTypes as $Key => $Val) {
				echo "\t\t\t\t\t\t<option value=\"$Key\"";
				if ($Key == $Torrent['ReleaseType']) {
					echo ' selected="selected"';
				}
				echo ">$Val</option>\n";
			}
?>
					</select> Please take the time to fill this out properly. Need help? Try reading <a href="wiki.php?action=article&amp;id=202" target="_blank">this wiki article</a> or searching <a href="https://musicbrainz.org/search" target="_blank">MusicBrainz</a>.
				</td>
			</tr>
<?		} ?>
			<tr>
				<td class="label">Edition information:</td>
				<td>
					<input type="checkbox" id="remaster" name="remaster"<? if ($IsRemaster) { echo ' checked="checked"'; } ?> onclick="Remaster();<? if ($this->NewTorrent) { ?> CheckYear();<? } ?>" />
					<label for="remaster">Check this box if this torrent is a different release to the original, for example a limited or country specific edition or a release that includes additional bonus tracks or is a bonus disc.</label>
					<div id="remaster_true"<? if (!$IsRemaster) { echo ' class="hidden"';} ?>>
<?	if (check_perms('edit_unknowns') || G::$LoggedUser['ID'] == $Torrent['UserID']) { ?>
						<br />
						<input type="checkbox" id="unknown" name="unknown"<? if ($UnknownRelease) { echo ' checked="checked"'; } ?> onclick="<? if ($this->NewTorrent) { ?>CheckYear(); <? } ?>ToggleUnknown();" /> <label for="unknown">Unknown Release</label>
<?	} ?>
						<br /><br />
<?	if (!empty($GroupRemasters)) { ?>
						<input type="hidden" id="json_remasters" value="<?=display_str(json_encode($GroupRemasters))?>" />
						<select id="groupremasters" name="groupremasters" onchange="GroupRemaster()"<? if ($UnknownRelease) { echo ' disabled="disabled"'; } ?>>
							<option value="">-------</option>
<?
	$LastLine = '';

	foreach ($GroupRemasters as $Index => $Remaster) {
		$Line = $Remaster['RemasterYear'].' / '.$Remaster['RemasterTitle'].' / '.$Remaster['RemasterRecordLabel'].' / '.$Remaster['RemasterCatalogueNumber'];
		if ($Line != $LastLine) {
			$LastLine = $Line;
?>
							<option value="<?=$Index?>"<?=(($Remaster['ID'] == $this->TorrentID) ? ' selected="selected"' : '')?>><?=$Line?></option>
<?
		}
	}
?>
						</select>
						<br />
<?	} ?>
						<table id="edition_information" class="layout border" border="0" width="100%">
							<tbody>
								<tr id="edition_year">
									<td class="label">Year (required):</td>
									<td>
										<input type="text" id="remaster_year" name="remaster_year" size="5" value="<? if ($Torrent['RemasterYear']) { echo display_str($Torrent['RemasterYear']); } ?>"<? if ($UnknownRelease) { echo ' disabled="disabled"';} ?> />
									</td>
								</tr>
								<tr id="edition_title">
									<td class="label">Title:</td>
									<td>
										<input type="text" id="remaster_title" name="remaster_title" size="50" value="<?=display_str($Torrent['RemasterTitle']) ?>"<? if ($UnknownRelease) { echo ' disabled="disabled"';} ?> />
										<p class="min_padding">Title of the release (e.g. <span style="font-style: italic;">"Deluxe Edition" or "Remastered"</span>).</p>
									</td>
								</tr>
								<tr id="edition_record_label">
									<td class="label">Record label:</td>
									<td>
										<input type="text" id="remaster_record_label" name="remaster_record_label" size="50" value="<?=display_str($Torrent['RemasterRecordLabel']) ?>"<? if ($UnknownRelease) { echo ' disabled="disabled"';} ?> />
										<p class="min_padding">This is for the record label of the <strong>release</strong>. It may differ from the original.</p>
									</td>
								</tr>
								<tr id="edition_catalogue_number">
									<td class="label">Catalogue number:</td>
									<td><input type="text" id="remaster_catalogue_number" name="remaster_catalogue_number" size="50" value="<?=display_str($Torrent['RemasterCatalogueNumber']) ?>"<? if ($UnknownRelease) { echo ' disabled="disabled"';} ?> />
										<p class="min_padding">This is for the catalogue number of the <strong>release</strong>.</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</td>
			</tr>
			<tr>
				<td class="label">Scene:</td>
				<td>
					<input type="checkbox" id="scene" name="scene" <? if ($Torrent['Scene']) { echo 'checked="checked" ';} ?>/>
					<label for="scene">Select this only if this is a "scene release".<br />If you ripped it yourself, it is <strong>not</strong> a scene release. If you are not sure, <strong class="important_text">do not</strong> select it; you will be penalized. For information on the scene, visit <a href="https://en.wikipedia.org/wiki/Warez_scene" target="_blank">Wikipedia</a>.</label>
				</td>
			</tr>
			<tr>
				<td class="label">Format:</td>
				<td>
					<select id="format" name="format" onchange="Format()">
						<option>---</option>
<?
		foreach (Misc::display_array($this->Formats) as $Format) {
			echo "\t\t\t\t\t\t<option value=\"$Format\"";
			if ($Format == $Torrent['Format']) {
				echo ' selected="selected"';
			}
			echo ">$Format</option>\n";
			// <option value="$Format" selected="selected">$Format</option>
		}
?>
					</select>
				<span id="format_warning" class="important_text"></span>
				</td>
			</tr>
			<tr id="bitrate_row">
				<td class="label">Bitrate:</td>
				<td>
					<select id="bitrate" name="bitrate" onchange="Bitrate()">
						<option value="">---</option>
<?
		if ($Torrent['Bitrate'] && !in_array($Torrent['Bitrate'], $this->Bitrates)) {
			$OtherBitrate = true;
			if (substr($Torrent['Bitrate'], strlen($Torrent['Bitrate']) - strlen(' (VBR)')) == ' (VBR)') {
				$Torrent['Bitrate'] = substr($Torrent['Bitrate'], 0, strlen($Torrent['Bitrate']) - 6);
				$VBR = true;
			}
		} else {
			$OtherBitrate = false;
		}

		// See if they're the same bitrate
		// We have to do this screwery because '(' is a regex character.
		$SimpleBitrate = explode(' ', $Torrent['Bitrate']);
		$SimpleBitrate = $SimpleBitrate[0];


		foreach (Misc::display_array($this->Bitrates) as $Bitrate) {
			echo "\t\t\t\t\t\t<option value=\"$Bitrate\"";
			if (($SimpleBitrate && preg_match('/^'.$SimpleBitrate.'.*/', $Bitrate)) || ($OtherBitrate && $Bitrate == 'Other')) {
				echo ' selected="selected"';
			}
			echo ">$Bitrate</option>\n";
		}
?>
					</select>
					<span id="other_bitrate_span"<? if (!$OtherBitrate) { echo ' class="hidden"'; } ?>>
						<input type="text" name="other_bitrate" size="5" id="other_bitrate"<? if ($OtherBitrate) { echo ' value="'.display_str($Torrent['Bitrate']).'"';} ?> onchange="AltBitrate();" />
						<input type="checkbox" id="vbr" name="vbr"<? if (isset($VBR)) { echo ' checked="checked"'; } ?> /><label for="vbr"> (VBR)</label>
					</span>
				</td>
			</tr>
<?		if ($this->NewTorrent) { ?>
			<tr id="upload_logs" class="hidden">
				<td class="label">
					Log files:
				</td>
				<td id="logfields">
					Check your log files before uploading <a href="logchecker.php" target="_blank">here</a>. For multi-disc releases, click the "<span class="brackets">+</span>" button to add multiple log files.<br />
					<input id="file" type="file" multiple="multiple" name="logfiles[]" size="50" /> <a href="javascript:;" onclick="AddLogField();" class="brackets">+</a> <a href="javascript:;" onclick="RemoveLogField();" class="brackets">&minus;</a>
				</td>
			</tr>
<?
		}
		if ($this->NewTorrent) { ?>
		<tr>
			<td class="label">Multi-format uploader:</td>
			<td><input type="button" value="+" id="add_format" /><input type="button" style="display: none;" value="-" id="remove_format" /></td>
		</tr>
		<tr id="placeholder_row_top"></tr>
		<tr id="placeholder_row_bottom"></tr>
<?
		}
		if (check_perms('torrents_edit_vanityhouse') && $this->NewTorrent) {
?>
			<tr>
				<td class="label">Vanity House:</td>
				<td>
					<label><input type="checkbox" id="vanity_house" name="vanity_house"<? if ($Torrent['GroupID']) { echo ' disabled="disabled"'; } ?><? if ($Torrent['VanityHouse']) { echo ' checked="checked"';} ?> />
					Check this only if you are submitting your own work or submitting on behalf of the artist, and this is intended to be a Vanity House release. Checking this will also automatically add the group as a recommendation.
					</label>
				</td>
			</tr>
<?		} ?>
			<tr>
				<td class="label">Media:</td>
				<td>
					<select name="media" onchange="CheckYear();" id="media">
						<option>---</option>
<?
		foreach ($this->Media as $Media) {
			echo "\t\t\t\t\t\t<option value=\"$Media\"";
			if (isset($Torrent['Media']) && $Media == $Torrent['Media']) {
				echo ' selected="selected"';
			}
			echo ">$Media</option>\n";
		}
?>
					</select>
				</td>
			</tr>
<?		if (!$this->NewTorrent && check_perms('users_mod')) { ?>
			<tr>
				<td class="label">Log/cue:</td>
				<td>
					<input type="checkbox" id="flac_log" name="flac_log"<? if ($HasLog) { echo ' checked="checked"';} ?> /> <label for="flac_log">Check this box if the torrent has, or should have, a log file.</label><br />
					<input type="checkbox" id="flac_cue" name="flac_cue"<? if ($HasCue) { echo ' checked="checked"';} ?> /> <label for="flac_cue">Check this box if the torrent has, or should have, a cue file.</label><br />
<?
		}
		if ((check_perms('users_mod') || G::$LoggedUser['ID'] == $Torrent['UserID']) && ($Torrent['LogScore'] == 100 || $Torrent['LogScore'] == 99)) {

			G::$DB->query('
				SELECT LogID
				FROM torrents_logs_new
				WHERE TorrentID = '.$this->TorrentID."
					AND Log LIKE 'EAC extraction logfile%'
					AND (Adjusted = '0' OR Adjusted = '')");
			list($LogID) = G::$DB->next_record();
			if ($LogID) {
				if (!check_perms('users_mod')) {
?>
			<tr>
				<td class="label">Trumpable:</td>
				<td>
<?				} ?>
					<input type="checkbox" id="make_trumpable" name="make_trumpable"<? if ($Torrent['LogScore'] == 99) { echo ' checked="checked"';} ?> /> <label for="make_trumpable">Check this box if you want this torrent to be trumpable (subtracts 1 point).</label>
<?				if (!check_perms('users_mod')) { ?>
				</td>
			</tr>
<?
				}
			}
		}
		if (!$this->NewTorrent && check_perms('users_mod')) {
?>
				</td>
			</tr>
<?/*			if ($HasLog) { ?>
			<tr>
				<td class="label">Log score</td>
				<td><input type="text" name="log_score" size="5" id="log_score" value="<?=display_str($Torrent['LogScore']) ?>" /></td>
			</tr>
			<tr>
				<td class="label">Log adjustment reason</td>
				<td>
					<textarea name="adjustment_reason" id="adjustment_reason" cols="60" rows="8"><?=display_str($Torrent['AdjustmentReason']); ?></textarea>
					<p class="min_padding">Contains reason for adjusting a score. <strong>This field is displayed on the torrent page.</strong></p>
				</td>
			</tr>
<?			}*/?>
			<tr>
				<td class="label">Bad tags:</td>
				<td><input type="checkbox" id="bad_tags" name="bad_tags"<? if ($BadTags) { echo ' checked="checked"';} ?> /> <label for="bad_tags">Check this box if the torrent has bad tags.</label></td>
			</tr>
			<tr>
				<td class="label">Bad folder names:</td>
				<td><input type="checkbox" id="bad_folders" name="bad_folders"<? if ($BadFolders) { echo ' checked="checked"';} ?> /> <label for="bad_folders">Check this box if the torrent has bad folder names.</label></td>
			</tr>
			<tr>
				<td class="label">Bad file names:</td>
				<td><input type="checkbox" id="bad_files" name="bad_files"<? if ($BadFiles) {echo ' checked="checked"';} ?> /> <label for="bad_files">Check this box if the torrent has bad file names.</label></td>
			</tr>
			<tr>
				<td class="label">Cassette approved:</td>
				<td><input type="checkbox" id="cassette_approved" name="cassette_approved"<? if ($CassetteApproved) {echo ' checked="checked"';} ?> /> <label for="cassette_approved">Check this box if the torrent is an approved cassette rip.</label></td>
			</tr>
			<tr>
				<td class="label">Lossy master approved:</td>
				<td><input type="checkbox" id="lossymaster_approved" name="lossymaster_approved"<? if ($LossymasterApproved) {echo ' checked="checked"';} ?> /> <label for="lossymaster_approved">Check this box if the torrent is an approved lossy master.</label></td>
			</tr>
			<tr>
				<td class="label">Lossy web approved:</td>
				<td><input type="checkbox" id="lossyweb_approved" name="lossyweb_approved"<? if ($LossywebApproved) { echo ' checked="checked"';} ?> /> <label for="lossyweb_approved">Check this box if the torrent is an approved lossy WEB release.</label></td>
			</tr>
<?
		}
		if ($this->NewTorrent) {
?>
			<tr>
				<td class="label">Tags:</td>
				<td>
<?			if ($GenreTags) { ?>
					<select id="genre_tags" name="genre_tags" onchange="add_tag(); return false;"<?=$this->Disabled?>>
						<option>---</option>
<?				foreach (Misc::display_array($GenreTags) as $Genre) { ?>
						<option value="<?=$Genre?>"><?=$Genre?></option>
<?				} ?>
					</select>
<?			} ?>
					<input type="text" id="tags" name="tags" size="40" value="<?=display_str($Torrent['TagList']) ?>"<? Users::has_autocomplete_enabled('other'); ?><?=$this->Disabled?> />
					<br />
<? Rules::display_site_tag_rules(true); ?>
				</td>
			</tr>
			<tr>
				<td class="label">Image (optional):</td>
				<td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image']) ?>"<?=$this->Disabled?> /></td>
			</tr>
			<tr>
				<td class="label">Album description:</td>
				<td>
<?php new TEXTAREA_PREVIEW('album_desc', 'album_desc', display_str($Torrent['GroupDescription']), 60, 8, true, true, false, array($this->Disabled)); ?>
					<p class="min_padding">Contains background information such as album history and maybe a review.</p>
				</td>
			</tr>
<?		} // if new torrent ?>
			<tr>
				<td class="label">Release description (optional):</td>
				<td>
<?php new TEXTAREA_PREVIEW('release_desc', 'release_desc', display_str($Torrent['TorrentDescription']), 60, 8); ?>
					<p class="min_padding">Contains information like encoder settings or details of the ripping process. <strong class="important_text">Do not paste the ripping log here.</strong></p>
				</td>
			</tr>
		</table>
<?
		//	For AJAX requests (e.g. when changing the type from Music to Applications),
		//	we don't need to include all scripts, but we do need to include the code
		//	that generates previews. It will have to be eval'd after an AJAX request.
		if ($_SERVER['SCRIPT_NAME'] === '/ajax.php')
			TEXTAREA_PREVIEW::JavaScript(false);

		G::$DB->set_query_id($QueryID);
	}//function music_form


	function audiobook_form() {
		$Torrent = $this->Torrent;
?>
		<table cellpadding="3" cellspacing="1" border="0" class="layout border slice" width="100%">
<?		if ($this->NewTorrent) { ?>
			<tr id="title_tr">
				<td class="label">Author - Title:</td>
				<td>
					<input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title']) ?>" />
					<p class="min_padding">Should only include the author if applicable.</p>
				</td>
			</tr>
<?		} ?>
			<tr id="year_tr">
				<td class="label">Year:</td>
				<td><input type="text" id="year" name="year" size="5" value="<?=display_str($Torrent['Year']) ?>" /></td>
			</tr>
			<tr>
				<td class="label">Format:</td>
				<td>
					<select name="format" onchange="Format()">
						<option value="">---</option>
<?
		foreach (Misc::display_array($this->Formats) as $Format) {
			echo "\t\t\t\t\t\t<option value=\"$Format\"";
			if ($Format == $Torrent['Format']) {
				echo ' selected="selected"';
			}
			echo '>';
			echo $Format;
			echo "</option>\n";
		}
?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Bitrate:</td>
				<td>
					<select id="bitrate" name="bitrate" onchange="Bitrate()">
						<option value="">---</option>
<?
		if (!$Torrent['Bitrate'] || ($Torrent['Bitrate'] && !in_array($Torrent['Bitrate'], $this->Bitrates))) {
			$OtherBitrate = true;
			if (substr($Torrent['Bitrate'], strlen($Torrent['Bitrate']) - strlen(' (VBR)')) == ' (VBR)') {
				$Torrent['Bitrate'] = substr($Torrent['Bitrate'], 0, strlen($Torrent['Bitrate']) - 6);
				$VBR = true;
			}
		} else {
			$OtherBitrate = false;
		}
		foreach (Misc::display_array($this->Bitrates) as $Bitrate) {
			echo "\t\t\t\t\t\t<option value=\"$Bitrate\"";
			if ($Bitrate == $Torrent['Bitrate'] || ($OtherBitrate && $Bitrate == 'Other')) {
				echo ' selected="selected"';
			}
			echo '>';
			echo $Bitrate;
			echo "</option>\n";
		}
?>
					</select>
					<span id="other_bitrate_span"<? if (!$OtherBitrate) { echo ' class="hidden"'; } ?>>
						<input type="text" name="other_bitrate" size="5" id="other_bitrate"<? if ($OtherBitrate) { echo ' value="'.display_str($Torrent['Bitrate']).'"';} ?> onchange="AltBitrate()" />
						<input type="checkbox" id="vbr" name="vbr"<? if (isset($VBR)) { echo ' checked="checked"'; } ?> /><label for="vbr"> (VBR)</label>
					</span>
				</td>
			</tr>
<?		if ($this->NewTorrent) { ?>
			<tr>
				<td class="label">Tags:</td>
				<td>
					<input type="text" id="tags" name="tags" size="60" value="<?=display_str($Torrent['TagList']) ?>"<? Users::has_autocomplete_enabled('other'); ?> />
				</td>
			</tr>
			<tr>
				<td class="label">Image (optional):</td>
				<td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image']) ?>"<?=$this->Disabled?> /></td>
			</tr>
			<tr>
				<td class="label">Description:</td>
				<td>
<?php new TEXTAREA_PREVIEW('album_desc', 'album_desc', display_str($Torrent['GroupDescription']), 60, 8); ?>
					<p class="min_padding">Contains information like the track listing, a review, a link to Discogs or MusicBrainz, etc.</p>
				</td>
			</tr>
<?		} ?>
			<tr>
				<td class="label">Release description (optional):</td>
				<td>
<?php new TEXTAREA_PREVIEW('release_desc', 'release_desc', display_str($Torrent['TorrentDescription']), 60, 8); ?>
					<p class="min_padding">Contains information like encoder settings. For analog rips, this frequently contains lineage information.</p>
				</td>
			</tr>
		</table>
<?
		TEXTAREA_PREVIEW::JavaScript(false);
	}//function audiobook_form


	function simple_form($CategoryID) {
		$Torrent = $this->Torrent;
?>		<table cellpadding="3" cellspacing="1" border="0" class="layout border slice" width="100%">
			<tr id="name">
<?		if ($this->NewTorrent) {
			if ($this->Categories[$CategoryID] == 'E-Books') { ?>
				<td class="label">Author - Title:</td>
<?			} else { ?>
				<td class="label">Title:</td>
<?			} ?>
				<td><input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title']) ?>" /></td>
			</tr>
			<tr>
				<td class="label">Tags:</td>
				<td><input type="text" id="tags" name="tags" size="60" value="<?=display_str($Torrent['TagList']) ?>"<? Users::has_autocomplete_enabled('other'); ?> /></td>
			</tr>
			<tr>
				<td class="label">Image (optional):</td>
				<td><input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image']) ?>"<?=$this->Disabled?> /></td>
			</tr>
			<tr>
				<td class="label">Description:</td>
				<td>
<?php
	new TEXTAREA_PREVIEW('desc', 'desc', display_str($Torrent['GroupDescription']), 60, 8);
	TEXTAREA_PREVIEW::JavaScript(false);
?>
				</td>
			</tr>
<?		} ?>
		</table>
<?	}//function simple_form
}//class
?>
