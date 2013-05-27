<?
/**
 * Torrent class that contains some convenient functions related to torrent meta data
 */
class BencodeTorrent extends BencodeDecode {
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
			$this->Size = (Int64::is_int($InfoDict['length'])
				? Int64::get($InfoDict['length'])
				: $InfoDict['length']);
			$Name = (isset($InfoDict['name.utf-8'])
				? $InfoDict['name.utf-8']
				: $InfoDict['name']);
			$this->Files[] = array($this->Size, $Name);
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
			uasort($this->Files, function($a, $b) {
					return strnatcasecmp($a[1], $b[1]);
				});
		}
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
