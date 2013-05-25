<?
//Include the header
View::show_header('Tagging rules');
?>
<!-- General Rules -->
<div class="thin">
	<div class="header">
		<h3 id="general">Tagging rules</h3>
	</div>
	<div class="box pad rule_summary" style="padding: 10px 10px 10px 20px;">
		<ul>
			<li>Tags should be comma separated, and you should use a period ('.') to separate words inside a tag - eg. '<strong class="important_text_alt">hip.hop</strong>'.
			</li><li>
			There is a list of official tags on upload.php. Please use these tags instead of 'unofficial' tags (eg. use the official '<strong class="important_text_alt">drum.and.bass</strong>' tag, instead of an unofficial '<strong class="important_text">dnb</strong>' tag.)
			</li><li>
			Avoid abbreviations if at all possible. So instead of tagging an album as '<strong class="important_text">alt</strong>', tag it as '<strong class="important_text_alt">alternative</strong>'. Make sure that you use correct spelling.
			</li><li>
			Avoid using multiple synonymous tags. Using both '<strong class="important_text">prog.rock</strong>' and '<strong class="important_text_alt">progressive.rock</strong>' is redundant and annoying - just use the official '<strong class="important_text_alt">progressive.rock</strong>' tag.
			</li><li>
			Don't use 'useless' tags, such as '<strong class="important_text">seen.live</strong>', '<strong class="important_text">awesome</strong>', '<strong class="important_text">rap</strong>' (is encompassed by '<strong class="important_text_alt">hip.hop</strong>'), etc. If an album is live, you can tag it as '<strong class="important_text_alt">live</strong>'.
			</li><li>
			Only tag information on the album itself - NOT THE INDIVIDUAL RELEASE. Tags such as '<strong class="important_text">v0</strong>', '<strong class="important_text">eac</strong>', '<strong class="important_text">vinyl</strong>', '<strong class="important_text">from.oink</strong>' etc are strictly forbidden. Remember that these tags will be used for other versions of the same album.
			</li>
		</ul>
	</div>
	<!-- END General Rules -->
<? include('jump.php'); ?>
</div>
<?
View::show_footer();
?>
