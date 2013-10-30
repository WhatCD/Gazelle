<?php

if (empty($_GET['id']) || !is_number($_GET['id']) || empty($_GET['limit']) || !is_number($_GET['limit'])) {
	print
		json_encode(
			array(
				'status' => 'failure'
			)
		);
	die();
}

$artist_id = $_GET["id"];
$artist_limit = $_GET["limit"];

$DB->query("
		SELECT
			s2.ArtistID,
			ag.Name,
			ass.Score
		FROM artists_similar AS s1
			JOIN artists_similar AS s2 ON s1.SimilarID = s2.SimilarID AND s1.ArtistID != s2.ArtistID
			JOIN artists_similar_scores AS ass ON ass.SimilarID = s1.SimilarID
			JOIN artists_group AS ag ON ag.ArtistID = s2.ArtistID
		WHERE s1.ArtistID = $artist_id
		ORDER BY ass.Score DESC
		LIMIT $artist_limit");


		while (list($ArtistID, $Name, $Score) = $DB->next_record(MYSQLI_NUM, false)) {
			if ($Score < 0) {
				continue;
			}
			$results[] = array(
					'id' => (int)$ArtistID,
					'name' => $Name,
					'score' => (int)$Score);
		}

print json_encode($results);
exit();
?>
