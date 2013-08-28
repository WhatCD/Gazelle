<?
if (isset($_SERVER['http_if_modified_since'])) {
	header('Status: 304 Not Modified');
	die();
}

header('Expires: '.date('D, d-M-Y H:i:s \U\T\C', time() + 3600 * 24 * 120)); //120 days
header('Last-Modified: '.date('D, d-M-Y H:i:s \U\T\C', time()));

if (!check_perms('users_view_ips')) {
	die('Access denied.');
}

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
	die('Invalid IPv4 address.');
}

$Host = Tools::lookup_ip($_GET['ip']);

if ($Host === '') {
	trigger_error('Tools::get_host_by_ajax() command failed with no output, ensure that the host command exists on your system and accepts the argument -W');
} elseif ($Host === false) {
	print 'Could not retrieve host.';
} else {
	print $Host;
}
