<?
/*************************************************************************|
|--------------- Cookie class --------------------------------------------|
|*************************************************************************|

This class handles cookies.

$Cookie->get(); is user provided and untrustworthy

|*************************************************************************/

/*
interface COOKIE_INTERFACE {
	public function get($Key);
	public function set($Key, $Value, $Seconds, $LimitAccess);
	public function del($Key);

	public function flush();
}
*/

class COOKIE /*implements COOKIE_INTERFACE*/ {
	const LIMIT_ACCESS = true; //If true, blocks JS cookie API access by default (can be overridden case by case)
	const PREFIX = ''; //In some cases you may desire to prefix your cookies

	public function get($Key) {
		if (!isset($_COOKIE[SELF::PREFIX.$Key])) {
			return false;
		}
		return $_COOKIE[SELF::PREFIX.$Key];
	}

	//Pass the 4th optional param as false to allow JS access to the cookie
	public function set($Key, $Value, $Seconds = 86400, $LimitAccess = SELF::LIMIT_ACCESS) {
		setcookie(SELF::PREFIX.$Key, $Value, time() + $Seconds, '/', SITE_URL, $_SERVER['SERVER_PORT'] === '443', $LimitAccess, false);
	}

	public function del($Key) {
		setcookie(SELF::PREFIX.$Key, '', time() - 24 * 3600); //3600 vs 1 second to account for potential clock desyncs
	}

	public function flush() {
		$Cookies = array_keys($_COOKIE);
		foreach ($Cookies as $Cookie) {
			$this->del($Cookie);
		}
	}
}
