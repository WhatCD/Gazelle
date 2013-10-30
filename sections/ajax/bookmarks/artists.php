<?

if (!empty($_GET['userid'])) {
	if (!check_perms('users_override_paranoia')) {
		print
			json_encode(
				array(
					'status' => 'failure'
				)
			);
		die();
	}
	$UserID = $_GET['userid'];
	$Sneaky = ($UserID != $LoggedUser['ID']);
	if (!is_number($UserID)) {
		print
			json_encode(
				array(
					'status' => 'failure'
				)
			);
		die();
	}
	$DB->query("
		SELECT Username
		FROM users_main
		WHERE ID = '$UserID'");
	list($Username) = $DB->next_record();
} else {
	$UserID = $LoggedUser['ID'];
}

$Sneaky = ($UserID != $LoggedUser['ID']);

//$ArtistList = Bookmarks::all_bookmarks('artist', $UserID);

$DB->query("
	SELECT ag.ArtistID, ag.Name
	FROM bookmarks_artists AS ba
		INNER JOIN artists_group AS ag ON ba.ArtistID = ag.ArtistID
	WHERE ba.UserID = $UserID");

$ArtistList = $DB->to_array();

$JsonArtists = array();
foreach ($ArtistList as $Artist) {
	list($ArtistID, $Name) = $Artist;
	$JsonArtists[] = array(
		'artistId' => (int)$ArtistID,
		'artistName' => $Name
	);
}

print
	json_encode(
		array(
			'status' => 'success',
			'response' => array(
				'artists' => $JsonArtists
			)
		)
	);

?>
