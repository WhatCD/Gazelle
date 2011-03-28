<?
if(!check_perms('site_view_flow')) { error(403); }
show_header('Upscale Pool');
define('USERS_PER_PAGE', 50);
list($Page,$Limit) = page_limit(USERS_PER_PAGE);

$RS = $DB->query("SELECT 
	SQL_CALC_FOUND_ROWS
	m.ID,
	m.Username,
	m.Uploaded,
	m.Downloaded,
	m.PermissionID,
	m.Enabled,
	i.Donor,
	i.Warned,
	i.JoinDate,
	i.RatioWatchEnds,
	i.RatioWatchDownload,
	m.RequiredRatio
	FROM users_main AS m 
	LEFT JOIN users_info AS i ON i.UserID=m.ID
	WHERE i.RatioWatchEnds != '0000-00-00 00:00:00'
	AND m.Enabled = '1'
	ORDER BY i.RatioWatchEnds ASC LIMIT $Limit");
$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$DB->query("SELECT COUNT(UserID) FROM users_info WHERE BanDate != '0000-00-00 00:00:00' AND BanReason = '2'");
list($TotalDisabled) = $DB->next_record();
$DB->set_query_id($RS);

if($DB->record_count()) {
?>
		<div class="box pad">
		<p>There are currently <?=number_format($Results)?> users queued by the system and <?=number_format($TotalDisabled)?> already disabled.</p>
	</div>
	<div class="linkbox">
<?
	$Pages=get_pages($Page,$Results,USERS_PER_PAGE,11) ;
	echo $Pages;
?>
	</div>
	<table width="100%">
		<tr class="colhead">
			<td>User</td>
			<td>Up</td>
			<td>Down</td>
			<td>Ratio</td>
			<td>Required Ratio</td>
			<td>Defecit</td>
			<td>Gamble</td>
			<td>Registered</td>
			<td>Remaining</td>
			<td>Lifespan</td>
		</tr>
<?
	while(list($UserID, $Username, $Uploaded, $Downloaded, $PermissionID, $Enabled, $Donor, $Warned, $Joined, $RatioWatchEnds, $RatioWatchDownload, $RequiredRatio)=$DB->next_record()) {
	$Row = ($Row == 'b') ? 'a' : 'b';

?>
		<tr class="row<?=$Row?>">
			<td><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?></td>
			<td><?=get_size($Uploaded)?></td>
			<td><?=get_size($Downloaded)?></td>
			<td><?=ratio($Uploaded, $Downloaded)?></td>
			<td><?=number_format($RequiredRatio, 2)?></td>
			<td><? if(($Downloaded*$RequiredRatio)>$Uploaded) { echo get_size(($Downloaded*$RequiredRatio)-$Uploaded);}?></td>
			<td><?=get_size($Downloaded-$RatioWatchDownload)?></td>
			<td><?=time_diff($Joined,2)?></td>
			<td><?=time_diff($RatioWatchEnds)?></td>
			<td><?//time_diff(strtotime($Joined), strtotime($RatioWatchEnds))?></td>
		</tr>
<?	} ?>
	</table>
	<div class="linkbox">
<? echo $Pages; ?>
	</div>
<? } else { ?>
	<h2 align="center">There are currently no users on ratio watch.</h2>
<? }
show_footer();
?>
