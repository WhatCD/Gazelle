<?
enforce_login();
View::show_header('Donation Complete');
?>
<div class="thin">
	<div class="header">
		<h3 id="forums">Donation Complete</h3>
	</div>
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
		<p>Thank you for your donation! If this is your first time donating, you will have received two (2) invitations and a <img src="<?=(STATIC_SERVER)?>common/symbols/donor.png" alt="Donor" />.</p>
	</div>
</div>
<? View::show_footer();?>
