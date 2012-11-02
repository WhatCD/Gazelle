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
       print '<label><input type="checkbox" name="p_'.$FieldName.'_c" '.checked($Level >= 1).' onchange="AlterParanoia()" /> Show count</label>'."&nbsp;&nbsp;\n";
       print '<label><input type="checkbox" name="p_'.$FieldName.'_l" '.checked($Level >= 2).' onchange="AlterParanoia()" /> Show list</label>';
}

function checked($Checked) {
	return $Checked ? 'checked="checked"' : '';
}

if ($SiteOptions) { 
	$SiteOptions = unserialize($SiteOptions); 
} else { 
	$SiteOptions = array();
}

View::show_header($Username.' > Settings','user,jquery,jquery-ui,release_sort,password_validate,validate,push_settings');

$DB->query("SELECT PushService, PushOptions FROM 
    users_push_notifications WHERE UserID = '$LoggedUser[ID]'");

list($PushService, $PushOptions) = $DB->next_record(MYSQLI_NUM, false);

if ($PushOptions) { 
	$PushOptions = unserialize($PushOptions); 
} else { 
	$PushOptions = array();
}
echo $Val->GenerateJS('userform');
?>
<div class="thin">
	<div class="header">
		<h2><?=Users::format_username($UserID, false, false, false)?> &gt; Settings</h2>
	</div>
	<form class="edit_form" name="user" id="userform" action="" method="post" onsubmit="return formVal();" autocomplete="off">
		<div>
			<input type="hidden" name="action" value="takeedit" />
			<input type="hidden" name="userid" value="<?=$UserID?>" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		</div>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border">
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
						<option value="0"<? if ($SiteOptions['DisableGrouping2'] == 0) { ?>selected="selected"<? } ?>>Group torrents by default</option>
						<option value="1"<? if ($SiteOptions['DisableGrouping2'] == 1) { ?>selected="selected"<? } ?>>DO NOT Group torrents by default</option>
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
				<td class="label"><strong>Show Snatched Torrents</strong></td>
				<td>
					<input type="checkbox" name="showsnatched" id="showsnatched" <? if (!empty($SiteOptions['ShowSnatched'])) { ?>checked="checked"<? } ?> />
					<label for="showsnatched">"Snatched!" next to snatched torrents</label>
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
				<td class="label"><strong>Sort/Hide release types</strong></td>
				<td>
					<table class="layout" style="border:none;">
<?
	$ReleaseTypes[1024] = "Guest Appearance";
	$ReleaseTypes[1023] = "Remixed By";
	$ReleaseTypes[1022] = "Composition";
	$ReleaseTypes[1021] = "Produced By";
?>
    
	<a href="#" id="toggle_sortable" onclick="return false;">Expand</a>
	<div id="sortable_container" style="display: none;">
	<a href="#" id="reset_sortable" onclick="return false;">Reset to Default</a>
	<ul class="sortable_list" id="sortable">

<?
//Generate list of release types for sorting and hiding. 
//If statement is in place because on the first usage user will not have 'SortHide' set in $SiteOptions 
if(empty($SiteOptions['SortHide'])) {
	for($i = 0; list($Key,$Val) = each($ReleaseTypes); $i++) {
		if(!empty($SiteOptions['HideTypes']) && in_array($Key, $SiteOptions['HideTypes'])) {
			$Checked = 'checked="checked" ';
		} else {
			$Checked='';
		}
?>
		<li class="sortable_item"><input type="checkbox" <?=$Checked?> 
			id="<?=$Key."_".($Checked == 'checked="checked" ' ? 1 : 0)?>"><?=$Val?></li>
<?	} 
}
else {
	for($i = 0; list($Key,$Val) = each($SiteOptions['SortHide']); $i++) {
		if($Val == true) {
			$Checked = 'checked="checked" ';
		} else {
			$Checked='';
		}
		if(array_key_exists($Key, $ReleaseTypes)) {
			$Name = $ReleaseTypes[$Key];
		}
		else {
			$Name = "Error";
		}
	?>
		<li class="sortable_item"><input type="checkbox" <?=$Checked?> 
			id="<?=$Key."_".($Checked == 'checked="checked" ' ? 1 : 0)?>"><?=$Name?></li>
<?	} 
}
?>
		</ul>
	</div>
	<input type="hidden" id="sorthide" name="sorthide" value=""/>
		
<?
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
				<td class="label"><strong>Quote Notifications</strong></td>
				<td>
					<input type="checkbox" name="notifyquotes" id="notifyquotes" <? if (!empty($SiteOptions['NotifyOnQuote'])) { ?>checked="checked"<? } ?> />
					<label for="notifyquotes">Notifications when somebody quotes you in the forum</label>
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
                    <select name="disableavatars" id="disableavatars" onclick="ToggleIdenticons();"> 
                        <option value="1" <? if($SiteOptions['DisableAvatars'] == 1) { ?> selected="selected" <? } ?>/>Disable avatars</option>
                        <option value="0" <? if($SiteOptions['DisableAvatars'] == 0) { ?> selected="selected" <? } ?>/>Show avatars</option>
                        <option value="2" <? if($SiteOptions['DisableAvatars'] == 2) { ?> selected="selected" <? } ?>/>Show avatars or:</option>
                        <option value="3" <? if($SiteOptions['DisableAvatars'] == 3) { ?> selected="selected" <? } ?>/>Replace all avatars with:</option>
                    </select>
                    <select name="identicons" id="identicons">
                        <option value="0" <? if($SiteOptions['Identicons'] == 0) { ?> selected="selected" <? } ?>/>Identicon</option>
                        <option value="1" <? if($SiteOptions['Identicons'] == 1) { ?> selected="selected" <? } ?>/>MonsterID</option>
                        <option value="2" <? if($SiteOptions['Identicons'] == 2) { ?> selected="selected" <? } ?>/>Wavatar</option>
                        <option value="3" <? if($SiteOptions['Identicons'] == 3) { ?> selected="selected" <? } ?>/>Retro</option>
                        <option value="4" <? if($SiteOptions['Identicons'] == 4) { ?> selected="selected" <? } ?>/>Robots 1</option>
                        <option value="5" <? if($SiteOptions['Identicons'] == 5) { ?> selected="selected" <? } ?>/>Robots 2</option>
                        <option value="6" <? if($SiteOptions['Identicons'] == 6) { ?> selected="selected" <? } ?>/>Robots 3</option>
                    </select>
                </td>
            </tr>
              <tr>
                <td class="label"><strong>Push Notifications</strong></td>
                <td>
                    <select name="pushservice" id="pushservice">
                        <option value="0" <? if(empty($PushService)) { ?> selected="selected" <? } ?>/>Disable Push Notifications</option>
                        <option value="1" <? if($PushService == 1) { ?> selected="selected" <? } ?>/>Notify My Android</option>
                        <option value="2" <? if($PushService == 2) { ?> selected="selected" <? } ?>/>Prowl</option>
                        <option value="3" <? if($PushService == 3) { ?> selected="selected" <? } ?>/>Notifo</option>
                    </select>
                    <div id="pushsettings" style="display: none">
                    <br />
                        <label for="pushkey">API Key</label>
                        <input type="text" size="50" name="pushkey" id="pushkey" value="<?=display_str($PushOptions['PushKey'])?>" />
                        <div id="pushsettings_username" style="display: none">
                            <label for="pushusername">Username</label> <input type="text"
                                size="50" name="pushusername" id="pushusername"
                                value="<?=display_str($PushOptions['PushUsername'])?>" />
                        </div>
                        <br />
                    Push me on
                    <br />
                    <input type="checkbox" name="pushfilters[]" value="News" <? if(isset($PushOptions['PushFilters']['News'])) { ?> checked="checked"  <? } ?>/>Announcements<br />
                    <input type="checkbox" name="pushfilters[]" value="PM" <? if(isset($PushOptions['PushFilters']['PM'])) { ?> checked="checked"  <? } ?>/>Private Messages<br />
			<? /*		<input type="checkbox" name="pushfilters[]" value="Rippy" <? if(isset($PushOptions['PushFilters']['Rippy'])) { ?> checked="checked"  <? } ?>/>Rippys<br /> */ ?>
					
                   [<a href="user.php?action=take_push&amp;push=1&amp;userid=<?=$UserID?>&amp;auth=<?=$LoggedUser['AuthKey']?>">Test Push</a>]
                    	[<a href="wiki.php?action=article&id=1017">Wiki Guide</a>]
                    </div>
                 </td>
            </tr>
        <tr>
				<td class="label"><strong>Rippy!</strong></td>
				<td>
					<select name="rippy">
						<option value="On" <? if($SiteOptions['Rippy'] == 'On') { ?> selected="selected" <? } ?> >On</option>
						<option value="Off" <? if($SiteOptions['Rippy'] == 'Off') { ?> selected="selected" <? } ?> >Off</option>
						<option value="PM" <? if($SiteOptions['Rippy'] == 'PM' || empty($SiteOptions['Rippy'])) { ?> selected="selected" <? } ?> >Personal rippies only</option>
					</select>
				</td>
			</tr>	
			<tr>
				<td class="label"><strong>Auto-save Text</strong></td>
				<td>
					<input type="checkbox" name="disableautosave" id="disableautosave" <? if (!empty($SiteOptions['DisableAutoSave'])) { ?>checked="checked"<? } ?> />
					<label for="disableautosave">Disable reply text from being saved automatically when changing pages in a thread</label>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Voting links</strong></td>
				<td>
					<input type="checkbox" name="novotelinks" id="novotelinks" <? if (!empty($SiteOptions['NoVoteLinks'])) { ?>checked="checked"<? } ?> />
					<label for="novotelinks">Disable voting links on artist pages, collages, and snatched lists</label>
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
				<td><?php $textarea = new TEXTAREA_PREVIEW('info', 'info', display_str($Info), 50, 8); ?></td>
			</tr>
			<tr>
				<td class="label"><strong>IRCKey</strong></td>
				<td>
					<input type="text" size="50" name="irckey" id="irckey" value="<?=display_str($IRCKey)?>" />
					<p class="min_padding">If set, this field will be used in place of the password in the IRC login.</p>
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
					<p><span class="warning">Note: Paranoia has nothing to do with your security on this site; the only thing affected by this setting is other users' ability to see your site activity and taste in music.</span></p>
					<p>Select the elements <strong>you want to show</strong> on your profile. For example, if you tick "Show count" for "Snatched", users will be able to see how many torrents you have snatched. If you tick "Show list", they will be able to see the full list of torrents you've snatched.</p>
					<p><span class="warning">Some information will still be available in the site log.</span></p>
				</td>
			</tr>
			<tr>
				<td class="label">Recent activity</td>
				<td>
					<label><input type="checkbox" name="p_lastseen" <?=checked(!in_array('lastseen', $Paranoia))?>/> Last seen</label>
				</td>
			</tr>
			<tr>
				<td class="label">Preset</td>
				<td>
					<button type="button" onclick="ParanoiaResetOff()">Show everything</button>
					<button type="button" onclick="ParanoiaResetStats()">Show stats only</button>
					<!--<button type="button" onclick="ParanoiaResetOn()">Show nothing</button>-->
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
					<label><input type="checkbox" name="p_uploaded" onchange="AlterParanoia()"<?=$UploadChecked?> /> Uploaded</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_downloaded" onchange="AlterParanoia()"<?=$DownloadChecked?> /> Downloaded</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_ratio" onchange="AlterParanoia()"<?=$RatioChecked?> /> Ratio</label>
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
			<tr>
				<td class="label">Requests filled</td>
				<td>
<?
$RequestsFilledCountChecked = checked(!in_array('requestsfilled_count', $Paranoia));
$RequestsFilledBountyChecked = checked(!in_array('requestsfilled_bounty', $Paranoia));
$RequestsFilledListChecked = checked(!in_array('requestsfilled_list', $Paranoia));
?>
					<label><input type="checkbox" name="p_requestsfilled_count" onchange="AlterParanoia()" <?=$RequestsFilledCountChecked?> /> Show count</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsfilled_bounty" onchange="AlterParanoia()" <?=$RequestsFilledBountyChecked?> /> Show bounty</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsfilled_list" onchange="AlterParanoia()" <?=$RequestsFilledListChecked?> /> Show list</label>
				</td>
			</tr>
			<tr>
				<td class="label">Requests voted</td>
				<td>
<?
$RequestsVotedCountChecked = checked(!in_array('requestsvoted_count', $Paranoia));
$RequestsVotedBountyChecked = checked(!in_array('requestsvoted_bounty', $Paranoia));
$RequestsVotedListChecked = checked(!in_array('requestsvoted_list', $Paranoia));
?>
					<label><input type="checkbox" name="p_requestsvoted_count" onchange="AlterParanoia()" <?=$RequestsVotedCountChecked?> /> Show count</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsvoted_bounty" onchange="AlterParanoia()" <?=$RequestsVotedBountyChecked?> /> Show bounty</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsvoted_list" onchange="AlterParanoia()" <?=$RequestsVotedListChecked?> /> Show list</label>
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
					<label><input type="checkbox" name="p_requiredratio" <?=checked(!in_array('requiredratio', $Paranoia))?>/> Required ratio</label>
<?
$DB->query("SELECT COUNT(UserID) FROM users_info WHERE Inviter='$UserID'");
list($Invited) = $DB->next_record();
?>
					<br /><label><input type="checkbox" name="p_invitedcount" <?=checked(!in_array('invitedcount', $Paranoia))?>/> Number of users invited</label>
<?
$DB->query("SELECT COUNT(ta.ArtistID) FROM torrents_artists AS ta WHERE ta.UserID = ".$UserID);
list($ArtistsAdded) = $DB->next_record();
?>
					<br /><label><input type="checkbox" name="p_artistsadded" <?=checked(!in_array('artistsadded', $Paranoia))?>/> Number of artists added</label>
				</td>
			</tr>
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Reset Passkey</strong>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Reset passkey</strong></td>
				<td>
					<label><input type="checkbox" name="resetpasskey" />
					Any active torrents must be downloaded again to continue leeching/seeding.</label> <br />
					<a href="wiki.php?action=article&amp;name=Passkey">See also this wiki article</a>
				</td>
			</tr>
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Change password</strong>
				</td>
			</tr>
			<tr>
				<td/>
				<td>
					<p class="min_padding">A strong password is between 8 and 40 characters long</p>
					<p class="min_padding">Contains at least 1 lowercase and uppercase letter</p>
					<p class="min_padding">Contains at least a number or symbol</p>
				</td>
			</tr>
			<tr>
				<td class="label"><strong>Current password</strong></td>
				<td><input type="password" size="40" name="cur_pass" id="cur_pass" value="" /></td>
			</tr>
			<tr>
				<td class="label"><strong>New password</strong></td>
				<td><input type="password" size="40" name="new_pass_1" id="new_pass_1" value="" maxlength="40" /> <strong id="pass_strength"></strong></td>
			</tr>
			<tr>
				<td class="label"><strong>Re-type new password</strong></td>
				<td><input type="password" size="40" name="new_pass_2" id="new_pass_2" value="" maxlength="40" /> <strong id="pass_match"></strong></td>
			</tr>
			<tr>
				<td colspan="2" class="right">
					<input type="submit" value="Save Profile" />
				</td>
			</tr>
		</table>
	</form>
</div>
<? View::show_footer(); ?>
