<?
//Useful: http://www.robtex.com/cnet/
$AllowedProxies = array(
	//Opera Turbo (may include Opera-owned IP addresses that aren't used for Turbo, but shouldn't run much risk of exploitation)
	'64.255.180.*', //Norway
	'64.255.164.*', //Norway
	'80.239.242.*', //Poland
	'80.239.243.*', //Poland
	'91.203.96.*', //Norway
	'94.246.126.*', //Norway
	'94.246.127.*', //Norway
	'195.189.142.*', //Norway
	'195.189.143.*', //Norway
);

function proxyCheck($IP) {
	global $AllowedProxies;
	for ($i = 0, $il = count($AllowedProxies); $i < $il; ++$i) {
		//based on the wildcard principle it should never be shorter
		if (strlen($IP) < strlen($AllowedProxies[$i])) {
			continue;
		}

		//since we're matching bit for bit iterating from the start
		for ($j = 0, $jl = strlen($IP); $j < $jl; ++$j) {
			//completed iteration and no inequality
			if ($j == $jl - 1 && $IP[$j] === $AllowedProxies[$i][$j]) {
				return true;
			}

			//wildcard
			if ($AllowedProxies[$i][$j] === '*') {
				return true;
			}

			//inequality found
			if ($IP[$j] !== $AllowedProxies[$i][$j]) {
				break;
			}
		}
	}
	return false;
}
