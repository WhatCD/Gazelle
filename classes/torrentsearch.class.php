<?
class TorrentSearch {
	const TAGS_ANY = 0;
	const TAGS_ALL = 1;
	const SPH_BOOL_AND = ' ';
	const SPH_BOOL_OR = ' | ';

	/**
	 * Map of sort mode => attribute name for ungrouped torrent page
	 */
	public static $SortOrders = array(
		'year' => 'year',
		'time' => 'id',
		'size' => 'size',
		'seeders' => 'seeders',
		'leechers' => 'leechers',
		'snatched' => 'snatched',
		'random' => 1);

	/**
	 * Map of sort mode => attribute name for grouped torrent page
	 */
	private static $SortOrdersGrouped = array(
		'year' => 'year',
		'time' => 'id',
		'size' => 'maxsize',
		'seeders' => 'sumseeders',
		'leechers' => 'sumleechers',
		'snatched' => 'sumsnatched',
		'random' => 1);

	/**
	 * Map of sort mode => aggregate expression required for some grouped sort orders
	 */
	private static $AggregateExp = array(
		'size' => 'MAX(size) AS maxsize',
		'seeders' => 'SUM(seeders) AS sumseeders',
		'leechers' => 'SUM(leechers) AS sumleechers',
		'snatched' => 'SUM(snatched) AS sumsnatched');

	/**
	 * Map of attribute name => global variable name with list of values that can be used for filtering
	 */
	private static $Attributes = array(
		'filter_cat' => false,
		'releasetype' => 'ReleaseTypes',
		'freetorrent' => false,
		'hascue' => false,
		'haslog' => false,
		'scene' => false,
		'vanityhouse' => false,
		'year' => false);

	/**
	 * List of fields that can be used for fulltext searches
	 */
	private static $Fields = array(
		'artistname' => 1,
		'cataloguenumber' => 1,
		'description' => 1,
		'encoding' => 1,
		'filelist' => 1,
		'format' => 1,
		'groupname' => 1,
		'media' => 1,
		'recordlabel' => 1,
		'remastercataloguenumber' => 1,
		'remasterrecordlabel' => 1,
		'remastertitle' => 1,
		'remasteryear' => 1,
		'searchstr' => 1,
		'taglist' => 1);

	/**
	 * List of torrent-specific fields that can be used for filtering
	 */
	private static $TorrentFields = array(
		'description' => 1,
		'encoding' => 1,
		'filelist' => 1,
		'format' => 1,
		'media' => 1,
		'remastercataloguenumber' => 1,
		'remasterrecordlabel' => 1,
		'remastertitle' => 1,
		'remasteryear' => 1);

	/**
	 * Some form field names don't match the ones in the index
	 */
	private static $FormsToFields = array(
		'searchstr' => '(groupname,artistname,yearfulltext)');

	/**
	 * Specify the operator type to use for fields. Empty key sets the default
	 */
	private static $FieldOperators = array(
		'' => self::SPH_BOOL_AND,
		'encoding' => self::SPH_BOOL_OR,
		'format' => self::SPH_BOOL_OR,
		'media' => self::SPH_BOOL_OR);

	/**
	 * Specify the separator character to use for fields. Empty key sets the default
	 */
	private static $FieldSeparators = array(
		'' => ' ',
		'encoding' => '|',
		'format' => '|',
		'media' => '|',
		'taglist' => ',');

	/**
	 * Primary SphinxqlQuery object used to get group IDs or torrent IDs for ungrouped searches
	 */
	private $SphQL;

	/**
	 * Second SphinxqlQuery object used to get torrent IDs if torrent-specific fulltext filters are used
	 */
	private $SphQLTor;

	/**
	 * Ordered result array or false if query resulted in an error
	 */
	private $SphResults;

	/**
	 * Requested page
	 */
	private $Page;

	/**
	 * Number of results per page
	 */
	private $PageSize;

	/**
	 * Number of results
	 */
	private $NumResults = 0;

	/**
	 * Array with info from all matching torrent groups
	 */
	private $Groups = array();

	/**
	 * True if the NOT operator can be used. Sphinx needs at least one positive search condition
	 */
	private $EnableNegation = false;

	/**
	 * Whether any filters were used
	 */
	private $Filtered = false;

	/**
	 * Whether the random sort order is selected
	 */
	private $Random = false;

	/**
	 * Storage for fulltext search terms
	 * ['Field name' => [
	 *     'include' => [],
	 *     'exclude' => [],
	 *     'operator' => self::SPH_BOOL_AND | self::SPH_BOOL_OR
	 * ]], ...
	 */
	private $Terms = array();

	/**
	 * Unprocessed search terms for retrieval
	 */
	private $RawTerms = array();

	/**
	 * Storage for used torrent-specific attribute filters
	 * ['Field name' => 'Search expression', ...]
	 */
	private $UsedTorrentAttrs = array();

	/**
	 * Storage for used torrent-specific fulltext fields
	 * ['Field name' => 'Search expression', ...]
	 */
	private $UsedTorrentFields = array();

	/**
	 * Initialize and configure a TorrentSearch object
	 *
	 * @param bool $GroupResults whether results should be grouped by group id
	 * @param string $OrderBy attribute to use for sorting the results
	 * @param string $OrderWay Whether to use ascending or descending order
	 * @param int $Page Page number to display
	 * @param int $PageSize Number of results per page
	 */
	public function __construct($GroupResults, $OrderBy, $OrderWay, $Page, $PageSize) {
		if ($GroupResults && !isset(self::$SortOrdersGrouped[$OrderBy])
				|| !$GroupResults && !isset(self::$SortOrders[$OrderBy])
				|| !in_array($OrderWay, array('asc', 'desc'))
		) {
			global $Debug;
			$ErrMsg = "TorrentSearch constructor arguments:\n" . print_r(func_get_args(), true);
			$Debug->analysis('Bad arguments in TorrentSearch constructor', $ErrMsg, 3600*24);
			error('-1');
		}
		if (!is_number($Page) || $Page < 1) {
			$Page = 1;
		}
		if (check_perms('site_search_many')) {
			$this->Page = $Page;
		} else {
			$this->Page = min($Page, SPHINX_MAX_MATCHES / $PageSize);
		}
		$ResultLimit = $PageSize;
		$this->PageSize = $PageSize;
		$this->GroupResults = $GroupResults;
		$this->SphQL = new SphinxqlQuery();
		if ($OrderBy === 'random') {
			$this->SphQL->select('id, groupid')
				->order_by('RAND()', '');
			$this->Random = true;
			$this->Page = 1;
			if ($GroupResults) {
				// Get more results because ORDER BY RAND() can't be used in GROUP BY queries
				$ResultLimit *= 5;
			}
		} elseif ($GroupResults) {
			$Select = 'groupid';
			if (isset(self::$AggregateExp[$OrderBy])) {
				$Select .= ', ' . self::$AggregateExp[$OrderBy];
			}
			$this->SphQL->select($Select)
				->group_by('groupid')
				->order_group_by(self::$SortOrdersGrouped[$OrderBy], $OrderWay)
				->order_by(self::$SortOrdersGrouped[$OrderBy], $OrderWay);
		} else {
			$this->SphQL->select('id, groupid')
				->order_by(self::$SortOrders[$OrderBy], $OrderWay);
		}
		$Offset = ($this->Page - 1) * $ResultLimit;
		$MaxMatches = $Offset + $ResultLimit;
		$this->SphQL->from('torrents, delta')
			->limit($Offset, $ResultLimit, $MaxMatches);
	}

	/**
	 * Process search terms and run the main query
	 *
	 * @param array $Terms Array containing all search terms (e.g. $_GET)
	 * @return array List of matching group IDs with torrent ID as key for ungrouped results
	 */
	public function query($Terms = array()) {
		$this->process_search_terms($Terms);
		$this->build_query();
		$this->run_query();
		$this->process_results();
		return $this->SphResults;
	}

	/**
	 * Internal function that runs the queries needed to get the desired results
	 */
	private function run_query() {
		$SphQLResult = $this->SphQL->query();
		if ($SphQLResult->Errno > 0) {
			$this->SphResults = false;
			return;
		}
		if ($this->Random && $this->GroupResults) {
			$TotalCount = $SphQLResult->get_meta('total_found');
			$this->SphResults = $SphQLResult->collect('groupid');
			$GroupIDs = array_keys($this->SphResults);
			$GroupCount = count($GroupIDs);
			while ($SphQLResult->get_meta('total') < $TotalCount && $GroupCount < $this->PageSize) {
				// Make sure we get $PageSize results, or all of them if there are less than $PageSize hits
				$this->SphQL->where('groupid', $GroupIDs, true);
				$SphQLResult = $this->SphQL->query();
				if (!$SphQLResult->has_results()) {
					break;
				}
				$this->SphResults += $SphQLResult->collect('groupid');
				$GroupIDs = array_keys($this->SphResults);
				$GroupCount = count($GroupIDs);
			}
			if ($GroupCount > $this->PageSize) {
				$this->SphResults = array_slice($this->SphResults, 0, $this->PageSize, true);
			}
			$this->NumResults = count($this->SphResults);
		} else {
			$this->NumResults = (int)$SphQLResult->get_meta('total_found');
			if ($this->GroupResults) {
				$this->SphResults = $SphQLResult->collect('groupid');
			} else {
				$this->SphResults = $SphQLResult->to_pair('id', 'groupid');
			}
		}
	}

	/**
	 * Process search terms and store the parts in appropriate arrays until we know if
	 * the NOT operator can be used
	 */
	private function build_query() {
		foreach ($this->Terms as $Field => $Words) {
			$SearchString = '';
			if (isset(self::$FormsToFields[$Field])) {
				$Field = self::$FormsToFields[$Field];
			}
			$QueryParts = array('include' => array(), 'exclude' => array());
			if (!$this->EnableNegation && !empty($Words['exclude'])) {
				$Words['include'] = $Words['exclude'];
				unset($Words['exclude']);
			}
			if (!empty($Words['include'])) {
				foreach ($Words['include'] as $Word) {
					$QueryParts['include'][] = Sphinxql::sph_escape_string($Word);
				}
			}
			if (!empty($Words['exclude'])) {
				foreach ($Words['exclude'] as $Word) {
					$QueryParts['exclude'][] = '!' . Sphinxql::sph_escape_string(substr($Word, 1));
				}
			}
			if (!empty($QueryParts)) {
				if (isset($Words['operator'])) {
					// Is the operator already specified?
					$Operator = $Words['operator'];
				} elseif(isset(self::$FieldOperators[$Field])) {
					// Does this field have a non-standard operator?
					$Operator = self::$FieldOperators[$Field];
				} else {
					// Go for the default operator
					$Operator = self::$FieldOperators[''];
				}
				if (!empty($QueryParts['include'])) {
					$SearchString .= '( ' . implode($Operator, $QueryParts['include']) . ' ) ';
				}
				if (!empty($QueryParts['exclude'])) {
					 $SearchString .= implode(' ', $QueryParts['exclude']);
				}
				$this->SphQL->where_match($SearchString, $Field, false);
				if (isset(self::$TorrentFields[$Field])) {
					$this->UsedTorrentFields[$Field] = $SearchString;
				}
				$this->Filtered = true;
			}
		}
	}

	/**
	 * Look at each search term and figure out what to do with it
	 *
	 * @param array $Terms Array with search terms from query()
	 */
	private function process_search_terms($Terms) {
		foreach ($Terms as $Key => $Term) {
			if (isset(self::$Fields[$Key])) {
				$this->process_field($Key, $Term);
			} elseif (isset(self::$Attributes[$Key])) {
				$this->process_attribute($Key, $Term);
			}
			$this->RawTerms[$Key] = $Term;
		}
		$this->post_process_fields();
	}

	/**
	 * Process attribute filters and store them in case we need to post-process grouped results
	 *
	 * @param string $Attribute Name of the attribute to filter against
	 * @param mixed $Value The filter's condition for a match
	 */
	private function process_attribute($Attribute, $Value) {
		if ($Value === '') {
			return;
		}
		switch ($Attribute) {
			case 'year':
				if (!$this->search_year($Value)) {
					return;
				}
				break;

			case 'haslog':
				if ($Value == 0) {
					$this->SphQL->where('haslog', 0);
				} elseif ($Value == 100) {
					$this->SphQL->where('logscore', 100);
				} elseif ($Value < 0) {
					$this->SphQL->where_lt('logscore', 100);
					$this->SphQL->where('haslog', 1);
				} else {
					$this->SphQL->where('haslog', 1);
				}
				$this->UsedTorrentAttrs['haslog'] = $Value;
				break;

			case 'freetorrent':
				if ($Value == 3) {
					$this->SphQL->where('freetorrent', 0, true);
					$this->UsedTorrentAttrs['freetorrent'] = 3;
				} elseif ($Value >= 0 && $Value < 3) {
					$this->SphQL->where('freetorrent', $Value);
					$this->UsedTorrentAttrs[$Attribute] = $Value;
				} else {
					return;
				}
				break;

			case 'filter_cat':
				if (!is_array($Value)) {
					$Value = array_fill_keys(explode('|', $Value), 1);
				}
				$CategoryFilter = array();
				foreach (array_keys($Value) as $Category) {
					if (is_number($Category)) {
						$CategoryFilter[] = $Category;
					} else {
						global $Categories;
						$ValidValues = array_map('strtolower', $Categories);
						if (($CategoryID = array_search(strtolower($Category), $ValidValues)) !== false) {
							$CategoryFilter[] = $CategoryID + 1;
						}
					}
				}
				if (empty($CategoryFilter)) {
					$CategoryFilter = 0;
				}
				$this->SphQL->where('categoryid', $CategoryFilter);
				break;

			default:
				if (!is_number($Value) && self::$Attributes[$Attribute] !== false) {
					// Check if the submitted value can be converted to a valid one
					$ValidValuesVarname = self::$Attributes[$Attribute];
					global $$ValidValuesVarname;
					$ValidValues = array_map('strtolower', $$ValidValuesVarname);
					if (($Value = array_search(strtolower($Value), $ValidValues)) === false) {
						// Force the query to return 0 results if value is still invalid
						$Value = max(array_keys($ValidValues)) + 1;
					}
				}
				$this->SphQL->where($Attribute, $Value);
				$this->UsedTorrentAttrs[$Attribute] = $Value;
				break;
		}
		$this->Filtered = true;
	}

	/**
	 * Look at a fulltext search term and figure out if it needs special treatment
	 *
	 * @param string $Field Name of the search field
	 * @param string $Term Search expression for the field
	 */
	private function process_field($Field, $Term) {
		$Term = trim($Term);
		if ($Term === '') {
			return;
		}
		if ($Field === 'searchstr') {
			$this->search_basic($Term);
		} elseif ($Field === 'filelist') {
			$this->search_filelist($Term);
		} elseif ($Field === 'taglist') {
			$this->search_taglist($Term);
		} else {
			$this->add_field($Field, $Term);
		}
	}

	/**
	 * Some fields may require post-processing
	 */
	private function post_process_fields() {
		if (isset($this->Terms['taglist'])) {
			// Replace bad tags with tag aliases
			$this->Terms['taglist'] = Tags::remove_aliases($this->Terms['taglist']);
			if (isset($this->RawTerms['tags_type']) && (int)$this->RawTerms['tags_type'] === self::TAGS_ANY) {
				$this->Terms['taglist']['operator'] = self::SPH_BOOL_OR;
			}
			// Update the RawTerms array so get_terms() can return the corrected search terms
			if (isset($this->Terms['taglist']['include'])) {
				$AllTags = $this->Terms['taglist']['include'];
			} else {
				$AllTags = array();
			}
			if (isset($this->Terms['taglist']['exclude'])) {
				$AllTags = array_merge($AllTags, $this->Terms['taglist']['exclude']);
			}
			$this->RawTerms['taglist'] = str_replace('_', '.', implode(', ', $AllTags));
		}
	}

	/**
	 * Handle magic keywords in the basic torrent search
	 *
	 * @param string $Term Given search expression
	 */
	private function search_basic($Term) {
		global $Bitrates, $Formats, $Media;
		$SearchBitrates = array_map('strtolower', $Bitrates);
		array_push($SearchBitrates, 'v0', 'v1', 'v2', '24bit');
		$SearchFormats = array_map('strtolower', $Formats);
		$SearchMedia = array_map('strtolower', $Media);

		foreach (explode(' ', $Term) as $Word) {
			if (in_array($Word, $SearchBitrates)) {
				$this->add_word('encoding', $Word);
			} elseif (in_array($Word, $SearchFormats)) {
				$this->add_word('format', $Word);
			} elseif (in_array($Word, $SearchMedia)) {
				$this->add_word('media', $Word);
			} elseif ($Word === '100%') {
				$this->process_attribute('haslog', 100);
			} elseif ($Word === '!100%') {
				$this->process_attribute('haslog', -1);
			} else {
				$this->add_word('searchstr', $Word);
			}
		}
	}

	/**
	 * Use phrase boundary for file searches to make sure we don't count
	 * partial hits from multiple files
	 *
	 * @param string $Term Given search expression
	 */
	private function search_filelist($Term) {
		$SearchString = '"' . Sphinxql::sph_escape_string($Term) . '"~20';
		$this->SphQL->where_match($SearchString, 'filelist', false);
		$this->UsedTorrentFields['filelist'] = $SearchString;
		$this->EnableNegation = true;
		$this->Filtered = true;
	}

	/**
	 * Prepare tag searches before sending them to the normal treatment
	 *
	 * @param string $Term Given search expression
	 */
	private function search_taglist($Term) {
		$Term = strtr($Term, '.', '_');
		$this->add_field('taglist', $Term);
	}

	/**
	 * The year filter accepts a range. Figure out how to handle the filter value
	 *
	 * @param string $Term Filter condition. Can be an integer or a range with the format X-Y
	 * @return bool True if parameters are valid
	 */
	private function search_year($Term) {
		$Years = explode('-', $Term);
		if (count($Years) === 1 && is_number($Years[0])) {
			// Exact year
			$this->SphQL->where('year', $Years[0]);
		} elseif (count($Years) === 2) {
			if (empty($Years[0]) && is_number($Years[1])) {
				// Range: 0 - 2005
				$this->SphQL->where_lt('year', $Years[1], true);
			} elseif (empty($Years[1]) && is_number($Years[0])) {
				// Range: 2005 - 2^32-1
				$this->SphQL->where_gt('year', $Years[0], true);
			} elseif (is_number($Years[0]) && is_number($Years[1])) {
				// Range: 2005 - 2009
				$this->SphQL->where_between('year', array(min($Years), max($Years)));
			} else {
				// Invalid input
				return false;
			}
		} else {
			// Invalid input
			return false;
		}
		return true;
	}

	/**
	 * Add a field filter that doesn't need special treatment
	 *
	 * @param string $Field Name of the search field
	 * @param string $Term Search expression for the field
	 */
	private function add_field($Field, $Term) {
		if (isset(self::$FieldSeparators[$Field])) {
			$Separator = self::$FieldSeparators[$Field];
		} else {
			$Separator = self::$FieldSeparators[''];
		}
		$Words = explode($Separator, $Term);
		foreach ($Words as $Word) {
			$this->add_word($Field, $Word);
		}
	}

	/**
	 * Add a keyword to the array of search terms
	 *
	 * @param string $Field Name of the search field
	 * @param string $Word Keyword
	 */
	private function add_word($Field, $Word) {
		$Word = trim($Word);
		// Skip isolated hyphens to enable "Artist - Title" searches
		if ($Word === '' || $Word === '-') {
			return;
		}
		if ($Word[0] === '!' && strlen($Word) >= 2 && strpos($Word, '!', 1) === false) {
			$this->Terms[$Field]['exclude'][] = $Word;
		} else {
			$this->Terms[$Field]['include'][] = $Word;
			$this->EnableNegation = true;
		}
	}

	/**
	 * @return array Torrent group information for the matches from Torrents::get_groups
	 */
	public function get_groups() {
		return $this->Groups;
	}

	/**
	 * @param string $Type Field or attribute name
	 * @return string Unprocessed search terms
	 */
	public function get_terms($Type) {
		return isset($this->RawTerms[$Type]) ? $this->RawTerms[$Type] : '';
	}

	/**
	 * @return int Result count
	 */
	public function record_count() {
		return $this->NumResults;
	}

	/**
	 * @return bool Whether any filters were used
	 */
	public function has_filters() {
		return $this->Filtered;
	}

	/**
	 * @return bool Whether any torrent-specific fulltext filters were used
	 */
	public function need_torrent_ft() {
		return $this->GroupResults && $this->NumResults > 0 && !empty($this->UsedTorrentFields);
	}

	/**
	 * Get torrent group info and remove any torrents that don't match
	 */
	private function process_results() {
		if (count($this->SphResults) == 0) {
			return;
		}
		$this->Groups = Torrents::get_groups($this->SphResults);
		if ($this->need_torrent_ft()) {
			// Query Sphinx for torrent IDs if torrent-specific fulltext filters were used
			$this->filter_torrents_sph();
		} elseif ($this->GroupResults) {
			// Otherwise, let PHP discard unmatching torrents
			$this->filter_torrents_internal();
		}
		// Ungrouped searches don't need any additional filtering
	}

	/**
	 * Build and run a query that gets torrent IDs from Sphinx when fulltext filters
	 * were used to get primary results and they are grouped
	 */
	private function filter_torrents_sph() {
		$AllTorrents = array();
		foreach ($this->Groups as $GroupID => $Group) {
			if (!empty($Group['Torrents'])) {
				$AllTorrents += array_fill_keys(array_keys($Group['Torrents']), $GroupID);
			}
		}
		$TorrentCount = count($AllTorrents);
		$this->SphQLTor = new SphinxqlQuery();
		$this->SphQLTor->select('id')->from('torrents, delta');
		foreach ($this->UsedTorrentFields as $Field => $Term) {
			$this->SphQLTor->where_match($Term, $Field, false);
		}
		$this->SphQLTor->copy_attributes_from($this->SphQL);
		$this->SphQLTor->where('id', array_keys($AllTorrents))->limit(0, $TorrentCount, $TorrentCount);
		$SphQLResultTor = $this->SphQLTor->query();
		$MatchingTorrentIDs = $SphQLResultTor->to_pair('id', 'id');
		foreach ($AllTorrents as $TorrentID => $GroupID) {
			if (!isset($MatchingTorrentIDs[$TorrentID])) {
				unset($this->Groups[$GroupID]['Torrents'][$TorrentID]);
			}
		}
	}

	/**
	 * Non-Sphinx method of collecting IDs of torrents that match any
	 * torrent-specific attribute filters that were used in the search query
	 */
	private function filter_torrents_internal() {
		foreach ($this->Groups as $GroupID => $Group) {
			if (empty($Group['Torrents'])) {
				continue;
			}
			foreach ($Group['Torrents'] as $TorrentID => $Torrent) {
				if (!$this->filter_torrent_internal($Torrent)) {
					unset($this->Groups[$GroupID]['Torrents'][$TorrentID]);
				}
			}
		}
	}

	/**
	 * Post-processing to determine if a torrent is a real hit or if it was
	 * returned because another torrent in the group matched. Only used if
	 * there are no torrent-specific fulltext conditions
	 *
	 * @param array $Torrent Torrent array, probably from Torrents::get_groups()
	 * @return bool True if it's a real hit
	 */
	private function filter_torrent_internal($Torrent) {
		if (isset($this->UsedTorrentAttrs['freetorrent'])) {
			$FilterValue = $this->UsedTorrentAttrs['freetorrent'];
			if ($FilterValue == '3' && $Torrent['FreeTorrent'] == '0') {
				// Either FL or NL is ok
				return false;
			} elseif ($FilterValue != '3' && $FilterValue != (int)$Torrent['FreeTorrent']) {
				return false;
			}
		}
		if (isset($this->UsedTorrentAttrs['hascue'])) {
			if ($this->UsedTorrentAttrs['hascue'] != (int)$Torrent['HasCue']) {
				return false;
			}
		}
		if (isset($this->UsedTorrentAttrs['haslog'])) {
			$FilterValue = $this->UsedTorrentAttrs['haslog'];
			if ($FilterValue == '0') {
				// No logs
				$Pass = empty($Torrent['HasLog']);
			} elseif ($FilterValue == '100') {
				// 100% logs
				$Pass = $Torrent['LogScore'] == '100';
			} elseif ($FilterValue < 0) {
				// Unscored or <100% logs
				$Pass = !empty($Torrent['HasLog']) && $Torrent['LogScore'] != '100';
			} else {
				// Any log score
				$Pass = $Torrent['HasLog'] == '1';
			}
			if (!$Pass) {
				return false;
			}
		}
		if (isset($this->UsedTorrentAttrs['scene'])) {
			if ($this->UsedTorrentAttrs['scene'] != (int)$Torrent['Scene']) {
				return false;
			}
		}
		return true;
	}
}
