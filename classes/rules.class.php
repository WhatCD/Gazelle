<?php
class Rules {

	/**
	 * Displays the site's "Golden Rules".
	 *
	 */
	public static function display_golden_rules() {
		$site_name = SITE_NAME;
		$disabled_channel = BOT_DISABLED_CHAN;
		$staffpm = '<a href="staffpm.php">Staff PM</a>';
		$irc = '<a href="chat.php">IRC</a>';
		$vpns_article = '<a href="wiki.php?action=article&name=vpns">Proxy/VPN Tips</a>';
		$ips_article = '<a href="wiki.php?action=article&name=ips">Multiple IPs</a>';
		$autofl_article = '<a href="wiki.php?action=article&name=autofl">Freeleech Autosnatching Policy</a>';
		$bugs_article = '<a href="wiki.php?action=article&name=bugs">Responsible Disclosure Policy</a>';
		$exploit_article = '<a href="wiki.php?action=article&name=exploit">Exploit Policy</a>';
		$golden_rules = array(
			[ 'n' => "1.1",
			  'short' => "Do not create more than one account.",
			  'long' => "Users are allowed one account per lifetime. If your account is disabled, contact staff in ${disabled_channel} on ${irc}." ],
			[ 'n' => "1.2",
			  'short' => "Do not trade, sell, give away, or offer accounts.",
			  'long' => "If you no longer wish to use your account, send a ${staffpm} and request that your account be disabled." ],
			[ 'n' => "1.3",
			  'short' => "Do not share accounts.",
			  'long' => "Accounts are for personal use only. Granting access to your account in any way (e.g., shared login details, external programs) is prohibited. <a href=\"wiki.php?action=article&name=invite\">Invite</a> friends or direct them to the <a href=\"http://www.whatinterviewprep.com/\">IRC Interview</a>." ],
			[ 'n' => "2.1",
			  'short' => "Do not invite bad users.",
			  'long' => "You are responsible for your invitees. You will not be punished if your invitees fail to maintain required share ratios, but invitees who break golden rules will place your invite privileges and account at risk." ],
			[ 'n' => "2.2",
			  'short' => "Do not trade, sell, publicly give away, or publicly offer invites.",
			  'long' => "Only invite people you know and trust. Do not offer invites via other trackers, forums, social media, or other public locations. Responding to public invite requests is prohibited. Exception: Staff-designated recruiters may offer invites in approved locations." ],
			[ 'n' => "2.3",
			  'short' => "Do not request invites or accounts.",
			  'long' => "Requesting invites to&mdash;or accounts on&mdash;${site_name} or other trackers is prohibited. Invites may be <i>offered</i>, but not requested, in the site's Invites forum (restricted to the <a href=\"wiki.php?action=article&name=classes\">Power User class</a> and above). You may request invites by messaging users only when they have offered them in the Invites Forum. Unsolicited invite requests, even by private message, are prohibited." ],
			[ 'n' => "3.1",
			  'short' => "Do not engage in ratio manipulation.",
			  'long' => "Transferring buffer&mdash;or increasing your buffer&mdash;through unintended uses of the BitTorrent protocol or site features (e.g., <a href=\"rules.php?p=requests\">request abuse</a>) constitutes ratio manipulation. When in doubt, send a ${staffpm} asking for more information." ],
			[ 'n' => "3.2",
			  'short' => "Do not report incorrect data to the tracker (i.e., cheating).",
			  'long' => "Reporting incorrect data to the tracker constitutes cheating, whether it is accomplished through the use of a modified \"cheat client\" or through manipulation of an approved client." ],
			[ 'n' => "3.3",
			  'short' => "Do not use unapproved clients.",
			  'long' => "Your client must be found on the <a href=\"rules.php?p=clients\">Client Whitelist</a>. You must not use clients that have been modified in any way. Developers interested in testing unstable clients must first receive staff approval." ],
			[ 'n' => "3.4",
			  'short' => "Do not modify ${site_name} .torrent files.",
			  'long' => "Embedding non-${site_name} announce URLs in ${site_name} .torrents is prohibited. Doing so causes false data to be reported and will be interpreted as cheating. This applies to standalone .torrent files and .torrent files that have been loaded into a client." ],
			[ 'n' => "3.5",
			  'short' => "Do not share .torrent files or your passkey.",
			  'long' => "Embedded in each ${site_name} .torrent file is an announce URL containing your personal passkey. Passkeys enable users to report stats to the tracker." ],
			[ 'n' => "4.1",
			  'short' => "Do not blackmail, threaten, or expose fellow users.",
			  'long' => "Exposing or threatening to expose private information about users for any reason is prohibited. Private information includes but is not limited to personally identifying information (e.g., names, records, biographical details, photos). Information that hasn't been openly volunteered by a user should not be discussed or shared without permission. This includes private information collected via investigations into openly volunteered information (e.g., Google search results)." ],
			[ 'n' => "4.2",
			  'short' => "Do not scam or defraud.",
			  'long' => "Scams (e.g., phishing) of any kind are prohibited." ],
			[ 'n' => "4.3",
			  'short' => "Do not disrespect staff decisions.",
			  'long' => "Disagreements must be discussed privately with the deciding moderator. If the moderator has retired or is unavailable, you may send a ${staffpm}. Do not contact multiple moderators hoping to find one amenable to your cause; however, you may contact a site administrator if you require a second opinion. Options for contacting staff include private message, Staff PM, and ${disabled_channel} on ${irc}." ],
			[ 'n' => "4.4",
			  'short' => "Do not impersonate staff.",
			  'long' => "Impersonating staff or official service accounts (e.g., Drone) on-site, off-site, or on IRC is prohibited. Deceptively misrepresenting staff decisions is also prohibited." ],
			[ 'n' => "4.5",
			  'short' => "Do not backseat moderate.",
			  'long' => "\"Backseat moderation\" occurs when users police other users. Confronting, provoking, or chastising users suspected of violating rules&mdash;or users suspected of submitting reports&mdash;is prohibited. Submit a report if you see a rule violation." ],
			[ 'n' => "4.6",
			  'short' => "Do not request special events.",
			  'long' => "Special events (e.g., freeleech, neutral leech, picks) are launched at the discretion of the staff. They do not adhere to a fixed schedule, and may not be requested by users." ],
			[ 'n' => "4.7",
			  'short' => "Do not harvest user-identifying information.",
			  'long' => "Using ${site_name}'s services to harvest user-identifying information of any kind (e.g., IP addresses, personal links) through the use of scripts, exploits, or other techniques is prohibited." ],
			[ 'n' => "4.8",
			  'short' => "Do not use ${site_name}'s services (including the tracker, website, and IRC network) for commercial gain.",
			  'long' => "Commercializing services provided by or code maintained by ${site_name} (e.g., Gazelle, Ocelot) is prohibited. Commercializing content provided by ${site_name} users via the aforementioned services (e.g., user torrent data) is prohibited. Referral schemes, financial solicitations, and money offers are also prohibited." ],
			[ 'n' => "5.1",
			  'short' => "Do not browse ${site_name} using proxies (including any VPN) with dynamic or shared IP addresses.",
			  'long' => "You may browse the site through a private server/proxy only if it has a static IP address unique to you, or through your private or shared seedbox. Note that this applies to every kind of proxy, including VPN services, Tor, and public proxies. When in doubt, send a ${staffpm} seeking approval of your proxy or VPN. See our ${vpns_article} and ${ips_article} articles for more information." ],
			[ 'n' => "5.2",
			  'short' => "Do not abuse automated site access.",
			  'long' => "All automated site access must be done through the <a href=\"https://github.com/WhatCD/Gazelle/wiki/JSON-API-Documentation\">API</a>. API use is limited to 5 requests within any 10-second window. Scripts and other automated processes must not scrape the site's HTML pages." ],
			[ 'n' => "5.3",
			  'short' => "Do not autosnatch freeleech torrents.",
			  'long' => "The automatic snatching of freeleech torrents using any method involving little or no user-input (e.g., API-based scripts, log or site scraping, etc.) is prohibited. See ${site_name}'s ${autofl_article} article for more information." ],
			[ 'n' => "6.1",
			  'short' => "Do not seek or exploit live bugs for any reason.",
			  'long' => "Seeking or exploiting bugs in the live site (as opposed to a local development environment) is prohibited. If you discover a critical bug or security vulnerability, immediately report it in accordance with ${site_name}'s ${bugs_article}. Non-critical bugs can be reported in the <a href=\"forums.php?action=viewforum&forumid=27\">Bugs Forum</a>." ],
			[ 'n' => "6.2",
			  'short' => "Do not publish exploits.",
			  'long' => "The publication, organization, dissemination, sharing, technical discussion, or technical facilitation of exploits is prohibited at staff discretion. Exploits are defined as unanticipated or unaccepted uses of internal, external, non-profit, or for-profit services. See ${site_name}'s ${exploit_article} article for more information. Exploits are subject to reclassification at any time." ]
		);
		echo "<ul class=\"rules golden_rules\">\n";
		foreach($golden_rules as $gr) {
			$r_link = "gr${gr['n']}";
			echo    "<li id=\"${r_link}\">" .
					"<a href=\"#${r_link}\" class=\"rule_link\">${gr['n']}.</a>" .
					'<div class="rule_wrap">' .
						'<div class="rule_short">' .
							$gr['short'] .
						'</div>' .
						'<div class="rule_long">' .
							$gr['long'] .
						'</div>' .
					'</div>' .
				"</li>\n";
		}
		echo "</ul>\n";
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
