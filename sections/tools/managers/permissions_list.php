<?
View::show_header('Manage Permissions');
?>
<script type="text/javascript">//<![CDATA[
function confirmDelete(id) {
	if (confirm("Are you sure you want to remove this permission class?")) {
		location.href = "tools.php?action=permissions&removeid=" + id;
	}
	return false;
}
//]]>
</script>
<div class="thin">
	<div class="header">
		<div class="linkbox">
			<a href="tools.php?action=permissions&amp;id=new" class="brackets">Create a new permission set</a>
			<a href="tools.php" class="brackets">Back to tools</a>
		</div>
	</div>
<?
$DB->query("
	SELECT
		p.ID,
		p.Name,
		p.Level,
		p.Secondary,
		COUNT(u.ID) + COUNT(DISTINCT l.UserID)
	FROM permissions AS p
		LEFT JOIN users_main AS u ON u.PermissionID = p.ID
		LEFT JOIN users_levels AS l ON l.PermissionID = p.ID
	GROUP BY p.ID
	ORDER BY p.Secondary ASC, p.Level ASC");
if ($DB->has_results()) {
?>
	<table width="100%">
		<tr class="colhead">
			<td>Name</td>
			<td>Level</td>
			<td>User count</td>
			<td class="center">Actions</td>
		</tr>
<?	while (list($ID, $Name, $Level, $Secondary, $UserCount) = $DB->next_record()) { ?>
		<tr>
			<td><?=display_str($Name); ?></td>
			<td><?=($Secondary ? 'Secondary' : $Level) ?></td>
			<td><?=number_format($UserCount); ?></td>
			<td class="center">
				<a href="tools.php?action=permissions&amp;id=<?=$ID ?>" class="brackets">Edit</a>
				<a href="#" onclick="return confirmDelete(<?=$ID?>);" class="brackets">Remove</a>
			</td>
		</tr>
<?	} ?>
	</table>
<?
} else { ?>
	<h2 align="center">There are no permission classes.</h2>
<?
} ?>
</div>
<?
View::show_footer();
?>
