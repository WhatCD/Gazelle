<?
enforce_login();
show_header('IRC');	

$DB->query("SELECT IRCKey FROM users_main WHERE ID = $LoggedUser[ID]");
list($IRCKey) = $DB->next_record();

if(empty($IRCKey)) {
?>
<div class="thin">
	<h3 id="irc">IRC Rules - Please read these carefully!</h3>
	<div class="box pad" style="padding:10px 10px 10px 20px;">
		<p>
			<strong>Please set your IRC Key on your <a href="user.php?action=edit&amp;userid=<?=$LoggedUser['ID']?>">profile</a> first! For more information on IRC, please read the <a href="wiki.php?action=article&amp;name=IRC+-+How+to+join">wiki article</a>.</strong>
		</p>
	</div>
</div>
<?
} else {
	if(!isset($_POST["accept"])) {
?>
<div class="thin">
	<h3 id="irc">IRC Rules - Please read these carefully!</h3>
	<div class="box pad" style="padding:10px 10px 10px 20px;">
		<ul>
			<li>
				Staff have the final decision, if they say stop and you continue, expect at least to be banned from the IRC server. 
			</li>
			<li>
				Be respectful to IRC Operators and Administrators. These people are site staff who volunteer their time for little compensation. They are there for the benefit of all and to aid in conflict resolution, do not waste their time.
			</li>
			<li>
				Do not link shock sites or anything NSFW (not safe for work) without a warning. If in doubt, ask a staff member in <?=BOT_HELP_CHAN?> about it.
			</li>
			<li>
				Excessive swearing will get you kicked, keep swearing to a minimum.
			</li>
			<li>
				Do not leave your Caps Lock on all the time. It gets annoying, and you will likely get yourself kicked.
			</li>
			<li>
				No arguing. You can't win an argument over the internet, so you're just wasting your time trying.
			</li>
			<li>
				No prejudice, especially related to race, religion, politics, ethnic background, etc. It is highly suggested to avoid this entirely.
			</li>
			<li>
				Flooding is irritating and will merit you a kick. This includes but is not limited to: automatic now playing scripts, pasting large amounts of text, and multiple consecutive lines with no relevance to the conversation at hand.
			</li>
			<li>
				Impersonation of other members (particularly staff members) will not go unpunished. If you are uncertain of a users identity, check their vhost.
			</li>
			<li>
				Spamming is strictly forbidden. This includes but is not limited to: personal sites, online auctions, and torrent uploads.
			</li>
			<li>
				Obsessive annoyance both to other users and staff will not be tolerated.
			</li>
			<li>
				Don't PM, DCC, or Query anyone you don't know or have never talked to without asking, this applies specifically to staff.
			</li>
			<li>
				No language other than English is permitted in the official IRC channels. If we can't understand it, we can't moderate it. 
			</li>
			<li>
				The offering, selling, trading and giving away of invites to this or any other site on our IRC network is <strong>strictly forbidden</strong>.
			</li>
			<li>
				<strong>Read the topic before asking questions.</strong>
			</li>
		</ul>
	</div>
</div>
<form method="post" action="chat.php">
	<center>
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
		<input type="submit" name="accept" value="I agree to these rules" />
	</center>
</form>
<?
	} else {
		$nick = $LoggedUser["Username"];
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
	<h3 id="general">IRC</h3>
	<div class="box pad" style="padding:10px 0px 10px 0px;">
		<div style="padding:0px 10px 10px 20px;">
			<p>If you have an IRC client, visit <a href="wiki.php?action=article&amp;name=IRC+-+How+to+join">this wiki entry</a> for more information how to connect. (IRC Applet users are automatically identified with Drone.)</p>
		</div>
		<center>
			<applet codebase="static/irc/" code="IRCApplet.class" archive="irc.jar,sbox.jar" width=800 height=600>
				<param name="nick" value="<?=$nick?>">
				<param name="alternatenick" value="WhatGuest????">
				<param name="name" value="Java IRC User">
				<param name="host" value="<?=BOT_SERVER?>">
				<param name="multiserver" value="true">
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
}

show_footer();
?>
