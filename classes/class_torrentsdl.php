<?
/**
 * Class for functions related to the features involving torrent downloads
 */
class TorrentsDL {
	const ChunkSize = 100;
	private $QueryResult;
	private $QueryRowNum = 0;
	private $Zip;
	private $IDBoundaries;
	private $FailedFiles = array();
	private $NumAdded = 0;
	private $NumFound = 0;
	private $Size = 0;
	private $Title;

	/**
	 * Create a Zip object and store the query results
	 *
	 * @param mysqli_result $QueryResult results from a query on the collector pages
	 * @param string $Title name of the collection that will be created
	 */
	public function __construct(&$QueryResult, $Title) {
		Zip::unlimit(); // Need more memory and longer timeout
		$this->QueryResult = $QueryResult;
		$this->Title = $Title;
		$this->Zip = new Zip(Misc::file_string($Title));
	}

	/**
	 * Store the results from a DB query in smaller chunks to save memory
	 *
	 * @param string $Key the key to use in the result hash map
	 * @return array with results and torrent group IDs or false if there are no results left
	 */
	public function get_downloads($Key) {
		global $DB;
		$GroupIDs = $Downloads = array();
		$OldQuery = $DB->get_query_id();
		$DB->set_query_id($this->QueryResult);
		if (!isset($this->IDBoundaries)) {
			if ($Key == 'TorrentID') {
				$this->IDBoundaries = false;
			} else {
				$this->IDBoundaries = $DB->to_pair($Key, 'TorrentID', false);
			}
		}
		$Found = 0;
		while ($Download = $DB->next_record(MYSQLI_ASSOC, false)) {
			if (!$this->IDBoundaries || $Download['TorrentID'] == $this->IDBoundaries[$Download[$Key]]) {
				$Found++;
				$Downloads[$Download[$Key]] = $Download;
				$GroupIDs[$Download['TorrentID']] = $Download['GroupID'];
				if ($Found >= self::ChunkSize) {
					break;
				}
			}
		}
		$this->NumFound += $Found;
		$DB->set_query_id($OldQuery);
		if (empty($Downloads)) {
			return false;
		}
		return array($Downloads, $GroupIDs);
	}

	/**
	 * Add a file to the zip archive
	 *
	 * @param string $Content file content
	 * @param array $FileInfo file info stored as an array with at least the keys
	 *  Artist, Name, Year, Media, Format, Encoding and TorrentID
	 * @param string $FolderName folder name
	 */
	public function add_file($Content, $FileInfo, $FolderName = '') {
		$FileName = self::construct_file_name($FileInfo['Artist'], $FileInfo['Name'], $FileInfo['Year'], $FileInfo['Media'], $FileInfo['Format'], $FileInfo['Encoding'], $FileInfo['TorrentID']);
		$this->Size += $FileInfo['Size'];
		$this->NumAdded++;
		$this->Zip->add_file($Content, ($FolderName ? "$FolderName/" : "") . $FileName);
		usleep(25000); // We don't want to send much faster than the client can receive
	}

	/**
	 * Add a file to the list of files that could not be downloaded
	 *
	 * @param array $FileInfo file info stored as an array with at least the keys Artist, Name and Year
	 */
	public function fail_file($FileInfo) {
		$this->FailedFiles[] = $FileInfo['Artist'] . $FileInfo['Name'] . " $FileInfo[Year]";
	}

	/**
	 * Add a file to the list of files that did not match the user's format or quality requirements
	 *
	 * @param array $FileInfo file info stored as an array with at least the keys Artist, Name and Year
	 */
	public function skip_file($FileInfo) {
		$this->SkippedFiles[] = $FileInfo['Artist'] . $FileInfo['Name'] . " $FileInfo[Year]";
	}

	/**
	 * Add a summary to the archive and include a list of files that could not be added. Close the zip archive
	 *
	 * @param int $Analyzed number of files that were analyzed (e.g. number of groups in a collage)
	 * @param int $Skips number of files that did not match any of the user's criteria
	 */
	public function finalize($FilterStats = true) {
		$this->Zip->add_file($this->summary($FilterStats), "Summary.txt");
		if (!empty($this->FailedFiles)) {
			$this->Zip->add_file($this->errors(), "Errors.txt");
		}
		$this->Zip->close_stream();
	}

	/**
	 * Produce a summary text over the collector results
	 *
	 * @param bool $FilterStats whether to include filter stats in the report
	 * @return summary text
	 */
	public function summary($FilterStats) {
		global $LoggedUser, $ScriptStartTime;
		$Time = number_format(1000 * (microtime(true) - $ScriptStartTime), 2)." ms";
		$Used = Format::get_size(memory_get_usage(true));
		$Date = date("M d Y, H:i");
		$NumSkipped = count($this->SkippedFiles);
		return "Collector Download Summary for $this->Title - ".SITE_NAME."\r\n"
			. "\r\n"
			. "User:		$LoggedUser[Username]\r\n"
			. "Passkey:	$LoggedUser[torrent_pass]\r\n"
			. "\r\n"
			. "Time:		$Time\r\n"
			. "Used:		$Used\r\n"
			. "Date:		$Date\r\n"
			. "\r\n"
			. ($FilterStats !== false
				? "Torrent groups analyzed:	$this->NumFound\r\n"
					. "Torrent groups filtered:	$NumSkipped\r\n"
				: "")
			. "Torrents downloaded:		$this->NumAdded\r\n"
			. "\r\n"
			. "Total size of torrents (ratio hit): ".Format::get_size($this->Size)."\r\n"
			. ($NumSkipped
				? "\r\n"
					. "Albums unavailable within your criteria (consider making a request for your desired format):\r\n"
					. implode("\r\n", $this->SkippedFiles) . "\r\n"
				: "");
	}

	/**
	 * Compile a list of files that could not be added to the archive
	 *
	 * @return list of files
	 */
	public function errors() {
		return "A server error occurred. Please try again at a later time.\r\n"
			. "\r\n"
			. "The following torrents could not be downloaded:\r\n"
			. implode("\r\n", $this->FailedFiles) . "\r\n";
	}

	/**
	 * Combine a bunch of torrent info into a standardized file name
	 *
	 * @params most input variables are mostly self-explanatory
	 * @param int $TorrentID if given, append "-TorrentID" to torrent name
	 * @param bool $TxtExtension whether to use .txt or .torrent as file extension
	 * @return file name with at most 180 characters that is valid on most systems
	 */
	public static function construct_file_name($Artist, $Album, $Year, $Media, $Format, $Encoding, $TorrentID = false, $TxtExtension = false) {
		$TorrentName = Misc::file_string($Album);
		if ($Year > 0) {
			$TorrentName .= " - $Year";
		}
		$TorrentInfo = array();
		if ($Media != '') {
			$TorrentInfo[] = $Media;
		}
		if ($Format != '') {
			$TorrentInfo[] = $Format;
		}
		if ($Encoding != '') {
			$TorrentInfo[] = $Encoding;
		}
		if (!empty($TorrentInfo)) {
			$TorrentInfo = " (" . Misc::file_string(implode(" - ", $TorrentInfo)) . ")";
		} else {
			$TorrentInfo = "";
		}

		if (!$TorrentName) {
			$TorrentName = "No Name";
		} else if (strlen($TorrentName . $TorrentInfo) <= 197) {
			$TorrentName = Misc::file_string($Artist) . $TorrentName;
		}

		// Leave some room to the user in case the file system limits the path length
		$MaxLength = $TxtExtension ? 196 : 192;
		if ($TorrentID) {
			$MaxLength -= 8;
		}
		$TorrentName = Format::cut_string($TorrentName . $TorrentInfo, $MaxLength, true, false);
		if ($TorrentID) {
			$TorrentName .= "-$TorrentID";
		}
		if ($TxtExtension) {
			return "$TorrentName.txt";
		}
		return "$TorrentName.torrent";
	}
}
