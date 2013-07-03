<?
if (!check_perms('users_mod')) {
	error(403);
}
View::show_header('Staff Tools');
?>
<div class="permissions">
	<div class="permission_container">
		<table class="layout">
			<tr class="colhead"><td>Managers</td></tr>
<?	if (check_perms('admin_manage_permissions')) { ?>
			<tr><td><a href="tools.php?action=permissions">Permissions</a></td></tr>
<?	} if (check_perms('admin_whitelist')) { ?>
			<tr><td><a href="tools.php?action=whitelist">Client whitelist</a></td></tr>
<?	} if (check_perms('admin_manage_ipbans')) { ?>
			<tr><td><a href="tools.php?action=ip_ban">IP address bans</a></td></tr>
<?	} if (check_perms('admin_login_watch')) { ?>
			<tr><td><a href="tools.php?action=login_watch">Login watch</a></td></tr>
<?	} if (check_perms('admin_manage_forums')) { ?>
			<tr><td><a href="tools.php?action=forum">Forums</a></td></tr>
<?	} if (check_perms('admin_manage_news')) { ?>
			<tr><td><a href="tools.php?action=news">News</a></td></tr>
<?	} if (check_perms('admin_dnu')) { ?>
			<tr><td><a href="tools.php?action=dnu">"Do Not Upload" list</a></td></tr>
<?	} if (check_perms('site_recommend_own') || check_perms('site_manage_recommendations')) { ?>
			<tr><td><a href="tools.php?action=recommend">Vanity House additions</a></td></tr>
<?	} if (check_perms('users_view_email')) { ?>
			<tr><td><a href="tools.php?action=email_blacklist">Email blacklist</a></td></tr>
<?	} if (check_perms('users_mod')) { ?>
			<tr><td><a href="tools.php?action=tokens">Manage freeleech tokens</a></td></tr>
			<tr><td><a href="tools.php?action=official_tags">Official tags manager</a></td></tr>
			<tr><td><a href="tools.php?action=tag_aliases">Tag aliases</a></td></tr>
<?	} if (check_perms('users_mod') || $LoggedUser['ExtraClasses'][DELTA_TEAM]) { ?>
			<tr><td><a href="tools.php?action=label_aliases">Label aliases</a></td></tr>
<?	} if (check_perms('users_mod')) { ?>
			<tr><td><a href="tools.php?action=change_log">Change log</a></td></tr>
<?	} ?>
		</table>
	</div>
	<div class="permission_container">
		<table class="layout">
			<tr class="colhead"><td>Data</td></tr>
<?	if (check_perms('admin_donor_log')) { ?>
			<tr><td><a href="tools.php?action=donation_log">Donation log</a></td></tr>
			<tr><td><a href="tools.php?action=bitcoin_balance">Bitcoin donation balance</a></td></tr>
<?	} if (check_perms('users_view_ips') && check_perms('users_view_email')) { ?>
			<tr><td><a href="tools.php?action=registration_log">Registration log</a></td></tr>
<?	} if (check_perms('users_view_invites')) { ?>
			<tr><td><a href="tools.php?action=invite_pool">Invite pool</a></td></tr>
<?	} if (check_perms('site_view_flow')) { ?>
			<tr><td><a href="tools.php?action=upscale_pool">Upscale pool</a></td></tr>
			<tr><td><a href="tools.php?action=user_flow">User flow</a></td></tr>
			<tr><td><a href="tools.php?action=torrent_stats">Torrent stats</a></td></tr>
			<tr><td><a href="tools.php?action=economic_stats">Economic stats</a></td></tr>
<?	} if (check_perms('site_debug')) { ?>
			<tr><td><a href="tools.php?action=opcode_stats">Opcode stats</a></td></tr>
			<tr><td><a href="tools.php?action=service_stats">Service stats</a></td></tr>
<?	} if (check_perms('admin_manage_permissions')) { ?>
			<tr><td><a href="tools.php?action=special_users">Special users</a></td></tr>
<?	} ?>
		</table>
	</div>
	<div class="permission_container">
		<table class="layout">
			<tr class="colhead"><td>Misc</td></tr>
<?	if (check_perms('users_mod')) { ?>
			<tr><td><a href="tools.php?action=edit_tags">Batch tag editor</a></td></tr>
<?	} if (check_perms('users_mod')) { ?>
			<tr><td><a href="tools.php?action=manipulate_tree">Manipulate tree</a></td></tr>
<?	} if (check_perms('admin_update_geoip')) { ?>
			<tr><td><a href="tools.php?action=update_geoip">Update GeoIP </a></td></tr>
<?	} if (check_perms('admin_create_users')) { ?>
			<tr><td><a href="tools.php?action=create_user">Create user</a></td></tr>
<?	} if (check_perms('users_mod')) { ?>
			<tr><td><a href="tools.php?action=clear_cache">Clear/view a cache key</a></td></tr>
<?	} if (check_perms('users_view_ips')) { ?>
			<tr><td><a href="tools.php?action=dupe_ips">Duplicate IP addresses</a></td></tr>
<?	} if (check_perms('site_debug')) { ?>
			<tr><td><a href="tools.php?action=process_info">PHP processes</a></td></tr>
			<tr><td><a href="tools.php?action=sandbox1">Sandbox (1)</a></td></tr>
			<tr><td><a href="tools.php?action=sandbox2">Sandbox (2)</a></td></tr>
			<tr><td><a href="tools.php?action=sandbox3">Sandbox (3)</a></td></tr>
			<tr><td><a href="tools.php?action=sandbox4">Sandbox (4)</a></td></tr>
			<tr><td><a href="tools.php?action=sandbox5">Sandbox (5)</a></td></tr>
			<tr><td><a href="tools.php?action=sandbox6">Sandbox (6)</a></td></tr>
			<tr><td><a href="tools.php?action=sandbox7">Sandbox (7)</a></td></tr>
			<tr><td><a href="tools.php?action=sandbox8">Sandbox (8)</a></td></tr>
			<tr><td><a href="schedule.php?auth=<?=$LoggedUser['AuthKey']?>">Schedule</a></td></tr>
<?	} if (check_perms('admin_clear_cache') || check_perms('users_mod')) { ?>
			<tr><td><a href="tools.php?action=rerender_gallery">Rerender stylesheet gallery images</a></td></tr>
<?	} if (check_perms('users_mod')) { ?>
			<tr><td><strong><a href="tools.php?action=public_sandbox">Public sandbox</a></strong></td></tr>
<?	} if (check_perms('users_mod')) { ?>
			<tr><td><strong><a href="tools.php?action=mod_sandbox">Mod-level sandbox</a></strong></td></tr>
<?	} ?>
		</table>
	</div>
</div>
<? View::show_footer(); ?>
