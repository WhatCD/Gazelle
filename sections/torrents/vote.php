<?
$UserVotes = $Cache->get_value('voted_albums_'.$LoggedUser['ID']);
if ($UserVotes === FALSE) {
	$DB->query('SELECT GroupID, Type FROM users_votes WHERE UserID='.$LoggedUser['ID']);
	$UserVotes = $DB->to_array('GroupID', MYSQL_ASSOC, false);
	$Cache->cache_value('voted_albums_'.$LoggedUser['ID'], $UserVotes);
}

$GroupVotes = $Cache->get_value('votes_'.$GroupID);
if ($GroupVotes === FALSE) {
	$DB->query("SELECT Ups AS Ups, Total AS Total FROM torrents_votes WHERE GroupID=$GroupID");
	if ($DB->record_count() == 0) {
		$GroupVotes = array('Ups'=>0, 'Total'=>0);
	} else {
		$GroupVotes = $DB->next_record(MYSQLI_ASSOC, false);
	}
	$Cache->cache_value('votes_'.$GroupID, $GroupVotes);
}
$TotalVotes = $GroupVotes['Total'];
$UpVotes    = $GroupVotes['Ups'];

$Voted = isset($UserVotes[$GroupID])?$UserVotes[$GroupID]['Type']:false;
?>
<div class="box" id="votes">
	<div class="head"><strong>Favorite Album Votes</strong></div>
	<div class="album_votes body">
		This has <span id="upvotes" class="favoritecount"><?=$UpVotes?></span> <?=(($UpVotes==1)?'upvote':'upvotes')?> out of <span id="totalvotes" class="favoritecount"><?=$TotalVotes?></span> total<span id="upvoted" <?=($Voted!='Up'?'class="hidden"':'')?>>, including your upvote</span><span id="downvoted" <?=($Voted!='Down'?'class="hidden"':'')?>>, including your downvote</span>.
		<br /><br />
<?	if (check_perms('site_album_votes')) { ?>
		<span <?=($Voted?'class="hidden"':'')?> id="vote_message"><a href="#" onClick="UpVoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;">Upvote</a> - <a href="#" onClick="DownVoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;">Downvote</a></span>
<?	} ?>
		<span <?=($Voted?'':'class="hidden"')?> id="unvote_message">Changed your mind?<br /><a href="#" onClick="UnvoteGroup(<?=$GroupID?>, '<?=$LoggedUser['AuthKey']?>'); return false;">Clear your vote</a></span>
	</div>
</div>
