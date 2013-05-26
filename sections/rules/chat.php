<?
//Include the header
View::show_header('Chat Rules');
?>
<!-- Forum Rules -->
<div class="thin">
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
		<p>Anything not allowed on the forums is also not allowed on IRC and vice versa. They are separated for convenience only.</p>
	</div>
	<br />
	<h3 id="forums">Forum Rules</h3>
	<div class="box pad rule_summary" style="padding: 10px 10px 10px 20px;">
		<ul>
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
		</ul>
	</div>
</div>
<!-- END Forum Rules -->

<!-- IRC Rules -->
<div class="thin">
	<h3 id="irc">IRC Rules</h3>
	<div class="box pad rule_summary" style="padding: 10px 10px 10px 20px;">
<?		Rules::display_irc_chat_rules() ?>
	</div>
<? include('jump.php'); ?>
</div>
<?
View::show_footer();
?>
