<?php
class Rules {

	/**
	 * Displays the site's "Golden Rules".
	 *
	 */
	public static function display_golden_rules() {
		?>
		<ol>
			<li>All staff decisions must be respected. If you take issue with a decision, you must do so privately with the staff member who issued the decision or with an administrator of the site. Complaining about staff decisions in public or otherwise disrespecting staff members will not be taken lightly.</li>
			<li>Access to this web site is a privilege, not a right, and it can be taken away from you for any reason.</li>
			<li>One account per person per lifetime. Anyone creating additional accounts will be banned. Additionally, unless your account is immune to <a href="wiki.php?action=article&amp;id=8">inactivity pruning</a>, accounts are automatically disabled if one page load is not made at least once every four months.</li>
			<li>Avatars must not exceed <span class="tooltip" title="262,144 bytes">256 kB</span> or be vertically longer than 400 pixels. Avatars must be safe for work, be entirely unoffensive, and cannot contain any nudity or religious imagery. Use common sense.</li>
			<li>Do not post our torrent files on other sites. Your personal passkey is embedded in every torrent file. The tracker will automatically disable your account if you share your torrent files with others. You will not get your account back. This does not prohibit you from sharing the content of the torrents on other sites, but this does prohibit you from sharing the torrent file itself (i.e. the file with a ".torrent" file extension).</li>
			<li>Any torrent you are seeding to this tracker must have <em>only</em> <?=SITE_NAME?>'s tracker URL in it. Adding another BitTorrent tracker's URL will cause incorrect data to be sent to our tracker, and you will be disabled for cheating. Similarly, your client must have DHT and PEX (peer exchange) disabled for all <?=SITE_NAME?> torrents.</li>
			<li>This is a BitTorrent site which promotes sharing amongst the community. If you are not willing to give back to the community what you take from it, this site is not for you. In other words, we expect you to have an acceptable share ratio. If you download a torrent, please seed the copy you have until there are sufficient people seeding the torrent before you stop.</li>
			<li>Do not browse the site using proxies or Tor. The site will automatically alert us. This includes VPNs with dynamic IP addresses.</li>
			<li>Asking for invites to any site is not allowed anywhere on <?=SITE_NAME?> or our IRC network. Invites may be offered in the Invites forum and nowhere else.</li>
			<li>Trading, selling, and publicly offering <?=SITE_NAME?> invites is strictly prohibited; this includes on any forum which is not a class-restricted section on a private, invitation-only, BitTorrent tracker. Responding to public requests for invites may also jeopardize your account and the accounts of those you invite from a public request.</li>
			<li>Trading, selling, sharing, or giving away your account is strictly prohibited. If you no longer want your account, send a <a href="staffpm.php">Staff PM</a> requesting that it be disabled.</li>
			<li>You are completely responsible for the people you invite. If your invitees are caught cheating or trading/selling invites, not only will they be banned, so will you. Be careful who you invite. Invites are a precious commodity.</li>
			<li>Be careful when sharing an IP address or a computer with a friend if they have (or have had) an account. From then on, your accounts will be permanently linked, and if one of you violates the rules, both accounts will be disabled along with any other accounts linked by IP address. This rule applies to logging into the site.</li>
			<li>Attempting to find or exploit a bug in the site code is the worst possible offense you can commit. We have automatic systems in place for monitoring these activities, and committing them will result in the banning of you, your inviter, and your inviter's entire invite tree.</li>
			<li>We're a community. Working together is what makes this place what it is. There are well over a thousand new torrents uploaded every day and, sadly, the staff aren't psychic. If you come across something that violates a rule, report it, and help us better organize the site for you.</li>
			<li>We respect the wishes of other BitTorrent trackers here, as we wish for them to do the same. Please refrain from posting full names or links to sites that do not want to be mentioned.</li>
		</ol>
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
	 * Displays the site's rules for the forum
	 *
	 */
	public static function display_forum_rules() {
		?>
		<ol>
			<li>
				Many forums (Tutorials, The Library, etc.) have their own set of rules. Make sure you read and take note of these rules before you attempt to post in one of these forums.
			</li>
			<li>
				Don't use all capital letters, excessive !!! (exclamation marks) or ??? (question marks). It seems like you're shouting!
			</li>
			<li>
				No lame referral schemes. This includes freeipods.com, freepsps.com, or any other similar scheme in which the poster gets personal gain from users clicking a link.
			</li>
			<li>
				No asking for money for any reason whatsoever. We don't know or care about your friend who lost everything, or dying relative who wants to enjoy their last few moments alive by being given lots of money.
			</li>
			<li>
				Do not inappropriately advertise your uploads. In special cases, it is acceptable to mention new uploads in an approved thread (e.g. <a href="forums.php?action=viewthread&amp;threadid=133982">New Users — We'll Snatch Your First 100% FLAC</a>), but be sure to carefully read the thread's rules before posting. It is also acceptable to discuss releases you have uploaded when conversing about the music itself. Blatant attempts to advertise your uploads outside of the appropriate forums or threads may result in a warning or the loss of forum privileges.
			</li>
			<li>
				No posting music requests in forums. There's a request link at the top of the page; please use that instead.
			</li>
			<li>
				No flaming; be pleasant and polite. Don't use offensive language, and don't be confrontational for the sake of confrontation.
			</li>
			<li>
				Don't point out or attack other members' share ratios. A higher ratio does not make you better than someone else.
			</li>
			<li>
				Try not to ask stupid questions. A stupid question is one that you could have found the answer to yourself with a little research, or one that you're asking in the wrong place. If you do the basic research suggested (i.e., read the rules/wiki) or search the forums and don't find the answer to your question, then go ahead and ask. Staff and First Line Support (FLS) are not here to hand-feed you the answers you could have found on your own with a little bit of effort.
			</li>
			<li>
				Be sure you read all the sticky threads in a forum before you post.
			</li>
			<li>
				Use descriptive and specific subject lines. This helps others decide whether your particular words of wisdom relate to a topic they care about.
			</li>
			<li>
				Try not to post comments that don't add anything to the discussion. When you're just cruising through a thread in a leisurely manner, it's not too annoying to read through a lot of "hear, hear"'s and "I agree"'s. But if you're actually trying to find information, it's a pain in the neck. So save those one-word responses for threads that have degenerated to the point where none but true aficionados are following them any more.
				<p>
					Or short: NO spamming
				</p>
			</li>
			<li>
				Refrain from quoting excessively. When quoting someone, use only the portion of the quote that is absolutely necessary. This includes quoting pictures!
			</li>
			<li>
				No posting of requests for serials or cracks. No links to warez or crack sites in the forums.
			</li>
			<li>
				No political or religious discussions. These types of discussions lead to arguments and flaming users, something that will not be tolerated. The only exception to this rule is The Library forum, which exists solely for the purpose of intellectual discussion and civilized debate.
			</li>
			<li>
				Don't waste other people's bandwidth by posting images of a large file size.
			</li>
			<li>
				Be patient with newcomers. Once you have become an expert, it is easy to forget that you started out as a newbie too.
			</li>
			<li>
				No requesting invites to any sites anywhere on the site or IRC. Invites may be <strong>offered</strong> in the invite forum, and nowhere else.
			</li>
			<li>
				No language other than English is permitted in the forums. If we can't understand it, we can't moderate it.
			</li>
			<li>
				Be cautious when posting mature content on the forums. All mature imagery must abide by <a href="wiki.php?action=article&amp;id=1063">the rules found here</a>. Gratuitously sexual or violent content which falls outside of the allowable categories will result in a warning or worse.
			</li>
			<li>
				Mature content in posts must be properly tagged. The correct format is as follows: <strong>[mature=description] ...content... [/mature]</strong>, where "description" is a mandatory description of the post contents. Misleading or inadequate descriptions will be penalized.
			</li>
			<li>
				Threads created for the exclusive purpose of posting mature imagery will be trashed. Mature content (including graphic album art) should be contextually relevant to the thread and/or forum you're posting in. Mature content is only allowed in: The Lounge, The Lounge +1, The Library, Music, Power Users, Elite, Torrent Masters, VIPs, Comics, Contests &amp; Designs, The Laboratory. If you are in doubt about a post's appropriateness, send a <a href="staffpm.php">Staff PM to the Forum Moderators</a> and wait for a reply before proceeding.
			</li>
		</ol>
<?
	}

	/**
	 * Displays the site's rules for conversing on its IRC network
	 *
	 */
	public static function display_irc_chat_rules() {
		?>
		<ol>
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
		</ol>
<?
	}
}
