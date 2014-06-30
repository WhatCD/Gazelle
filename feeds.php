<?
/*-- Feed Start Class ----------------------------------*/
/*------------------------------------------------------*/
/* Simplified version of script_start, used for the	 */
/* sitewide RSS system.								 */
/*------------------------------------------------------*/
/********************************************************/

// Let's prevent people from clearing feeds
if (isset($_GET['clearcache'])) {
	unset($_GET['clearcache']);
}

require 'classes/config.php'; // The config contains all site-wide configuration information as well as memcached rules

require(SERVER_ROOT.'/classes/misc.class.php'); // Require the misc class
require(SERVER_ROOT.'/classes/cache.class.php'); // Require the caching class
require(SERVER_ROOT.'/classes/feed.class.php'); // Require the feeds class
$Cache = NEW CACHE($MemcachedServers); // Load the caching class
$Feed = NEW FEED; // Load the time class

function check_perms() {
	return false;
}

function is_number($Str) {
	if ($Str < 0) {
		return false;
	}
	// We're converting input to an int, then string, and comparing to the original
	return ($Str == strval(intval($Str)) ? true : false);
}

function display_str($Str) {
	if ($Str != '') {
		$Str = make_utf8($Str);
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

function display_array($Array, $Escape = array()) {
	foreach ($Array as $Key => $Val) {
		if ((!is_array($Escape) && $Escape == true) || !in_array($Key, $Escape)) {
			$Array[$Key] = display_str($Val);
		}
	}
	return $Array;
}

/**
 * Print the site's URL including the appropriate URI scheme, including the trailing slash
 *
 * @param bool $SSL - whether the URL should be crafted for HTTPS or regular HTTP
 */
function site_url($SSL = true) {
	return $SSL ? 'https://' . SSL_SITE_URL . '/' : 'http://' . NONSSL_SITE_URL . '/';
}

header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma:');
header('Expires: '.date('D, d M Y H:i:s', time() + (2 * 60 * 60)).' GMT');
header('Last-Modified: '.date('D, d M Y H:i:s').' GMT');

$Feed->UseSSL = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
require(SERVER_ROOT.'/sections/feeds/index.php');
