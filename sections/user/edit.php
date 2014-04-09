<?
$UserID = $_REQUEST['userid'];
if (!is_number($UserID)) {
	error(404);
}

$DB->query("
	SELECT
		m.Username,
		m.Email,
		m.IRCKey,
		m.Paranoia,
		i.Info,
		i.Avatar,
		i.StyleID,
		i.StyleURL,
		i.SiteOptions,
		i.UnseededAlerts,
		i.DownloadAlt,
		p.Level AS Class,
		i.InfoTitle
	FROM users_main AS m
		JOIN users_info AS i ON i.UserID = m.ID
		LEFT JOIN permissions AS p ON p.ID = m.PermissionID
	WHERE m.ID = '".db_string($UserID)."'");
list($Username, $Email, $IRCKey, $Paranoia, $Info, $Avatar, $StyleID, $StyleURL, $SiteOptions, $UnseededAlerts, $DownloadAlt, $Class, $InfoTitle) = $DB->next_record(MYSQLI_NUM, array(3, 8));

if ($UserID != $LoggedUser['ID'] && !check_perms('users_edit_profiles', $Class)) {
	error(403);
}

$Paranoia = unserialize($Paranoia);
if (!is_array($Paranoia)) {
	$Paranoia = array();
}

function paranoia_level($Setting) {
	global $Paranoia;
	// 0: very paranoid; 1: stats allowed, list disallowed; 2: not paranoid
	return (in_array($Setting . '+', $Paranoia)) ? 0 : (in_array($Setting, $Paranoia) ? 1 : 2);
}

function display_paranoia($FieldName) {
	$Level = paranoia_level($FieldName);
	print "\t\t\t\t\t<label><input type=\"checkbox\" name=\"p_{$FieldName}_c\"".checked($Level >= 1).' onchange="AlterParanoia()" /> Show count</label>'."&nbsp;&nbsp;\n";
	print "\t\t\t\t\t<label><input type=\"checkbox\" name=\"p_{$FieldName}_l\"".checked($Level >= 2).' onchange="AlterParanoia()" /> Show list</label>'."\n";
}

function checked($Checked) {
	return ($Checked ? ' checked="checked"' : '');
}

if ($SiteOptions) {
	$SiteOptions = unserialize($SiteOptions);
} else {
	$SiteOptions = array();
}

View::show_header("$Username &gt; Settings", 'user,jquery-ui,release_sort,password_validate,validate,cssgallery,preview_paranoia,bbcode,user_settings,donor_titles');



$DonorRank = Donations::get_rank($UserID);
$DonorIsVisible = Donations::is_visible($UserID);

if ($DonorIsVisible === null) {
	$DonorIsVisible = true;
}

extract(Donations::get_enabled_rewards($UserID));
$Rewards = Donations::get_rewards($UserID);
$ProfileRewards = Donations::get_profile_rewards($UserID);
$DonorTitles = Donations::get_titles($UserID);

$DB->query("
	SELECT username
	FROM lastfm_users
	WHERE ID = '$UserID'");
$LastFMUsername = '';
list($LastFMUsername) = $DB->next_record();
echo $Val->GenerateJS('userform');
?>
<div class="thin">
	<div class="header">
		<h2><?=Users::format_username($UserID, false, false, false)?> &gt; Settings</h2>
	</div>
	<form class="edit_form" name="user" id="userform" action="" method="post" autocomplete="off">
	<div class="sidebar settings_sidebar">
		<div class="box box2" id="settings_sections">
			<div class="head">
				<strong>Sections</strong>
			</div>
			<ul class="nobullet">
				<li data-gazelle-section-id="all_settings">
					<h2><a href="#" class="tooltip" title="View the full list of user settings.">All Settings</a></h2>
				</li>
				<li data-gazelle-section-id="site_appearance_settings">
					<h2><a href="#" class="tooltip" title="These settings change the visual style of the entire site.">Site Appearance Settings</a></h2>
				</li>
				<li data-gazelle-section-id="torrent_settings">
					<h2><a href="#" class="tooltip" title="These settings change how torrents are searched for, grouped, displayed, and downloaded.">Torrent Settings</a></h2>
				</li>
				<li data-gazelle-section-id="community_settings">
					<h2><a href="#" class="tooltip" title="These settings change how interactions with other users are formatted, grouped, and displayed.">Community Settings</a></h2>
				</li>
				<li data-gazelle-section-id="notification_settings">
					<h2><a href="#" class="tooltip" title="These settings change the format and types of notifications you receive.">Notification Settings</a></h2>
				</li>
				<li data-gazelle-section-id="personal_settings">
					<h2><a href="#" class="tooltip" title="These settings alter the appearance of your profile and posts.">Personal Settings</a></h2>
				</li>
				<li data-gazelle-section-id="paranoia_settings">
					<h2><a href="#" class="tooltip" title="These settings allow you to display or hide different categories of information from your profile.">Paranoia Settings</a></h2>
				</li>
				<li data-gazelle-section-id="access_settings">
					<h2><a href="#" class="tooltip" title="These settings control your login and access details for <?=SITE_NAME?>, the site's IRC network, and the tracker.">Access Settings</a></h2>
				</li>
				<li data-gazelle-section-id="live_search">
					<input type="text" id="settings_search" placeholder="Live Search" />
				</li>
				<li>
					<input type="submit" id="submit" value="Save profile" />
				</li>
			</ul>
		</div>
	</div>
	<div class="main_column">
		<div>
			<input type="hidden" name="action" value="take_edit" />
			<input type="hidden" name="userid" value="<?=$UserID?>" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		</div>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="site_appearance_settings">
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Site Appearance Settings</strong>
				</td>
			</tr>
			<tr id="site_style_tr">
				<td class="label tooltip" title="Selecting a stylesheet will change <?=SITE_NAME?>'s visual appearance."><strong>Stylesheet</strong></td>
				<td>
					<select name="stylesheet" id="stylesheet">
<?	foreach ($Stylesheets as $Style) { ?>
						<option value="<?=($Style['ID'])?>"<?=$Style['ID'] == $StyleID ? ' selected="selected"' : ''?>><?=($Style['ProperName'])?></option>
<?	} ?>
					</select>&nbsp;&nbsp;
					<a href="#" id="toggle_css_gallery" class="brackets">Show gallery</a>
					<div id="css_gallery">
<?	foreach ($Stylesheets as $Style) { ?>
						<div class="preview_wrapper">
							<div class="preview_image" name="<?=($Style['Name'])?>">
								<a href="<?=STATIC_SERVER.'stylespreview/full_'.$Style['Name'].'.png'?>" target="_blank">
									<img src="<?=STATIC_SERVER.'stylespreview/thumb_'.$Style['Name'].'.png'?>" alt="<?=$Style['Name']?>" />
								</a>
							</div>
							<p class="preview_name">
								<label><input type="radio" name="stylesheet_gallery" value="<?=($Style['ID'])?>" /> <?=($Style['ProperName'])?></label>
							</p>
						</div>
<?	} ?>
					</div>
				</td>
			</tr>
			<tr id="site_extstyle_tr">
				<td class="label tooltip" title="Providing a link to an externally-hosted stylesheet will override your default stylesheet selection."><strong>External stylesheet URL</strong></td>
				<td>
					<input type="text" size="40" name="styleurl" id="styleurl" value="<?=display_str($StyleURL)?>" />
				</td>
			</tr>
			<tr id="site_opendyslexic_tr">
				<td class="label tooltip_interactive" title="&lt;a href=&quot;http://opendyslexic.org&quot; target=&quot;_blank&quot;&gt;Click here&lt;/a&gt; to read about OpenDyslexic, a CC-BY 3.0 licensed font designed for users with dyslexia." data-title-plain="Go to http://opendyslexic.org to read about OpenDyslexic, a CC-BY 3.0 licensed font designed for users with dyslexia."><strong>OpenDyslexic</strong></td>
				<td>
					<div class="field_div">
						<input type="checkbox" name="useopendyslexic" id="useopendyslexic"<?=!empty($SiteOptions['UseOpenDyslexic']) ? ' checked="checked"' : ''?> />
						<label for="useopendyslexic">Enable the OpenDyslexic font</label>
					</div>
					<p class="min_padding">This is an experimental feature, and some stylesheets will have display issues.</p>
				</td>
			</tr>
			<tr id="site_tooltips_tr">
				<td class="label tooltip" title="Use styled tooltips instead of the browser's default when hovering elements with extra information (such as this one)."><strong>Styled tooltips</strong></td>
				<td>
					<input type="checkbox" name="usetooltipster" id="usetooltipster"<?=!isset($SiteOptions['Tooltipster']) || $SiteOptions['Tooltipster'] ? ' checked="checked"' : ''?> />
					<label for="usetooltipster">Enable styled tooltips</label>
				</td>
			</tr>
<?	if (check_perms('users_mod')) { ?>
			<tr id="site_autostats_tr">
				<td class="label tooltip" title="This is a staff-only feature to bypass the &quot;Show stats&quot; button for seeding, leeching, snatched, and downloaded stats on profile pages."><strong>Profile stats</strong></td>
				<td>
					<label>
						<input type="checkbox" name="autoload_comm_stats"<?Format::selected('AutoloadCommStats', 1, 'checked', $SiteOptions);?> />
						Automatically fetch the snatch and peer stats on profile pages.
					</label>
				</td>
			</tr>
<?	} ?>
		</table>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="torrent_settings">
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Torrent Settings</strong>
				</td>
			</tr>
<?	if (check_perms('site_advanced_search')) { ?>
			<tr id="tor_searchtype_tr">
				<td class="label tooltip" title="This option allows you to choose whether the default torrent search menu will be basic (fewer options) or advanced (more options)."><strong>Default search type</strong></td>
				<td>
					<ul class="options_list nobullet">
						<li>
							<input type="radio" name="searchtype" id="search_type_simple" value="0"<?=$SiteOptions['SearchType'] == 0 ? ' checked="checked"' : ''?> />
							<label for="search_type_simple">Simple</label>
						</li>
						<li>
							<input type="radio" name="searchtype" id="search_type_advanced" value="1"<?=$SiteOptions['SearchType'] == 1 ? ' checked="checked"' : ''?> />
							<label for="search_type_advanced">Advanced</label>
						</li>
					</ul>
				</td>
			</tr>
<?	} ?>
			<tr id="tor_group_tr">
				<td class="label tooltip" title="Enabling torrent grouping will place multiple formats of the same torrent group together beneath a common header."><strong>Torrent grouping</strong></td>
				<td>
					<div class="option_group">
						<input type="checkbox" name="disablegrouping" id="disablegrouping"<?=$SiteOptions['DisableGrouping2'] == 0 ? ' checked="checked"' : ''?> />
						<label for="disablegrouping">Enable torrent grouping</label>
					</div>
				</td>
			</tr>
			<tr id="tor_gdisp_search_tr">
				<td class="label tooltip" title="In torrent search results and on artist pages, &quot;open&quot; will expand torrent groups by default, and &quot;closed&quot; will collapse torrent groups by default."><strong>Torrent group display</strong></td>
				<td>
					<div class="option_group">
						<ul class="options_list nobullet">
							<li>
								<input type="radio" name="torrentgrouping" id="torrent_grouping_open" value="0"<?=$SiteOptions['TorrentGrouping'] == 0 ? ' checked="checked"' : ''?> />
								<label for="torrent_grouping_open">Open</label>
							</li>
							<li>
								<input type="radio" name="torrentgrouping" id="torrent_grouping_closed" value="1"<?=$SiteOptions['TorrentGrouping'] == 1 ? ' checked="checked"' : ''?> />
								<label for="torrent_grouping_closed">Closed</label>
							</li>
						</ul>
					</div>
				</td>
			</tr>
			<tr id="tor_gdisp_artist_tr">
				<td class="label tooltip" title="On artist pages, &quot;open&quot; will expand release type sections by default, and &quot;closed&quot; will collapse release type sections by default."><strong>Release type display (artist pages)</strong></td>
				<td>
					<ul class="options_list nobullet">
						<li>
							<input type="radio" name="discogview" id="discog_view_open" value="0"<?=$SiteOptions['DiscogView'] == 0 ? ' checked="checked"' : ''?> />
							<label for="discog_view_open">Open</label>
						</li>
						<li>
							<input type="radio" name="discogview" id="discog_view_closed" value="1"<?=$SiteOptions['DiscogView'] == 1 ? ' checked="checked"' : ''?> />
							<label for="discog_view_closed">Closed</label>
						</li>
					</ul>
				</td>
			</tr>
			<tr id="tor_reltype_tr">
				<td class="label tooltip" title="Any selected release type will be collapsed by default on artist pages."><strong>Release type display (artist pages)</strong></td>
				<td>
					<a href="#" id="toggle_sortable" class="brackets">Expand</a>
					<div id="sortable_container" style="display: none;">
						<a href="#" id="reset_sortable" class="brackets">Reset to default</a>
						<p class="min_padding">Drag and drop release types to change their order.</p>
						<ul class="sortable_list" id="sortable">
<?Users::release_order($SiteOptions)?>
						</ul>
						<script type="text/javascript" id="sortable_default">//<![CDATA[
							var sortable_list_default = <?=Users::release_order_default_js($SiteOptions)?>;
							//]]>
						</script>
					</div>
					<input type="hidden" id="sorthide" name="sorthide" value="" />
				</td>
			</tr>
			<tr id="tor_snatched_tr">
				<td class="label tooltip" title="Enabling the snatched torrents indicator will display &quot;Snatched!&quot; next to torrents you've snatched."><strong>Snatched torrents indicator</strong></td>
				<td>
					<input type="checkbox" name="showsnatched" id="showsnatched"<?=!empty($SiteOptions['ShowSnatched']) ? ' checked="checked"' : ''?> />
					<label for="showsnatched">Enable snatched torrents indicator</label>
				</td>
			</tr>
<!--			<tr>
				<td class="label"><strong>Collage album art view</strong></td>
				<td>
					<select name="hidecollage" id="hidecollage">
						<option value="0"<?=$SiteOptions['HideCollage'] == 0 ? ' selected="selected"' : ''?>>Show album art</option>
						<option value="1"<?=$SiteOptions['HideCollage'] == 1 ? ' selected="selected"' : ''?>>Hide album art</option>
					</select>
				</td>
			</tr>-->
			<tr id="tor_cover_tor_tr">
				<td class="label tooltip" title="Enabling cover artwork for torrents will show cover artwork next to torrent information. Enabling additional cover artwork will display all additional cover artwork as well."><strong>Cover art (torrents)</strong></td>
				<td>
					<ul class="options_list nobullet">
						<li>
							<input type="hidden" name="coverart" value="" />
							<input type="checkbox" name="coverart" id="coverart"<?=$SiteOptions['CoverArt'] ? ' checked="checked"' : ''?> />
							<label for="coverart">Enable cover artwork</label>
						</li>
						<li>
							<input type="checkbox" name="show_extra_covers" id="show_extra_covers"<?=$SiteOptions['ShowExtraCovers'] ? ' checked="checked"' : ''?> />
							<label for="show_extra_covers">Enable additional cover artwork</label>
						</li>
					</ul>
				</td>
			</tr>
			<tr id="tor_cover_coll_tr">
				<td class="label tooltip" title="This option allows you to change the number of album covers to display within a single collage page."><strong>Cover art (collages)</strong></td>
				<td>
					<select name="collagecovers" id="collagecovers">
						<option value="10"<?=$SiteOptions['CollageCovers'] == 10 ? ' selected="selected"' : ''?>>10</option>
						<option value="25"<?=($SiteOptions['CollageCovers'] == 25 || !isset($SiteOptions['CollageCovers'])) ? ' selected="selected"' : ''?>>25 (default)</option>
						<option value="50"<?=$SiteOptions['CollageCovers'] == 50 ? ' selected="selected"' : ''?>>50</option>
						<option value="100"<?=$SiteOptions['CollageCovers'] == 100 ? ' selected="selected"' : ''?>>100</option>
						<option value="1000000"<?=$SiteOptions['CollageCovers'] == 1000000 ? ' selected="selected"' : ''?>>All</option>
						<option value="0"<?=($SiteOptions['CollageCovers'] === 0 || (!isset($SiteOptions['CollageCovers']) && $SiteOptions['HideCollage'])) ? ' selected="selected"' : ''?>>None</option>
					</select>
					covers per page
				</td>
			</tr>
			<tr id="tor_showfilt_tr">
				<td class="label tooltip" title="Displaying filter controls will show torrent filtering options in the torrent search menu by default. Displaying filters for official tags will list clickable filters for official tags in the torrent search menu by default."><strong>Torrent search filters</strong></td>
				<td>
					<ul class="options_list nobullet">
						<li>
							<input type="checkbox" name="showtfilter" id="showtfilter"<?=(!isset($SiteOptions['ShowTorFilter']) || $SiteOptions['ShowTorFilter'] ? ' checked="checked"' : '')?> />
							<label for="showtfilter">Display filter controls</label>
						</li>
						<li>
							<input type="checkbox" name="showtags" id="showtags"<? Format::selected('ShowTags', 1, 'checked', $SiteOptions); ?> />
							<label for="showtags">Display official tag filters</label>
						</li>
					</ul>
				</td>
			</tr>
			<tr id="tor_autocomp_tr">
				<td class="label tooltip" title="Autocomplete will try to predict the word or phrase that you're typing. Selecting &quot;Everywhere&quot; will enable autocomplete on artist and tag fields across the site. Selecting &quot;Searches only&quot; will enable autocomplete in searches."><strong>Autocompletion</strong></td>
				<td>
					<select name="autocomplete">
						<option value="0"<?=empty($SiteOptions['AutoComplete']) ? ' selected="selected"' : ''?>>Everywhere</option>
						<option value="2"<?=$SiteOptions['AutoComplete'] === 2 ? ' selected="selected"' : ''?>>Searches only</option>
						<option value="1"<?=$SiteOptions['AutoComplete'] === 1 ? ' selected="selected"' : ''?>>Disable</option>
					</select>
				</td>
			</tr>
			<tr id="tor_voting_tr">
				<td class="label tooltip" title="This option allows you to enable or disable &quot;up&quot; and &quot;down&quot; voting links on artist pages, collages, and snatched lists."><strong>Voting links</strong></td>
				<td>
					<input type="checkbox" name="novotelinks" id="novotelinks"<?=!empty($SiteOptions['NoVoteLinks']) ? ' checked="checked"' : ''?> />
					<label for="novotelinks">Disable voting links</label>
				</td>
			</tr>
			<tr id="tor_dltext_tr">
				<td class="label tooltip" title="Some ISPs block the downloading of torrent files. Enable this option if you wish to download torrent files with a &quot;.txt&quot; file extension."><strong>Text file downloads</strong></td>
				<td>
					<input type="checkbox" name="downloadalt" id="downloadalt"<?=$DownloadAlt ? ' checked="checked"' : ''?> />
					<label for="downloadalt">Enable downloading torrent files as text files</label>
				</td>
			</tr>
		</table>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="community_settings">
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Community Settings</strong>
				</td>
			</tr>
			<tr id="comm_ppp_tr">
				<td class="label tooltip" title="This option allows you to set the desired number of displayed posts per page within forum threads."><strong>Posts per page (forums)</strong></td>
				<td>
					<select name="postsperpage" id="postsperpage">
						<option value="25"<?=$SiteOptions['PostsPerPage'] == 25 ? ' selected="selected"' : ''?>>25 (default)</option>
						<option value="50"<?=$SiteOptions['PostsPerPage'] == 50 ? ' selected="selected"' : ''?>>50</option>
						<option value="100"<?=$SiteOptions['PostsPerPage'] == 100 ? ' selected="selected"' : ''?>>100</option>
					</select>
					posts per page
				</td>
			</tr>
			<tr id="comm_inbsort_tr">
				<td class="label tooltip" title="This option will force unread private messages to be listed first."><strong>Inbox sorting</strong></td>
				<td>
					<input type="checkbox" name="list_unread_pms_first" id="list_unread_pms_first"<?=!empty($SiteOptions['ListUnreadPMsFirst']) ? ' checked="checked"' : ''?> />
					<label for="list_unread_pms_first">List unread private messages first</label>
				</td>
			</tr>
			<tr id="comm_emot_tr">
				<td class="label tooltip" title="Emoticons are small images which replace traditional text-based &quot;smileys&quot; like :) or :("><strong>Emoticons</strong></td>
				<td>
					<input type="checkbox" name="disablesmileys" id="disablesmileys"<?=!empty($SiteOptions['DisableSmileys']) ? ' checked="checked"' : ''?> />
					<label for="disablesmileys">Disable emoticons</label>
				</td>
			</tr>
			<tr id="comm_mature_tr">
				<td class="label tooltip_interactive" title="<?=SITE_NAME?> has very flexible &lt;a href=&quot;wiki.php?action=article&amp;amp;id=1063&quot;&gt;mature content policies&lt;/a&gt; for all community areas. Choosing to display mature content will allow you to click-through to view any content posted beneath &lt;code&gt;[mature]&lt;/code&gt; tags. If you choose not to display mature content, all content tagged with &lt;code&gt;[mature]&lt;/code&gt; tags will be hidden from you." data-title-plain="<?=SITE_NAME?> has very flexible mature content policies for all community areas. Choosing to display mature content will allow you to click-through to view any content posted beneath [mature] tags. If you choose not to display mature content, all content tagged with [mature] tags will be hidden from you."><strong>Mature content (forums, comments, profiles)</strong></td>
				<td>
					<input type="checkbox" name="enablematurecontent" id="enablematurecontent"<?=!empty($SiteOptions['EnableMatureContent']) ? ' checked="checked"' : ''?> />
					<label for="enablematurecontent">Display mature content</label>
				</td>
			</tr>
			<tr id="comm_avatars_tr">
				<td class="label tooltip" title="This option allows you to disable all avatars, show all avatars (with a placeholder for users without avatars), show all avatars &lt;em&gt;or&lt;/em&gt; an identicon set (for users without avatars), or replace all avatars with an identicon set of your choosing." data-title-plain="This option allows you to disable all avatars, show all avatars (with a placeholder for users without avatars), show all avatars or an identicon set (for users without avatars), or replace all avatars with an identicon set of your choosing."><strong>Avatar display (posts)</strong></td>
				<td>
					<select name="disableavatars" id="disableavatars" onclick="ToggleIdenticons();">
						<option value="1"<?=$SiteOptions['DisableAvatars'] == 1 ? ' selected="selected"' : ''?>>Disable avatars</option>
						<option value="0"<?=$SiteOptions['DisableAvatars'] == 0 ? ' selected="selected"' : ''?>>Show avatars</option>
						<option value="2"<?=$SiteOptions['DisableAvatars'] == 2 ? ' selected="selected"' : ''?>>Show avatars or:</option>
						<option value="3"<?=$SiteOptions['DisableAvatars'] == 3 ? ' selected="selected"' : ''?>>Replace all avatars with:</option>
					</select>
					<select name="identicons" id="identicons">
						<option value="0"<?=$SiteOptions['Identicons'] == 0 ? ' selected="selected"' : ''?>>Identicon</option>
						<option value="1"<?=$SiteOptions['Identicons'] == 1 ? ' selected="selected"' : ''?>>MonsterID</option>
						<option value="2"<?=$SiteOptions['Identicons'] == 2 ? ' selected="selected"' : ''?>>Wavatar</option>
						<option value="3"<?=$SiteOptions['Identicons'] == 3 ? ' selected="selected"' : ''?>>Retro</option>
						<option value="4"<?=$SiteOptions['Identicons'] == 4 ? ' selected="selected"' : ''?>>Robots 1</option>
						<option value="5"<?=$SiteOptions['Identicons'] == 5 ? ' selected="selected"' : ''?>>Robots 2</option>
						<option value="6"<?=$SiteOptions['Identicons'] == 6 ? ' selected="selected"' : ''?>>Robots 3</option>
					</select>
				</td>
			</tr>
			<tr id="comm_autosave_tr">
				<td class="label tooltip" title="As you add text to a post or reply, this text is automatically saved. If you stop and return to your post at a later time (e.g. accidentally clicking a link and then pressing the &quot;Back&quot; button in your browser), the text will remain. This option allows you to disable this feature."><strong>Auto-save reply text</strong></td>
				<td>
					<input type="checkbox" name="disableautosave" id="disableautosave"<?=!empty($SiteOptions['DisableAutoSave']) ? ' checked="checked"' : ''?> />
					<label for="disableautosave">Disable text auto-saving</label>
				</td>
			</tr>
		</table>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="notification_settings">
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Notification Settings</strong>
				</td>
			</tr>
			<tr id="notif_autosubscribe_tr">
				<td class="label tooltip" title="Enabling this will automatically subscribe you to any thread you post in."><strong>Automatic thread subscriptions</strong></td>
				<td>
					<input type="checkbox" name="autosubscribe" id="autosubscribe"<?=!empty($SiteOptions['AutoSubscribe']) ? ' checked="checked"' : ''?> />
					<label for="autosubscribe">Enable automatic thread subscriptions</label>
				</td>
			</tr>
			<tr id="notif_unseeded_tr">
				<td class="label tooltip" title="Enabling this will send you a PM alert before your uploads are deleted for being unseeded."><strong>Unseeded torrent alerts</strong></td>
				<td>
					<input type="checkbox" name="unseededalerts" id="unseededalerts"<?=checked($UnseededAlerts)?> />
					<label for="unseededalerts">Enable unseeded torrent alerts</label>
				</td>
			</tr>
			<? NotificationsManagerView::render_settings(NotificationsManager::get_settings($UserID)); ?>
		</table>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="personal_settings">
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Personal Settings</strong>
				</td>
			</tr>
			<tr id="pers_avatar_tr">
				<td class="label tooltip_interactive" title="Please link to an avatar which follows the &lt;a href=&quot;rules.php&quot;&gt;site rules&lt;/a&gt;. The avatar width should be 150 pixels and will be resized if necessary." data-title-plain="Please link to an avatar which follows the site rules. The avatar width should be 150 pixels and will be resized if necessary."><strong>Avatar URL</strong></td>
				<td>
					<input type="text" size="50" name="avatar" id="avatar" value="<?=display_str($Avatar)?>" />
				</td>
			</tr>
<?	if ($HasSecondAvatar) { ?>
			<tr id="pers_avatar2_tr">
				<td class="label tooltip_interactive" title="Congratulations! You've unlocked this option by reaching Special Rank #2. Thanks for donating. Your normal avatar will &quot;flip&quot; to this one when someone runs their mouse over the image. Please link to an avatar which follows the &lt;a href=&quot;rules.php&quot;&gt;site rules&lt;/a&gt;. The avatar width should be 150 pixels and will be resized if necessary." data-title-plain="Congratulations! You've unlocked this option by reaching Special Rank #2. Thanks for donating. Your normal avatar will &quot;flip&quot; to this one when someone runs their mouse over the image. Please link to an avatar which follows the site rules. The avatar width should be 150 pixels and will be resized if necessary."><strong>Second avatar URL</strong></td>
				<td>
					<input type="text" size="50" name="second_avatar" id="second_avatar" value="<?=$Rewards['SecondAvatar']?>" />
				</td>
			</tr>
<?	}
	if ($HasAvatarMouseOverText) { ?>
			<tr id="pers_avatarhover_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #3. Thanks for donating. Text you enter in this field will appear when someone mouses over your avatar. All text should follow site rules. 200 character limit."><strong>Avatar mouseover text</strong></td>
				<td>
					<input type="text" size="50" name="avatar_mouse_over_text" id="avatar_mouse_over_text" value="<?=$Rewards['AvatarMouseOverText']?>" />
				</td>
			</tr>
<?	}
	if ($HasDonorIconMouseOverText) { ?>
			<tr id="pers_donorhover_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #2. Thanks for donating. Text you enter in this field will appear when someone mouses over your donor icon. All text should follow site rules. 200 character limit."><strong>Donor icon mouseover text</strong></td>
				<td>
					<input type="text" size="50" name="donor_icon_mouse_over_text" id="donor_icon_mouse_over_text" value="<?=$Rewards['IconMouseOverText']?>" />
				</td>
			</tr>
<?	}
	if ($HasDonorIconLink) { ?>
			<tr id="pers_donorlink_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #4. Thanks for donating. Links you enter in this field will be accessible when your donor icon is clicked. All links should follow site rules."><strong>Donor icon link</strong></td>
				<td>
					<input type="text" size="50" name="donor_icon_link" id="donor_icon_link" value="<?=$Rewards['CustomIconLink']?>" />
				</td>
			</tr>
<?	}
	if ($HasCustomDonorIcon) { ?>
			<tr id="pers_donoricon_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #5. Thanks for donating. Please link to an icon which you'd like to replace your default donor icon with. Icons must be 15 pixels wide by 13 pixels tall. Icons of any other size will be automatically resized."><strong>Custom donor icon URL</strong></td>
				<td>
					<input type="text" size="50" name="donor_icon_custom_url" id="donor_icon_custom_url" value="<?=$Rewards['CustomIcon']?>" />
				</td>
			</tr>
<?	}
	if ($HasDonorForum) { ?>
			<tr id="pers_donorforum_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #5. Thanks for donating. You may select a prefix and suffix which will be used in the Donor Forum you now have access to."><strong>Donor forum honorific</strong></td>
				<td>
					<div class="field_div">
						<label><strong>Prefix:</strong> <input type="text" size="30" maxlength="30" name="donor_title_prefix" id="donor_title_prefix" value="<?=$DonorTitles['Prefix']?>" /></label>
					</div>
					<div class="field_div">
						<label><strong>Suffix:</strong> <input type="text" size="30" maxlength="30" name="donor_title_suffix" id="donor_title_suffix" value="<?=$DonorTitles['Suffix']?>" /></label>
					</div>
					<div class="field_div">
						<label><strong>Hide comma:</strong> <input type="checkbox" id="donor_title_comma" name="donor_title_comma"<?=!$DonorTitles['UseComma'] ? ' checked="checked"' : '' ?> /></label>
					</div>
					<strong>Preview:</strong> <span id="donor_title_prefix_preview"></span><?=$Username?><span id="donor_title_comma_preview">, </span><span id="donor_title_suffix_preview"></span>
				</td>
			</tr>
<?	} ?>
			<tr id="pers_lastfm_tr">
				<td class="label tooltip_interactive" title="This is used to display &lt;a href=&quot;http://www.last.fm/&quot;&gt;Last.fm&lt;/a&gt; information on your profile. Entering your Last.fm username will allow others to see your Last.fm account." data-title-plain="This is used to display Last.fm information on your profile. Entering your Last.fm username will allow others to see your Last.fm account."><strong>Last.fm username</strong></td>
				<td><input type="text" size="50" name="lastfm_username" id="lastfm_username" value="<?=display_str($LastFMUsername)?>" />
				</td>
			</tr>
			<tr id="pers_proftitle_tr">
				<td class="label tooltip" title="You can customize your profile information with text and BBCode. Entering a title will label your profile information section. Unlock additional profile info boxes via Donor Ranks."><strong>Profile title 1</strong></td>
				<td><input type="text" size="50" name="profile_title" id="profile_title" value="<?=display_str($InfoTitle)?>" />
				</td>
			</tr>
			<tr id="pers_profinfo_tr">
				<td class="label tooltip" title="You can customize your profile information with text and BBCode. Entering a title will label your profile information section. Unlock additional profile info boxes via Donor Ranks."><strong>Profile info 1</strong></td>
				<td><?php $textarea = new TEXTAREA_PREVIEW('info', 'info', display_str($Info), 40, 8); ?></td>
			</tr>
			<!-- Excuse this numbering confusion, we start numbering our profile info/titles at 1 in the donor_rewards table -->
<?	if ($HasProfileInfo1) { ?>
			<tr id="pers_proftitle2_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #2. Thanks for donating. You can customize your profile information with text and BBCode. Entering a title will label your profile information section."><strong>Profile title 2</strong></td>
				<td><input type="text" size="50" name="profile_title_1" id="profile_title_1" value="<?=display_str($ProfileRewards['ProfileInfoTitle1'])?>" />
				</td>
			</tr>
			<tr id="pers_profinfo2_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #2. Thanks for donating. You can customize your profile information with text and BBCode. Entering a title will label your profile information section."><strong>Profile info 2</strong></td>
				<td><?php $textarea = new TEXTAREA_PREVIEW('profile_info_1', 'profile_info_1', display_str($ProfileRewards['ProfileInfo1']), 40, 8); ?></td>
			</tr>
<?	}
	if ($HasProfileInfo2) { ?>
			<tr id="pers_proftitle3_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #3. Thanks for donating. You can customize your profile information with text and BBCode. Entering a title will label your profile information section."><strong>Profile title 3</strong></td>
				<td><input type="text" size="50" name="profile_title_2" id="profile_title_2" value="<?=display_str($ProfileRewards['ProfileInfoTitle2'])?>" />
				</td>
			</tr>
			<tr id="pers_profinfo3_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #3. Thanks for donating. You can customize your profile information with text and BBCode. Entering a title will label your profile information section."><strong>Profile info 3</strong></td>
				<td><?php $textarea = new TEXTAREA_PREVIEW('profile_info_2', 'profile_info_2', display_str($ProfileRewards['ProfileInfo2']), 40, 8); ?></td>
			</tr>
<?	}
	if ($HasProfileInfo3) { ?>
			<tr id="pers_proftitle4_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #4. Thanks for donating. You can customize your profile information with text and BBCode. Entering a title will label your profile information section."><strong>Profile title 4</strong></td>
				<td><input type="text" size="50" name="profile_title_3" id="profile_title_3" value="<?=display_str($ProfileRewards['ProfileInfoTitle3'])?>" />
				</td>
			</tr>
			<tr id="pers_profinfo4_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #4. Thanks for donating. You can customize your profile information with text and BBCode. Entering a title will label your profile information section."><strong>Profile info 4</strong></td>
				<td><?php $textarea = new TEXTAREA_PREVIEW('profile_info_3', 'profile_info_3', display_str($ProfileRewards['ProfileInfo3']), 40, 8); ?></td>
			</tr>
<?	}
	if ($HasProfileInfo4) { ?>
			<tr id="pers_proftitle5_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #5. Thanks for donating. You can customize your profile information with text and BBCode. Entering a title will label your profile information section."><strong>Profile title 5</strong></td>
				<td><input type="text" size="50" name="profile_title_4" id="profile_title_4" value="<?=display_str($ProfileRewards['ProfileInfoTitle4'])?>" />
				</td>
			</tr>
			<tr id="pers_profinfo5_tr">
				<td class="label tooltip" title="Congratulations! You've unlocked this option by reaching Donor Rank #5. Thanks for donating. You can customize your profile information with text and BBCode. Entering a title will label your profile information section."><strong>Profile info 5</strong></td>
				<td><?php $textarea = new TEXTAREA_PREVIEW('profile_info_4', 'profile_info_4', display_str($ProfileRewards['ProfileInfo4']), 40, 8); ?></td>
			</tr>
<?	} ?>
		</table>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="paranoia_settings">
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Paranoia Settings</strong>
				</td>
			</tr>
			<tr>
				<td class="label">&nbsp;</td>
				<td>
					<p><strong>Select the profile elements you wish to display to other users.</strong></p>
					<p>For example, if you select "Show count" for "Requests (filled)", the number of requests you have filled will be visible. If you select "Show bounty", the amount of request bounty you have received will be visible. If you select "Show list", the full list of requests you have filled will be visible.</p>
					<p><span class="warning">Note: Paranoia has nothing to do with your security on this site. These settings only determine if others can view your site activity. Some information will remain available in the site log.</span></p>
				</td>
			</tr>
			<tr id="para_lastseen_tr">
				<td class="label tooltip" title="Enable this to allow others to see when your most recent site activity occurred."><strong>Recent activity</strong></td>
				<td>
					<label><input type="checkbox" name="p_lastseen"<?=checked(!in_array('lastseen', $Paranoia))?> /> Last seen</label>
				</td>
			</tr>
			<tr id="para_presets_tr">
				<td class="label"><strong>Presets</strong></td>
				<td>
					<input type="button" onclick="ParanoiaResetOff();" value="Show everything" />
					<input type="button" onclick="ParanoiaResetStats();" value="Show stats only" />
					<!--<input type="button" onclick="ParanoiaResetOn();" value="Show nothing" />-->
				</td>
			</tr>
			<tr id="para_donations_tr">
				<td class="label"><strong>Donations</strong></td>
				<td>
					<input type="checkbox" id="p_donor_stats" name="p_donor_stats" onchange="AlterParanoia();"<?=$DonorIsVisible ? ' checked="checked"' : ''?> />
					<label for="p_donor_stats">Show donor stats</label>
					<input type="checkbox" id="p_donor_heart" name="p_donor_heart" onchange="AlterParanoia();"<?=checked(!in_array('hide_donor_heart', $Paranoia))?> />
					<label for="p_donor_heart">Show donor heart</label>
				</td>
			</tr>
			<tr id="para_stats_tr">
				<td class="label tooltip" title="These settings control the display of your uploaded data amount, downloaded data amount, and ratio."><strong>Statistics</strong></td>
				<td>
<?
$UploadChecked = checked(!in_array('uploaded', $Paranoia));
$DownloadChecked = checked(!in_array('downloaded', $Paranoia));
$RatioChecked = checked(!in_array('ratio', $Paranoia));
?>
					<label><input type="checkbox" name="p_uploaded" onchange="AlterParanoia();"<?=$UploadChecked?> /> Uploaded</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_downloaded" onchange="AlterParanoia();"<?=$DownloadChecked?> /> Downloaded</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_ratio" onchange="AlterParanoia();"<?=$RatioChecked?> /> Ratio</label>
				</td>
			</tr>
			<tr id="para_reqratio_tr">
				<td class="label"><strong>Required Ratio</strong></td>
				<td>
					<label><input type="checkbox" name="p_requiredratio"<?=checked(!in_array('requiredratio', $Paranoia))?> /> Required Ratio</label>
				</td>
			</tr>
			<tr id="para_comments_tr">
				<td class="label"><strong>Comments (torrents)</strong></td>
				<td>
<? display_paranoia('torrentcomments'); ?>
				</td>
			</tr>
			<tr id="para_collstart_tr">
				<td class="label"><strong>Collages (started)</strong></td>
				<td>
<? display_paranoia('collages'); ?>
				</td>
			</tr>
			<tr id="para_collcontr_tr">
				<td class="label"><strong>Collages (contributed to)</strong></td>
				<td>
<? display_paranoia('collagecontribs'); ?>
				</td>
			</tr>
			<tr id="para_reqfill_tr">
				<td class="label"><strong>Requests (filled)</strong></td>
				<td>
<?
$RequestsFilledCountChecked = checked(!in_array('requestsfilled_count', $Paranoia));
$RequestsFilledBountyChecked = checked(!in_array('requestsfilled_bounty', $Paranoia));
$RequestsFilledListChecked = checked(!in_array('requestsfilled_list', $Paranoia));
?>
					<label><input type="checkbox" name="p_requestsfilled_count" onchange="AlterParanoia();"<?=$RequestsFilledCountChecked?> /> Show count</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsfilled_bounty" onchange="AlterParanoia();"<?=$RequestsFilledBountyChecked?> /> Show bounty</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsfilled_list" onchange="AlterParanoia();"<?=$RequestsFilledListChecked?> /> Show list</label>
				</td>
			</tr>
			<tr id="para_reqvote_tr">
				<td class="label"><strong>Requests (voted for)</strong></td>
				<td>
<?
$RequestsVotedCountChecked = checked(!in_array('requestsvoted_count', $Paranoia));
$RequestsVotedBountyChecked = checked(!in_array('requestsvoted_bounty', $Paranoia));
$RequestsVotedListChecked = checked(!in_array('requestsvoted_list', $Paranoia));
?>
					<label><input type="checkbox" name="p_requestsvoted_count" onchange="AlterParanoia();"<?=$RequestsVotedCountChecked?> /> Show count</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsvoted_bounty" onchange="AlterParanoia();"<?=$RequestsVotedBountyChecked?> /> Show bounty</label>&nbsp;&nbsp;
					<label><input type="checkbox" name="p_requestsvoted_list" onchange="AlterParanoia();"<?=$RequestsVotedListChecked?> /> Show list</label>
				</td>
			</tr>
			<tr id="para_upltor_tr">
				<td class="label"><strong>Uploaded torrents</strong></td>
				<td>
<? display_paranoia('uploads'); ?>
				</td>
			</tr>
			<tr id="para_uplunique_tr">
				<td class="label"><strong>Uploaded torrents (unique groups)</strong></td>
				<td>
<? display_paranoia('uniquegroups'); ?>
				</td>
			</tr>
			<tr id="para_uplpflac_tr">
				<td class="label"><strong>Uploaded torrents ("perfect" FLACs)</strong></td>
				<td>
<? display_paranoia('perfectflacs'); ?>
				</td>
			</tr>
			<tr id="para_torseed_tr">
				<td class="label"><strong>Torrents (seeding)</strong></td>
				<td>
<? display_paranoia('seeding'); ?>
				</td>
			</tr>
			<tr id="para_torleech_tr">
				<td class="label"><strong>Torrents (leeching)</strong></td>
				<td>
<? display_paranoia('leeching'); ?>
				</td>
			</tr>
			<tr id="para_torsnatch_tr">
				<td class="label"><strong>Torrents (snatched)</strong></td>
				<td>
<? display_paranoia('snatched'); ?>
				</td>
			</tr>
			<tr id="para_torsubscr_tr">
				<td class="label tooltip" title="This option allows other users to subscribe to your torrent uploads."><strong>Torrents (upload subscriptions)</strong></td>
				<td>
					<label><input type="checkbox" name="p_notifications"<?=checked(!in_array('notifications', $Paranoia))?> /> Allow torrent upload subscriptions</label>
				</td>
			</tr>
<?
$DB->query("
	SELECT COUNT(UserID)
	FROM users_info
	WHERE Inviter = '$UserID'");
list($Invited) = $DB->next_record();
?>
			<tr id="para_invited_tr">
				<td class="label tooltip" title="This option controls the display of your <?=SITE_NAME?> invitees."><strong>Invitees</strong></td>
				<td>
					<label><input type="checkbox" name="p_invitedcount"<?=checked(!in_array('invitedcount', $Paranoia))?> /> Show count</label>
				</td>
			</tr>
<?
$DB->query("
	SELECT COUNT(ArtistID)
	FROM torrents_artists
	WHERE UserID = $UserID");
list($ArtistsAdded) = $DB->next_record();
?>
			<tr id="para_artistsadded_tr">
				<td class="label tooltip" title="This option controls the display of the artists you have added to torrent groups on the site. This number includes artists added via the torrent upload form, as well as artists added via the &quot;Add artists&quot; box on torrent group pages."><strong>Artists added</strong></td>
				<td>
					<label><input type="checkbox" name="p_artistsadded"<?=checked(!in_array('artistsadded', $Paranoia))?> /> Show count</label>
				</td>
			</tr>
			<tr id="para_preview_tr">
				<td></td>
				<td><a href="#" id="preview_paranoia" class="brackets">Preview paranoia</a></td>
			</tr>
		</table>
		<table cellpadding="6" cellspacing="1" border="0" width="100%" class="layout border user_options" id="access_settings">
			<tr class="colhead_dark">
				<td colspan="2">
					<strong>Access Settings</strong>
				</td>
			</tr>
			<tr id="acc_resetpk_tr">
				<td class="label tooltip_interactive" title="For information about the function of your passkey, please &lt;a href=&quot;<?=site_url()?>wiki.php?action=article&amp;amp;name=Passkey&quot;&gt;read this wiki article&lt;/a&gt;." data-title-plain="For information about the function of your passkey, please read the &quot;Passkey&quot; wiki article."><strong>Reset passkey</strong></td>
				<td>
					<div class="field_div">
						<label><input type="checkbox" name="resetpasskey" id="resetpasskey" />
						Reset your passkey?</label>
					</div>
					<p class="min_padding">Any active torrents must be downloaded again to continue leeching/seeding.</p>
				</td>
			</tr>
			<tr id="acc_irckey_tr">
				<td class="label"><strong>IRC key</strong></td>
				<td>
					<div class="field_div">
						<input type="text" size="50" name="irckey" id="irckey" value="<?=display_str($IRCKey)?>" />
					</div>
					<p class="min_padding">If set, this key will be used instead of your site password when authenticating with <?=BOT_NICK?> on the <a href="wiki.php?action=article&amp;id=30">site's IRC network</a>. <span style="white-space: nowrap;">Please note:</span></p>
					<ul>
						<li>This value is stored in plaintext and should not be your password.</li>
						<li>IRC keys must be between 6 and 32 characters.</li>
					</ul>
				</td>
			</tr>
			<tr id="acc_email_tr">
				<td class="label tooltip" title="This is the email address you want associated with your <?=SITE_NAME?> account. It will be used if you forget your password or if an alert needs to be sent to you."><strong>Email address</strong></td>
				<td>
					<div class="field_div">
						<input type="email" size="50" name="email" id="email" value="<?=display_str($Email)?>" />
					</div>
					<p class="min_padding">When changing your email address, you must enter your current password in the "Current password" field before saving your changes.</p>
				</td>
			</tr>
			<tr id="acc_password_tr">
				<td class="label"><strong>Change password</strong></td>
				<td>
					<div class="field_div">
						<label>Current password:<br />
						<input type="password" size="40" name="cur_pass" id="cur_pass" value="" /></label>
					</div>
					<div class="field_div">
						<label>New password:<br />
						<input type="password" size="40" name="new_pass_1" id="new_pass_1" value="" /> <strong id="pass_strength"></strong></label>
					</div>
					<div class="field_div">
						<label>Confirm new password:<br />
						<input type="password" size="40" name="new_pass_2" id="new_pass_2" value="" /> <strong id="pass_match"></strong></label>
					</div>
					<div class="setting_description">
						A strong password:
						<ul>
							<li>is 8 characters or longer</li>
							<li>contains at least 1 lowercase and uppercase letter</li>
							<li>contains at least a number or symbol</li>
						</ul>
					</div>
				</td>
			</tr>
		</table>
	</div>
	</form>
</div>
<? View::show_footer(); ?>
