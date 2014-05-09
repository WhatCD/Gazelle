<?
View::show_header('Recover Password');
echo $Validate->GenerateJS('recoverform');
?>
<script src="<?=(STATIC_SERVER)?>functions/validate.js" type="text/javascript"></script>
<script src="<?=(STATIC_SERVER)?>functions/password_validate.js" type="text/javascript"></script>
<form class="auth_form" name="recovery" id="recoverform" method="post" action="" onsubmit="return formVal();">
	<input type="hidden" name="key" value="<?=display_str($_REQUEST['key'])?>" />
	<div style="width: 500px;">
		<span class="titletext">Reset your password - Final Step</span><br /><br />
<?
if (empty($Reset)) {
	if (!empty($Err)) {
?>
		<strong class="important_text"><?=display_str($Err)?></strong><br /><br />
<?	} ?> A strong password is 8 characters or longer, contains at least 1 lowercase and uppercase letter, and contains at least a number or symbol.<br /><br />
		<table class="layout" cellpadding="2" cellspacing="1" border="0" align="center" width="100%">
			<tr valign="top">
				<td align="right" style="width: 100px;">Password&nbsp;</td>
				<td align="left"><input type="password" name="password" id="new_pass_1" class="inputtext" /> <strong id="pass_strength"></strong></td>
			</tr>
			<tr valign="top">
				<td align="right">Confirm Password&nbsp;</td>
				<td align="left"><input type="password" name="verifypassword" id="new_pass_2" class="inputtext" /> <strong id="pass_match"></strong></td>
			</tr>
			<tr>
				<td colspan="2" align="right"><input type="submit" name="reset" value="Reset!" class="submit" /></td>
			</tr>
		</table>
<? } else { ?>
		Your password has been successfully reset.<br />
		Please <a href="login.php">click here</a> to log in using your new password.
<? } ?>
	</div>
</form>
<?
View::show_footer();
?>
