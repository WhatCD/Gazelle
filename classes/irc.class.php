<?
class IRC_DB extends DB_MYSQL {
	function halt($Msg) {
		global $Bot;
		$Bot->send_to($Bot->get_channel(), 'The database is currently unavailable; try again later.');
	}
}

abstract class IRC_BOT {
	abstract protected function connect_events();
	abstract protected function channel_events();
	abstract protected function query_events();
	abstract protected function irc_events();
	abstract protected function listener_events();

	protected $Debug = false;
	protected $Socket = false;
	protected $Data = false;
	protected $Whois = false;
	protected $Identified = array();
	protected $Channels = array();
	protected $Messages = array();
	protected $LastChan = false;
	protected $ListenSocket = false;
	protected $Listened = false;
	protected $Connecting = false;
	protected $State = 1; // Drone is live
	public $Restart = 0; // Die by default

	public function __construct() {
		if (isset($_SERVER['HOME']) && is_dir($_SERVER['HOME']) && getcwd() != $_SERVER['HOME']) {
			chdir($_SERVER['HOME']);
		}
		ob_end_clean();
		restore_error_handler(); //Avoid PHP error logging
		set_time_limit(0);
	}

	public function connect() {
		$this->connect_irc();
		$this->connect_listener();
		$this->post_connect();
	}

	private function connect_irc($Reconnect = false) {
		$this->Connecting = true;
		//Open a socket to the IRC server
		if (defined('BOT_PORT_SSL')) {
			$IrcAddress = 'tls://' . BOT_SERVER . ':' . BOT_PORT_SSL;
		} else {
			$IrcAddress = 'tcp://' . BOT_SERVER . ':' . BOT_PORT;
		}
		while (!$this->Socket = stream_socket_client($IrcAddress, $ErrNr, $ErrStr)) {
			sleep(15);
		}
		stream_set_blocking($this->Socket, 0);
		$this->Connecting = false;
		if ($Reconnect) {
			$this->post_connect();
		}
	}

	private function connect_listener() {
		//create a socket to listen on
		$ListenAddress = 'tcp://' . SOCKET_LISTEN_ADDRESS . ':' . SOCKET_LISTEN_PORT;
		if (!$this->ListenSocket = stream_socket_server($ListenAddress, $ErrNr, $ErrStr)) {
			die("Cannot create listen socket: $ErrStr");
		}
		stream_set_blocking($this->ListenSocket, false);
	}

	private function post_connect() {
		fwrite($this->Socket, "NICK ".BOT_NICK."Init\n");
		fwrite($this->Socket, "USER ".BOT_NICK." * * :IRC Bot\n");
		$this->listen();
	}

	public function disconnect() {
		fclose($this->ListenSocket);
		$this->State = 0; //Drones dead
	}

	public function get_channel() {
		preg_match('/.+ PRIVMSG ([^:]+) :.+/', $this->Data, $Channel);
		if (preg_match('/#.+/', $Channel[1])) {
			return $Channel[1];
		} else {
			return false;
		}
	}

	public function get_nick() {
		preg_match('/:([^!:]+)!.+@[^\s]+ PRIVMSG [^:]+ :.+/', $this->Data, $Nick);
		return $Nick[1];
	}

	protected function get_message() {
		preg_match('/:.+ PRIVMSG [^:]+ :(.+)/', $this->Data, $Msg);
		return trim($Msg[1]);
	}

	protected function get_irc_host() {
		preg_match('/:[^!:]+!.+@([^\s]+) PRIVMSG [^:]+ :.+/', $this->Data, $Host);
		return trim($Host[1]);
	}

	protected function get_word($Select = 1) {
		preg_match('/:.+ PRIVMSG [^:]+ :(.+)/', $this->Data, $Word);
		$Word = split(' ', $Word[1]);
		return trim($Word[$Select]);
	}

	protected function get_action() {
		preg_match('/:.+ PRIVMSG [^:]+ :!(\S+)/', $this->Data, $Action);
		return strtoupper($Action[1]);
	}

	protected function send_raw($Text) {
		if (!feof($this->Socket)) {
			fwrite($this->Socket, "$Text\n");
		} elseif (!$this->Connecting) {
			$this->Connecting = true;
			sleep(120);
			$this->connect_irc(true);
		}
	}

	public function send_to($Channel, $Text) {
		// split the message up into <= 460 character strings and send each individually
		// this is used to prevent messages from getting truncated
		$Text = wordwrap($Text, 460, "\n", true);
		$TextArray = explode("\n", $Text);
		foreach ($TextArray as $Text) {
			$this->send_raw("PRIVMSG $Channel :$Text");
		}
	}

	protected function whois($Nick) {
		$this->Whois = $Nick;
		$this->send_raw("WHOIS $Nick");
	}

	/*
	This function uses blacklisted_ip, which is no longer in RC2.
	You can probably find it in old RC1 code kicking aronud if you need it.
	protected function ip_check($IP, $Gline = false, $Channel = BOT_REPORT_CHAN) {
		if (blacklisted_ip($IP)) {
			$this->send_to($Channel, 'TOR IP Detected: '.$IP);
			if ($Gline) {
				$this->send_raw('GLINE *@'.$IP.' 90d :DNSBL Proxy');
			}
		}
		if (Tools::site_ban_ip($IP)) {
			$this->send_to($Channel, 'Site IP Ban Detected: '.$IP);
			if ($Gline) {
				$this->send_raw('GLINE *@'.$IP.' 90d :IP Ban');
			}
		}
	}*/

	protected function listen() {
		G::$Cache->InternalCache = false;
		stream_set_timeout($this->Socket, 10000000000);
		while ($this->State == 1) {
			$NullSock = null;
			$Sockets = array($this->Socket, $this->ListenSocket);
			if (stream_select($Sockets, $NullSock, $NullSock, null) === false) {
				die();
			}
			foreach ($Sockets as $Socket) {
				if ($Socket === $this->Socket) {
					$this->irc_events();
				} else {
					$this->Listened = stream_socket_accept($Socket);
					$this->listener_events();
				}
			}
			G::$DB->LinkID = false;
			G::$DB->Queries = array();
		}
	}
}
?>
