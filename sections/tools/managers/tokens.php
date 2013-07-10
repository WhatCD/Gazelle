<?php
if (!check_perms('users_mod')) {
	error(403);
}

if (isset($_REQUEST['addtokens'])) {
	authorize();
	$Tokens = $_REQUEST['numtokens'];

	if (!is_number($Tokens) || ($Tokens < 0)) {
		error('Please enter a valid number of tokens.');
	}
	$sql = "
		UPDATE users_main
		SET FLTokens = FLTokens + $Tokens
		WHERE Enabled = '1'";
	if (!isset($_REQUEST['leechdisabled'])) {
		$sql .= "
			AND can_leech = 1";
	}
	$DB->query($sql);
	$sql = "
		SELECT ID
		FROM users_main
		WHERE Enabled = '1'";
	if (!isset($_REQUEST['leechdisabled'])) {
		$sql .= "
			AND can_leech = 1";
	}
	$DB->query($sql);
	while (list($UserID) = $DB->next_record()) {
		$Cache->delete_value("user_info_heavy_$UserID");
	}
	$message = '<strong>' . number_format($Tokens) . 'freeleech tokens added to all enabled users' . (!isset($_REQUEST['leechdisabled']) ? ' with enabled leeching privs' : '') . '.</strong><br /><br />';
} elseif (isset($_REQUEST['cleartokens'])) {
	authorize();
	$Tokens = $_REQUEST['numtokens'];

	if (!is_number($Tokens) || ($Tokens < 0)) {
		error('Please enter a valid number of tokens.');
	}

	if (isset($_REQUEST['onlydrop'])) {
		$Where = "WHERE FLTokens > $Tokens";
	} elseif (!isset($_REQUEST['leechdisabled'])) {
		$Where = "WHERE (Enabled = '1' AND can_leech = 1) OR FLTokens > $Tokens";
	} else {
		$Where = "WHERE Enabled = '1' OR FLTokens > $Tokens";
	}
	$DB->query("
		SELECT ID
		FROM users_main
		$Where");
	$Users = $DB->to_array();
	$DB->query("
		UPDATE users_main
		SET FLTokens = $Tokens
		$Where");

	foreach ($Users as $UserID) {
		list($UserID) = $UserID;
		$Cache->delete_value("user_info_heavy_$UserID");
	}

	$where = '';
}


	View::show_header('Add tokens sitewide');

?>
<div class="header">
	<h2>Add freeleech tokens to all enabled users</h2>
	<div class="linkbox">
		<a href="tools.php?action=tokens&amp;showabusers=1" class="brackets">Show abusers</a>
	</div>
</div>
<div class="box pad" style="margin-left: auto; margin-right: auto; text-align: center; max-width: 40%;">
	<?=$message?>
	<form class="add_form" name="fltokens" action="" method="post">
		<input type="hidden" name="action" value="tokens" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		Tokens to add: <input type="text" name="numtokens" size="5" style="text-align: right;" value="0" /><br /><br />
		<label for="leechdisabled">Grant tokens to leech disabled users: </label><input type="checkbox" id="leechdisabled" name="leechdisabled" value="1" /><br /><br />
		<input type="submit" name="addtokens" value="Add tokens" />
	</form>
</div>
<br />
<div class="box pad" style="margin-left: auto; margin-right: auto; text-align: center; max-width: 40%;">
	<?=$message?>
	<form class="manage_form" name="fltokens" action="" method="post">
		<input type="hidden" name="action" value="tokens" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		Tokens to set: <input type="text" name="numtokens" size="5" style="text-align: right;" value="0" /><br /><br />
		<span id="droptokens" class=""><label for="onlydrop">Only affect users with at least this many tokens: </label><input type="checkbox" id="onlydrop" name="onlydrop" value="1" onchange="$('#disabled').gtoggle(); return true;" /></span><br />
		<span id="disabled" class=""><label for="leechdisabled">Also add tokens (as needed) to leech disabled users: </label><input type="checkbox" id="leechdisabled" name="leechdisabled" value="1" onchange="$('#droptokens').gtoggle(); return true;" /></span><br /><br />
		<input type="submit" name="cleartokens" value="Set token total" />
	</form>
</div>
<?

View::show_footer()
?>
