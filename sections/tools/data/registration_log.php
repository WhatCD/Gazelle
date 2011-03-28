<?
if(!check_perms('users_view_ips') || !check_perms('users_view_email')) { error(403); }
show_header('Registration log');
define('USERS_PER_PAGE', 50);
list($Page,$Limit) = page_limit(USERS_PER_PAGE);


$RS = $DB->query("SELECT 
	SQL_CALC_FOUND_ROWS
	m.ID,
	m.IP,
	m.Email,
	m.Username,
	m.PermissionID,
	m.Uploaded,
	m.Downloaded,
	m.Enabled,
	i.Donor,
	i.Warned,
	i.JoinDate,
	(SELECT COUNT(h1.UserID) FROM users_history_ips AS h1 WHERE h1.IP=m.IP) AS Uses,
	im.ID,
	im.IP,
	im.Email,
	im.Username,
	im.PermissionID,
	im.Uploaded,
	im.Downloaded,
	im.Enabled,
	ii.Donor,
	ii.Warned,
	ii.JoinDate,
	(SELECT COUNT(h2.UserID) FROM users_history_ips AS h2 WHERE h2.IP=im.IP) AS InviterUses 
	FROM users_main AS m 
	LEFT JOIN users_info AS i ON i.UserID=m.ID
	LEFT JOIN users_main AS im ON i.Inviter = im.ID
	LEFT JOIN users_info AS ii ON i.Inviter = ii.UserID
	WHERE i.JoinDate > '".time_minus(3600*24*3)."' 
	ORDER BY i.Joindate DESC LIMIT $Limit");
$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$DB->set_query_id($RS);

if($DB->record_count()) {
?>
	<div class="linkbox">
<?
	$Pages=get_pages($Page,$Results,USERS_PER_PAGE,11) ;
	echo $Pages;
?>
	</div>
	<table width="100%">
		<tr class="colhead">
			<td>User</td>
			<td>Ratio</td>
			<td>Email</td>
			<td>IP</td>
			<td>Host</td>
			<td>Registered</td>
		</tr>
<?
	while(list($UserID, $IP, $Email, $Username, $PermissionID, $Uploaded, $Downloaded, $Enabled, $Donor, $Warned, $Joined, $Uses, $InviterID, $InviterIP, $InviterEmail, $InviterUsername, $InviterPermissionID, $InviterUploaded, $InviterDownloaded, $InviterEnabled, $InviterDonor, $InviterWarned, $InviterJoined, $InviterUses)=$DB->next_record()) {
	$Row = ($IP == $InviterIP) ? 'a' : 'b';
?>
		<tr class="row<?=$Row?>">
			<td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?><br /><?=format_username($InviterID, $InviterUsername, $InviterDonor, $InviterWarned, $InviterEnabled, $InviterPermissionID)?></td>
			<td><?=ratio($Uploaded,$Downloaded)?><br /><?=ratio($InviterUploaded,$InviterDownloaded)?></td>
			<td>
				<span style="float:left;"><?=display_str($Email)?></span>
				<span style="float:right;">[<a href="userhistory.php?action=email&amp;userid=<?=$UserID?>" title="History">H</a>|<a href="/user.php?action=search&email_history=on&email=<?=display_str($Email)?>" title="Search">S</a>]</span><br />
				<span style="float:left;"><?=display_str($InviterEmail)?></span>
				<span style="float:right;">[<a href="userhistory.php?action=email&amp;userid=<?=$InviterID?>" title="History">H</a>|<a href="/user.php?action=search&amp;email_history=on&amp;email=<?=display_str($InviterEmail)?>" title="Search">S</a>]</span><br />
			</td>
			<td>
				<span style="float:left;"><?=display_str($IP)?></span>
				<span style="float:right;"><?=display_str($Uses)?> [<a href="userhistory.php?action=ips&amp;userid=<?=$UserID?>" title="History">H</a>|<a href="/user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($IP)?>" title="Search">S</a>]</span><br />
				<span style="float:left;"><?=display_str($InviterIP)?></span>
				<span style="float:right;"><?=display_str($InviterUses)?> [<a href="userhistory.php?action=ips&amp;userid=<?=$InviterID?>" title="History">H</a>|<a href="/user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($InviterIP)?>" title="Search">S</a>]</span><br />
			</td>
			<td>
				<?=get_host($IP)?><br />
				<?=get_host($InviterIP)?>
			</td>
			<td><?=time_diff($Joined)?><br /><?=time_diff($InviterJoined)?></td>
		</tr>
<?	} ?>
	</table>
	<div class="linkbox">
<? echo $Pages; ?>
	</div>
<? } else { ?>
	<h2 align="center">There have been no new registrations in the past 72 hours.</h2>
<? }
show_footer();
?>
