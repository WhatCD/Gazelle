<?

/********************************************************************************
 ************ Permissions form ********************** user.php and tools.php ****
 ********************************************************************************
 ** This function is used to create both the class permissions form, and the   **
 ** user custom permissions form.											  **
 ********************************************************************************/

$PermissionsArray = array(
	'site_leech' => 'Can leech (Does this work?).',
	'site_upload' => 'Upload torrent access.',
	'site_vote' => 'Request vote access.',
	'site_submit_requests' => 'Request create access.',
	'site_advanced_search' => 'Advanced search access.',
	'site_top10' => 'Top 10 access.',
	'site_advanced_top10' => 'Advanced Top 10 access.',
	'site_album_votes' => 'Voting for favorite torrents.',
	'site_torrents_notify' => 'Notifications access.',
	'site_collages_create' => 'Collage create access.',
	'site_collages_manage' => 'Collage manage access.',
	'site_collages_delete' => 'Collage delete access.',
	'site_collages_subscribe' => 'Collage subscription access.',
	'site_collages_personal' => 'Can have a personal collage.',
	'site_collages_renamepersonal' => 'Can rename own personal collages.',
	'site_make_bookmarks' => 'Bookmarks access.',
	'site_edit_wiki' => 'Wiki edit access.',
	'site_can_invite_always' => 'Can invite past user limit.',
	'site_send_unlimited_invites' => 'Unlimited invites.',
	'site_moderate_requests' => 'Request moderation access.',
	'site_delete_artist' => 'Can delete artists (must be able to delete torrents+requests).',
	'site_moderate_forums' => 'Forum moderation access.',
	'site_admin_forums' => 'Forum administrator access.',
	'site_forums_double_post' => 'Can double post in the forums.',
	'site_view_flow' => 'Can view stats and data pools.',
	'site_view_full_log' => 'Can view old log entries.',
	'site_view_torrent_snatchlist' => 'Can view torrent snatch lists.',
	'site_recommend_own' => 'Can recommend own torrents.',
	'site_manage_recommendations' => 'Recommendations management access.',
	'site_delete_tag' => 'Can delete tags.',
	'site_disable_ip_history' => 'Disable IP history.',
	'zip_downloader' => 'Download multiple torrents at once.',
	'site_debug' => 'Developer access.',
	'site_proxy_images' => 'Image proxy & anti-canary.',
	'site_search_many' => 'Can go past low limit of search results.',
	'users_edit_usernames' => 'Can edit usernames.',
	'users_edit_ratio' => 'Can edit anyone\'s upload/download amounts.',
	'users_edit_own_ratio' => 'Can edit own upload/download amounts.',
	'users_edit_titles' => 'Can edit titles.',
	'users_edit_avatars' => 'Can edit avatars.',
	'users_edit_invites' => 'Can edit invite numbers and cancel sent invites.',
	'users_edit_watch_hours' => 'Can edit contrib watch hours.',
	'users_edit_reset_keys' => 'Can reset passkey/authkey.',
	'users_edit_profiles' => 'Can edit anyone\'s profile.',
	'users_view_friends' => 'Can view anyone\'s friends.',
	'users_reset_own_keys' => 'Can reset own passkey/authkey.',
	'users_edit_password' => 'Can change passwords.',
	'users_promote_below' => 'Can promote users to below current level.',
	'users_promote_to' => 'Can promote users up to current level.',
	'users_give_donor' => 'Can give donor access.',
	'users_warn' => 'Can warn users.',
	'users_disable_users' => 'Can disable users.',
	'users_disable_posts' => 'Can disable users\' posting privileges.',
	'users_disable_any' => 'Can disable any users\' rights.',
	'users_delete_users' => 'Can delete users.',
	'users_view_invites' => 'Can view who user has invited.',
	'users_view_seedleech' => 'Can view what a user is seeding or leeching.',
	'users_view_uploaded' => 'Can view a user\'s uploads, regardless of privacy level.',
	'users_view_keys' => 'Can view passkeys.',
	'users_view_ips' => 'Can view IP addresses.',
	'users_view_email' => 'Can view email addresses.',
	'users_invite_notes' => 'Can add a staff note when inviting someone.',
	'users_override_paranoia' => 'Can override paranoia.',
	'users_logout' => 'Can log users out (old?).',
	'users_make_invisible' => 'Can make users invisible.',
	'users_mod' => 'Basic moderator tools.',
	'torrents_edit' => 'Can edit any torrent.',
	'torrents_delete' => 'Can delete torrents.',
	'torrents_delete_fast' => 'Can delete more than 3 torrents at a time.',
	'torrents_freeleech' => 'Can make torrents freeleech.',
	'torrents_search_fast' => 'Rapid search (for scripts).',
	'torrents_hide_dnu' => 'Hide the Do Not Upload list by default.',
	'torrents_fix_ghosts' => 'Can fix "ghost" groups on artist pages.',
	'admin_manage_news' => 'Can manage site news.',
	'admin_manage_blog' => 'Can manage the site blog.',
	'admin_manage_polls' => 'Can manage polls.',
	'admin_manage_forums' => 'Can manage forums (add/edit/delete).',
	'admin_manage_fls' => 'Can manage FLS.',
	'admin_reports' => 'Can access reports system.',
	'admin_advanced_user_search' => 'Can access advanced user search.',
	'admin_create_users' => 'Can create users through an administrative form.',
	'admin_donor_log' => 'Can view the donor log.',
	'admin_manage_ipbans' => 'Can manage IP bans.',
	'admin_dnu' => 'Can manage do not upload list.',
	'admin_clear_cache' => 'Can clear cached.',
	'admin_whitelist' => 'Can manage the list of allowed clients.',
	'admin_manage_permissions' => 'Can edit permission classes/user permissions.',
	'admin_schedule' => 'Can run the site schedule.',
	'admin_login_watch' => 'Can manage login watch.',
	'admin_manage_wiki' => 'Can manage wiki access.',
	'admin_update_geoip' => 'Can update geoIP data.',
	'site_collages_recover' => 'Can recover \'deleted\' collages.',
	'torrents_add_artist' => 'Can add artists to any group.',
	'edit_unknowns' => 'Can edit unknown release information.',
	'forums_polls_create' => 'Can create polls in the forums.',
	'forums_polls_moderate' => 'Can feature and close polls.',
	'project_team' => 'Is part of the project team.',
	'torrents_edit_vanityhouse' => 'Can mark groups as part of Vanity House.',
	'artist_edit_vanityhouse' => 'Can mark artists as part of Vanity House.',
	'site_tag_aliases_read' => 'Can view the list of tag aliases.'
);

function permissions_form() {
?>
<div class="permissions">
	<div class="permission_container">
		<table>
			<tr class="colhead">
				<td>Site</td>
			</tr>
			<tr>
				<td>
<?
					display_perm('site_leech','Can leech.');
					display_perm('site_upload','Can upload.');
					display_perm('site_vote','Can vote on requests.');
					display_perm('site_submit_requests','Can submit requests.');
					display_perm('site_advanced_search','Can use advanced search.');
					display_perm('site_top10','Can access top 10.');
					display_perm('site_torrents_notify','Can access torrents notifications system.');
					display_perm('site_collages_create','Can create collages.');
					display_perm('site_collages_manage','Can manage collages (add torrents, sorting).');
					display_perm('site_collages_delete','Can delete collages.');
					display_perm('site_collages_subscribe','Can access collage subscriptions.');
					display_perm('site_collages_personal','Can have a personal collage.');
					display_perm('site_collages_renamepersonal','Can rename own personal collages.');
					display_perm('site_advanced_top10','Can access advanced top 10.');
					display_perm('site_album_votes', 'Can vote for favorite torrents.');
					display_perm('site_make_bookmarks','Can make bookmarks.');
					display_perm('site_edit_wiki','Can edit wiki pages.');
					display_perm('site_can_invite_always', 'Can invite users even when invites are closed.');
					display_perm('site_send_unlimited_invites', 'Can send unlimited invites.');
					display_perm('site_moderate_requests', 'Can moderate any request.');
					display_perm('site_delete_artist', 'Can delete artists (must be able to delete torrents+requests).');
					display_perm('forums_polls_create','Can create polls in the forums.');
					display_perm('forums_polls_moderate','Can feature and close polls.');
					display_perm('site_moderate_forums', 'Can moderate the forums.');
					display_perm('site_admin_forums', 'Can administrate the forums.');
					display_perm('site_view_flow', 'Can view site stats and data pools.');
					display_perm('site_view_full_log', 'Can view the full site log.');
					display_perm('site_view_torrent_snatchlist', 'Can view torrent snatch lists.');
					display_perm('site_recommend_own', 'Can add own torrents to recommendations list.');
					display_perm('site_manage_recommendations', 'Can edit recommendations list.');
					display_perm('site_delete_tag', 'Can delete tags.');
					display_perm('site_disable_ip_history', 'Disable IP history.');
					display_perm('zip_downloader', 'Download multiple torrents at once.');
					display_perm('site_debug', 'View site debug tables.');
					display_perm('site_proxy_images', 'Proxy images through the server.');
					display_perm('site_search_many', 'Can go past low limit of search results.');
					display_perm('site_collages_recover', 'Can recover \'deleted\' collages.');
					display_perm('site_forums_double_post', 'Can double post in the forums.');
					display_perm('project_team', 'Part of the project team.');
					display_perm('site_tag_aliases_read', 'Can view the list of tag aliases.');
?>
				</td>
			</tr>
		</table>
	</div>
	<div class="permission_container">
		<table>
			<tr class="colhead">
				<td>Users</td>
			</tr>
			<tr>
				<td>
<?
					display_perm('users_edit_usernames', 'Can edit usernames.');
					display_perm('users_edit_ratio', 'Can edit anyone\'s upload/download amounts.');
					display_perm('users_edit_own_ratio', 'Can edit own upload/download amounts.');
					display_perm('users_edit_titles', 'Can edit titles.');
					display_perm('users_edit_avatars', 'Can edit avatars.');
					display_perm('users_edit_invites', 'Can edit invite numbers and cancel sent invites.');
					display_perm('users_edit_watch_hours', 'Can edit contrib watch hours.');
					display_perm('users_edit_reset_keys', 'Can reset any passkey/authkey.');
					display_perm('users_edit_profiles', 'Can edit anyone\'s profile.');
					display_perm('users_view_friends', 'Can view anyone\'s friends.');
					display_perm('users_reset_own_keys', 'Can reset own passkey/authkey.');
					display_perm('users_edit_password', 'Can change password.');
					display_perm('users_promote_below', 'Can promote users to below current level.');
					display_perm('users_promote_to', 'Can promote users up to current level.');
					display_perm('users_give_donor', 'Can give donor access.');
					display_perm('users_warn', 'Can warn users.');
					display_perm('users_disable_users', 'Can disable users.');
					display_perm('users_disable_posts', 'Can disable users\' posting privileges.');
					display_perm('users_disable_any', 'Can disable any users\' rights.');
					display_perm('users_delete_users', 'Can delete anyone\'s account');
					display_perm('users_view_invites', 'Can view who user has invited');
					display_perm('users_view_seedleech', 'Can view what a user is seeding or leeching');
					display_perm('users_view_uploaded', 'Can view a user\'s uploads, regardless of privacy level');
					display_perm('users_view_keys', 'Can view passkeys');
					display_perm('users_view_ips', 'Can view IP addresses');
					display_perm('users_view_email', 'Can view email addresses');
					display_perm('users_invite_notes', 'Can add a staff note when inviting someone.');
					display_perm('users_override_paranoia', 'Can override paranoia');
					display_perm('users_make_invisible', 'Can make users invisible');
					display_perm('users_logout', 'Can log users out');
					display_perm('users_mod', 'Can access basic moderator tools (Admin comment)');
?>
					*Everything is only applicable to users with the same or lower class level
				</td>
			</tr>
		</table>
	</div>
	<div class="permission_container">
		<table>
			<tr class="colhead">
				<td>Torrents</td>
			</tr>
			<tr>
				<td>
<?
					display_perm('torrents_edit', 'Can edit any torrent');
					display_perm('torrents_delete', 'Can delete torrents');
					display_perm('torrents_delete_fast', 'Can delete more than 3 torrents at a time.');
					display_perm('torrents_freeleech', 'Can make torrents freeleech');
					display_perm('torrents_search_fast', 'Unlimit search frequency (for scripts).');
					display_perm('torrents_add_artist', 'Can add artists to any group.');
					display_perm('edit_unknowns', 'Can edit unknown release information.');
					display_perm('torrents_edit_vanityhouse', 'Can mark groups as part of Vanity House.');
					display_perm('artist_edit_vanityhouse', 'Can mark artists as part of Vanity House.');
					display_perm('torrents_hide_dnu', 'Hide the Do Not Upload list by default.');
					display_perm('torrents_fix_ghosts', 'Can fix ghost groups on artist pages.');
?>
				</td>
			</tr>
		</table>
	</div>
	<div class="permission_container">
		<table>
			<tr class="colhead">
				<td>Administrative</td>
			</tr>
			<tr>
				<td>
<?
					display_perm('admin_manage_news', 'Can manage site news');
					display_perm('admin_manage_blog', 'Can manage the site blog');
					display_perm('admin_manage_polls', 'Can manage polls');
					display_perm('admin_manage_forums', 'Can manage forums (add/edit/delete)');
					display_perm('admin_manage_fls', 'Can manage FLS');
					display_perm('admin_reports', 'Can access reports system');
					display_perm('admin_advanced_user_search', 'Can access advanced user search');
					display_perm('admin_create_users', 'Can create users through an administrative form');
					display_perm('admin_donor_log', 'Can view the donor log');
					display_perm('admin_manage_ipbans', 'Can manage IP bans');
					display_perm('admin_dnu', 'Can manage do not upload list');
					display_perm('admin_clear_cache', 'Can clear cached pages');
					display_perm('admin_whitelist', 'Can manage the list of allowed clients.');
					display_perm('admin_manage_permissions', 'Can edit permission classes/user permissions.');
					display_perm('admin_schedule', 'Can run the site schedule.');
					display_perm('admin_login_watch', 'Can manage login watch.');
					display_perm('admin_manage_wiki', 'Can manage wiki access.');
					display_perm('admin_update_geoip', 'Can update geoIP data.');
?>
				</td>
			</tr>
		</table>
	</div>
	<div class="submit_container"><input type="submit" name="submit" value="Save Permission Class" /></div>
</div>
<?
}
