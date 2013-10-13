<?

if (!check_perms("users_mod")) {
	error(404);
}

View::show_header("Global Notification");

$GlobalNotification = NotificationsManager::get_global_notification();

$Expiration = $GlobalNotification['Expiration'] ? $GlobalNotification['Expiration'] / 60 : "";
?>

<h2>Set global notification</h2>

<div class="thin box pad">
	<form action="tools.php" method="post">
		<input type="hidden" name="action" value="take_global_notification" />
		<input type="hidden" name="type" value="set" />
		<table align="center">
			<tr>
				<td class="label">Message</td>
				<td>
					<input type="text" name="message" id="message" size="50" value="<?=$GlobalNotification['Message']?>" />
				</td>
			</tr>
			<tr>
				<td class="label">URL</td>
				<td>
					<input type="text" name="url" id="url" size="50" value="<?=$GlobalNotification['URL']?>" />
				</td>
			</tr>
			<tr>
				<td class="label">Importance</td>
				<td>
					<select name="importance" id="importance">
<?		foreach (NotificationsManager::$Importances as $Key => $Value) { ?>
						<option value="<?=$Value?>"<?=$Value == $GlobalNotification['Importance'] ? ' selected="selected"' : ''?>><?=ucfirst($Key)?></option>
<?		} ?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">Length (in minutes)</td>
				<td>
					<input type="text" name="length" id="length" size="20" value="<?=$Expiration?>" />
				</td>
			</tr>
			<tr>
				<td>
					<input type="submit" name="set" value="Create Notification" />
				</td>
<?		if ($GlobalNotification) { ?>
				<td>
					<input type="submit" name="delete" value="Delete Notification" />
				</td>
<?		} ?>
			</tr>
		</table>
	</form>
</div>

<?
View::show_footer();
