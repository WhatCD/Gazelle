<?php

View::show_header('Staff Inbox');

$View = display_str($_GET['view']);
$UserLevel = $LoggedUser['EffectiveClass'];

// Setup for current view mode
$SortStr = 'IF(AssignedToUser = '.$LoggedUser['ID'].', 0, 1) ASC, ';
switch ($View) {
	case 'unanswered':
		$ViewString = 'Unanswered';
		$WhereCondition = "
			WHERE (Level <= $UserLevel OR AssignedToUser = '".$LoggedUser['ID']."')
				AND Status = 'Unanswered'";
		break;
	case 'open':
		$ViewString = 'Unresolved';
		$WhereCondition = "
			WHERE (Level <= $UserLevel OR AssignedToUser = '".$LoggedUser['ID']."')
				AND Status IN ('Open', 'Unanswered')";
		$SortStr = '';
		break;
	case 'resolved':
		$ViewString = 'Resolved';
		$WhereCondition = "
			WHERE (Level <= $UserLevel OR AssignedToUser = '".$LoggedUser['ID']."')
				AND Status = 'Resolved'";
		$SortStr = '';
		break;
	case 'my':
		$ViewString = 'Your Unanswered';
		$WhereCondition = "
			WHERE (Level = $UserLevel OR AssignedToUser = '".$LoggedUser['ID']."')
				AND Status = 'Unanswered'";
		break;
	default:
		if ($UserLevel >= 700) {
			$ViewString = 'Your Unanswered';
			$WhereCondition = "
				WHERE (
						(Level >= ".max($Classes[MOD]['Level'], 700)." AND Level <= $UserLevel)
						OR AssignedToUser = '".$LoggedUser['ID']."'
					)
					AND Status = 'Unanswered'";
		} elseif ($UserLevel == 650) {
			// Forum Mods
			$ViewString = 'Your Unanswered';
			$WhereCondition = "
				WHERE (Level = $UserLevel OR AssignedToUser = '".$LoggedUser['ID']."')
					AND Status = 'Unanswered'";
		} else {
			// FLS
			$ViewString = 'Unanswered';
			$WhereCondition = "
				WHERE (Level <= $UserLevel OR AssignedToUser = '".$LoggedUser['ID']."')
					AND Status = 'Unanswered'";
		}
		break;
}

list($Page, $Limit) = Format::page_limit(MESSAGES_PER_PAGE);
// Get messages
$StaffPMs = $DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		ID,
		Subject,
		UserID,
		Status,
		Level,
		AssignedToUser,
		Date,
		Unread,
		ResolverID
	FROM staff_pm_conversations
	$WhereCondition
	ORDER BY $SortStr Level DESC, Date DESC
	LIMIT $Limit
");

$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();
$DB->set_query_id($StaffPMs);

$CurURL = Format::get_url();
if (empty($CurURL)) {
	$CurURL = 'staffpm.php?';
} else {
	$CurURL = "staffpm.php?$CurURL&";
}
$Pages = Format::get_pages($Page, $NumResults, MESSAGES_PER_PAGE, 9);

$Row = 'a';

// Start page
?>
<div class="thin">
	<div class="header">
		<h2><?=$ViewString?> Staff PMs</h2>
		<div class="linkbox">
<? 	if ($IsStaff) { ?>
			<a href="staffpm.php" class="brackets">View your unanswered</a>
<? 	} ?>
			<a href="staffpm.php?view=unanswered" class="brackets">View all unanswered</a>
			<a href="staffpm.php?view=open" class="brackets">View unresolved</a>
			<a href="staffpm.php?view=resolved" class="brackets">View resolved</a>
<? 	if ($IsStaff) { ?>
			<a href="staffpm.php?action=scoreboard" class="brackets">View scoreboard</a>
<?	} ?>
		</div>
	</div>
	<br />
	<br />
	<div class="linkbox">
		<?=$Pages?>
	</div>
	<div class="box pad" id="inbox">
<?

if (!$DB->has_results()) {
	// No messages
?>
		<h2>No messages</h2>
<?

} else {
	// Messages, draw table
	if ($ViewString != 'Resolved' && $IsStaff) {
		// Open multiresolve form
?>
		<form class="manage_form" name="staff_messages" method="post" action="staffpm.php" id="messageform">
			<input type="hidden" name="action" value="multiresolve" />
			<input type="hidden" name="view" value="<?=strtolower($View)?>" />
<?
	}

	// Table head
?>
			<table class="message_table<?=($ViewString != 'Resolved' && $IsStaff) ? ' checkboxes' : '' ?>">
				<tr class="colhead">
<? 	if ($ViewString != 'Resolved' && $IsStaff) { ?>
					<td width="10"><input type="checkbox" onclick="toggleChecks('messageform', this);" /></td>
<? 	} ?>
					<td width="50%">Subject</td>
					<td>Sender</td>
					<td>Date</td>
					<td>Assigned to</td>
<?	if ($ViewString == 'Resolved') { ?>
					<td>Resolved by</td>
<?	} ?>
				</tr>
<?

	// List messages
	while (list($ID, $Subject, $UserID, $Status, $Level, $AssignedToUser, $Date, $Unread, $ResolverID) = $DB->next_record()) {
		$Row = $Row === 'a' ? 'b' : 'a';
		$RowClass = "row$Row";

		//$UserInfo = Users::user_info($UserID);
		$UserStr = Users::format_username($UserID, true, true, true, true);

		// Get assigned
		if ($AssignedToUser == '') {
			// Assigned to class
			$Assigned = ($Level == 0) ? 'First Line Support' : $ClassLevels[$Level]['Name'];
			// No + on Sysops
			if ($Assigned != 'Sysop') {
				$Assigned .= '+';
			}

		} else {
			// Assigned to user
			// $UserInfo = Users::user_info($AssignedToUser);
			$Assigned = Users::format_username($AssignedToUser, true, true, true, true);

		}

		// Get resolver
		if ($ViewString == 'Resolved') {
			//$UserInfo = Users::user_info($ResolverID);
			$ResolverStr = Users::format_username($ResolverID, true, true, true, true);
		}

		// Table row
?>
				<tr class="<?=$RowClass?>">
<? 		if ($ViewString != 'Resolved' && $IsStaff) { ?>
					<td class="center"><input type="checkbox" name="id[]" value="<?=$ID?>" /></td>
<? 		} ?>
					<td><a href="staffpm.php?action=viewconv&amp;id=<?=$ID?>"><?=display_str($Subject)?></a></td>
					<td><?=$UserStr?></td>
					<td><?=time_diff($Date, 2, true)?></td>
					<td><?=$Assigned?></td>
<?		if ($ViewString == 'Resolved') { ?>
					<td><?=$ResolverStr?></td>
<?		} ?>
				</tr>
<?

		$DB->set_query_id($StaffPMs);
	} //while

	// Close table and multiresolve form
?>
			</table>
<? 	if ($ViewString != 'Resolved' && $IsStaff) { ?>
			<div class="submit_div">
				<input type="submit" value="Resolve selected" />
			</div>
		</form>
<?
	}
} //if (!$DB->has_results())
?>
	</div>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>
<?

View::show_footer();

?>
