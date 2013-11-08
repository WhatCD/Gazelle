<?php
/*-- Script Start Class --------------------------------*/
/*------------------------------------------------------*/
/* This isnt really a class but a way to tie other      */
/* classes and functions used all over the site to the  */
/* page currently being displayed.                      */
/*------------------------------------------------------*/
/* The code that includes the main php files and		*/
/* generates the page are at the bottom.				*/
/*------------------------------------------------------*/
/********************************************************/
require 'config.php'; //The config contains all site wide configuration information
//Deal with dumbasses
if (isset($_REQUEST['info_hash']) && isset($_REQUEST['peer_id'])) {
	die('d14:failure reason40:Invalid .torrent, try downloading again.e');
}

require(SERVER_ROOT.'/classes/proxies.class.php');

// Get the user's actual IP address if they're proxied.
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])
		&& proxyCheck($_SERVER['REMOTE_ADDR'])
		&& filter_var($_SERVER['HTTP_X_FORWARDED_FOR'],
				FILTER_VALIDATE_IP,
				FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
}


$SSL = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
if (!isset($argv) && !empty($_SERVER['HTTP_HOST'])) {
//Skip this block if running from cli or if the browser is old and shitty
	if (!$SSL && $_SERVER['HTTP_HOST'] == 'www.'.NONSSL_SITE_URL) {
		header('Location: http://'.NONSSL_SITE_URL.$_SERVER['REQUEST_URI']); die();
	}
	if ($SSL && $_SERVER['HTTP_HOST'] == 'www.'.NONSSL_SITE_URL) {
		header('Location: https://'.SSL_SITE_URL.$_SERVER['REQUEST_URI']); die();
	}
	if (SSL_SITE_URL != NONSSL_SITE_URL) {
		if (!$SSL && $_SERVER['HTTP_HOST'] == SSL_SITE_URL) {
			header('Location: https://'.SSL_SITE_URL.$_SERVER['REQUEST_URI']); die();
		}
		if ($SSL && $_SERVER['HTTP_HOST'] == NONSSL_SITE_URL) {
			header('Location: https://'.SSL_SITE_URL.$_SERVER['REQUEST_URI']); die();
		}
	}
	if ($_SERVER['HTTP_HOST'] == 'www.m.'.NONSSL_SITE_URL) {
		header('Location: http://m.'.NONSSL_SITE_URL.$_SERVER['REQUEST_URI']); die();
	}
}



$ScriptStartTime = microtime(true); //To track how long a page takes to create
if (!defined('PHP_WINDOWS_VERSION_MAJOR')) {
	$RUsage = getrusage();
	$CPUTimeStart = $RUsage['ru_utime.tv_sec'] * 1000000 + $RUsage['ru_utime.tv_usec'];
}
ob_start(); //Start a buffer, mainly in case there is a mysql error


require(SERVER_ROOT.'/classes/debug.class.php'); //Require the debug class
require(SERVER_ROOT.'/classes/mysql.class.php'); //Require the database wrapper
require(SERVER_ROOT.'/classes/cache.class.php'); //Require the caching class
require(SERVER_ROOT.'/classes/encrypt.class.php'); //Require the encryption class
require(SERVER_ROOT.'/classes/time.class.php'); //Require the time class
require(SERVER_ROOT.'/classes/paranoia.class.php'); //Require the paranoia check_paranoia function
require(SERVER_ROOT.'/classes/regex.php');
require(SERVER_ROOT.'/classes/util.php');

$Debug = new DEBUG;
$Debug->handle_errors();
$Debug->set_flag('Debug constructed');

$DB = new DB_MYSQL;
$Cache = new CACHE($MemcachedServers);
$Enc = new CRYPT;

// Autoload classes.
require(SERVER_ROOT.'/classes/classloader.php');

// Note: G::initialize is called twice.
// This is necessary as the code inbetween (initialization of $LoggedUser) makes use of G::$DB and G::$Cache.
// TODO: remove one of the calls once we're moving everything into that class
G::initialize();

//Begin browser identification

$Browser = UserAgent::browser($_SERVER['HTTP_USER_AGENT']);
$OperatingSystem = UserAgent::operating_system($_SERVER['HTTP_USER_AGENT']);
//$Mobile = UserAgent::mobile($_SERVER['HTTP_USER_AGENT']);
$Mobile = in_array($_SERVER['HTTP_HOST'], array('m.'.NONSSL_SITE_URL, 'm.'.NONSSL_SITE_URL));


$Debug->set_flag('start user handling');

// Get classes
// TODO: Remove these globals, replace by calls into Users
list($Classes, $ClassLevels) = Users::get_classes();

//-- Load user information
// User info is broken up into many sections
// Heavy - Things that the site never has to look at if the user isn't logged in (as opposed to things like the class, donor status, etc)
// Light - Things that appear in format_user
// Stats - Uploaded and downloaded - can be updated by a script if you want super speed
// Session data - Information about the specific session
// Enabled - if the user's enabled or not
// Permissions

if (isset($_COOKIE['session'])) {
	$LoginCookie = $Enc->decrypt($_COOKIE['session']);
}
if (isset($LoginCookie)) {
	list($SessionID, $LoggedUser['ID']) = explode('|~|', $Enc->decrypt($LoginCookie));
	$LoggedUser['ID'] = (int)$LoggedUser['ID'];

	$UserID = $LoggedUser['ID']; //TODO: UserID should not be LoggedUser

	if (!$LoggedUser['ID'] || !$SessionID) {
		logout();
	}

	$UserSessions = $Cache->get_value("users_sessions_$UserID");
	if (!is_array($UserSessions)) {
		$DB->query("
			SELECT
				SessionID,
				Browser,
				OperatingSystem,
				IP,
				LastUpdate
			FROM users_sessions
			WHERE UserID = '$UserID'
				AND Active = 1
			ORDER BY LastUpdate DESC");
		$UserSessions = $DB->to_array('SessionID',MYSQLI_ASSOC);
		$Cache->cache_value("users_sessions_$UserID", $UserSessions, 0);
	}

	if (!array_key_exists($SessionID, $UserSessions)) {
		logout();
	}

	// Check if user is enabled
	$Enabled = $Cache->get_value('enabled_'.$LoggedUser['ID']);
	if ($Enabled === false) {
		$DB->query("
			SELECT Enabled
			FROM users_main
			WHERE ID = '$LoggedUser[ID]'");
		list($Enabled) = $DB->next_record();
		$Cache->cache_value('enabled_'.$LoggedUser['ID'], $Enabled, 0);
	}
	if ($Enabled == 2) {

		logout();
	}

	// Up/Down stats
	$UserStats = $Cache->get_value('user_stats_'.$LoggedUser['ID']);
	if (!is_array($UserStats)) {
		$DB->query("
			SELECT Uploaded AS BytesUploaded, Downloaded AS BytesDownloaded, RequiredRatio
			FROM users_main
			WHERE ID = '$LoggedUser[ID]'");
		$UserStats = $DB->next_record(MYSQLI_ASSOC);
		$Cache->cache_value('user_stats_'.$LoggedUser['ID'], $UserStats, 3600);
	}

	// Get info such as username
	$LightInfo = Users::user_info($LoggedUser['ID']);
	$HeavyInfo = Users::user_heavy_info($LoggedUser['ID']);

	// Create LoggedUser array
	$LoggedUser = array_merge($HeavyInfo, $LightInfo, $UserStats);

	$LoggedUser['RSS_Auth'] = md5($LoggedUser['ID'] . RSS_HASH . $LoggedUser['torrent_pass']);

	// $LoggedUser['RatioWatch'] as a bool to disable things for users on Ratio Watch
	$LoggedUser['RatioWatch'] = (
		$LoggedUser['RatioWatchEnds'] != '0000-00-00 00:00:00'
		&& time() < strtotime($LoggedUser['RatioWatchEnds'])
		&& ($LoggedUser['BytesDownloaded'] * $LoggedUser['RequiredRatio']) > $LoggedUser['BytesUploaded']
	);

	// Load in the permissions
	$LoggedUser['Permissions'] = Permissions::get_permissions_for_user($LoggedUser['ID'], $LoggedUser['CustomPermissions']);
	$LoggedUser['Permissions']['MaxCollages'] += Donations::get_personal_collages($LoggedUser['ID']);

	// Change necessary triggers in external components
	$Cache->CanClear = check_perms('admin_clear_cache');

	// Because we <3 our staff
	if (check_perms('site_disable_ip_history')) {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	}

	// Update LastUpdate every 10 minutes
	if (strtotime($UserSessions[$SessionID]['LastUpdate']) + 600 < time()) {
		$DB->query("
			UPDATE users_main
			SET LastAccess = '".sqltime()."'
			WHERE ID = '$LoggedUser[ID]'");
		$DB->query("
			UPDATE users_sessions
			SET
				IP = '".$_SERVER['REMOTE_ADDR']."',
				Browser = '$Browser',
				OperatingSystem = '$OperatingSystem',
				LastUpdate = '".sqltime()."'
			WHERE UserID = '$LoggedUser[ID]'
				AND SessionID = '".db_string($SessionID)."'");
		$Cache->begin_transaction("users_sessions_$UserID");
		$Cache->delete_row($SessionID);
		$Cache->insert_front($SessionID,array(
				'SessionID' => $SessionID,
				'Browser' => $Browser,
				'OperatingSystem' => $OperatingSystem,
				'IP' => $_SERVER['REMOTE_ADDR'],
				'LastUpdate' => sqltime()
				));
		$Cache->commit_transaction(0);
	}

	// Notifications
	if (isset($LoggedUser['Permissions']['site_torrents_notify'])) {
		$LoggedUser['Notify'] = $Cache->get_value('notify_filters_'.$LoggedUser['ID']);
		if (!is_array($LoggedUser['Notify'])) {
			$DB->query("
				SELECT ID, Label
				FROM users_notify_filters
				WHERE UserID = '$LoggedUser[ID]'");
			$LoggedUser['Notify'] = $DB->to_array('ID');
			$Cache->cache_value('notify_filters_'.$LoggedUser['ID'], $LoggedUser['Notify'], 2592000);
		}
	}

	// We've never had to disable the wiki privs of anyone.
	if ($LoggedUser['DisableWiki']) {
		unset($LoggedUser['Permissions']['site_edit_wiki']);
	}

	// IP changed

	if ($LoggedUser['IP'] != $_SERVER['REMOTE_ADDR'] && !check_perms('site_disable_ip_history')) {

		if (Tools::site_ban_ip($_SERVER['REMOTE_ADDR'])) {
			error('Your IP address has been banned.');
		}

		$CurIP = db_string($LoggedUser['IP']);
		$NewIP = db_string($_SERVER['REMOTE_ADDR']);
		$DB->query("
			UPDATE users_history_ips
			SET EndTime = '".sqltime()."'
			WHERE EndTime IS NULL
				AND UserID = '$LoggedUser[ID]'
				AND IP = '$CurIP'");
		$DB->query("
			INSERT IGNORE INTO users_history_ips
				(UserID, IP, StartTime)
			VALUES
				('$LoggedUser[ID]', '$NewIP', '".sqltime()."')");

		$ipcc = Tools::geoip($NewIP);
		$DB->query("
			UPDATE users_main
			SET IP = '$NewIP', ipcc = '$ipcc'
			WHERE ID = '$LoggedUser[ID]'");
		$Cache->begin_transaction('user_info_heavy_'.$LoggedUser['ID']);
		$Cache->update_row(false, array('IP' => $_SERVER['REMOTE_ADDR']));
		$Cache->commit_transaction(0);


	}


	// Get stylesheets
	$Stylesheets = $Cache->get_value('stylesheets');
	if (!is_array($Stylesheets)) {
		$DB->query('
			SELECT
				ID,
				LOWER(REPLACE(Name, " ", "_")) AS Name,
				Name AS ProperName
			FROM stylesheets');
		$Stylesheets = $DB->to_array('ID', MYSQLI_BOTH);
		$Cache->cache_value('stylesheets', $Stylesheets, 0);
	}

	//A9 TODO: Clean up this messy solution
	$LoggedUser['StyleName'] = $Stylesheets[$LoggedUser['StyleID']]['Name'];

	if (empty($LoggedUser['Username'])) {
		logout(); // Ghost
	}
}
G::initialize();
$Debug->set_flag('end user handling');

$Debug->set_flag('start function definitions');

/**
 * Log out the current session
 */
function logout() {
	global $SessionID;
	setcookie('session', '', time() - 60 * 60 * 24 * 365, '/', '', false);
	setcookie('keeplogged', '', time() - 60 * 60 * 24 * 365, '/', '', false);
	setcookie('session', '', time() - 60 * 60 * 24 * 365, '/', '', false);
	if ($SessionID) {

		G::$DB->query("
			DELETE FROM users_sessions
			WHERE UserID = '" . G::$LoggedUser['ID'] . "'
				AND SessionID = '".db_string($SessionID)."'");

		G::$Cache->begin_transaction('users_sessions_' . G::$LoggedUser['ID']);
		G::$Cache->delete_row($SessionID);
		G::$Cache->commit_transaction(0);
	}
	G::$Cache->delete_value('user_info_' . G::$LoggedUser['ID']);
	G::$Cache->delete_value('user_stats_' . G::$LoggedUser['ID']);
	G::$Cache->delete_value('user_info_heavy_' . G::$LoggedUser['ID']);

	header('Location: login.php');

	die();
}

function enforce_login() {
	global $SessionID;
	if (!$SessionID || !G::$LoggedUser) {
		setcookie('redirect', $_SERVER['REQUEST_URI'], time() + 60 * 30, '/', '', false);
		logout();
	}
}

/**
 * Make sure $_GET['auth'] is the same as the user's authorization key
 * Should be used for any user action that relies solely on GET.
 *
 * @param Are we using ajax?
 * @return authorisation status. Prints an error message to LAB_CHAN on IRC on failure.
 */
function authorize($Ajax = false) {
	if (empty($_REQUEST['auth']) || $_REQUEST['auth'] != G::$LoggedUser['AuthKey']) {
		send_irc("PRIVMSG ".LAB_CHAN." :".G::$LoggedUser['Username']." just failed authorize on ".$_SERVER['REQUEST_URI']." coming from ".$_SERVER['HTTP_REFERER']);
		error('Invalid authorization key. Go back, refresh, and try again.', $Ajax);
		return false;
	}
	return true;
}

$Debug->set_flag('ending function definitions');
//Include /sections/*/index.php
$Document = basename(parse_url($_SERVER['SCRIPT_FILENAME'], PHP_URL_PATH), '.php');
if (!preg_match('/^[a-z0-9]+$/i', $Document)) {
	error(404);
}

$StripPostKeys = array_fill_keys(array('password', 'cur_pass', 'new_pass_1', 'new_pass_2', 'verifypassword', 'confirm_password', 'ChangePassword', 'Password'), true);
$Cache->cache_value('php_' . getmypid(), array(
	'start' => sqltime(),
	'document' => $Document,
	'query' => $_SERVER['QUERY_STRING'],
	'get' => $_GET,
	'post' => array_diff_key($_POST, $StripPostKeys)), 600);
require(SERVER_ROOT.'/sections/'.$Document.'/index.php');
$Debug->set_flag('completed module execution');

/* Required in the absence of session_start() for providing that pages will change
upon hit rather than being browser cached for changing content.

Old versions of Internet Explorer choke when downloading binary files over HTTPS with disabled cache.
Define the following constant in files that handle file downloads */
if (!defined('SKIP_NO_CACHE_HEADERS')) {
	header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
	header('Pragma: no-cache');
}

//Flush to user
ob_end_flush();

$Debug->set_flag('set headers and send to user');

//Attribute profiling
$Debug->profile();
