<?
if (!check_perms('users_mod')) {
	error(403);
}
$Title = "BBCode Sandbox";
View::show_header($Title, 'bbcode_sandbox');
?>
<div class="header">
	<h2><?=$Title?></h2>
</div>
<div class="thin">
	<textarea id="sandbox" class="wbbarea" style="width: 98%;" onkeyup="resize('sandbox');" name="body" cols="90" rows="8"></textarea>
	<br />
	<br />
	<div class="thin">
		<table class="forum_post wrap_overflow box vertical_margin">
			<tr>
				<td class="body" id="preview">
				</td>
			</tr>
		</table>
	</div>
</div>
<?
View::show_footer();
