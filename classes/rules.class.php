<?php
class Rules {
	/**
	 * Displays the site's rules for tags.
	 *
	 * @param boolean $OnUpload - whether it's being displayed on a torrent upload form
	 */
	public static function display_site_tag_rules($OnUpload = false) {
		?>
		<ul>
			<li>Tags should be comma-separated, and you should use a period (".") to separate words inside a tag &mdash; e.g. "<strong class="important_text_alt">hip.hop</strong>".</li>

			<li>There is a list of official tags <?=($OnUpload ? 'to the left of the text box' : 'on <a href="upload.php">the torrent upload page</a>')?>. Please use these tags instead of "unofficial" tags (e.g. use the official "<strong class="important_text_alt">drum.and.bass</strong>" tag, instead of an unofficial "<strong class="important_text">dnb</strong>" tag). <strong>Please note that the "<strong class="important_text_alt">2000s</strong>" tag refers to music produced between 2000 and 2009.</strong></li>

			<li>Avoid abbreviations if at all possible. So instead of tagging an album as "<strong class="important_text">alt</strong>", tag it as "<strong class="important_text_alt">alternative</strong>". Make sure that you use correct spelling.</li>

			<li>Avoid using multiple synonymous tags. Using both "<strong class="important_text">prog.rock</strong>" and "<strong class="important_text_alt">progressive.rock</strong>" is redundant and annoying&mdash;just use the official "<strong class="important_text_alt">progressive.rock</strong>" tag.</li>

			<li>Do not add "useless" tags, such as "<strong class="important_text">seen.live</strong>", "<strong class="important_text">awesome</strong>", "<strong class="important_text">rap</strong>" (is encompassed by "<strong class="important_text_alt">hip.hop</strong>"), etc. If an album is live, you can tag it as "<strong class="important_text_alt">live</strong>".</li>

			<li>Only tag information on the album itself&mdash;<strong>not the individual release</strong>. Tags such as "<strong class="important_text">v0</strong>", "<strong class="important_text">eac</strong>", "<strong class="important_text">vinyl</strong>", "<strong class="important_text">from.oink</strong>", etc. are strictly forbidden. Remember that these tags will be used for other versions of the same album.</li>

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
			<li>Impersonation of other members&mdash;particularly staff members&mdash;will not go unpunished. If you are uncertain of a user's identity, check their vhost.</li>
			<li>Spamming is strictly forbidden. This includes, but is not limited to, personal sites, online auctions, and torrent uploads.</li>
			<li>Obsessive annoyance&mdash;both to other users and staff&mdash;will not be tolerated.</li>
			<li>Do not PM, DCC, or Query anyone you don't know or have never talked to without asking first; this applies specifically to staff.</li>
			<li>No language other than English is permitted in the official IRC channels. If we cannot understand it, we cannot moderate it.</li>
			<li>The offering, selling, trading, and giving away of invites to this or any other site on our IRC network is <strong>strictly forbidden</strong>.</li>
			<li><strong>Read the channel topic before asking questions.</strong></li>
		</ul>
<?
	}
}
