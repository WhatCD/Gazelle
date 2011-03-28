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
	var $Categories = array();
	var $Formats = array();
	var $Bitrates = array();
	var $Media = array();
	var $NewTorrent = false;
	var $Torrent = array();
	var $Error = false;
	var $TorrentID = false;
	var $Disabled = '';
	
	function TORRENT_FORM($Torrent = false, $Error = false, $NewTorrent = true) {
		
		$this->NewTorrent = $NewTorrent;
		$this->Torrent = $Torrent;
		$this->Error = $Error;
		
		global $Categories, $Formats, $Bitrates, $Media, $TorrentID;
		
		$this->Categories = $Categories;
		$this->Formats = $Formats;
		$this->Bitrates = $Bitrates;
		$this->Media = $Media;
		$this->TorrentID = $TorrentID;
		
		if($this->Torrent && $this->Torrent['GroupID']) {
			$this->Disabled = ' disabled="disabled"';
		}
	}


	function head() {
		global $LoggedUser;
?>
<div class="thin">
<?		if($this->NewTorrent) { ?>
	<p style="text-align: center;">
		Your personal announce url is:<br />
		<input type="text" value="<?= ANNOUNCE_URL.'/'.$LoggedUser['torrent_pass'].'/announce'?>" size="71" onfocus="this.select()" />
	</p>
<?		}
		if($this->Error) {
			echo '<p style="color: red;text-align:center;">'.$this->Error.'</p>';
		}
?>
	<form action="" enctype="multipart/form-data" method="post" id="upload_table" onsubmit="$('#post').raw().disabled = 'disabled'">
		<div>
			<input type="hidden" name="submit" value="true" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
<?		if(!$this->NewTorrent) { ?>
			<input type="hidden" name="action" value="takeedit" />
			<input type="hidden" name="torrentid" value="<?=display_str($this->TorrentID)?>" />
			<input type="hidden" name="type" value="<?=display_str($this->Torrent['CategoryID'])?>" />
<?		} else if($this->Torrent && $this->Torrent['GroupID']) { ?>
			<input type="hidden" name="groupid" value="<?=display_str($this->Torrent['GroupID'])?>" />
			<input type="hidden" name="type" value="Music" />
<?		} else if($this->Torrent && $this->Torrent['RequestID']) { ?>
			<input type="hidden" name="requestid" value="<?=display_str($this->Torrent['RequestID'])?>" />
<?		} ?>
		</div>
<?		if($this->NewTorrent) { ?>
		<table cellpadding="3" cellspacing='1' border='0' class='border' width="100%">
			<tr>
				<td class="label">
					Torrent file
				</td>
				<td>
					<input id="file" type="file" name="file_input" size="50" />
				</td>
			</tr>
			<tr>
				<td class="label">
					Type
				</td>
				<td>
				<select id="categories" name="type" onchange="Categories()"<?=$this->Disabled?>>
<?			foreach(display_array($this->Categories) as $Index => $Cat) {
				echo "<option value='$Index'";
				if($Cat == $this->Torrent['CategoryName']) { echo " selected='selected'"; }
				echo ">";
				echo $Cat;
				echo "</option>\n";
			} 
?>
				</select>
				</td>
			</tr>
		</table>
<?		}//if ?>
		<div id="dynamic_form">
<?	} // function head

	
	function foot() {
		$Torrent = $this->Torrent;
?>
		</div>
	
		<table cellpadding="3" cellspacing="1" border="0" class="border slice" width="100%">
<?		if(!$this->NewTorrent) {
			if(check_perms('torrents_freeleech')) {
?>
			<tr id="freetorrent">
				<td class="label">Freeleech</td>
				<td>
					<input type="checkbox" name="freeleech"<?=$Torrent['FreeTorrent'] ? ' checked="checked"' : ''?> /> Checking this box will make the torrent freeleech. Don't abuse it.
				</td>
			</tr>

			<tr id="grace">
				<td class="label">Extended Grace</td>
				<td>
					<input type="checkbox" name="extendedgrace"<?=$Torrent['ExtendedGrace']? ' checked="checked"' : ''?> /> Checking this box extendes the grace period for this torrent.
				</td>
			</tr>
<?
			}
		}
?>
			<tr>
				<td colspan="2" style="text-align: center;">
					<p>Be sure that your torrent is approved by the <a href="rules.php?p=upload">rules</a>. Not doing this will result in a <strong>warning</strong> or <strong>worse</strong>.</p>
<?		if($this->NewTorrent) { ?>
					<p>After uploading the torrent, you will have a one hour grace period during which no one other than you can fill requests with this torrent. Make use of this time wisely, and search the requests. </p>
<?		} ?>
					<input id="post" type="submit" <? if($this->NewTorrent) { echo "value=\"Upload torrent\""; } else { echo "value=\"Edit torrent\"";} ?> />
				</td>
			</tr>
		</table>
	</form>
</div>
<?	} //function foot
	
	
	function music_form($GenreTags) {
		$Torrent = $this->Torrent;
		$IsRemaster = !empty($Torrent['Remastered']);
		$UnknownRelease = !$this->NewTorrent && $IsRemaster && !$Torrent['RemasterYear'];
		
		if($Torrent['GroupID']) {
			global $DB;
			$DB->query("SELECT ID,
							RemasterYear,
							RemasterTitle, 
							RemasterRecordLabel, 
							RemasterCatalogueNumber 
						FROM torrents 
						WHERE GroupID = ".$Torrent['GroupID']." 
							AND Remastered = '1' 
							AND RemasterYear != 0
						ORDER BY RemasterYear DESC, 
							RemasterTitle DESC, 
							RemasterRecordLabel DESC, 
							RemasterCatalogueNumber DESC");
			
			if($DB->record_count() > 0) {
				$GroupRemasters = $DB->to_array(false, MYSQLI_BOTH, false);
			}
		}
		
		$HasLog = $Torrent['HasLog'];
		$HasCue = $Torrent['HasCue'];
		$BadTags = $Torrent['BadTags'];
		$BadFolders = $Torrent['BadFolders'];
		$BadFiles = $Torrent['BadFiles'];
		global $ReleaseTypes;
?>
		<table cellpadding="3" cellspacing="1" border="0" class="border<? if($this->NewTorrent) { echo ' slice'; }?>" width="100%">
<?		if($this->NewTorrent) { ?>
			<tr id="artist_tr">
			<td class="label">Artist(s)</td>		
			<td id="artistfields">
				<p id="vawarning" class="hidden">Please use the multiple artists feature rather than adding 'Various Artists' as an artist, read <a href='wiki.php?action=article&amp;id=369'>this</a> for more information on why.</p>
<?			if(!empty($Torrent['Artists'])) {
				foreach($Torrent['Artists'] as $Importance => $Artists) {
					foreach($Artists as $Artist) {
?>
					<input type="text" id="artist" name="artists[]" size="45" value="<?=display_str($Artist['name']) ?>" onblur="CheckVA();" <?=$this->Disabled?>/>
					<select id="importance" name="importance[]" <?=$this->Disabled?>>
							<option value="1"<?=($Importance == '1' ? ' selected="selected"' : '')?>>Main</option>
							<option value="2"<?=($Importance == '2' ? ' selected="selected"' : '')?>>Guest</option>
							<option value="3"<?=($Importance == '3' ? ' selected="selected"' : '')?>>Remixer</option>
					</select>
					<br />
<?					}
				}
			} else {
?>
					<input type="text" id="artist" name="artists[]" size="45" onblur="CheckVA();"<?=$this->Disabled?>/>
					<select id="importance" name="importance[]" <?=$this->Disabled?>>
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
			<tr id="title_tr">
				<td class="label">Album title:</td>
				<td>
					<input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title']) ?>"<?=$this->Disabled?>/>
					<p class="min_padding">Do not include the words remaster, re-issue, MSFL Gold, limited edition, bonus tracks, bonus disc or country specific information in this field. That belongs in the edition information fields below, see <a href="wiki.php?action=article&amp;id=159">this</a> for further information. Also remember to use the correct capitalization for your upload. See the <a href="wiki.php?action=article&amp;id=317">Capitalization Guidelines</a> for more information.
				</td>
			</tr>
			<tr id="year_tr">
				<td class="label">
					<span id="year_label_not_remaster"<? if($IsRemaster) { echo ' class="hidden"';}?>>Year</span>
					<span id="year_label_remaster"<? if(!$IsRemaster) { echo ' class="hidden"';}?>>Year of original release</span>
				</td>
				<td>
					<p id="yearwarning" class="hidden">You have entered a year for a release which predates the medium's availibility. You will need to change the year, enter additional edition information or if this information cannot be provided, select the 'Unknown Release' checkbox below</p>
					<input type="text" id="year" name="year" size="5" value="<?=display_str($Torrent['Year']) ?>"<?=$this->Disabled?> onblur="CheckYear();" /> This is the year of the original release.
				</td>
			</tr>
			<tr id="label_tr">
				<td class="label">Record Label (Optional):</td>
				<td>
					<input type="text" id="record_label" name="record_label" size="40" value="<?=display_str($Torrent['RecordLabel']) ?>"<?=$this->Disabled?> />
				</td>
			</tr>
			<tr id="catalogue_tr">
				<td class="label">Catalogue Number (Optional):</td>
				<td>
					<input type="text" id="catalogue_number" name="catalogue_number" size="40" value="<?=display_str($Torrent['CatalogueNumber']) ?>"<?=$this->Disabled?> />
				</td>
			</tr>
			<tr id="releasetype_tr">
				<td class="label">
					<span id="releasetype_label">Release type</span>
				</td>
				<td>
					<select id="releasetype" name="releasetype"<?=$this->Disabled?>>
						<option>---</option>
<?
			
			foreach ($ReleaseTypes as $Key => $Val) {
				echo "<option value='$Key'";
				if($Key == $Torrent['ReleaseType']) { echo " selected='selected'"; }
				echo ">";
				echo $Val;
				echo "</option>\n";
			}

?>
					</select> Please take the time to fill this out properly (try searching <a href="http://musicbrainz.org/search.html">musicbrainz</a>).
				</td>
			</tr>
<?		} ?>
			<tr>
				<td class="label">Edition information</td>
				<td>
					<input type="checkbox" id="remaster" name="remaster"<? if($IsRemaster) { echo " checked='checked' ";}?> onclick="Remaster();<?if($this->NewTorrent) {?> CheckYear();<? } ?>" />
					Check this box if this torrent is a different release to the original, for example a limited or country specific edition or a release that includes additional bonus tracks or is a bonus disc.
					<div id="remaster_true"<? if(!$IsRemaster) { echo ' class="hidden"';}?>>
<? if(check_perms('edit_unknowns') || $LoggedUser['ID'] == $Torrent['UserID']) { ?>
						<br />
						<input type="checkbox" id="unknown" name="unknown"<? if($UnknownRelease) { echo " checked='checked' ";}?> onclick="<?if($this->NewTorrent) {?> CheckYear();<? } ?>ToggleUnknown();"/> Unknown Release
<? } ?>
						<br /><br />
<? if(!empty($GroupRemasters)) { ?>
						<input type="hidden" id="json_remasters" value="<?=display_str(json_encode($GroupRemasters))?>" />
						<select id="groupremasters" name="groupremasters" onchange="GroupRemaster()"<? if($UnknownRelease) { echo " disabled";}?>>
							<option value="">-------</option>
<?
	$LastLine = "";
	
	foreach($GroupRemasters as $Index => $Remaster) {
		$Line = $Remaster['RemasterYear']." / ".$Remaster['RemasterTitle']." / ".$Remaster['RemasterRecordLabel']." / ".$Remaster['RemasterCatalogueNumber'];
		if($Line != $LastLine) {
			$LastLine = $Line;
				
?>
							<option value="<?=$Index?>"<?=($Remaster['ID'] == $this->TorrentID) ? ' selected="selected"' : ''?>><?=$Line?></option>
<?
		}
	}
?>
						</select>
						<br />
<?	} ?>
						<br />
						<strong>Year (Required):</strong> 
						<input type="text" id="remaster_year" name="remaster_year" size="5" value="<? if($Torrent['RemasterYear']) { echo display_str($Torrent['RemasterYear']);} ?>"<? if($UnknownRelease) { echo " disabled";}?> /> <br />
						<strong>Title:</strong>
						<input type="text" id="remaster_title" name="remaster_title" size="50" value="<?=display_str($Torrent['RemasterTitle']) ?>"<? if($UnknownRelease) { echo " disabled";}?> />
						<p class="min_padding">Title of the release, eg. <i>'Deluxe Edition' or 'Remastered'</i>.</p>
						<strong>Record Label:</strong>
						<input type="text" id="remaster_record_label" name="remaster_record_label" size="50" value="<?=display_str($Torrent['RemasterRecordLabel']) ?>"<? if($UnknownRelease) { echo " disabled";}?> />
						<p class="min_padding">This is for the record label of the <strong>release</strong> (It may differ from the original).</p>
						<strong>Catalogue Number:</strong>
						<input type="text" id="remaster_catalogue_number" name="remaster_catalogue_number" size="50" value="<?=display_str($Torrent['RemasterCatalogueNumber']) ?>"<? if($UnknownRelease) { echo " disabled";}?> />
						<p class="min_padding">This is for the catalogue number of the <strong>release</strong>.</p>
					</div>
				</td>
			</tr> 
			<tr>
				<td class="label">Scene</td>
				<td>
					<input type="checkbox" id="scene" name="scene" <? if($Torrent['Scene']) { echo "checked='checked' ";}?>/>
					Check this only if this is a 'scene release'. If you ripped it yourself, it is <strong>not</strong> a scene release. <br />If you are not sure, <strong>DO NOT</strong> check it, you will be penalized. For information on the scene, visit <a href="http://en.wikipedia.org/wiki/Scene_%28software%29">Wikipedia</a>.
				</td>
			</tr>
			<tr>
				<td class="label">Format</td>
				<td>
					<select id="format" name="format" onchange="Format()">
						<option>---</option>
<?		foreach(display_array($this->Formats) as $Format) {
			echo "<option value='$Format'";
			if($Format == $Torrent['Format']) { echo " selected='selected'"; }
			echo ">";
			echo $Format;
			echo "</option>\n";
			// <option value='$Format' selected='selected'>$Format</option>
		} 
?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Bitrate</td>
				<td>
					<select id="bitrate" name="bitrate" onchange="Bitrate()">
						<option value="">---</option>
<?		if($Torrent['Bitrate'] && !in_array($Torrent['Bitrate'], $this->Bitrates)) {
			$OtherBitrate = true;
			if(substr($Torrent['Bitrate'], strlen($Torrent['Bitrate']) - strlen(" (VBR)")) == " (VBR)") {
				$Torrent['Bitrate'] = substr($Torrent['Bitrate'], 0, strlen($Torrent['Bitrate'])-6);
				$VBR = true;
			}
		} else {
			$OtherBitrate = false;
		}
		
		// See if they're the same bitrate
		// We have to do this screwery because '(' is a regex character. 
		$SimpleBitrate = explode(' ', $Torrent['Bitrate']);
		$SimpleBitrate = $SimpleBitrate[0];
		
		
		foreach(display_array($this->Bitrates) as $Bitrate) {
			echo "<option value='$Bitrate'";
			if(($SimpleBitrate && preg_match('/^'.$SimpleBitrate.'.*/', $Bitrate)) || ($OtherBitrate && $Bitrate == "Other")) {
				echo ' selected="selected"';
			}
			echo ">";
			echo $Bitrate;
			echo "</option>\n";
		} ?>
					</select>
					<span id="other_bitrate_span"<? if(!$OtherBitrate) { echo ' class="hidden"'; } ?>>
						<input type="text" name="other_bitrate" size="5" id="other_bitrate"<? if($OtherBitrate) { echo " value='".display_str($Torrent['Bitrate'])."'";} ?> onchange="AltBitrate()" />
						<input type="checkbox" id="vbr" name="vbr"<? if(isset($VBR)) { echo ' checked="checked"'; } ?> /> (VBR)
					</span>
				</td>
			</tr>
			<tr>
				<td class="label">Media</td>
				<td>
					<select name="media" onchange="Media(); CheckYear();" id="media">
						<option>---</option>
<?		foreach($this->Media as $Media) {
			echo "<option value='$Media'";
			if(isset($Torrent['Media']) && $Media == $Torrent['Media']) { echo " selected='selected'"; }
			echo ">";
			echo $Media;
			echo "</option>\n";
		} 
?>
					</select>
					<span id="cassette_true" class="hidden"><strong style="color:red;">Do NOT upload a cassette rip without first getting approval from a moderator!</strong></span>
				</td>
			</tr>
<?
		if($this->NewTorrent) { ?>
			<tr id="upload_logs" class="hidden">
				<td class="label">
					Log Files
				</td>
				<td id="logfields">
					Check your log files here before uploading: <a href="logchecker.php">logchecker.php</a><br />
					<input id="file" type="file" name="logfiles[]" size="50" /> [<a href="javascript:;" onclick="AddLogField();">+</a>] [<a href="javascript:;" onclick="RemoveLogField();">-</a>]
				</td>
			</tr>
<?
		} ?>
<?		if(!$this->NewTorrent && check_perms('users_mod')) { ?>
			<tr>
				<td class="label">Log/Cue</td>
				<td>
					<input type="checkbox" id="flac_log" name="flac_log"<? if($HasLog) { echo " checked='checked'";}?>/> Check this box if the torrent has (or should have) a log file.<br />
					<input type="checkbox" id="flac_cue" name="flac_cue"<? if($HasCue) { echo " checked='checked'";}?>/> Check this box if the torrent has (or should have) a cue file.<br />
<?
		}
		global $LoggedUser;
		if ((check_perms('users_mod') || $LoggedUser['ID'] == $Torrent['UserID']) && ($Torrent['LogScore'] == 100 || $Torrent['LogScore'] == 99)) {

			$DB->query("SELECT LogID FROM torrents_logs_new where TorrentID = ".$this->TorrentID." AND Log LIKE 'EAC extraction logfile%' AND (Adjusted = '0' OR Adjusted = '')");
			list($LogID) = $DB->next_record();
			if ($LogID) {
				if (!check_perms('users_mod')) {
?>				
					<tr>
						<td class="label">Trumpable</td>
						<td>
<?
				}
?>
							<input type="checkbox" id="make_trumpable" name="make_trumpable"<? if ($Torrent['LogScore'] == 99) { echo " checked='checked'";}?>/>Check this box if you want this torrent to be trumpable (subtracts 1 point).
<?			
				if (!check_perms('users_mod')) {
?>						</td>
					</tr>
<?
				}
			}
		} 
		if(!$this->NewTorrent && check_perms('users_mod')) {?>
				</td>
			</tr>
<?/*			if($HasLog) { ?>
			<tr>
				<td class="label">Log Score</td>
				<td>
					<input type="text" name="log_score" size="5" id="log_score" value="<?=display_str($Torrent['LogScore']) ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">Log Adjustment Reason</td>
				<td>
					<textarea name="adjustment_reason" id="adjustment_reason" cols="60" rows="8"><?=display_str($Torrent['AdjustmentReason']); ?></textarea>
					<p class="min_padding">Contains reason for adjusting a score. <b>This field is displayed on the torrent page</b>.</p> 
				</td>
			</tr>
<?			}*/?>
			<tr>
				<td class="label">Bad Tags</td>
				<td>
					<input type="checkbox" id="bad_tags" name="bad_tags"<? if($BadTags) { echo " checked='checked'";}?>/> Check this box if the torrent has bad tags.
				</td>
			</tr>
			<tr>
				<td class="label">Bad Folder Names</td>
				<td>
					<input type="checkbox" id="bad_folders" name="bad_folders"<? if($BadFolders) { echo " checked='checked'";}?>/> Check this box if the torrent has bad folder names.
				</td>
			</tr>
			<tr>
				<td class="label">Bad File Names</td>
				<td>
					<input type="checkbox" id="bad_files" name="bad_files"<? if ($BadFiles) {echo " checked='checked'";}?>/> Check this box if the torrent has bad file names.
				</td>
			</tr>
<?		} ?>
<?		 if($this->NewTorrent) { ?> 
			<tr>
				<td class="label">Tags</td>
				<td>
<?			if($GenreTags) { ?>
					<select id="genre_tags" name="genre_tags" onchange="add_tag();return false;" <?=$this->Disabled?>>
						<option>---</option>
<?				foreach(display_array($GenreTags) as $Genre) { ?>
						<option value="<?=$Genre ?>"><?=$Genre ?></option>
<?				} ?>
					</select>
<?			} ?> 
					<input type="text" id="tags" name="tags" size="40" value="<?=display_str($Torrent['TagList']) ?>" <?=$this->Disabled?>/>
					<br />
					Tags should be comma separated, and you should use a period ('.') to separate words inside a tag - eg. '<strong style="color:green;">hip.hop</strong>'. 
					<br /><br />
					There is a list of official tags to the left of the text box. Please use these tags instead of 'unofficial' tags (eg. use the official '<strong style="color:green;">drum.and.bass</strong>' tag, instead of an unofficial '<strong style="color:red;">dnb</strong>' tag.)
					<br /><br />
					Avoid abbreviations if at all possible. So instead of tagging an album as '<strong style="color:red;">alt</strong>', tag it as '<strong style="color:green;">alternative</strong>'. Make sure that you use correct spelling. 
					<br /><br />
					Avoid using multiple synonymous tags. Using both '<strong style="color:red;">prog.rock</strong>' and '<strong style="color:green;">progressive.rock</strong>' is redundant and annoying - just use the official '<strong style="color:green;">progressive.rock</strong>' tag. 
					<br /><br />
					Don't use 'useless' tags, such as '<strong style="color:red;">seen.live</strong>', '<strong style="color:red;">awesome</strong>', '<strong style="color:red;">rap</strong>' (is encompassed by '<strong style="color:green;">hip.hop</strong>'), etc. If an album is live, you can tag it as '<strong style="color:green;">live</strong>'. 
					<br /><br />
					Only tag information on the album itself - NOT THE INDIVIDUAL RELEASE. Tags such as '<strong style="color:red;">v0</strong>', '<strong style="color:red;">eac</strong>', '<strong style="color:red;">vinyl</strong>', '<strong style="color:red;">from.oink</strong>' etc are strictly forbidden. Remember that these tags will be used for other versions of the same album. 
					<br /><br />
					<strong>You should be able to build up a list of tags using only the official tags to the left of the text box. If you are in any doubt about whether or not a tag is acceptable, do not add it.</strong>
				</td>
			</tr>
			<tr>
				<td class="label">Image (optional)</td>
				<td>
					<input type="text" id="image" name="image" size="60" value="<?=display_str($Torrent['Image']) ?>" <?=$this->Disabled?>/>
				</td>
			</tr>
			<tr>
				<td class="label">Album Description</td>
				<td>
					<textarea name="album_desc" id="album_desc" cols="60" rows="8" <?=$this->Disabled?>><?=display_str($Torrent['GroupDescription']); ?></textarea>
					<p class="min_padding">Contains background information such as album history and maybe a review.</p> 
				</td>
			</tr>
<?		} // if new torrent ?> 
			<tr>
				<td class="label">Release Description (optional)</td>
				<td>
					<textarea name="release_desc" id="release_desc" cols="60" rows="8"><?=display_str($Torrent['TorrentDescription']); ?></textarea>
					<p class="min_padding">Contains information like encoder settings or details of the ripping process. <b>DO NOT PASTE THE RIPPING LOG HERE.</b></p>
				</td>
			</tr>
		</table>
<?
	}//function music_form

	


	function audiobook_form() { 
		$Torrent = $this->Torrent;
?>
		<table cellpadding="3" cellspacing="1" border="0" class="border slice" width="100%">
<?		if($this->NewTorrent){ ?>
			<tr id="title_tr">
				<td class="label">Artist - Title</td>
				<td>
					<input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title']) ?>" />
					<p class="min_padding">Should only include the artist if applicable</p>
				</td>
			</tr>
<?		} ?>
			<tr id="year_tr">
				<td class="label">Year</td>
				<td>
					<input type="text" id="year" name="year" size="5" value="<?=display_str($Torrent['Year']) ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">Format</td>
				<td>
					<select name="format" onchange="Format()">
						<option value="">---</option>
<?
		foreach(display_array($this->Formats) as $Format) {
			echo "<option value='$Format'";
			if($Format == $Torrent['Format']) { echo " selected='selected'"; }
			echo ">";
			echo $Format;
			echo "</option>\n";
		} 
?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Bitrate</td>
				<td>
					<select id="bitrate" name="bitrate" onchange="Bitrate()">
						<option value="">---</option>
<?		if($Torrent['Bitrate'] && !in_array($Torrent['Bitrate'], $this->Bitrates)) {
			$OtherBitrate = true;
		} else {
			$OtherBitrate = false;
		}
		foreach(display_array($this->Bitrates) as $Bitrate) {
			echo "<option value='$Bitrate'";
			if($Bitrate == $Torrent['Bitrate']) { 
				echo " selected='selected'";
			}
			echo ">";
			echo $Bitrate;
			echo "</option>\n";
		}
?> 
					</select>
					<span id="other_bitrate_span"<? if(!$OtherBitrate) { echo ' class="hidden"'; } ?> >
						<input type="text" name="other_bitrate" size="5" id="other_bitrate"<? if($OtherBitrate) { echo " value='".display_str($Torrent['Encoding'])."'";} ?> onchange="AltBitrate()" />
<? 		if($this->NewTorrent) {  ?>
						<input type="checkbox" id="vbr" name="vbr" /> (VBR)
<?		} ?>
					</span>
				</td>
			</tr>
<?		if($this->NewTorrent) { ?> 
			<tr>
				<td class="label">Tags</td>
				<td>
					<input type="text" id="tags" name="tags" size="60" value="<?=display_str($Torrent['TagList']) ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">Description</td>
				<td>
					<textarea name="album_desc" id="album_desc" cols="60" rows="8"><?=display_str($Torrent['GroupDescription']); ?></textarea>
					<p class="min_padding">Contains information like the track listing, and maybe a review.</p>
				</td>
			</tr>
<?		}?> 
			<tr>
				<td class="label">Release Description (optional)</td>
				<td>
					<textarea name="release_desc" id="release_desc" cols="60" rows="8"><?=display_str($Torrent['TorrentDescription']); ?></textarea>
					<p class="min_padding">Contains information like encoder settings, and/or a log of the ripping process.</p>
				</td>
			</tr>
		</table>
<?
	}//function audiobook_form



	

	function simple_form($CategoryID) {
		$Torrent = $this->Torrent; 
?>		<table cellpadding="3" cellspacing="1" border="0" class="border slice" width="100%">
			<tr id="name">
<?				if ($this->NewTorrent) { 
					if ($this->Categories[$CategoryID] == 'E-Books') { ?>
						<td class="label">Author - Title</td>
<?					} else { ?>
						<td class="label">Title</td>
<?					} 
?>
				<td>
					<input type="text" id="title" name="title" size="60" value="<?=display_str($Torrent['Title']) ?>" />
				</td>
				</tr>
				<tr>
					<td class="label">Tags</td>
					<td>
						<input type="text" id="tags" name="tags" size="60" value="<?=display_str($Torrent['TagList']) ?>" />
					</td>
				</tr>
				<tr>
					<td class="label">Description</td>
					<td>
						<textarea name="desc" id="desc" cols="60" rows="8"><?=display_str($Torrent['GroupDescription']); ?></textarea>
					</td>
				</tr>
<?				} ?>

		</table>
<?	}//function simple_form
}//class
?>
