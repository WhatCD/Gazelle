<?php
/************************************************************************
||------------|| User IP history page ||---------------------------||

This page lists previous IPs a user has connected to the site with. It
gets called if $_GET['action'] == 'ips'.

It also requires $_GET['userid'] in order to get the data for the correct
user.

************************************************************************/

define('IPS_PER_PAGE', 25);

$UserID = $_GET['userid'];
if (!is_number($UserID)) {
	error(404);
}

$DB->query("
	SELECT
		um.Username,
		p.Level AS Class
	FROM users_main AS um
		LEFT JOIN permissions AS p ON p.ID = um.PermissionID
	WHERE um.ID = $UserID");
list($Username, $Class) = $DB->next_record();

if (!check_perms('users_view_ips', $Class)) {
	error(403);
}

$UsersOnly = $_GET['usersonly'];

if (isset($_POST['ip'])) {
	$SearchIP = db_string($_POST['ip']);
	$SearchIPQuery = " AND h1.IP = '$SearchIP' ";
}

View::show_header("IP address history for $Username");
?>
<script type="text/javascript">//<![CDATA[
function ShowIPs(rowname) {
	$('tr[name="' + rowname + '"]').gtoggle();

}
function Ban(ip, id, elemID) {
	var notes = prompt("Enter notes for this ban");
	if (notes != null && notes.length > 0) {
		var xmlhttp;
		if (window.XMLHttpRequest) {
			xmlhttp = new XMLHttpRequest();
		} else {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange=function() {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				document.getElementById(elemID).innerHTML = "<strong>[Banned]</strong>";
			}
		}
		xmlhttp.open("GET", "tools.php?action=quick_ban&perform=create&ip=" + ip + "&notes=" + notes, true);
		xmlhttp.send();
	}

}
/*
function UnBan(ip, id, elemID) {
		var xmlhttp;
		if (window.XMLHttpRequest) {
			xmlhttp = new XMLHttpRequest();
		} else {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				document.getElementById(elemID).innerHTML = "Ban";
				document.getElementById(elemID).onclick = function() { Ban(ip, id, elemID); return false; };
			}
		}
		xmlhttp.open("GET","tools.php?action=quick_ban&perform=delete&id=" + id + "&ip=" + ip, true);
		xmlhttp.send();
}
*/
//]]>
</script>
<?
list($Page, $Limit) = Format::page_limit(IPS_PER_PAGE);

if ($UsersOnly == 1) {
	$RS = $DB->query("
		SELECT
			SQL_CALC_FOUND_ROWS
			h1.IP,
			h1.StartTime,
			h1.EndTime,
			GROUP_CONCAT(h2.UserID SEPARATOR '|'),
			GROUP_CONCAT(h2.StartTime SEPARATOR '|'),
			GROUP_CONCAT(IFNULL(h2.EndTime,0) SEPARATOR '|'),
			GROUP_CONCAT(um2.Username SEPARATOR '|'),
			GROUP_CONCAT(um2.Enabled SEPARATOR '|'),
			GROUP_CONCAT(ui2.Donor SEPARATOR '|'),
			GROUP_CONCAT(ui2.Warned SEPARATOR '|')
		FROM users_history_ips AS h1
			LEFT JOIN users_history_ips AS h2 ON h2.IP = h1.IP AND h2.UserID != $UserID
			LEFT JOIN users_main AS um2 ON um2.ID = h2.UserID
			LEFT JOIN users_info AS ui2 ON ui2.UserID = h2.UserID
		WHERE h1.UserID = '$UserID'
			AND h2.UserID > 0 $SearchIPQuery
		GROUP BY h1.IP, h1.StartTime
		ORDER BY h1.StartTime DESC
		LIMIT $Limit");
} else {
	$RS = $DB->query("
		SELECT
			SQL_CALC_FOUND_ROWS
			h1.IP,
			h1.StartTime,
			h1.EndTime,
			GROUP_CONCAT(h2.UserID SEPARATOR '|'),
			GROUP_CONCAT(h2.StartTime SEPARATOR '|'),
			GROUP_CONCAT(IFNULL(h2.EndTime,0) SEPARATOR '|'),
			GROUP_CONCAT(um2.Username SEPARATOR '|'),
			GROUP_CONCAT(um2.Enabled SEPARATOR '|'),
			GROUP_CONCAT(ui2.Donor SEPARATOR '|'),
			GROUP_CONCAT(ui2.Warned SEPARATOR '|')
		FROM users_history_ips AS h1
			LEFT JOIN users_history_ips AS h2 ON h2.IP = h1.IP AND h2.UserID != $UserID
			LEFT JOIN users_main AS um2 ON um2.ID = h2.UserID
			LEFT JOIN users_info AS ui2 ON ui2.UserID = h2.UserID
		WHERE h1.UserID = '$UserID' $SearchIPQuery
		GROUP BY h1.IP, h1.StartTime
		ORDER BY h1.StartTime DESC
		LIMIT $Limit");
}
$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();
$DB->set_query_id($RS);

$Pages = Format::get_pages($Page, $NumResults, IPS_PER_PAGE, 9);

?>
<div class="thin">
	<div class="header">
		<h2>IP address history for <a href="/user.php?id=<?=$UserID?>"><?=$Username?></a></h2>
		<div class="linkbox">
<?	if ($UsersOnly) { ?>
			<a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>" class="brackets">View all IP addresses</a>
<?	} else { ?>
			<a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>&amp;usersonly=1" class="brackets">View IP addresses with users</a>
<?	} ?>
		</div>
<?	if ($Pages) { ?>
		<div class="linkbox pager"><?=$Pages?></div>
<?	} ?>
	</div>
	<table>
		<tr class="colhead">
			<td>IP address search</td>
		</tr>

		<tr><td>
			<form class="search_form" name="ip_log" method="post" action="">
				<input type="text" name="ip" />
				<input type="submit" value="Search" />
			</form>
		</td></tr>
	</table>

	<table id="iphistory">
		<tr class="colhead">
			<td>IP address</td>
			<td>Started <a href="#" onclick="$('#iphistory td:nth-child(2), #iphistory td:nth-child(4)').ghide(); $('#iphistory td:nth-child(3), #iphistory td:nth-child(5)').gshow(); return false;" class="brackets">Toggle</a></td>
			<td class="hidden">Started <a href="#" onclick="$('#iphistory td:nth-child(2), #iphistory td:nth-child(4)').gshow(); $('#iphistory td:nth-child(3), #iphistory td:nth-child(5)').ghide(); return false;" class="brackets">Toggle</a></td>
			<td>Ended</td>
			<td class="hidden">Ended</td>
			<td>Elapsed</td>
		</tr>
<?
$counter = 0;
$IPs = array();
$Results = $DB->to_array();
$CanManageIPBans = check_perms('admin_manage_ipbans');

foreach ($Results as $Index => $Result) {
	list($IP, $StartTime, $EndTime, $UserIDs, $UserStartTimes, $UserEndTimes, $Usernames, $UsersEnabled, $UsersDonor, $UsersWarned) = $Result;

	$HasDupe = false;
	$UserIDs = explode('|', $UserIDs);
	if (!$EndTime) {
		$EndTime = sqltime();
	}
	if ($UserIDs[0] != 0) {
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
				<?=$IP?> (<?=Tools::get_country_code_by_ajax($IP)?>)<?
	if ($CanManageIPBans) {
		if (!isset($IPs[$IP])) {
			$sql = "
				SELECT ID, FromIP, ToIP
				FROM ip_bans
				WHERE '".Tools::ip_to_unsigned($IP)."' BETWEEN FromIP AND ToIP
				LIMIT 1";
			$DB->query($sql);

			if ($DB->has_results()) {
				$IPs[$IP] = true;
?>
				<strong>[Banned]</strong>
<?
			} else {
				$IPs[$IP] = false;
?>
				<a id="<?=$counter?>" href="#" onclick="Ban('<?=$IP?>', '<?=$ID?>', '<?=$counter?>'); this.onclick = null; return false;" class="brackets">Ban</a>
<?
			}
			$counter++;
		}
	}
?>
				<br />
				<?=Tools::get_host_by_ajax($IP)?>
				<?=($HasDupe ? '<a href="#" onclick="ShowIPs('.$Index.'); return false;">('.count($UserIDs).')</a>' : '(0)')?>
			</td>
			<td><?=time_diff($StartTime)?></td>
			<td class="hidden"><?=$StartTime?></td>
			<td><?=time_diff($EndTime)?></td>
			<td class="hidden"><?=$EndTime?></td>
			<td><?//time_diff(strtotime($StartTime), strtotime($EndTime)); ?></td>
		</tr>
<?
	if ($HasDupe) {
		$HideMe = (count($UserIDs) > 10);
		foreach ($UserIDs as $Key => $Val) {
			if (!$UserEndTimes[$Key]) {
				$UserEndTimes[$Key] = sqltime();
			}
?>
		<tr class="rowb<?=($HideMe ? ' hidden' : '')?>" name="<?=$Index?>">
			<td>&nbsp;&nbsp;&#187;&nbsp;<?=Users::format_username($Val, true, true, true)?></td>
			<td><?=time_diff($UserStartTimes[$Key])?></td>
			<td class="hidden"><?=$UserStartTimes[$Key]?></td>
			<td><?=time_diff($UserEndTimes[$Key])?></td>
			<td class="hidden"><?=$UserEndTimes[$Key]?></td>
			<td><?//time_diff(strtotime($UserStartTimes[$Key]), strtotime($UserEndTimes[$Key])); ?></td>
		</tr>
<?

		}
	}
}
?>
	</table>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>
<?
View::show_footer();
