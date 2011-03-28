<?
show_header('Register','validate');
echo $Val->GenerateJS('regform');
?>
<form name="regform" id="regform" method="post" action="" onsubmit="return formVal();">
<div style="width:500px;">
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
<?

if(empty($Sent)) {
	if(!empty($_REQUEST['invite'])) {
		echo '<input type="hidden" name="invite" value="'.display_str($_REQUEST['invite']).'" />'."\n";
	}
	if(!empty($Err)) {
?>
	<font color="red"><strong><?=$Err?></strong></font><br /><br />
<?	} ?>
	<table cellpadding="2" cellspacing="1" border="0" align="center">
		<tr valign="top">
			<td align="right">Username&nbsp;</td>
			<td align="left">
				<input type="text" name="username" id="username" class="inputtext" value="<?=(!empty($_REQUEST['username']) ? display_str($_REQUEST['username']) : '')?>" />
				<p>It is recommended that you do NOT use your real name for personal security! We will not be changing it for you.</p>
			</td>
		</tr>
		<tr valign="top">
			<td align="right">Email&nbsp;</td>
			<td align="left"><input type="text" name="email" id="email" class="inputtext" value="<?=(!empty($_REQUEST['email']) ? display_str($_REQUEST['email']) : (!empty($InviteEmail) ? display_str($InviteEmail) : ''))?>" /></td>
		</tr>
		<tr valign="top">
			<td align="right">Password&nbsp;</td>
			<td align="left"><input type="password" name="password" id="password" class="inputtext" /></td>
		</tr>
		<tr valign="top">
			<td align="right">Verify Password&nbsp;</td>
			<td align="left"><input type="password" name="confirm_password" id="confirm_password" class="inputtext" /></td>
		</tr>
		<tr valign="top">
			<td></td>
			<td align="left"><input type="checkbox" name="readrules" id="readrules" value="1"<? if (!empty($_REQUEST['readrules'])) { ?> checked="checked"<? } ?> /> <label for="readrules">I will read the rules.</label></td>
		</tr>
		<tr valign="top">
			<td></td>
			<td align="left"><input type="checkbox" name="readwiki" id="readwiki" value="1"<? if (!empty($_REQUEST['readwiki'])) { ?> checked="checked"<? } ?> /> <label for="readwiki">I will read the wiki.</label></td>
		</tr>
		<tr valign="top">
			<td></td>
			<td align="left"><input type="checkbox" name="agereq" id="agereq" value="1"<? if (!empty($_REQUEST['agereq'])) { ?> checked="checked"<? } ?> /> <label for="agereq">I am 13 years of age or older.</label></td>
		</tr>
		<tr>
			<td colspan="2" height="10"></td>
		</tr>
		<tr>
			<td colspan="2" align="right"><input type="submit" name="submit" value="Submit" class="submit" /></td>
		</tr>
	</table>
<? } else { ?>
	An email has been sent to the address that you provided. After you confirm your email address you will be able to log into your account.

<? 		if($NewInstall) { echo "Since this is a new installation, you can log in directly without having to confirm your account."; }
} ?>
</div>
</form>
<?
show_footer();
