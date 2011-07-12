<?

include(SERVER_ROOT.'/sections/staffpm/functions.php');

show_header('Staff PMs', 'staffpm');

// Get messages
$StaffPMs = $DB->query("
	SELECT
		ID, 
		Subject, 
		UserID, 
		Status, 
		Level, 
		AssignedToUser, 
		Date, 
		Unread
	FROM staff_pm_conversations 
	WHERE UserID=".$LoggedUser['ID']." 
	ORDER BY Status, Date DESC"
);

// Start page
?>
<div class="thin">
	<h2>Staff PMs</h2>
	<div class="linkbox">
		<a href="#" onClick="$('#compose').toggle();">[Compose New]</a>
		<br />
		<br />
	</div>
	<? print_compose_staff_pm(true); ?>
	<div class="box pad" id="inbox">
<?

if ($DB->record_count() == 0) {
	// No messages
?>
		<h2>No messages</h2>
<?

} else {
	// Messages, draw table
?>
		<form method="post" action="staffpm.php" id="messageform">
			<input type="hidden" name="action" value="multiresolve" />
			<h3>Open messages</h3>
			<table>
				<tr class="colhead">
					<td width="10"><input type="checkbox" onclick="toggleChecks('messageform',this)" /></td>
					<td width="50%">Subject</td>
					<td>Date</td>
					<td>Assigned to</td>
				</tr>
<?
	// List messages
	$Row = 'a';
	$ShowBox = 1;
	while(list($ID, $Subject, $UserID, $Status, $Level, $AssignedToUser, $Date, $Unread, $Resolved) = $DB->next_record()) {
		if($Unread === '1') {
			$RowClass = 'unreadpm';
		} else {
			$Row = ($Row === 'a') ? 'b' : 'a';
			$RowClass = 'row'.$Row;
		}

		if ($Status == 'Resolved') { $ShowBox++; }
		if ($ShowBox == 2) {
			// First resolved PM
?>
			</table>
			<br />
			<h3>Resolved messages</h3>
			<table>	
				<tr class="colhead">
					<td width="10"><input type="checkbox" onclick="toggleChecks('messageform',this)" /></td>
					<td width="50%">Subject</td>
					<td>Date</td>
					<td>Assigned to</td>
				</tr>
<?
		}

		// Get assigned
		$Assigned = ($Level == 0) ? "First Line Support" : $ClassLevels[$Level]['Name'];
		// No + on Sysops
		if ($Assigned != 'Sysop') { $Assigned .= "+"; }
			
		// Table row
?>
				<tr class="<?=$RowClass?>">

					<td class="center"><input type="checkbox" name="id[]" value="<?=$ID?>" /></td>
					<td><a href="staffpm.php?action=viewconv&amp;id=<?=$ID?>"><?=display_str($Subject)?></a></td>
					<td><?=time_diff($Date, 2, true)?></td>
					<td><?=$Assigned?></td>
				</tr>
<?
		$DB->set_query_id($StaffPMs);
	}

	// Close table and multiresolve form
?>
			</table>
			<input type="submit" value="Resolve selected" />
		</form>
<?

}

?>
	</div>
</div>
<?

show_footer();

?>
