<?
//no authorization because this page needs to be accessed to get the authkey

//authorize(true);

//calculate ratio --Gwindow
//returns 0 for DNE and -1 for infiinity, because we dont want strings being returned for a numeric value in our java
$Ratio = 0;
if($LoggedUser['BytesUploaded'] == 0 && $LoggedUser['BytesDownloaded'] == 0) {
	$Ratio = 0;
} elseif($LoggedUser['BytesDownloaded'] == 0) {
	$Ratio = -1;
} else {
	$Ratio = number_format(max($LoggedUser['BytesUploaded']/$LoggedUser['BytesDownloaded']-0.005,0), 2); //Subtract .005 to floor to 2 decimals
}

print json_encode(
	array(
		'status' => 'success',
		'response' => array(
			'username' => $LoggedUser['Username'],
			'id' => $LoggedUser['ID'],
			'authkey'=> $LoggedUser['AuthKey'],
			'passkey'=> $LoggedUser['torrent_pass'],
			'userstats' => array(
				'uploaded' => (int) $LoggedUser['BytesUploaded'],
				'downloaded' => (int) $LoggedUser['BytesDownloaded'],
				'ratio' => (float) $Ratio,
				'requiredratio' => (float) $LoggedUser['RequiredRatio'],
				//'class' => $Class
				'class' => $ClassLevels[$LoggedUser['Class']]['Name']
			),
		)
	)
);

?>
