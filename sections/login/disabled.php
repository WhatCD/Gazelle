<?
show_header('Disabled');
if(empty($_POST['submit']) || empty($_POST['username'])) {
?>
<p class="warning">
Your account has been disabled.<br />
This is either due to inactivity or rule violation.<br />
To discuss this come to our IRC at: <?=BOT_SERVER?><br />
And join <?=BOT_DISABLED_CHAN?><br /><br />
Be honest - at this point, lying will get you nowhere.<br /><br/><br/>
</p>

<strong>Before joining the disabled channel please read our <br/> <span style="color:gold;">Golden Rules</span> which can be found <a style="color:#1464F4;" href="#" onclick="toggle_visibility('golden_rules')">here.</a></strong> <br/><br/>

<script type="text/javascript">
    function toggle_visibility(id) {
       var e = document.getElementById(id);
       if(e.style.display == 'block')
          e.style.display = 'none';
       else
          e.style.display = 'block';
    }
</script>

<div id="golden_rules" style="width:35%;font-weight:bold;display:none;" >
<ul>
<li>All staff decisions must be respected. If you take issue with a decision, you must do so privately with the staff member who issued the decision or with an administrator of the site. Complaining about staff decisions in public or otherwise disrespecting staff members will not be taken lightly.</li><br/>
<li>Access to this website is a privilege, not a right, and it can be taken away from you for any reason.</li><br/>
<li>One account per person per lifetime. Anyone creating additional accounts will be banned.</li><br/>
<li>Avatars must not exceed 256 kB or be vertically longer than 400 pixels. Avatars must be safe for work, be entirely unoffensive, and cannot contain any nudity or religious imagery. Use common sense.</li><br/>
<li>Do not post our .torrent files on other sites. Every .torrent file has your personal passkey embedded in it. The tracker will automatically disable your account if you share your torrent files with others. You will not get your account back. This doesn't prohibit you from sharing the content on other sites, but does prohibit you from sharing the .torrent file.</li><br/>
<li>Any torrent you are seeding to this tracker must only have our tracker's URL in it. Adding another tracker's URL will cause incorrect data to be sent to our tracker, and will lead to your getting disabled for cheating. Similarly, your client must have DHT and PEX disabled for all What.CD torrents.</li><br/>
<li>This is a torrent site which promotes sharing amongst the community. If you are not willing to give back to the community what you take from it, this site is not for you. In other words, we expect you to have an acceptable share </li><br/>
<li>Do not browse the site using proxies or TOR. The site will automatically alert us. This includes VPNs with dynamic IP addresses.</li><br/>
<li>Asking for invites to any site is not allowed anywhere on What.CD or our IRC network. Invites may be offered in the Invites forum, and nowhere else.</li><br/>
<li>Trading, selling, sharing, or giving away your account is prohibited. PM a mod to disable your account if you no longer want it.</li><br/>
<li>You're completely responsible for the people you invite. If your invitees are caught cheating or trading/selling invites, not only will they be banned, so will you. Be careful who you invite. Invites are a precious commodity.</li><br/>
<li>Be careful when sharing an IP or a computer with a friend if they have (or have had) an account. From then on your accounts will be inherently linked and if one of you violates the rules, both accounts will be disabled along with any other accounts linked by IP. This rule applies to logging into the site.</li><br/>
<li>Attempting to find or exploit a bug in the site code is the worst possible offense you can commit. We have automatic systems in place for monitoring these activities, and committing them will result in the banning of you, your inviter, and your inviter's entire invite tree.</li><br/>
<li>We're a community. Working together is what makes this place what it is. There are well over a thousand new torrents uploaded every day and sadly the staff aren't psychic. If you come across something that violates a rule, report it and help us better organize the site for you.</li><br/>
<li>We respect the wishes of other sites here, as we wish for them to do the same. Please refrain from posting links to or full names for sites that do not want to be mentioned.</li><br/>

</ul>

</div>

</p>
<p class="strong">
If you do not have access to an IRC client you can use the WebIRC interface provided below.<br />
Please use your What.CD? username.
</p>
<br />
<form class="confirm_form" name="chat" action="" method="post">
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
	<div class="header">
		<h3 id="general">Disabled IRC</h3>
	</div>
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
