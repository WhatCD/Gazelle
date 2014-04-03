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

	private static function render_push_settings() {
		$PushService = self::$Settings['PushService'];
		$PushOptions = unserialize(self::$Settings['PushOptions']);
		if (empty($PushOptions['PushDevice'])) {
			$PushOptions['PushDevice'] = '';
		}
		?>
		<tr>
			<td class="label"><strong>Push notifications</strong></td>
			<td>
				<select name="pushservice" id="pushservice">
					<option value="0"<? if (empty($PushService)) { ?> selected="selected"<? } ?>>Disable push notifications</option>
					<option value="1"<? if ($PushService == 1) { ?> selected="selected"<? } ?>>Notify My Android</option>
					<option value="2"<? if ($PushService == 2) { ?> selected="selected"<? } ?>>Prowl</option>
<!--						No option 3, notifo died. -->
					<option value="4"<? if ($PushService == 4) { ?> selected="selected"<? } ?>>Super Toasty</option>
					<option value="5"<? if ($PushService == 5) { ?> selected="selected"<? } ?>>Pushover</option>
					<option value="6"<? if ($PushService == 6) { ?> selected="selected"<? } ?>>PushBullet</option>
				</select>
				<div id="pushsettings" style="display: none;">
					<label id="pushservice_title" for="pushkey">API key</label>
					<input type="text" size="50" name="pushkey" id="pushkey" value="<?=display_str($PushOptions['PushKey'])?>" />
					<label class="pushdeviceid" id="pushservice_device" for="pushdevice">Device ID</label>
					<select class="pushdeviceid" name="pushdevice" id="pushdevice">
						<option value="<?= display_str($PushOptions['PushDevice'])?>" selected="selected"><?= display_str($PushOptions['PushDevice'])?></option>
					</select>
					<br />
					<a href="user.php?action=take_push&amp;push=1&amp;userid=<?=G::$LoggedUser['ID']?>&amp;auth=<?=G::$LoggedUser['AuthKey']?>" class="brackets">Test push</a>
					<a href="wiki.php?action=article&amp;id=1017" class="brackets">View wiki guide</a>
				</div>
			</td>
		</tr>
<?	}

	public static function render_settings($Settings) {
		self::$Settings = $Settings;
		self::render_push_settings();
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
<?				self::render_checkbox(NotificationsManager::STAFFPM, false, false); ?>
			</td>
		</tr>
		<tr>
			<td class="label">
				<strong>Thread subscriptions</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::SUBSCRIPTIONS, false, false); ?>
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
<?					self::render_checkbox(NotificationsManager::TORRENTS, true, false); ?>
				</td>
			</tr>
<?		} ?>

		<tr>
			<td class="label tooltip" title="Enabling this will give you a notification when a torrent is added to a collage you are subscribed to.">
				<strong>Collage subscriptions</strong>
			</td>
			<td>
<?				self::render_checkbox(NotificationsManager::COLLAGES. false, false); ?>
			</td>
		</tr>
<?	}

	private static function render_checkbox($Name, $Traditional = false, $Push = true) {
		$Checked = self::$Settings[$Name];
		$PopupChecked = $Checked == NotificationsManager::OPT_POPUP || $Checked == NotificationsManager::OPT_POPUP_PUSH || !isset($Checked) ? ' checked="checked"' : '';
		$TraditionalChecked = $Checked == NotificationsManager::OPT_TRADITIONAL || $Checked == NotificationsManager::OPT_TRADITIONAL_PUSH ? ' checked="checked"' : '';
		$PushChecked = $Checked == NotificationsManager::OPT_TRADITIONAL_PUSH || $Checked == NotificationsManager::OPT_POPUP_PUSH || $Checked == NotificationsManager::OPT_PUSH ? ' checked="checked"' : '';

?>
		<label>
			<input type="checkbox" name="notifications_<?=$Name?>_popup" id="notifications_<?=$Name?>_popup"<?=$PopupChecked?> />
			Pop-up
		</label>
<?		if ($Traditional) { ?>
		<label>
			<input type="checkbox" name="notifications_<?=$Name?>_traditional" id="notifications_<?=$Name?>_traditional"<?=$TraditionalChecked?> />
			Traditional
		</label>
<?		}
		if ($Push) { ?>
		<label>
			<input type="checkbox" name="notifications_<?=$Name?>_push" id="notifications_<?=$Name?>_push"<?=$PushChecked?> />
			Push
		</label>
<?		}
	}

	public static function format_traditional($Contents) {
		return "<a href=\"$Contents[url]\">$Contents[message]</a>";
	}

}
