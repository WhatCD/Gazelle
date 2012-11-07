<?
//Show the "This album is number x overall, etc. box for music only
if ($GroupCategoryID == 1) {
	$Rankings = Votes::get_ranking($GroupID, $GroupYear);
	$LIs = '';
	// Display information for the return categories of get_ranking()
	$names = array('overall'=>'overall',
				   'decade'=>'for the '.($GroupYear-($GroupYear%10)).'s',
				   'year'=>"for $GroupYear");
				   
	foreach ($names as $key => $text) {
		if ($Rank = $Rankings[$key]) {
			if ($Rank <= 10) {
				$Class = "vr_top_10";
			} elseif ($Rank <= 25) {
				$Class = "vr_top_25";
			} elseif ($Rank <= 50) {
				$Class = "vr_top_50";
			}
			
			$LIs .= '<li id="vote_rank_'.$key.'" class="'.$Class.'">No. '.$Rank.' '.$text.'</li>';
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