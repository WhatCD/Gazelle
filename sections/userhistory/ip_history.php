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
$UserInfo = Users::user_info($UserID);
if (!check_perms('users_view_ips', $UserInfo['Class'])) {
	error(403);
}

$UsersOnly = !empty($_GET['usersonly']);

if (!empty($_GET['ip']) && trim($_GET['ip']) != '') {
	$SearchIP = db_string(str_replace("*", "%", trim($_GET['ip'])));
	$SearchIPQuery = "AND IP LIKE '$SearchIP'";
} else {
	$SearchIPQuery = "";
}

View::show_header("IP address history for $UserInfo[Username]");
?>
<script type="text/javascript">//<![CDATA[
function Ban(ip, elemID) {
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
				document.getElementById(elemID).onclick = function() { Ban(ip, elemID); return false; };
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

if ($UsersOnly) {
	$DB->query("
		SELECT DISTINCT IP
		FROM users_history_ips
		WHERE UserID = '$UserID'
			$SearchIPQuery");

	if ($DB->has_results()) {
		$UserIPs = db_array($DB->collect('IP'), array(), true);
		$DB->query("
			SELECT DISTINCT IP
			FROM users_history_ips
			WHERE UserID != '$UserID'
				AND IP IN (" . implode(',', $UserIPs) . ")");
		unset($UserIPs);

		if ($DB->has_results()) {
			$OtherIPs = db_array($DB->collect('IP'), array(), true);
			$QueryID = $DB->query("
				SELECT
					SQL_CALC_FOUND_ROWS
					IP,
					StartTime,
					EndTime
				FROM users_history_ips
				WHERE UserID = '$UserID'
					AND IP IN (" . implode(',', $OtherIPs) . ")
				ORDER BY StartTime DESC
				LIMIT $Limit");
			unset($OtherIPs);
		}
	}
} else {
	$QueryID = $DB->query("
		SELECT
			SQL_CALC_FOUND_ROWS
			IP,
			StartTime,
			EndTime
		FROM users_history_ips
		WHERE UserID = '$UserID'
			$SearchIPQuery
		ORDER BY StartTime DESC
		LIMIT $Limit");
}

if (isset($QueryID)) {
	$DB->query('SELECT FOUND_ROWS()');
	list($NumResults) = $DB->next_record();
	$DB->set_query_id($QueryID);
	$Results = $DB->to_array(false, MYSQLI_ASSOC);
	$IPMatches = $IPMatchesUser = $IPMatchesIgnored = array();
} else {
	$NumResults = 0;
	$Results = array();
}

if (!empty($Results)) {
	$IPs = db_array($DB->collect('IP'), array(), true);
	$DB->query("
		SELECT
			UserID,
			IP,
			StartTime,
			EndTime
		FROM users_history_ips
		WHERE IP IN (" . implode(',', $IPs) . ")
			AND UserID != '$UserID'
			AND UserID != 0
		ORDER BY StartTime DESC");
	unset($IPs);

	while ($Match = $DB->next_record(MYSQLI_ASSOC)) {
		$OtherIP = $Match['IP'];
		$OtherUserID = $Match['UserID'];
		if (!isset($IPMatchesUser[$OtherIP][$OtherUserID])) {
			$IPMatchesUser[$OtherIP][$OtherUserID] = 0;
		}
		if ($IPMatchesUser[$OtherIP][$OtherUserID] < 500) {
			$IPMatches[$OtherIP][] = $Match;
		} else {
			if (!isset($IPMatchesIgnored[$OtherIP][$OtherUserID])) {
				$IPMatchesIgnored[$OtherIP][$OtherUserID] = 0;
			}
			$IPMatchesIgnored[$OtherIP][$OtherUserID]++;
		}
		$IPMatchesUser[$OtherIP][$OtherUserID]++;
	}
}

$Pages = Format::get_pages($Page, $NumResults, IPS_PER_PAGE, 9);

?>
<div class="thin">
	<div class="header">
		<h2>IP address history for <a href="user.php?id=<?=$UserID?>"><?=$UserInfo['Username']?></a></h2>
		<div class="linkbox">
<?	if ($UsersOnly) { ?>
			<a href="userhistory.php?<?=Format::get_url(array('usersonly'))?>" class="brackets">View all IP addresses</a>
<?	} else { ?>
			<a href="userhistory.php?<?=Format::get_url()?>&amp;usersonly=1" class="brackets">View IP addresses with users</a>
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
			<form class="search_form" name="ip_log" method="get" action="">
				<input type="hidden" name="action" value="<?=$_GET['action']?>" />
				<input type="hidden" name="userid" value="<?=$UserID?>" />
<?	if ($UsersOnly) { ?>
				<input type="hidden" name="usersonly" value="1" />
<?	} ?>
				<input type="text" name="ip" value="<?=Format::form('ip')?>" />
				<input type="submit" value="Search" />
				Wildcard (*) search examples: 127.0.* or 1*2.0.*.1 or *.*.*.*
			</form>
		</td></tr>
	</table>

	<table id="iphistory">
		<tr class="colhead">
			<td>IP address</td>
			<td>Started <a href="#" onclick="$('#iphistory .reltime').gtoggle(); $('#iphistory .abstime').gtoggle(); return false;" class="brackets">Toggle</a></td>
			<td>Ended</td>
			<td class="hidden">Ended</td>
			<td>Elapsed</td>
		</tr>
<?
$Counter = 0;
$IPBanChecks = array();
$PrintedIPs = array();
$CanManageIPBans = check_perms('admin_manage_ipbans');
foreach ($Results as $Index => $Result) {
	$IP = $Result['IP'];
	$StartTime = $Result['StartTime'];
	$EndTime = $Result['EndTime'];
	if (!$Result['EndTime']) {
		$EndTime = sqltime();
	}
	$OtherUsers = isset($IPMatches[$IP]) ? $IPMatches[$IP] : array();
	$ElementID = 'ip_' . strtr($IP, '.', '-');
	$FirstOccurrence = !isset($IPIndexes[$IP]);
	if ($FirstOccurrence) {
		$IPIndexes[$IP] = $Index;
	}
?>
		<tr class="rowa" <?=$FirstOccurrence ? "id=\"$ElementID\"" : ''?>>
			<td>
				<?=$IP?> (<?=Tools::get_country_code_by_ajax($IP)?>)<?
	if ($CanManageIPBans) {
		if (!isset($IPBanChecks[$IP])) {
			if (Tools::site_ban_ip($IP)) {
				$IPBanChecks[$IP] = true;
?>
				<strong>[Banned]</strong>
<?
			} else {
				$IPBanChecks[$IP] = false;
?>
				<a id="<?=$Counter?>" href="#" onclick="Ban('<?=$IP?>', '<?=$Counter?>'); this.onclick = null; return false;" class="brackets">Ban</a>
<?
			}
			$Counter++;
		}
	}
?>
				<br />
				<?=Tools::get_host_by_ajax($IP)?>
<?
	if (!empty($OtherUsers)) {
		if ($FirstOccurrence || count($OtherUsers) <= 100) {
?>
				<a href="#" onclick="$('.otherusers' + <?=$Index?>).gtoggle(); return false;">(<?=count($OtherUsers)?>)</a>
<?
		} else {
?>
				<a href="#<?=$ElementID?>" onclick="$('.otherusers' + <?=$IPIndexes[$IP]?>).gshow();">(<?=count($OtherUsers)?>)</a>
<?
		}
	} else {
?>
				(0)
<?
	}
?>
			</td>
			<td>
				<span class="reltime"><?=time_diff($StartTime)?></span>
				<span class="abstime hidden"><?=$StartTime?></span>
			</td>
			<td>
				<span class="reltime"><?=time_diff($EndTime)?></span>
				<span class="abstime hidden"><?=$EndTime?></span>
			</td>
			<td><?//time_diff(strtotime($StartTime), strtotime($EndTime)); ?></td>
		</tr>
<?
	if (!empty($OtherUsers) && ($FirstOccurrence || count($OtherUsers) < 100)) {
		$HideMe = (count($OtherUsers) > 10);
		foreach ($OtherUsers as $OtherUser) {
			if (!$OtherUser['EndTime']) {
				$OtherUser['EndTime'] = sqltime();
			}
?>
		<tr class="rowb otherusers<?=$Index?><?=($HideMe ? ' hidden' : '')?>">
			<td>&nbsp;&nbsp;&#187;&nbsp;<?=Users::format_username($OtherUser['UserID'], true, true, true)?></td>
			<td>
				<span class="reltime"><?=time_diff($OtherUser['StartTime'])?></span>
				<span class="hidden abstime"><?=$OtherUser['StartTime']?></span>
			</td>
			<td>
				<span class="reltime"><?=time_diff($OtherUser['EndTime'])?></span>
				<span class="hidden abstime"><?=$OtherUser['EndTime']?></span>
			</td>
			<td><?//time_diff(strtotime($OtherUser['StartTime']), strtotime($OtherUser['EndTime'])); ?></td>
		</tr>
<?
		}
		if (isset($IPMatchesIgnored[$IP])) {
			foreach ($IPMatchesIgnored[$IP] as $OtherUserID => $MatchCount) {
?>
		<tr class="rowb otherusers<?=$Index?><?=($HideMe ? ' hidden' : '')?>">
			<td colspan="4">&nbsp;&nbsp;&#187;&nbsp;<?=$MatchCount?> matches skipped for <?=Users::format_username($OtherUserID, false, false, false)?></td>
		</tr>
<?
			}
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
