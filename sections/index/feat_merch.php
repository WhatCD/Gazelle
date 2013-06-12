<?

	$FeaturedMerchURL = '';

	$FeaturedMerch = $Cache->get_value('featured_merch');
	if ($FeaturedMerch === false) {
		$DB->query('
			SELECT ProductID, Title, Image, ArtistID
			FROM featured_merch
			WHERE Ended = 0');
		$FeaturedMerch = $DB->next_record(MYSQLI_ASSOC);
		$Cache->cache_value('featured_merch', $FeaturedMerch, 0);
	}

	if ($FeaturedMerch != null) {
?>
<div id="merchbox" class="box">
	<div class="head colhead_dark">
		<strong>Featured Product</strong>
	</div>
	<div class="center pad">
		<a href="http://anonym.to/?<?=$FeaturedMerchURL . $FeaturedMerch['ProductID']?>"><img src="<?=ImageTools::process($FeaturedMerch['Image'])?>" width="100%" alt="Featured Product Image" /></a>
	</div>
	<div class="center pad">
		<a href="http://anonym.to/?<?=$FeaturedMerchURL . $FeaturedMerch['ProductID']?>"><em>Product Page</em></a>
<?		if ($FeaturedMerch['ArtistID'] > 0) {
			$UserInfo = Users::user_info($FeaturedMerch['ArtistID']);
?>		- Artist: <a href="user.php?id=<?=$FeaturedMerch['ArtistID']?>"><?=$UserInfo['Username']?></a>
<?		} ?>
	</div>
</div>
<?	} else { ?>
<div class="box">
	<div class="head colhead_dark">
		<strong>It's a mystery!</strong>
	</div>
	<div class="center pad">
		You may want to put an image here.
	</div>
</div>
<?
	}
?>
