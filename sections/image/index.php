<?
if (!check_perms('site_proxy_images')) {
	img_error('forbidden');
}

$URL = isset($_GET['i']) ? htmlspecialchars_decode($_GET['i']) : null;

if (!extension_loaded('openssl') && strtoupper($URL[4]) == 'S') {
	img_error('badprotocol');
}

if (!preg_match('/^'.IMAGE_REGEX.'/is', $URL, $Matches)) {
	img_error('invalid');
}

if (isset($_GET['c'])) {
	list($Data, $FileType) = $Cache->get_value('image_cache_'.md5($URL));
	$Cached = true;
}
if (!isset($Data) || !$Data) {
	$Cached = false;
	$Data = @file_get_contents($URL, 0, stream_context_create(array('http' => array('timeout' => 15))));
	if (!$Data || empty($Data)) {
		img_error('timeout');
	}
	$FileType = image_type($Data);
	if ($FileType && function_exists("imagecreatefrom$FileType")) {
		$Image = imagecreatefromstring($Data);
		if (invisible($Image)) {
			img_error('invisible');
		}
		if (verysmall($Image)) {
			img_error('small');
		}
	}

	if (isset($_GET['c']) && strlen($Data) < 262144) {
		$Cache->cache_value('image_cache_'.md5($URL), array($Data, $FileType), 3600 * 24 * 7);
	}
}
// Reset avatar, add mod note
function reset_image($UserID, $Type, $AdminComment, $PrivMessage) {
	if ($Type === 'avatar') {
		$CacheKey = "user_info_$UserID";
		$DBTable = 'users_info';
		$DBColumn = 'Avatar';
		$PMSubject = 'Your avatar has been automatically reset';
	} elseif ($Type === 'avatar2') {
		$CacheKey = "donor_info_$UserID";
		$DBTable = 'donor_rewards';
		$DBColumn = 'SecondAvatar';
		$PMSubject = 'Your second avatar has been automatically reset';
	} elseif ($Type === 'donoricon') {
		$CacheKey = "donor_info_$UserID";
		$DBTable = 'donor_rewards';
		$DBColumn = 'CustomIcon';
		$PMSubject = 'Your donor icon has been automatically reset';
	}

	$UserInfo = G::$Cache->get_value($CacheKey, true);
	if ($UserInfo !== false) {
		if ($UserInfo[$DBColumn] === '') {
			// This image has already been reset
			return;
		}
		$UserInfo[$DBColumn] = '';
		G::$Cache->cache_value($CacheKey, $UserInfo, 2592000); // cache for 30 days
	}

	// reset the avatar or donor icon URL
	G::$DB->query("
		UPDATE $DBTable
		SET $DBColumn = ''
		WHERE UserID = '$UserID'");

	// write comment to staff notes
	G::$DB->query("
		UPDATE users_info
		SET AdminComment = CONCAT('".sqltime().' - '.db_string($AdminComment)."\n\n', AdminComment)
		WHERE UserID = '$UserID'");

	// clear cache keys
	G::$Cache->delete_value($CacheKey);

	Misc::send_pm($UserID, 0, $PMSubject, $PrivMessage);
}

// Enforce avatar rules
if (isset($_GET['type']) && isset($_GET['userid'])) {
	$ValidTypes = array('avatar', 'avatar2', 'donoricon');
	if (!is_number($_GET['userid']) || !in_array($_GET['type'], $ValidTypes)) {
		die();
	}
	$UserID = $_GET['userid'];
	$Type = $_GET['type'];

	if ($Type === 'avatar' || $Type === 'avatar2') {
		$MaxFileSize = 256 * 1024; // 256 kB
		$MaxImageHeight = 400; // pixels
		$TypeName = $Type === 'avatar' ? 'avatar' : 'second avatar';
	} elseif ($Type === 'donoricon') {
		$MaxFileSize = 64 * 1024; // 64 kB
		$MaxImageHeight = 100; // pixels
		$TypeName = 'donor icon';
	}

	$Height = image_height($FileType, $Data);
	if (strlen($Data) > $MaxFileSize || $Height > $MaxImageHeight) {
		// Sometimes the cached image we have isn't the actual image
		if ($Cached) {
			$Data2 = @file_get_contents($URL, 0, stream_context_create(array('http' => array('timeout' => 15))));
		} else {
			$Data2 = $Data;
		}

		if (strlen($Data2) > $MaxFileSize || image_height($FileType, $Data2) > $MaxImageHeight) {
			require_once(SERVER_ROOT.'/classes/mysql.class.php');
			require_once(SERVER_ROOT.'/classes/time.class.php');
			$DBURL = db_string($URL);
			$AdminComment = ucfirst($TypeName)." reset automatically (Size: ".number_format((strlen($Data)) / 1024)." kB, Height: ".$Height."px). Used to be $DBURL";
			$PrivMessage = SITE_NAME." has the following requirements for {$TypeName}s:\n\n".
				"[b]".ucfirst($TypeName)."s must not exceed ".($MaxFileSize / 1024)." kB or be vertically longer than {$MaxImageHeight}px.[/b]\n\n".
				"Your $TypeName at $DBURL has been found to exceed these rules. As such, it has been automatically reset. You are welcome to reinstate your $TypeName once it has been resized down to an acceptable size.";
			reset_image($UserID, $Type, $AdminComment, $PrivMessage);
		}
	}
}

if (!isset($FileType)) {
	img_error('timeout');
}

header("Content-type: image/$FileType");
echo $Data;
