<?php
authorize();
$SimilarID = db_string($_GET['similarid']);

if (!is_number($SimilarID) || !$SimilarID) {
	error(404);
}
if (!check_perms('site_delete_tag')) {
	error(403);
}
$DB->query("
	SELECT ArtistID
	FROM artists_similar
	WHERE SimilarID = '$SimilarID'");
$ArtistIDs = $DB->to_array();
$DB->query("
	DELETE FROM artists_similar
	WHERE SimilarID = '$SimilarID'");
$DB->query("
	DELETE FROM artists_similar_scores
	WHERE SimilarID = '$SimilarID'");
$DB->query("
	DELETE FROM artists_similar_votes
	WHERE SimilarID = '$SimilarID'");

foreach ($ArtistIDs as $ArtistID) {
	list($ArtistID) = $ArtistID;
	$Cache->delete_value("artist_$ArtistID"); // Delete artist cache
	$Cache->delete_value("similar_positions_$ArtistID");
}
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
