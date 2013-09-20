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
<?		Rules::display_forum_rules() ?>
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
