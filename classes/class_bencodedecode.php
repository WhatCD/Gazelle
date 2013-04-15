<?
/**
 * The decode class is simple and straightforward. The only thing to
 * note is that empty dictionaries are represented by boolean trues
 */
class BencodeDecode extends Bencode {
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
		} elseif (!$this->Data) {
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
