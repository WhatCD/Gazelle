<?
show_header('Recover Password','validate');
echo $Validate->GenerateJS('recoverform');
?>
<form name="recoverform" id="recoverform" method="post" action="" onsubmit="return formVal();">
	<input type="hidden" name="key" value="<?=display_str($_REQUEST['key'])?>" />
	<div style="width:320px;">
		<font class="titletext">Reset your password - Final Step</font><br /><br />
<?
if(empty($Reset)) {
	if(!empty($Err)) {
?>
		<font color="red"><strong><?=display_str($Err)?></strong></font><br /><br />
<?	} ?>
		Please choose a password between 6 and 15 characters long<br /><br />
		<table cellpadding="2" cellspacing="1" border="0" align="center">
			<tr valign="top">
				<td align="right">Password&nbsp;</td>
				<td align="left"><input type="password" name="password" id="password" class="inputtext" /></td>
			</tr>
			<tr valign="top">
				<td align="right">Confirm Password&nbsp;</td>
				<td align="left"><input type="password" name="verifypassword" id="verifypassword" class="inputtext" /></td>
			</tr>
			<tr>
				<td colspan="2" align="right"><input type="submit" name="reset" value="Reset!" class="submit" /></td>
			</tr>
		</table>
<? } else { ?>
		Your password has been succesfully reset.<br />
		Please <a href="login.php">click here</a> to log in using your new password.
<? } ?>
	</div>
</form>
<?
show_footer();
?>
