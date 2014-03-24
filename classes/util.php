<?
// This is a file of miscellaneous functions that are called so damn often
// that it'd just be annoying to stick them in namespaces.

/**
 * Return true if the given string is numeric.
 *
 * @param mixed $Str
 * @return bool
 */
if (PHP_INT_SIZE === 4) {
	function is_number($Str) {
		if ($Str === null || $Str === '') {
			return false;
		}
		if (is_int($Str)) {
			return true;
		}
		if ($Str[0] == '-' || $Str[0] == '+') { // Leading plus/minus signs are ok
			$Str[0] = 0;
		}
		return ltrim($Str, "0..9") === '';
	}
} else {
	function is_number($Str) {
		return $Str == strval(intval($Str));
	}
}

function is_date($Date) {
	list($Y, $M, $D) = explode('-', $Date);
	if (checkdate($M, $D, $Y)) {
		return true;
	}
	return false;
}

/**
 * Check that some given variables (usually in _GET or _POST) are numbers
 *
 * @param array $Base array that's supposed to contain all keys to check
 * @param array $Keys list of keys to check
 * @param mixed $Error error code or string to pass to the error() function if a key isn't numeric
 */
function assert_numbers(&$Base, $Keys, $Error = 0) {
	// make sure both arguments are arrays
	if (!is_array($Base) || !is_array($Keys)) {
		return;
	}
	foreach ($Keys as $Key) {
		if (!isset($Base[$Key]) || !is_number($Base[$Key])) {
			error($Error);
		}
	}
}

/**
 * Return true, false or null, depending on the input value's "truthiness" or "non-truthiness"
 *
 * @param $Value the input value to check for truthiness
 * @return true if $Value is "truthy", false if it is "non-truthy" or null if $Value was not
 *         a bool-like value
 */
function is_bool_value($Value) {
	if (is_bool($Value)) {
		return $Value;
	}
	if (is_string($Value)) {
		switch (strtolower($Value)) {
			case 'true':
			case 'yes':
			case 'on':
			case '1':
				return true;
			case 'false':
			case 'no':
			case 'off':
			case '0':
				return false;
		}
	}
	if (is_numeric($Value)) {
		if ($Value == 1) {
			return true;
		} elseif ($Value == 0) {
			return false;
		}
	}
	return null;
}

/**
 * HTML-escape a string for output.
 * This is preferable to htmlspecialchars because it doesn't screw up upon a double escape.
 *
 * @param string $Str
 * @return string escaped string.
 */
function display_str($Str) {
	if ($Str === null || $Str === false || is_array($Str)) {
		return '';
	}
	if ($Str != '' && !is_number($Str)) {
		$Str = Format::make_utf8($Str);
		$Str = mb_convert_encoding($Str, 'HTML-ENTITIES', 'UTF-8');
		$Str = preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/m", '&amp;', $Str);

		$Replace = array(
			"'",'"',"<",">",
			'&#128;','&#130;','&#131;','&#132;','&#133;','&#134;','&#135;','&#136;',
			'&#137;','&#138;','&#139;','&#140;','&#142;','&#145;','&#146;','&#147;',
			'&#148;','&#149;','&#150;','&#151;','&#152;','&#153;','&#154;','&#155;',
			'&#156;','&#158;','&#159;'
		);

		$With = array(
			'&#39;','&quot;','&lt;','&gt;',
			'&#8364;','&#8218;','&#402;','&#8222;','&#8230;','&#8224;','&#8225;','&#710;',
			'&#8240;','&#352;','&#8249;','&#338;','&#381;','&#8216;','&#8217;','&#8220;',
			'&#8221;','&#8226;','&#8211;','&#8212;','&#732;','&#8482;','&#353;','&#8250;',
			'&#339;','&#382;','&#376;'
		);

		$Str = str_replace($Replace, $With, $Str);
	}
	return $Str;
}


/**
 * Send a message to an IRC bot listening on SOCKET_LISTEN_PORT
 *
 * @param string $Raw An IRC protocol snippet to send.
 */
function send_irc($Raw) {
	$IRCSocket = fsockopen(SOCKET_LISTEN_ADDRESS, SOCKET_LISTEN_PORT);
	$Raw = str_replace(array("\n", "\r"), '', $Raw);
	fwrite($IRCSocket, $Raw);
	fclose($IRCSocket);
}


/**
 * Display a critical error and kills the page.
 *
 * @param string $Error Error type. Automatically supported:
 *	403, 404, 0 (invalid input), -1 (invalid request)
 *	If you use your own string for Error, it becomes the error description.
 * @param boolean $NoHTML If true, the header/footer won't be shown, just the description.
 * @param string $Log If true, the user is given a link to search $Log in the site log.
 */
function error($Error, $NoHTML = false, $Log = false) {
	global $Debug;
	require(SERVER_ROOT.'/sections/error/index.php');
	$Debug->profile();
	die();
}


/**
 * Convenience function. See doc in permissions.class.php
 */
function check_perms($PermissionName, $MinClass = 0) {
	return Permissions::check_perms($PermissionName, $MinClass);
}

/**
 * Print JSON status result with an optional message and die.
 * DO NOT USE THIS FUNCTION!
 */
function json_die($Status, $Message) {
	json_print($Status, $Message);
	die();
}

/**
 * Print JSON status result with an optional message.
 */
function json_print($Status, $Message) {
	if ($Status == 'success' && $Message) {
		print json_encode(array('status' => $Status, 'response' => $Message));
	} elseif ($Message) {
		print json_encode(array('status' => $Status, 'error' => $Message));
	} else {
		print json_encode(array('status' => $Status, 'response' => array()));
	}
}

/**
 * Print the site's URL including the appropriate URI scheme, including the trailing slash
 *
 * @param bool $SSL - whether the URL should be crafted for HTTPS or regular HTTP
 */
function site_url($SSL = true) {
	return $SSL ? 'https://' . SSL_SITE_URL . '/' : 'http://' . NONSSL_SITE_URL . '/';
}
?>
