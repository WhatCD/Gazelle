<?
if (!check_perms("users_mod")) {
	error(403);
}

View::show_header("BBCode Sandbox", 'bbcode_sandbox');
?>
<div class="thin">
	<textarea id="sandbox" class="wbbarea" style="width: 98%;" onkeyup="resize('sandbox');" name="body" cols="90" rows="8"></textarea>
	<br />
	<br />
	<div id="preview" class="">
	</div>
</div>
<?
View::show_footer();