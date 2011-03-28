<?
//TODO: rewrite this, make it cleaner, make it work right, add it common stuff
if(!check_perms('admin_create_users')) { error(403); }

//Show our beautiful header
show_header('Create a User');

//Make sure the form was sent
if (isset($_POST['Username'])) {
	authorize();

	//Create variables for all the fields
	$Username = $_POST['Username'];
	$Email = $_POST['Email'];
	$Password = $_POST['Password'];
	
	//Make sure all the fields are filled in
	if (!empty($Username) && !empty($Email) && !empty($Password)) {

		//Create hashes...
		$Secret=make_secret();
		$torrent_pass=make_secret();

		//Create the account
		$DB->query("INSERT INTO users_main (Username,Email,PassHash,Secret,torrent_pass,Enabled,PermissionID, Language) VALUES ('".db_string($Username)."','".db_string($Email)."','".db_string(make_hash($Password, $Secret))."','".db_string($Secret)."','".db_string($torrent_pass)."','1','".USER."', 'en')");
		
		//Increment site user count
		$Cache->increment('stats_user_count');
		
		//Grab the userid
		$UserID=$DB->inserted_id();
		
		update_tracker('add_user', array('id' => $UserID, 'passkey' => $torrent_pass));

		//Default stylesheet
		$DB->query("SELECT ID FROM stylesheets");
		list($StyleID)=$DB->next_record();
		
		//Auth key
		$AuthKey = make_secret();
		
		//Give them a row in users_info
		$DB->query("INSERT INTO users_info 
		(UserID,StyleID,AuthKey,JoinDate) VALUES 
		('".db_string($UserID)."','".db_string($StyleID)."','".db_string($AuthKey)."', '".sqltime()."')");
		
		//Redirect to users profile
		header ("Location: user.php?id=".$UserID);
	
	//What to do if we don't have a username, email, or password
	} elseif (empty($Username)) {
	
		//Give the Error -- We do not have a username
		error("Please supply a username");
		
	} elseif (empty($Email)) {
	
		//Give the Error -- We do not have an email address
		error("Please supply an email address");
		
	} elseif (empty($Password)) {
	
		//Give the Error -- We do not have a password
		error("Please supply a password");
	
	} else {
	
		//Uh oh, something went wrong
		error("Unknown error");
	
	}
	
//Form wasn't sent -- Show form
} else {

	?>
	<h2>Create a User</h2>
	
	<form method="post" action="" name="create_user">
		<input type="hidden" name="action" value="create_user" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<table cellpadding="2" cellspacing="1" border="0" align="center">
		<tr valign="top">
			<td align="right">Username&nbsp;</td>
			<td align="left"><input type="text" name="Username" id="username" class="inputtext" /></td>
		</tr>
		<tr valign="top">
			<td align="right">Email&nbsp;</td>
			<td align="left"><input type="text" name="Email" id="email" class="inputtext" /></td>
		</tr>
		<tr valign="top">
			<td align="right">Password&nbsp;</td>
			<td align="left"><input type="password" name="Password" id="password" class="inputtext" /></td>
		</tr>
		<tr>
			<td colspan="2" align="right"><input type="submit" name="submit" value="Create User" class="submit" /></td>
		</tr>
	</table>
	</form>
	<?

}

//Show the footer
show_footer();

?>
