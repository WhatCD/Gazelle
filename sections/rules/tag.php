<?
//Include the header
show_header('Tagging rules');
?>
<!-- General Rules -->
<div class="thin">
	<h3 id="general">Tagging rules</h3>
	<div class="box pad" style="padding:10px 10px 10px 20px;">
		<ul>
			<li>Tags should be comma separated, and you should use a period ('.') to separate words inside a tag - eg. '<strong style="color:green;">hip.hop</strong>'. 
			</li><li>
			There is a list of official tags on upload.php. Please use these tags instead of 'unofficial' tags (eg. use the official '<strong style="color:green;">drum.and.bass</strong>' tag, instead of an unofficial '<strong style="color:red;">dnb</strong>' tag.)
			</li><li>
			Avoid abbreviations if at all possible. So instead of tagging an album as '<strong style="color:red;">alt</strong>', tag it as '<strong style="color:green;">alternative</strong>'. Make sure that you use correct spelling. 
			</li><li>
			Avoid using multiple synonymous tags. Using both '<strong style="color:red;">prog.rock</strong>' and '<strong style="color:green;">progressive.rock</strong>' is redundant and annoying - just use the official '<strong style="color:green;">progressive.rock</strong>' tag. 
			</li><li>
			Don't use 'useless' tags, such as '<strong style="color:red;">seen.live</strong>', '<strong style="color:red;">awesome</strong>', '<strong style="color:red;">rap</strong>' (is encompassed by '<strong style="color:green;">hip.hop</strong>'), etc. If an album is live, you can tag it as '<strong style="color:green;">live</strong>'. 
			</li><li>
			Only tag information on the album itself - NOT THE INDIVIDUAL RELEASE. Tags such as '<strong style="color:red;">v0</strong>', '<strong style="color:red;">eac</strong>', '<strong style="color:red;">vinyl</strong>', '<strong style="color:red;">from.oink</strong>' etc are strictly forbidden. Remember that these tags will be used for other versions of the same album. 
			</li>
		</ul>
	</div>
	<!-- END General Rules -->
<? include('jump.php'); ?>
</div>
<?
show_footer();
?>