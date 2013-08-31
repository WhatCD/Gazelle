<?
if (!check_perms('users_mod')) {
	error(403);
}
View::show_header('Unprocessed Bitcoin donations');

// Find all donors
$AllDonations = DonationsBitcoin::get_received();

$DB->query("
	SELECT BitcoinAddress, SUM(Amount)
	FROM donations_bitcoin
	GROUP BY BitcoinAddress");
$OldDonations = G::$DB->to_pair(0, 1, false);
?>
<div id="thin">
	<div class="header">
		<h2>Unprocessed Bitcoin donations</h2>
	</div>
	<div class="box2">
		<div class="pad">Do not process these donations manually! The bitcoin parser <em>will</em> get them sooner or later (poke a developer if something seems broken).</div>
	</div>
	<table class="border" width="100%">
		<tr class="colhead">
			<td>Bitcoin address</td>
			<td>User</td>
			<td>Unprocessed amount</td>
			<td>Total amount</td>
			<td>Donor rank</td>
			<td>Special rank</td>
		</tr>
<?
$NewDonations = array();
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
	$NewDonations[$Address] = $Amount;
}
if (!empty($NewDonations)) {
	foreach(DonationsBitcoin::get_userids(array_keys($NewDonations)) as $Address => $UserID) {
?>
		<tr>
			<td><?=$Address?></td>
			<td><?=Users::format_username($UserID, true, false, false)?></td>
			<td><?=$NewDonations[$Address]?></td>
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
