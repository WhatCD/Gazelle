<?
/**
 * If we're running a 32bit PHP version, we use small objects to store ints.
 * Overhead from the function calls is small enough to not worry about
 */
class Int64 {
	private $Num;

	public function __construct($Val) {
		$this->Num = $Val;
	}

	public static function make($Val) {
		return PHP_INT_SIZE === 4 ? new Int64($Val) : (int)$Val;
	}

	public static function get($Val) {
		return PHP_INT_SIZE === 4 ? $Val->Num : $Val;
	}

	public static function is_int($Val) {
		return is_int($Val) || (is_object($Val) && get_class($Val) === 'Int64');
	}
}

/**
 * The encode class is simple and straightforward. The only thing to
 * note is that empty dictionaries are represented by boolean trues
 */
class BEnc {
	private $DefaultKeys = array( // Get rid of everything except these keys to save some space
			'created by', 'creation date', 'encoding', 'info');
	private $Data;
	public $Enc;

	/**
	 * Encode an arbitrary array (usually one that's just been decoded)
	 *
	 * @param array $Arg the thing to encode
	 * @param mixed $Keys string or array with keys in the input array to encode or true to encode everything
	 * @return bencoded string representing the content of the input array
	 */
	public function encode($Arg = false, $Keys = false) {
		if ($Arg === false) {
			$Data =& $this->Dec;
		} else {
			$Data =& $Arg;
		}
		if ($Keys === true) {
			$this->Data = $Data;
		} else if ($Keys === false) {
			$this->Data = array_intersect_key($Data, array_flip($this->DefaultKeys));
		} else if (is_array($Keys)) {
			$this->Data = array_intersect_key($Data, array_flip($Keys));
		} else {
			$this->Data = isset($Data[$Keys]) ? $Data[$Keys] : false;
		}
		if (!$this->Data) {
			return false;
		}
		$this->Enc = $this->_benc();
		return $this->Enc;
	}

	/**
	 * Internal encoding function that does the actual job
	 *
	 * @return bencoded string
	 */
	private function _benc() {
		if (!is_array($this->Data)) {
			if (Int64::is_int($this->Data)) { // Integer
				return 'i'.Int64::get($this->Data).'e';
			}
			if ($this->Data === true) { // Empty dictionary
				return 'de';
			}
			return strlen($this->Data).':'.$this->Data; // String
		}
		if (empty($this->Data) || Int64::is_int(key($this->Data))) {
			$IsDict = false;
		} else {
			$IsDict = true;
			ksort($this->Data); // Dictionaries must be sorted
		}
		$Ret = $IsDict ? 'd' : 'l';
		foreach ($this->Data as $Key => $Value) {
			if ($IsDict) {
				$Ret .= strlen($Key).':'.$Key;
			}
			$this->Data = $Value;
			$Ret .= $this->_benc();
		}
		return $Ret.'e';
	}
}

/**
 * The decode class is simple and straightforward. The only thing to
 * note is that empty dictionaries are represented by boolean trues
 */
class BEncDec extends BEnc {
	private $Data;
	private $Length;
	private $Pos = 0;
	public $Dec = array();
	public $ExitOnError = true;
	const SnipLength = 40;

	/**
	 * Decode prepararations
	 *
	 * @param string $Arg bencoded string or path to bencoded file to decode
	 * @param bool $IsPath needs to be true if $Arg is a path
	 * @return decoded data with a suitable structure
	 */
	function __construct($Arg = false, $IsPath = false) {
		if ($Arg === false) {
			if (empty($this->Enc)) {
				return false;
			}
		} else {
			if ($IsPath === true) {
				return $this->bdec_file($Arg);
			}
			$this->Data = $Arg;
		}
		return $this->decode();
	}

	/**
	 * Decodes a bencoded file
	 *
	 * @param $Path path to bencoded file to decode
	 * @return decoded data with a suitable structure
	 */
	public function bdec_file($Path = false) {
		if (empty($Path)) {
			return false;
		}
		if (!$this->Data = @file_get_contents($Path, FILE_BINARY)) {
			return $this->error("Error: file '$Path' could not be opened.\n");
		}
		return $this->decode();
	}

	/**
	 * Decodes a string with bencoded data
	 *
	 * @param mixed $Arg bencoded data or false to decode the content of $this->Data
	 * @return decoded data with a suitable structure
	 */
	public function decode($Arg = false) {
		if ($Arg !== false) {
			$this->Data = $Arg;
		} else if (!$this->Data) {
			$this->Data = $this->Enc;
		}
		if (!$this->Data) {
			return false;
		}
		$this->Length = strlen($this->Data);
		$this->Pos = 0;
		$this->Dec = $this->_bdec();
		if ($this->Pos < $this->Length) {
			// Not really necessary, but if the torrent is invalid, it's better to warn than to silently truncate it
			return $this->error();
		}
		return $this->Dec;
	}

	/**
	 * Internal decoding function that does the actual job
	 *
	 * @return decoded data with a suitable structure
	 */
	private function _bdec() {
		switch ($this->Data[$this->Pos]) {

			case 'i':
				$this->Pos++;
				$Value = substr($this->Data, $this->Pos, strpos($this->Data, 'e', $this->Pos) - $this->Pos);
				if (!ctype_digit($Value) && !($Value[0] == '-' && ctype_digit(substr($Value, 1)))) {
					return $this->error();
				}
				$this->Pos += strlen($Value) + 1;
				return Int64::make($Value);

			case 'l':
				$Value = array();
				$this->Pos++;
				while ($this->Data[$this->Pos] != 'e') {
					if ($this->Pos >= $this->Length) {
						return $this->error();
					}
					$Value[] = $this->_bdec();
				}
				$this->Pos++;
				return $Value;

			case 'd':
				$Value = array();
				$this->Pos++;
				while ($this->Data[$this->Pos] != 'e') {
					$Length = substr($this->Data, $this->Pos, strpos($this->Data, ':', $this->Pos) - $this->Pos);
					if (!ctype_digit($Length)) {
						return $this->error();
					}
					$this->Pos += strlen($Length) + $Length + 1;
					$Key = substr($this->Data, $this->Pos - $Length, $Length);
					if ($this->Pos >= $this->Length) {
						return $this->error();
					}
					$Value[$Key] = $this->_bdec();
				}
				$this->Pos++;
				// Use boolean true to keep track of empty dictionaries
				return empty($Value) ? true : $Value;

			default:
				$Length = substr($this->Data, $this->Pos, strpos($this->Data, ':', $this->Pos) - $this->Pos);
				if (!ctype_digit($Length)) {
					return $this->error(); // Even if the string is likely to be decoded correctly without this check, it's malformed
				}
				$this->Pos += strlen($Length) + $Length + 1;
				return substr($this->Data, $this->Pos - $Length, $Length);
		}
	}

	/**
	 * Convert everything to the correct data types and optionally escape strings
	 *
	 * @param bool $Escape whether to escape the textual data
	 * @param mixed $Data decoded data or false to use the $Dec property
	 * @return decoded data with more useful data types
	 */
	public function dump($Escape = true, $Data = false) {
		if ($Data === false) {
			$Data = $this->Dec;
		}
		if (Int64::is_int($Data)) {
			return Int64::get($Data);
		}
		if (is_bool($Data)) {
			return array();
		}
		if (is_array($Data)) {
			$Output = array();
			foreach ($Data as $Key => $Val) {
				$Output[$Key] = $this->dump($Escape, $Val);
			}
			return $Output;
		}
		return $Escape ? htmlentities($Data) : $Data;
	}

	/**
	 * Display an error and halt the operation unless the $ExitOnError property is false
	 *
	 * @param string $ErrMsg the error message to display
	 */
	private function error($ErrMsg = false) {
		static $ErrorPos;
		if ($this->Pos === $ErrorPos) {
			// The recursive nature of the class requires this to avoid duplicate error messages
			return false;
		}
		if ($ErrMsg === false) {
			printf("Malformed string. Invalid character at pos 0x%X: %s\n",
					$this->Pos, str_replace(array("\r","\n"), array('',' '), htmlentities(substr($this->Data, $this->Pos, self::SnipLength))));
		} else {
			echo $ErrMsg;
		}
		if ($this->ExitOnError) {
			exit();
		}
		$ErrorPos = $this->Pos;
		return false;
	}
}

/**
 * Torrent class that contains some convenient functions related to torrent meta data
 */
class BEncTorrent extends BEncDec {
	private $PathKey = 'path';
	public $Files = array();
	public $Size = 0;

	/**
	 * Create a list of the files in the torrent and their sizes as well as the total torrent size
	 *
	 * @return array with a list of files and file sizes
	 */
	public function file_list() {
		if (empty($this->Dec)) {
			return false;
		}
		$InfoDict =& $this->Dec['info'];
		if (!isset($InfoDict['files'])) {
			// Single-file torrent
			$this->Files = array(
				$this->Size = (Int64::is_int($InfoDict['length'])
					? Int64::get($InfoDict['length'])
					: $InfoDict['length']),
				$this->Files = (isset($InfoDict['name.utf-8'])
					? $InfoDict['name.utf-8']
					: $InfoDict['name'])
			);
		} else {
			if (isset($InfoDict['path.utf-8']['files'][0])) {
				$this->PathKey = 'path.utf-8';
			}
			foreach ($InfoDict['files'] as $File) {
				$TmpPath = array();
				foreach ($File[$this->PathKey] as $SubPath) {
					$TmpPath[] = $SubPath;
				}
				$CurSize = (Int64::is_int($File['length'])
					? Int64::get($File['length'])
					: $File['length']);
				$this->Files[] = array($CurSize, implode('/', $TmpPath));
				$this->Size += $CurSize;
			}
		}
		uasort($this->Files, function($a, $b) {
				return strnatcasecmp($a[1], $b[1]);
			});
		return array($this->Size, $this->Files);
	}

	/**
	 * Find out the name of the torrent
	 *
	 * @return string torrent name
	 */
	public function get_name() {
		if (empty($this->Dec)) {
			return false;
		}
		if (isset($this->Dec['info']['name.utf-8'])) {
			return $this->Dec['info']['name.utf-8'];
		}
		return $this->Dec['info']['name'];
	}

	/**
	 * Find out the total size of the torrent
	 *
	 * @return string torrent size
	 */
	public function get_size() {
		if (empty($this->Files)) {
			if (empty($this->Dec)) {
				return false;
			}
			$FileList = $this->file_list();
		}
		return $FileList[0];
	}

	/**
	 * Checks if the "private" flag is present in the torrent
	 *
	 * @return true if the "private" flag is set
	 */
	public function is_private() {
		if (empty($this->Dec)) {
			return false;
		}
		return isset($this->Dec['info']['private']) && Int64::get($this->Dec['info']['private']) == 1;
	}
	/**
	 * Add the "private" flag to the torrent
	 *
	 * @return true if a change was required
	 */
	public function make_private() {
		if (empty($this->Dec)) {
			return false;
		}
		if ($this->is_private()) {
			return false;
		}
		$this->Dec['info']['private'] = Int64::make(1);
		ksort($this->Dec['info']);
		return true;
	}

	/**
	 * Calculate the torrent's info hash
	 *
	 * @return info hash in hexadecimal form
	 */
	public function info_hash() {
		if (empty($this->Dec) || !isset($this->Dec['info'])) {
			return false;
		}
		return sha1($this->encode(false, 'info'));
	}

	/**
	 * Add the announce URL to a torrent
	 */
	public static function add_announce_url($Data, $Url) {
		return 'd8:announce'.strlen($Url).':'.$Url . substr($Data, 1);
	}
}
?>
