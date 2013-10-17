<?

class NotificationsManagerView {
	private static $Settings;

	public static function load_js() {
		$JSIncludes = array(
			'noty/noty.js',
			'noty/layouts/bottomRight.js',
			'noty/themes/default.js',
			'user_notifications.js');
		foreach ($JSIncludes as $JSInclude) {
			$Path = STATIC_SERVER."functions/$JSInclude";
?>
	<script src="<?=$Path?>?v=<?=filemtime(SERVER_ROOT."/$Path")?>" type="text/javascript"></script>
<?
		}
	}

	public static function render_settings($Settings) {
		self::$Settings = $Settings;
?>
		<tr>
			<td class="label">
				<strong>News announcements</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::NEWS); ?>
			</td>
		</tr>
		<tr>
			<td class="label">
				<strong>Blog announcements</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::BLOG); ?>
			</td>
		</tr>
		<tr>
			<td class="label">
				<strong>Inbox messages</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::INBOX, true); ?>
			</td>
		</tr>
		<tr>
			<td class="label tooltip" title="Enabling this will give you a notification when you receive a new private message from a member of the <?=SITE_NAME?> staff.">
				<strong>Staff messages</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::STAFFPM); ?>
			</td>
		</tr>
		<tr>
			<td class="label">
				<strong>Thread subscriptions</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::SUBSCRIPTIONS); ?>
			</td>
		</tr>
		<tr>
			<td class="label tooltip" title="Enabling this will give you a notification whenever someone quotes you in the forums.">
				<strong>Quote notifications</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::QUOTES); ?>
			</td>
		</tr>
<? 		if (check_perms('site_torrents_notify')) { ?>
			<tr>
				<td class="label tooltip" title="Enabling this will give you a notification when the torrent notification filters you have established are triggered.">
					<strong>Torrent notifications</strong>
				</td>
				<td>
<?					self::render_checkbox(NotificationsManager::TORRENTS, true); ?>
				</td>
			</tr>
<?		} ?>
		<tr>
			<td class="label tooltip" title="Enabling this will give you a notification when a torrent is added to a collage you are subscribed to.">
				<strong>Collage subscriptions</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::COLLAGES); ?>
			</td>
		</tr>
<? /**
		<tr>
			<td class="label tooltip" title="">
				<strong>Site alerts</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::SITEALERTS); ?>
			</td>
		</tr>
		<tr>
			<td class="label tooltip" title="">
				<strong>Forum alerts</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::FORUMALERTS); ?>
			</td>
		</tr>
		<tr>
			<td class="label tooltip" title="">
				<strong>Request alerts</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::REQUESTALERTS); ?>
			</td>
		</tr>
		<tr>
			<td class="label tooltip" title="">
				<strong>Collage alerts</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::COLLAGEALERTS); ?>
			</td>
		</tr>
		<tr>
			<td class="label tooltip" title="">
				<strong>Torrent alerts</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::TORRENTALERTS); ?>
			</td>
		</tr>
<? **/
	}

	private static function render_checkbox($Name, $Both = false) {
		$Checked = self::$Settings[$Name];
		if ($Both) {
			$IsChecked = $Checked == NotificationsManager::OPT_TRADITIONAL ? ' checked="checked"' : '';
?>
			<label>
				<input type="checkbox" value="1" name="notifications_<?=$Name?>_traditional" id="notifications_<?=$Name?>_traditional"<?=$IsChecked?> />
				Traditional
			</label>
<?
		}
		$IsChecked = $Checked == NotificationsManager::OPT_POPUP || !isset($Checked) ? ' checked="checked"' : '';
?>
			<label>
				<input type="checkbox" name="notifications_<?=$Name?>_popup" id="notifications_<?=$Name?>_popup"<?=$IsChecked?> />
				Pop-up
			</label>
<?
	}

	public static function format_traditional($Contents) {
		return "<a href=\"$Contents[url]\">$Contents[message]</a>";
	}
}
