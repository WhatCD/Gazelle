<?
class G {
	public static $DB;
	public static $Cache;
	public static $LoggedUser;

	public static function initialize() {
		global $DB, $Cache, $LoggedUser;
		self::$DB = $DB;
		self::$Cache = $Cache;
		self::$LoggedUser =& $LoggedUser;
	}
}