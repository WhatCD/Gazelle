<?php
class Rules {

	/**
	 * Displays the site's "Golden Rules".
	 *
	 */
	public static function display_golden_rules() {
		?>
		<ul>
			<li>All staff decisions must be respected. If you take issue with a decision, you must do so privately with the staff member who issued the decision or with an administrator of the site. Complaining about staff decisions in public or otherwise disrespecting staff members will not be taken lightly.</li>
			<li>Access to this web site is a privilege, not a right, and it can be taken away from you for any reason.</li>
			<li>One account per person per lifetime. Anyone creating additional accounts will be banned. Additionally, unless your account is immune to <a href="wiki.php?action=article&amp;id=8">inactivity pruning</a>, accounts are automatically disabled if one page load is not made at least once every four months.</li>
			<li>Avatars must not exceed 256 kB or be vertically longer than 400 pixels. Avatars must be safe for work, be entirely unoffensive, and cannot contain any nudity or religious imagery. Use common sense.</li>
			<li>Do not post our .torrent files on other sites. Every .torrent file has your personal passkey embedded in it. The tracker will automatically disable your account if you share your torrent files with others. You will not get your account back. This doesn't prohibit you from sharing the content on other sites, but does prohibit you from sharing the .torrent file.</li>
			<li>Any torrent you are seeding to this tracker must only have our tracker's URL in it. Adding another tracker's URL will cause incorrect data to be sent to our tracker, and will lead to your getting disabled for cheating. Similarly, your client must have DHT and PEX (peer exchange) disabled for all <?=SITE_NAME?> torrents.</li>
			<li>This is a torrent site which promotes sharing amongst the community. If you are not willing to give back to the community what you take from it, this site is not for you. In other words, we expect you to have an acceptable share ratio. If you download a torrent, please, seed the copy you have until there are sufficient people seeding the torrent data before you stop.</li>
			<li>Do not browse the site using proxies or Tor. The site will automatically alert us. This includes VPNs with dynamic IP addresses.</li>
			<li>Asking for invites to any site is not allowed anywhere on <?=SITE_NAME?> or our IRC network. Invites may be offered in the Invites forum, and nowhere else.</li>
			<li>Trading and selling invites is strictly prohibited, as is offering them in public - this includes on any forum which is not a class-restricted section on an invitation-only torrent site. Responding to public requests for invites may also jeopardize your account and those whom you invite from a public request.</li>
			<li>Trading, selling, sharing, or giving away your account is prohibited. If you no longer want your account, send a staff PM requesting that it be disabled.</li>
			<li>You're completely responsible for the people you invite. If your invitees are caught cheating or trading/selling invites, not only will they be banned, so will you. Be careful who you invite. Invites are a precious commodity.</li>
			<li>Be careful when sharing an IP address or a computer with a friend if they have (or have had) an account. From then on your accounts will be inherently linked and if one of you violates the rules, both accounts will be disabled along with any other accounts linked by IP address. This rule applies to logging into the site.</li>
			<li>Attempting to find or exploit a bug in the site code is the worst possible offense you can commit. We have automatic systems in place for monitoring these activities, and committing them will result in the banning of you, your inviter, and your inviter's entire invite tree.</li>
			<li>We're a community. Working together is what makes this place what it is. There are well over a thousand new torrents uploaded every day and sadly the staff aren't psychic. If you come across something that violates a rule, report it and help us better organize the site for you.</li>
			<li>We respect the wishes of other sites here, as we wish for them to do the same. Please refrain from posting links to or full names for sites that do not want to be mentioned.</li>
		</ul>
<?
	}

	/**
	 * Displays the site's rules for tags.
	 *
	 * @param boolean $OnUpload - whether it's being displayed on a torrent upload form
	 */
	public static function display_site_tag_rules($OnUpload = false) {
		?>
		<ul>
			<li>Tags should be comma-separated, and you should use a period (".") to separate words inside a tag&#8202;&mdash;&#8202;e.g. "<strong class="important_text_alt">hip.hop</strong>".</li>

			<li>There is a list of official tags <?=($OnUpload ? 'to the left of the text box' : 'on <a href="upload.php">the torrent upload page</a>')?>. Please use these tags instead of "unofficial" tags (e.g. use the official "<strong class="important_text_alt">drum.and.bass</strong>" tag, instead of an unofficial "<strong class="important_text">dnb</strong>" tag). <strong>Please note that the "<strong class="important_text_alt">2000s</strong>" tag refers to music produced between 2000 and 2009.</strong></li>

			<li>Avoid abbreviations if at all possible. So instead of tagging an album as "<strong class="important_text">alt</strong>", tag it as "<strong class="important_text_alt">alternative</strong>". Make sure that you use correct spelling.</li>

			<li>Avoid using multiple synonymous tags. Using both "<strong class="important_text">prog.rock</strong>" and "<strong class="important_text_alt">progressive.rock</strong>" is redundant and annoying&#8202;&mdash;&#8202;just use the official "<strong class="important_text_alt">progressive.rock</strong>" tag.</li>

			<li>Do not add "useless" tags, such as "<strong class="important_text">seen.live</strong>", "<strong class="important_text">awesome</strong>", "<strong class="important_text">rap</strong>" (is encompassed by "<strong class="important_text_alt">hip.hop</strong>"), etc. If an album is live, you can tag it as "<strong class="important_text_alt">live</strong>".</li>

			<li>Only tag information on the album itself&#8202;&mdash;&#8202;<strong>not the individual release</strong>. Tags such as "<strong class="important_text">v0</strong>", "<strong class="important_text">eac</strong>", "<strong class="important_text">vinyl</strong>", "<strong class="important_text">from.oink</strong>", etc. are strictly forbidden. Remember that these tags will be used for other versions of the same album.</li>

			<li><strong>You should be able to build up a list of tags using only the official tags <?=($OnUpload ? 'to the left of the text box' : 'on <a href="upload.php">the torrent upload page</a>')?>. If you are in any doubt about whether or not a tag is acceptable, do not add it.</strong></li>
		</ul>
<?
	}

	/**
	 * Displays the site's rules for conversing on its IRC network
	 *
	 */
	public static function display_irc_chat_rules() {
		?>
		<ul>
			<li>Staff have the final decision. If a staff member says stop and you continue, expect at least to be banned from the IRC network.</li>
			<li>Be respectful to IRC Operators and Administrators. These people are site staff who volunteer their time for little compensation. They are there for the benefit of all and to aid in conflict resolution; do not waste their time.</li>
			<li>Do not link shock sites or anything NSFW (not safe for work) without a warning. If in doubt, ask a staff member in <?=(BOT_HELP_CHAN)?> about it.</li>
			<li>Excessive swearing will get you kicked; keep swearing to a minimum.</li>
			<li>Do not leave Caps Lock enabled all the time. It gets annoying, and you will likely get yourself kicked.</li>
			<li>No arguing. You can't win an argument over the Internet, so you are just wasting your time trying.</li>
			<li>No prejudice, especially related to race, religion, politics, ethnic background, etc. It is highly suggested to avoid this entirely.</li>
			<li>Flooding is irritating and will warrant you a kick. This includes, but is not limited to, automatic "now playing" scripts, pasting large amounts of text, and multiple consecutive lines with no relevance to the conversation at hand.</li>
			<li>Impersonation of other members&#8202;&mdash;&#8202;particularly staff members&#8202;&mdash;&#8202;will not go unpunished. If you are uncertain of a user's identity, check their vhost.</li>
			<li>Spamming is strictly forbidden. This includes, but is not limited to, personal sites, online auctions, and torrent uploads.</li>
			<li>Obsessive annoyance&#8202;&mdash;&#8202;both to other users and staff&#8202;&mdash;&#8202;will not be tolerated.</li>
			<li>Do not PM, DCC, or Query anyone you don't know or have never talked to without asking first; this applies specifically to staff.</li>
			<li>No language other than English is permitted in the official IRC channels. If we cannot understand it, we cannot moderate it.</li>
			<li>The offering, selling, trading, and giving away of invites to this or any other site on our IRC network is <strong>strictly forbidden</strong>.</li>
			<li><strong>Read the channel topic before asking questions.</strong></li>
		</ul>
<?
	}
}
