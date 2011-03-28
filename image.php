<?
/*-- Image Start Class ---------------------------------*/
/*------------------------------------------------------*/
/* Simplified version of script_start, used for the	 */
/* sitewide image proxy.								*/
/*------------------------------------------------------*/
/********************************************************/

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


require(SERVER_ROOT.'/sections/image/index.php');
?>
