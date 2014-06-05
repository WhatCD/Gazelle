<?
/*************************************************************************|
|--------------- Caching class -------------------------------------------|
|*************************************************************************|

This class is a wrapper for the Memcache class, and it's been written in
order to better handle the caching of full pages with bits of dynamic
content that are different for every user.

As this inherits memcache, all of the default memcache methods work -
however, this class has page caching functions superior to those of
memcache.

Also, Memcache::get and Memcache::set have been wrapped by
CACHE::get_value and CACHE::cache_value. get_value uses the same argument
as get, but cache_value only takes the key, the value, and the duration
(no zlib).

// Unix sockets
memcached -d -m 5120 -s /var/run/memcached.sock -a 0777 -t16 -C -u root

// TCP bind
memcached -d -m 8192 -l 10.10.0.1 -t8 -C

|*************************************************************************/

if (!extension_loaded('memcache')) {
	die('Memcache Extension not loaded.');
}

class CACHE extends Memcache {
	/**
	 * Torrent Group cache version
	 */
	const GROUP_VERSION = 5;

	public $CacheHits = array();
	public $MemcacheDBArray = array();
	public $MemcacheDBKey = '';
	protected $InTransaction = false;
	public $Time = 0;
	private $Servers = array();
	private $PersistentKeys = array(
		'ajax_requests_*',
		'query_lock_*',
		'stats_*',
		'top10tor_*',
		'top10votes_*',
		'users_snatched_*',

		// Cache-based features
		'global_notification',
		'notifications_one_reads_*',
	);
	private $ClearedKeys = array();

	public $CanClear = false;
	public $InternalCache = true;

	function __construct($Servers) {
		$this->Servers = $Servers;
		foreach ($Servers as $Server) {
			$this->addServer($Server['host'], $Server['port'], true, $Server['buckets']);
		}
	}

	//---------- Caching functions ----------//

	// Allows us to set an expiration on otherwise perminantly cache'd values
	// Useful for disabled users, locked threads, basically reducing ram usage
	public function expire_value($Key, $Duration = 2592000) {
		$StartTime = microtime(true);
		$this->set($Key, $this->get($Key), $Duration);
		$this->Time += (microtime(true) - $StartTime) * 1000;
	}

	// Wrapper for Memcache::set, with the zlib option removed and default duration of 30 days
	public function cache_value($Key, $Value, $Duration = 2592000) {
		$StartTime = microtime(true);
		if (empty($Key)) {
			trigger_error("Cache insert failed for empty key");
		}
		if (!$this->set($Key, $Value, 0, $Duration)) {
			trigger_error("Cache insert failed for key $Key");
		}
		if ($this->InternalCache && array_key_exists($Key, $this->CacheHits)) {
			$this->CacheHits[$Key] = $Value;
		}
		$this->Time += (microtime(true) - $StartTime) * 1000;
	}

	// Wrapper for Memcache::add, with the zlib option removed and default duration of 30 days
	public function add_value($Key, $Value, $Duration = 2592000) {
		$StartTime = microtime(true);
		$Added = $this->add($Key, $Value, 0, $Duration);
		$this->Time += (microtime(true) - $StartTime) * 1000;
		return $Added;
	}

	public function replace_value($Key, $Value, $Duration = 2592000) {
		$StartTime = microtime(true);
		$this->replace($Key, $Value, false, $Duration);
		if ($this->InternalCache && array_key_exists($Key, $this->CacheHits)) {
			$this->CacheHits[$Key] = $Value;
		}
		$this->Time += (microtime(true) - $StartTime) * 1000;
	}

	public function get_value($Key, $NoCache = false) {
		if (!$this->InternalCache) {
			$NoCache = true;
		}
		$StartTime = microtime(true);
		if (empty($Key)) {
			trigger_error('Cache retrieval failed for empty key');
		}

		if (!empty($_GET['clearcache']) && $this->CanClear && !isset($this->ClearedKeys[$Key]) && !Misc::in_array_partial($Key, $this->PersistentKeys)) {
			if ($_GET['clearcache'] === '1') {
				// Because check_perms() isn't true until LoggedUser is pulled from the cache, we have to remove the entries loaded before the LoggedUser data
				// Because of this, not user cache data will require a secondary pageload following the clearcache to update
				if (count($this->CacheHits) > 0) {
					foreach (array_keys($this->CacheHits) as $HitKey) {
						if (!isset($this->ClearedKeys[$HitKey]) && !Misc::in_array_partial($HitKey, $this->PersistentKeys)) {
							$this->delete($HitKey);
							unset($this->CacheHits[$HitKey]);
							$this->ClearedKeys[$HitKey] = true;
						}
					}
				}
				$this->delete($Key);
				$this->Time += (microtime(true) - $StartTime) * 1000;
				return false;
			} elseif ($_GET['clearcache'] == $Key) {
				$this->delete($Key);
				$this->Time += (microtime(true) - $StartTime) * 1000;
				return false;
			} elseif (substr($_GET['clearcache'], -1) === '*') {
				$Prefix = substr($_GET['clearcache'], 0, -1);
				if ($Prefix === '' || $Prefix === substr($Key, 0, strlen($Prefix))) {
					$this->delete($Key);
					$this->Time += (microtime(true) - $StartTime) * 1000;
					return false;
				}
			}
			$this->ClearedKeys[$Key] = true;
		}

		// For cases like the forums, if a key is already loaded, grab the existing pointer
		if (isset($this->CacheHits[$Key]) && !$NoCache) {
			$this->Time += (microtime(true) - $StartTime) * 1000;
			return $this->CacheHits[$Key];
		}

		$Return = $this->get($Key);
		if ($Return !== false) {
			$this->CacheHits[$Key] = $NoCache ? null : $Return;
		}
		$this->Time += (microtime(true) - $StartTime) * 1000;
		return $Return;
	}

	// Wrapper for Memcache::delete. For a reason, see above.
	public function delete_value($Key) {
		$StartTime = microtime(true);
		if (empty($Key)) {
			trigger_error('Cache deletion failed for empty key');
		}
		if (!$this->delete($Key)) {
			//trigger_error("Cache delete failed for key $Key");
		}
		unset($this->CacheHits[$Key]);
		$this->Time += (microtime(true) - $StartTime) * 1000;
	}

	public function increment_value($Key, $Value = 1) {
		$StartTime = microtime(true);
		$NewVal = $this->increment($Key, $Value);
		if (isset($this->CacheHits[$Key])) {
			$this->CacheHits[$Key] = $NewVal;
		}
		$this->Time += (microtime(true) - $StartTime) * 1000;
	}

	public function decrement_value($Key, $Value = 1) {
		$StartTime = microtime(true);
		$NewVal = $this->decrement($Key, $Value);
		if (isset($this->CacheHits[$Key])) {
			$this->CacheHits[$Key] = $NewVal;
		}
		$this->Time += (microtime(true) - $StartTime) * 1000;
	}

	//---------- memcachedb functions ----------//

	public function begin_transaction($Key) {
		$Value = $this->get($Key);
		if (!is_array($Value)) {
			$this->InTransaction = false;
			$this->MemcacheDBKey = array();
			$this->MemcacheDBKey = '';
			return false;
		}
		$this->MemcacheDBArray = $Value;
		$this->MemcacheDBKey = $Key;
		$this->InTransaction = true;
		return true;
	}

	public function cancel_transaction() {
		$this->InTransaction = false;
		$this->MemcacheDBKey = array();
		$this->MemcacheDBKey = '';
	}

	public function commit_transaction($Time = 2592000) {
		if (!$this->InTransaction) {
			return false;
		}
		$this->cache_value($this->MemcacheDBKey, $this->MemcacheDBArray, $Time);
		$this->InTransaction = false;
	}

	// Updates multiple rows in an array
	public function update_transaction($Rows, $Values) {
		if (!$this->InTransaction) {
			return false;
		}
		$Array = $this->MemcacheDBArray;
		if (is_array($Rows)) {
			$i = 0;
			$Keys = $Rows[0];
			$Property = $Rows[1];
			foreach ($Keys as $Row) {
				$Array[$Row][$Property] = $Values[$i];
				$i++;
			}
		} else {
			$Array[$Rows] = $Values;
		}
		$this->MemcacheDBArray = $Array;
	}

	// Updates multiple values in a single row in an array
	// $Values must be an associative array with key:value pairs like in the array we're updating
	public function update_row($Row, $Values) {
		if (!$this->InTransaction) {
			return false;
		}
		if ($Row === false) {
			$UpdateArray = $this->MemcacheDBArray;
		} else {
			$UpdateArray = $this->MemcacheDBArray[$Row];
		}
		foreach ($Values as $Key => $Value) {
			if (!array_key_exists($Key, $UpdateArray)) {
				trigger_error('Bad transaction key ('.$Key.') for cache '.$this->MemcacheDBKey);
			}
			if ($Value === '+1') {
				if (!is_number($UpdateArray[$Key])) {
					trigger_error('Tried to increment non-number ('.$Key.') for cache '.$this->MemcacheDBKey);
				}
				++$UpdateArray[$Key]; // Increment value
			} elseif ($Value === '-1') {
				if (!is_number($UpdateArray[$Key])) {
					trigger_error('Tried to decrement non-number ('.$Key.') for cache '.$this->MemcacheDBKey);
				}
				--$UpdateArray[$Key]; // Decrement value
			} else {
				$UpdateArray[$Key] = $Value; // Otherwise, just alter value
			}
		}
		if ($Row === false) {
			$this->MemcacheDBArray = $UpdateArray;
		} else {
			$this->MemcacheDBArray[$Row] = $UpdateArray;
		}
	}

	// Increments multiple values in a single row in an array
	// $Values must be an associative array with key:value pairs like in the array we're updating
	public function increment_row($Row, $Values) {
		if (!$this->InTransaction) {
			return false;
		}
		if ($Row === false) {
			$UpdateArray = $this->MemcacheDBArray;
		} else {
			$UpdateArray = $this->MemcacheDBArray[$Row];
		}
		foreach ($Values as $Key => $Value) {
			if (!array_key_exists($Key, $UpdateArray)) {
				trigger_error("Bad transaction key ($Key) for cache ".$this->MemcacheDBKey);
			}
			if (!is_number($Value)) {
				trigger_error("Tried to increment with non-number ($Key) for cache ".$this->MemcacheDBKey);
			}
			$UpdateArray[$Key] += $Value; // Increment value
		}
		if ($Row === false) {
			$this->MemcacheDBArray = $UpdateArray;
		} else {
			$this->MemcacheDBArray[$Row] = $UpdateArray;
		}
	}

	// Insert a value at the beginning of the array
	public function insert_front($Key, $Value) {
		if (!$this->InTransaction) {
			return false;
		}
		if ($Key === '') {
			array_unshift($this->MemcacheDBArray, $Value);
		} else {
			$this->MemcacheDBArray = array($Key=>$Value) + $this->MemcacheDBArray;
		}
	}

	// Insert a value at the end of the array
	public function insert_back($Key, $Value) {
		if (!$this->InTransaction) {
			return false;
		}
		if ($Key === '') {
			array_push($this->MemcacheDBArray, $Value);
		} else {
			$this->MemcacheDBArray = $this->MemcacheDBArray + array($Key=>$Value);
		}

	}

	public function insert($Key, $Value) {
		if (!$this->InTransaction) {
			return false;
		}
		if ($Key === '') {
			$this->MemcacheDBArray[] = $Value;
		} else {
			$this->MemcacheDBArray[$Key] = $Value;
		}
	}

	public function delete_row($Row) {
		if (!$this->InTransaction) {
			return false;
		}
		if (!isset($this->MemcacheDBArray[$Row])) {
			trigger_error("Tried to delete non-existent row ($Row) for cache ".$this->MemcacheDBKey);
		}
		unset($this->MemcacheDBArray[$Row]);
	}

	public function update($Key, $Rows, $Values, $Time = 2592000) {
		if (!$this->InTransaction) {
			$this->begin_transaction($Key);
			$this->update_transaction($Rows, $Values);
			$this->commit_transaction($Time);
		} else {
			$this->update_transaction($Rows, $Values);
		}
	}

	/**
	 * Tries to set a lock. Expiry time is one hour to avoid indefinite locks
	 *
	 * @param string $LockName name on the lock
	 * @return true if lock was acquired
	 */
	public function get_query_lock($LockName) {
		return $this->add_value('query_lock_'.$LockName, 1, 3600);
	}

	/**
	 * Remove lock
	 *
	 * @param string $LockName name on the lock
	 */
	public function clear_query_lock($LockName) {
		$this->delete_value('query_lock_'.$LockName);
	}

	/**
	 * Get cache server status
	 *
	 * @return array (host => bool status, ...)
	 */
	public function server_status() {
		$Status = array();
		foreach ($this->Servers as $Server) {
			$Status["$Server[host]:$Server[port]"] = $this->getServerStatus($Server['host'], $Server['port']);
		}
		return $Status;
	}
}
