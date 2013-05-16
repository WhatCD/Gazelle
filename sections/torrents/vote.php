<?
	$UserVotes = Votes::get_user_votes($LoggedUser['ID']);
	$GroupVotes = Votes::get_group_votes($GroupID);

	$TotalVotes = $GroupVotes['Total'];
	$UpVotes	= $GroupVotes['Ups'];

	$Voted = isset($UserVotes[$GroupID]) ? $UserVotes[$GroupID]['Type'] : false;
?>
<div class="box" id="votes">
	<div class="head"><strong>Album votes</strong></div>
	<div class="album_votes body">
		This has <span id="upvotes" class="favoritecount"><?=number_format($UpVotes)?></span> <?=(($UpVotes == 1) ? 'upvote' : 'upvotes')?> out of <span id="totalvotes" class="favoritecount"><?=number_format($TotalVotes)?></span> total<span id="upvoted"<?=(($Voted != 'Up') ? ' class="hidden"' : '')?>>, including your upvote</span><span id="downvoted"<?=(($Voted != 'Down') ? ' class="hidden"' : '')?>>, including your downvote</span>.
		<br /><br />
<?	if (check_perms('site_album_votes')) { ?>
		<span<?=($Voted ? ' class="hidden"' : '')?> id="vote_message"><a href="#" class="brackets upvote" onclick="UpVoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;">Upvote</a> - <a href="#" class="brackets downvote" onclick="DownVoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;">Downvote</a></span>
<?	} ?>
		<span<?=($Voted ? '' : ' class="hidden"')?> id="unvote_message">
			Changed your mind?
			<br />
			<a href="#" onclick="UnvoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;" class="brackets">Clear your vote</a>
		</span>
	</div>
</div>
