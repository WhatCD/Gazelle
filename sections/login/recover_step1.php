<?
show_header('Recover Password','validate');
echo $Validate->GenerateJS('recoverform');
?>
<form name="recoverform" id="recoverform" method="post" action="" onsubmit="return formVal();">
	<div style="width:320px;">
		<font class="titletext">Reset your password - Step 1</font><br /><br />
<?
if(empty($Sent) || (!empty($Sent) && $Sent!=1)) {
	if(!empty($Err)) {
?>
		<font color="red"><strong><?=$Err ?></strong></font><br /><br />
<?	} ?>
	An email will be sent to your email address with information on how to reset your password<br /><br />
		<table cellpadding="2" cellspacing="1" border="0" align="center">
			<tr valign="top">
				<td align="right">Email&nbsp;</td>
				<td align="left"><input type="text" name="email" id="email" class="inputtext" /></td>
			</tr>
			<tr>
				<td colspan="2" align="right"><input type="submit" name="reset" value="Reset!" class="submit" /></td>
			</tr>
		</table>
<? } else { ?>
	An email has been sent to you, please follow the directions in that email to reset your password.
<? } ?>
	</div>
</form>
<?
show_footer();
?>
