<?
authorize();

if (empty($_POST['collageid']) || !is_number($_POST['collageid']) || $_POST['body'] === '' || !isset($_POST['body'])) {
	error(0);
}
$CollageID = $_POST['collageid'];

if ($LoggedUser['DisablePosting']) {
	error('Your posting privileges have been removed'); // Should this be logged?
}

$DB->query("
	SELECT
		CEIL(
			(
			SELECT COUNT(ID) + 1
			FROM collages_comments
			WHERE CollageID = '".db_string($CollageID)."'
			) / ".TORRENT_COMMENTS_PER_PAGE."
		) AS Pages");
list($Pages) = $DB->next_record();

$DB->query("
	INSERT INTO collages_comments
		(CollageID, Body, UserID, Time)
	VALUES
		('$CollageID', '".db_string($_POST['body'])."', '$LoggedUser[ID]', '".sqltime()."')");

$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $Pages - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);

$Cache->delete_value("collage_$CollageID");
$Cache->delete_value("collage_comments_{$CollageID}_catalogue_$CatalogueID");
$Cache->increment("collage_comments_$CollageID");
header('Location: collages.php?id='.$CollageID);

?>
