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
<div class="thin float_clear">
	<div class="two_columns pad">
<?
if (check_perms('admin_reports')) {
$DB->query("
	SELECT um.Username,
		COUNT(r.ID) AS Reports
	FROM reports AS r
		JOIN users_main AS um ON um.ID = r.ResolverID
	WHERE r.ReportedTime > '2009-08-21 22:39:41'
		AND r.ReportedTime > NOW() - INTERVAL 24 HOUR
	GROUP BY r.ResolverID
	ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<h3><strong>Reports resolved in the last 24 hours</strong></h3>
		<table class="box border">
			<tr class="colhead">
				<td class="colhead_dark">Username</td>
				<td class="colhead_dark number_column">Reports</td>
			</tr>
<?
	foreach ($Results as $Result) {
		list($Username, $Reports) = $Result;
		if ($Username == $LoggedUser['Username']) {
			$RowClass = ' class="rowa"';
		} else {
			$RowClass = '';
		}
?>
			<tr<?=$RowClass?>>
				<td><?=$Username?></td>
				<td class="number_column"><?=number_format($Reports)?></td>
			</tr>
<?	} ?>
		</table>
<?
$DB->query("
	SELECT um.Username,
		COUNT(r.ID) AS Reports
	FROM reports AS r
		JOIN users_main AS um ON um.ID = r.ResolverID
	WHERE r.ReportedTime > '2009-08-21 22:39:41'
		AND r.ReportedTime > NOW() - INTERVAL 1 WEEK
	GROUP BY r.ResolverID
	ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<h3><strong>Reports resolved in the last week</strong></h3>
		<table class="box border">
			<tr class="colhead">
				<td class="colhead_dark">Username</td>
				<td class="colhead_dark number_column">Reports</td>
			</tr>
<?
	foreach ($Results as $Result) {
		list($Username, $Reports) = $Result;
		if ($Username == $LoggedUser['Username']) {
			$RowClass = ' class="rowa"';
		} else {
			$RowClass = '';
		}
?>
			<tr<?=$RowClass?>>
				<td><?=$Username?></td>
				<td class="number_column"><?=number_format($Reports)?></td>
			</tr>
<?	} ?>
		</table>
<?
$DB->query("
	SELECT um.Username,
		COUNT(r.ID) AS Reports
	FROM reports AS r
		JOIN users_main AS um ON um.ID = r.ResolverID
	WHERE r.ReportedTime > '2009-08-21 22:39:41'
		AND r.ReportedTime > NOW() - INTERVAL 1 MONTH
	GROUP BY r.ResolverID
	ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<h3><strong>Reports resolved in the last month</strong></h3>
		<table class="box border">
			<tr class="colhead">
				<td class="colhead_dark">Username</td>
				<td class="colhead_dark number_column">Reports</td>
			</tr>
<?
	foreach ($Results as $Result) {
		list($Username, $Reports) = $Result;
		if ($Username == $LoggedUser['Username']) {
			$RowClass = ' class="rowa"';
		} else {
			$RowClass = '';
		}
?>
			<tr<?=$RowClass?>>
				<td><?=$Username?></td>
				<td class="number_column"><?=number_format($Reports)?></td>
			</tr>
<?	} ?>
		</table>
<?
$DB->query("
	SELECT um.Username,
		COUNT(r.ID) AS Reports
	FROM reports AS r
		JOIN users_main AS um ON um.ID = r.ResolverID
	GROUP BY r.ResolverID
	ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<h3><strong>Reports resolved since "other" reports (2009-08-21)</strong></h3>
		<table class="box border">
			<tr class="colhead">
				<td class="colhead_dark">Username</td>
				<td class="colhead_dark number_column">Reports</td>
			</tr>
<?
	foreach ($Results as $Result) {
		list($Username, $Reports) = $Result;
		if ($Username == $LoggedUser['Username']) {
			$RowClass = ' class="rowa"';
		} else {
			$RowClass = '';
		}
?>
			<tr<?=$RowClass?>>
				<td><?=$Username?></td>
				<td class="number_column"><?=number_format($Reports)?></td>
			</tr>
<?	} ?>
		</table>
<?
} //if (check_perms('admin_reports')) ?>
	</div>
	<div class="two_columns pad">
<?

	$TrashForumIDs = '12';

	$DB->query("
		SELECT u.Username,
			COUNT(f.LastPostAuthorID) as Trashed
		FROM forums_topics AS f
			LEFT JOIN users_main AS u ON u.ID = f.LastPostAuthorID
		WHERE f.ForumID IN ($TrashForumIDs)
		GROUP BY f.LastPostAuthorID
		ORDER BY Trashed DESC
		LIMIT 30");
	$Results = $DB->to_array();
?>
		<h3><strong>Threads trashed since the beginning of time</strong></h3>
		<table class="box border">
			<tr class="colhead">
				<td class="colhead_dark number_column">Place</td>
				<td class="colhead_dark">Username</td>
				<td class="colhead_dark number_column">Trashed</td>
			</tr>
<?
	$i = 1;
	foreach ($Results as $Result) {
		list($Username, $Trashed) = $Result;
		if ($Username == $LoggedUser['Username']) {
			$RowClass = ' class="rowa"';
		} else {
			$RowClass = '';
		}
?>
			<tr<?=$RowClass?>>
				<td class="number_column"><?=$i?></td>
				<td><?=$Username?></td>
				<td class="number_column"><?=number_format($Trashed)?></td>
			</tr>
<?
		$i++;
	}
?>
		</table>
	</div>
</div>
<?
View::show_footer();
?>
