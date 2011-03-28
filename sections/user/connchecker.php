<?
//TODO: Move to somewhere more appropriate, doesn't really belong under users, tools maybe but we don't have that page publicly accessible.

if(isset($_GET['ip']) && isset($_GET['port'])){
	$Octets = explode(".", $_GET['ip']);
	if(
		empty($_GET['ip']) ||
		!preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $_GET['ip']) ||
		$Octets[0] < 0 ||
		$Octets[0] > 255 ||
		$Octets[1] < 0 ||
		$Octets[1] > 255 ||
		$Octets[2] < 0 ||
		$Octets[2] > 255 ||
		$Octets[3] < 0 ||
		$Octets[3] > 255 ||
		$Octets[0] == 127 ||
		$Octets[0] == 192
	) {
		die('Invalid IP');
	}
	
	if (empty($_GET['port']) || !is_number($_GET['port']) ||  $_GET['port']<1 || $_GET['port']>65535){
		die('Invalid Port');
	}

	//Error suppression, ugh.	
	if(@fsockopen($_GET['ip'], $_GET['port'], $Errno, $Errstr, 20)){
		die('Port '.$_GET['port'].' on '.$_GET['ip'].' connected successfully.');
	} else {
		die('Port '.$_GET['port'].' on '.$_GET['ip'].' failed to connect.');
	}
}

show_header('Connectability Checker');
?>
<div class="thin">
	<h2><a href="user.php?id=<?=$LoggedUser['ID']?>"><?=$LoggedUser['Username']?></a> &gt; Connectability Checker</h2>
	<form action="javascript:check_ip();" method="get">
		<table>
			<tr>
				<td class="label">IP</td>
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
<script type="text/javascript">
var result = $('#result').raw();

function check_ip() {
	var intervalid = setInterval("result.innerHTML += '.';",999);
	result.innerHTML = 'Checking.';
	ajax.get('user.php?action=connchecker&ip=' + $('#ip').raw().value + '&port=' + $('#port').raw().value, function (response) {
		clearInterval(intervalid);
		result.innerHTML = response;
	});
}
</script>
<? show_footer(); ?>
