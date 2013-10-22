<?
// Functions and headers needed by the image proxy
error_reporting(E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR);

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
	header("HTTP/1.1 304 Not Modified");
	die();
}

header('Expires: '.date('D, d-M-Y H:i:s \U\T\C', time() + 3600 * 24 * 120)); // 120 days
header('Last-Modified: '.date('D, d-M-Y H:i:s \U\T\C', time()));

if (!extension_loaded('gd')) {
	error('nogd');
}

function img_error($Type) {
	header('Content-type: image/gif');
	die(file_get_contents(SERVER_ROOT.'/sections/image/'.$Type.'.gif'));
}

function invisible($Image) {
	$Count = imagecolorstotal($Image);
	if ($Count == 0) {
		return false;
	}
	$TotalAlpha = 0;
	for ($i = 0; $i < $Count; ++$i) {
		$Color = imagecolorsforindex($Image, $i);
		$TotalAlpha += $Color['alpha'];
	}
	return (($TotalAlpha / $Count) == 127) ? true : false;

}

function verysmall($Image) {
	return ((imagesx($Image) * imagesy($Image)) < 25) ? true : false;
}

function image_type($Data) {
	if (!strncmp($Data, 'GIF', 3)) {
		return 'gif';
	}
	if (!strncmp($Data, pack('H*', '89504E47'), 4)) {
		return 'png';
	}
	if (!strncmp($Data, pack('H*', 'FFD8'), 2)) {
		return 'jpeg';
	}
	if (!strncmp($Data, 'BM', 2)) {
		return 'bmp';
	}
	if (!strncmp($Data, 'II', 2) || !strncmp($Data, 'MM', 2)) {
		return 'tiff';
	}
}

function image_height($Type, $Data) {
	$Length = strlen($Data);
	global $URL, $_GET;
	switch ($Type) {
		case 'jpeg':
			// See http://www.obrador.com/essentialjpeg/headerinfo.htm
			$i = 4;
			$Data = (substr($Data, $i));
			$Block = unpack('nLength', $Data);
			$Data = substr($Data, $Block['Length']);
			$i += $Block['Length'];
			$Str []= "Started 4, + ".$Block['Length'];
			while ($Data != '') { // iterate through the blocks until we find the start of frame marker (FFC0)
				$Block = unpack('CBlock/CType/nLength', $Data); // Get info about the block
				if ($Block['Block'] != '255') { // We should be at the start of a new block
					break;
				}
				if ($Block['Type'] != '192') { // C0
					$Data = substr($Data, $Block['Length'] + 2); // Next block
					$Str []= "Started $i, + ".($Block['Length'] + 2);
					$i += ($Block['Length'] + 2);
				} else { // We're at the FFC0 block
					$Data = substr($Data, 5); // Skip FF C0 Length(2) precision(1)
					$i += 5;
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

define('SKIP_NO_CACHE_HEADERS', 1);
require('classes/script_start.php'); // script_start contains all we need and includes sections/image/index.php
?>
