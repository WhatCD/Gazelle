<?
$UserID = $LoggedUser['ID'];
$SimilarID = db_string($_GET['similarid']);
$ArtistID = db_string($_GET['artistid']);
$Way = db_string($_GET['way']);

if (!is_number($SimilarID) || !is_number($ArtistID)) {
	error(404);
}
if (!in_array($Way, array('up', 'down'))) {
	error(404);
}

$DB->query("
	SELECT SimilarID
	FROM artists_similar_votes
	WHERE SimilarID='$SimilarID'
		AND UserID='$UserID'
		AND Way='$Way'");
if (!$DB->has_results()) {
	if ($Way == 'down') {
		$Score = 'Score-100';
	} elseif ($Way == 'up') {
		$Score = 'Score+100';
	} else { // Nothing is impossible!
		$Score = 'Score';
	}
	$DB->query("
		UPDATE artists_similar_scores
		SET Score=$Score
		WHERE SimilarID='$SimilarID'");
	$DB->query("
		INSERT INTO artists_similar_votes (SimilarID, UserID, Way)
		VALUES ('$SimilarID', '$UserID', '$Way')");
	$Cache->delete_value('artist_'.$ArtistID); // Delete artist cache
}
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
