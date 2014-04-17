<?
if (!check_perms('users_mod')) {
	error(403);
}
$Title = "Unprocessed Bitcoin Donations";
View::show_header($Title);

// Find all donors
$AllDonations = DonationsBitcoin::get_received();

$DB->query("
	SELECT BitcoinAddress, SUM(Amount)
	FROM donations_bitcoin
	GROUP BY BitcoinAddress");
$OldDonations = G::$DB->to_pair(0, 1, false);
?>
<div class="thin">
	<div class="header">
		<h2><?=$Title?></h2>
	</div>
	<div class="box2">
		<div class="pad"><strong>Do not process these donations manually!</strong> The Bitcoin parser <em>will</em> get them sooner or later (poke a developer if something seems broken).</div>
	</div>
<?
$NewDonations = array();
$TotalUnproc = 0;
foreach ($AllDonations as $Address => $Amount) {
	if (isset($OldDonations[$Address])) {
		if ($Amount == $OldDonations[$Address]) { // Direct comparison should be fine as everything comes from bitcoind
			continue;
		}
		$Debug->log_var(array('old' => $OldDonations[$Address], 'new' => $Amount), "New donations from $Address");
		// PHP doesn't do fixed-point math, and json_decode has already botched the precision
		// so let's just round this off to satoshis and pray that we're on a 64 bit system
		$Amount = round($Amount - $OldDonations[$Address], 8);
	}
	$TotalUnproc += $Amount;
	$NewDonations[$Address] = $Amount;
}
?>
	<table class="border" width="100%">
		<tr class="colhead">
			<td>Bitcoin Address</td>
			<td>User</td>
			<td>Unprocessed Amount (Total: <?=$TotalUnproc ?: '0'?>)</td>
			<td>Total Amount</td>
			<td>Donor Rank</td>
			<td>Special Rank</td>
		</tr>
<?
if (!empty($NewDonations)) {
	foreach (DonationsBitcoin::get_userids(array_keys($NewDonations)) as $Address => $UserID) {
		$DonationEUR = Donations::currency_exchange($NewDonations[$Address], 'BTC');
?>
		<tr>
			<td><?=$Address?></td>
			<td><?=Users::format_username($UserID, true, false, false)?></td>
			<td><?=$NewDonations[$Address]?> (<?="$DonationEUR EUR"?>)</td>
			<td><?=$AllDonations[$Address]?></td>
			<td><?=(int)Donations::get_rank($UserID)?></td>
			<td><?=(int)Donations::get_special_rank($UserID)?></td>
		</tr>
<?	}
} else { ?>
		<tr>
			<td colspan="7">No unprocessed Bitcoin donations</td>
		</tr>
<? } ?>
	</table>
</div>
<?
View::show_footer();
