<?
if (!check_perms('users_mod')) {
	error(403);
}

/**
 * Used for rendering a single table row in the staff toolbox
 *
 * @param string $Title - the displayed name of the tool
 * @param string $URL - the relative URL of the tool
 * @param bool $HasPermission - whether the user has permission to view/use the tool
 * @param string $Tooltip - optional tooltip
 *
 */
function create_row($Title, $URL, $HasPermission = false, $Tooltip = false) {
	$TooltipHTML = $Tooltip !== false ? " class=\"tooltip\" title=\"$Tooltip\"" : "";

	if ($HasPermission) {
		echo "\t\t\t\t<tr><td><a href=\"$URL\"$TooltipHTML>$Title</a></td></tr>\n";
	}
}

View::show_header('Staff Tools');
?>
<div class="permissions">
	<div class="permission_container">
	<!-- begin left column -->
		<div class="permission_container">
			<table class="layout">
				<tr class="colhead"><td>Announcements</td></tr>
<?
				create_row("Calendar", "tools.php?action=calendar", Calendar::can_view());
				create_row("Change log", "tools.php?action=change_log", check_perms("users_mod"));
				create_row("Global notification", "tools.php?action=global_notification", check_perms("users_mod"));
				create_row("News post", "tools.php?action=news", check_perms("admin_manage_news"));
				create_row("Vanity House additions", "tools.php?action=recommend", check_perms("site_recommend_own") || check_perms("site_manage_recommendations"));
?>
			</table>
		</div>
		<div class="permission_container">
			<table class="layout">
				<tr class="colhead"><td>Community</td></tr>
<?
				create_row("Forum manager", "tools.php?action=forum", check_perms("admin_manage_forums"));
				create_row("Permissions manager", "tools.php?action=permissions", check_perms("admin_manage_permissions"));
				create_row("Special users", "tools.php?action=special_users", check_perms("admin_manage_permissions"));
?>
			</table>
		</div>
		<div class="permission_container">
			<table class="layout">
				<tr class="colhead"><td>Finances</td></tr>
<?
				create_row("Bitcoin donations (balance)", "tools.php?action=bitcoin_balance", check_perms("admin_donor_log"));
				create_row("Bitcoin donations (unprocessed)", "tools.php?action=bitcoin_unproc", check_perms("admin_donor_log"));
				create_row("Donation log", "tools.php?action=donation_log", check_perms("admin_donor_log"));
				create_row("Donor rewards", "tools.php?action=donor_rewards", check_perms("users_mod"));
?>
			</table>
		</div>
		<div class="permission_container">
			<table class="layout">
				<tr class="colhead"><td>Administration</td></tr>
<?
				create_row("Client whitelist", "tools.php?action=whitelist", check_perms("admin_whitelist"));
				create_row("Create user", "tools.php?action=create_user", check_perms("admin_create_users"));
				create_row("Global notification", "tools.php?action=global_notification", check_perms("users_mod"));
				create_row("Mass PM", "tools.php?action=mass_pm", check_perms("users_mod"));
?>
			</table>
		</div>
	<!-- end left column -->
	</div>
	<div class="permission_container">
	<!-- begin middle column -->
		<div class="permission_container">
			<table class="layout">
<?
				<tr class="colhead"><td>Queue</td></tr>
?>
<?				create_row("Login watch", "tools.php?action=login_watch", check_perms("admin_login_watch"));
?>
			</table>
		</div>
		<div class="permission_container">
			<table class="layout">
<?
				<tr class="colhead"><td>Managers</td></tr>
?>
<?				create_row("Email blacklist", "tools.php?action=email_blacklist", check_perms("users_view_email"));
				create_row("IP address bans", "tools.php?action=ip_ban", check_perms("admin_manage_ipbans"));
				create_row("Duplicate IP addresses", "tools.php?action=dupe_ips", check_perms("users_view_ips"));
				create_row("Manipulate invite tree", "tools.php?action=manipulate_tree", check_perms("users_mod"));
?>
			</table>
		</div>
	<!-- end middle column -->
	</div>
	<div class="permission_container">
	<!-- begin right column -->
		<div class="permission_container">
			<table class="layout">
				<tr class="colhead"><td>Site Information</td></tr>
<?
				create_row("Economic stats", "tools.php?action=economic_stats", check_perms("site_view_flow"));
				create_row("Invite pool", "tools.php?action=invite_pool", check_perms("users_view_invites"));
				create_row("Registration log", "tools.php?action=registration_log", check_perms("users_view_ips") && check_perms("users_view_email"));
				create_row("Torrent stats", "tools.php?action=torrent_stats", check_perms("site_view_flow"));
//				create_row("Upscale pool", "tools.php?action=upscale_pool", check_perms("site_view_flow"));
				create_row("User flow", "tools.php?action=user_flow", check_perms("site_view_flow"));
?>
			</table>
		</div>
		<div class="permission_container">
			<table class="layout">
				<tr class="colhead"><td>Torrents</td></tr>
<?
				create_row("\"Do Not Upload\" list", "tools.php?action=dnu", check_perms("admin_dnu"));
				create_row("Manage freeleech tokens", "tools.php?action=tokens", check_perms("users_mod"));
				create_row("Label aliases", "tools.php?action=label_aliases", check_perms("users_mod"));
				create_row("Tag aliases", "tools.php?action=tag_aliases", check_perms("users_mod"));
				create_row("Batch tag editor", "tools.php?action=edit_tags", check_perms("users_mod"));
				create_row("Official tags manager", "tools.php?action=official_tags", check_perms("users_mod"));
?>
			</table>
		</div>
		<div class="permission_container">
			<table class="layout">
				<tr class="colhead"><td>Development</td></tr>
<?
				create_row("Clear/view a cache key", "tools.php?action=clear_cache", check_perms("users_mod"));
				create_row("Opcode stats", "tools.php?action=opcode_stats", check_perms("site_debug"));
				create_row("PHP processes", "tools.php?action=process_info", check_perms("site_debug"));
				create_row("Rerender stylesheet gallery images", "tools.php?action=rerender_gallery", check_perms("admin_clear_cache") || check_perms("users_mod"));
				create_row("Schedule", "schedule.php?auth=$LoggedUser[AuthKey]", check_perms("site_debug"));
				create_row("Service stats", "tools.php?action=service_stats", check_perms("site_debug"));
				create_row("Update GeoIP", "tools.php?action=update_geoip", check_perms("admin_update_geoip"));
?>
			</table>
		</div>
		<div class="permission_container">
			<table class="layout">
				<tr class="colhead"><td>Developer Sandboxes</td></tr>
<?
				create_row("Sandbox (1)", "tools.php?action=sandbox1", check_perms("site_debug"));
				create_row("Sandbox (2)", "tools.php?action=sandbox2", check_perms("site_debug"));
				create_row("Sandbox (3)", "tools.php?action=sandbox3", check_perms("site_debug"));
				create_row("Sandbox (4)", "tools.php?action=sandbox4", check_perms("site_debug"));
				create_row("Sandbox (5)", "tools.php?action=sandbox5", check_perms("site_debug"));
				create_row("Sandbox (6)", "tools.php?action=sandbox6", check_perms("site_debug"));
				create_row("Sandbox (7)", "tools.php?action=sandbox7", check_perms("site_debug"));
				create_row("Sandbox (8)", "tools.php?action=sandbox8", check_perms("site_debug"));
				create_row("BBCode sandbox", "tools.php?action=bbcode_sandbox", check_perms("users_mod"));
				create_row("Public sandbox", "tools.php?action=public_sandbox", check_perms("users_mod"), "Do not click this!");
				create_row("Mod-level sandbox", "tools.php?action=mod_sandbox", check_perms("users_mod"), "Do not click this!");
?>
			</table>
		</div>
	<!-- end right column -->
	</div>
</div>
<? View::show_footer(); ?>
