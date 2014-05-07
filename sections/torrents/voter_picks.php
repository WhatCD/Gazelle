<?
//global $Cache, $DB;
include(SERVER_ROOT.'/sections/torrents/ranking_funcs.php');

$Top10 = $Cache->get_value('similar_albums_'.$GroupID);
if ($Top10 === false || isset($Top10[$GroupID])) {

	$VotePairs = $Cache->get_value('vote_pairs_'.$GroupID, true);
	if ($VotePairs === false || isset($VotePairs[$GroupID])) {
		$DB->query("
			SELECT v.GroupID, SUM(IF(v.Type='Up',1,0)) AS Ups, COUNT(1) AS Total
			FROM (	SELECT UserID
					FROM users_votes
					WHERE GroupID = '$GroupID'
						AND Type='Up'
				) AS a
				JOIN users_votes AS v USING (UserID)
			WHERE v.GroupID != '$GroupID'
			GROUP BY v.GroupID
			HAVING Ups > 0");
		$VotePairs = $DB->to_array('GroupID', MYSQL_ASSOC, false);
		$Cache->cache_value('vote_pairs_'.$GroupID, $VotePairs, 21600);
	}

	$GroupScores = array();
	foreach ($VotePairs as $RatingGroup) {
		// Cutting out the junk should speed the sort significantly
		$Score = binomial_score($RatingGroup['Ups'], $RatingGroup['Total']);
		if ($Score > 0.3) {
			$GroupScores[$RatingGroup['GroupID']] = $Score;
		}
	}

	arsort($GroupScores);
	$Top10 = array_slice($GroupScores, 0, 10, true);
	$Cache->cache_value('similar_albums_'.$GroupID, $Top10, 0.5 * 3600);
}
if (count($Top10) > 0) {
?>
		<table class="vote_matches_table" id="vote_matches">
			<tr class="colhead">
				<td><a href="#">&uarr;</a>&nbsp;People who like this album also liked... <a href="#" onclick="$('.votes_rows').gtoggle(); return false;">(Show)</a></td>
			</tr>
<?
	$Top10Groups = array_keys($Top10);

	$Groups = Torrents::get_groups($Top10Groups, true, true, false);
	$i = 0;
	foreach ($Top10Groups as $MatchGroupID) {
		if (!isset($Groups[$MatchGroupID])) {
			continue;
		}
		$MatchGroup = $Groups[$MatchGroupID];
		$i++;
		$Str = Artists::display_artists($MatchGroup['ExtendedArtists']).'<a href="torrents.php?id='.$MatchGroupID.'">'.$MatchGroup['Name'].'</a>';
?>
			<tr class="votes_rows hidden <?=($i & 1) ? 'rowb' : 'rowa'?>">
				<td><span class="like_ranks"><?=$i?>.</span> <?=$Str?></td>
			</tr>
<?	} ?>
		</table>
<?
}
?>
