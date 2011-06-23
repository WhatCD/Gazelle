<?
/************************************************************************
||------------|| User IP history page ||---------------------------||

This page lists previous IPs a user has connected to the site with. It
gets called if $_GET['action'] == 'ips'.

It also requires $_GET['userid'] in order to get the data for the correct
user.

************************************************************************/

define('IPS_PER_PAGE', 25);

$UserID = $_GET['userid'];
if (!is_number($UserID)) { error(404); }

$DB->query("SELECT um.Username, p.Level AS Class FROM users_main AS um LEFT JOIN permissions AS p ON p.ID=um.PermissionID WHERE um.ID = ".$UserID);
list($Username, $Class) = $DB->next_record();

if(!check_perms('users_view_ips', $Class)) { 
	error(403);
}

$UsersOnly = $_GET['usersonly'];

show_header("IP history for $Username");
?>
<script type="text/javascript">
function ShowIPs(rowname) {
	$('tr[name="'+rowname+'"]').toggle();
}
</script>
<div class="thin">
<?
list($Page,$Limit) = page_limit(IPS_PER_PAGE);

if ($UsersOnly == 1) {
	$RS = $DB->query("SELECT SQL_CALC_FOUND_ROWS
	        h1.IP,
	       	h1.StartTime,
	       	h1.EndTime,
	        GROUP_CONCAT(h2.UserID SEPARATOR '|'),
	        GROUP_CONCAT(h2.StartTime SEPARATOR '|'),
	        GROUP_CONCAT(h2.EndTime SEPARATOR '|'),
	        GROUP_CONCAT(um2.Username SEPARATOR '|'),
	   	GROUP_CONCAT(um2.Enabled SEPARATOR '|'),
	        GROUP_CONCAT(ui2.Donor SEPARATOR '|'),
	        GROUP_CONCAT(ui2.Warned SEPARATOR '|')
	        FROM users_history_ips AS h1
	        LEFT JOIN users_history_ips AS h2 ON h2.IP=h1.IP AND h2.UserID!=$UserID
	        LEFT JOIN users_main AS um2 ON um2.ID=h2.UserID
	        LEFT JOIN users_info AS ui2 ON ui2.UserID=h2.UserID
		WHERE h1.UserID='$UserID'
		AND h2.UserID>0
	        GROUP BY h1.IP, h1.StartTime
		ORDER BY h1.StartTime DESC LIMIT $Limit");
} else {
	$RS = $DB->query("SELECT SQL_CALC_FOUND_ROWS
		h1.IP, 
		h1.StartTime, 
		h1.EndTime,
		GROUP_CONCAT(h2.UserID SEPARATOR '|'),
		GROUP_CONCAT(h2.StartTime SEPARATOR '|'),
		GROUP_CONCAT(h2.EndTime SEPARATOR '|'),
		GROUP_CONCAT(um2.Username SEPARATOR '|'),
		GROUP_CONCAT(um2.Enabled SEPARATOR '|'),
		GROUP_CONCAT(ui2.Donor SEPARATOR '|'),
		GROUP_CONCAT(ui2.Warned SEPARATOR '|')
		FROM users_history_ips AS h1
		LEFT JOIN users_history_ips AS h2 ON h2.IP=h1.IP AND h2.UserID!=$UserID
		LEFT JOIN users_main AS um2 ON um2.ID=h2.UserID
		LEFT JOIN users_info AS ui2 ON ui2.UserID=h2.UserID
		WHERE h1.UserID='$UserID'
		GROUP BY h1.IP, h1.StartTime
		ORDER BY h1.StartTime DESC LIMIT $Limit");
}
$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();
$DB->set_query_id($RS);

$Pages=get_pages($Page,$NumResults,IPS_PER_PAGE,9);

?>
	<h2>IP history for <a href="/user.php?id=<?=$UserID?>"><?=$Username?></a></h2>
	<div class="linkbox"><?=$Pages?></div>
	<table>
		<tr class="colhead">
			<td>IP address</td>
			<td>Started</td>
			<td>Ended</td>
			<td>Elapsed</td>
		</tr>
<?
$Results = $DB->to_array();
foreach($Results as $Index => $Result) {
	list($IP, $StartTime, $EndTime, $UserIDs, $UserStartTimes, $UserEndTimes, $Usernames, $UsersEnabled, $UsersDonor, $UsersWarned) = $Result;

	$HasDupe = false;
	$UserIDs = explode('|', $UserIDs);
	if(!$EndTime) { $EndTime = sqltime(); }
	if($UserIDs[0] != 0){
		$HasDupe = true;
		$UserStartTimes = explode('|', $UserStartTimes);
		$UserEndTimes = explode('|', $UserEndTimes);
		$Usernames = explode('|', $Usernames);
		$UsersEnabled = explode('|', $UsersEnabled);
		$UsersDonor = explode('|', $UsersDonor);
		$UsersWarned = explode('|', $UsersWarned);
	}
?>
		<tr class="rowa">
			<td>
				<?=$IP?> (<?=get_cc($IP)?>)				<br />
				<?=get_host($IP)?>
			<?=($HasDupe ? 
			'<a href="#" onclick="ShowIPs('.$Index.'); return false;">('.count($UserIDs).')</a>' 
			: '(0)')?></td>
			<td><?=time_diff($StartTime)?></td>
			<td><?=time_diff($EndTime)?></td>
			<td><?//time_diff(strtotime($StartTime), strtotime($EndTime)); ?></td>
		</tr>
<?
	if($HasDupe){
		$HideMe = (count($UserIDs) > 10);
		foreach ($UserIDs as $Key => $Val) {
		if(!$UserEndTimes[$Key]){ $UserEndTimes[$Key] = sqltime(); }
?>
		<tr class="rowb<?=($HideMe ? ' hidden' : '')?>" name="<?=$Index?>">
			<td>&nbsp;&nbsp;&#187;&nbsp;<?=format_username($Val, $Usernames[$Key], $UsersDonor[$Key], $UsersWarned[$Key], $UsersEnabled[$Key] == 2 ? false : true)?></td>
			<td><?=time_diff($UserStartTimes[$Key])?></td>
			<td><?=time_diff($UserEndTimes[$Key])?></td>
			<td><?//time_diff(strtotime($UserStartTimes[$Key]), strtotime($UserEndTimes[$Key])); ?></td>
		</tr>
<?
			
		}
	}
?>
<?
}
?>
	</table>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>

<?
show_footer();
?>
