<?
	$UserVotes = Votes::get_user_votes($LoggedUser['ID']);
	$GroupVotes = Votes::get_group_votes($GroupID);

	$TotalVotes = $GroupVotes['Total'];
	$UpVotes	= $GroupVotes['Ups'];

	$Voted = isset($UserVotes[$GroupID])?$UserVotes[$GroupID]['Type']:false;
?>
<div class="box" id="votes">
	<div class="head"><strong>Album votes</strong></div>
	<div class="album_votes body">
		This has <span id="upvotes" class="favoritecount"><?=$UpVotes?></span> <?=(($UpVotes==1)?'upvote':'upvotes')?> out of <span id="totalvotes" class="favoritecount"><?=$TotalVotes?></span> total<span id="upvoted" <?=($Voted!='Up'?'class="hidden"':'')?>>, including your upvote</span><span id="downvoted" <?=($Voted!='Down'?'class="hidden"':'')?>>, including your downvote</span>.
		<br /><br />
<?	if (check_perms('site_album_votes')) { ?>
		<span <?=($Voted?'class="hidden"':'')?> id="vote_message"><a href="#" class="upvote" onclick="UpVoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;">Upvote</a> - <a href="#" class="downvote" onclick="DownVoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;">Downvote</a></span>
<?	} ?>
		<span <?=($Voted?'':'class="hidden"')?> id="unvote_message">Changed your mind?<br /><a href="#" onclick="UnvoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;">Clear your vote</a></span>
	</div>
</div>
