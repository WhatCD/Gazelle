<?
//TODO: Developer, add resend last donation when available AND add missing headers to Test IPN
enforce_login();

//Include the header
if ($LoggedUser['RatioWatch']) {
	error('Due to the high volume of payment disputes, we do not accept donations from users on ratio watch. Sorry.');
}

if (!$UserCount = $Cache->get_value('stats_user_count')) {
	$DB->query("
		SELECT COUNT(ID)
		FROM users_main
		WHERE Enabled = '1'");
	list($UserCount) = $DB->next_record();
	$Cache->cache_value('stats_user_count', $UserCount, 0); //inf cache
}

$DonorPerms = Permissions::get_permissions(DONOR);

View::show_header('Donate');

?>
<!-- Donate -->
<div class="thin">
<? if (check_perms('site_debug')) { ?>
	<div class="header">
		<h2>Test IPN</h2>
	</div>
	<div class="box pad">
		<form class="donate_form" name="test_paypal" method="post" action="donate.php">
			<input type="hidden" name="action" value="ipn" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<?=PAYPAL_SYMBOL?> <input type="text" name="mc_gross" value="<?=number_format(PAYPAL_MINIMUM, 2)?>" />
			<input type="hidden" name="custom" value="<?=$LoggedUser['ID']?>" />
			<input type="hidden" name="payment_status" value="Completed" />
			<input type="hidden" name="mc_fee" value="0.45" />
			<input type="hidden" name="business" value="<?=PAYPAL_ADDRESS?>" />
			<input type="hidden" name="txn_id" value="0" />
			<input type="hidden" name="payment_type" value="instant" />
			<input type="text" name="payer_email" value="<?=$LoggedUser['Username']?>@<?=NONSSL_SITE_URL?>" />
			<input type="hidden" name="mc_currency" value="<?=PAYPAL_CURRENCY?>" />
			<input name="test" type="submit" value="Donate" />
		</form>
	</div>
<?
}
?>
	<div class="header">
		<h2>Donate</h2>
	</div>
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
		<p>We accept donations to cover the costs associated with running the site and tracker. These costs come from the rental and purchase of the hardware the site runs on (servers, components, etc.), in addition to operating expenses (bandwidth, power, etc.).</p>
		<p>Because we do not have any advertisements or sponsorships and this service is provided free of charge, we are entirely reliant upon user donations. If you are financially able, please consider making a donation to help us pay the bills!</p>
		<p>We currently only accept one payment method: PayPal. Because of the fees they charge, there is a <strong>minimum donation amount of <?=PAYPAL_SYMBOL?> <?=PAYPAL_MINIMUM?></strong> (Please note, this is only a minimum amount and we greatly appreciate any extra you can afford.).</p>
		<p>You don't have to be a PayPal member to make a donation, you can simply donate with your credit or debit card. If you do not have a credit or debit card, you should be able to donate from your bank account, but you will need to make an account with them to do this.</p>
		<form class="donate_form" name="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="rm" value="2" />
			<input type="hidden" name="cmd" value="_donations" />
			<input type="hidden" name="business" value="<?=PAYPAL_ADDRESS?>" />
			<input type="hidden" name="return" value="<?=site_url()?>donate.php?action=complete" />
			<input type="hidden" name="cancel_return" value="<?=site_url()?>donate.php?action=cancel" />
			<input type="hidden" name="notify_url" value="<?=site_url()?>donate.php?action=ipn" />
			<input type="hidden" name="item_name" value="Donation" />
			<input type="hidden" name="amount" value="" />
			<input type="hidden" name="custom" value="<?=$LoggedUser['ID']?>" />
			<input type="hidden" name="no_shipping" value="0" />
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="currency_code" value="<?=PAYPAL_CURRENCY?>" />
			<input type="hidden" name="tax" value="0" />
			<input type="hidden" name="bn" value="PP-DonationsBF" />
			<input type="submit" value="PayPal Donate" />
		</form>
	</div>

?>
	<h3>What you will receive for a 5&euro; minimum donation</h3>
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
		<ul>
<?	if ($LoggedUser['Donor']) { ?>
			<li>Even more love! (You will not get multiple hearts.)</li>
			<li>A warmer, fuzzier feeling than before!</li>
<?	} else { ?>
			<li>Our eternal love, as represented by the <img src="<?=STATIC_SERVER?>common/symbols/donor.png" alt="Donor" /> you get next to your name.</li>
			<li>Two invitations to invite 2 good friends to use this tracker.</li>
<?
		if (USER_LIMIT != 0 && $UserCount >= USER_LIMIT && !check_perms('site_can_invite_always') && !isset($DonorPerms['site_can_invite_always'])) {
?>
			<li class="warning">Note: Because the user limit has been reached, you will be unable to use the invites received until a later date.</li>
<?		} ?>
			<li>Immunity to inactivity pruning.</li>
			<li>Access to an ever growing list of exclusive features, including the ability to submit requests and personal collages.</li>
			<li>A warm, fuzzy feeling.</li>

<?	} ?>
		</ul>
	</div>
	<h3>What you will <strong>not</strong> receive</h3>
	<div class="box pad" style="padding: 10px 10px 10px 20px;">
		<ul>
<?	if ($LoggedUser['Donor']) { ?>
			<li>Two more invitations; these are one time only.</li>
<?	} ?>
			<li>Immunity from the rules.</li>
			<li>Additional upload credit.</li>
		</ul>
	</div>
</div>
<!-- END Donate -->
<? View::show_footer(); ?>
