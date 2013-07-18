<?
enforce_login();
View::show_header('IRC');

$DB->query("
	SELECT IRCKey
	FROM users_main
	WHERE ID = $LoggedUser[ID]");
list($IRCKey) = $DB->next_record();

if (empty($IRCKey)) {
?>
<div class="thin">
	<div class="header">
		<h3 id="irc">IRC Rules - Please read these carefully!</h3>
	</div>
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
		<p>
			<strong>Please set your IRC Key on your <a href="user.php?action=edit&amp;userid=<?=$LoggedUser['ID']?>">profile</a> first! For more information on IRC, please read the <a href="wiki.php?action=article&amp;name=IRC+-+How+to+join">wiki article</a>.</strong>
		</p>
	</div>
</div>
<?
} else {
	if (!isset($_POST['accept'])) {
?>
<div class="thin">
	<div class="header">
		<h3 id="irc">IRC Rules - Please read these carefully!</h3>
	</div>
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
<?		Rules::display_irc_chat_rules() ?>
		<form class="confirm_form center" name="chat" method="post" action="chat.php">
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="submit" name="accept" value="I agree to these rules" />
		</form>
	</div>
</div>
<?
	} else {
		$nick = $LoggedUser['Username'];
		$nick = preg_replace('/[^a-zA-Z0-9\[\]\\`\^\{\}\|_]/', '', $nick);
		if (strlen($nick) == 0) {
			$nick = SITE_NAME.'Guest????';
		} else {
			if (is_numeric(substr($nick, 0, 1))) {
				$nick = '_' . $nick;
			}
		}
?>
<div class="thin">
	<div class="header">
		<h3 id="general">IRC</h3>
	</div>
	<div class="box pad" style="padding: 10px 0px 10px 0px;">
		<div style="padding: 0px 10px 10px 20px;">
			<p>If you have an IRC client, refer to <a href="wiki.php?action=article&amp;name=IRC+-+How+to+join">this wiki article</a> for information on how to connect. (IRC applet users are automatically identified with Drone.)</p>
		</div>
		<applet codebase="static/irc/" code="IRCApplet.class" archive="irc.jar,sbox.jar" width="800" height="600" align="center">
			<param name="nick" value="<?=$nick?>" />
			<param name="alternatenick" value="WhatGuest????" />
			<param name="name" value="Java IRC User" />
			<param name="host" value="<?=BOT_SERVER?>" />
			<param name="multiserver" value="true" />
			<param name="autorejoin" value="false" />

			<param name="gui" value="sbox" />
			<param name="pixx:highlight" value="true" />
			<param name="pixx:highlightnick" value="true" />
			<param name="pixx:prefixops" value="true" />
			<param name="sbox:scrollspeed" value="5" />
		</applet>
	</div>
</div>
<?
	}
}

View::show_footer();
?>
