<?
/*******************************************************************************
|~~~~ Gazelle bencode parser                                               ~~~~|
--------------------------------------------------------------------------------

Welcome to the Gazelle bencode parser. bencoding is the way of encoding data
that bittorrent uses in torrent files. When we read the torrent files, we get
one long string that must be parsed into a format we can easily edit - that's
where this file comes into play.

There are 4 data types in bencode:
* String
* Int
* List - array without keys
	- like array('value', 'value 2', 'value 3', 'etc')
* Dictionary - array with string keys
	- like array['key 1'] = 'value 1'; array['key 2'] = 'value 2';

Before you go any further, we recommend reading the sections on bencoding and
metainfo file structure here: http://wiki.theory.org/BitTorrentSpecification

//----- How we store the data -----//

* Strings
	- Stored as php strings. Not difficult to remember.

* Integers
	- Stored as php strings with an [*INT*] marker
	- Can be stored an an int on 64 bit boxes for uber speed (we do this)
	- If stored as an int on 32 bit boxes, it won't allow for any size over 2 gigs

* Lists
	- Stored as a BENCODE_LIST object.
	- The actual list is in BENCODE_LIST::$Val, as an array with incrementing integer indices
	- The list in BENCODE_LIST::$Val is populated by the BENCODE_LIST::dec() function

* Dictionaries
	- Stored as a BENCODE_DICT object.
	- The actual list is in BENCODE_DICT::$Val, as an array with incrementing integer indices
	- The list in BENCODE_DICT::$Val is populated by the BENCODE_DICT::dec() function

//----- BENCODE_* Objects -----//

Lists and dictionaries are stored as objects. They each have the following
functions:

* decode(Type, $Key)
	- Decodes ANY bencoded element, given the type and the key
	- Gets the position and string from $this

* encode($Val)
	- Encodes ANY non-bencoded element, given the value

* dec()
	- Decodes either a dictionary or a list, depending on where it's called from
	- Uses the decode() function quite a bit

* enc()
	- Encodes either a dictionary or a list, depending on where it's called from
	- Relies mostly on the encode() function

Finally, as all torrents are just large dictionaries, the TORRENT class extends
the BENCODE_DICT class.



**Note** The version we run doesn't store ints as strings marked with [*INT*]
We store them as php integers. You can do this too for added speed and reduced
hackery, if you're running a 64 bit box, or if you're running a 32 bit box and
don't care about files larger than 2 gigs. The system with the [*INT*]s was
coded up in around 4 minutes for STC when we discovered this problem, then
discovered that floats aren't accurate enough to use. :(

*******************************************************************************/
class BENCODE2 {
	var $Val; // Decoded array
	var $Pos = 1; // Pointer that indicates our position in the string
	var $Str = ''; // Torrent string

	function __construct($Val, $IsParsed = false) {
		if (!$IsParsed) {
			$this->Str = $Val;
			$this->dec();
		} else {
			$this->Val = $Val;
		}
	}

	// Decode an element based on the type
	function decode($Type, $Key) {
		if (ctype_digit($Type)) { // Element is a string
			// Get length of string
			$StrLen = $Type;
			while ($this->Str[$this->Pos + 1] != ':') {
				$this->Pos++;
				$StrLen.=$this->Str[$this->Pos];
			}
			$this->Val[$Key] = substr($this->Str, $this->Pos + 2, $StrLen);

			$this->Pos += $StrLen;
			$this->Pos += 2;

		} elseif ($Type == 'i') { // Element is an int
			$this->Pos++;

			// Find end of integer (first occurance of 'e' after position)
			$End = strpos($this->Str, 'e', $this->Pos);

			// Get the integer, and mark it as an int (on our version 64 bit box, we cast it to an int)
			$this->Val[$Key] = '[*INT*]'.substr($this->Str, $this->Pos, $End-$this->Pos);
			$this->Pos = $End + 1;

		} elseif ($Type == 'l') { // Element is a list
			$this->Val[$Key] = new BENCODE_LIST(substr($this->Str, $this->Pos));
			$this->Pos += $this->Val[$Key]->Pos;

		} elseif ($Type == 'd') { // Element is a dictionary
			$this->Val[$Key] = new BENCODE_DICT(substr($this->Str, $this->Pos));
			$this->Pos += $this->Val[$Key]->Pos;
			// Sort by key to respect spec
			ksort($this->Val[$Key]->Val);

		} else {
			die('Invalid torrent file');
		}
	}

	function encode($Val) {
		if (is_string($Val)) {
			if (substr($Val, 0, 7) == '[*INT*]') {
				return 'i'.substr($Val,7).'e';
			} else {
				return strlen($Val).':'.$Val;
			}
		} elseif (is_object($Val)) {
			return $Val->enc();
		} else {
			return 'fail';
		}
	}
}

class BENCODE_LIST extends BENCODE2 {
	function enc() {
		$Str = 'l';
		reset($this->Val);
		while (list($Key, $Value) = each($this->Val)) {
			$Str.=$this->encode($Value);
		}
		return $Str.'e';
	}

	// Decode a list
	function dec() {
		$Key = 0; // Array index
		$Length = strlen($this->Str);
		while ($this->Pos<$Length) {
			$Type = $this->Str[$this->Pos];
			// $Type now indicates what type of element we're dealing with
			// It's either an integer (string), 'i' (an integer), 'l' (a list), 'd' (a dictionary), or 'e' (end of dictionary/list)

			if ($Type == 'e') { // End of list
				$this->Pos += 1;
				unset($this->Str); // Since we're finished parsing the string, we don't need to store it anymore. Benchmarked - this makes the parser run way faster.
				return;
			}

			// Decode the bencoded element.
			// This function changes $this->Pos and $this->Val, so you don't have to.
			$this->decode($Type, $Key);
			++ $Key;
		}
		return true;
	}
}

class BENCODE_DICT extends BENCODE2 {
	function enc() {
		$Str = 'd';
		reset($this->Val);
		while (list($Key, $Value) = each($this->Val)) {
			$Str.=strlen($Key).':'.$Key.$this->encode($Value);
		}
		return $Str.'e';
	}

	// Decode a dictionary
	function dec() {
		$Length = strlen($this->Str);
		while ($this->Pos < $Length) {

			if ($this->Str[$this->Pos] == 'e') { // End of dictionary
				$this->Pos += 1;
				unset($this->Str); // Since we're finished parsing the string, we don't need to store it anymore. Benchmarked - this makes the parser run way faster.
				return;
			}

			// Get the dictionary key
			// Length of the key, in bytes
			$KeyLen = $this->Str[$this->Pos];

			// Allow for multi-digit lengths
			while ($this->Str[$this->Pos + 1] != ':' && $this->Pos + 1 < $Length) {
				$this->Pos++;
				$KeyLen.=$this->Str[$this->Pos];
			}
			// $this->Pos is now on the last letter of the key length
			// Adding 2 brings it past that character and the ':' to the beginning of the string
			$this->Pos+=2;

			// Get the name of the key
			$Key = substr($this->Str, $this->Pos, $KeyLen);

			// Move the position past the key to the beginning of the element
			$this->Pos += $KeyLen;
			$Type = $this->Str[$this->Pos];
			// $Type now indicates what type of element we're dealing with
			// It's either an integer (string), 'i' (an integer), 'l' (a list), 'd' (a dictionary), or 'e' (end of dictionary/list)

			// Decode the bencoded element.
			// This function changes $this->Pos and $this->Val, so you don't have to.
			$this->decode($Type, $Key);


		}
		return true;
	}
}


class TORRENT extends BENCODE_DICT {
	function dump() {
		// Convenience function used for testing and figuring out how we store the data
		print_r($this->Val);
	}

	function dump_data() {
		// Function which serializes $this->Val for storage
		return base64_encode(serialize($this->Val));
	}

	function set_announce_url($Announce) {
		$this->Val['announce'] = $Announce;
		ksort($this->Val);
	}

	// Returns an array of:
	// 	* the files in the torrent
	//	* the total size of files described therein
	function file_list() {
		$FileList = array();
		if (!isset($this->Val['info']->Val['files'])) { // Single file mode
			$TotalSize = substr($this->Val['info']->Val['length'],7);
			$FileList[] = array($TotalSize, $this->get_name());
		} else { // Multiple file mode
			$FileNames = array();
			$FileSizes = array();
			$TotalSize = 0;
			$Files = $this->Val['info']->Val['files']->Val;
			if (isset($Files[0]->Val['path.utf-8'])) {
				$PathKey = 'path.utf-8';
			} else {
				$PathKey = 'path';
			}
			foreach ($Files as $File) {
				$FileSize = substr($File->Val['length'], 7);
				$TotalSize += $FileSize;

				$FileName = ltrim(implode('/', $File->Val[$PathKey]->Val), '/');
				$FileSizes[] = $FileSize;
				$FileNames[] = $FileName;
			}
			natcasesort($FileNames);
			foreach ($FileNames as $Index => $FileName) {
				$FileList[] = array($FileSizes[$Index], $FileName);
			}
		}
		return array($TotalSize, $FileList);
	}

	function get_name() {
		if (isset($this->Val['info']->Val['name.utf-8'])) {
			return $this->Val['info']->Val['name.utf-8'];
		} else {
			return $this->Val['info']->Val['name'];
		}
	}

	function make_private() {
		//----- The following properties do not affect the infohash:

		// anounce-list is an unofficial extension to the protocol
		// that allows for multiple trackers per torrent
		unset($this->Val['announce-list']);

		// Bitcomet & Azureus cache peers in here
		unset($this->Val['nodes']);

		// Azureus stores the dht_backup_enable flag here
		unset($this->Val['azureus_properties']);

		// Remove web-seeds
		unset($this->Val['url-list']);

		// Remove libtorrent resume info
		unset($this->Val['libtorrent_resume']);

		//----- End properties that do not affect the infohash

		if (!empty($this->Val['info']->Val['private']) && $this->Val['info']->Val['private'] == '[*INT*]1') {
			return true;
		} else {
			// Torrent is not private!
			// add private tracker flag and sort info dictionary
			$this->Val['info']->Val['private'] = '[*INT*]1';
			ksort($this->Val['info']->Val);
			return false;
		}
	}
}
?>
