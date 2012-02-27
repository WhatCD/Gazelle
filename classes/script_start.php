<?

/*-- Script Start Class --------------------------------*/
/*------------------------------------------------------*/
/* This isnt really a class but a way to tie other	  */
/* classes and functions used all over the site to the  */
/* page currently being displayed.					  */
/*------------------------------------------------------*/
/* The code that includes the main php files and		*/
/* generates the page are at the bottom.				*/
/*------------------------------------------------------*/
/********************************************************/
require 'config.php'; //The config contains all site wide configuration information

//Deal with dumbasses
if(isset($_REQUEST['info_hash']) && isset($_REQUEST['peer_id'])) { die('d14:failure reason40:Invalid .torrent, try downloading again.e'); }

require(SERVER_ROOT.'/classes/class_proxies.php');
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && proxyCheck($_SERVER['REMOTE_ADDR'])) {
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

$SSL = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

if (!isset($argv) && !empty($_SERVER['HTTP_HOST'])) { //Skip this block if running from cli or if the browser is old and shitty
	if (!$SSL && $_SERVER['HTTP_HOST'] == 'www.'.NONSSL_SITE_URL) { header('Location: http://'.NONSSL_SITE_URL.$_SERVER['REQUEST_URI']); die(); }
	if ($SSL && $_SERVER['HTTP_HOST'] == 'www.'.NONSSL_SITE_URL) { header('Location: https://'.SSL_SITE_URL.$_SERVER['REQUEST_URI']); die(); }
	if(SSL_SITE_URL != NONSSL_SITE_URL) {
		if (!$SSL && $_SERVER['HTTP_HOST'] == SSL_SITE_URL) { header('Location: https://'.SSL_SITE_URL.$_SERVER['REQUEST_URI']); die(); }
		if ($SSL && $_SERVER['HTTP_HOST'] == NONSSL_SITE_URL) { header('Location: https://'.SSL_SITE_URL.$_SERVER['REQUEST_URI']); die(); }
	}
	if($_SERVER['HTTP_HOST'] == 'www.m.'.NONSSL_SITE_URL) { header('Location: http://m.'.NONSSL_SITE_URL.$_SERVER['REQUEST_URI']); die(); }
}



$ScriptStartTime=microtime(true); //To track how long a page takes to create

ob_start(); //Start a buffer, mainly in case there is a mysql error


require(SERVER_ROOT.'/classes/class_debug.php'); //Require the debug class
require(SERVER_ROOT.'/classes/class_mysql.php'); //Require the database wrapper
require(SERVER_ROOT.'/classes/class_cache.php'); //Require the caching class
require(SERVER_ROOT.'/classes/class_encrypt.php'); //Require the encryption class
require(SERVER_ROOT.'/classes/class_useragent.php'); //Require the useragent class
require(SERVER_ROOT.'/classes/class_time.php'); //Require the time class
require(SERVER_ROOT.'/classes/class_search.php'); //Require the searching class
require(SERVER_ROOT.'/classes/class_paranoia.php'); //Require the paranoia check_paranoia function
require(SERVER_ROOT.'/classes/regex.php');

$Debug = new DEBUG;
$Debug->handle_errors();
$Debug->set_flag('Debug constructed');

$DB = new DB_MYSQL;
$Cache = new CACHE;
$Enc = new CRYPT;
$UA = new USER_AGENT;
$SS = new SPHINX_SEARCH;


//Begin browser identification

$Browser = $UA->browser($_SERVER['HTTP_USER_AGENT']);
$OperatingSystem = $UA->operating_system($_SERVER['HTTP_USER_AGENT']);
//$Mobile = $UA->mobile($_SERVER['HTTP_USER_AGENT']);
$Mobile = in_array($_SERVER['HTTP_HOST'], array('m.'.NONSSL_SITE_URL, 'm.'.NONSSL_SITE_URL));


$Debug->set_flag('start user handling');

// Get permissions
list($Classes, $ClassLevels) = $Cache->get_value('classes');
if(!$Classes || !$ClassLevels) {
	$DB->query('SELECT ID, Name, Level FROM permissions ORDER BY Level');
	$Classes = $DB->to_array('ID');
	$ClassLevels = $DB->to_array('Level');
	$Cache->cache_value('classes', array($Classes, $ClassLevels), 0);
}
$Debug->set_flag('Loaded permissions');

//-----------------------------------------------------------------------------------
/////////////////////////////////////////////////////////////////////////////////////
//-- Load user information ----------------------------------------------------------
// User info is broken up into many sections
// Heavy - Things that the site never has to look at if the user isn't logged in (as opposed to things like the class, donor status, etc)
// Light - Things that appear in format_user
// Stats - Uploaded and downloaded - can be updated by a script if you want super speed
// Session data - Information about the specific session
// Enabled - if the user's enabled or not
// Permissions

if (isset($_COOKIE['session'])) { $LoginCookie=$Enc->decrypt($_COOKIE['session']); }
if(isset($LoginCookie)) {
	list($SessionID, $LoggedUser['ID'])=explode("|~|",$Enc->decrypt($LoginCookie));
	$LoggedUser['ID'] = (int)$LoggedUser['ID'];

	$UserID=$LoggedUser['ID']; //TODO: UserID should not be LoggedUser

	if (!$LoggedUser['ID'] || !$SessionID) {
		logout();
	}
	
	$UserSessions = $Cache->get_value('users_sessions_'.$UserID);
	if(!is_array($UserSessions)) {
		$DB->query("SELECT
			SessionID,
			Browser,
			OperatingSystem,
			IP,
			LastUpdate
			FROM users_sessions
			WHERE UserID='$UserID'
			AND Active = 1
			ORDER BY LastUpdate DESC");
		$UserSessions = $DB->to_array('SessionID',MYSQLI_ASSOC);
		$Cache->cache_value('users_sessions_'.$UserID, $UserSessions, 0);
	}

	if (!array_key_exists($SessionID,$UserSessions)) {
		logout();
	}
	
	// Check if user is enabled
	$Enabled = $Cache->get_value('enabled_'.$LoggedUser['ID']);
	if($Enabled === false) {
		$DB->query("SELECT Enabled FROM users_main WHERE ID='$LoggedUser[ID]'");
		list($Enabled)=$DB->next_record();
		$Cache->cache_value('enabled_'.$LoggedUser['ID'], $Enabled, 0);
	}
	if ($Enabled==2) {
		
		logout();
	}

	

	// Up/Down stats
	$UserStats = $Cache->get_value('user_stats_'.$LoggedUser['ID']);
	if(!is_array($UserStats)) {
		$DB->query("SELECT Uploaded AS BytesUploaded, Downloaded AS BytesDownloaded, RequiredRatio FROM users_main WHERE ID='$LoggedUser[ID]'");
		$UserStats = $DB->next_record(MYSQLI_ASSOC);
		$Cache->cache_value('user_stats_'.$LoggedUser['ID'], $UserStats, 3600);
	}

	// Get info such as username
	$LightInfo = user_info($LoggedUser['ID']);
	$HeavyInfo = user_heavy_info($LoggedUser['ID']);

	// Get user permissions
	$Permissions = get_permissions($LightInfo['PermissionID']);

	// Create LoggedUser array
	$LoggedUser = array_merge($HeavyInfo, $LightInfo, $Permissions, $UserStats);

	$LoggedUser['RSS_Auth']=md5($LoggedUser['ID'].RSS_HASH.$LoggedUser['torrent_pass']);

	//$LoggedUser['RatioWatch'] as a bool to disable things for users on Ratio Watch
	$LoggedUser['RatioWatch'] = (
		$LoggedUser['RatioWatchEnds'] != '0000-00-00 00:00:00' &&
		time() < strtotime($LoggedUser['RatioWatchEnds']) &&
		($LoggedUser['BytesDownloaded']*$LoggedUser['RequiredRatio'])>$LoggedUser['BytesUploaded']
	);
	if(!isset($LoggedUser['ID'])) {
		$Debug->log_var($LightInfo, 'LightInfo');
		$Debug->log_var($HeavyInfo, 'HeavyInfo');
		$Debug->log_var($Permissions, 'Permissions');
		$Debug->log_var($UserStats, 'UserStats');
	}

	//Load in the permissions
	$LoggedUser['Permissions'] = get_permissions_for_user($LoggedUser['ID'], $LoggedUser['CustomPermissions']);
	
	//Change necessary triggers in external components
	$Cache->CanClear = check_perms('admin_clear_cache');
	
	// Because we <3 our staff
	if (check_perms('site_disable_ip_history')) { $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; }

	// Update LastUpdate every 10 minutes
	if(strtotime($UserSessions[$SessionID]['LastUpdate'])+600<time()) {
		$DB->query("UPDATE users_main SET LastAccess='".sqltime()."' WHERE ID='$LoggedUser[ID]'");
		$DB->query("UPDATE users_sessions SET IP='".$_SERVER['REMOTE_ADDR']."', Browser='".$Browser."', OperatingSystem='".$OperatingSystem."', LastUpdate='".sqltime()."' WHERE UserID='$LoggedUser[ID]' AND SessionID='".db_string($SessionID)."'");
		$Cache->begin_transaction('users_sessions_'.$UserID);
		$Cache->delete_row($SessionID);
		$Cache->insert_front($SessionID,array(
				'SessionID'=>$SessionID,
				'Browser'=>$Browser,
				'OperatingSystem'=>$OperatingSystem,
				'IP'=>$_SERVER['REMOTE_ADDR'],
				'LastUpdate'=>sqltime()
				));
		$Cache->commit_transaction(0);
	}
	
	// Notifications
	if(isset($LoggedUser['Permissions']['site_torrents_notify'])) {
		$LoggedUser['Notify'] = $Cache->get_value('notify_filters_'.$LoggedUser['ID']);
		if(!is_array($LoggedUser['Notify'])) {
			$DB->query("SELECT ID, Label FROM users_notify_filters WHERE UserID='$LoggedUser[ID]'");
			$LoggedUser['Notify'] = $DB->to_array('ID');
			$Cache->cache_value('notify_filters_'.$LoggedUser['ID'], $LoggedUser['Notify'], 2592000);
		}
	}
	
	// We've never had to disable the wiki privs of anyone.
	if ($LoggedUser['DisableWiki']) {
		unset($LoggedUser['Permissions']['site_edit_wiki']);
	}
	
	// IP changed
	if($LoggedUser['IP'] != $_SERVER['REMOTE_ADDR'] && !check_perms('site_disable_ip_history')) {
		if(site_ban_ip($_SERVER['REMOTE_ADDR'])) {
			error('Your IP has been banned.');
		}

		$CurIP = db_string($LoggedUser['IP']);
		$NewIP = db_string($_SERVER['REMOTE_ADDR']);
		$DB->query("UPDATE users_history_ips SET
				EndTime='".sqltime()."'
				WHERE EndTime IS NULL
				AND UserID='$LoggedUser[ID]'
				AND IP='$CurIP'");
		$DB->query("INSERT IGNORE INTO users_history_ips
				(UserID, IP, StartTime) VALUES
				('$LoggedUser[ID]', '$NewIP', '".sqltime()."')");

		$ipcc = geoip($NewIP);
		$DB->query("UPDATE users_main SET IP='$NewIP', ipcc='".$ipcc."' WHERE ID='$LoggedUser[ID]'");
		$Cache->begin_transaction('user_info_heavy_'.$LoggedUser['ID']);
		$Cache->update_row(false, array('IP' => $_SERVER['REMOTE_ADDR']));
		$Cache->commit_transaction(0);

		
	}
	
	
	
	// Get stylesheets
	$Stylesheets = $Cache->get_value('stylesheets');
	if (!is_array($Stylesheets)) {
		$DB->query('SELECT ID, LOWER(REPLACE(Name," ","_")) AS Name, Name AS ProperName FROM stylesheets');
		$Stylesheets = $DB->to_array('ID', MYSQLI_BOTH);
		$Cache->cache_value('stylesheets', $Stylesheets, 600);
	}

	//A9 TODO: Clean up this messy solution
	$LoggedUser['StyleName']=$Stylesheets[$LoggedUser['StyleID']]['Name'];
	
	if(empty($LoggedUser['Username'])) {
		logout(); // Ghost
	}
}


$Debug->set_flag('end user handling');

$Debug->set_flag('start function definitions');

// Get cached user info, is used for the user loading the page and usernames all over the site
function user_info($UserID) {
	global $DB, $Cache;
	$UserInfo = $Cache->get_value('user_info_'.$UserID);
	// the !isset($UserInfo['Paranoia']) can be removed after a transition period
	if(empty($UserInfo) || empty($UserInfo['ID']) || !isset($UserInfo['Paranoia'])) {


		$DB->query("SELECT
			m.ID,
			m.Username,
			m.PermissionID,
			m.Paranoia,
			i.Artist,
			i.Donor,
			i.Warned,
			i.Avatar,
			m.Enabled,
			m.Title,
			i.CatchupTime,
			m.Visible
			FROM users_main AS m
			INNER JOIN users_info AS i ON i.UserID=m.ID
			WHERE m.ID='$UserID'");
		if($DB->record_count() == 0) { // Deleted user, maybe?
			$UserInfo = array('ID'=>'','Username'=>'','PermissionID'=>0,'Artist'=>false,'Donor'=>false,'Warned'=>'0000-00-00 00:00:00','Avatar'=>'','Enabled'=>0,'Title'=>'', 'CatchupTime'=>0, 'Visible'=>'1');

		} else {
			$UserInfo = $DB->next_record(MYSQLI_ASSOC, array('Paranoia', 'Title'));
			$UserInfo['CatchupTime'] = strtotime($UserInfo['CatchupTime']);
			$UserInfo['Paranoia'] = unserialize($UserInfo['Paranoia']);
			if($UserInfo['Paranoia'] === false) {
				$UserInfo['Paranoia'] = array();
			}
		}
		$Cache->cache_value('user_info_'.$UserID, $UserInfo, 2592000);
	}
	if(strtotime($UserInfo['Warned']) < time()) {
		$UserInfo['Warned'] = '0000-00-00 00:00:00';
		$Cache->cache_value('user_info_'.$UserID, $UserInfo, 2592000);
	}
	
	// Image proxy
	if(check_perms('site_proxy_images') && !empty($UserInfo['Avatar'])) {
		$UserInfo['Avatar'] = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?c=1&amp;avatar='.$UserID.'&amp;i='.urlencode($UserInfo['Avatar']);
	}
	return $UserInfo;
}

// Only used for current user
function user_heavy_info($UserID) {
	global $DB, $Cache;
	$HeavyInfo = $Cache->get_value('user_info_heavy_'.$UserID);

	if(empty($HeavyInfo)) {

		$DB->query("SELECT
			m.Invites,
			m.torrent_pass,
			m.IP,
			m.CustomPermissions,
			m.can_leech AS CanLeech,
			i.AuthKey,
			i.RatioWatchEnds,
			i.RatioWatchDownload,
			i.StyleID,
			i.StyleURL,
			i.DisableInvites,
			i.DisablePosting,
			i.DisableUpload,
			i.DisableWiki,
			i.DisableAvatar,
			i.DisablePM,
			i.DisableRequests,
			i.SiteOptions,
			i.DownloadAlt,
			i.LastReadNews,
			i.RestrictedForums,
			i.PermittedForums,
			m.FLTokens
			FROM users_main AS m
			INNER JOIN users_info AS i ON i.UserID=m.ID
			WHERE m.ID='$UserID'");
		$HeavyInfo = $DB->next_record(MYSQLI_ASSOC, array('CustomPermissions', 'SiteOptions'));

		if (!empty($HeavyInfo['CustomPermissions'])) {
			$HeavyInfo['CustomPermissions'] = unserialize($HeavyInfo['CustomPermissions']);
		} else {
			$HeavyInfo['CustomPermissions'] = array();
		}

		if (!empty($HeavyInfo['RestrictedForums'])) {
			$RestrictedForums = explode(',', $HeavyInfo['RestrictedForums']);
		} else {
			$RestrictedForums = array();
		}
		unset($HeavyInfo['RestrictedForums']);
		if (!empty($HeavyInfo['PermittedForums'])) {
			$PermittedForums = explode(',', $HeavyInfo['PermittedForums']);
		} else {
			$PermittedForums = array();
		}
		unset($HeavyInfo['PermittedForums']);
		if (!empty($PermittedForums) || !empty($RestrictedForums)) {
			$HeavyInfo['CustomForums'] = array();
			foreach ($RestrictedForums as $ForumID) {
				$HeavyInfo['CustomForums'][$ForumID] = 0;
			}
			foreach ($PermittedForums as $ForumID) {
				$HeavyInfo['CustomForums'][$ForumID] = 1;
			}
		} else {
			$HeavyInfo['CustomForums'] = null;
		}
		
		$HeavyInfo['SiteOptions'] = unserialize($HeavyInfo['SiteOptions']);
		if(!empty($HeavyInfo['SiteOptions'])) {
			$HeavyInfo = array_merge($HeavyInfo, $HeavyInfo['SiteOptions']);
		}
		unset($HeavyInfo['SiteOptions']);

		$Cache->cache_value('user_info_heavy_'.$UserID, $HeavyInfo, 0);
	}
	return $HeavyInfo;
}

function update_site_options($UserID, $NewOptions) {
	if(!is_number($UserID)) {
		error(0);
	}
	if(empty($NewOptions)) {
		return false;
	}
	global $DB, $Cache, $LoggedUser;

	// Get SiteOptions
	$DB->query("SELECT SiteOptions FROM users_info WHERE UserID = $UserID");
	list($SiteOptions) = $DB->next_record(MYSQLI_NUM,false);
	$SiteOptions = unserialize($SiteOptions);

	// Get HeavyInfo
	$HeavyInfo = user_heavy_info($UserID);

	// Insert new/replace old options
	$SiteOptions = array_merge($SiteOptions, $NewOptions);
	$HeavyInfo = array_merge($HeavyInfo, $NewOptions);

	// Update DB
	$DB->query("UPDATE users_info SET SiteOptions = '".db_string(serialize($SiteOptions))."' WHERE UserID = $UserID");

	// Update cache
	$Cache->cache_value('user_info_heavy_'.$UserID, $HeavyInfo, 0);

	// Update $LoggedUser if the options are changed for the current
	if($LoggedUser['ID'] == $UserID) {
		$LoggedUser = array_merge($LoggedUser, $NewOptions);
		$LoggedUser['ID'] = $UserID; // We don't want to allow userid switching
	}
}

function get_permissions($PermissionID) {
	global $DB, $Cache;
	$Permission = $Cache->get_value('perm_'.$PermissionID);
	if(empty($Permission)) {
		$DB->query("SELECT p.Level AS Class, p.Values as Permissions FROM permissions AS p WHERE ID='$PermissionID'");
		$Permission = $DB->next_record(MYSQLI_ASSOC, array('Permissions'));
		$Permission['Permissions'] = unserialize($Permission['Permissions']);
		$Cache->cache_value('perm_'.$PermissionID, $Permission, 2592000);
	}
	return $Permission;
}

function get_permissions_for_user($UserID, $CustomPermissions = false) {
	global $DB;

	$UserInfo = user_info($UserID);
	
	if ($CustomPermissions === false) {
		$DB->query('SELECT um.CustomPermissions FROM users_main AS um WHERE um.ID = '.((int)$UserID));
	
		list($CustomPermissions) = $DB->next_record(MYSQLI_NUM, false);
	}
	
	if (!empty($CustomPermissions) && !is_array($CustomPermissions)) {
		$CustomPermissions = unserialize($CustomPermissions);
	}

	$Permissions = get_permissions($UserInfo['PermissionID']);
	

	// Manage 'special' inherited permissions
	if($UserInfo['Artist']) {
		$ArtistPerms = get_permissions(ARTIST);
	} else {
		$ArtistPerms = array('Permissions' => array());
	}

	if($UserInfo['Donor']) {
		$DonorPerms = get_permissions(DONOR);
	} else {
		$DonorPerms = array('Permissions' => array());
	}

	if(!empty($CustomPermissions)) {
		$CustomPerms = $CustomPermissions;
	} else {
		$CustomPerms = array();
	}

	$MaxCollages = $Permissions['Permissions']['MaxCollages'] + $DonorPerms['Permissions']['MaxCollages'] + $ArtistPerms['Permissions']['MaxCollages'] + $CustomPerms['MaxCollages'];
	
	//Combine the permissions
	return array_merge($Permissions['Permissions'], $DonorPerms['Permissions'], $ArtistPerms['Permissions'], $CustomPerms, array('MaxCollages' => $MaxCollages));
}

// This function is slow. Don't call it unless somebody's logging in.
function site_ban_ip($IP) {
	global $DB, $Cache;
	$IPNum = ip2unsigned($IP);
	$IPBans = $Cache->get_value('ip_bans');
	if(!is_array($IPBans)) {
		$DB->query("SELECT ID, FromIP, ToIP FROM ip_bans");
		$IPBans = $DB->to_array(0, MYSQLI_NUM);
		$Cache->cache_value('ip_bans', $IPBans, 0);
	}
	foreach($IPBans as $Index => $IPBan) {
		list($ID, $FromIP, $ToIP) = $IPBan;
		if($IPNum >= $FromIP && $IPNum <= $ToIP) {
			return true;
		}
	}

	return false;
}



function ip2unsigned($IP) {
	return sprintf("%u", ip2long($IP));
}

// Geolocate an IP address. Two functions - a database one, and a dns one.
function geoip($IP) {
	static $IPs = array();
	if (isset($IPs[$IP])) {
		return $IPs[$IP];
	}
	$Long = ip2unsigned($IP);
	if(!$Long || $Long == 2130706433) { // No need to check cc for 127.0.0.1
		return false;
	}
	global $DB;
	$DB->query("SELECT EndIP,Code FROM geoip_country WHERE $Long >= StartIP ORDER BY StartIP DESC LIMIT 1");
	if((!list($EndIP,$Country) = $DB->next_record()) || $EndIP < $Long) {
		$Country = '?';
	}
	$IPs[$IP] = $Country;
	return $Country;
}

function old_geoip($IP) {
	static $Countries = array();
	if(empty($Countries[$IP])) {
		$Country = 0;
		// Reverse IP, so 127.0.0.1 becomes 1.0.0.127
		$ReverseIP = implode('.', array_reverse(explode('.', $IP)));
		$TestHost = $ReverseIP.'.country.netop.org';
		$Return = dns_get_record($TestHost, DNS_TXT);
		if (!empty($Return)) {
			$Country = $Return[0]['txt'];
		}
		if(!$Country) {
			$Return = gethostbyaddr($IP);
			$Return = explode('.',$Return);
			$Return = array_pop($Return);
			if(strlen($Return) == 2 && !is_number($Return)) {
				$Country = strtoupper($Return);
			} else {
				$Country = '?';
			}
		}
		if($Country == 'UK') { $Country = 'GB'; }
		$Countries[$IP] = $Country;
	}
	return $Countries[$IP];
}

function gethostbyip($ip)
{
	$testar = explode('.',$ip);
	if (count($testar)!=4) { 
		return $ip;
	}
	for ($i=0;$i<4;++$i) {
		if (!is_numeric($testar[$i])) {
			return $ip;
		}
	}
	
	$host = `host -W 1 $ip`;
	return (($host ? end ( explode (' ', $host)) : $ip));
}


function get_host($IP) {
	static $ID = 0;
	++$ID;
	return '<span id="host_'.$ID.'">Resolving host...<script type="text/javascript">ajax.get(\'tools.php?action=get_host&ip='.$IP.'\',function(host){$(\'#host_'.$ID.'\').raw().innerHTML=host;});</script></span>';
}

function lookup_ip($IP) {
	//TODO: use the $Cache
	$Output = explode(' ',shell_exec('host -W 1 '.escapeshellarg($IP)));
	if(count($Output) == 1 && empty($Output[0])) {
		//No output at all implies the command failed
		return '';
	}

	if(count($Output) != 5) {
		return false;
	} else {
		return $Output[4];
	}
}

function get_cc($IP) {
	static $ID = 0;
	++$ID;
	return '<span id="cc_'.$ID.'">Resolving CC...<script type="text/javascript">ajax.get(\'tools.php?action=get_cc&ip='.$IP.'\',function(cc){$(\'#cc_'.$ID.'\').raw().innerHTML=cc;});</script></span>';
}

function display_ip($IP) {
	$Line = display_str($IP).' ('.get_cc($IP).') ';
	$Line .= '[<a href="user.php?action=search&amp;ip_history=on&amp;ip='.display_str($IP).'&amp;matchtype=strict" title="Search">S</a>]';
	
	return $Line;
}

function logout() {
	global $SessionID, $LoggedUser, $DB, $Cache;
	setcookie('session','',time()-60*60*24*365,'/','',false);
	setcookie('keeplogged','',time()-60*60*24*365,'/','',false);
	setcookie('session','',time()-60*60*24*365,'/','',false);
	if($SessionID) {
		
		
		$DB->query("DELETE FROM users_sessions WHERE UserID='$LoggedUser[ID]' AND SessionID='".db_string($SessionID)."'");
		
		$Cache->begin_transaction('users_sessions_'.$LoggedUser['ID']);
		$Cache->delete_row($SessionID);
		$Cache->commit_transaction(0);
	}
	$Cache->delete_value('user_info_'.$LoggedUser['ID']);
	$Cache->delete_value('user_stats_'.$LoggedUser['ID']);
	$Cache->delete_value('user_info_heavy_'.$LoggedUser['ID']);

	header('Location: login.php');
	
	die();
}

function enforce_login() {
	global $SessionID, $LoggedUser;
	if (!$SessionID || !$LoggedUser) {
		setcookie('redirect',$_SERVER['REQUEST_URI'],time()+60*30,'/','',false);
		logout();
	}
}

// Make sure $_GET['auth'] is the same as the user's authorization key
// Should be used for any user action that relies solely on GET.
function authorize($Ajax = false) {
	global $LoggedUser;
	if(empty($_REQUEST['auth']) || $_REQUEST['auth'] != $LoggedUser['AuthKey']) {
		send_irc("PRIVMSG ".LAB_CHAN." :".$LoggedUser['Username']." just failed authorize on ".$_SERVER['REQUEST_URI']." coming from ".$_SERVER['HTTP_REFERER']);
		error('Invalid authorization key. Go back, refresh, and try again.', $Ajax);
		return false;
	}
	return true;
}

// This function is to include the header file on a page.
// $JSIncludes is a comma separated list of js files to be inclides on
// the page, ONLY PUT THE RELATIVE LOCATION WITHOUT .js
// ex: 'somefile,somdire/somefile'

function show_header($PageTitle='',$JSIncludes='') {
	global $Document, $Cache, $DB, $LoggedUser, $Mobile, $Classes;

	if($PageTitle!='') { $PageTitle.=' :: '; }
	$PageTitle .= SITE_NAME;

	if(!is_array($LoggedUser)) {
		require(SERVER_ROOT.'/design/publicheader.php');
	} else {
		require(SERVER_ROOT.'/design/privateheader.php');
	}
}

/*-- show_footer function ------------------------------------------------*/
/*------------------------------------------------------------------------*/
/* This function is to include the footer file on a page.				 */
/* $Options is an optional array that you can pass information to the	 */
/*  header through as well as setup certain limitations				   */
/*  Here is a list of parameters that work in the $Options array:		 */
/*  ['disclaimer']	= [boolean]		Displays the disclaimer in the footer */
/*								  Default is false					  */
/**************************************************************************/
function show_footer($Options=array()) {
	global $ScriptStartTime, $LoggedUser, $Cache, $DB, $SessionID, $UserSessions, $Debug, $Time;
	if (!is_array($LoggedUser)) { require(SERVER_ROOT.'/design/publicfooter.php'); }
	else { require(SERVER_ROOT.'/design/privatefooter.php'); }
}

function cut_string($Str,$Length,$Hard=0,$ShowDots=1) {
	if (strlen($Str)>$Length) {
		if ($Hard==0) {
			// Not hard, cut at closest word
			$CutDesc=substr($Str,0,$Length);
			$DescArr=explode(' ',$CutDesc);
			$DescArr=array_slice($DescArr,0,count($DescArr)-1);
			$CutDesc=implode($DescArr,' ');
			if ($ShowDots==1) { $CutDesc.='...'; }
		} else {
			$CutDesc=substr($Str,0,$Length);
			if ($ShowDots==1) { $CutDesc.='...'; }
		}
		return $CutDesc;
	} else {
		return $Str;
	}
}

function get_ratio_color($Ratio) {
	if ($Ratio < 0.1) { return 'r00'; }
	if ($Ratio < 0.2) { return 'r01'; }
	if ($Ratio < 0.3) { return 'r02'; }
	if ($Ratio < 0.4) { return 'r03'; }
	if ($Ratio < 0.5) { return 'r04'; }
	if ($Ratio < 0.6) { return 'r05'; }
	if ($Ratio < 0.7) { return 'r06'; }
	if ($Ratio < 0.8) { return 'r07'; }
	if ($Ratio < 0.9) { return 'r08'; }
	if ($Ratio < 1) { return 'r09'; }
	if ($Ratio < 2) { return 'r10'; }
	if ($Ratio < 5) { return 'r20'; }
	return 'r50';
}

function ratio($Dividend, $Divisor, $Color = true) {
	if($Divisor == 0 && $Dividend == 0) {
		return '--';
	} elseif($Divisor == 0) {
		return '<span class="r99">∞</span>';
	}
	$Ratio = number_format(max($Dividend/$Divisor-0.005,0), 2); //Subtract .005 to floor to 2 decimals
	if($Color) {
		$Class = get_ratio_color($Ratio);
		if($Class) {
			$Ratio = '<span class="'.$Class.'">'.$Ratio.'</span>';
		}
	}
	return $Ratio;

}

function get_url($Exclude = false) {
	if($Exclude !== false) {
		$QueryItems = array();
		parse_str($_SERVER['QUERY_STRING'], $QueryItems);

		foreach($QueryItems AS $Key => $Val) {
			if(!in_array(strtolower($Key),$Exclude)) {
				$Query[$Key] = $Val;
			}
		}

		if(empty($Query)) {
			return;
		}
		return display_str(http_build_query($Query));
	} else {
		return display_str($_SERVER['QUERY_STRING']);
	}
}

/**
 * Finds what page we're on and gives it to us, as well as the LIMIT clause for SQL
 * Takes in $_GET['page'] as an additional input
 *
 * @param $PerPage Results to show per page
 *
 * @param $DefaultResult Optional, which result's page we want if no page is specified
 * If this parameter is not specified, we will default to page 1
 *
 * @return array(int,string) What page we are on, and what to use in the LIMIT section of a query
 * i.e. "SELECT [...] LIMIT $Limit;"
 */
function page_limit($PerPage, $DefaultResult = 1) {
	if(!isset($_GET['page'])) {
		$Page = ceil($DefaultResult/$PerPage);
		if($Page == 0) $Page = 1;
		$Limit=$PerPage;
	} else {
		if(!is_number($_GET['page'])) {
			error(0);
		}
		$Page = $_GET['page'];
		if ($Page == 0) { $Page = 1; }
		$Limit=$PerPage*$_GET['page']-$PerPage . ', ' . $PerPage;
	}
	return array($Page,$Limit);
}

// For data stored in memcached catalogues (giant arrays), eg. forum threads
function catalogue_limit($Page,$PerPage,$CatalogueSize=500) {
	$CatalogueID = floor(($PerPage*$Page-$PerPage)/$CatalogueSize);;
	$CatalogueLimit = ($CatalogueID*$CatalogueSize).', '.$CatalogueSize;
	return array($CatalogueID,$CatalogueLimit);
}

function catalogue_select($Catalogue,$Page,$PerPage,$CatalogueSize=500) {
	return array_slice($Catalogue,(($PerPage*$Page-$PerPage)%$CatalogueSize),$PerPage,true);
}

function get_pages($StartPage,$TotalRecords,$ItemsPerPage,$ShowPages=11,$Anchor='') {
	global $Document, $Method, $Mobile;
	$Location = $Document.'.php';
	/*-- Get pages ---------------------------------------------------------------//
	This function returns a page list, given certain information about the pages.

	Explanation of arguments:
	* $StartPage: The current record the page you're on starts with.
		eg. if you're on page 2 of a forum thread with 25 posts per page, $StartPage is 25.
		If you're on page 1, $StartPage is 0.
	* $TotalRecords: The total number of records in the result set.
		eg. if you're on a forum thread with 152 posts, $TotalRecords is 152.
	* $ItemsPerPage: Self-explanatory. The number of records shown on each page
		eg. if there are 25 posts per forum page, $ItemsPerPage is 25.
	$ShowPages: The number of page links that are shown.
		eg. If there are 20 pages that exist, but $ShowPages is only 11, only 11 links will be shown.
	//----------------------------------------------------------------------------*/
	$StartPage=ceil($StartPage);
	if ($StartPage==0) { $StartPage=1; }
	$TotalPages = 0;
	if ($TotalRecords>0) {
		if ($StartPage>ceil($TotalRecords/$ItemsPerPage)) { $StartPage=ceil($TotalRecords/$ItemsPerPage); }

		$ShowPages--;
		$TotalPages=ceil($TotalRecords/$ItemsPerPage);

		if ($TotalPages>$ShowPages) {
			$StartPosition=$StartPage-round($ShowPages/2);

			if ($StartPosition<=0) {
				$StartPosition=1;
			} else {
				if ($StartPosition>=($TotalPages-$ShowPages)) {
					$StartPosition=$TotalPages-$ShowPages;
				}
			}

			$StopPage=$ShowPages+$StartPosition;

		} else {
			$StopPage=$TotalPages;
			$StartPosition=1;
		}

		if ($StartPosition<1) { $StartPosition=1; }

		$QueryString = get_url(array('page','post'));
		if($QueryString != '') { $QueryString = '&amp;'.$QueryString; }
		
		$Pages = '';

		if ($StartPage>1) {
			$Pages.='<a href="'.$Location.'?page=1'.$QueryString.$Anchor.'"><strong>&lt;&lt; First</strong></a> ';
			$Pages.='<a href="'.$Location.'?page='.($StartPage-1).$QueryString.$Anchor.'" class="pager_prev"><strong>&lt; Prev</strong></a> | ';
		}
		//End change
		
		if (!$Mobile) {
			for ($i=$StartPosition; $i<=$StopPage; $i++) {
				//if ($i!=$StartPage) { $Pages.='<a href="'.$Location.'?page='.$i.$QueryString.'">'; }
				if ($i!=$StartPage) { $Pages.='<a href="'.$Location.'?page='.$i.$QueryString.$Anchor.'">'; }
				$Pages.="<strong>";
				if($i*$ItemsPerPage>$TotalRecords) {
					$Pages.=((($i-1)*$ItemsPerPage)+1).'-'.($TotalRecords);
				} else {
					$Pages.=((($i-1)*$ItemsPerPage)+1).'-'.($i*$ItemsPerPage);
				}

				$Pages.="</strong>";
				if ($i!=$StartPage) { $Pages.='</a>'; }
				if ($i<$StopPage) { $Pages.=" | "; }
			}
		} else {
			$Pages .= $StartPage;
		}

		if ($StartPage<$TotalPages) {
			$Pages.=' | <a href="'.$Location.'?page='.($StartPage+1).$QueryString.$Anchor.'" class="pager_next"><strong>Next &gt;</strong></a> ';
			$Pages.='<a href="'.$Location.'?page='.$TotalPages.$QueryString.$Anchor.'"><strong> Last &gt;&gt;</strong></a>';
		}
		
	}
	
	if ($TotalPages>1) { return $Pages; }
	
}

function send_email($To,$Subject,$Body,$From='noreply',$ContentType='text/plain') {
	$Headers='MIME-Version: 1.0'."\r\n";
	$Headers.='Content-type: '.$ContentType.'; charset=iso-8859-1'."\r\n";
	$Headers.='From: '.SITE_NAME.' <'.$From.'@'.NONSSL_SITE_URL.'>'."\r\n";
	$Headers.='Reply-To: '.$From.'@'.NONSSL_SITE_URL."\r\n";
	$Headers.='X-Mailer: Project Gazelle'."\r\n";
	$Headers.='Message-Id: <'.make_secret().'@'.NONSSL_SITE_URL.">\r\n";
	$Headers.='X-Priority: 3'."\r\n";
	mail($To,$Subject,$Body,$Headers,"-f ".$From."@".NONSSL_SITE_URL);
}

function get_size($Size, $Levels = 2) {
	$Units = array(' B',' KB',' MB',' GB',' TB',' PB',' EB',' ZB',' YB');
	$Size = (double) $Size;
	for($Steps = 0; abs($Size) >= 1024; $Size /= 1024, $Steps++) {}
	if(func_num_args() == 1 && $Steps >= 4) {
		$Levels++;
	}
	return number_format($Size,$Levels).$Units[$Steps];
}

function get_bytes($Size) {
	list($Value,$Unit) = sscanf($Size, "%f%s");
	$Unit = ltrim($Unit);
	if(empty($Unit)) {
		return $Value ? round($Value) : 0;
	}
	switch(strtolower($Unit[0])) {
		case 'k': return round($Value * 1024);
		case 'm': return round($Value * 1048576);
		case 'g': return round($Value * 1073741824);
		case 't': return round($Value * 1099511627776);
		default: return 0;
	}
}

function human_format($Number) {
	$Steps = 0;
	while($Number>=1000) {
		$Steps++;
		$Number=$Number/1000;
	}
	switch ($Steps) {
		case 0: return round($Number); break;
		case 1: return round($Number,2).'k'; break;
		case 2: return round($Number,2).'M'; break;
		case 3: return round($Number,2).'G'; break;
		case 4: return round($Number,2).'T'; break;
		case 5: return round($Number,2).'P'; break;
		default:
			return round($Number,2).'E + '.$Steps*3;
	}
}

function is_number($Str) {
	$Return = true;
	if ($Str < 0) { $Return = false; }
	// We're converting input to a int, then string and comparing to original
	$Return = ($Str == strval(intval($Str)) ? true : false);
	return $Return;
}

function file_string($EscapeStr) {
	return str_replace(array('"','*','/',':','<','>','?','\\','|'), '', $EscapeStr);
}

// This is preferable to htmlspecialchars because it doesn't screw up upon a double escape
function display_str($Str) {
	if ($Str === NULL || $Str === FALSE || is_array($Str)) {
		return '';
	}
	if ($Str!='' && !is_number($Str)) {
		$Str=make_utf8($Str);
		$Str=mb_convert_encoding($Str,"HTML-ENTITIES","UTF-8");
		$Str=preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/m","&amp;",$Str);

		$Replace = array(
			"'",'"',"<",">",
			'&#128;','&#130;','&#131;','&#132;','&#133;','&#134;','&#135;','&#136;','&#137;','&#138;','&#139;','&#140;','&#142;','&#145;','&#146;','&#147;','&#148;','&#149;','&#150;','&#151;','&#152;','&#153;','&#154;','&#155;','&#156;','&#158;','&#159;'
		);

		$With=array(
			'&#39;','&quot;','&lt;','&gt;',
			'&#8364;','&#8218;','&#402;','&#8222;','&#8230;','&#8224;','&#8225;','&#710;','&#8240;','&#352;','&#8249;','&#338;','&#381;','&#8216;','&#8217;','&#8220;','&#8221;','&#8226;','&#8211;','&#8212;','&#732;','&#8482;','&#353;','&#8250;','&#339;','&#382;','&#376;'
		);

		$Str=str_replace($Replace,$With,$Str);
	}
	return $Str;
}

// Use sparingly
function undisplay_str($Str) {
	return mb_convert_encoding($Str, 'UTF-8', 'HTML-ENTITIES');
}

function make_utf8($Str) {
	if ($Str!="") {
		if (is_utf8($Str)) { $Encoding="UTF-8"; }
		if (empty($Encoding)) { $Encoding=mb_detect_encoding($Str,'UTF-8, ISO-8859-1'); }
		if (empty($Encoding)) { $Encoding="ISO-8859-1"; }
		if ($Encoding=="UTF-8") { return $Str; }
		else { return @mb_convert_encoding($Str,"UTF-8",$Encoding); }
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

// Escape an entire array for output
// $Escape is either true, false, or a list of array keys to not escape
function display_array($Array, $Escape = array()) {
	foreach ($Array as $Key => $Val) {
		if((!is_array($Escape) && $Escape == true) || !in_array($Key, $Escape)) {
			$Array[$Key] = display_str($Val);
		}
	}
	return $Array;
}

// Gets a tag ready for database input and display
function sanitize_tag($str) {
	$str = strtolower($str);
	$str = preg_replace('/[^a-z0-9.]/', '', $str);
	$str = htmlspecialchars($str);
	$str = db_string(trim($str));
	return $str;
}

// Generate a random string
function make_secret($Length = 32) {
	$Secret = '';
	$Chars='abcdefghijklmnopqrstuvwxyz0123456789';
	for($i=0; $i<$Length; $i++) {
		$Rand = mt_rand(0, strlen($Chars)-1);
		$Secret .= substr($Chars, $Rand, 1);
	}
	return str_shuffle($Secret);
}

//TODO: Read and add this one
/*
function make_secret($Length = 32) {
	$Secret = '';
	$Chars='abcdefghijklmnopqrstuvwxyz0123456789';
	$CharLen = strlen($Chars)-1;
	for ($i = 0; $i < $Length; ++$i) {
		$Secret .= $Chars[mt_rand(0, $CharLen)];
	}
	return $Secret;
}
*/

// Password hashes, feel free to make your own algorithm here
function make_hash($Str,$Secret) {
	return sha1(md5($Secret).$Str.sha1($Secret).SITE_SALT);
}

/*
Returns a username string for display
$Class and $Title can be omitted for an abbreviated version
$IsDonor, $IsWarned and $IsEnabled can be omitted for a *very* abbreviated version
*/
function format_username($UserID, $Username, $IsDonor = false, $IsWarned = '0000-00-00 00:00:00', $IsEnabled = true, $Class = false, $Title = false) {
	if($UserID == 0) {
		return 'System';
	} elseif($Username == '') {
		return "Unknown [$UserID]";
	}
	$str='<a href="user.php?id='.$UserID.'">'.$Username.'</a>';
	$str.=($IsDonor) ? '<a href="donate.php"><img src="'.STATIC_SERVER.'common/symbols/donor.png" alt="Donor" title="Donor" /></a>' : '';


	$str.=($IsWarned!='0000-00-00 00:00:00') ? '<img src="'.STATIC_SERVER.'common/symbols/warned.png" alt="Warned" title="Warned" />' : '';
	$str.=(!$IsEnabled) ? '<img src="'.STATIC_SERVER.'common/symbols/disabled.png" alt="Banned" title="Be good, and you won\'t end up like this user" />' : '';

	$str.=($Class) ? ' ('.make_class_string($Class).')' : '';
	$str.=($Title) ? ' ('.$Title.')' : '';
	return $str;
}

function make_class_string($ClassID) {
	global $Classes;
	return $Classes[$ClassID]['Name'];
}

//Write to the group log
function write_group_log($GroupID, $TorrentID, $UserID, $Message, $Hidden) {
	global $DB,$Time;
	$DB->query("INSERT INTO group_log (GroupID, TorrentID, UserID, Info, Time, Hidden) VALUES (".$GroupID.", ".$TorrentID.", ".$UserID.", '".db_string($Message)."', '".sqltime()."', ".$Hidden.")");
}

// Write a message to the system log
function write_log($Message) {
	global $DB,$Time;
	$DB->query('INSERT INTO log (Message, Time) VALUES (\''.db_string($Message).'\', \''.sqltime().'\')');
}

// Send a message to an IRC bot listening on SOCKET_LISTEN_PORT
function send_irc($Raw) {
	$IRCSocket = fsockopen(SOCKET_LISTEN_ADDRESS, SOCKET_LISTEN_PORT);
	$Raw = str_replace(array("\n", "\r"), '', $Raw);
	fwrite($IRCSocket, $Raw);
	fclose($IRCSocket);
}

function delete_torrent($ID, $GroupID=0) {
	global $DB, $Cache, $LoggedUser;
	if(!$GroupID) {
		$DB->query("SELECT GroupID, UserID FROM torrents WHERE ID='$ID'");
		list($GroupID, $UploaderID) = $DB->next_record();
		
	}
	if(empty($UserID)) {
		$DB->query("SELECT UserID FROM torrents WHERE ID='$ID'");
		list($UserID) = $DB->next_record();
	}

	$RecentUploads = $Cache->get_value('recent_uploads_'.$UserID);
	if(is_array($RecentUploads)) {
		foreach($RecentUploads as $Key => $Recent) {
			if($Recent['ID'] == $GroupID) {
				$Cache->delete_value('recent_uploads_'.$UserID);
			}
		}
	}
	
	
	$DB->query("SELECT info_hash FROM torrents WHERE ID = ".$ID);
	list($InfoHash) = $DB->next_record(MYSQLI_BOTH, false);
	$DB->query("DELETE FROM torrents WHERE ID = ".$ID);
	update_tracker('delete_torrent', array('info_hash' => rawurlencode($InfoHash), 'id' => $ID));
	
	$Cache->decrement('stats_torrent_count');

	$DB->query("SELECT COUNT(ID) FROM torrents WHERE GroupID='$GroupID' AND flags <> 1");
	list($Count) = $DB->next_record();

	if($Count == 0) {
		delete_group($GroupID);
	} else {
		update_hash($GroupID);
		//Artists
		$DB->query("SELECT ArtistID
				FROM torrents_artists 
				WHERE GroupID = ".$GroupID);
		$ArtistIDs = $DB->collect('ArtistID');
		foreach($ArtistIDs as $ArtistID) {
			$Cache->delete_value('artist_'.$ArtistID);
		}
	}

	// Torrent notifications
	$DB->query("SELECT UserID FROM users_notify_torrents WHERE TorrentID='$ID'");
	while(list($UserID) = $DB->next_record()) {
		$Cache->delete_value('notifications_new_'.$UserID);
	}
	$DB->query("DELETE FROM users_notify_torrents WHERE TorrentID='$ID'");


	$DB->query("UPDATE reportsv2 SET
			Status='Resolved',
			LastChangeTime='".sqltime()."',
			ModComment='Report already dealt with (Torrent deleted)'
		WHERE TorrentID=".$ID."
			AND Status != 'Resolved'");
	$Reports = $DB->affected_rows();
	if($Reports) {
		$Cache->decrement('num_torrent_reportsv2', $Reports);
	}

	$DB->query("DELETE FROM torrents_files WHERE TorrentID='$ID'");
	$DB->query("DELETE FROM torrents_bad_tags WHERE TorrentID = ".$ID);
	$DB->query("DELETE FROM torrents_bad_folders WHERE TorrentID = ".$ID);
	$DB->query("DELETE FROM torrents_bad_files WHERE TorrentID = ".$ID);
	$DB->query("DELETE FROM torrents_cassette_approved WHERE TorrentID = ".$ID);
	$DB->query("DELETE FROM torrents_lossymaster_approved WHERE TorrentID = ".$ID);
	$Cache->delete_value('torrent_download_'.$ID);
	$Cache->delete_value('torrent_group_'.$GroupID);
	$Cache->delete_value('torrents_details_'.$GroupID);
}

function delete_group($GroupID) {
	global $DB, $Cache;

	write_log("Group ".$GroupID." automatically deleted (No torrents have this group).");
	//$DB->query("DELETE FROM group_log WHERE GroupID = ".$GroupID);

	//Never call this unless you're certain the group is no longer used by any torrents
	$DB->query("SELECT CategoryID FROM torrents_group WHERE ID='$GroupID'");
	list($Category) = $DB->next_record();
	if($Category == 1) {
		$Cache->decrement('stats_album_count');
	}
	$Cache->decrement('stats_group_count');
	
	
	
	// Collages
	$DB->query("SELECT CollageID FROM collages_torrents WHERE GroupID='$GroupID'");
	if($DB->record_count()>0) {
		$CollageIDs = $DB->collect('CollageID');
		$DB->query("UPDATE collages SET NumTorrents=NumTorrents-1 WHERE ID IN (".implode(', ',$CollageIDs).")");
		$DB->query("DELETE FROM collages_torrents WHERE GroupID='$GroupID'");

		foreach($CollageIDs as $CollageID) {
			$Cache->delete_value('collage_'.$CollageID);
		}
		$Cache->delete_value('torrent_collages_'.$GroupID);
	}
	
	//Artists
	//Collect the artist IDs and then wipe the torrents_artist entry
	$DB->query("SELECT ArtistID FROM torrents_artists WHERE GroupID = ".$GroupID);
	$Artists = $DB->collect('ArtistID');
	
	$DB->query("DELETE FROM torrents_artists WHERE GroupID='$GroupID'");
	
	foreach($Artists as $ArtistID) {
		if(empty($ArtistID)) { continue; }
		//Get a count of how many groups or requests use the artist ID
		$DB->query("SELECT COUNT(ag.ArtistID)
					FROM artists_group as ag 
						LEFT JOIN requests_artists AS ra ON ag.ArtistID=ra.ArtistID 
					WHERE ra.ArtistID IS NOT NULL
						AND ag.ArtistID = '$ArtistID'");
		list($ReqCount) = $DB->next_record();
		$DB->query("SELECT COUNT(ag.ArtistID)
					FROM artists_group as ag 
						LEFT JOIN torrents_artists AS ta ON ag.ArtistID=ta.ArtistID 
					WHERE ta.ArtistID IS NOT NULL
						AND ag.ArtistID = '$ArtistID'");
		list($GroupCount) = $DB->next_record();
		if(($ReqCount + $GroupCount) == 0) {
			//The only group to use this artist
			delete_artist($ArtistID);
		} else {
			//Not the only group, still need to clear cache
			$Cache->delete_value('artist_'.$ArtistID);
		}
	}
	
	// Requests
	$DB->query("SELECT ID FROM requests WHERE GroupID='$GroupID'");
	$Requests = $DB->collect('ID');
	$DB->query("UPDATE requests SET GroupID = NULL WHERE GroupID = '$GroupID'");
	foreach ($Requests as $RequestID) {
		$Cache->delete_value('request_'.$RequestID);
	}
	
	$DB->query("DELETE FROM torrents_group WHERE ID='$GroupID'");
	$DB->query("DELETE FROM torrents_tags WHERE GroupID='$GroupID'");
	$DB->query("DELETE FROM torrents_tags_votes WHERE GroupID='$GroupID'");
	$DB->query("DELETE FROM torrents_comments WHERE GroupID='$GroupID'");
	$DB->query("DELETE FROM bookmarks_torrents WHERE GroupID='$GroupID'");
	$DB->query("DELETE FROM wiki_torrents WHERE PageID='$GroupID'");
	$DB->query("REPLACE INTO sphinx_delta (ID,Time) VALUES ('$GroupID',UNIX_TIMESTAMP())"); // Tells Sphinx that the group is removed
	
	$Cache->delete_value('torrents_details_'.$GroupID);
	$Cache->delete_value('torrent_group_'.$GroupID);
	$Cache->delete_value('groups_artists_'.$GroupID);
}

function delete_artist($ArtistID) {
	global $DB, $LoggedUser, $Cache;

	$DB->query("SELECT Name FROM artists_group WHERE ArtistID = ".$ArtistID);
	list($Name) = $DB->next_record();
	
	// Delete requests
	$DB->query("SELECT RequestID FROM requests_artists WHERE ArtistID=".$ArtistID." AND ArtistID != 0");
	$Requests = $DB->to_array();
	foreach($Requests AS $Request) {
		list($RequestID) = $Request;
		$DB->query('DELETE FROM requests WHERE ID='.$RequestID);
		$DB->query('DELETE FROM requests_votes WHERE RequestID='.$RequestID);
		$DB->query('DELETE FROM requests_tags WHERE RequestID='.$RequestID);
		$DB->query('DELETE FROM requests_artists WHERE RequestID='.$RequestID);
	}

	// Delete artist
	$DB->query('DELETE FROM artists_group WHERE ArtistID='.$ArtistID);
	$DB->query('DELETE FROM artists_alias WHERE ArtistID='.$ArtistID);
	$Cache->decrement('stats_artist_count');

	// Delete wiki revisions
	$DB->query('DELETE FROM wiki_artists WHERE PageID='.$ArtistID);

	// Delete tags
	$DB->query('DELETE FROM artists_tags WHERE ArtistID='.$ArtistID);

	$Cache->delete_value('artist_'.$ArtistID);
	// Record in log

	if(!empty($LoggedUser['Username'])) {
		$Username = $LoggedUser['Username'];
	} else {
		$Username = 'System';
	}
	write_log('Artist '.$ArtistID.' ('.$Name.') was deleted by '.$Username);
}

function warn_user($UserID, $Duration, $Reason) {
	global $LoggedUser, $DB, $Cache, $Time;

	$DB->query("SELECT Warned FROM users_info WHERE UserID=".$UserID." AND Warned <> '0000-00-00 00:00:00'");
	if($DB->record_count() > 0) {
		//User was already warned, appending new warning to old.
		list($OldDate) = $DB->next_record();
		$NewExpDate = date('Y-m-d H:i:s', strtotime($OldDate) + $Duration);

		send_pm($UserID, 0, db_string("You have received multiple warnings."), db_string("When you received your latest warning (Set to expire on ".date("Y-m-d", (time() + $Duration))."), you already had a different warning (Set to expire on ".date("Y-m-d", strtotime($OldDate)).").\n\n Due to this collision, your warning status will now expire at ".$NewExpDate."."));

		$AdminComment = date("Y-m-d").' - Warning (Clash) extended to expire at '.$NewExpDate.' by '.$LoggedUser['Username']."\nReason: $Reason\n\n";

		$DB->query('UPDATE users_info SET
			Warned=\''.db_string($NewExpDate).'\',
			WarnedTimes=WarnedTimes+1,
			AdminComment=CONCAT(\''.db_string($AdminComment).'\',AdminComment)
			WHERE UserID=\''.db_string($UserID).'\'');
	} else {
		//Not changing, user was not already warned
		$WarnTime = time_plus($Duration);

		$Cache->begin_transaction('user_info_'.$UserID);
		$Cache->update_row(false, array('Warned' => $WarnTime));
		$Cache->commit_transaction(0);

		$AdminComment = date("Y-m-d").' - Warned until '.$WarnTime.' by '.$LoggedUser['Username']."\nReason: $Reason\n\n";

		$DB->query('UPDATE users_info SET
			Warned=\''.db_string($WarnTime).'\',
			WarnedTimes=WarnedTimes+1,
			AdminComment=CONCAT(\''.db_string($AdminComment).'\',AdminComment)
			WHERE UserID=\''.db_string($UserID).'\'');
	}
}

/*-- update_hash function ------------------------------------------------*/
/*------------------------------------------------------------------------*/
/* This function is to update the cache and sphinx delta index to keep    */
/* everything up to date                                                  */
/*-- TODO ----------------------------------------------------------------*/
/* Add in tag sorting based on positive negative votes algo               */
/**************************************************************************/

function update_hash($GroupID) {
	global $DB,$SpecialChars,$Cache;
	$DB->query("UPDATE torrents_group SET TagList=(SELECT REPLACE(GROUP_CONCAT(tags.Name SEPARATOR ' '),'.','_')
		FROM torrents_tags AS t
		INNER JOIN tags ON tags.ID=t.TagID
		WHERE t.GroupID='$GroupID'
		GROUP BY t.GroupID)
		WHERE ID='$GroupID'");

	$DB->query("REPLACE INTO sphinx_delta (ID, GroupName, TagList, Year, CategoryID, Time, ReleaseType, CatalogueNumber, Size, Snatched, Seeders, Leechers, LogScore, Scene, HasLog, HasCue, FreeTorrent, Media, Format, Encoding, RemasterTitle, FileList)
		SELECT
		g.ID AS ID,
		g.Name AS GroupName,
		g.TagList,
		g.Year,
		g.CategoryID,
		UNIX_TIMESTAMP(g.Time) AS Time,
		g.ReleaseType,
		g.CatalogueNumber,
		MAX(CEIL(t.Size/1024)) AS Size,
		SUM(t.Snatched) AS Snatched,
		SUM(t.Seeders) AS Seeders,
		SUM(t.Leechers) AS Leechers,
		MAX(t.LogScore) AS LogScore,
		MAX(t.Scene) AS Scene,
		MAX(t.HasLog) AS HasLog,
		MAX(t.HasCue) AS HasCue,
		BIT_OR(t.FreeTorrent-1) AS FreeTorrent,
		GROUP_CONCAT(DISTINCT t.Media SEPARATOR ' ') AS Media,
		GROUP_CONCAT(DISTINCT t.Format SEPARATOR ' ') AS Format,
		GROUP_CONCAT(DISTINCT t.Encoding SEPARATOR ' ') AS Encoding,
		GROUP_CONCAT(DISTINCT t.RemasterTitle SEPARATOR ' ') AS RemasterTitle,
		GROUP_CONCAT(REPLACE(REPLACE(FileList, '|||', '\n '), '_', ' ') SEPARATOR '\n ') AS FileList
		FROM torrents AS t
		JOIN torrents_group AS g ON g.ID=t.GroupID
		WHERE g.ID=$GroupID
		GROUP BY g.ID");

	$DB->query("INSERT INTO sphinx_delta
		(ID, ArtistName)
		SELECT
		GroupID,
		GROUP_CONCAT(aa.Name separator ' ')
		FROM torrents_artists AS ta
		JOIN artists_alias AS aa ON aa.AliasID=ta.AliasID
		JOIN torrents_group AS tg ON tg.ID=ta.GroupID
		WHERE ta.GroupID=$GroupID AND ta.Importance IN ('1', '4', '5', '6')
		GROUP BY tg.ID
		ON DUPLICATE KEY UPDATE ArtistName=values(ArtistName)");
	
	$Cache->delete_value('torrents_details_'.$GroupID);
	$Cache->delete_value('torrent_group_'.$GroupID);

	$ArtistInfo = get_artist($GroupID);
	foreach($ArtistInfo as $Importances => $Importance) {
		foreach($Importance as $Artist) {
			$Cache->delete_value('artist_'.$Artist['id']); //Needed for at least freeleech change, if not others.
		}
	}
		
	$Cache->delete_value('groups_artists_'.$GroupID);
}

// this function sends a PM to the userid $ToID and from the userid $FromID, sets date to now
// this function no longer uses db_string() so you will need to escape strings before using this function!
// set userid to 0 for a PM from 'system'
// if $ConvID is not set, it auto increments it, ie. starting a new conversation
function send_pm($ToID,$FromID,$Subject,$Body,$ConvID='') {
	global $DB, $Cache, $Time;
	if($ToID==0) {
		// Don't allow users to send messages to the system
		return;
	}
	if($ConvID=='') {
		$DB->query("INSERT INTO pm_conversations(Subject) VALUES ('".$Subject."')");
		$ConvID = $DB->inserted_id();
		$DB->query("INSERT INTO pm_conversations_users
				(UserID, ConvID, InInbox, InSentbox, SentDate, ReceivedDate, UnRead) VALUES
				('$ToID', '$ConvID', '1','0','".sqltime()."', '".sqltime()."', '1')");
		if ($FromID != 0) {
			$DB->query("INSERT INTO pm_conversations_users
				(UserID, ConvID, InInbox, InSentbox, SentDate, ReceivedDate, UnRead) VALUES
				('$FromID', '$ConvID', '0','1','".sqltime()."', '".sqltime()."', '0')");
		}
		$ToID = array($ToID);
	} else {
		$DB->query("UPDATE pm_conversations_users SET
				InInbox='1',
				UnRead='1',
				ReceivedDate='".sqltime()."'
				WHERE UserID IN (".implode(',', $ToID).")
				AND ConvID='$ConvID'");

		$DB->query("UPDATE pm_conversations_users SET
				InSentbox='1',
				SentDate='".sqltime()."'
				WHERE UserID='$FromID'
				AND ConvID='$ConvID'");
	}
	$DB->query("INSERT INTO pm_messages
			(SenderID, ConvID, SentDate, Body) VALUES
			('$FromID', '$ConvID', '".sqltime()."', '".$Body."')");

	// Clear the caches of the inbox and sentbox
	//$DB->query("SELECT UnRead from pm_conversations_users WHERE ConvID='$ConvID' AND UserID='$ToID'");
	foreach($ToID as $ID) {
		$DB->query("SELECT COUNT(ConvID) FROM pm_conversations_users WHERE UnRead = '1' and UserID='$ID' AND InInbox = '1'");
		list($UnRead) = $DB->next_record();
		$Cache->cache_value('inbox_new_'.$ID, $UnRead);
	}

	//if ($UnRead == 0) {
	//	$Cache->increment('inbox_new_'.$ToID);
	//}
	return $ConvID;
}

//Create thread function, things should already be escaped when sent here.
//Almost all the code is stolen straight from the forums and tailored for new posts only
function create_thread($ForumID, $AuthorID, $Title, $PostBody) {
	global $DB, $Cache, $Time;
	if(!$ForumID || !$AuthorID || !is_number($AuthorID) || !$Title || !$PostBody) {
		return -1;
	}

	$DB->query("SELECT Username FROM users_main WHERE ID=".$AuthorID);
	if($DB->record_count() < 1) {
		return -2;
	}
	list($AuthorName) = $DB->next_record();

	$ThreadInfo = array();
	$ThreadInfo['IsLocked'] = 0;
	$ThreadInfo['IsSticky'] = 0;

	$DB->query("INSERT INTO forums_topics
		(Title, AuthorID, ForumID, LastPostTime, LastPostAuthorID)
		Values
		('".$Title."', '".$AuthorID."', '$ForumID', '".sqltime()."', '".$AuthorID."')");
	$TopicID = $DB->inserted_id();
	$Posts = 1;

	$DB->query("INSERT INTO forums_posts
			(TopicID, AuthorID, AddedTime, Body)
			VALUES
			('$TopicID', '".$AuthorID."', '".sqltime()."', '".$PostBody."')");
	$PostID = $DB->inserted_id();

	$DB->query("UPDATE forums SET
				NumPosts		  = NumPosts+1,
				NumTopics		 = NumTopics+1,
				LastPostID		= '$PostID',
				LastPostAuthorID  = '".$AuthorID."',
				LastPostTopicID   = '$TopicID',
				LastPostTime	  = '".sqltime()."'
				WHERE ID = '$ForumID'");

	$DB->query("UPDATE forums_topics SET
			NumPosts		  = NumPosts+1,
			LastPostID		= '$PostID',
			LastPostAuthorID  = '".$AuthorID."',
			LastPostTime	  = '".sqltime()."'
			WHERE ID = '$TopicID'");

	// Bump this topic to head of the cache
	list($Forum,,,$Stickies) = $Cache->get_value('forums_'.$ForumID);
	if (!empty($Forum)) {
		if (count($Forum) == TOPICS_PER_PAGE && $Stickies < TOPICS_PER_PAGE) {
			array_pop($Forum);
		}
		$DB->query("SELECT f.IsLocked, f.IsSticky, f.NumPosts FROM forums_topics AS f WHERE f.ID ='$TopicID'");
		list($IsLocked,$IsSticky,$NumPosts) = $DB->next_record();
		$Part1 = array_slice($Forum,0,$Stickies,true); //Stickys
		$Part2 = array(
			$TopicID=>array(
				'ID' => $TopicID,
				'Title' => $Title,
				'AuthorID' => $AuthorID,
				'AuthorUsername' => $AuthorName,
				'IsLocked' => $IsLocked,
				'IsSticky' => $IsSticky,
				'NumPosts' => $NumPosts,
				'LastPostID' => $PostID,
				'LastPostTime' => sqltime(),
				'LastPostAuthorID' => $AuthorID,
				'LastPostUsername' => $AuthorName
				)
			); //Bumped thread
		$Part3 = array_slice($Forum,$Stickies,TOPICS_PER_PAGE,true); //Rest of page
		if ($Stickies > 0) {
			$Part1 = array_slice($Forum,0,$Stickies,true); //Stickies
			$Part3 = array_slice($Forum,$Stickies,TOPICS_PER_PAGE-$Stickies-1,true); //Rest of page
		} else {
			$Part1 = array();
			$Part3 = $Forum;
		}
		if (is_null($Part1)) { $Part1 = array(); }
		if (is_null($Part3)) { $Part3 = array(); }
		$Forum = $Part1 + $Part2 + $Part3;
		$Cache->cache_value('forums_'.$ForumID, array($Forum,'',0,$Stickies), 0);
	}

	//Update the forum root
	$Cache->begin_transaction('forums_list');
	$UpdateArray = array(
		'NumPosts'=>'+1',
		'LastPostID'=>$PostID,
		'LastPostAuthorID'=>$AuthorID,
		'Username'=>$AuthorName,
		'LastPostTopicID'=>$TopicID,
		'LastPostTime'=>sqltime(),
		'Title'=>$Title,
		'IsLocked'=>$ThreadInfo['IsLocked'],
		'IsSticky'=>$ThreadInfo['IsSticky']
		);

	$UpdateArray['NumTopics']='+1';

	$Cache->update_row($ForumID, $UpdateArray);
	$Cache->commit_transaction(0);

	$CatalogueID = floor((POSTS_PER_PAGE*ceil($Posts/POSTS_PER_PAGE)-POSTS_PER_PAGE)/THREAD_CATALOGUE);
	$Cache->begin_transaction('thread_'.$TopicID.'_catalogue_'.$CatalogueID);
	$Post = array(
		'ID'=>$PostID,
		'AuthorID'=>$LoggedUser['ID'],
		'AddedTime'=>sqltime(),
		'Body'=>$PostBody,
		'EditedUserID'=>0,
		'EditedTime'=>'0000-00-00 00:00:00',
		'Username'=>''
		);
	$Cache->insert('', $Post);
	$Cache->commit_transaction(0);

	$Cache->begin_transaction('thread_'.$TopicID.'_info');
	$Cache->update_row(false, array('Posts'=>'+1', 'LastPostAuthorID'=>$AuthorID));
	$Cache->commit_transaction(0);

	return $TopicID;
}



// Check to see if a user has the permission to perform an action
function check_perms($PermissionName,$MinClass = 0) {
	global $LoggedUser;
	return (isset($LoggedUser['Permissions'][$PermissionName]) && $LoggedUser['Permissions'][$PermissionName] && $LoggedUser['Class']>=$MinClass)?true:false;
}

// TODO: make stricter, e.g. on all whitespace characters or Unicode normalisation
function normalise_artist_name($ArtistName) {
	// \u200e is &lrm;
	$ArtistName = trim($ArtistName);
	$ArtistName = preg_replace('/^(\xE2\x80\x8E)+/', '', $ArtistName);
	$ArtistName = preg_replace('/(\xE2\x80\x8E)+$/', '', $ArtistName);
	return trim(preg_replace('/ +/', ' ', $ArtistName));
}

function get_artists($GroupIDs, $Escape = array()) {
	global $Cache, $DB;
	$Results = array();
	$DBs = array();
	foreach($GroupIDs as $GroupID) {
		if(!is_number($GroupID)) {
			continue;
		}
		$Artists = $Cache->get_value('groups_artists_'.$GroupID);
		if(is_array($Artists)) {
			$Results[$GroupID] = $Artists;
		} else {
			$DBs[] = $GroupID;
		}
	}
	if(count($DBs) > 0) {
		$IDs = implode(',', $DBs);
		if(empty($IDs)) {
			$IDs = "null";
		}
		$DB->query("SELECT ta.GroupID,
				ta.ArtistID,
				aa.Name,
				ta.Importance,
				ta.AliasID
			FROM torrents_artists AS ta 
				JOIN artists_alias AS aa ON ta.AliasID = aa.AliasID 
			WHERE ta.GroupID IN ($IDs) 
			ORDER BY ta.GroupID ASC, 
				ta.Importance ASC, 
				aa.Name ASC;");
		while(list($GroupID,$ArtistID,$ArtistName,$ArtistImportance,$AliasID) = $DB->next_record(MYSQLI_BOTH, false)) {
			$Results[$GroupID][$ArtistImportance][] = array('id' => $ArtistID, 'name' => $ArtistName, 'aliasid' => $AliasID);
			$New[$GroupID][$ArtistImportance][] = array('id' => $ArtistID, 'name' => $ArtistName, 'aliasid' => $AliasID);
		}
		foreach($DBs as $GroupID) {
			if(isset($New[$GroupID])) {
				$Cache->cache_value('groups_artists_'.$GroupID, $New[$GroupID]);
			}
			else {
				$Cache->cache_value('groups_artists_'.$GroupID, array());
			}
		}
		$Missing = array_diff($GroupIDs, array_keys($Results));
		if(!empty($Missing)) {
			$Results += array_fill_keys($Missing, array());
		}
	}
	return $Results;
}

/**
 * Convenience class for when you just need one group
 * @param $GroupID
 * @return unknown_type
 */
function get_artist($GroupID) {
	$Results = get_artists(array($GroupID));
	return $Results[$GroupID];
}

function display_artists($Artists, $MakeLink = true, $IncludeHyphen = true, $Escape = true) {
	if(!empty($Artists)) {
		$ampersand = ($Escape) ? ' &amp; ' : ' & ';
		$link = '';
		
		$MainArtists = $Artists[1];
		$Guests      = $Artists[2];
		$Composers   = $Artists[4];
		$Conductors  = $Artists[5];
		$DJs         = $Artists[6];
		
		if ((count($MainArtists) + count($Conductors) + count($DJs) == 0) && (count($Composers) == 0)) {
			return '';
		}
		
		// Various Composers is not needed and is ugly and should die
		switch(count($Composers)) {
			case 0:
				break;
			case 1:
				$link .= display_artist($Composers[0], $MakeLink, $Escape);
				break;
			case 2:
				$link .= display_artist($Composers[0], $MakeLink, $Escape).$ampersand.display_artist($Composers[1], $MakeLink, $Escape);
				break;
		}

		if ((count($Composers) > 0) && (count($Composers) < 3) && (count($MainArtists) > 0)) {
			$link .= ' performed by ';
		}
		
		$ComposerStr .= $link;
		
		switch(count($MainArtists)) {
			case 0:
				break;
			case 1:
				$link .= display_artist($MainArtists[0], $MakeLink, $Escape);
				break;
			case 2:
				$link .= display_artist($MainArtists[0], $MakeLink, $Escape).$ampersand.display_artist($MainArtists[1], $MakeLink, $Escape);
				break;
			default:
				$link .= 'Various Artists';
		}
		
		/*if(!empty($Guests) &&  (count($MainArtists) + count($Composers) > 0) && (count($MainArtists) + count($Composers) + count($Conductors) < 3)) {
			switch(count($Guests)) {
				case 1:
					$link .= ' with '.display_artist($Guests[0], $MakeLink, $Escape);
					break;
				case 2:
					$link .= ' with '.display_artist($Guests[0], $MakeLink, $Escape).$ampersand.display_artist($Guests[1], $MakeLink, $Escape);
					break;
			}
		}*/
		
		if ((count($Conductors) > 0) && (count($MainArtists) + count($Composers) > 0) && (count($Composers) < 3 || count($MainArtists) > 0)) {
			$link .= ' under ';
		}
		switch(count($Conductors)) {
			case 0:
				break;
			case 1:
				$link .= display_artist($Conductors[0], $MakeLink, $Escape);
				break;
			case 2:
				$link .= display_artist($Conductors[0], $MakeLink, $Escape).$ampersand.display_artist($Conductors[1], $MakeLink, $Escape);
				break;
			default:
				$link .= ' Various Conductors';
		}
		
		if ((count($Composers) > 0) && (count($MainArtists) + count($Conductors) > 3) && (count($MainArtists) > 1) && (count($Conductors) > 1)) {
			$link = $ComposerStr . 'Various Artists';
		} elseif ((count($Composers) > 2) && (count($MainArtists) + count($Conductors) == 0)) {
			$link = 'Various Composers';
		}
				
		// DJs override everything else
		switch(count($DJs)) {
			case 0:
				break;
			case 1:
				$link = display_artist($DJs[0], $MakeLink, $Escape);
				break;
			case 2:
				$link = display_artist($DJs[0], $MakeLink, $Escape).$ampersand.display_artist($DJs[1], $MakeLink, $Escape);
				break;
			default :
				$link = 'Various DJs';
		}
		
		return $link.($IncludeHyphen?' - ':'');
	} else {
		return '';
	}
}

function display_artist($Artist, $MakeLink = true, $Escape = true) {
	if ($MakeLink && !$Escape) {
		error('Invalid parameters to display_artist()');
	} elseif ($MakeLink) {
		return '<a href="artist.php?id='.$Artist['id'].'">'.display_str($Artist['name']).'</a>';
	} elseif ($Escape) {
		return display_str($Artist['name']);
	} else {
		return $Artist['name'];
	}
}

// Function to get data and torrents for an array of GroupIDs.
// In places where the output from this is merged with sphinx filters, it will be in a different order.
function get_groups($GroupIDs, $Return = true, $GetArtists = true, $Torrents = true) {
	global $DB, $Cache;
	
	$Found = array_flip($GroupIDs);
	$NotFound = array_flip($GroupIDs);
	$Key = $Torrents ? 'torrent_group_' : 'torrent_group_light_';
	
	foreach($GroupIDs as $GroupID) {
		$Data = $Cache->get_value($Key.$GroupID);
		if(!empty($Data) && (@$Data['ver'] >= 4)) {
			unset($NotFound[$GroupID]);
			$Found[$GroupID] = $Data['d'];
		}
	}
	
	$IDs = implode(',',array_flip($NotFound));
	
	/*
	Changing any of these attributes returned will cause very large, very dramatic site-wide chaos.
	Do not change what is returned or the order thereof without updating:
		torrents, artists, collages, bookmarks, better, the front page, 
	and anywhere else the get_groups function is used.
	*/
	
	if(count($NotFound)>0) {
		$DB->query("SELECT g.ID, g.Name, g.Year, g.RecordLabel, g.CatalogueNumber, g.TagList, g.ReleaseType, g.VanityHouse FROM torrents_group AS g WHERE g.ID IN ($IDs)");
	
		while($Group = $DB->next_record(MYSQLI_ASSOC, true)) {
			unset($NotFound[$Group['ID']]);
			$Found[$Group['ID']] = $Group;
			$Found[$Group['ID']]['Torrents'] = array();
			$Found[$Group['ID']]['Artists'] = array();
		}
		
		if ($Torrents) {
			$DB->query("SELECT
						ID, GroupID, Media, Format, Encoding, RemasterYear, Remastered, RemasterTitle, RemasterRecordLabel, RemasterCatalogueNumber, Scene, HasLog, HasCue, LogScore, FileCount, FreeTorrent, Size, Leechers, Seeders, Snatched, Time, ID AS HasFile
						FROM torrents AS t WHERE GroupID IN($IDs) ORDER BY GroupID, Remastered, (RemasterYear <> 0) DESC, RemasterYear, RemasterTitle, RemasterRecordLabel, RemasterCatalogueNumber, Media, Format, Encoding, ID");
			while($Torrent = $DB->next_record(MYSQLI_ASSOC, true)) {
				$Found[$Torrent['GroupID']]['Torrents'][$Torrent['ID']] = $Torrent;
		
				$Cache->cache_value('torrent_group_'.$Torrent['GroupID'], array('ver'=>4, 'd'=>$Found[$Torrent['GroupID']]), 0);
				$Cache->cache_value('torrent_group_light_'.$Torrent['GroupID'], array('ver'=>4, 'd'=>$Found[$Torrent['GroupID']]), 0);
			}
		} else {
			foreach ($Found as $Group) {
				$Cache->cache_value('torrent_group_light_'.$Group['ID'], array('ver'=>4, 'd'=>$Found[$Group['ID']]), 0);
			}
		}
	}
	if($GetArtists) {
		$Artists = get_artists($GroupIDs);
	} else {
		$Artists = array();
	}
	
	if($Return) { // If we're interested in the data, and not just caching it
		foreach($Artists as $GroupID=>$Data) {
			if(array_key_exists(1, $Data) || array_key_exists(4, $Data) || array_key_exists(6, $Data)) {
				$Found[$GroupID]['Artists']=$Data[1]; // Only use main artists (legacy)
				$Found[$GroupID]['ExtendedArtists'][1]=$Data[1];
				$Found[$GroupID]['ExtendedArtists'][2]=$Data[2];
				$Found[$GroupID]['ExtendedArtists'][3]=$Data[3];
				$Found[$GroupID]['ExtendedArtists'][4]=$Data[4];
				$Found[$GroupID]['ExtendedArtists'][5]=$Data[5];
				$Found[$GroupID]['ExtendedArtists'][6]=$Data[6];
			}
			else {
				$Found[$GroupID]['ExtendedArtists'] = false;
			}
		}

		$Matches = array('matches'=>$Found, 'notfound'=>array_flip($NotFound));

		return $Matches;
	}
}

//Function to get data from an array of $RequestIDs.
//In places where the output from this is merged with sphinx filters, it will be in a different order.
function get_requests($RequestIDs, $Return = true) {
	global $DB, $Cache;
	
	$Found = array_flip($RequestIDs);
	$NotFound = array_flip($RequestIDs);
	
	foreach($RequestIDs as $RequestID) {
		$Data = $Cache->get_value('request_'.$RequestID);
		if(!empty($Data)) {
			unset($NotFound[$RequestID]);
			$Found[$RequestID] = $Data;
		}
	}
	
	$IDs = implode(',',array_flip($NotFound));

	/*
		Don't change without ensuring you change everything else that uses get_requests()
	*/
	
	if(count($NotFound) > 0) {
		$DB->query("SELECT
					r.ID AS ID,
					r.UserID,
					u.Username,
					r.TimeAdded,
					r.LastVote,
					r.CategoryID, 
					r.Title, 
					r.Year, 
					r.Image,
					r.Description,
					r.CatalogueNumber,
					r.RecordLabel,
					r.ReleaseType, 
					r.BitrateList,
					r.FormatList, 
					r.MediaList,
					r.LogCue,
					r.FillerID,
					filler.Username,
					r.TorrentID,
					r.TimeFilled,
					r.GroupID
				FROM requests AS r
					LEFT JOIN users_main AS u ON u.ID=r.UserID
					LEFT JOIN users_main AS filler ON filler.ID=FillerID AND FillerID!=0
				WHERE r.ID IN (".$IDs.")
				ORDER BY ID");
		
		$Requests = $DB->to_array();
		foreach($Requests as $Request) {
			unset($NotFound[$Request['ID']]);
			$Request['Tags'] = get_request_tags($Request['ID']);
			$Found[$Request['ID']] = $Request;
			$Cache->cache_value('request_'.$Request['ID'], $Request, 0);
		}
	}

	if($Return) { // If we're interested in the data, and not just caching it
		$Matches = array('matches'=>$Found, 'notfound'=>array_flip($NotFound));
		return $Matches;
	}
}

function update_sphinx_requests($RequestID) {
	global $DB, $Cache;

	$DB->query("REPLACE INTO sphinx_requests_delta (
				ID, UserID, TimeAdded, LastVote, CategoryID, Title,
				Year, ReleaseType, CatalogueNumber, BitrateList,
				FormatList, MediaList, LogCue, FillerID, TorrentID,
				TimeFilled, Visible, Votes, Bounty)
			SELECT
				ID, r.UserID, UNIX_TIMESTAMP(TimeAdded) AS TimeAdded,
				UNIX_TIMESTAMP(LastVote) AS LastVote, CategoryID,
				Title, Year, ReleaseType, CatalogueNumber, BitrateList,
				FormatList, MediaList, LogCue, FillerID, TorrentID,
				UNIX_TIMESTAMP(TimeFilled) AS TimeFilled, Visible,
				COUNT(rv.UserID) AS Votes, SUM(rv.Bounty) >> 10 AS Bounty
			FROM requests AS r LEFT JOIN requests_votes AS rv ON rv.RequestID=r.ID
				wHERE ID = ".$RequestID."
				GROUP BY r.ID");

	$DB->query("UPDATE sphinx_requests_delta
					SET ArtistList = (SELECT
						GROUP_CONCAT(aa.Name SEPARATOR ' ')
					FROM requests_artists AS ra
						JOIN artists_alias AS aa ON aa.AliasID=ra.AliasID
					WHERE ra.RequestID = ".$RequestID."
					GROUP BY NULL)
				WHERE ID = ".$RequestID);

	$Cache->delete_value('requests_'.$RequestID);
}

function get_tags($TagNames) {
	global $Cache, $DB;
	$TagIDs = array();
	foreach($TagNames as $Index => $TagName) {
		$Tag = $Cache->get_value('tag_id_'.$TagName);
		if(is_array($Tag)) {
			unset($TagNames[$Index]);
			$TagIDs[$Tag['ID']] = $Tag['Name'];
		}
	}
	if(count($TagNames) > 0) {
		$DB->query("SELECT ID, Name FROM tags WHERE Name IN ('".implode("', '", $TagNames)."')");
		$SQLTagIDs = $DB->to_array();
		foreach($SQLTagIDs as $Tag) {
			$TagIDs[$Tag['ID']] = $Tag['Name'];
			$Cache->cache_value('tag_id_'.$Tag['Name'], $Tag, 0);
		}
	}
	
	return($TagIDs);
}

function torrent_info($Data) {
	$Info = array();
	if(!empty($Data['Format'])) { $Info[]=$Data['Format']; }
	if(!empty($Data['Encoding'])) { $Info[]=$Data['Encoding']; }
	if(!empty($Data['HasLog'])) {
		$Str = 'Log';
		if(!empty($Data['LogScore'])) {
			$Str.=' ('.$Data['LogScore'].'%)';
		}
		$Info[]=$Str;
	}
	if(!empty($Data['HasCue'])) { $Info[]='Cue'; }
	if(!empty($Data['Scene'])) { $Info[]='Scene'; }
	if($Data['FreeTorrent'] == '1') { $Info[]='<strong>Freeleech!</strong>'; }
	if($Data['FreeTorrent'] == '2') { $Info[]='<strong>Neutral Leech!</strong>'; }
	if($Data['PersonalFL'] == 1) { $Info[]='<strong>Personal Freeleech!</strong>'; }
	return implode(' / ', $Info);
}

// Echo data sent in a form, typically a text area
function form($Index, $Return = false) {
	if(!empty($_GET[$Index])) {
		if($Return) {
			return display_str($_GET[$Index]);
		} else {
			echo display_str($_GET[$Index]);
		}
	}
}

// Check/select tickboxes and <select>s
function selected($Name, $Value, $Attribute='selected', $Array = array()) {
	if(!empty($Array)) {
		if(isset($Array[$Name]) && $Array[$Name]!=='') {
			if($Array[$Name] == $Value) {
				echo ' '.$Attribute.'="'.$Attribute.'"';
			}
		}
	} else {
		if(isset($_GET[$Name]) && $_GET[$Name]!=='') {
			if($_GET[$Name] == $Value) {
				echo ' '.$Attribute.'="'.$Attribute.'"';
			}
		}
	}
}
function error($Error, $Ajax=false) {
	global $Debug;
	require(SERVER_ROOT.'/sections/error/index.php');
	$Debug->profile();
	die();
}

/**
 * @param BanReason 0 - Unknown, 1 - Manual, 2 - Ratio, 3 - Inactive, 4 - Unused.
 */

function disable_users($UserIDs, $AdminComment, $BanReason = 1) {
	global $Cache, $DB;
	if(!is_array($UserIDs)) {
		$UserIDs = array($UserIDs);
	}
	$DB->query("UPDATE users_info AS i JOIN users_main AS m ON m.ID=i.UserID
		SET m.Enabled='2',
		m.can_leech='0',
		i.AdminComment = CONCAT('".sqltime()." - ".($AdminComment ? $AdminComment : 'Disabled by system')."\n\n', i.AdminComment),
		i.BanDate='".sqltime()."',
		i.BanReason='".$BanReason."',
		i.RatioWatchDownload=".($BanReason == 2?'m.Downloaded':"'0'")."
		WHERE m.ID IN(".implode(',',$UserIDs).") ");
	$Cache->decrement('stats_user_count',$DB->affected_rows());
	foreach($UserIDs as $UserID) {
		/*
		$Cache->cache_value('enabled_'.$UserID, 2, 2592000);
		$Cache->begin_transaction('user_info_'.$UserID);
		$Cache->update_row(false, array('Enabled' => 2));
		$Cache->commit_transaction(0);
		*/
		$Cache->delete_value('enabled_'.$UserID);
		$Cache->delete_value('user_info_'.$UserID);
		$Cache->delete_value('user_info_heavy_'.$UserID);
		$Cache->delete_value('user_stats_'.$UserID);
	
		$DB->query("SELECT SessionID FROM users_sessions WHERE UserID='$UserID' AND Active = 1");
		while(list($SessionID) = $DB->next_record()) {
			$Cache->delete_value('session_'.$UserID.'_'.$SessionID);
		}
		$Cache->delete_value('users_sessions_'.$UserID);
		
		
		$DB->query("DELETE FROM users_sessions WHERE UserID='$UserID'");
		
	}
	$DB->query("SELECT torrent_pass FROM users_main WHERE ID in (".implode(", ",$UserIDs).")");
	$PassKeys = $DB->collect('torrent_pass');
	$Concat = "";
	foreach($PassKeys as $PassKey) {
		if(strlen($Concat) > 4000) {
			update_tracker('remove_users', array('passkeys' => $Concat));
			$Concat = $PassKey;
		} else {
			$Concat .= $PassKey;
		}
	}
	update_tracker('remove_users', array('passkeys' => $Concat));
}


/**
 * Send a GET request over a socket directly to the tracker
 * For example, update_tracker('change_passkey', array('oldpasskey' => OLD_PASSKEY, 'newpasskey' => NEW_PASSKEY)) will send the request:
 * GET /tracker_32_char_secret_code/update?action=change_passkey&oldpasskey=OLD_PASSKEY&newpasskey=NEW_PASSKEY HTTP/1.1
 * @param $Action The action to send
 * @param $Updates An associative array of key->value pairs to send to the tracker
 */
function update_tracker($Action, $Updates, $ToIRC = false) {
	global $Cache;
	//Build request
	$Get = '/update?action='.$Action;
	foreach($Updates as $Key => $Value) {
		$Get .= '&'.$Key.'='.$Value;
	}

	if($ToIRC != false) {
		send_irc('PRIVMSG #tracker :'.$Get);
	}
	$Path = TRACKER_SECRET.$Get;

	$Return = "";
	$Attempts = 0;
	while($Return != "success" && $Attempts < 3) {
	
		// Send update
		$File = fsockopen(TRACKER_HOST, TRACKER_PORT, $ErrorNum, $ErrorString);
		if($File) {
			$Header = 'GET /'.$Path.' HTTP/1.1\r\n';
			if(fwrite($File, $Header) === false) {
				$Attempts++;
				$Err = "Failed to fwrite()";
				sleep(3);
				continue;
			}
		} else {
			$Attempts++;
			$Err = "Failed to fsockopen() - ".$ErrorNum." - ".$ErrorString;
			sleep(6);
			continue;
		}

		// Check for response.

		$ResHeader = '';
		do {
			$ResHeader .= fread($File, 1);
		} while (!feof($File) && !ends_with($ResHeader, "\r\n\r\n"));

		$Response = '';
		while($Line = fgets($File)) {
			$Response .= $Line;
		}

		$Return = chop($Response);
		$Attempts++;
	}

	if($Return != "success") {
		send_irc("PRIVMSG #tracker :{$Attempts} {$Err} {$Get}");
		if($Cache->get_value('ocelot_error_reported') === false) {
			send_irc("PRIVMSG ".ADMIN_CHAN." :Failed to update ocelot: ".$Err." : ".$Get);
			$Cache->cache_value('ocelot_error_reported', true, 900);
		}
	}
	return ($Return == "success");
}
/*
function ends_with($Haystack, $Needle) {
	return strrpos($Haystack, $Needle) === strlen($Haystack)-strlen($Needle);
}
*/

/** This ends_with is slightly slower when the string is found, but a lot faster when it isn't.
 */
function ends_with($Haystack, $Needle) {
	return substr($Haystack, strlen($Needle) * -1) == $Needle;
}

function starts_with($Haystack, $Needle) {
	return strpos($Haystack, $Needle) === 0;
}

/**
 * Variant of in_array() with trailing wildcard support
 * @param string $Needle, array $Haystack
 * @return true if (substring of) $Needle exists in $Haystack
 */
function in_array_partial($Needle, $Haystack) {
	static $Searches = array();
	if(array_key_exists($Needle, $Searches)) {
		return $Searches[$Needle];
	}
	foreach($Haystack as $String) {
		if(substr($String, -1) == '*') {
			if(!strncmp($Needle, $String, strlen($String)-1)) {
				$Searches[$Needle] = true;
				return true;
			}
		} elseif(!strcmp($Needle, $String)) {
			$Searches[$Needle] = true;
			return true;
		}
	}
	$Searches[$Needle] = false;
	return false;
}

/**
 * Will freeleech / neutralleech / normalise a set of torrents
 * @param array $TorrentIDs An array of torrents IDs to iterate over
 * @param int $FreeNeutral 0 = normal, 1 = fl, 2 = nl
 * @param int $FreeLeechType 0 = Unknown, 1 = Staff picks, 2 = Perma-FL (Toolbox, etc.), 3 = Vanity House
 */
function freeleech_torrents($TorrentIDs, $FreeNeutral = 1, $FreeLeechType = 0) {
	global $DB, $Cache, $LoggedUser;

	if(!is_array($TorrentIDs)) {
		$TorrentIDs = array($TorrentIDs);
	}
	
	$DB->query("UPDATE torrents SET FreeTorrent = '".$FreeNeutral."', FreeLeechType = '".$FreeLeechType."' WHERE ID IN (".implode(", ", $TorrentIDs).")");
	$DB->query("SELECT ID, GroupID, info_hash FROM torrents WHERE ID IN (".implode(", ", $TorrentIDs).") ORDER BY GroupID ASC");
	$Torrents = $DB->to_array(false, MYSQLI_NUM, false);
	$GroupIDs = $DB->collect('GroupID');

	foreach($Torrents as $Torrent) {
		list($TorrentID, $GroupID, $InfoHash) = $Torrent;
		update_tracker('update_torrent', array('info_hash' => rawurlencode($InfoHash), 'freetorrent' => $FreeNeutral));
		$Cache->delete_value('torrent_download_'.$TorrentID);
		write_log($LoggedUser['Username']." marked torrent ".$TorrentID." freeleech type ".$FreeLeechType."!");
		write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], "marked as freeleech type ".$FreeLeechType."!", 0);
	}

	foreach($GroupIDs as $GroupID) {
		update_hash($GroupID);
	}
}

/**
 * Convenience function to allow for passing groups to freeleech_torrents()
 */	
function freeleech_groups($GroupIDs, $FreeNeutral = 1, $FreeLeechType = 0) {
	global $DB;

	if(!is_array($GroupIDs)) {
		$GroupIDs = array($GroupIDs);
	}

	$DB->query("SELECT ID from torrents WHERE GroupID IN (".implode(", ", $GroupIDs).")");
	if($DB->record_count()) {
		$TorrentIDs = $DB->collect('ID');
		freeleech_torrents($TorrentIDs, $FreeNeutral, $FreeLeechType);
	}
}


$Debug->set_flag('ending function definitions');
//Include /sections/*/index.php
$Document = basename(parse_url($_SERVER['SCRIPT_FILENAME'], PHP_URL_PATH), '.php');
if(!preg_match('/^[a-z0-9]+$/i', $Document)) { error(404); }



require(SERVER_ROOT.'/sections/'.$Document.'/index.php');
$Debug->set_flag('completed module execution');

/* Required in the absence of session_start() for providing that pages will change 
upon hit rather than being browser cache'd for changing content. */
header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
header('Pragma: no-cache');

//Flush to user
ob_end_flush();

$Debug->set_flag('set headers and send to user');


//Attribute profiling
$Debug->profile();
