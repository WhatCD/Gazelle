<?
//-----------------------------------------------------------------------------------
/////////////////////////////////////////////////////////////////////////////////////
/*//-- MySQL wrapper class ----------------------------------------------------------

This class provides an interface to mysqli. You should always use this class instead
of the mysql/mysqli functions, because this class provides debugging features and a
bunch of other cool stuff.

Everything returned by this class is automatically escaped for output. This can be
turned off by setting $Escape to false in next_record or to_array.

//--------- Basic usage -------------------------------------------------------------

* Creating the object.

require(SERVER_ROOT.'/classes/mysql.class.php');
$DB = NEW DB_MYSQL;
-----

* Making a query

$DB->query("
	SELECT *
	FROM table...");

	Is functionally equivalent to using mysqli_query("SELECT * FROM table...")
	Stores the result set in $this->QueryID
	Returns the result set, so you can save it for later (see set_query_id())
-----

* Getting data from a query

$array = $DB->next_record();
	Is functionally equivalent to using mysqli_fetch_array($ResultSet)
	You do not need to specify a result set - it uses $this-QueryID
-----

* Escaping a string

db_string($str);
	Is a wrapper for $DB->escape_str(), which is a wrapper for
	mysqli_real_escape_string(). The db_string() function exists so that you
	don't have to keep calling $DB->escape_str().

	USE THIS FUNCTION EVERY TIME YOU USE AN UNVALIDATED USER-SUPPLIED VALUE IN
	A DATABASE QUERY!


//--------- Advanced usage ---------------------------------------------------------

* The conventional way of retrieving a row from a result set is as follows:

list($All, $Columns, $That, $You, $Select) = $DB->next_record();
-----

* This is how you loop over the result set:

while (list($All, $Columns, $That, $You, $Select) = $DB->next_record()) {
	echo "Do stuff with $All of the ".$Columns.$That.$You.$Select;
}
-----

* There are also a couple more mysqli functions that have been wrapped. They are:

record_count()
	Wrapper to mysqli_num_rows()

affected_rows()
	Wrapper to mysqli_affected_rows()

inserted_id()
	Wrapper to mysqli_insert_id()

close
	Wrapper to mysqli_close()
-----

* And, of course, a few handy custom functions.

to_array($Key = false)
	Transforms an entire result set into an array (useful in situations where you
	can't order the rows properly in the query).

	If $Key is set, the function uses $Key as the index (good for looking up a
	field). Otherwise, it uses an iterator.

	For an example of this function in action, check out forum.php.

collect($Key)
	Loops over the result set, creating an array from one of the fields ($Key).
	For an example, see forum.php.

set_query_id($ResultSet)
	This class can only hold one result set at a time. Using set_query_id allows
	you to set the result set that the class is using to the result set in
	$ResultSet. This result set should have been obtained earlier by using
	$DB->query().

	Example:

	$FoodRS = $DB->query("
			SELECT *
			FROM food");
	$DB->query("
		SELECT *
		FROM drink");
	$Drinks = $DB->next_record();
	$DB->set_query_id($FoodRS);
	$Food = $DB->next_record();

	Of course, this example is contrived, but you get the point.


-------------------------------------------------------------------------------------
*///---------------------------------------------------------------------------------

if (!extension_loaded('mysqli')) {
	die('Mysqli Extension not loaded.');
}

//Handles escaping
function db_string($String, $DisableWildcards = false) {
	global $DB;
	//Escape
	$String = $DB->escape_str($String);
	//Remove user input wildcards
	if ($DisableWildcards) {
		$String = str_replace(array('%','_'), array('\%','\_'), $String);
	}
	return $String;
}

function db_array($Array, $DontEscape = array(), $Quote = false) {
	foreach ($Array as $Key => $Val) {
		if (!in_array($Key, $DontEscape)) {
			if ($Quote) {
				$Array[$Key] = '\''.db_string(trim($Val)).'\'';
			} else {
				$Array[$Key] = db_string(trim($Val));
			}
		}
	}
	return $Array;
}

//TODO: revisit access levels once Drone is replaced by ZeRobot
class DB_MYSQL {
	public $LinkID = false;
	protected $QueryID = false;
	protected $Record = array();
	protected $Row;
	protected $Errno = 0;
	protected $Error = '';

	public $Queries = array();
	public $Time = 0.0;

	protected $Database = '';
	protected $Server = '';
	protected $User = '';
	protected $Pass = '';
	protected $Port = 0;
	protected $Socket = '';

	function __construct($Database = SQLDB, $User = SQLLOGIN, $Pass = SQLPASS, $Server = SQLHOST, $Port = SQLPORT, $Socket = SQLSOCK) {
		$this->Database = $Database;
		$this->Server = $Server;
		$this->User = $User;
		$this->Pass = $Pass;
		$this->Port = $Port;
		$this->Socket = $Socket;
	}

	function halt($Msg) {
		global $Debug, $argv;
		$DBError = 'MySQL: '.strval($Msg).' SQL error: '.strval($this->Errno).' ('.strval($this->Error).')';
		if ($this->Errno == 1194) {
			send_irc('PRIVMSG '.ADMIN_CHAN.' :'.$this->Error);
		}
		/*if ($this->Errno == 1194) {
			preg_match("Table '(\S+)' is marked as crashed and should be repaired", $this->Error, $Matches);
		} */
		$Debug->analysis('!dev DB Error', $DBError, 3600 * 24);
		if (DEBUG_MODE || check_perms('site_debug') || isset($argv[1])) {
			echo '<pre>'.display_str($DBError).'</pre>';
			if (DEBUG_MODE || check_perms('site_debug')) {
				print_r($this->Queries);
			}
			die();
		} else {
			error('-1');
		}
	}

	function connect() {
		if (!$this->LinkID) {
			$this->LinkID = mysqli_connect($this->Server, $this->User, $this->Pass, $this->Database, $this->Port, $this->Socket); // defined in config.php
			if (!$this->LinkID) {
				$this->Errno = mysqli_connect_errno();
				$this->Error = mysqli_connect_error();
				$this->halt('Connection failed (host:'.$this->Server.':'.$this->Port.')');
			}
		}
	}

	function query($Query, $AutoHandle = 1) {
		global $Debug;
		/*
		 * If there was a previous query, we store the warnings. We cannot do
		 * this immediately after mysqli_query because mysqli_insert_id will
		 * break otherwise due to mysqli_get_warnings sending a SHOW WARNINGS;
		 * query. When sending a query, however, we're sure that we won't call
		 * mysqli_insert_id (or any similar function, for that matter) later on,
		 * so we can safely get the warnings without breaking things.
		 * Note that this means that we have to call $this->warnings manually
		 * for the last query!
		 */
		if ($this->QueryID) {
			$this->warnings();
		}
		$QueryStartTime = microtime(true);
		$this->connect();
		// In the event of a MySQL deadlock, we sleep allowing MySQL time to unlock, then attempt again for a maximum of 5 tries
		for ($i = 1; $i < 6; $i++) {
			$this->QueryID = mysqli_query($this->LinkID, $Query);
			if (!in_array(mysqli_errno($this->LinkID), array(1213, 1205))) {
				break;
			}
			$Debug->analysis('Non-Fatal Deadlock:', $Query, 3600 * 24);
			trigger_error("Database deadlock, attempt $i");

			sleep($i * rand(2, 5)); // Wait longer as attempts increase
		}
		$QueryEndTime = microtime(true);
		$this->Queries[] = array($Query, ($QueryEndTime - $QueryStartTime) * 1000, null);
		$this->Time += ($QueryEndTime - $QueryStartTime) * 1000;

		if (!$this->QueryID) {
			$this->Errno = mysqli_errno($this->LinkID);
			$this->Error = mysqli_error($this->LinkID);

			if ($AutoHandle) {
				$this->halt("Invalid Query: $Query");
			} else {
				return $this->Errno;
			}
		}

		/*
		$QueryType = substr($Query, 0, 6);
		if ($QueryType === 'DELETE' || $QueryType === 'UPDATE') {
			if ($this->affected_rows() > 50) {
				$Debug->analysis($this->affected_rows().' rows altered:', $Query, 3600 * 24);
			}
		}
		*/
		$this->Row = 0;
		if ($AutoHandle) {
			return $this->QueryID;
		}
	}

	function query_unb($Query) {
		$this->connect();
		mysqli_real_query($this->LinkID, $Query);
	}

	function inserted_id() {
		if ($this->LinkID) {
			return mysqli_insert_id($this->LinkID);
		}
	}

	function next_record($Type = MYSQLI_BOTH, $Escape = true) { // $Escape can be true, false, or an array of keys to not escape
		if ($this->LinkID) {
			$this->Record = mysqli_fetch_array($this->QueryID, $Type);
			$this->Row++;
			if (!is_array($this->Record)) {
				$this->QueryID = false;
			} elseif ($Escape !== false) {
				$this->Record = Misc::display_array($this->Record, $Escape);
			}
			return $this->Record;
		}
	}

	function close() {
		if ($this->LinkID) {
			if (!mysqli_close($this->LinkID)) {
				$this->halt('Cannot close connection or connection did not open.');
			}
			$this->LinkID = false;
		}
	}

	/*
	 * returns an integer with the number of rows found
	 * returns a string if the number of rows found exceeds MAXINT
	 */
	function record_count() {
		if ($this->QueryID) {
			return mysqli_num_rows($this->QueryID);
		}
	}

	/*
	 * returns true if the query exists and there were records found
	 * returns false if the query does not exist or if there were 0 records returned
	 */
	function has_results() {
		return ($this->QueryID && $this->record_count() !== 0);
	}

	function affected_rows() {
		if ($this->LinkID) {
			return mysqli_affected_rows($this->LinkID);
		}
	}

	function info() {
		return mysqli_get_host_info($this->LinkID);
	}

	// You should use db_string() instead.
	function escape_str($Str) {
		$this->connect(0);
		if (is_array($Str)) {
			trigger_error('Attempted to escape array.');
			return '';
		}
		return mysqli_real_escape_string($this->LinkID, $Str);
	}

	// Creates an array from a result set
	// If $Key is set, use the $Key column in the result set as the array key
	// Otherwise, use an integer
	function to_array($Key = false, $Type = MYSQLI_BOTH, $Escape = true) {
		$Return = array();
		while ($Row = mysqli_fetch_array($this->QueryID, $Type)) {
			if ($Escape !== false) {
				$Row = Misc::display_array($Row, $Escape);
			}
			if ($Key !== false) {
				$Return[$Row[$Key]] = $Row;
			} else {
				$Return[] = $Row;
			}
		}
		mysqli_data_seek($this->QueryID, 0);
		return $Return;
	}

	//  Loops through the result set, collecting the $ValField column into an array with $KeyField as keys
	function to_pair($KeyField, $ValField, $Escape = true) {
		$Return = array();
		while ($Row = mysqli_fetch_array($this->QueryID)) {
			if ($Escape) {
				$Key = display_str($Row[$KeyField]);
				$Val = display_str($Row[$ValField]);
			} else {
				$Key = $Row[$KeyField];
				$Val = $Row[$ValField];
			}
			$Return[$Key] = $Val;
		}
		mysqli_data_seek($this->QueryID, 0);
		return $Return;
	}

	//  Loops through the result set, collecting the $Key column into an array
	function collect($Key, $Escape = true) {
		$Return = array();
		while ($Row = mysqli_fetch_array($this->QueryID)) {
			$Return[] = $Escape ? display_str($Row[$Key]) : $Row[$Key];
		}
		mysqli_data_seek($this->QueryID, 0);
		return $Return;
	}

	function set_query_id(&$ResultSet) {
		$this->QueryID = $ResultSet;
		$this->Row = 0;
	}

	function get_query_id() {
		return $this->QueryID;
	}

	function beginning() {
		mysqli_data_seek($this->QueryID, 0);
		$this->Row = 0;
	}

	/**
	 * This function determines whether the last query caused warning messages
	 * and stores them in $this->Queries.
	 */
	function warnings() {
		$Warnings = array();
		if (mysqli_warning_count($this->LinkID)) {
			$e = mysqli_get_warnings($this->LinkID);
			do {
				if ($e->errno == 1592) {
					// 1592: Unsafe statement written to the binary log using statement format since BINLOG_FORMAT = STATEMENT.
					continue;
				}
				$Warnings[] = 'Code ' . $e->errno . ': ' . display_str($e->message);
			} while ($e->next());
		}
		$this->Queries[count($this->Queries) - 1][2] = $Warnings;
	}
}
?>
