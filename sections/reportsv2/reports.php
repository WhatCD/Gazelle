<?
/*
 * This is the outline page for auto reports, it calls the AJAX functions
 * that actually populate the page and shows the proper header and footer.
 * The important function is AddMore().
 */
if(!check_perms('admin_reports')){
	error(403);
}

show_header('Reports V2!', 'reportsv2');
?>
<script type="text/javascript">
function Taste(torrent_id, report_id, taste) {
	ajax.get('reportsv2.php?action=ajax_taste&torrent_id='+torrent_id+'&report_id='+report_id+'&taste='+taste, function(data) {
		if (data == '1') {
			$('#taste' + torrent_id).raw().innerHTML = '[Omnomnom]';
			Grab(report_id);
		} else {
			alert(data);
		}
	});
}
</script>
<?
include('header.php');
?>
<h2>New reports, auto assigned!</h2>
<div class="buttonbox thin center">
	<input type="button" onclick="AddMore();" value="Add More" /><input type="text" name="repop_amount" id="repop_amount" size="2" value="10" />
	| <span title="Changes whether to automatically replace resolved ones with new ones"><input type="checkbox" checked="checked" id="dynamic"/> <label for="dynamic">Dynamic</label></span>
	| <span title="Resolves *all* checked reports with their respective resolutions"><input type="button" onclick="MultiResolve();" value="Multi-Resolve" /></span>
	| <span title="Un-In Progress all the reports currently displayed"><input type="button" onclick="GiveBack();" value="Give back all" /></span>
</div>
<br />
<div id="all_reports" style="width: 80%; margin-left: auto; margin-right: auto">
</div>
<?
show_footer();
?>
