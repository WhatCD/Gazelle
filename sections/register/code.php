<?
View::show_header('Register');
?>
<div style="width: 500px;">
	<form class="auth_form" name="invite" method="get" action="register.php">
	Please enter your invite code into the box below.<br /><br />
	<table class="layout" cellpadding="2" cellspacing="1" border="0" align="center">
		<tr valign="top">
			<td align="right">Invite&nbsp;</td>
			<td align="left"><input type="text" name="invite" id="invite" class="inputtext" /></td>
		</tr>
		<tr>
			<td colspan="2" align="right"><input type="submit" name="submit" value="Begin!" class="submit" /></td>
		</tr>
	</table>
	</form>
</div>
<?
View::show_footer();
?>
