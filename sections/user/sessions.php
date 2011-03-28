<?
//TODO: restrict to viewing bellow class, username in h2
if(isset($_GET['userid']) && check_perms('users_view_ips') && check_perms('users_logout')) {
	if(!is_number($_GET['userid'])) { error(404); }
	$UserID = $_GET['userid'];
} else {
	$UserID = $LoggedUser['ID'];
}

$UserSessions = $Cache->get_value('users_sessions_'.$UserID);
if(!is_array($UserSessions)) {
	$DB->query("SELECT 
		SessionID, 
		Browser, 
		OperatingSystem, 
		IP, 
		LastUpdate 
		FROM users_sessions 
		WHERE UserID='$UserID' 
		ORDER BY LastUpdate DESC");
	$UserSessions = $DB->to_array('SessionID',MYSQLI_ASSOC);
	$Cache->cache_value('users_sessions_'.$UserID, $UserSessions, 0);
}

if(isset($_POST['all'])) {
	authorize();

	$DB->query("DELETE FROM users_sessions WHERE UserID='$UserID' AND SessionID<>'$SessionID'");
	$UserSessions = array($SessionID=>array('SessionID'=>$SessionID,'Browser'=>$Browser,'OperatingSystem'=>$OperatingSystem,'IP'=>$SessionIP,'LastUpdate'=>sqltime()));
	$Cache->cache_value('users_sessions_'.$UserID, $UserSessions, 0);
}

if (isset($_POST['session'])) {

	authorize();

	$DB->query("DELETE FROM users_sessions WHERE UserID='$UserID' AND SessionID='".db_string($_POST['session'])."'");
	unset($UserSessions[$_POST['session']]);
	$Cache->begin_transaction('users_sessions_'.$UserID);
	$Cache->delete_row($_POST['session']);
	$Cache->commit_transaction(0);
}

list($UserID, $Username) = array_values(user_info($UserID));

show_header($Username.' &gt; Sessions');
?>
<div class="thin">
<h2><?=format_username($UserID,$Username)?> &gt; Sessions</h2>
	<div class="box pad">
		<p>Note: Clearing cookies can result in ghost sessions which are automatically removed after 30 days.</p>
	</div>
	<div class="box pad">
		<table cellpadding="5" cellspacing="1" border="0" class="border" width="100%">
			<tr class="colhead">
				<td><strong>IP</strong></td>
				<td><strong>Browser</strong></td>
				<td><strong>Platform</strong></td>
				<td><strong>Last Activity</strong></td>
				<td>
					<form action="" method="post">
						<input type="hidden" name="action" value="sessions" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="all" value="1" />
						<input type="submit" value="Logout All" />
					</form>
				</td>
			</tr>
<?
	$Row = 'a';
	foreach($UserSessions as $Session) {
		list($ThisSessionID,$Browser,$OperatingSystem,$IP,$LastUpdate) = array_values($Session);
		$Row = ($Row == 'a') ? 'b' : 'a';
?>
			<tr class="row<?=$Row?>">
				<td><?=$IP?></td>
				<td><?=$Browser?></td>
				<td><?=$OperatingSystem?></td>
				<td><?=time_diff($LastUpdate)?></td>
				<td>
					<form action="" method="post">
						<input type="hidden" name="action" value="sessions" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="session" value="<?=$ThisSessionID?>" />
						<input type="submit" value="<?=(($ThisSessionID == $SessionID)?'Current" disabled="disabled':'Logout')?>" />
					</form>
				</td>
			</tr>
<? } ?>
		</table>
	</div>
</div>
<?
show_footer();
?>
