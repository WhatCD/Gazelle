<?php
if (!check_perms('users_mod')) { error(403);
}

show_header('Common Snatches');


?>
<div class="header">
	<h2>Common Snatches</h2>
</div>
<table width="100%">
	<tr class="colhead">
		<td>User A</td>
		<td>User B</td>
		<td>Limit</td>
	</tr>
	<tr/>
	<tr>
		<form class="manage_form" name="common_snatches" method="post">
			<input type="hidden" name="compare" value="1" />
			<td>
			<input type="text" name="userida"/>
			</td>
			<td>
			<input type="text" name="useridb"/>
			</td>
			<td>
			<input type="text" name="limit" value="50"/>
			</td>
			<td>
			<input type="submit" value="Compare"/>
			</td>
		</form>
	</tr>
</table>

<?
if(isset($_POST['compare'])) {
	if (isset($_POST['userida']) && is_numeric($_POST['userida']) && isset($_POST['useridb']) && is_numeric($_POST['useridb'])) {
		$UserIDA = (int) $_POST['userida'];
		$UserIDB = (int) $_POST['useridb'];
		if(isset($_POST['limit']) && is_numeric($_POST['limit'])) {
			$Limit = 'LIMIT ' . $_POST['limit'];
		}
		$DB->query("SELECT g.ID, g.Name FROM torrents AS t INNER JOIN torrents_group AS g ON g.ID = t.GroupID JOIN xbt_snatched AS xs ON xs.fid=t.ID WHERE xs.uid IN ($UserIDA,$UserIDB) HAVING COUNT(xs.fid) > 1 ORDER BY xs.tstamp DESC $LIMIT");
?>
<table width="80%">
	<tr class="colhead">
		<td>Torrent</td>
	</tr>
	<tr/>
<?
		while(list($GroupID, $GroupName) = $DB->next_record()) {
?>		
		<tr>
		<td>
		<a href="torrents.php?id=<?=$GroupID?>"><?=$GroupName?></a>
		</td>
		</tr>
<?
		}
?>
</table>
<?
	}
}

show_footer();
?>