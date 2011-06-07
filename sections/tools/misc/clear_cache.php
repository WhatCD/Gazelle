<?
if(!check_perms('users_mod') || !check_perms('admin_clear_cache')) {
	error(403);
}

show_header('Clear a cache key');

//Make sure the form was sent
if(!empty($_GET['key']) && $_GET['type'] == "clear") {
	if(preg_match('/(.*?)(\d+)\.\.(\d+)$/', $_GET['key'], $Matches) && is_number($Matches[2]) && is_number($Matches[3])) {
		for($i=$Matches[2]; $i<=$Matches[3]; $i++) {
			$Cache->delete_value($Matches[1].$i);
		}
		echo '<div class="save_message">Keys '.display_str($_GET['key']).' cleared!</div>';
	} else {
		$Cache->delete_value($_GET['key']);
		echo '<div class="save_message">Key '.display_str($_GET['key']).' cleared!</div>';
	}
}
?>
	<h2>Clear a cache key</h2>
	
	<form method="get" action="" name="clear_cache">
		<input type="hidden" name="action" value="clear_cache" />
		<table cellpadding="2" cellspacing="1" border="0" align="center">
			<tr valign="top">
				<td align="right">Key</td>
				<td align="left">
					<input type="text" name="key" id="key" class="inputtext" />
					<select name="type">
						<option value="view">View</option>
						<option value="clear">Clear</option>
					</select>
					<input type="submit" value="key" class="submit" />
				</td>
			</tr>
<? if(!empty($_GET['key']) && $_GET['type'] == "view") { ?>
			<tr>
				<td colspan="2">
					<pre><? var_dump($Cache->get_value($_GET['key'])); ?></pre>
				</td>
			</tr>
<? } ?>
		</table>
	</form>
<?
show_footer();
