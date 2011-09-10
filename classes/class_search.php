<?
//Require base class
if(!extension_loaded('sphinx')) {
	require(SERVER_ROOT.'/classes/sphinxapi.php');
}

class SPHINX_SEARCH extends SphinxClient {
	private $Index='*';
	public $TotalResults = 0;
	public $Queries = array();
	public $Time = 0.0;
	public $Filters = array();
	
	function SPHINX_SEARCH() {
		parent::__construct();
		$this->SetServer(SPHINX_HOST, SPHINX_PORT);
		$this->SetMatchMode(SPH_MATCH_EXTENDED2);
	}
	
	/****************************************************************
	/--- Search function --------------------------------------------
	
	This function queries sphinx for whatever is in $Query, in 
	extended2 mode. It then fetches the records for each primary key
	from memcached (by joining $CachePrefix and the primary key), and
	fetches the fields needed ($ReturnData) from the memcached 
	result.
	
	Any keys not found in memcached are then queried in MySQL, using 
	$SQL. They are then cached, and merged with the memcached matches
	and returned.
	
	$Query			- sphinx query
	$CachePrefix	- Prefix for memcache key (no underscore)
	$CacheLength	- How long to store data in the cache, if it's found by MySQL
	$ReturnData	- Array of keys to the array in memcached to return. 
					  If empty, return all.
	$SQL			- SQL query to fetch results not found in memcached
					- Should take the format of:
					  SELECT fields FROM table WHERE primary key IN(%ids)
					  where %ids will be replaced by a list of IDs not found in memcached
	$IDColumn		- The primary key of the SQL table - must be the
					  same primary key returned by sphinx!
	
	****************************************************************/
	
	function search($Query='', $CachePrefix='', $CacheLength=0, $ReturnData=array(), $SQL = '', $IDColumn='ID') {
		global $Cache, $DB;
		$QueryStartTime=microtime(true);
		$Result = $this->Query($Query, $this->Index);
		$QueryEndTime=microtime(true);

		$Filters = array();
		foreach($this->Filters as $Name => $Values) {
			foreach($Values as $Value) {
				$Filters[] = $Name." - ".$Value;
			}
		}

		$this->Queries[]=array('Params: '.$Query.' Filters: '.implode(", ", $Filters).' Indicies: '.$this->Index,($QueryEndTime-$QueryStartTime)*1000);
		$this->Time+=($QueryEndTime-$QueryStartTime)*1000;
		
		if($Result === false) {
			if($this->_connerror && !$Cache->get_value('sphinx_crash_reported')) {
				send_irc('PRIVMSG '.ADMIN_CHAN.' :!dev Connection to searchd failed');
				$Cache->cache_value('sphinx_crash_reported', 1, 3600);
			}
			send_irc('PRIVMSG '.LAB_CHAN.' :Search for "'.$Query.'" ('.str_replace("\n",'',print_r($this->Filters, true)).') failed: '.$this->GetLastError());
		}
		
		$this->TotalResults = $Result['total'];
		$this->SearchTime = $Result['time'];
		
		if(empty($Result['matches'])) {
			return false;
		}
		$Matches = $Result['matches'];
		
		$MatchIDs = array_keys($Matches);
		
		
		
		$NotFound = array();
		$Skip = array();
		if(!empty($ReturnData)) {
			$AllFields = false;
		} else {
			$AllFields = true;
		}
		
		foreach($MatchIDs as $Match) {
			$Matches[$Match] = $Matches[$Match]['attrs'];
			if(!empty($CachePrefix)) {
				$Data = $Cache->get_value($CachePrefix.'_'.$Match);
				if($Data == false) {
					$NotFound[]=$Match;
					continue;
				}
			} else {
				$NotFound[]=$Match;
			}
			if(!$AllFields) {
				// Populate list of fields to unset (faster than picking out the ones we need). Should only be run once, on the first cache key
				if(empty($Skip)) {
					foreach(array_keys($Data) as $Key) {
						if(!in_array($Key, $ReturnData)) {
							$Skip[]=$Key;
						}
					}
					if(empty($Skip)) {
						$AllFields = true;
					}
				}
				foreach($Skip as $Key) {
					unset($Data[$Key]);
				}
				reset($Skip);
			}
			if(!empty($Data)) {
				$Matches[$Match] = array_merge($Matches[$Match], $Data);
			}
		}
		
		if($SQL!='') {
			if(!empty($NotFound)) {
				$DB->query(str_replace('%ids', implode(',',$NotFound), $SQL));
				while($Data = $DB->next_record(MYSQLI_ASSOC)) {
					$Matches[$Data[$IDColumn]] = array_merge($Matches[$Data[$IDColumn]], $Data);
					$Cache->cache_value($CachePrefix.'_'.$Data[$IDColumn], $Data, $CacheLength);
				}
			}
		} else {
			$Matches = array('matches'=>$Matches,'notfound'=>$NotFound);
		}
		
		return $Matches;
	}
	
	function limit($Start, $Length, $MaxMatches=SPHINX_MATCHES_START) {
		if(check_perms('site_search_many') && empty($_GET['limit_matches'])) {
			$MaxMatches = 500000; 
		}
		$this->SetLimits((int)$Start, (int)$Length, $MaxMatches, 0);
	}
	
	
	function set_index($Index) {
		$this->Index = $Index;
	}
	
	function set_filter($Name, $Vals, $Exclude=false) {
		foreach($Vals as $Val) {
			$this->Filters[$Name][] = $Val;
		}
		$this->SetFilter($Name, $Vals, $Exclude);
	}
	
	function set_filter_range($Name, $Min, $Max, $Exclude) {
		$this->Filters[$Name] = array($Min.'-'.$Max);
		$this->SetFilterRange($Name, $Min, $Max, $Exclude);
	}
	
	function escape_string($String) {
		return strtr($String, array('('=>'\(', ')'=>'\)',  '|'=>'\|',  '-'=>'\-',  '@'=>'\@',  '~'=>'\~',  '&'=>'\&',  '/'=>'\/'));
	}
	
	
}
?>
