<?
View::show_header('Client Rules');

if (!$WhitelistedClients = $Cache->get_value('whitelisted_clients')) {
	$DB->query('
		SELECT vstring
		FROM xbt_client_whitelist
		WHERE vstring NOT LIKE \'//%\'
		ORDER BY vstring ASC');
	$WhitelistedClients = $DB->to_array(false, MYSQLI_NUM, false);
	$Cache->cache_value('whitelisted_clients', $WhitelistedClients, 604800);
}
?>
	<div class="thin">
	<div class="header">
		<h2 class="center">Client Whitelist</h2>
	</div>
	<div class="box pad">
		<p>Client rules are how we maintain the integrity of our swarms. This allows us to filter out disruptive and dishonest clients that may hurt the performance of either the tracker or individual peers.</p>
		<table cellpadding="5" cellspacing="1" border="0" class="border" width="100%">
			<tr class="colhead">
				<td style="width: 150px;"><strong>Allowed Client</strong></td>
				<!-- td style="width: 400px;"><strong>Additional Notes</strong></td> -->
			</tr>
<?
	$Row = 'a';
	foreach ($WhitelistedClients as $Client) {
		//list($ClientName, $Notes) = $Client;
		list($ClientName) = $Client;
		$Row = $Row === 'a' ? 'b' : 'a';
?>
			<tr class="row<?=$Row?>">
				<td><?=$ClientName?></td>
			</tr>
<?	} ?>
		</table>
	</div>

	<h3>Further Rules</h3>
	<div class="box pad rule_summary">
		<p>
			The modification of clients to bypass our client requirements (spoofing) is explicitly forbidden. People caught doing this will be instantly and permanently banned. This is your only warning.
		</p>
		<p>
			The use of clients or proxies which have been modified to report incorrect stats to the tracker (cheating) is not allowed, and will result in a permanent ban. Additionally, your information will be passed on to representatives of other trackers, where you are liable to be banned as well.
		</p>
		<p>
			The testing of unstable clients by developers is not allowed unless approved by a staff member.
		</p>
	</div>
	<h3>Further Details</h3>
	<div class="box pad rule_summary">
		<p>
			If someone you invited to the site breaks the above rules you will receive a 2 month warning and lose the right to invite people to this site.
		</p>
		<p>
			If you were invited by someone who broke the above rules, your account will be disabled without warning.
		</p>
	</div>
<? include('jump.php'); ?>
</div>
<? View::show_footer(); ?>
