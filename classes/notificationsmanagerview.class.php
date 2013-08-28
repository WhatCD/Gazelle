<?

class NotificationsManagerView {
	private static $Settings;

	public static function load_js() { ?>
		<script type="text/javascript" src="<?=STATIC_SERVER?>functions/noty/noty.js"></script>
		<script type="text/javascript" src="<?=STATIC_SERVER?>functions/noty/layouts/bottomRight.js"></script>
		<script type="text/javascript" src="<?=STATIC_SERVER?>functions/noty/themes/default.js"></script>
		<script type="text/javascript" src="<?=STATIC_SERVER?>functions/user_notifications.js"></script>
<?
	}


	public static function render_settings($Settings) {
		self::$Settings = $Settings;
?>
		<tr>
			<td class="label tooltip" title="Enabling this will give you a notification when a new sitewide news announcement is made.">
				<strong>News announcements</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::NEWS); ?>
			</td>
		</tr>
		<tr>
			<td class="label tooltip" title="Enabling this will give you a notification when a new sitewide blog post is made.">
				<strong>Blog announcements</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::BLOG); ?>
			</td>
		</tr>
		<tr>
			<td class="label tooltip" title="Enabling this will give you a notification when you receive a new private message.">
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
			<td class="label tooltip" title="Enabling this will give you a notification when a thread you have subscribed to receives a new post.">
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

	/*
	 * FIXME: The use of radio buttons with different "name" attributes is an ugly
	 *		workaround for how NotificationsManager::save_settings() is coded.
	 */
	private static function render_checkbox($Name, $Both = false) {
		$Checked = self::$Settings[$Name];
		if ($Both) {
			$IsChecked = $Checked == 2 ? ' checked="checked"' : '';
?>
			<input type="radio" value="1" name="notifications_<?=$Name?>_traditional" id="notifications_<?=$Name?>_traditional"<?=$IsChecked?> />
			<label for="notifications_<?=$Name?>_traditional">Traditional</label>
<?
		}
		$IsChecked = $Checked == 1 || !isset($Checked) ? ' checked="checked"' : '';
?>
			<input <?=$Both ? 'type="radio" value="1"' : 'type="checkbox"'?> name="notifications_<?=$Name?>_popup" id="notifications_<?=$Name?>_popup"<?=$IsChecked?> />
			<label for="notifications_<?=$Name?>_popup">Pop-up</label>
<?
	}

	public static function format_traditional($Contents) {
		return '<a href="' . $Contents['url'] . '">' . $Contents['message'] . '</a>';
	}
}
