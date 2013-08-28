<?
//TODO: Move to somewhere more appropriate, doesn't really belong under users, tools maybe but we don't have that page publicly accessible.

if (isset($_GET['ip']) && isset($_GET['port'])) {
	$Octets = explode('.', $_GET['ip']);
	if (
		empty($_GET['ip'])
		|| !preg_match('/'.IP_REGEX.'/', $_GET['ip'])
		|| $Octets[0] < 0
		|| $Octets[0] > 255
		|| $Octets[1] < 0
		|| $Octets[1] > 255
		|| $Octets[2] < 0
		|| $Octets[2] > 255
		|| $Octets[3] < 0
		|| $Octets[3] > 255
		/*
		 * Per RFC 1918, the following CIDR blocks should never be found on the public Internet.
		 *		10.0.0.0/8
		 *		172.16.0.0/12
		 *		192.168.0.0/16
		 *
		 * Per RFC 3330, the block 127.0.0.0/8 should never appear on any network.
		 *
		 */
		|| $Octets[0] == 127
		|| $Octets[0] == 10
		|| ($Octets[0] == 172 && ((16 <= $Octets[1]) && ($Octets[1] <= 31)))
		|| ($Octets[0] == 192 && $Octets[1] == 168)
	) {
		die('Invalid IPv4 address');
	}

	// Valid port numbers are defined in RFC 1700
	if (empty($_GET['port']) || !is_number($_GET['port']) || $_GET['port'] < 1 || $_GET['port'] > 65535) {
		die('Invalid port');
	}

	// Error suppression, ugh.
	if (@fsockopen($_GET['ip'], $_GET['port'], $Errno, $Errstr, 20)) {
		die('Port '.$_GET['port'].' on '.$_GET['ip'].' connected successfully.');
	} else {
		die('Port '.$_GET['port'].' on '.$_GET['ip'].' failed to connect.');
	}
}

View::show_header('Connectability Checker');
?>
<div class="thin">
	<div class="header">
		<h2><a href="user.php?id=<?=$LoggedUser['ID']?>"><?=$LoggedUser['Username']?></a> &gt; Connectability Checker</h2>
	</div>
	<form class="manage_form" name="connections" action="javascript:check_ip();" method="get">
		<table class="layout">
			<tr>
				<td class="label">IP address</td>
				<td>
					<input type="text" id="ip" name="ip" value="<?=$_SERVER['REMOTE_ADDR']?>" size="20" />
				</td>
				<td class="label">Port</td>
				<td>
					<input type="text" id="port" name="port" size="10" />
				</td>
				<td>
					<input type="submit" value="Check" />
				</td>
			</tr>
		</table>
	</form>
	<div id="result" class="box pad"></div>
</div>
<script type="text/javascript">//<![CDATA[
var result = $('#result').raw();

function check_ip() {
	var intervalid = setInterval("result.innerHTML += '.';",999);
	result.innerHTML = 'Checking.';
	ajax.get('user.php?action=connchecker&ip=' + $('#ip').raw().value + '&port=' + $('#port').raw().value, function (response) {
		clearInterval(intervalid);
		result.innerHTML = response;
	});
}
//]]>
</script>
<? View::show_footer(); ?>
