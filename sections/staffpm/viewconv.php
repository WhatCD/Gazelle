<?
include(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

if ($ConvID = (int)$_GET['id']) {
	// Get conversation info
	$DB->query("SELECT Subject, UserID, Level, AssignedToUser, Unread, Status FROM staff_pm_conversations WHERE ID=$ConvID");
	list($Subject, $UserID, $Level, $AssignedToUser, $Unread, $Status) = $DB->next_record();
	$DB->query("SELECT Subject, UserID, Level, AssignedToUser, Unread, Status FROM staff_pm_conversations WHERE ID=$ConvID");
	list($Subject, $UserID, $Level, $AssignedToUser, $Unread, $Status) = $DB->next_record();

	if (!(($UserID == $LoggedUser['ID']) || ($AssignedToUser == $LoggedUser['ID']) || (($Level > 0 && $Level <= $LoggedUser['Class']) || ($Level == 0 && $IsFLS)))) {
	// User is trying to view someone else's conversation
		error(403);
	}
	// User is trying to view their own unread conversation, set it to read
	if ($UserID == $LoggedUser['ID'] && $Unread) {
		$DB->query("UPDATE staff_pm_conversations SET Unread=false WHERE ID=$ConvID");
		// Clear cache for user
		$Cache->delete_value('staff_pm_new_'.$LoggedUser['ID']);
	}

	show_header('Staff PM', 'staffpm,bbcode');

	$UserInfo = user_info($UserID);
	$UserStr = format_username($UserID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'], $UserInfo['PermissionID']);

	$OwnerID = $UserID;

?>
<div id="thin">
	<h2>Staff PM - <?=display_str($Subject)?></h2>
	<div class="linkbox">
<?
	// Staff only
	if ($IsStaff) {
?>
	<a href="staffpm.php">[My unanswered]</a>
<?
	}

	// FLS/Staff
	if ($IsFLS) {
?>
		<a href="staffpm.php?view=unanswered">[All unanswered]</a>
		<a href="staffpm.php?view=open">[Open]</a>
		<a href="staffpm.php?view=resolved">[Resolved]</a>
<?
		// User
	} else {
?>
		<a href="staffpm.php">[Back to inbox]</a>
<?
	}

?>
		<br />
		<br />
	</div>
	<div id="inbox">
<?
	// Get messages
	$StaffPMs = $DB->query("SELECT UserID, SentDate, Message FROM staff_pm_messages WHERE ConvID=$ConvID");

	while(list($UserID, $SentDate, $Message) = $DB->next_record()) {
		// Set user string
		if ($UserID == $OwnerID) {
			// User, use prepared string
			$UserString = $UserStr;
		} else {
			// Staff/FLS
			$UserInfo = user_info($UserID);
			$UserString = format_username($UserID, $UserInfo['Username'], $UserInfo['Donor'], $UserInfo['Warned'], $UserInfo['Enabled'], $UserInfo['PermissionID']);

		}
?>
		<div class="box vertical_space">
			<div class="head">
				<strong>
					<?=$UserString?>

				</strong>
				<?=time_diff($SentDate, 2, true)?>

			</div>
			<div class="body"><?=$Text->full_format($Message)?></div>
		</div>
		<div align="center" style="display: none"></div>
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
				<div id="common_answers_body" class="body">Select an answer from the dropdown to view it.</div>
			</div>
			<br />
			<div class="center">
				<select id="common_answers_select" onChange="UpdateMessage();">
					<option id="first_common_response">Select a message</option>
<?
		// List common responses
		$DB->query("SELECT ID, Name FROM staff_pm_responses");
		while(list($ID, $Name) = $DB->next_record()) {
?>
					<option value="<?=$ID?>"><?=$Name?></option>
<?		} ?>
				</select>
				<input type="button" value="Set message" onClick="SetMessage();" />
				<input type="button" value="Create new / Edit" onClick="location.href='staffpm.php?action=responses&convid=<?=$ConvID?>'" />
			</div>
		</div>
<?	}

	// Ajax assign response div
	if ($IsStaff) { ?>
		<div id="ajax_message" class="hidden center alertbar"></div>
<?	}

	// Replybox and buttons
?>
		<h3>Reply</h3>
		<div class="box pad">
			<div id="preview" class="hidden"></div>
			<div id="buttons" class="center">
				<form action="staffpm.php" method="post" id="messageform">
					<input type="hidden" name="action" value="takepost" />
					<input type="hidden" name="convid" value="<?=$ConvID?>" id="convid" />
					<textarea id="quickpost" name="message" cols="90" rows="10"></textarea> <br />
<?
	// Assign to
	if ($IsStaff) {
		// Staff assign dropdown
?>
					<select id="assign_to" name="assign">
						<optgroup label="User classes">
<?		// FLS "class"
		$Selected = (!$AssignedToUser && $Level == 0) ? ' selected="selected"' : '';
?>
							<option value="class_0"<?=$Selected?>>First Line Support</option>
<?		// Staff classes
		foreach ($ClassLevels as $Class) {
			// Create one <option> for each staff user class
			if ($Class['Level'] >= 650) {
				$Selected = (!$AssignedToUser && ($Level == $Class['Level'])) ? ' selected="selected"' : '';
?>
							<option value="class_<?=$Class['Level']?>"<?=$Selected?>><?=$Class['Name']?></option>
<?			}
		} ?>
						</optgroup>
						<optgroup label="Staff">
<?		// Staff members
		$DB->query("
			SELECT
				m.ID,
				m.Username
			FROM permissions as p
			JOIN users_main as m ON m.PermissionID=p.ID
			WHERE p.DisplayStaff='1'
			ORDER BY p.Level DESC, m.Username ASC"
		);
		while(list($ID, $Name) = $DB->next_record()) {
			// Create one <option> for each staff member
			$Selected = ($AssignedToUser == $ID) ? ' selected="selected"' : '';
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
			FROM users_info as i
			JOIN users_main as m ON m.ID=i.UserID
			JOIN permissions as p ON p.ID=m.PermissionID
			WHERE p.DisplayStaff!='1' AND i.SupportFor!=''
			ORDER BY m.Username ASC
		");
		while(list($ID, $Name) = $DB->next_record()) {
			// Create one <option> for each FLS user
			$Selected = ($AssignedToUser == $ID) ? ' selected="selected"' : '';
?>
							<option value="user_<?=$ID?>"<?=$Selected?>><?=$Name?></option>
<?		} ?>
						</optgroup>
					</select>
					<input type="button" onClick="Assign();" value="Assign" />
<?	} elseif ($IsFLS) {	// FLS assign button ?>
					<input type="button" value="Assign to staff" onClick="location.href='staffpm.php?action=assign&to=staff&convid=<?=$ConvID?>';" />
					<input type="button" value="Assign to forum staff" onClick="location.href='staffpm.php?action=assign&to=forum&convid=<?=$ConvID?>';" />
<?	}

	if ($Status != 'Resolved') { ?>
					<input type="button" value="Resolve" onClick="location.href='staffpm.php?action=resolve&id=<?=$ConvID?>';" />
<?			if ($IsFLS) {  //Moved by request ?>
					<input type="button" value="Common answers" onClick="$('#common_answers').toggle();" />
					<input type="button" id="previewbtn" value="Preview" onclick="PreviewMessage();" />
<?			} ?>
					<input type="submit" value="Send message" />
<?	} else { ?>
					<input type="button" value="Unresolve" onClick="location.href='staffpm.php?action=unresolve&id=<?=$ConvID?>';" />
<?	} 
	if (check_perms('users_give_donor')) { ?>
					<br />	
					<input type="button" value="Make Donor" onClick="location.href='staffpm.php?action=make_donor&id=<?=$ConvID?>';" />
<?	} ?>
				</form>
			</div>
		</div>
	</div>
</div>
<?

	show_footer();
} else {
	// No id
	header('Location: staffpm.php');
}
