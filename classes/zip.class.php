<?
/*************************************************************************|
|--------------- Zip class -----------------------------------------------|
|*************************************************************************|

This class provides a convenient way for us to generate and serve zip
archives to our end users, both from physical files, cached
or already parsed data (torrent files). It's all done on the fly, due to
the high probability that a filesystem stored archive will never be
downloaded twice.

Utilizes gzcompress, based upon RFC 1950

//------------- How it works --------------//

Basic concept is construct archive, add files, and serve on the fly.

//------------- How to use it --------------//

* First, construct the archive:

$Zip = new Zip('FileName');

	Adds the headers so that add_file can stream and we don't need to create a massive buffer.
	open_stream(); was integrated into the constructor to conform with Object-Oriented Standards.

$Zip->unlimit();

	A simple shortcut function for raising the basic PHP limits, time and memory for larger archives.

-----

* Then, add files and begin streaming to the user to avoid memory buffering:

$Zip->add_file(file_get_contents("data/file.txt"), "File.txt");

	Adds the contents of data/file.txt into File.txt in the archive root.

$Zip->add_file($TorrentData, "Bookmarks/Artist - Album [2008].torrent");

	Adds the parsed torrent to the archive in the Bookmarks folder (created simply by placing it in the path).

-----

* Then, close the archive to the user:

$Zip->close_stream();

	This collects everything put together thus far in the archive, and streams it to the user in the form of Test7.zip

//------ Explanation of basic functions ------//

add_file(Contents, Internal Path)
	Adds the contents to the archive, where it will be extracted to Internal Path.

close_stream();
	Collect and stream to the user.

//------------- Detailed example -------------//

require('classes/zip.class.php');
$Zip = new Zip('FileName');
$Name = 'Ubuntu-8.10';
$Zip->add_file($TorrentData, 'Torrents/'.Misc::file_string($Name).'.torrent');
$Zip->add_file(file_get_contents('zip.php'), 'zip.php');
$Zip->close_stream();


//---------- Development reference -----------//
http://www.pkware.com/documents/casestudies/APPNOTE.TXT - ZIP spec (this class)
http://www.ietf.org/rfc/rfc1950.txt - ZLIB compression spec (gzcompress function)
http://www.fileformat.info/tool/hexdump.htm - Useful for analyzing ZIP files

|*************************************************************************/

if (!extension_loaded('zlib')) {
	error('Zlib Extension not loaded.');
}
/*
//Handles timestamps
function dostime($TimeStamp = 0) {
	if (!is_number($TimeStamp)) { // Assume that $TimeStamp is SQL timestamp
		if ($TimeStamp == '0000-00-00 00:00:00') {
			return 'Never';
		}
		$TimeStamp = strtotime($TimeStamp);
	}
	$Date = (($TimeStamp == 0) ? getdate() : getdate($TimeStamp));
	$Hex = dechex((($Date['year'] - 1980) << 25) | ($Date['mon'] << 21) | ($Date['mday'] << 16) | ($Date['hours'] << 11) | ($Date['minutes'] << 5) | ($Date['seconds'] >> 1));
	eval("\$Return = \"\x$Hex[6]$Hex[7]\x$Hex[4]$Hex[5]\x$Hex[2]$Hex[3]\x$Hex[0]$Hex[1]\";");
	return $Return;
}
*/

class Zip {
	public $ArchiveSize = 0; // Total size
	public $ArchiveFiles = 0; // Total files
	private $Structure = ''; // Structure saved to memory
	private $FileOffset = 0; // Offset to write data
	private $Data = ''; //An idea

	public function __construct ($ArchiveName = 'Archive') {
		header("Content-type: application/octet-stream"); // Stream download
		header("Content-disposition: attachment; filename=\"$ArchiveName.zip\""); // Name the archive - Should not be urlencoded
	}

	public static function unlimit () {
		ob_end_clean();
		set_time_limit(3600); // Limit 1 hour
		ini_set('memory_limit', '1024M'); // Because the buffers can get extremely large
	}

	public function add_file ($FileData, $ArchivePath, $TimeStamp = 0) {
		/* File header */
		$this->Data = "\x50\x4b\x03\x04"; // PK signature
		$this->Data .= "\x14\x00"; // Version requirements
		$this->Data .= "\x00\x08"; // Bit flag - 0x8 = UTF-8 file names
		$this->Data .= "\x08\x00"; // Compression
		//$this->Data .= dostime($TimeStamp); // Last modified
		$this->Data .= "\x00\x00\x00\x00";
		$DataLength = strlen($FileData); // Saved as variable to avoid wasting CPU calculating it multiple times.
		$CRC32 = crc32($FileData); // Ditto.
		$ZipData = gzcompress($FileData); // Ditto.
		$ZipData = substr ($ZipData, 2, (strlen($ZipData) - 6)); // Checksum resolution
		$ZipLength = strlen($ZipData); // Ditto.
		$this->Data .= pack('V', $CRC32); // CRC-32
		$this->Data .= pack('V', $ZipLength); // Compressed file size
		$this->Data .= pack('V', $DataLength); // Uncompressed file size
		$this->Data .= pack('v', strlen($ArchivePath)); // Path name length
		$this->Data .="\x00\x00"; // Extra field length (0'd so we can ignore this)
		$this->Data .= $ArchivePath; // File name & Extra Field (length set to 0 so ignored)
		/* END file header */

		/* File data */
		$this->Data .= $ZipData; // File data
		/* END file data */

		/* Data descriptor
		Not needed (only needed when 3rd bitflag is set), causes problems with OS X archive utility
		$this->Data .= pack('V', $CRC32); // CRC-32
		$this->Data .= pack('V', $ZipLength); // Compressed file size
		$this->Data .= pack('V', $DataLength); // Uncompressed file size
		END data descriptor */

		$FileDataLength = strlen($this->Data);
		$this->ArchiveSize = $this->ArchiveSize + $FileDataLength; // All we really need is the size
		$CurrentOffset = $this->ArchiveSize; // Update offsets
		echo $this->Data; // Get this out to reduce our memory consumption

		/* Central Directory Structure */
		$CDS = "\x50\x4b\x01\x02"; // CDS signature
		$CDS .="\x14\x00"; // Constructor version
		$CDS .="\x14\x00"; // Version requirements
		$CDS .="\x00\x08"; // Bit flag - 0x8 = UTF-8 file names
		$CDS .="\x08\x00"; // Compression
		$CDS .="\x00\x00\x00\x00"; // Last modified
		$CDS .= pack('V', $CRC32); // CRC-32
		$CDS .= pack('V', $ZipLength); // Compressed file size
		$CDS .= pack('V', $DataLength); // Uncompressed file size
		$CDS .= pack('v', strlen($ArchivePath)); // Path name length
		$CDS .="\x00\x00"; // Extra field length (0'd so we can ignore this)
		$CDS .="\x00\x00"; // File comment length  (no comment, 0'd)
		$CDS .="\x00\x00"; // Disk number start (0 seems valid)
		$CDS .="\x00\x00"; // Internal file attributes (again with the 0's)
		$CDS .="\x20\x00\x00\x00"; // External file attributes
		$CDS .= pack('V', $this->FileOffset); // Offsets
		$CDS .= $ArchivePath; // File name & Extra Field (length set to 0 so ignored)
		/* END central Directory Structure */

		$this->FileOffset = $CurrentOffset; // Update offsets
		$this->Structure .= $CDS; // Append to structure
		$this->ArchiveFiles++; // Increment file count
	}

	public function close_stream() {
		echo $this->Structure; // Structure Root
		echo "\x50\x4b\x05\x06"; // End of central directory signature
		echo "\x00\x00"; // This disk
		echo "\x00\x00"; // CDS start
		echo pack('v', $this->ArchiveFiles); // Handle the number of entries
		echo pack('v', $this->ArchiveFiles); // Ditto
		echo pack('V', strlen($this->Structure)); //Size
		echo pack('V', $this->ArchiveSize); // Offset
		echo "\x00\x00"; // No comment, close it off
	}
}
