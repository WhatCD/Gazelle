<?php


if (!isset($_GET['apikey']) || empty($_GET['apikey'])) {
	echo '{"error": { "message": "No API Key specified" }}';
	die();
}

$ApiKey = $_GET['apikey'];


curl_setopt_array($Ch = curl_init(), array(
	CURLOPT_URL => 'https://api.pushbullet.com/api/devices',
	CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_USERPWD => $ApiKey . ':'
));

$Result = curl_exec($Ch);
curl_close($Ch);
echo json_encode($Result);
