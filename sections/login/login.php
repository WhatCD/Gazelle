<? show_header('Login'); ?>
	<span id="no-cookies" class="hidden warning">You appear to have cookies disabled.<br /><br /></span>
	<noscript><span class="warning">You appear to have javascript disabled.</span><br /><br /></noscript> 
<?
if(strtotime($BannedUntil)<time() && !$BanID) {
?>
	<form id="loginform" method="post" action="login.php">
<?

	if(!empty($BannedUntil) && $BannedUntil != '0000-00-00 00:00:00') {
		$DB->query("UPDATE login_attempts SET BannedUntil='0000-00-00 00:00:00', Attempts='0' WHERE ID='".db_string($AttemptID)."'");
		$Attempts = 0;
	}
	if(isset($Err)) {
?>
	<span class="warning"><?=$Err?><br /><br /></span>
<? } ?>
<? if ($Attempts > 0) { ?>
	You have <span class="info"><?=(6-$Attempts)?></span> attempts remaining.<br /><br />
	<strong>WARNING:</strong> You will be banned for 6 hours after your login attempts run out!<br /><br />
<? } ?>
	<table>
		<tr>
			<td>Username&nbsp;</td>
			<td colspan="2"><input type="text" name="username" id="username" class="inputtext" required="required" maxlength="20" pattern="[A-Za-z0-9_?]{1,20}" autofocus="autofocus" /></td>
		</tr>
		<tr>
			<td>Password&nbsp;</td>
			<td colspan="2"><input type="password" name="password" id="password" class="inputtext" required="required" maxlength="40" pattern=".{6,40}" /></td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" id="keeplogged" name="keeplogged" value="1"<? if(isset($_REQUEST['keeplogged']) && $_REQUEST['keeplogged']) { ?> checked="checked"<? } ?> />
				<label for="keeplogged">Remember me</label>
			</td>
			<td><input type="submit" name="login" value="Login" class="submit" /></td>
		</tr>
	</table>
	</form>
<?
} else {
	if($BanID) {
?>
	<span class="warning">Your IP is banned indefinitely.</span>
<? } else { ?>
	<span class="warning">You are banned from logging in for another <?=time_diff($BannedUntil)?>.</span>
<?
	}
}

if ($Attempts > 0) {
?>
	<br /><br />
	Lost your password? <a href="login.php?act=recover">Recover it here!</a>
<? } ?>
<script type="text/javascript">
cookie.set('cookie_test',1,1);
if (cookie.get('cookie_test') != null) {
	cookie.del('cookie_test');
} else {
	$('#no-cookies').show();
}
</script>
<? show_footer(); ?>
