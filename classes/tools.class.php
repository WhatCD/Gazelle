<?
class Tools {
	/**
	 * Returns true if given IP is banned.
	 *
	 * @param string $IP
	 */
	public static function site_ban_ip($IP) {
		global $Debug;
		$A = substr($IP, 0, strcspn($IP, '.'));
		$IPNum = Tools::ip_to_unsigned($IP);
		$IPBans = G::$Cache->get_value('ip_bans_'.$A);
		if (!is_array($IPBans)) {
			$SQL = sprintf("
				SELECT ID, FromIP, ToIP
				FROM ip_bans
				WHERE FromIP BETWEEN %d << 24 AND (%d << 24) - 1", $A, $A + 1);
			$QueryID = G::$DB->get_query_id();
			G::$DB->query($SQL);
			$IPBans = G::$DB->to_array(0, MYSQLI_NUM);
			G::$DB->set_query_id($QueryID);
			G::$Cache->cache_value('ip_bans_'.$A, $IPBans, 0);
		}
		foreach ($IPBans as $Index => $IPBan) {
			list ($ID, $FromIP, $ToIP) = $IPBan;
			if ($IPNum >= $FromIP && $IPNum <= $ToIP) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the unsigned form of an IP address.
	 *
	 * @param string $IP The IP address x.x.x.x
	 * @return string the long it represents.
	 */
	public static function ip_to_unsigned($IP) {
		return sprintf('%u', ip2long($IP));
	}

	/**
	 * Geolocate an IP address using the database
	 *
	 * @param $IP the ip to fetch the country for
	 * @return the country of origin
	 */
	public static function geoip($IP) {
		static $IPs = array();
		if (isset($IPs[$IP])) {
			return $IPs[$IP];
		}
		if (is_number($IP)) {
			$Long = $IP;
		} else {
			$Long = Tools::ip_to_unsigned($IP);
		}
		if (!$Long || $Long == 2130706433) { // No need to check cc for 127.0.0.1
			return false;
		}
		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT EndIP, Code
			FROM geoip_country
			WHERE $Long >= StartIP
			ORDER BY StartIP DESC
			LIMIT 1");
		if ((!list($EndIP, $Country) = G::$DB->next_record()) || $EndIP < $Long) {
			$Country = '?';
		}
		G::$DB->set_query_id($QueryID);
		$IPs[$IP] = $Country;
		return $Country;
	}

	/**
	 * Gets the hostname for an IP address
	 *
	 * @param $IP the IP to get the hostname for
	 * @return hostname fetched
	 */
	public static function get_host_by_ip($IP) {
		$testar = explode('.', $IP);
		if (count($testar) != 4) {
			return $IP;
		}
		for ($i = 0; $i < 4; ++$i) {
			if (!is_numeric($testar[$i])) {
				return $IP;
			}
		}

		$host = `host -W 1 $IP`;
		return ($host ? end(explode(' ', $host)) : $IP);
	}

	/**
	 * Gets an hostname using AJAX
	 *
	 * @param $IP the IP to fetch
	 * @return a span with JavaScript code
	 */
	public static function get_host_by_ajax($IP) {
		static $IPs = array();
		$Class = strtr($IP, '.', '-');
		$HTML = '<span class="host_'.$Class.'">Resolving host...';
		if (!isset($IPs[$IP])) {
			$HTML .= '<script type="text/javascript">' .
					'$(document).ready(function() {' .
						'$.get(\'tools.php?action=get_host&ip='.$IP.'\', function(host) {' .
							'$(\'.host_'.$Class.'\').html(host);' .
						'});' .
					'});' .
				'</script>';
		}
		$HTML .= '</span>';
		$IPs[$IP] = 1;
		return $HTML;
	}


	/**
	 * Looks up the full host of an IP address, by system call.
	 * Used as the server-side counterpart to get_host_by_ajax.
	 *
	 * @param string $IP The IP address to look up.
	 * @return string the host.
	 */
	public static function lookup_ip($IP) {
		//TODO: use the G::$Cache
		$Output = explode(' ',shell_exec('host -W 1 '.escapeshellarg($IP)));
		if (count($Output) == 1 && empty($Output[0])) {
			//No output at all implies the command failed
			return '';
		}

		if (count($Output) != 5) {
			return false;
		} else {
			return trim($Output[4]);
		}
	}

	/**
	 * Format an IP address with links to IP history.
	 *
	 * @param string IP
	 * @return string The HTML
	 */
	public static function display_ip($IP) {
		$Line = display_str($IP).' ('.Tools::get_country_code_by_ajax($IP).') ';
		$Line .= '<a href="user.php?action=search&amp;ip_history=on&amp;ip='.display_str($IP).'&amp;matchtype=strict" title="Search" class="brackets tooltip">S</a>';

		return $Line;
	}

	public static function get_country_code_by_ajax($IP) {
		static $IPs = array();
		$Class = strtr($IP, '.', '-');
		$HTML = '<span class="cc_'.$Class.'">Resolving CC...';
		if (!isset($IPs[$IP])) {
			$HTML .= '<script type="text/javascript">' .
					'$(document).ready(function() {' .
						'$.get(\'tools.php?action=get_cc&ip='.$IP.'\', function(cc) {' .
							'$(\'.cc_'.$Class.'\').html(cc);' .
						'});' .
					'});' .
				'</script>';
		}
		$HTML .= '</span>';
		$IPs[$IP] = 1;
		return $HTML;
	}

	/**
	 * Disable an array of users.
	 *
	 * @param array $UserIDs (You can also send it one ID as an int, because fuck types)
	 * @param BanReason 0 - Unknown, 1 - Manual, 2 - Ratio, 3 - Inactive, 4 - Unused.
	 */
	public static function disable_users($UserIDs, $AdminComment, $BanReason = 1) {
		$QueryID = G::$DB->get_query_id();
		if (!is_array($UserIDs)) {
			$UserIDs = array($UserIDs);
		}
		G::$DB->query("
			UPDATE users_info AS i
				JOIN users_main AS m ON m.ID = i.UserID
			SET m.Enabled = '2',
				m.can_leech = '0',
				i.AdminComment = CONCAT('".sqltime()." - ".($AdminComment ? $AdminComment : 'Disabled by system')."\n\n', i.AdminComment),
				i.BanDate = '".sqltime()."',
				i.BanReason = '$BanReason',
				i.RatioWatchDownload = ".($BanReason == 2 ? 'm.Downloaded' : "'0'")."
			WHERE m.ID IN(".implode(',', $UserIDs).') ');
		G::$Cache->decrement('stats_user_count', G::$DB->affected_rows());
		foreach ($UserIDs as $UserID) {
			G::$Cache->delete_value("enabled_$UserID");
			G::$Cache->delete_value("user_info_$UserID");
			G::$Cache->delete_value("user_info_heavy_$UserID");
			G::$Cache->delete_value("user_stats_$UserID");

			G::$DB->query("
				SELECT SessionID
				FROM users_sessions
				WHERE UserID = '$UserID'
					AND Active = 1");
			while (list($SessionID) = G::$DB->next_record()) {
				G::$Cache->delete_value("session_$UserID"."_$SessionID");
			}
			G::$Cache->delete_value("users_sessions_$UserID");

			G::$DB->query("
				DELETE FROM users_sessions
				WHERE UserID = '$UserID'");

		}

		// Remove the users from the tracker.
		G::$DB->query('
			SELECT torrent_pass
			FROM users_main
			WHERE ID in ('.implode(', ', $UserIDs).')');
		$PassKeys = G::$DB->collect('torrent_pass');
		$Concat = '';
		foreach ($PassKeys as $PassKey) {
			if (strlen($Concat) > 3950) { // Ocelot's read buffer is 4 KiB and anything exceeding it is truncated
				Tracker::update_tracker('remove_users', array('passkeys' => $Concat));
				$Concat = $PassKey;
			} else {
				$Concat .= $PassKey;
			}
		}
		Tracker::update_tracker('remove_users', array('passkeys' => $Concat));
		G::$DB->set_query_id($QueryID);
	}

	/**
	 * Warn a user.
	 *
	 * @param int $UserID
	 * @param int $Duration length of warning in seconds
	 * @param string $reason
	 */
	public static function warn_user($UserID, $Duration, $Reason) {
		global $Time;

		$QueryID = G::$DB->get_query_id();
		G::$DB->query("
			SELECT Warned
			FROM users_info
			WHERE UserID = $UserID
				AND Warned != '0000-00-00 00:00:00'");
		if (G::$DB->has_results()) {
			//User was already warned, appending new warning to old.
			list($OldDate) = G::$DB->next_record();
			$NewExpDate = date('Y-m-d H:i:s', strtotime($OldDate) + $Duration);

			Misc::send_pm($UserID, 0,
				'You have received multiple warnings.',
				"When you received your latest warning (set to expire on ".date('Y-m-d', (time() + $Duration)).'), you already had a different warning (set to expire on '.date('Y-m-d', strtotime($OldDate)).").\n\n Due to this collision, your warning status will now expire at $NewExpDate.");

			$AdminComment = date('Y-m-d')." - Warning (Clash) extended to expire at $NewExpDate by " . G::$LoggedUser['Username'] . "\nReason: $Reason\n\n";

			G::$DB->query('
				UPDATE users_info
				SET
					Warned = \''.db_string($NewExpDate).'\',
					WarnedTimes = WarnedTimes + 1,
					AdminComment = CONCAT(\''.db_string($AdminComment).'\', AdminComment)
				WHERE UserID = \''.db_string($UserID).'\'');
		} else {
			//Not changing, user was not already warned
			$WarnTime = time_plus($Duration);

			G::$Cache->begin_transaction("user_info_$UserID");
			G::$Cache->update_row(false, array('Warned' => $WarnTime));
			G::$Cache->commit_transaction(0);

			$AdminComment = date('Y-m-d')." - Warned until $WarnTime by " . G::$LoggedUser['Username'] . "\nReason: $Reason\n\n";

			G::$DB->query('
				UPDATE users_info
				SET
					Warned = \''.db_string($WarnTime).'\',
					WarnedTimes = WarnedTimes + 1,
					AdminComment = CONCAT(\''.db_string($AdminComment).'\', AdminComment)
				WHERE UserID = \''.db_string($UserID).'\'');
		}
		G::$DB->set_query_id($QueryID);
	}

	/**
	 * Update the notes of a user
	 * @param unknown $UserID ID of user
	 * @param unknown $AdminComment Comment to update with
	 */
	public static function update_user_notes($UserID, $AdminComment) {
		$QueryID = G::$DB->get_query_id();
		G::$DB->query('
			UPDATE users_info
			SET AdminComment = CONCAT(\''.db_string($AdminComment).'\', AdminComment)
			WHERE UserID = \''.db_string($UserID).'\'');
		G::$DB->set_query_id($QueryID);
	}

	/**
	* Check if an IP address is part of a given CIDR range.
	* @param string $CheckIP the IP address to be looked up
	* @param string $Subnet the CIDR subnet to be checked against
	*/
	public static function check_cidr_range($CheckIP, $Subnet) {
		$IP = ip2long($CheckIP);
		$CIDR = split('/', $Subnet);
		$SubnetIP = ip2long($CIDR[0]);
		$SubnetMaskBits = 32 - $CIDR[1];

		return (($IP>>$SubnetMaskBits) == ($SubnetIP>>$SubnetMaskBits));
	}

}
?>
