<?
//no authorization because this page needs to be accessed to get the authkey

//authorize(true);

global $DB, $Cache;
$HeavyInfo = $Cache->get_value('user_info_heavy_'.$UserID);

$DB->query("SELECT
	m.Username,
	m.torrent_pass,
	i.AuthKey,
	Uploaded AS BytesUploaded,
	Downloaded AS BytesDownloaded,
	RequiredRatio,
	p.Level AS Class
	FROM users_main AS m
	INNER JOIN users_info AS i ON i.UserID=m.ID
	LEFT JOIN permissions AS p ON p.ID=m.PermissionID
	WHERE m.ID='$UserID'");

list($Username,$torrent_pass,$AuthKey,$Uploaded,$Downloaded,$RequiredRatio,$Class) = $DB->next_record(MYSQLI_NUM, array(9,11));

//calculate ratio --Gwindow
//returns 0 for DNE and -1 for infiinity, because we dont want strings being returned for a numeric value in our java
if($Uploaded == 0 && $Downloaded == 0) {
	$Ratio = '0';
} elseif($Downloaded == 0) {
	$Ratio = '-1';
} else {
	$Ratio = number_format(max($Uploaded/$Downloaded-0.005,0), 2); //Subtract .005 to floor to 2 decimals
}



print json_encode(
	array(
		'status' => 'success',
		'response' => array(
			'username' => $Username,
			'id' => $UserID,
			'authkey'=>$AuthKey,
			'passkey'=>$torrent_pass,
			'userstats' => array(
				'uploaded' => $Uploaded,
				'downloaded' => $Downloaded,
				'ratio' => $Ratio,
				'requiredratio' => $RequiredRatio,
				//'class' => $Class
				'class' => $ClassLevels[$Class]['Name']
			),
		)
	)
);

?>
