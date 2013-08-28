<?
if (!check_perms('admin_donor_log')) {
	error(403);
}

View::show_header('Bitcoin donation balance');

$Balance = DonationsBitcoin::get_balance() . ' BTC';
$BitcoinAddresses = DonationsBitcoin::get_received();
$Debug->log_var($BitcoinAddresses, 'Bitcoin addresses');

$UserQ = $DB->query("
	SELECT i.UserID, i.BitcoinAddress
	FROM users_info AS i
		JOIN users_main AS m ON m.ID = i.UserID
	WHERE BitcoinAddress != ''
	ORDER BY m.Username ASC");
?>
<div class="thin">
	<div class="header">
		<h3><?=$Balance?></h3>
	</div>
	<table>
	<tr class="colhead">
		<th>Username</th>
		<th>Receiving Bitcoin address</th>
		<th>Amount</th>
	</tr>
<?
while (list($UserID, $BitcoinAddress) = $DB->next_record(MYSQLI_NUM, false)) {
	if (!$BitcoinAddresses[$BitcoinAddress]) {
		continue;
	}
?>
	<tr>
		<td><?=Users::format_username($UserID, true, false, false, false)?></td>
		<td><tt><?=$BitcoinAddress?></tt></td>
		<td><?=$BitcoinAddresses[$BitcoinAddress]?> BTC</td>
	</tr>
<?
	$DB->set_query_id($UserQ);
}
?>
	</table>
</div>
<? View::show_footer(); ?>
