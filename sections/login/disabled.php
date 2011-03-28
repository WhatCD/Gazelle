<?
show_header('Disabled');
if(empty($_POST['submit']) || empty($_POST['username'])) {
?>
<p class="warning">
Your account has been disabled.<br />
This is either due to inactivity or rule violation.<br />
To discuss this come to our IRC at: <?=BOT_SERVER?><br />
And join <?=BOT_DISABLED_CHAN?><br /><br />
Be honest - at this point, lying will get you nowhere.<br /><br />
</p>
<p class="strong">
If you do not have access to an IRC client you can use the WebIRC interface provided below.<br />
Please use your What.CD? username.
</p>
<br />
<form action="" method="post">
	<input type="text" name="username" width="20" />
	<input type="submit" name="submit" value="Join WebIRC" />
</form>

<?
} else {
	$nick = $_POST['username'];
	$nick = preg_replace('/[^a-zA-Z0-9\[\]\\`\^\{\}\|_]/', '', $nick);
	if(strlen($nick) == 0) {
		$nick = "WhatGuest????";
	} else {
		if(is_numeric(substr($nick, 0, 1))) {
			$nick = "_" . $nick;
		}
	}
?>
<div class="thin">
	<h3 id="general">Disabled IRC</h3>
	<div class="box pad" style="padding:10px 0px 10px 0px;">
		<div style="padding:0px 10px 10px 20px;">
			<p>Please read the topic carefully.</p>
		</div>
		<center>
			<applet codebase="static/irc/" code="IRCApplet.class" archive="irc.jar,sbox.jar" width=800 height=600>
				<param name="nick" value="<?=$nick?>">
				<param name="alternatenick" value="WhatGuest????">
				<param name="name" value="Java IRC User">
				<param name="host" value="<?=BOT_SERVER?>">
				<param name="multiserver" value="false">
				<param name="autorejoin" value="false">

				<param name="gui" value="sbox">
				<param name="pixx:highlight" value="true">
				<param name="pixx:highlightnick" value="true">
				<param name="pixx:prefixops" value="true">
				<param name="sbox:scrollspeed" value="5">
			</applet>
		</center>
	</div>
</div>
<?
}
show_footer();
?>
