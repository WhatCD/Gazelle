<?
if(!check_perms('users_view_ips')) { error(403); }
show_header('Dupe IPs');
define('USERS_PER_PAGE', 50);
define('IP_OVERLAPS', 5);
list($Page,$Limit) = page_limit(USERS_PER_PAGE);


$RS = $DB->query("SELECT 
	SQL_CALC_FOUND_ROWS
	m.ID,
	m.IP,
	m.Username,
	m.PermissionID,
	m.Enabled,
	i.Donor,
	i.Warned,
	i.JoinDate,
	(SELECT COUNT(DISTINCT h.UserID) FROM users_history_ips AS h WHERE h.IP=m.IP) AS Uses
	FROM users_main AS m 
	LEFT JOIN users_info AS i ON i.UserID=m.ID
	WHERE (SELECT COUNT(DISTINCT h.UserID) FROM users_history_ips AS h WHERE h.IP=m.IP) >= ".IP_OVERLAPS."
	AND m.Enabled = '1'
	AND m.IP != '127.0.0.1'
	ORDER BY Uses DESC LIMIT $Limit");
$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$DB->set_query_id($RS);

if($DB->record_count()) {
?>
	<div class="linkbox">
<?
	$Pages=get_pages($Page,$Results,USERS_PER_PAGE, 11) ;
	echo $Pages;
?>
	</div>
	<table width="100%">
		<tr class="colhead">
			<td>User</td>
			<td>IP</td>
			<td>Dupes</td>
			<td>Registered</td>
		</tr>
<?
	while(list($UserID, $IP, $Username, $PermissionID, $Enabled, $Donor, $Warned, $Joined, $Uses)=$DB->next_record()) {
	$Row = ($Row == 'b') ? 'a' : 'b';
?>
		<tr class="row<?=$Row?>">
			<td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?></td>
			<td><span style="float:left;"><?=get_host($IP)." ($IP)"?></span><span style="float:right;">[<a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>" title="History">H</a>|<a href="user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($IP)?>" title="Search">S</a>]</span></td>
			<td><?=display_str($Uses)?></td>
			<td><?=time_diff($Joined)?></td>
		</tr>
<?	} ?>
	</table>
	<div class="linkbox">
<? echo $Pages; ?>
	</div>
<? } else { ?>
	<h2 align="center">There are currently no users with more than <?=IP_OVERLAPS?> IP overlaps.</h2>
<? }
show_footer();
?>
