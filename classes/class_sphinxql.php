<?
if (!extension_loaded('mysqli')) {
	error('Mysqli Extension not loaded.');
}

class SPHINXQL extends mysqli {
	private static $Connections = array();
	private $Server;
	private $Port;
	private $Socket;
	public $Ident;
	private $Connected = false;

	public static $Queries = array();
	public static $Time = 0.0;


	/**
	 * Initialize SphinxQL object
	 *
	 * @string $Server server address or hostname
	 * @int $Port listening port
	 * @string $Socket Unix socket address, overrides $Server:$Port
	 */
	public function __construct($Server, $Port, $Socket) {
		$this->Server = $Server;
		$this->Port = $Port;
		$this->Socket = $Socket;
		$this->Ident = $this->get_ident($Server, $Port, $Socket);
	}

	/**
	 * Create server ident based on connection information
	 *
	 * @string $Server server address or hostname
	 * @int $Port listening port
	 * @string $Socket Unix socket address, overrides $Server:$Port
	 * @return identification string
	 */
	private function get_ident($Server, $Port, $Socket) {
		if($Socket) {
			return $Socket;
		} else {
			return "$Server:$Port";
		}
	}

	/**
	 * Create SphinxQL object or return existing one
	 *
	 * @string $Server server address or hostname
	 * @int $Port listening port
	 * @string $Socket Unix socket address, overrides $Server:$Port
	 * @return SphinxQL object
	 */
	public static function init_connection($Server, $Port, $Socket) {
		$Ident = self::get_ident($Server, $Port, $Socket);
		if(!isset(self::$Connections[$Ident])) {
			self::$Connections[$Ident] = new SPHINXQL($Server, $Port, $Socket);
		}
		return self::$Connections[$Ident];
	}

	/**
	 * Connect the SphinxQL object to the Sphinx server
	 */
	public function connect() {
		if(!$this->Connected) {
			global $Debug;
			$Debug->set_flag('Connecting to Sphinx server '.$this->Ident);
			parent::__construct($this->Server, '', '', '', $this->Port, $this->Socket);
			if($this->connect_error) {
				$Errno = $this->connect_errno;
				$Error = $this->connect_error;
				$this->error("Connection failed. ".strval($Errno)." (".strval($Error).")");
			}
			$Debug->set_flag('Connected to Sphinx server '.$this->Ident);
			$this->Connected = true;
		}
	}

	/**
	 * Print a message to privileged users and optionally halt page processing
	 *
	 * @string $Msg message to display
	 * @bool $Halt halt page processing. Default is to continue processing the page
	 * @return SphinxQL object
	 */
	public function error($Msg, $Halt = false) {
		global $Debug;
		$ErrorMsg = 'SphinxQL ('.$this->Ident.'): '.strval($Msg);
		$Debug->analysis('SphinxQL Error', $ErrorMsg, 3600*24);
		if($Halt === true && (DEBUG_MODE || check_perms('site_debug'))) {
			echo '<pre>'.display_str($ErrorMsg).'</pre>';
			die();
		} elseif($Halt === true) {
			error('-1');
		}
	}

	/**
	 * Escape special characters before sending them to the Sphinx server.
	 * Two escapes needed because the first one is eaten up by the mysql driver.
	 *
	 * @string $String string to escape
	 * @return escaped string
	 */
	public function escape_string($String) {
		return strtr($String, array(
			'('=>'\\\\(',
			')'=>'\\\\)',
			'|'=>'\\\\|',
			'-'=>'\\\\-',
			'@'=>'\\\\@',
			'~'=>'\\\\~',
			'&'=>'\\\\&',
			'\''=>'\\\'',
			'<'=>'\\\\<',
			'!'=>'\\\\!',
			'"'=>'\\\\"',
			'\\'=>'\\\\\\\\')
		);
	}

	/**
	 * Register sent queries globally for later retrieval by debug functions
	 *
	 * @string $QueryString query text
	 * @param $QueryProcessTime time building and processing the query
	 */
	public function register_query($QueryString, $QueryProcessTime) {
		SPHINXQL::$Queries[] = array($QueryString, $QueryProcessTime);
		SPHINXQL::$Time += $QueryProcessTime;
	}
}

class SPHINXQL_QUERY {
	private $SphinxQL;

	private $Expressions = array();
	private $Filters = array();
	private $GroupBy = '';
	private $Indexes = '';
	private $Limits = array();
	private $Options = array();
	private $QueryString = '';
	private $Select = '*';
	private $SortBy = array();
	private $SortGroupBy = '';

	/**
	 * Initialize SphinxQL object
	 *
	 * @string $Server server address or hostname
	 * @int $Port listening port
	 * @string $Socket Unix socket address, overrides $Server:$Port
	 */
	public function __construct($Server = SPHINXQL_HOST, $Port = SPHINXQL_PORT, $Socket = SPHINXQL_SOCK) {
		$this->SphinxQL = SPHINXQL::init_connection($Server, $Port, $Socket);
	}

	/**
	 * Specify what data the Sphinx query is supposed to return
	 *
	 * @string $Fields Attributes and expressions
	 * @return current SphinxQL query object
	 */
	public function select($Fields) {
		$this->reset_query();
		$this->Select = $Fields;
		return $this;
	}

	/**
	 * Specify the indexes to use in the search
	 *
	 * @string $Indexes comma separated list of indexes
	 * @return current SphinxQL query object
	 */
	public function from($Indexes) {
		$this->Indexes = $Indexes;
		return $this;
	}

	/**
	 * Add attribute filter. Calling this function multiple times results in boolean AND between each condition
	 *
	 * @string $Attribute attribute which the filter will apply to
	 * @mixed $Values scalar or array of numerical values. Array uses boolean OR in query condition
	 * @bool $Exclude whether to exclude or include matching documents. Default mode is to include matches
	 * @return current SphinxQL query object
	 */
	public function where($Attribute, $Values, $Exclude = false) {
		if(empty($Attribute) && empty($Values)) {
			return false;
		}
		$Filters = array();
		if(is_array($Values)) {
			foreach($Values as $Value) {
				if(!is_number($Value)) {
					$this->error("Filters require numeric values");
				}
			}
			if($Exclude) {
				$Filters[] = "$Attribute NOT IN (".implode(",", $Value).")";
			} else {
				$Filters[] = "$Attribute IN (".implode(",", $Value).")";
			}
		} else {
			if(!is_number($Values)) {
				$this->error("Filters require numeric values");
			}
			if($Exclude) {
				$Filters[] = "$Attribute != $Values";
			} else {
				$Filters[] = "$Attribute = $Values";
			}
		}
		$this->Filters[] = implode(" AND ", $Filters);
		return $this;
	}

	/**
	 * Add attribute range filter. Calling this function multiple times results in boolean AND between each condition
	 *
	 * @string $Attribute attribute which the filter will apply to
	 * @array $Values pair of numerical values that defines the filter range
	 * @return current SphinxQL query object
	 */
	public function where_between($Attribute, $Values) {
		if(empty($Attribute) || empty($Values) || count($Values) != 2 || !is_number($Values[0]) || !is_number($Values[1])) {
			$this->error("Filter range requires array of two numerical boundaries as values.");
		}
		$this->Filters[] = "$Attribute BETWEEN $Values[0] AND $Values[1]";
		return $this;
	}

	/**
	 * Add fulltext query expression. Calling this function multiple times results in boolean AND between each condition.
	 * Query expression is escaped automatically
	 *
	 * @string $Expr query expression
	 * @string $Field field to match $Expr against. Default is *, which means all available fields
	 * @return current SphinxQL query object
	 */
	public function where_match($Expr, $Field = '*') {
		if(empty($Expr)) {
			return $this;
		}
		$this->Expressions[] = "@$Field ".SPHINXQL::escape_string($Expr);
		return $this;
	}

	/**
	 * Specify the order of the matches. Calling this function multiple times sets secondary priorities
	 *
	 * @string $Attribute attribute to use for sorting
	 * @string $Mode sort method to apply to the selected attribute
	 * @return current SphinxQL query object
	 */
	public function order_by($Attribute, $Mode) {
		$this->SortBy[] = "$Attribute $Mode";
		return $this;
	}

	/**
	 * Specify how the results are grouped
	 *
	 * @string $Attribute group matches with the same $Attribute value
	 * @return current SphinxQL query object
	 */
	public function group_by($Attribute) {
		$this->GroupBy = "$Attribute";
		return $this;
	}

	/**
	 * Specify the order of the results within groups
	 *
	 * @string $Attribute attribute to use for sorting
	 * @string $Mode sort method to apply to the selected attribute
	 * @return current SphinxQL query object
	 */
	public function order_group_by($Attribute, $Mode) {
		$this->SortGroupBy = "$Attribute $Mode";
		return $this;
	}

	/**
	 * Specify the offset and amount of matches to return
	 *
	 * @int $Offset number of matches to discard
	 * @int $Limit number of matches to return
	 * @int $MaxMatches number of results to store in the Sphinx server's memory
	 * @return current SphinxQL query object
	 */
	public function limit($Offset, $Limit, $MaxMatches = SPHINX_MATCHES_START) {
		$this->Limits = "$Offset, $Limit";
		$this->Options['max_matches'] = $MaxMatches;
		return $this;
	}

	/**
	 * Combine the query options into a valid Sphinx query segment
	 *
	 * @return string of options
	 */
	private function build_options() {
		$Options = array();
		foreach($this->Options as $Option => $Value) {
			$Options[] = "$Option = $Value";
		}
		return implode(", ", $Options);
	}

	/**
	 * Combine the query conditions into a valid Sphinx query segment
	 *
	 * @return string of conditions
	 */
	private function build_query() {
		if(!$this->Indexes) {
			$this->error('Index name is required.');
		}
		$this->QueryString = "SELECT $this->Select FROM $this->Indexes";
		if(!empty($this->Expressions)) {
			$this->Filters[] = "MATCH('".implode(" ", $this->Expressions)."')";
		}
		if(!empty($this->Filters)) {
			$this->QueryString .= "\nWHERE ".implode("\n\tAND ", $this->Filters);
		}
		if(!empty($this->GroupBy)) {
			$this->QueryString .= "\nGROUP BY $this->GroupBy";
		}
		if(!empty($this->SortGroupBy)) {
			$this->QueryString .= "\nWITHIN GROUP ORDER BY $this->SortGroupBy";
		}
		if(!empty($this->SortBy)) {
			$this->QueryString .= "\nORDER BY ".implode(", ", $this->SortBy);
		}
		if(!empty($this->Limits)) {
			$this->QueryString .= "\nLIMIT $this->Limits";
		}
		if(!empty($this->Options)) {
			$Options = $this->build_options();
			$this->QueryString .= "\nOPTION $Options";
		}
	}

	/**
	 * Construct and send the query. Register the query in the global SphinxQL object
	 *
	 * @bool GetMeta whether to fetch meta data for the executed query. Default is yes
	 * @return SphinxQL result object
	 */
	public function query($GetMeta = true) {
		$QueryStartTime = microtime(true);
		$this->build_query();
		$QueryString = $this->QueryString;
		$Result = $this->send_query($GetMeta);
		$QueryProcessTime = (microtime(true) - $QueryStartTime)*1000;
		SPHINXQL::register_query($QueryString, $QueryProcessTime);
		return $Result;
	}

	/**
	 * Run a manually constructed query
	 *
	 * @string Query query expression
	 * @bool GetMeta whether to fetch meta data for the executed query. Default is yes
	 * @return SphinxQL result object
	 */
	public function raw_query($Query, $GetMeta = true) {
		$this->QueryString = $Query;
		return $this->send_query($GetMeta);
	}

	/**
	 * Run a pre-processed query. Only used internally
	 *
	 * @bool GetMeta whether to fetch meta data for the executed query
	 * @return SphinxQL result object
	 */
	private function send_query($GetMeta) {
		if(!$this->QueryString) {
			return false;
		}
		$this->SphinxQL->connect();
		$Result = $this->SphinxQL->query($this->QueryString);
		if($Result === false) {
			$Errno = $this->SphinxQL->errno;
			$Error = $this->SphinxQL->error;
			$this->error("Query returned error $Errno ($Error).\n$this->QueryString");
		} else {
			$Meta = $GetMeta ? $this->get_meta() : null;
		}
		return new SPHINXQL_RESULT($Result, $Meta, $Errno, $Error);
	}

	/**
	 * Reset all query options and conditions
	 */
	private function reset_query() {
		$this->Expressions = array();
		$this->Filters = array();
		$this->GroupBy = '';
		$this->Indexes = '';
		$this->Limits = array();
		$this->Options = array();
		$this->QueryString = '';
		$this->Select = '*';
		$this->SortBy = array();
		$this->SortGroupBy = '';
	}

	/**
	 * Fetch and store meta data for the last executed query
	 *
	 * @return meta data
	 */
	private function get_meta() {
		return $this->raw_query("SHOW META", false)->to_pair(0, 1);
	}

	/**
	 * Wrapper for the current SphinxQL connection's error function
	 */
	private function error($Msg, $Halt = false) {
		$this->SphinxQL->error($Msg, $Halt);
	}
}

class SPHINXQL_RESULT {
	private $Result;
	private $Meta;
	public $Errno;
	public $Error;

	/**
	 * Create SphinxQL result object
	 *
	 * @mysqli_result $Result query results
	 * @array $Meta meta data for the query
	 * @int $Errno error code returned by the query upon failure
	 * @string $Error error message returned by the query upon failure
	 */
	public function __construct($Result, $Meta, $Errno, $Error) {
		$this->Result = $Result;
		$this->Meta = $Meta;
		$this->Errno = $Errno;
		$this->Error = $Error;
	}

	/**
	 * Redirect to the Mysqli result object if a nonexistent method is called
	 *
	 * @string $Name method name
	 * @array $Arguments arguments used in the function call
	 * @return whatever the parent function returns
	 */
	public function __call($Name, $Arguments) {
		return call_user_func_array(array($this->Result, $Name), $Arguments);
	}

	/**
	 * Collect and return the specified key of all results as a list
	 *
	 * @string $Key key containing the desired data
	 * @return array with the $Key value of all results
	 */
	public function collect($Key) {
		$Return = array();
		while($Row = $this->fetch_array()) {
			$Return[] = $Row[$Key];
		}
		$this->data_seek(0);
		return $Return;
	}

	/**
	 * Collect and return all available data for the matches optionally indexed by a specified key
	 *
	 * @string $Key key to use as indexing value
	 * @string $ResultType method to use when fetching data from the mysqli_result object. Default is MYSQLI_ASSOC
	 * @return array with all available data for the matches
	 */
	public function to_array($Key, $ResultType = MYSQLI_ASSOC) {
		$Return = array();
		while($Row = $this->fetch_array($ResultType)) {
			if($Key !== false) {
				$Return[$Row[$Key]] = $Row;
			} else {
				$Return[] = $Row;
			}
		}
		$this->data_seek(0);
		return $Return;
	}

	/**
	 * Collect pairs of keys for all matches
	 *
	 * @string $Key1 key to use as indexing value
	 * @string $Key2 key to use as value
	 * @return array with $Key1 => $Key2 pairs for matches
	 */
	public function to_pair($Key1, $Key2) {
		$Return = array();
		while($Row = $this->fetch_row()) {
			$Return[$Row[$Key1]] = $Row[$Key2];
		}
		$this->data_seek(0);
		return $Return;
	}

	/**
	 * Return specified portions of the current SphinxQL result object's meta data
	 *
	 * @mixed $Keys scalar or array with keys to return. Default is false, which returns all meta data
	 * @return array with meta data
	 */
	public function get_meta($Keys = false) {
		if($Keys !== false) {
			if(is_array($Keys)) {
				$Return = array();
				foreach($Keys as $Key) {
					if(!isset($this->Meta[$Key])) {
						continue;
					}
					$Return[$Key] = $this->Meta[$Key];
				}
				return $Return;
			} else {
				return isset($this->Meta[$Keys]) ? $this->Meta[$Keys] : false;
			}
		} else {
			return $this->Meta;
		}
	}

	/**
	 * Return specified portions of the current Mysqli result object's information
	 *
	 * @mixed $Keys scalar or array with keys to return. Default is false, which returns all available information
	 * @return array with result information
	 */
	public function get_result_info($Keys = false) {
		if($Keys !== false) {
			if(is_array($Keys)) {
				$Return = array();
				foreach($Keys as $Key) {
					if(!isset($this->Result->$Key)) {
						continue;
					}
					$Return[$Key] = $this->Result->$Key;
				}
				return $Return;
			} else {
				return isset($this->Result->$Keys) ? $this->Result->$Keys : false;
			}
		} else {
			return $this->Result;
		}
	}
}
