<?
// TODO: Turn this into a class with nice functions like update_user, delete_torrent, etc.
class Tracker {
	/**
	 * Send a GET request over a socket directly to the tracker
	 * For example, Tracker::update_tracker('change_passkey', array('oldpasskey' => OLD_PASSKEY, 'newpasskey' => NEW_PASSKEY)) will send the request:
	 * GET /tracker_32_char_secret_code/update?action=change_passkey&oldpasskey=OLD_PASSKEY&newpasskey=NEW_PASSKEY HTTP/1.1
	 *
	 * @param string $Action The action to send
	 * @param array $Updates An associative array of key->value pairs to send to the tracker
	 * @param boolean $ToIRC Sends a message to the channel #tracker with the GET URL.
	 */
	public static function update_tracker($Action, $Updates, $ToIRC = false) {
		//Build request
		$Get = '/update?action='.$Action;
		foreach ($Updates as $Key => $Value) {
			$Get .= '&'.$Key.'='.$Value;
		}

		if ($ToIRC != false) {
			send_irc('PRIVMSG #tracker :'.$Get);
		}
		$Path = TRACKER_SECRET.$Get;

		$Return = '';
		$Attempts = 0;
		while ($Return != "success" && $Attempts < 3) {

			// Send update
			$File = fsockopen(TRACKER_HOST, TRACKER_PORT, $ErrorNum, $ErrorString);
			if ($File) {
				$Header = 'GET /'.$Path.' HTTP/1.1\r\n';
				if (fwrite($File, $Header) === false) {
					$Attempts++;
					$Err = "Failed to fwrite()";
					sleep(3);
					continue;
				}
			} else {
				$Attempts++;
				$Err = "Failed to fsockopen() - ".$ErrorNum." - ".$ErrorString;
				sleep(6);
				continue;
			}

			// Check for response.

			$ResHeader = '';
			do {
				$ResHeader .= fread($File, 1);
			} while (!feof($File) && !Misc::ends_with($ResHeader, "\r\n\r\n"));

			$Response = '';
			while ($Line = fgets($File)) {
				$Response .= $Line;
			}

			$Return = chop($Response);
			$Attempts++;
		}

		if ($Return != "success") {
			send_irc("PRIVMSG #tracker :{$Attempts} {$Err} {$Get}");
			if (G::$Cache->get_value('ocelot_error_reported') === false) {
				send_irc("PRIVMSG ".ADMIN_CHAN." :Failed to update ocelot: ".$Err." : ".$Get);
				G::$Cache->cache_value('ocelot_error_reported', true, 3600);
			}
		}
		return ($Return == "success");
	}
}
?>
