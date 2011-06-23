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

if ($UsersOnly == 1) {
	$DB->query("SELECT
	                                u.Email,
	                                '".sqltime()."' AS Time,
	                                u.IP,
	                                c.Code
					FROM users_main AS u
					LEFT JOIN users_main AS u2 ON u2.Email = u.Email AND u2.ID != '$UserID'
	                        LEFT JOIN geoip_country AS c ON INET_ATON(u.IP) BETWEEN c.StartIP AND c.EndIP
	                        WHERE u.ID='$UserID' AND u2.ID > 0
	                        UNION SELECT
	                                h.Email,
	                                h.Time,
	                                h.IP,
	                                c.Code
					FROM users_history_emails AS h
					LEFT JOIN users_history_emails AS h2 ON h2.email=h.email and h2.UserID != '$UserID'
	                        LEFT JOIN geoip_country AS c ON INET_ATON(h.IP) BETWEEN c.StartIP AND c.EndIP
	                        WHERE h.UserID='$UserID' AND h2.UserID>0"/*AND Time<>'0000-00-00 00:00:00'*/."
				ORDER BY Time DESC");
} else {
	$DB->query("SELECT 
					u.Email,
					'".sqltime()."' AS Time,
					u.IP,
					c.Code
				FROM users_main AS u
				LEFT JOIN geoip_country AS c ON INET_ATON(u.IP) BETWEEN c.StartIP AND c.EndIP
				WHERE u.ID='$UserID'
				UNION SELECT 
					h.Email, 
					h.Time,
					h.IP,
					c.Code
				FROM users_history_emails AS h
				LEFT JOIN geoip_country AS c ON INET_ATON(h.IP) BETWEEN c.StartIP AND c.EndIP
				WHERE UserID='$UserID' "/*AND Time<>'0000-00-00 00:00:00'*/."
				ORDER BY Time DESC");
}
$History = $DB->to_array();
?>
<h2>Email history for <a href="user.php?id=<?=$UserID ?>"><?=$Username ?></a></h2>
<table width="100%">
	<tr class="colhead">
		<td>Email</td>
		<td>Set</td>
		<td>IP [<a href="userhistory.php?action=ips&amp;userid=<?=$UserID ?>">H</a>]</td>
<? if ($UsersOnly == 1) {
?>
	<td>User</td>
<?
}
?>
	</tr>
<?
foreach($History as $Key => $Values){
	if (isset($History[$Key+1])) {
		$Values['Time'] = $History[$Key+1]['Time'];
	} else {
		$Values['Time'] = $Joined;
	}
?>
	<tr class="rowa">
		<td><?=display_str($Values['Email'])?></td>
		<td><?=time_diff($Values['Time'])?></td>
		<td><?=display_str($Values['IP'])?> (<?=display_str($Values['Code'])?>) [<a href="user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($Values['IP'])?>" title="Search">S</a>]</td>
<?
	if ($UsersOnly == 1) {
		$ueQuery = $DB->query("SELECT ue.UserID, Username, ue.Time, ue.IP FROM users_history_emails AS ue, users_main WHERE ue.Email = '".db_string($Values['Email'])."' AND UserID != ".$UserID." AND ID = UserID");
		while (list($UserID2, $Username, $Time, $IP) = $DB->next_record()) { ?>
	</tr>
	<tr>
		<td />
		<td><?=time_diff($Time)?></td>
		<td><?=display_str($IP)?></td>
<? $UserURL = "http://".NONSSL_SITE_URL."/user.php?id=$UserID2";
			$DB->query("SELECT Enabled FROM users_main WHERE ID = ".$UserID2);
			list($Enabled)=$DB->next_record();
			$DB->set_query_id($ueQuery);
?>
		<td><a href="<?=display_str($UserURL)?>"><?=format_username($UserID2, $Username, 0, 0, $Enabled == 2 ? false : true, "")?></a></td>
	</tr>	
<?
		}
	}
?>
<? } ?>
</table>
<? show_footer(); ?>
