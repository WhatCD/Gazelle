<?

$UserID = $_REQUEST['userid'];
if(!is_number($UserID)){
	error(404);
}



$DB->query("SELECT 
			m.Username,
			m.Email,
			m.IRCKey,
			m.Paranoia,
			i.Info,
			i.Avatar,
			i.Country,
			i.StyleID,
			i.StyleURL,
			i.SiteOptions,
			i.UnseededAlerts,
			p.Level AS Class
			FROM users_main AS m
			JOIN users_info AS i ON i.UserID = m.ID
			LEFT JOIN permissions AS p ON p.ID=m.PermissionID
			WHERE m.ID = '".db_string($UserID)."'");
list($Username,$Email,$IRCKey,$Paranoia,$Info,$Avatar,$Country,$StyleID,$StyleURL,$SiteOptions,$UnseededAlerts,$Class)=$DB->next_record(MYSQLI_NUM, array(3,9));


if($UserID != $LoggedUser['ID'] && !check_perms('users_edit_profiles', $Class)) {
	error(403);
}

$Paranoia = unserialize($Paranoia);
if(!is_array($Paranoia)) { 
	$Paranoia = array(); 
}

function paranoia_level($Setting) {
       global $Paranoia;
       // 0: very paranoid; 1: stats allowed, list disallowed; 2: not paranoid
       return (in_array($Setting . '+', $Paranoia)) ? 0 : (in_array($Setting, $Paranoia) ? 1 : 2);
}

function display_paranoia($FieldName) {
       $Level = paranoia_level($FieldName);
       print '<label><input type="checkbox" name="p_'.$FieldName.'_c" '.checked($Level >= 1).' onChange="AlterParanoia()" /> Show count</label>'."&nbsp;&nbsp;\n";
       print '<label><input type="checkbox" name="p_'.$FieldName.'_l" '.checked($Level >= 2).' onChange="AlterParanoia()" /> Show list</label>';
}

function checked($Checked) {
	return $Checked ? 'checked="checked"' : '';
}

$DB->query("SELECT COUNT(x.uid) FROM xbt_snatched AS x INNER JOIN torrents AS t ON t.ID=x.fid WHERE x.uid='$UserID'");
list($Snatched) = $DB->next_record();

if ($SiteOptions) { 
	$SiteOptions = unserialize($SiteOptions); 
} else { 
	$SiteOptions = array();
}

show_header($Username.' > Settings','user,validate');
echo $Val->GenerateJS('userform');
?>
<div class="thin">
	<h2><?=format_username($UserID,$Username)?> &gt; Settings</h2>
	<form id="userform" name="userform" action="" method="post" onsubmit="return formVal();" autocomplete="off">
		<div>
			<input type="hidden" name="action" value="takeedit" />
			<input type="hidden" name="userid" value="<?=$UserID?>" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		</div>
		<table cellpadding='6' cellspacing='1' border='0' width='100%' class='border'>
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Site preferences</strong>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Stylesheet</strong></td>
				<td>
					<select name="stylesheet" id="stylesheet">
<? foreach($Stylesheets as $Style) { ?>
						<option value="<?=$Style['ID']?>"<? if ($Style['ID'] == $StyleID) { ?>selected="selected"<? } ?>><?=$Style['ProperName']?></option>
<? } ?>
					</select>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Or -&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					External CSS: <input type="text" size="40" name="styleurl" id="styleurl" value="<?=display_str($StyleURL)?>" />
				</td>
			</tr>
<? if (check_perms('site_advanced_search')) { ?>
			<tr>
				<td class="label"><strong>Default Search Type</strong></td>
				<td>
					<select name="searchtype" id="searchtype">
						<option value="0"<? if ($SiteOptions['SearchType'] == 0) { ?>selected="selected"<? } ?>>Simple</option>
						<option value="1"<? if ($SiteOptions['SearchType'] == 1) { ?>selected="selected"<? } ?>>Advanced</option>
					</select>
				</td>
			</tr>
<? } ?>
			<tr>
				<td class="label"><strong>Torrent Grouping</strong></td>
				<td>
					<select name="disablegrouping" id="disablegrouping">
						<option value="0"<? if ($SiteOptions['DisableGrouping'] == 0) { ?>selected="selected"<? } ?>>Group torrents by default</option>
						<option value="1"<? if ($SiteOptions['DisableGrouping'] == 1) { ?>selected="selected"<? } ?>>DO NOT Group torrents by default</option>
					</select>&nbsp;
					<select name="torrentgrouping" id="torrentgrouping">
						<option value="0"<? if ($SiteOptions['TorrentGrouping'] == 0) { ?>selected="selected"<? } ?>>Groups are open by default</option>
						<option value="1"<? if ($SiteOptions['TorrentGrouping'] == 1) { ?>selected="selected"<? } ?>>Groups are closed by default</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Discography View</strong></td>
				<td>
					<select name="discogview" id="discogview">
						<option value="0"<? if ($SiteOptions['DiscogView'] == 0) { ?>selected="selected"<? } ?>>Open by default</option>
						<option value="1"<? if ($SiteOptions['DiscogView'] == 1) { ?>selected="selected"<? } ?>>Closed by default</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Posts per page (Forum)</strong></td>
				<td>
					<select name="postsperpage" id="postsperpage">
						<option value="25"<? if ($SiteOptions['PostsPerPage'] == 25) { ?>selected="selected"<? } ?>>25 (Default)</option>
						<option value="50"<? if ($SiteOptions['PostsPerPage'] == 50) { ?>selected="selected"<? } ?>>50</option>
						<option value="100"<? if ($SiteOptions['PostsPerPage'] == 100) { ?>selected="selected"<? } ?>>100</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Hide release types</strong></td>
				<td>
					<table style="border:none;">
<?
	$ReleaseTypes[1024] = "Guest Appearance";
	$ReleaseTypes[1023] = "Remixed By";
	$ReleaseTypes[1022] = "Composition";
	for($i = 0; list($Key,$Val) = each($ReleaseTypes); $i++) {
		if(!($i % 7)) {
			if($i) {
?>
						</tr>
<?
			}
?>
						<tr style="border:none;">
<?
		}
		if(!empty($SiteOptions['HideTypes']) && in_array($Key, $SiteOptions['HideTypes'])) {
			$Checked = 'checked="checked" ';
		} else {
			$Checked='';
		}
?>
							<td style="border:none;">
								<label><input type="checkbox" id="hide_type_<?=$Key?>" name="hidetypes[]=" value="<?=$Key?>" <?=$Checked?>/>
								<?=$Val?></label>
							</td>
<?
	}
	if($i % 7) {
?>
							<td style="border:none;" colspan="<?=7 - ($i % 7)?>"></td>
<?
	}
	unset($ReleaseTypes[1023], $ReleaseTypes[1024], $ReleaseTypes[1022]);
?>
						</tr>
					</table>
				</td>
			</tr>
<!--			<tr>
				<td class="label"><strong>Collage album art view</strong></td>
				<td>
					<select name="hidecollage" id="hidecollage">
						<option value="0"<? if ($SiteOptions['HideCollage'] == 0) { ?>selected="selected"<? } ?>>Show album art</option>
						<option value="1"<? if ($SiteOptions['HideCollage'] == 1) { ?>selected="selected"<? } ?>>Hide album art</option>
					</select>
				</td>
			</tr>-->
			<tr>
				<td class="label"><strong>Collage album covers to show per page</strong></td>
				<td>
					<select name="collagecovers" id="collagecovers">
						<option value="10"<? if ($SiteOptions['CollageCovers'] == 10) { ?>selected="selected"<? } ?>>10</option>
						<option value="25"<? if (($SiteOptions['CollageCovers'] == 25) || !isset($SiteOptions['CollageCovers'])) { ?>selected="selected"<? } ?>>25 (default)</option>
						<option value="50"<? if ($SiteOptions['CollageCovers'] == 50) { ?>selected="selected"<? } ?>>50</option>
						<option value="100"<? if ($SiteOptions['CollageCovers'] == 100) { ?>selected="selected"<? } ?>>100</option>
						<option value="1000000"<? if ($SiteOptions['CollageCovers'] == 1000000) { ?>selected="selected"<? } ?>>All</option>
						<option value="0"<? if (($SiteOptions['CollageCovers'] === 0) || (!isset($SiteOptions['CollageCovers']) && $SiteOptions['HideCollage'])) { ?>selected="selected"<? } ?>>None</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Browse Page Tag list</strong></td>
				<td>
					<select name="showtags" id="showtags">
						<option value="1"<? if ($SiteOptions['ShowTags'] == 1) { ?>selected="selected"<? } ?>>Open by default.</option>
						<option value="0"<? if ($SiteOptions['ShowTags'] == 0) { ?>selected="selected"<? } ?>>Closed by default.</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Subscription</strong></td>
				<td>
					<input type="checkbox" name="autosubscribe" id="autosubscribe" <? if (!empty($SiteOptions['AutoSubscribe'])) { ?>checked="checked"<? } ?> />
					<label for="autosubscribe">Subscribe to topics when posting</label>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Smileys</strong></td>
				<td>
					<input type="checkbox" name="disablesmileys" id="disablesmileys" <? if (!empty($SiteOptions['DisableSmileys'])) { ?>checked="checked"<? } ?> />
					<label for="disablesmileys">Disable smileys</label>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Avatars</strong></td>
				<td>
					<input type="checkbox" name="disableavatars" id="disableavatars" <? if (!empty($SiteOptions['DisableAvatars'])) { ?>checked="checked"<? } ?> />
					<label for="disableavatars">Disable avatars</label>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Download torrents as text files</strong></td>
				<td>
					<input type="checkbox" name="downloadalt" id="downloadalt" <? if ($DownloadAlt) { ?>checked="checked"<? } ?> />
					<label for="downloadalt">For users whose ISP block the downloading of torrent files</label>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Unseeded torrent alerts</strong></td>
				<td>
					<input type="checkbox" name="unseededalerts" id="unseededalerts" <?=checked($UnseededAlerts)?> />
					<label for="unseededalerts">Receive a PM alert before your uploads are deleted for being unseeded</label>
				</td>
			</tr>
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>User info</strong>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Avatar URL</strong></td>
				<td>
					<input type="text" size="50" name="avatar" id="avatar" value="<?=display_str($Avatar)?>" />
					<p class="min_padding">Width should be 150 pixels (will be resized if necessary)</p>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Email</strong></td>
				<td><input type="text" size="50" name="email" id="email" value="<?=display_str($Email)?>" />
					<p class="min_padding">If changing this field you must enter your current password in the "Current password" field before saving your changes.</p>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Info</strong></td>
				<td><textarea name="info" cols="50" rows="8"><?=display_str($Info)?></textarea></td>
			</tr>
			<tr>
				<td class="label"><strong>IRCKey</strong></td>
				<td>
					<input type="text" size="50" name="irckey" id="irckey" value="<?=display_str($IRCKey)?>" />
					<p class="min_padding">This field, if set will be used in place of the password in the IRC login.</p>
					<p class="min_padding">Note: This value is stored in plaintext and should not be your password.</p>
					<p class="min_padding">Note: In order to be accepted as correct, your IRCKey must be between 6 and 32 characters.</p>
				</td>
			</tr>
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Paranoia settings</strong>
				</td>
			</tr>
			<tr>
				<td class="label">&nbsp;</td>
				<td>
					<p><span class="warning">Note: Paranoia has nothing to do with your security on this site, the only thing affected by this setting is other users ability to see your site activity and taste in music.</span></p>
					<p>Select the elements <strong>you want to show</strong> on your profile. For example, if you tick "Show count" for "Snatched", users will be able to see that you have snatched <?=number_format($Snatched)?> torrents. If you tick "Show list", they will be able to see the full list of torrents you've snatched.</p>
					<p><span class="warning">Some information will still be available in the site log.</span></p>
				</td>
			</tr>
			<tr>
				<td class="label">Recent activity</td>
				<td>
					<label><input type="checkbox" name="p_lastseen" <?=checked(!in_array('lastseen', $Paranoia))?>> Last seen</label>
				</td>
			</tr>
			<tr>
				<td class="label">Preset</td>
				<td>
					<button type="button" onClick="ParanoiaResetOff()">Show everything</button>
					<button type="button" onClick="ParanoiaResetStats()">Show stats only</button>
					<!--<button type="button" onClick="ParanoiaResetOn()">Show nothing</button>-->
				</td>
			</tr>
			<tr>
				<td class="label">Stats</td>
				<td>
<?
$UploadChecked = checked(!in_array('uploaded', $Paranoia));
$DownloadChecked = checked(!in_array('downloaded', $Paranoia));
$RatioChecked = checked(!in_array('ratio', $Paranoia));
?>
					<label><input type="checkbox" name="p_uploaded" onChange="AlterParanoia()"<?=$UploadChecked?> /> Uploaded</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_downloaded" onChange="AlterParanoia()"<?=$DownloadChecked?> /> Downloaded</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_ratio" onChange="AlterParanoia()"<?=$RatioChecked?> /> Ratio</label>
				</td>
			</tr>
			<tr>
				<td class="label">Torrent comments</td>
				<td>
<? display_paranoia('torrentcomments'); ?>
				</td>
			</tr>
			<tr>
				<td class="label">Collages started</td>
				<td>
<? display_paranoia('collages'); ?>
				</td>
			</tr>
			<tr>
				<td class="label">Collages contributed to</td>
				<td>
<? display_paranoia('collagecontribs'); ?>
				</td>
			</tr>
				<td class="label">Requests filled</td>
				<td>
<?
$RequestsFilledCountChecked = checked(!in_array('requestsfilled_count', $Paranoia));
$RequestsFilledBountyChecked = checked(!in_array('requestsfilled_bounty', $Paranoia));
$RequestsFilledListChecked = checked(!in_array('requestsfilled_list', $Paranoia));
?>
					<label><input type="checkbox" name="p_requestsfilled_count" onChange="AlterParanoia()" <?=$RequestsFilledCountChecked?> /> Show count</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsfilled_bounty" onChange="AlterParanoia()" <?=$RequestsFilledBountyChecked?> /> Show bounty</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsfilled_list" onChange="AlterParanoia()" <?=$RequestsFilledListChecked?> /> Show list</label>
				</td>
			</tr>
				<td class="label">Requests voted</td>
				<td>
<?
$RequestsVotedCountChecked = checked(!in_array('requestsvoted_count', $Paranoia));
$RequestsVotedBountyChecked = checked(!in_array('requestsvoted_bounty', $Paranoia));
$RequestsVotedListChecked = checked(!in_array('requestsvoted_list', $Paranoia));
?>
					<label><input type="checkbox" name="p_requestsvoted_count" onChange="AlterParanoia()" <?=$RequestsVotedCountChecked?> /> Show count</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsvoted_bounty" onChange="AlterParanoia()" <?=$RequestsVotedBountyChecked?> /> Show bounty</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsvoted_list" onChange="AlterParanoia()" <?=$RequestsVotedListChecked?> /> Show list</label>
				</td>
			</tr>
			<tr>
				<td class="label">Uploaded</td>
				<td>
<? display_paranoia('uploads'); ?>
				</td>
			</tr>
			<tr>
				<td class="label">Unique groups</td>
				<td>
<? display_paranoia('uniquegroups'); ?>
				</td>
			</tr>
			<tr>
				<td class="label">"Perfect" FLACs</td>
				<td>
<? display_paranoia('perfectflacs'); ?>
				</td>
			</tr>
			<tr>
				<td class="label">Seeding</td>
				<td>
<? display_paranoia('seeding'); ?>
				</td>
			</tr>
			<tr>
				<td class="label">Leeching</td>
				<td>
<? display_paranoia('leeching'); ?>
				</td>
			</tr>
			<tr>
				<td class="label">Snatched</td>
				<td>
<? display_paranoia('snatched'); ?>
				</td>
			</tr>
			<tr>
				<td class="label">Miscellaneous</td>
				<td>
					<label><input type="checkbox" name="p_requiredratio" <?=checked(!in_array('requiredratio', $Paranoia))?>> Required ratio</label>
<?
$DB->query("SELECT COUNT(UserID) FROM users_info WHERE Inviter='$UserID'");
list($Invited) = $DB->next_record();
?>
					<br /><label><input type="checkbox" name="p_invitedcount" <?=checked(!in_array('invitedcount', $Paranoia))?>> Number of users invited</label>
<?
$DB->query("SELECT COUNT(ta.ArtistID) FROM torrents_artists AS ta WHERE ta.UserID = ".$UserID);
list($ArtistsAdded) = $DB->next_record();
?>
					<br /><label><input type="checkbox" name="p_artistsadded" <?=checked(!in_array('artistsadded', $Paranoia))?>> Number of artists added</label>
				</td>
			</tr>
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Change password</strong>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Current password</strong></td>
				<td><input type="password" size="40" name="cur_pass" id="cur_pass" value="" /></td>
			</tr>
			<tr>
				<td class="label"><strong>New password</strong></td>
				<td><input type="password" size="40" name="new_pass_1" id="new_pass_1" value="" /></td>
			</tr>
			<tr>
				<td class="label"><strong>Re-type new password</strong></td>
				<td><input type="password" size="40" name="new_pass_2" id="new_pass_2" value="" /></td>
			</tr>
			<tr>
				<td class="label"><strong>Reset passkey</strong></td>
				<td>
					<input type="checkbox" name="resetpasskey" />
					<label for="ResetPasskey">Any active torrents must be downloaded again to continue leeching/seeding.</label>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="right">
					<input type="submit" value="Save Profile" />
				</td>
			</tr>
		</table>
	</form>
</div>
<?
show_footer();
?>
