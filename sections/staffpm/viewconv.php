<?

if ($ConvID = (int)$_GET['id']) {
	// Get conversation info
	$DB->query("
		SELECT Subject, UserID, Level, AssignedToUser, Unread, Status
		FROM staff_pm_conversations
		WHERE ID = $ConvID");
	list($Subject, $UserID, $Level, $AssignedToUser, $Unread, $Status) = $DB->next_record();

	if (!(($UserID == $LoggedUser['ID'])
			|| ($AssignedToUser == $LoggedUser['ID'])
			|| (($Level > 0 && $Level <= $LoggedUser['EffectiveClass']) || ($Level == 0 && $IsFLS))
		)) {
	// User is trying to view someone else's conversation
		error(403);
	}
	// User is trying to view their own unread conversation, set it to read
	if ($UserID == $LoggedUser['ID'] && $Unread) {
		$DB->query("
			UPDATE staff_pm_conversations
			SET Unread = false
			WHERE ID = $ConvID");
		// Clear cache for user
		$Cache->delete_value("staff_pm_new_$LoggedUser[ID]");
	}

	View::show_header('Staff PM', 'staffpm,bbcode');

	$UserInfo = Users::user_info($UserID);
	$UserStr = Users::format_username($UserID, true, true, true, true);

	$OwnerID = $UserID;
	$OwnerName = $UserInfo['Username'];

?>
<div class="thin">
	<div class="header">
		<h2>Staff PM - <?=display_str($Subject)?></h2>
		<div class="linkbox">
<?
	// Staff only
	if ($IsStaff) {
?>
		<a href="staffpm.php" class="brackets">My unanswered</a>
<?
	}

	// FLS/Staff
	if ($IsFLS) {
?>
			<a href="staffpm.php?view=unanswered" class="brackets">All unanswered</a>
			<a href="staffpm.php?view=open" class="brackets">Open</a>
			<a href="staffpm.php?view=resolved" class="brackets">Resolved</a>
<?
		// User
	} else {
?>
			<a href="staffpm.php" class="brackets">Back to inbox</a>
<?
	}

?>		</div>
	</div>
	<br />
	<br />
	<div id="inbox">
<?
	// Get messages
	$StaffPMs = $DB->query("
		SELECT UserID, SentDate, Message, ID
		FROM staff_pm_messages
		WHERE ConvID = $ConvID");

	while (list($UserID, $SentDate, $Message, $MessageID) = $DB->next_record()) {
		// Set user string
		if ($UserID == $OwnerID) {
			// User, use prepared string
			$UserString = $UserStr;
			$Username = $OwnerName;
		} else {
			// Staff/FLS
			$UserInfo = Users::user_info($UserID);
			$UserString = Users::format_username($UserID, true, true, true, true);
			$Username = $UserInfo['Username'];
		}
?>
		<div class="box vertical_space" id="post<?=$MessageID?>">
			<div class="head">
<?				// TODO: the inline style in the <a> tag is an ugly hack. get rid of it. ?>
				<a class="postid" href="staffpm.php?action=viewconv&amp;id=<?=$ConvID?>#post<?=$MessageID?>" style="font-weight: normal;">#<?=$MessageID?></a>
				<strong>
					<?=$UserString?>
				</strong>
				<?=time_diff($SentDate, 2, true)?>
<?		if ($Status != 'Resolved') { ?>
				- <a href="#quickpost" onclick="Quote('<?=$MessageID?>', '<?=$Username?>');" class="brackets">Quote</a>
<?		} ?>
			</div>
			<div class="body"><?=Text::full_format($Message)?></div>
		</div>
		<div align="center" style="display: none;"></div>
<?
		$DB->set_query_id($StaffPMs);
	}

	// Common responses
	if ($IsFLS && $Status != 'Resolved') {
?>
		<div id="common_answers" class="hidden">
			<div class="box vertical_space">
				<div class="head">
					<strong>Preview</strong>
				</div>
				<div id="common_answers_body" class="body">Select an answer from the drop-down to view it.</div>
			</div>
			<br />
			<div class="center">
				<select id="common_answers_select" onchange="UpdateMessage();">
					<option id="first_common_response">Select a message</option>
<?
		// List common responses
		$DB->query("
			SELECT ID, Name
			FROM staff_pm_responses");
		while (list($ID, $Name) = $DB->next_record()) {
?>
					<option value="<?=$ID?>"><?=$Name?></option>
<?		} ?>
				</select>
				<input type="button" value="Set message" onclick="SetMessage();" />
				<input type="button" value="Create new / Edit" onclick="location.href='staffpm.php?action=responses&amp;convid=<?=$ConvID?>';" />
			</div>
		</div>
<?
	}

	// Ajax assign response div
	if ($IsStaff) {
?>
		<div id="ajax_message" class="hidden center alertbar"></div>
<?
	}

	// Reply box and buttons
?>
		<h3>Reply</h3>
		<div class="box pad" id="reply_box">
			<div id="buttons" class="center">
				<form class="manage_form" name="staff_messages" action="staffpm.php" method="post" id="messageform">
					<input type="hidden" name="action" value="takepost" />
					<input type="hidden" name="convid" value="<?=$ConvID?>" id="convid" />
<?
					if ($Status != 'Resolved') {
						$TextPrev = new TEXTAREA_PREVIEW('message', 'quickpost', '', 90, 10, true, false);
					}
?>
					<br />
<?
	// Assign to
	if ($IsStaff) {
		// Staff assign dropdown
?>
					<select id="assign_to" name="assign">
						<optgroup label="User classes">
<?		// FLS "class"
		$Selected = ((!$AssignedToUser && $Level == 0) ? ' selected="selected"' : '');
?>
							<option value="class_0"<?=$Selected?>>First Line Support</option>
<?		// Staff classes
		foreach ($ClassLevels as $Class) {
			// Create one <option> for each staff user class
			if ($Class['Level'] >= 650) {
				$Selected = ((!$AssignedToUser && ($Level == $Class['Level'])) ? ' selected="selected"' : '');
?>
							<option value="class_<?=$Class['Level']?>"<?=$Selected?>><?=$Class['Name']?></option>
<?
			}
		}
?>
						</optgroup>
						<optgroup label="Staff">
<?		// Staff members
		$DB->query("
			SELECT
				m.ID,
				m.Username
			FROM permissions AS p
				JOIN users_main AS m ON m.PermissionID = p.ID
			WHERE p.DisplayStaff = '1'
			ORDER BY p.Level DESC, m.Username ASC"
		);
		while (list($ID, $Name) = $DB->next_record()) {
			// Create one <option> for each staff member
			$Selected = (($AssignedToUser == $ID) ? ' selected="selected"' : '');
?>
							<option value="user_<?=$ID?>"<?=$Selected?>><?=$Name?></option>
<?		} ?>
						</optgroup>
						<optgroup label="First Line Support">
<?
		// FLS users
		$DB->query("
			SELECT
				m.ID,
				m.Username
			FROM users_info AS i
				JOIN users_main AS m ON m.ID = i.UserID
				JOIN permissions AS p ON p.ID = m.PermissionID
			WHERE p.DisplayStaff != '1'
				AND i.SupportFor != ''
			ORDER BY m.Username ASC
		");
		while (list($ID, $Name) = $DB->next_record()) {
			// Create one <option> for each FLS user
			$Selected = (($AssignedToUser == $ID) ? ' selected="selected"' : '');
?>
							<option value="user_<?=$ID?>"<?=$Selected?>><?=$Name?></option>
<?		} ?>
						</optgroup>
					</select>
					<input type="button" onclick="Assign();" value="Assign" />
<?	} elseif ($IsFLS) {	// FLS assign button ?>
					<input type="button" value="Assign to staff" onclick="location.href='staffpm.php?action=assign&amp;to=staff&amp;convid=<?=$ConvID?>';" />
					<input type="button" value="Assign to forum staff" onclick="location.href='staffpm.php?action=assign&amp;to=forum&amp;convid=<?=$ConvID?>';" />
<?
	}

	if ($Status != 'Resolved') { ?>
					<input type="button" value="Resolve" onclick="location.href='staffpm.php?action=resolve&amp;id=<?=$ConvID?>';" />
<?		if ($IsFLS) { //Moved by request ?>
					<input type="button" value="Common answers" onclick="$('#common_answers').gtoggle();" />
<?		} ?>
					<input type="button" id="previewbtn" value="Preview" class="hidden button_preview_<?=$TextPrev->getID()?>" />
					<input type="submit" value="Send message" />
<?	} else { ?>
					<input type="button" value="Unresolve" onclick="location.href='staffpm.php?action=unresolve&amp;id=<?=$ConvID?>';" />
<?
	}
	if (check_perms('users_give_donor')) { ?>
					<br />
					<input type="button" value="Make Donor" onclick="$('#make_donor_form').gtoggle(); return false;" />
<?	} ?>
				</form>
<?	if (check_perms('users_give_donor')) { ?>
				<div id="make_donor_form" class="hidden">
					<form action="staffpm.php" method="post">
						<input type="hidden" name="action" value="make_donor" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="id" value="<?=$ConvID?>" />
						<strong>Amount: </strong>
						<input type="text" name="donation_amount" onkeypress="return isNumberKey(event);" />
						<br />
						<strong>Reason: </strong>
						<input type="text" name="donation_reason" />
						<br />
						<select name="donation_source">
							<option value="Flattr">Flattr</option>
						</select>
						<select name="donation_currency">
							<option value="EUR">EUR</option>
						</select>
						<input type="submit" value="Submit" />
					</form>
				</div>
<?	} ?>
			</div>
		</div>
	</div>
</div>
<?

	View::show_footer();
} else {
	// No ID
	header('Location: staffpm.php');
}
