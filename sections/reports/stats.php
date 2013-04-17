<?

if (!check_perms('admin_reports') && !check_perms('site_moderate_forums')) {
	error(403);
}
View::show_header('Other reports stats');

?>
<div class="header">
	<h2>Other reports stats!</h2>
	<div class="linkbox">
		<a href="reports.php">New</a> |
		<a href="reports.php?view=old">Old</a> |
		<a href="reports.php?action=stats">Stats</a>
	</div>
</div>
<div class="box pad thin" style="padding: 0px 0px 0px 20px; margin-left: auto; margin-right: auto;">
	<table class="layout">
<?
if (check_perms('admin_reports')) :
$DB->query("SELECT um.Username,
				COUNT(r.ID) AS Reports
			FROM reports AS r
				JOIN users_main AS um ON um.ID=r.ResolverID
			WHERE r.ReportedTime > '2009-08-21 22:39:41'
				AND r.ReportedTime > NOW() - INTERVAL 24 HOUR
			GROUP BY r.ResolverID
			ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<tr>
		<td class="label"><strong>Reports resolved in the last 24 hours</strong></td>
		<td>
		<table style="width: 50%; margin-left: auto; margin-right: auto;" class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<?	foreach ($Results as $Result) {
		list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=number_format($Reports)?></td>
			</tr>
<?	} ?>
		</table>
		</td>
		</tr>
		<tr>
<?
$DB->query("SELECT um.Username,
				COUNT(r.ID) AS Reports
			FROM reports AS r
				JOIN users_main AS um ON um.ID=r.ResolverID
			WHERE r.ReportedTime > '2009-08-21 22:39:41'
				AND r.ReportedTime > NOW() - INTERVAL 1 WEEK
			GROUP BY r.ResolverID
			ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<td class="label"><strong>Reports resolved in the last week</strong></td>
		<td>
		<table style="width: 50%; margin-left: auto; margin-right: auto;" class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<?	foreach ($Results as $Result) {
		list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=number_format($Reports)?></td>
			</tr>
<?	} ?>
		</table>
		</td>
		</tr>
		<tr>
<?
$DB->query("SELECT um.Username,
				COUNT(r.ID) AS Reports
			FROM reports AS r
				JOIN users_main AS um ON um.ID=r.ResolverID
			WHERE r.ReportedTime > '2009-08-21 22:39:41'
				AND r.ReportedTime > NOW() - INTERVAL 1 MONTH
			GROUP BY r.ResolverID
			ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<td class="label"><strong>Reports resolved in the last month</strong></td>
		<td>
		<table style="width: 50%; margin-left: auto; margin-right: auto;" class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<?	foreach ($Results as $Result) {
		list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=number_format($Reports)?></td>
			</tr>
<?	} ?>
		</table>
		</td>
		</tr>
		<tr>
<?
$DB->query("SELECT um.Username,
				COUNT(r.ID) AS Reports
			FROM reports AS r
				JOIN users_main AS um ON um.ID=r.ResolverID
			GROUP BY r.ResolverID
			ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<td class="label"><strong>Reports resolved since "other" reports (2009-08-21)</strong></td>
		<td>
		<table style="width: 50%; margin-left: auto; margin-right: auto;" class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<?	foreach ($Results as $Result) {
		list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=number_format($Reports)?></td>
			</tr>
<?	} ?>
		</table>
		</td>
		</tr>
<? endif; ?>
		<tr>
<?
			$DB->query("SELECT u.Username,
							count(LastPostAuthorID) as Trashed
						FROM forums_topics as f
						LEFT JOIN users_main as u on u.id = LastPostAuthorID
						WHERE ForumID = 12
						GROUP BY LastPostAuthorID
						ORDER BY Trashed DESC
						LIMIT 30;");
				$Results = $DB->to_array();
				?>
			<td class="label"><strong>Threads trashed since the beginning of time</strong></td>
			<td>
				<table style="width: 50%; margin-left: auto; margin-right: auto;" class="border">
					<tr>
						<td class="head colhead_dark">Place</td>
						<td class="head colhead_dark">Username</td>
						<td class="head colhead_dark">Trashed</td>
					</tr>
<?
				$i = 1;
				foreach ($Results as $Result) {
					list($Username, $Trashed) = $Result;
						?>
					<tr>
						<td><?=$i?></td>
						<td><?=$Username?></td>
						<td><?=number_format($Trashed)?></td>
					</tr>
<?					$i++;
				} ?>
					</table>
				</td>
			</tr>
		</table>
</div>
<?
View::show_footer();
?>
