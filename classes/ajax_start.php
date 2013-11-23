<?
require 'config.php'; //The config contains all site wide configuration information as well as memcached rules
require(SERVER_ROOT.'/classes/debug.class.php');
require(SERVER_ROOT.'/classes/cache.class.php'); //Require the caching class
require(SERVER_ROOT.'/classes/encrypt.class.php'); //Require the caching class

$Debug = new DEBUG;
$Cache = NEW CACHE($MemcachedServers); //Load the caching class
$Enc = NEW CRYPT; //Load the encryption class

$SSL = $_SERVER['SERVER_PORT'] === '443';

if (isset($_COOKIE['session'])) {
	$LoginCookie = $Enc->decrypt($_COOKIE['session']);
}
if (isset($LoginCookie)) {
	list($SessionID, $UserID) = explode("|~|", $Enc->decrypt($LoginCookie));

	if (!$UserID || !$SessionID) {
		die('Not logged in!');
	}

	if (!$Enabled = $Cache->get_value("enabled_$UserID")) {
		require(SERVER_ROOT.'/classes/mysql.class.php'); //Require the database wrapper
		$DB = NEW DB_MYSQL; //Load the database wrapper
		$DB->query("
			SELECT Enabled
			FROM users_main
			WHERE ID = '$UserID'");
		list($Enabled) = $DB->next_record();
		$Cache->cache_value("enabled_$UserID", $Enabled, 0);
	}
} else {
	die('Not logged in!');
}

function error($Error) {
	die($Error);
}

function is_number($Str) {
	if ($Str < 0) {
		return false;
	}
	// We're converting input to a int, then string and comparing to original
	return ($Str == strval(intval($Str)) ? true : false);
}

function display_str($Str) {
	if ($Str != '') {
		$Str = make_utf8($Str);
		$Str = mb_convert_encoding($Str, 'HTML-ENTITIES', 'UTF-8');
		$Str = preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/m", '&amp;', $Str);

		$Replace = array(
			"'",'"',"<",">",
			'&#128;','&#130;','&#131;','&#132;','&#133;','&#134;','&#135;','&#136;','&#137;','&#138;','&#139;','&#140;','&#142;','&#145;','&#146;','&#147;','&#148;','&#149;','&#150;','&#151;','&#152;','&#153;','&#154;','&#155;','&#156;','&#158;','&#159;'
		);

		$With = array(
			'&#39;','&quot;','&lt;','&gt;',
			'&#8364;','&#8218;','&#402;','&#8222;','&#8230;','&#8224;','&#8225;','&#710;','&#8240;','&#352;','&#8249;','&#338;','&#381;','&#8216;','&#8217;','&#8220;','&#8221;','&#8226;','&#8211;','&#8212;','&#732;','&#8482;','&#353;','&#8250;','&#339;','&#382;','&#376;'
		);

		$Str = str_replace($Replace, $With, $Str);
	}
	return $Str;
}

function make_utf8($Str) {
	if ($Str != '') {
		if (is_utf8($Str)) {
			$Encoding = 'UTF-8';
		}
		if (empty($Encoding)) {
			$Encoding = mb_detect_encoding($Str, 'UTF-8, ISO-8859-1');
		}
		if (empty($Encoding)) {
			$Encoding = 'ISO-8859-1';
		}
		if ($Encoding == 'UTF-8') {
			return $Str;
		} else {
			return @mb_convert_encoding($Str, 'UTF-8', $Encoding);
		}
	}
}

function is_utf8($Str) {
	return preg_match('%^(?:
		[\x09\x0A\x0D\x20-\x7E]			 // ASCII
		| [\xC2-\xDF][\x80-\xBF]			// non-overlong 2-byte
		| \xE0[\xA0-\xBF][\x80-\xBF]		// excluding overlongs
		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} // straight 3-byte
		| \xED[\x80-\x9F][\x80-\xBF]		// excluding surrogates
		| \xF0[\x90-\xBF][\x80-\xBF]{2}	 // planes 1-3
		| [\xF1-\xF3][\x80-\xBF]{3}		 // planes 4-15
		| \xF4[\x80-\x8F][\x80-\xBF]{2}	 // plane 16
		)*$%xs', $Str
	);
}

function display_array($Array, $DontEscape = array()) {
	foreach ($Array as $Key => $Val) {
		if (!in_array($Key, $DontEscape)) {
			$Array[$Key] = display_str($Val);
		}
	}
	return $Array;
}

function make_secret($Length = 32) {
	$Secret = '';
	$Chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
	for ($i = 0; $i < $Length; $i++) {
		$Rand = mt_rand(0, strlen($Chars) - 1);
		$Secret .= substr($Chars, $Rand, 1);
	}
	return str_shuffle($Secret);
}

// Send a message to an IRC bot listening on SOCKET_LISTEN_PORT
function send_irc($Raw) {
	$IRCSocket = fsockopen(SOCKET_LISTEN_ADDRESS, SOCKET_LISTEN_PORT);
	$Raw = str_replace(array("\n", "\r"), '', $Raw);
	fwrite($IRCSocket, $Raw);
	fclose($IRCSocket);
}
