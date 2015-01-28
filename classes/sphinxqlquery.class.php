<?
class SphinxqlQuery {
	private $Sphinxql;

	private $Errors;
	private $Expressions;
	private $Filters;
	private $GroupBy;
	private $Indexes;
	private $Limits;
	private $Options;
	private $QueryString;
	private $Select;
	private $SortBy;
	private $SortGroupBy;

	/**
	 * Initialize Sphinxql object
	 *
	 * @param string $Server server address or hostname
	 * @param int $Port listening port
	 * @param string $Socket Unix socket address, overrides $Server:$Port
	 */
	public function __construct($Server = SPHINXQL_HOST, $Port = SPHINXQL_PORT, $Socket = SPHINXQL_SOCK) {
		$this->Sphinxql = Sphinxql::init_connection($Server, $Port, $Socket);
		$this->reset();
	}

	/**
	 * Specify what data the Sphinx query is supposed to return
	 *
	 * @param string $Fields Attributes and expressions
	 * @return current SphinxqlQuery object
	 */
	public function select($Fields) {
		$this->Select = $Fields;
		return $this;
	}

	/**
	 * Specify the indexes to use in the search
	 *
	 * @param string $Indexes comma separated list of indexes
	 * @return current SphinxqlQuery object
	 */
	public function from($Indexes) {
		$this->Indexes = $Indexes;
		return $this;
	}

	/**
	 * Add attribute filter. Calling multiple filter functions results in boolean AND between each condition.
	 *
	 * @param string $Attribute attribute which the filter will apply to
	 * @param mixed $Values scalar or array of numerical values. Array uses boolean OR in query condition
	 * @param bool $Exclude whether to exclude or include matching documents. Default mode is to include matches
	 * @return current SphinxqlQuery object
	 */
	public function where($Attribute, $Values, $Exclude = false) {
		if (empty($Attribute) || !isset($Values)) {
			$this->error("Attribute name and filter value are required.");
			return $this;
		}
		$Filters = array();
		if (is_array($Values)) {
			foreach ($Values as $Value) {
				if (!is_number($Value)) {
					$this->error("Filters only support numeric values.");
					return $this;
				}
			}
			if ($Exclude) {
				$Filters[] = "$Attribute NOT IN (".implode(",", $Values).")";
			} else {
				$Filters[] = "$Attribute IN (".implode(",", $Values).")";
			}
		} else {
			if (!is_number($Values)) {
				$this->error("Filters only support numeric values.");
				return $this;
			}
			if ($Exclude) {
				$Filters[] = "$Attribute != $Values";
			} else {
				$Filters[] = "$Attribute = $Values";
			}
		}
		$this->Filters[] = implode(" AND ", $Filters);
		return $this;
	}

	/**
	 * Add attribute less-than filter. Calling multiple filter functions results in boolean AND between each condition.
	 *
	 * @param string $Attribute attribute which the filter will apply to
	 * @param array $Value upper limit for matches
	 * @param bool $Inclusive whether to use <= or <
	 * @return current SphinxqlQuery object
	 */
	public function where_lt($Attribute, $Value, $Inclusive = false) {
		if (empty($Attribute) || !isset($Value) || !is_number($Value)) {
			$this->error("Attribute name is required and only numeric filters are supported.");
			return $this;
		}
		$this->Filters[] = $Inclusive ? "$Attribute <= $Value" : "$Attribute < $Value";
		return $this;
	}

	/**
	 * Add attribute greater-than filter. Calling multiple filter functions results in boolean AND between each condition.
	 *
	 * @param string $Attribute attribute which the filter will apply to
	 * @param array $Value lower limit for matches
	 * @param bool $Inclusive whether to use >= or >
	 * @return current SphinxqlQuery object
	 */
	public function where_gt($Attribute, $Value, $Inclusive = false) {
		if (empty($Attribute) || !isset($Value) || !is_number($Value)) {
			$this->error("Attribute name is required and only numeric filters are supported.");
			return $this;
		}
		$this->Filters[] = $Inclusive ? "$Attribute >= $Value" : "$Attribute > $Value";
		return $this;
	}

	/**
	 * Add attribute range filter. Calling multiple filter functions results in boolean AND between each condition.
	 *
	 * @param string $Attribute attribute which the filter will apply to
	 * @param array $Values pair of numerical values that defines the filter range
	 * @return current SphinxqlQuery object
	 */
	public function where_between($Attribute, $Values) {
		if (empty($Attribute) || empty($Values) || count($Values) != 2 || !is_number($Values[0]) || !is_number($Values[1])) {
			$this->error("Filter range requires array of two numerical boundaries as values.");
			return $this;
		}
		$this->Filters[] = "$Attribute BETWEEN $Values[0] AND $Values[1]";
		return $this;
	}

	/**
	 * Add fulltext query expression. Calling multiple filter functions results in boolean AND between each condition.
	 * Query expression is escaped automatically
	 *
	 * @param string $Expr query expression
	 * @param string $Field field to match $Expr against. Default is *, which means all available fields
	 * @return current SphinxqlQuery object
	 */
	public function where_match($Expr, $Field = '*', $Escape = true) {
		if (empty($Expr)) {
			return $this;
		}
		if ($Field !== false) {
			$Field = "@$Field ";
		}
		if ($Escape === true) {
			$this->Expressions[] = "$Field".Sphinxql::sph_escape_string($Expr);
		} else {
			$this->Expressions[] = $Field.$Expr;
		}
		return $this;
	}

	/**
	 * Specify the order of the matches. Calling this function multiple times sets secondary priorities
	 *
	 * @param string $Attribute attribute to use for sorting.
	 *     Passing an empty attribute value will clear the current sort settings
	 * @param string $Mode sort method to apply to the selected attribute
	 * @return current SphinxqlQuery object
	 */
	public function order_by($Attribute = false, $Mode = false) {
		if (empty($Attribute)) {
			$this->SortBy = array();
		} else {
			$this->SortBy[] = "$Attribute $Mode";
		}
		return $this;
	}

	/**
	 * Specify how the results are grouped
	 *
	 * @param string $Attribute group matches with the same $Attribute value.
	 *     Passing an empty attribute value will clear the current group settings
	 * @return current SphinxqlQuery object
	 */
	public function group_by($Attribute = false) {
		if (empty($Attribute)) {
			$this->GroupBy = '';
		} else {
			$this->GroupBy = $Attribute;
		}
		return $this;
	}

	/**
	 * Specify the order of the results within groups
	 *
	 * @param string $Attribute attribute to use for sorting.
	 *     Passing an empty attribute will clear the current group sort settings
	 * @param string $Mode sort method to apply to the selected attribute
	 * @return current SphinxqlQuery object
	 */
	public function order_group_by($Attribute = false, $Mode = false) {
		if (empty($Attribute)) {
			$this->SortGroupBy = '';
		} else {
			$this->SortGroupBy = "$Attribute $Mode";
		}
		return $this;
	}

	/**
	 * Specify the offset and amount of matches to return
	 *
	 * @param int $Offset number of matches to discard
	 * @param int $Limit number of matches to return
	 * @param int $MaxMatches number of results to store in the Sphinx server's memory. Must be >= ($Offset+$Limit)
	 * @return current SphinxqlQuery object
	 */
	public function limit($Offset, $Limit, $MaxMatches = SPHINX_MAX_MATCHES) {
		$this->Limits = "$Offset, $Limit";
		$this->set('max_matches', $MaxMatches);
		return $this;
	}

	/**
	 * Tweak the settings to use for the query. Sanity checking shouldn't be needed as Sphinx already does it
	 *
	 * @param string $Name setting name
	 * @param mixed $Value value
	 * @return current SphinxqlQuery object
	 */
	public function set($Name, $Value) {
		$this->Options[$Name] = $Value;
		return $this;
	}

	/**
	 * Combine the query options into a valid Sphinx query segment
	 *
	 * @return string of options
	 */
	private function build_options() {
		$Options = array();
		foreach ($this->Options as $Option => $Value) {
			$Options[] = "$Option = $Value";
		}
		return implode(', ', $Options);
	}

	/**
	 * Combine the query conditions into a valid Sphinx query segment
	 */
	private function build_query() {
		if (!$this->Indexes) {
			$this->error('Index name is required.');
			return false;
		}
		$this->QueryString = "SELECT $this->Select\nFROM $this->Indexes";
		if (!empty($this->Expressions)) {
			$this->Filters['expr'] = "MATCH('".implode(' ', $this->Expressions)."')";
		}
		if (!empty($this->Filters)) {
			$this->QueryString .= "\nWHERE ".implode("\n\tAND ", $this->Filters);
			unset($this->Filters['expr']);
		}
		if (!empty($this->GroupBy)) {
			$this->QueryString .= "\nGROUP BY $this->GroupBy";
		}
		if (!empty($this->SortGroupBy)) {
			$this->QueryString .= "\nWITHIN GROUP ORDER BY $this->SortGroupBy";
		}
		if (!empty($this->SortBy)) {
			$this->QueryString .= "\nORDER BY ".implode(", ", $this->SortBy);
		}
		if (!empty($this->Limits)) {
			$this->QueryString .= "\nLIMIT $this->Limits";
		}
		if (!empty($this->Options)) {
			$Options = $this->build_options();
			$this->QueryString .= "\nOPTION $Options";
		}
	}

	/**
	 * Construct and send the query. Register the query in the global Sphinxql object
	 *
	 * @param bool GetMeta whether to fetch meta data for the executed query. Default is yes
	 * @return SphinxqlResult object
	 */
	public function query($GetMeta = true) {
		$QueryStartTime = microtime(true);
		$this->build_query();
		if (count($this->Errors) > 0) {
			$ErrorMsg = implode("\n", $this->Errors);
			$this->Sphinxql->error("Query builder found errors:\n$ErrorMsg");
			return new SphinxqlResult(null, null, 1, $ErrorMsg);
		}
		$QueryString = $this->QueryString;
		$Result = $this->send_query($GetMeta);
		$QueryProcessTime = (microtime(true) - $QueryStartTime)*1000;
		Sphinxql::register_query($QueryString, $QueryProcessTime);
		return $Result;
	}

	/**
	 * Run a manually constructed query
	 *
	 * @param string Query query expression
	 * @param bool GetMeta whether to fetch meta data for the executed query. Default is yes
	 * @return SphinxqlResult object
	 */
	public function raw_query($Query, $GetMeta = true) {
		$this->QueryString = $Query;
		return $this->send_query($GetMeta);
	}

	/**
	 * Run a pre-processed query. Only used internally
	 *
	 * @param bool GetMeta whether to fetch meta data for the executed query
	 * @return SphinxqlResult object
	 */
	private function send_query($GetMeta) {
		if (!$this->QueryString) {
			return false;
		}
		$this->Sphinxql->sph_connect();
		$Result = $this->Sphinxql->query($this->QueryString);
		if ($Result === false) {
			$Errno = $this->Sphinxql->errno;
			$Error = $this->Sphinxql->error;
			$this->Sphinxql->error("Query returned error $Errno ($Error).\n$this->QueryString");
			$Meta = null;
		} else {
			$Errno = 0;
			$Error = '';
			$Meta = $GetMeta ? $this->get_meta() : null;
		}
		return new SphinxqlResult($Result, $Meta, $Errno, $Error);
	}

	/**
	 * Reset all query options and conditions
	 */
	public function reset() {
		$this->Errors = array();
		$this->Expressions = array();
		$this->Filters = array();
		$this->GroupBy = '';
		$this->Indexes = '';
		$this->Limits = array();
		$this->Options = array('ranker' => 'none');
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
	 * Copy attribute filters from another SphinxqlQuery object
	 *
	 * @param SphinxqlQuery $SphQLSource object to copy the filters from
	 * @return current SphinxqlQuery object
	 */
	public function copy_attributes_from($SphQLSource) {
		$this->Filters = $SphQLSource->Filters;
	}

	/**
	 * Store error messages
	 */
	private function error($Msg) {
		$this->Errors[] = $Msg;
	}
}
