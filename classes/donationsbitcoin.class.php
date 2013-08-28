<?
class DonationsBitcoin {
	/**
	 * Ask bitcoind for a list of all addresses that have received bitcoins
	 *
	 * @return array (BitcoinAddress => Amount, ...)
	 */
	public static function get_received() {
		if (defined('BITCOIN_RPC_URL')) {
			$Donations = BitcoinRpc::listreceivedbyaddress();
		}
		if (empty($Donations)) {
			return array();
		}
		$BTCUsers = array();
		foreach ($Donations as $Account) {
			$BTCUsers[$Account->address] = $Account->amount;
		}
		return $BTCUsers;
	}

	/**
	 * Ask bitcoind for the current account balance
	 *
	 * @return float balance
	 */
	public static function get_balance() {
		if (defined('BITCOIN_RPC_URL')) {
			return BitcoinRpc::getbalance();
		}
	}

	/**
	 * Get a user's existing bitcoin address or generate a new one
	 *
	 * @param int $UserID
	 * @param bool $GenAddress whether to create a new address if it doesn't exist
	 * @return false if no address exists and $GenAddress is false
	 *         string bitcoin address otherwise
	 */
	public static function get_address($UserID, $GenAddress = false) {
		$UserID = (int)$UserID;
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT BitcoinAddress
			FROM users_info
			WHERE UserID = '$UserID'");
		list($Addr) = G::$DB->next_record();
		G::$DB->set_query_id($QueryID);

		if (!empty($Addr)) {
			return $Addr;
		} elseif ($GenAddress) {
			if (defined('BITCOIN_RPC_URL')) {
				$NewAddr = BitcoinRpc::getnewaddress();
			}
			if (empty($NewAddr)) {
				error(0);
			}
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				UPDATE users_info
				SET BitcoinAddress = '".db_string($NewAddr)."'
				WHERE UserID = '$UserID'
					AND BitcoinAddress IS NULL");
			G::$DB->set_query_id($QueryID);
			return $NewAddr;
		} else {
			return false;
		}
	}

	/**
	 * Ask bitcoind for the total amount of bitcoins received
	 *
	 * @return float amount
	 */
	public static function get_total_received() {
		if (defined('BITCOIN_RPC_URL')) {
			$Accounts = BitcoinRpc::listreceivedbyaccount();
		}
		if (empty($Accounts)) {
			return 0.0;
		}
		foreach ($Accounts as $Account) {
			if ($Account->account == '') {
				return $Account->amount;
			}
		}
		return 0.0;
	}

	/**
	 * Translate bitcoin addresses to user IDs
	 *
	 * @param array $Addresses list of bitcoin addresses
	 * @return array (BitcoinAddress => UserID, ...)
	 */
	public static function get_userids($Addresses) {
		if (!is_array($Addresses) || empty($Addresses)) {
			return false;
		}
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT BitcoinAddress, UserID
			FROM users_info
			WHERE BitcoinAddress IN ('" . implode("', '", $Addresses) . "')");
		if (G::$DB->has_results()) {
			$UserIDs = G::$DB->to_pair(0, 1);
		} else {
			$UserIDs = array();
		}
		G::$DB->set_query_id($QueryID);
		return $UserIDs;
	}

	/**
	 * Find and process new donations since the last time this function was called.
	 */
	public static function find_new_donations() {
		global $Debug;
		if (($OldAmount = G::$Cache->get_value('btc_total_received')) === false) {
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT IFNULL(SUM(Amount), 0)
				FROM donations_bitcoin");
			list($OldAmount) = G::$DB->next_record(MYSQLI_NUM, false);
			G::$DB->set_query_id($QueryID);
		}
		$NewAmount = self::get_total_received();
		if ($NewAmount < $OldAmount) {
			// This shouldn't happen. Perhaps bitcoind was restarted recently
			// or the block index was removed. Either way, try again later
			send_irc('PRIVMSG ' . LAB_CHAN . " :Bad bitcoin donation data (is $NewAmount, was $OldAmount). If this persists, something is probably wrong");
			return false;
		}
		if ($NewAmount > $OldAmount) {
			// I really wish we didn't have to do it like this
			$QueryID = G::$DB->get_query_id();
			G::$DB->query("
				SELECT BitcoinAddress, SUM(Amount)
				FROM donations_bitcoin
				GROUP BY BitcoinAddress");
			$OldDonations = G::$DB->to_pair(0, 1, false);
			G::$DB->set_query_id($QueryID);
			$NewDonations = self::get_received();
			foreach ($NewDonations as $Address => &$Amount) {
				if (isset($OldDonations[$Address])) {
					if ($Amount == $OldDonations[$Address]) { // Direct comparison should be fine as everything comes from bitcoind
						unset($NewDonations[$Address]);
						continue;
					}
					$Debug->log_var(array('old' => $OldDonations[$Address], 'new' => $Amount), "New donations from $Address");
					// PHP doesn't do fixed-point math, and json_decode has already botched the precision
					// so let's just round this off to satoshis and pray that we're on a 64 bit system
					$Amount = round($Amount - $OldDonations[$Address], 8);
				}
				$NewDonations[$Address] = $Amount;
			}
			$Debug->log_var($NewDonations, '$NewDonations');
			foreach (self::get_userids(array_keys($NewDonations)) as $Address => $UserID) {
				Donations::regular_donate($UserID, $NewDonations[$Address], 'Bitcoin Parser', '', 'BTC');
				self::store_donation($Address, $NewDonations[$Address]);
			}
			G::$Cache->cache_value('btc_total_received', $NewAmount, 0);
		}
	}

	/**
	 * Record a donation in the database
	 *
	 * @param string $Address bitcoin address
	 * @param double $Amount amount of bitcoins transferred
	 */
	public static function store_donation($Address, $Amount) {
		if (!is_numeric($Amount) || $Amount <= 0) {
			// Panic!
			return false;
		}
		G::$DB->query("
			INSERT INTO donations_bitcoin
				(BitcoinAddress, Amount)
			VALUES
				('$Address', $Amount)");
	}
}