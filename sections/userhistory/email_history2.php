<?
/************************************************************************
||------------|| User email history page ||---------------------------||

This page lists previous email addresses a user has used on the site. It
gets called if $_GET['action'] == 'email'.

It also requires $_GET['userid'] in order to get the data for the correct
user.

************************************************************************/

$UserID = $_GET['userid'];
if (!is_number($UserID)) { error(404); }

$DB->query("SELECT um.Username, ui.JoinDate, p.Level AS Class FROM users_main AS um JOIN users_info AS ui ON um.ID=ui.UserID JOIN permissions AS p ON p.ID=um.PermissionID WHERE um.ID = $UserID");
list($Username, $Joined, $Class) = $DB->next_record();

if(!check_perms('users_view_email', $Class)) {
	error(403);
}

$UsersOnly = $_GET['usersonly'];

show_header("Email history for $Username");

// Get current email (and matches)
$DB->query("
	SELECT 
		m.Email,
		'".sqltime()."' AS Time,
		m.IP,
		GROUP_CONCAT(h.UserID SEPARATOR '|') AS UserIDs,
		GROUP_CONCAT(h.Time SEPARATOR '|') AS UserSetTimes,
		GROUP_CONCAT(h.IP SEPARATOR '|') AS UserIPs,
		GROUP_CONCAT(m2.Username SEPARATOR '|') AS Usernames,
	   	GROUP_CONCAT(m2.Enabled SEPARATOR '|') AS UsersEnabled,
		GROUP_CONCAT(i.Donor SEPARATOR '|') AS UsersDonor,
		GROUP_CONCAT(i.Warned SEPARATOR '|') AS UsersWarned
	FROM users_main AS m
	LEFT JOIN users_history_emails AS h ON h.Email=m.Email AND h.UserID<>m.ID
	LEFT JOIN users_main AS m2 ON m2.ID=h.UserID
	LEFT JOIN users_info AS i ON i.UserID=h.UserID
	WHERE m.ID='$UserID'"
);
$CurrentEmail = array_shift($DB->to_array());

// Get historic emails (and matches)
$DB->query("
	SELECT 
		h2.Email, 
		h2.Time,
		h2.IP,
		h3.UserID AS UserIDs,
		h3.Time AS UserSetTimes,
		h3.IP AS UserIPs,
		m3.Username AS Usernames,
	   	m3.Enabled AS UsersEnabled,
		i2.Donor AS UsersDonor,
		i2.Warned AS UsersWarned
	FROM users_history_emails AS h2
	LEFT JOIN users_history_emails AS h3 ON h3.Email=h2.Email AND h3.UserID<>h2.UserID
	LEFT JOIN users_main AS m3 ON m3.ID=h3.UserID
	LEFT JOIN users_info AS i2 ON i2.UserID=h3.UserID
	WHERE h2.UserID='$UserID'
	ORDER BY Time DESC"
);
$History = $DB->to_array();

// Current email
$Current['Email'] = $CurrentEmail['Email'];
$Current['StartTime'] = $History[0]['Time'];
$Current['CurrentIP'] = $CurrentEmail['IP'];
$Current['IP'] = $History[(count($History) - 1)]['IP'];

// Matches for current email
if ($CurrentEmail['Usernames'] != '') {
	$UserIDs=explode('|', $CurrentEmail['UserIDs']);
	$Usernames=explode('|', $CurrentEmail['Usernames']);
	$UsersEnabled=explode('|', $CurrentEmail['UsersEnabled']);
	$UsersDonor=explode('|', $CurrentEmail['UsersDonor']);
	$UsersWarned=explode('|', $CurrentEmail['UsersWarned']);
	$UserSetTimes=explode('|', $CurrentEmail['UserSetTimes']);
	$UserIPs=explode('|', $CurrentEmail['UserIPs']);

	foreach($UserIDs as $Key => $Val) {
		$CurrentMatches[$Key]['Username'] = '&nbsp;&nbsp;&#187;&nbsp;'.format_username($Val, $Usernames[$Key], $UsersDonor[$Key], $UsersWarned[$Key], $UsersEnabled[$Key] == 2 ? false : true);
		$CurrentMatches[$Key]['IP'] = $UserIPs[$Key];
		$CurrentMatches[$Key]['EndTime'] = $UserSetTimes[$Key];
	}
}

// Email history records
if (count($History) == 1) {
	$Invite['Email'] = $History[0]['Email'];
	$Invite['EndTime'] = $Joined;
	$Invite['AccountAge'] = date(time() + time() - strtotime($Joined)); // Same as EndTime but without ' ago'
	$Invite['IP'] = $History[0]['IP'];
	if ($Current['StartTime'] == '0000-00-00 00:00:00') { $Current['StartTime'] = $Joined; }
} else {
	foreach ($History as $Key => $Val) {
		if ($History[$Key+1]['Time'] == '0000-00-00 00:00:00' && $Val['Time'] != '0000-00-00 00:00:00') {
			// Invited email
			$Invite['Email'] = $Val['Email'];
			$Invite['EndTime'] = $Joined;
			$Invite['AccountAge'] = date(time() + time() - strtotime($Joined)); // Same as EndTime but without ' ago'
			$Invite['IP'] = $Val['IP'];

		} elseif ($History[$Key-1]['Email'] != $Val['Email'] && $Val['Time'] != '0000-00-00 00:00:00') {
			// Old email
			$i=1;
			while($Val['Email'] == $History[$Key+$i]['Email']) {
				$i++;
			}
			$Old[$Key]['StartTime'] = (isset($History[$Key+$i]) && $History[$Key+$i]['Time'] != '0000-00-00 00:00:00') ? $History[$Key+$i]['Time'] : $Joined;
			$Old[$Key]['EndTime'] = $Val['Time'];
			$Old[$Key]['IP'] = $Val['IP'];
			$Old[$Key]['ElapsedTime'] = date(time() + strtotime($Old[$Key]['EndTime']) - strtotime($Old[$Key]['StartTime']));
			$Old[$Key]['Email'] =  $Val['Email'];

		} else {
			// Shouldn't have to be here but I'll leave it anyway
			$Other[$Key]['StartTime'] = (isset($History[$Key+$i])) ? $History[$Key+$i]['Time'] : $Joined;
			$Other[$Key]['EndTime'] = $Val['Time'];
			$Other[$Key]['IP'] = $Val['IP'];
			$Other[$Key]['ElapsedTime'] = date(time() + strtotime($Other[$Key]['EndTime']) - strtotime($Other[$Key]['StartTime']));
			$Other[$Key]['Email'] =  $Val['Email'];
		}

		if ($Val['Usernames'] != '') {
			// Match with old email
			$OldMatches[$Key]['Email'] = $Val['Email'];
			$OldMatches[$Key]['Username'] = '&nbsp;&nbsp;&#187;&nbsp;'.format_username($Val['UserIDs'], $Val['Usernames'], $Val['UsersDonor'], $Val['UsersWarned'], $Val['UsersEnabled'] == 2 ? false : true);
			$OldMatches[$Key]['EndTime'] = $Val['UserSetTimes'];
			$OldMatches[$Key]['IP'] = $Val['UserIPs'];
		}
	}
}

// Clean up arrays
if ($Old) {
	$Old = array_reverse(array_reverse($Old));
	$LastOld = count($Old)-1;
	if ($Old[$LastOld]['StartTime'] != $Invite['EndTime']) {
		// Make sure the timeline is intact (invite email was used as email for the account in the beginning)
		$Old[$LastOld+1]['Email'] = $Invite['Email'];
		$Old[$LastOld+1]['StartTime'] = $Invite['EndTime'];
		$Old[$LastOld+1]['EndTime'] = $Old[$LastOld]['StartTime'];
		$Old[$LastOld+1]['ElapsedTime'] = date(time()+strtotime($Old[$LastOld+1]['EndTime'] )-strtotime($Old[$LastOld+1]['StartTime']));
		$Old[$LastOld+1]['IP'] = $Invite['IP'];
	}
}

// Start page with current email
?>
<h2>Email history for <a href="user.php?id=<?=$UserID ?>"><?=$Username ?></a></h2>
<div class="thin">
	<br />
	<div class="center">
		<a href="userhistory.php?action=email&userid=<?=$UserID?>"><strong>Old email history</strong></a>
	</div>
	<br />
	<table width="100%">
		<tr class="colhead">
			<td>Current email</td>
			<td>Start</td>
			<td>End</td>
			<td>Current IP [<a href="userhistory.php?action=ips&amp;userid=<?=$UserID ?>">H</a>]</td>
			<td>Set from IP</td>
		</tr>
		<tr class="rowa">
			<td><?=display_str($Current['Email'])?></td>
			<td><?=time_diff($Current['StartTime'])?></td>
			<td></td>
			<td>
				<?=display_str($Current['CurrentIP'])?> (<?=get_cc($Current['CurrentIP'])?>) [<a href="user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($Current['CurrentIP'])?>" title="Search">S</a>]<br />
				<?=get_host($Current['CurrentIP'])?>
			</td>
			<td>
				<?=display_str($Current['IP'])?> (<?=get_cc($Current['IP'])?>) [<a href="user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($Current['IP'])?>" title="Search">S</a>]<br />
				<?=get_host($Current['IP'])?>
			</td>
		</tr>
<?
if ($CurrentMatches) {
	// Match on the current email
	foreach($CurrentMatches as $Match) {		
?>
		<tr class="rowb">
			<td><?=$Match['Username']?></td>
			<td></td>
			<td><?=time_diff($Match['EndTime'])?></td>
			<td></td>
			<td>
				<?=display_str($Match['IP'])?> (<?=get_cc($Match['IP'])?>) [<a href="user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($Match['IP'])?>" title="Search">S</a>]<br />
				<?=get_host($Match['IP'])?>
			</td>
		</tr>			
<? 
	}
}
// Old emails
if ($Old) {
?>
		<tr class="colhead">
			<td>Old emails</td>
			<td>Start</td>
			<td>End</td>
			<td>Elapsed</td>
			<td>Set from IP</td>
		</tr>
<?
	$j=0;
	// Old email
	foreach ($Old as $Record) { 
		++$j;

		// Matches on old email
		ob_start();
		$i=0;
		foreach ($OldMatches as $Match) {
			if ($Match['Email'] == $Record['Email']) {
				++$i;
				// Email matches
?>
		<tr class="rowb hidden" id="matches_<?=$j?>">
			<td><?=$Match['Username']?></td>
			<td></td>
			<td><?=time_diff($Match['EndTime'])?></td>
			<td></td>
			<td>
				<?=display_str($Match['IP'])?> (<?=get_cc($Match['IP'])?>) [<a href="user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($Match['IP'])?>" title="Search">S</a>]<br />
				<?=get_host($Match['IP'])?>
			</td>
		</tr>	
<?			
			}
		}

		// Save matches to variable
		$MatchCount = $i;
		$Matches = ob_get_contents();
		ob_end_clean();
?>
		<tr class="rowa">
			<td><?=display_str($Record['Email'])?><?=(($MatchCount > 0) ? ' <a href="#" onClick="$(\'#matches_'.$j.'\').toggle();return false;">('.$MatchCount.')</a>' : '')?></td>
			<td><?=time_diff($Record['StartTime'])?></td>
			<td><?=time_diff($Record['EndTime'])?></td>
			<td><?=time_diff($Record['ElapsedTime'])?></td>
			<td>
				<?=display_str($Record['IP'])?> (<?=get_cc($Record['IP'])?>) [<a href="user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($Record['IP'])?>" title="Search">S</a>]<br />
				<?=get_host($Record['IP'])?>
			</td>
		</tr>			
<?		
		if ($MatchCount > 0) {
			if (isset($Matches)) { 
				echo $Matches;
				unset($Matches);
				unset($MatchCount);
			}
		}
	}
}
// Invite email (always there)
?>
		<tr class="colhead">
			<td>Invite email</td>
			<td>Start</td>
			<td>End</td>
			<td>Age of account</td>
			<td>Signup IP</td>
		</tr>
<?
// Matches on invite email
if ($OldMatches) {
	$i=0;
	ob_start();
	foreach ($OldMatches as $Match) {
		if ($Match['Email'] == $Invite['Email']) {
			++$i;
			// Match email is the same as the invite email
?>
		<tr class="rowb hidden" id="matches_invite">
			<td><?=$Match['Username']?></td>
			<td></td>
			<td><?=time_diff($Match['EndTime'])?></td>
			<td></td>
			<td>
				<?=display_str($Match['IP'])?> (<?=get_cc($Match['IP'])?>) [<a href="user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($Match['IP'])?>" title="Search">S</a>]<br />
				<?=get_host($Match['IP'])?>
			</td>
		</tr>	
<?
		}
	}
	$MatchCount = $i;
	$Matches = ob_get_contents();
	ob_end_clean();
}
?>
		<tr class="rowa">
			<td><?=display_str($Invite['Email'])?><?=(($MatchCount > 0) ? ' <a href="#" onClick="$(\'#matches_invite\').toggle();return false;">('.$MatchCount.')</a>' : '')?></td>
			<td>Never</td>
			<td><?=time_diff($Invite['EndTime'])?></td>
			<td><?=time_diff($Invite['AccountAge'])?></td>
			<td>
				<?=display_str($Invite['IP'])?> (<?=get_cc($Invite['IP'])?>) [<a href="user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($Invite['IP'])?>" title="Search">S</a>]<br />
				<?=get_host($Invite['IP'])?>
			</td>
		</tr>
<?

if ($Matches) {
	echo $Matches;
}

?>		
	</table>
</div>
<? show_footer(); ?>
