<?php
define("PUSH_SOCKET_LISTEN_ADDRESS", "127.0.0.1");
define("PUSH_SOCKET_LISTEN_PORT", 6789);

require 'NMA_API.php';
require 'config.php';
class PushServer {
	private $ListenSocket = false;
	private $State = 1;
	private $Listened = false;

	public function __construct() {
		// restore_error_handler(); //Avoid PHP error logging
		set_time_limit(0);
		$this->init();
		$this->listen();
	}

	private function init() {
		$this->ListenSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($this->ListenSocket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->ListenSocket, PUSH_SOCKET_LISTEN_ADDRESS, PUSH_SOCKET_LISTEN_PORT);
		socket_listen($this->ListenSocket);
		socket_set_nonblock($this->ListenSocket);
		echo "\nInitialized\n";
	}

	private function listen() {
		echo "\nListening...\n";
		while ( ($this->State) == 1 ) {
			if ($this->Listened = @socket_accept($this->ListenSocket)) {
				$Data = socket_read($this->Listened, 512);
				$this->parse_data($Data);
			}
			usleep(5000);
		}
	}

	private function parse_data($Data) {
		$JSON = json_decode($Data, true);
		$Service = strtolower($JSON['service']);
		switch ($Service) {
			case 'nma':
				$this->push_nma($JSON['user']['key'], $JSON['message']['title'], $JSON['message']['body'], $JSON['message']['url']);
				break;
			case 'prowl':
				$this->push_prowl($JSON['user']['key'], $JSON['message']['title'], $JSON['message']['body'], $JSON['message']['url']);
				break;
			case 'toasty':
				$this->push_toasty($JSON['user']['key'], $JSON['message']['title'], $JSON['message']['body'], $JSON['message']['url']);
				break;
			case 'pushover':
				$this->push_pushover($JSON['user']['key'], $JSON['message']['title'], $JSON['message']['body'], $JSON['message']['url']);
				break;
			default:
				break;
		}
	}

	private function push_prowl($Key, $Title, $Message, $URL) {
		$API = "https://api.prowlapp.com/publicapi/add";
		$Fields = array(
				'apikey' => urlencode($Key),
				'application' => urlencode(SITE_NAME),
				'event' => urlencode($Title),
				'description' => urlencode($Message)
		);
		if (!empty($URL)) {
			$Fields['url'] = $URL;
		}
		$FieldsString = "";
		foreach ($Fields as $key => $value) {
			$FieldsString .= $key . '=' . $value . '&';
		}
		rtrim($FieldsString, '&');

		$Curl = curl_init();
		curl_setopt($Curl, CURLOPT_URL, $API);
		curl_setopt($Curl, CURLOPT_POST, count($Fields));
		curl_setopt($Curl, CURLOPT_POSTFIELDS, $FieldsString);
		curl_exec($Curl);
		curl_close($Curl);
		echo "Push sent to Prowl";
	}

	private function push_toasty($Key, $Title, $Message, $URL) {
		$API = "http://api.supertoasty.com/notify/" . urlencode($Key) . "?";
		if (!empty($URL)) {
			$Message = $Message . " " . $URL;
		}
		$Fields = array(
				'title' => urlencode($Title),
				'text' => urlencode($Message),
				'sender' => urlencode(SITE_NAME)
		);
		$FieldsString = "";
		foreach ($Fields as $key => $value) {
			$FieldsString .= $key . '=' . $value . '&';
		}
		rtrim($FieldsString, '&');

		$Curl = curl_init();
		curl_setopt($Curl, CURLOPT_URL, $API);
		curl_setopt($Curl, CURLOPT_POST, count($Fields));
		curl_setopt($Curl, CURLOPT_POSTFIELDS, $FieldsString);
		curl_exec($Curl);
		curl_close($Curl);
		echo "Push sent to Toasty";
	}

	private function push_nma($Key, $Title, $Message, $URL) {
		$NMA = new NMA_API(array(
				'apikey' => $Key
		));
		if ($NMA->verify()) {
			if ($NMA->notify(SITE_NAME, $Title, $Message, $URL)) {
				echo "Push sent to NMA";
			}
		}
	}

	private function push_pushover($UserKey, $Title, $Message, $URL) {
		curl_setopt_array($ch = curl_init(), array(
				CURLOPT_URL => "https://api.pushover.net/1/messages.json",
				CURLOPT_POSTFIELDS => array(
						"token" => PUSHOVER_KEY,
						"user" => $UserKey,
						"title" => $Title,
						"message" => $Message,
						"url" => $URL
				)
		));
		curl_exec($ch);
		curl_close($ch);
		echo "Push sent to Pushover";
	}
}

$PushServer = new PushServer();
?>
