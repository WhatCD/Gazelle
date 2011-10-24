<?
/*-- Image Start Class ---------------------------------*/
/*------------------------------------------------------*/
/* Simplified version of script_start, used for the	 */
/* sitewide image proxy.								*/
/*------------------------------------------------------*/
/********************************************************/
error_reporting(E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR);

if(isset($_SERVER['http_if_modified_since'])) {
	header("Status: 304 Not Modified");
	die();
}

header('Expires: '.date('D, d-M-Y H:i:s \U\T\C',time()+3600*24*120)); //120 days
header('Last-Modified: '.date('D, d-M-Y H:i:s \U\T\C',time()));

require('classes/config.php'); //The config contains all site wide configuration information as well as memcached rules

if (!extension_loaded('gd')) { error('nogd'); }

require(SERVER_ROOT.'/classes/class_cache.php'); //Require the caching class
require(SERVER_ROOT.'/classes/class_encrypt.php'); //Require the encryption class
require(SERVER_ROOT.'/classes/regex.php');

$Cache = NEW CACHE; //Load the caching class
$Enc = NEW CRYPT; //Load the encryption class

if (isset($_COOKIE['session'])) { $LoginCookie=$Enc->decrypt($_COOKIE['session']); }
if(isset($LoginCookie)) {
	list($SessionID, $UserID)=explode("|~|",$Enc->decrypt($LoginCookie));
	$UserID = (int)$UserID;
	$UserInfo = $Cache->get_value('user_info_'.$UserID);
	$Permissions = $Cache->get_value('perm_'.$UserInfo['PermissionID']);
}

function check_perms($PermissionName) {
	global $Permissions;
	return (isset($Permissions['Permissions'][$PermissionName])) ? true : false;
}

function error($Type) {
		header('Content-type: image/gif');
		die(file_get_contents(SERVER_ROOT.'/sections/image/'.$Type.'.gif'));
}

function invisible($Image) {
	$Count = imagecolorstotal($Image);
	if ($Count == 0) { return false; }
	$TotalAlpha = 0;
	for ($i=0; $i<$Count; ++$i) {
		$Color = imagecolorsforindex($Image,$i);
		$TotalAlpha += $Color['alpha'];
	}
	return (($TotalAlpha/$Count) == 127) ? true : false;
	
}

function is_number($Str) {
	$Return = true;
	if ($Str < 0) { $Return = false; }
	// We're converting input to a int, then string and comparing to original
	$Return = ($Str == strval(intval($Str)) ? true : false);
	return $Return;
}

function verysmall($Image) {
	return ((imagesx($Image) * imagesy($Image)) < 25) ? true : false;
}

function image_type($Data) {
	if(!strncmp($Data,'GIF',3)) {
		return 'gif';
	}
	if(!strncmp($Data,pack('H*','89504E47'),4)) {
		return 'png';
	}
	if(!strncmp($Data,pack('H*','FFD8'),2)) {
		return 'jpeg';
	}
	if(!strncmp($Data,'BM',2)) {
		return 'bmp';
	}
	if(!strncmp($Data,'II',2) || !strncmp($Data,'MM',2)) {
		return 'tiff';
	}
}

function image_height($Type, $Data) {
	$Length = strlen($Data);
	global $URL, $_GET;
	switch($Type) {
		case 'jpeg':
			// See http://www.obrador.com/essentialjpeg/headerinfo.htm
			$i = 4;
			$Data = (substr($Data, $i));
			$Block = unpack('nLength', $Data);
			$Data = substr($Data, $Block['Length']);
			$i+=$Block['Length'];
			$Str []= "Started 4, + ".$Block['Length'];
			while($Data!='') { // iterate through the blocks until we find the start of frame marker (FFC0)
				$Block = unpack('CBlock/CType/nLength', $Data); // Get info about the block
				if($Block['Block'] != '255') { break; } // We should be at the start of a new block
				if($Block['Type'] != '192') { // C0
					$Data = substr($Data, $Block['Length']+2); // Next block
					$Str []= "Started ".$i.", + ".($Block['Length']+2);
					$i+=($Block['Length']+2);
				} else { // We're at the FFC0 block
					$Data = substr($Data, 5); // Skip FF C0 Length(2) precision(1)
					$i+=5;
					$Height = unpack('nHeight', $Data);
					return $Height['Height'];
				}
			}
			break;
		case 'gif':
			$Data = substr($Data, 8);
			$Height = unpack('vHeight', $Data);
			return $Height['Height'];
		case 'png':
			$Data = substr($Data, 20);
			$Height = unpack('NHeight', $Data);
			return $Height['Height'];
		default:
			return 0;
	}
}


function send_pm($ToID,$FromID,$Subject,$Body,$ConvID='') {
	global $DB, $Cache;
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
	} else {
		$DB->query("UPDATE pm_conversations_users SET
				InInbox='1',
				UnRead='1',
				ReceivedDate='".sqltime()."'
				WHERE UserID='$ToID'
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
	$DB->query("SELECT COUNT(ConvID) FROM pm_conversations_users WHERE UnRead = '1' and UserID='$ToID' AND InInbox = '1'");
	list($UnRead) = $DB->next_record(MYSQLI_BOTH, FALSE);
	$Cache->cache_value('inbox_new_'.$ToID, $UnRead);

	//if ($UnRead == 0) {
	//	$Cache->increment('inbox_new_'.$ToID);
	//}
	return $ConvID;
}

function send_irc($Raw) {
	$IRCSocket = fsockopen(SOCKET_LISTEN_ADDRESS, SOCKET_LISTEN_PORT);
	fwrite($IRCSocket, $Raw);
	fclose($IRCSocket);
}

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

require(SERVER_ROOT.'/sections/image/index.php');
?>
