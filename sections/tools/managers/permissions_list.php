<?
show_header('Manage Permissions');
?>
<script type="text/javascript" language="javascript">
//<![CDATA[
function confirmDelete(id) {
	if (confirm("Are you sure you want to remove this permission class?")) {
		location.href="tools.php?action=permissions&removeid="+id;
	}
	return false;
}
//]]>
</script>
<div class="thin">
	<div class="linkbox">
		[<a href="tools.php?action=permissions&amp;id=new">Create a new permission set</a>]
		[<a href="tools.php">Back to Tools</a>]
	</div>
<?
$DB->query("SELECT p.ID,p.Name,p.Level,COUNT(u.ID) FROM permissions AS p LEFT JOIN users_main AS u ON u.PermissionID=p.ID GROUP BY p.ID ORDER BY p.Level ASC");
if($DB->record_count()) {
?>
	<table width="100%">
		<tr class="colhead">
			<td>Name</td>
			<td>Level</td>
			<td>User Count</td>
			<td class="center">Actions</td>
		</tr>
<?	while(list($ID,$Name,$Level,$UserCount)=$DB->next_record()) { ?>
		<tr>
			<td><?=display_str($Name); ?></td>
			<td><?=$Level; ?></td>
			<td><?=number_format($UserCount); ?></td>
			<td class="center">[<a href="tools.php?action=permissions&amp;id=<?=$ID ?>">Edit</a> | <a href="#" onclick="return confirmDelete(<?=$ID?>)">Remove</a>]</td>
		</tr>
<?	} ?>
	</table>
<? } else { ?>
	<h2 align="center">There are no permission classes.</h2>
<? } ?>
</div>
<?
show_footer();
?>
