<?

if(!check_perms('admin_reports')){
	error(403);
}

show_header('Other reports stats');

?>
<h2>Other reports stats!</h2>
<br />
<div class="box pad thin" style="padding: 0px 0px 0px 20px; margin-left: auto; margin-right: auto">
<?
$DB->query("SELECT um.Username, COUNT(r.ID) AS Reports FROM reports AS r JOIN users_main AS um ON um.ID=r.ResolverID WHERE r.ReportedTime > '2009-08-21 22:39:41' AND r.ReportedTime > NOW() - INTERVAL 24 HOUR GROUP BY r.ResolverID ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<table>
		<tr>
		<td class="label"><strong>Reports resolved in the last 24h</strong></td>
		<td>
		<table style="width: 50%; margin-left: auto; margin-right: auto;" class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<? foreach($Results as $Result) {
	list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=$Reports?></td>
			</tr>
<? } ?>
		</table>
		</td>
		</tr>
		<tr>
<?
$DB->query("SELECT um.Username, COUNT(r.ID) AS Reports FROM reports AS r JOIN users_main AS um ON um.ID=r.ResolverID WHERE r.ReportedTime > '2009-08-21 22:39:41' AND r.ReportedTime > NOW() - INTERVAL 1 WEEK GROUP BY r.ResolverID ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<td class="label"><strong>Reports resolved in the last week</strong></td>
		<td>
		<table style="width: 50%; margin-left: auto; margin-right: auto;" class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<? foreach($Results as $Result) {
	list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=$Reports?></td>
			</tr>
<? } ?>
		</table>
		</td>
		<tr>
<?
$DB->query("SELECT um.Username, COUNT(r.ID) AS Reports FROM reports AS r JOIN users_main AS um ON um.ID=r.ResolverID WHERE r.ReportedTime > '2009-08-21 22:39:41' AND r.ReportedTime > NOW() - INTERVAL 1 MONTH GROUP BY r.ResolverID ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<td class="label"><strong>Reports resolved in the last month</strong></td>
		<td>
		<table style="width: 50%; margin-left: auto; margin-right: auto;" class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<? foreach($Results as $Result) {
	list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=$Reports?></td>
			</tr>
<? } ?>
		</table>
		</td>
		</tr>
		<tr>
<?
$DB->query("SELECT um.Username, COUNT(r.ID) AS Reports FROM reports AS r JOIN users_main AS um ON um.ID=r.ResolverID GROUP BY r.ResolverID ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<td class="label"><strong>Reports resolved since 'other' reports (2009-08-21)</strong></td>
		<td>
		<table style="width: 50%; margin-left: auto; margin-right: auto;" class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<? foreach($Results as $Result) {
	list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=$Reports?></td>
			</tr>
<? } ?>
		</table>
		</td>
		</tr>
		</table>
</div>
<?
show_footer();
?>
