<?
// Show the "This album is number x overall", etc. box for the "Music" category only
if ($GroupCategoryID == 1) {
	$Rankings = Votes::get_ranking($GroupID, $GroupYear);
	$LIs = '';
	// Display information for the return categories of get_ranking()
	$GroupDecade = $GroupYear - ($GroupYear % 10);
	$names = array('overall'=>'<a href="top10.php?type=votes">overall</a>',
				'decade'=>check_perms('site_advanced_top10') ? 'for the <a href="top10.php?advanced=1&amp;type=votes&amp;year1='.$GroupDecade.'&amp;year2='.($GroupDecade+9).'">'.$GroupDecade.'s</a>' : 'for the '.$GroupDecade.'s',
				'year'=>check_perms('site_advanced_top10') ? 'for <a href="top10.php?advanced=1&amp;type=votes&amp;year1='.$GroupYear.'&amp;year2=">'.$GroupYear.'</a>' : "for $GroupYear");

	foreach ($names as $key => $text) {
		if ($Rank = $Rankings[$key]) {
			if ($Rank <= 10) {
				$Class = 'vr_top_10';
			} elseif ($Rank <= 25) {
				$Class = 'vr_top_25';
			} elseif ($Rank <= 50) {
				$Class = 'vr_top_50';
			}

			$LIs .= "<li id=\"vote_rank_$key\" class=\"$Class\">No. $Rank $text</li>";
		}
	}

	if ($LIs != '') {
?>
		<div class="box" id="votes_ranks">
			<div class="head"><strong><?=SITE_NAME?> Favorites</strong></div>
			<div class="vote_charts body">
				<ul class="stats nobullet" id="vote_rankings">
					<?=$LIs?>
				</ul>
			</div>
		</div>
<?
	}
}
?>
