<?
//Include the header
View::show_header('Rule Index');
?>
<!-- General Rules -->
<div class="thin">
	<div class="header">
		<h3 id="general">Golden Rules</h3>
		<p>The Golden Rules encompass all of <? echo SITE_NAME; ?> and The What Network (IRC). These rules are paramount; non-compliance will jeopardize your account.</p>
	</div>
	<div class="box pad rule_summary" style="padding: 10px 10px 10px 20px;">
<? Rules::display_golden_rules(); ?>
	</div>
	<!-- END General Rules -->
<? include('jump.php'); ?>
</div>
<?
View::show_footer();
?>
