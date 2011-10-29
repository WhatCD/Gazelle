<?
// Debug info for developers

define('MAX_TIME', 20000); //Maximum execution time in ms
define('MAX_ERRORS', 0); //Maxmimum errors, warnings, notices we will allow in a page
define('MAX_MEMORY', 80*1024*1024); //Maximum memory used per pageload
define('MAX_QUERIES', 30); //Maxmimum queries

class DEBUG {
	public $Errors = array();
	public $Flags = array();
	private $LoggedVars = array();

	public function profile($Automatic='') {
		global $ScriptStartTime;
		$Reason = array();

		if (!empty($Automatic)) {
			$Reason[] = $Automatic;
		}

		$Micro = (microtime(true)-$ScriptStartTime)*1000;
		if ($Micro > MAX_TIME && !defined('TIME_EXCEPTION')) {
			$Reason[] = number_format($Micro, 3).' ms';
		}

		$Errors = count($this->get_errors());
		if ($Errors > MAX_ERRORS && !defined('ERROR_EXCEPTION')) {
			$Reason[] = $Errors.' PHP Errors';
		}
		/*
		$Queries = count($this->get_queries());
		if ($Queries > MAX_QUERIES && !defined('QUERY_EXCEPTION')) {
			$Reason[] = $Queries.' Queries';
		}
		*/
		$Ram = memory_get_usage(true);
		if ($Ram > MAX_MEMORY && !defined('MEMORY_EXCEPTION')) {
			$Reason[] = get_size($Ram).' Ram Used';
		}

		if (isset($_REQUEST['profile'])) {
			global $LoggedUser;
			$Reason[] = 'Requested by '.$LoggedUser['Username'];
		}

		if (isset($Reason[0])) {
			$this->analysis(implode(', ', $Reason));
			return true;
		}

		return false;
	}

	public function analysis($Message, $Report='', $Time=43200) {
		global $Cache, $Document;
		if (empty($Report)) {
			$Report = $Message;
		}
		$Identifier = make_secret(5);
		$Cache->cache_value(
			'analysis_'.$Identifier,
			array(
				'url' => $_SERVER['REQUEST_URI'],
				'message' => $Report,
				'errors' => $this->get_errors(true),
				'queries' => $this->get_queries(),
				'flags' => $this->get_flags(),
				'includes' => $this->get_includes(),
				'cache' => $this->get_cache_keys(),
				'vars' => $this->get_logged_vars()
			),
			$Time
		);
		send_irc('PRIVMSG '.LAB_CHAN.' :'.$Message.' '.$Document.' '.' http://'.NONSSL_SITE_URL.'/tools.php?action=analysis&case='.$Identifier.' http://'.NONSSL_SITE_URL.$_SERVER['REQUEST_URI']);
	}

	public function log_var($Var, $VarName = FALSE) {
		$BackTrace = debug_backtrace();
		$ID = uniqid();
		if(!$VarName) {
			$VarName = $ID;
		}
		$File = array('path' => substr($BackTrace[0]['file'], strlen(SERVER_ROOT)), 'line' => $BackTrace[0]['line']);
		$this->LoggedVars[$ID] = array($VarName => array('bt' => $File, 'data' => $Var));
	}

	public function set_flag($Event) {
		global $ScriptStartTime;
		$this->Flags[] = array($Event,(microtime(true)-$ScriptStartTime)*1000,memory_get_usage(true));
	}

	//This isn't in the constructor because $this is not available, and the function cannot be made static
	public function handle_errors() {
		//error_reporting(E_ALL ^ E_STRICT | E_WARNING | E_DEPRECATED | E_ERROR | E_PARSE); //E_STRICT disabled
		error_reporting(E_WARNING | E_ERROR | E_PARSE);
		set_error_handler(array($this, 'php_error_handler'));
	}

	protected function format_args($Array) {
		$LastKey = -1;
		$Return = array();
		foreach ($Array as $Key => $Val) {
			$Return[$Key] = '';
			if (!is_int($Key) || $Key != $LastKey+1) {
				$Return[$Key] .= "'$Key' => ";
			}
				if ($Val === true) {
					$Return[$Key] .= "true";
				} elseif ($Val === false) {
					$Return[$Key] .= "false";
				} elseif (is_string($Val)) {
					$Return[$Key] .= "'$Val'";
				} elseif (is_int($Val)) {
					$Return[$Key] .= $Val;
				} elseif (is_object($Val)) {
					$Return[$Key] .= get_class($Val);
				} elseif (is_array($Val)) {
					$Return[$Key] .= 'array('.$this->format_args($Val).')';
				}
			$LastKey = $Key;
		}
		return implode(', ', $Return);
	}

	public function php_error_handler($Level, $Error, $File, $Line) {
		//Who added this, it's still something to pay attention to...
		if (stripos('Undefined index', $Error) !== false) {
			//return true;
		}

		$Steps = 1; //Steps to go up in backtrace, default one
		$Call = '';
		$Args = '';
		$Tracer = debug_backtrace();

		//This is in case something in this function goes wrong and we get stuck with an infinite loop
		if (isset($Tracer[$Steps]['function'], $Tracer[$Steps]['class']) && $Tracer[$Steps]['function'] == 'php_error_handler' && $Tracer[$Steps]['class'] == 'DEBUG') {
			return true;
		}

		//If this error was thrown, we return the function which threw it
		if (isset($Tracer[$Steps]['function']) && $Tracer[$Steps]['function'] == 'trigger_error') {
			$Steps++;
			$File = $Tracer[$Steps]['file'];
			$Line = $Tracer[$Steps]['line'];
		}

		//At this time ONLY Array strict typing is fully supported.
		//Allow us to abuse strict typing (IE: function test(Array))
		if (preg_match('/^Argument (\d+) passed to \S+ must be an (array), (array|string|integer|double|object) given, called in (\S+) on line (\d+) and defined$/', $Error, $Matches)) {
			$Error = 'Type hinting failed on arg '.$Matches[1]. ', expected '.$Matches[2].' but found '.$Matches[3];
			$File = $Matches[4];
			$Line = $Matches[5];
		}

		//Lets not be repetative
		if (($Tracer[$Steps]['function'] == 'include' || $Tracer[$Steps]['function'] == 'require' ) && isset($Tracer[$Steps]['args'][0]) && $Tracer[$Steps]['args'][0] == $File) {
			unset($Tracer[$Steps]['args']);
		}

		//Class
		if (isset($Tracer[$Steps]['class'])) {
			$Call .= $Tracer[$Steps]['class'].'::';
		}

		//Function & args
		if (isset($Tracer[$Steps]['function'])) {
			$Call .= $Tracer[$Steps]['function'];
			if (isset($Tracer[$Steps]['args'][0])) {
				$Args = $this->format_args($Tracer[$Steps]['args']);
			}
		}

		//Shorten the path & we're done
		$File = str_replace(SERVER_ROOT, '', $File);
		$Error = str_replace(SERVER_ROOT, '', $Error);

		/*
		//Hiding "session_start(): Server 10.10.0.1 (tcp 11211) failed with: No route to host (113)" errors
		if($Call != "session_start") {
			$this->Errors[] = array($Error, $File.':'.$Line, $Call, $Args);
		}
		*/
		return true;
	}

	/* Data wrappers */

	public function get_flags() {
		return $this->Flags;
	}

	public function get_errors($Light=false) {
		//Because the cache can't take some of these variables
		if ($Light) {
			foreach ($this->Errors as $Key => $Value) {
				$this->Errors[$Key][3] = '';
			}
		}
		return $this->Errors;
	}

	public function get_constants() {
		return get_defined_constants(true);
	}

	public function get_classes() {
		foreach (get_declared_classes() as $Class) {
			$Classes[$Class]['Vars'] = get_class_vars($Class);
			$Classes[$Class]['Functions'] = get_class_methods($Class);
		}
		return $Classes;
	}

	public function get_extensions() {
		foreach (get_loaded_extensions() as $Extension) {
			$Extensions[$Extension]['Functions'] = get_extension_funcs($Extension);
		}
		return $Extensions;
	}

	public function get_includes() {
		return get_included_files();
	}

	public function get_cache_time() {
		global $Cache;
		return $Cache->Time;
	}

	public function get_cache_keys() {
		global $Cache;
		return array_keys($Cache->CacheHits);
	}

	public function get_sphinx_queries() {
		global $SS;
		return $SS->Queries;
	}

	public function get_sphinx_time() {
		global $SS;
		return $SS->Time;
	}

	public function get_queries() {
		global $DB;
		return $DB->Queries;
	}

	public function get_query_time() {
		global $DB;
		return $DB->Time;
	}

	public function get_logged_vars() {
		return $this->LoggedVars;
	}

	/* Output Formatting */

	public function include_table($Includes=false) {
		if (!is_array($Includes)) {
			$Includes = $this->get_includes();
		}
?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_include').toggle();return false;">(View)</a> <?=number_format(count($Includes))?> Includes:</strong></td>
		</tr>
	</table>
	<table id="debug_include" class="debug_table hidden" width="100%">
<?
		foreach ($Includes as $File) {
?>
		<tr valign="top">
			<td><?=$File?></td>
		</tr>
<?
		}
?>
	</table>
<?
	}

	public function class_table($Classes=false) {
		if (!is_array($Classes)) {
			$Classes = $this->get_classes();
		}
?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_classes').toggle();return false;">(View)</a> Classes:</strong></td>
		</tr>
	</table>
	<table id="debug_classes" class="debug_table hidden" width="100%">
		<tr>
			<td align="left">
				<pre><? print_r($Classes) ?></pre>
			</td>
		</tr>
	</table>
<?
	}

	public function extension_table() {
?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_extensions').toggle();return false;">(View)</a> Extensions:</strong></td>
		</tr>
	</table>
	<table id="debug_extensions" class="debug_table hidden" width="100%">
		<tr>
			<td align="left">
				<pre><? print_r($this->get_extensions()) ?></pre>
			</td>
		</tr>
	</table>
<?
	}

	public function flag_table($Flags=false) {
		if (!is_array($Flags)) {
			$Flags = $this->get_flags();
		}
		if (empty($Flags)) {
			return;
		}
?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_flags').toggle();return false;">(View)</a> Flags:</strong></td>
		</tr>
	</table>
	<table id="debug_flags" class="debug_table hidden" width="100%">
<?
		foreach ($Flags as $Flag) {
			list($Event,$MicroTime,$Memory) = $Flag;
?>
		<tr valign="top">
			<td align="left"><?=$Event?></td>
			<td align="left"><?=$MicroTime?> ms</td>
			<td align="left"><?=get_size($Memory)?></td>
		</tr>
<?
		}
?>
	</table>
<?
	}

	public function constant_table($Constants=false) {
		if (!is_array($Constants)) {
			$Constants = $this->get_constants();
		}
?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_constants').toggle();return false;">(View)</a> Constants:</strong></td>
		</tr>
	</table>
	<table id="debug_constants" class="debug_table hidden" width="100%">
		<tr>
			<td align="left" class="debug_data debug_constants_data">
				<pre><?=display_str(print_r($Constants, true))?></pre>
			</td>
		</tr>
	</table>
<?
	}

	public function cache_table($CacheKeys=false) {
		global $Cache;
		$Header = 'Cache Keys';
		if (!is_array($CacheKeys)) {
			$CacheKeys = $this->get_cache_keys();
			$Header .= ' ('.number_format($this->get_cache_time(), 5).' ms)';
		}
		if (empty($CacheKeys)) {
			return;
		}
		$Header = ' '.number_format(count($CacheKeys)).' '.$Header.':';

?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_cache').toggle();return false;">(View)</a><?=$Header?></strong></td>
		</tr>
	</table>
	<table id="debug_cache" class="debug_table hidden" width="100%">
<? 		foreach($CacheKeys as $Key) { ?>
		<tr>
			<td align="left">
				<a href="#" onclick="$('#debug_cache_<?=$Key?>').toggle(); return false;"><?=display_str($Key)?></a>
			</td>
			<td align="left" class="debug_data debug_cache_data">
				<pre id="debug_cache_<?=$Key?>" class="hidden"><?=display_str(print_r($Cache->get_value($Key, true), true))?></pre>
			</td>
		</tr>
<?		} ?>
	</table>
<?
	}

	public function error_table($Errors=false) {
		if (!is_array($Errors)) {
			$Errors = $this->get_errors();
		}
		if (empty($Errors)) {
			return;
		}
?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_error').toggle();return false;">(View)</a> <?=number_format(count($Errors))?> Errors:</strong></td>
		</tr>
	</table>
	<table id="debug_error" class="debug_table hidden" width="100%">
<?
		foreach ($Errors as $Error) {
			list($Error,$Location,$Call,$Args) = $Error;
?>
		<tr valign="top">
			<td align="left"><?=display_str($Call)?>(<?=display_str($Args)?>)</td>
			<td class="debug_data debug_error_data" align="left"><?=display_str($Error)?></td>
			<td align="left"><?=display_str($Location)?></td>
		</tr>
<?
		}
?>
	</table>
<?
	}

	public function query_table($Queries=false) {
		$Header = 'Queries';
		if (!is_array($Queries)) {
			$Queries = $this->get_queries();
			$Header .= ' ('.number_format($this->get_query_time(), 5).' ms)';
		}
		if (empty($Queries)) {
			return;
		}
		$Header = ' '.number_format(count($Queries)).' '.$Header.':';
?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_database').toggle();return false;">(View)</a><?=$Header?></strong></td>
		</tr>
	</table>
	<table id="debug_database" class="debug_table hidden" width="100%">
<?
		foreach ($Queries as $Query) {
			list($SQL,$Time) = $Query;
?>
		<tr valign="top">
			<td class="debug_data debug_query_data"><div><?=str_replace("\t", '&nbsp;&nbsp;', nl2br(display_str($SQL)))?></div></td>
			<td class="rowa" style="width:130px;" align="left"><?=number_format($Time, 5)?> ms</td>
		</tr>
<?
		}
?>
	</table>
<?
	}

	public function sphinx_table($Queries=false) {
		$Header = 'Searches';
		if (!is_array($Queries)) {
			$Queries = $this->get_sphinx_queries();
			$Header .= ' ('.number_format($this->get_sphinx_time(), 5).' ms)';
		}
		if (empty($Queries)) {
			return;
		}
		$Header = ' '.number_format(count($Queries)).' '.$Header.':';
?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_sphinx').toggle();return false;">(View)</a><?=$Header?></strong></td>
		</tr>
	</table>
	<table id="debug_sphinx" class="debug_table hidden" width="100%">
<?
		foreach ($Queries as $Query) {
			list($Params,$Time) = $Query;
?>
		<tr valign="top">
			<td class="debug_data debug_sphinx_data"><pre><?=str_replace("\t", '	', display_str($Params))?></pre></td>
			<td class="rowa" style="width:130px;" align="left"><?=number_format($Time, 5)?> ms</td>
		</tr>
<?
		}
?>
	</table>
<?
	}

	public function vars_table($Vars=false) {
		$Header = 'Logged Variables';
		if (empty($Vars)) {
			if(empty($this->LoggedVars)) {
				return;
			}
			$Vars = $this->LoggedVars;
		}
		$Header = ' '.number_format(count($Vars)).' '.$Header.':';

?>
	<table width="100%">
		<tr>
			<td align="left"><strong><a href="#" onclick="$('#debug_loggedvars').toggle();return false;">(View)</a><?=$Header?></strong></td>
		</tr>
	</table>
	<table id="debug_loggedvars" class="debug_table hidden" width="100%">
<?
		foreach($Vars as $ID => $Var) {
			list($Key, $Data) = each($Var);
			$Size = count($Data['data']);
?>
		<tr>
			<td align="left">
				<a href="#" onclick="$('#debug_loggedvars_<?=$ID?>').toggle(); return false;"><?=display_str($Key)?></a> (<?=$Size . ($Size == 1 ? ' element' : ' elements')?>)
				<div><?=$Data['bt']['path'].':'.$Data['bt']['line'];?></div>
			</td>
			<td class="debug_data debug_loggedvars_data" align="left">
				<pre id="debug_loggedvars_<?=$ID?>" class="hidden"><?=display_str(print_r($Data['data'], true));?></pre>
			</td>
		</tr>
<?		} ?>
	</table>
<?
	}
}
