<? // This is a very primitive IRC bot
if (!isset($argv)) {
	die('CLI Only.');
}

define('SERVER', BOT_SERVER);
define('PORT', 6667);
define('NICK', 'RawBot');
define('WATCH', '#raw-input');
define('RELAY', '#raw-output');

$Socket = fsockopen(SERVER, PORT);
fwrite($Socket, "USER ".NICK." * * :".NICK."\n");
fwrite($Socket, "NICK ".NICK."\n");

sleep(10);

fwrite($Socket, "JOIN ".WATCH."\n");
fwrite($Socket, "JOIN ".RELAY."\n");

while (!feof($Socket)) {
	$Line = fgets ($Socket, 1024);
	if (preg_match('/Nickname is already in use\.$/', $Line)) {
		fwrite($Socket, "NICK ".NICK."_\n");
	}
	if (preg_match('/PING :(.+)$/', $Line, $Ping)) {
		fwrite($Socket, "PONG :$Ping[1]\n");
	}

	// Example command
	if (stripos('!mode', $Line) !== false) {
		fwrite($Socket, "PRIVMSG ".RELAY." :Mode command used\n");
		fwrite($Socket, "MODE WhatMan\n");
		fwrite($Socket, "WHOIS WhatMan\n");
		fwrite($Socket, "MODE Orbulon\n");
	}

	fwrite($Socket, 'PRIVMSG '.RELAY." : -----$Line\n");
}
