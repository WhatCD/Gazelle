<?
if(!check_perms('admin_donor_log')) { error(403); }

include(SERVER_ROOT.'/sections/donate/config.php');

show_header('Bitcoin donation balance');

$Balance = btc_balance() . " BTC";
$Receiveds = btc_received();
$DB->query("SELECT m.ID, m.Username, i.Donor, i.BitcoinAddress FROM users_main m INNER JOIN users_info i ON m.ID = i.UserID WHERE BitcoinAddress IS NOT NULL");
?>
<div class="thin">
	<h3><?=$Balance?></h3>
	<table>
	<tr>
		<th>Username</th>
		<th>Receiving bitcoin address</th>
		<th>Amount</th>
	</tr>
<?
while ($row = $DB->next_record()) {
	$amount = false;
	foreach ($Receiveds as $R) {
		if ($R->address == $row['BitcoinAddress']) {
			$amount = $R->amount . ' BTC';
		}
	}
	if ($amount === false) { continue; }
	?>
	<tr>
		<td><?=format_username($row['ID'],$row['Username'],$row['Donor'])?></td>
		<td><tt><?=$row['BitcoinAddress']?></tt></td>
		<td><?=$amount?></td>
	</tr>
	<?
}
?>
	</table>
</div>
<? show_footer(); ?>
