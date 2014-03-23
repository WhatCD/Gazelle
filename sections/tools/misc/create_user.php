<?
//TODO: rewrite this, make it cleaner, make it work right, add it common stuff
if (!check_perms('admin_create_users')) {
	error(403);
}

//Show our beautiful header
View::show_header('Create a User');

//Make sure the form was sent
if (isset($_POST['Username'])) {
	authorize();

	//Create variables for all the fields
	$Username = trim($_POST['Username']);
	$Email = trim($_POST['Email']);
	$Password = $_POST['Password'];

	//Make sure all the fields are filled in
	//Don't allow a username of "0" or "1" because of PHP's type juggling
	if (!empty($Username) && !empty($Email) && !empty($Password) && $Username != '0' && $Username != '1') {

		//Create hashes...
		$Secret = Users::make_secret();
		$torrent_pass = Users::make_secret();

		//Create the account
		$DB->query("
			INSERT INTO users_main
				(Username, Email, PassHash, torrent_pass, Enabled, PermissionID)
			VALUES
				('".db_string($Username)."', '".db_string($Email)."', '".db_string(Users::make_crypt_hash($Password))."', '".db_string($torrent_pass)."', '1', '".USER."')");

		//Increment site user count
		$Cache->increment('stats_user_count');

		//Grab the userID
		$UserID = $DB->inserted_id();

		Tracker::update_tracker('add_user', array('id' => $UserID, 'passkey' => $torrent_pass));

		//Default stylesheet
		$DB->query("
			SELECT ID
			FROM stylesheets");
		list($StyleID) = $DB->next_record();

		//Auth key
		$AuthKey = Users::make_secret();

		//Give them a row in users_info
		$DB->query("
			INSERT INTO users_info
				(UserID, StyleID, AuthKey, JoinDate)
			VALUES
				('".db_string($UserID)."', '".db_string($StyleID)."', '".db_string($AuthKey)."', '".sqltime()."')");

		// Give the notification settings
		$DB->query("INSERT INTO users_notifications_settings (UserID) VALUES ('$UserID')");

		//Redirect to users profile
		header ("Location: user.php?id=$UserID");

	//What to do if we don't have a username, email, or password
	} elseif (empty($Username)) {

		//Give the Error -- We do not have a username
		error('Please supply a username');

	} elseif (empty($Email)) {

		//Give the Error -- We do not have an email address
		error('Please supply an email address');

	} elseif (empty($Password)) {

		//Give the Error -- We do not have a password
		error('Please supply a password');

	} else {

		//Uh oh, something went wrong
		error('Unknown error');

	}

//Form wasn't sent -- Show form
} else {

	?>
	<div class="header">
		<h2>Create a User</h2>
	</div>

	<div class="thin box pad">
	<form class="create_form" name="user" method="post" action="">
		<input type="hidden" name="action" value="create_user" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<table class="layout" cellpadding="2" cellspacing="1" border="0" align="center">
			<tr valign="top">
				<td align="right" class="label">Username:</td>
				<td align="left"><input type="text" name="Username" id="username" class="inputtext" /></td>
			</tr>
			<tr valign="top">
				<td align="right" class="label">Email address:</td>
				<td align="left"><input type="email" name="Email" id="email" class="inputtext" /></td>
			</tr>
			<tr valign="top">
				<td align="right" class="label">Password:</td>
				<td align="left"><input type="password" name="Password" id="password" class="inputtext" /></td>
			</tr>
			<tr>
				<td colspan="2" align="right">
					<input type="submit" name="submit" value="Create User" class="submit" />
				</td>
			</tr>
		</table>
	</form>
	</div>
<?
}

View::show_footer(); ?>
