var voteLock = false;
function DownVoteGroup(groupid, authkey) {
	if (voteLock) {
		return;
	}
	voteLock = true;
	ajax.get('ajax.php?action=votefavorite&do=vote&groupid='+groupid+'&vote=down'+'&auth='+authkey, function (response) {return});
	$('.vote_link_'+groupid).hide();
	$('.vote_clear_'+groupid).show();
	$('.voted_down_'+groupid).show();
	$('.voted_up_'+groupid).hide();
	voteLock = false;
}

function UpVoteGroup(groupid, authkey) {
	if (voteLock) {
		return;
	}
	voteLock = true;
	ajax.get('ajax.php?action=votefavorite&do=vote&groupid='+groupid+'&vote=up'+'&auth='+authkey, function (response) {return});
	$('.vote_link_'+groupid).hide();
	$('.vote_clear_'+groupid).show();
	$('.voted_down_'+groupid).hide();
	$('.voted_up_'+groupid).show();
	voteLock = false;
}

function UnvoteGroup(groupid, authkey) {
	if (voteLock) {
		return;
	}
	voteLock = true;
	ajax.get('ajax.php?action=votefavorite&do=unvote&groupid='+groupid+'&auth='+authkey, function (response) {return});
	$('.vote_link_'+groupid).show();
	$('.vote_clear_'+groupid).hide();
	$('.voted_down_'+groupid).hide();
	$('.voted_up_'+groupid).hide();
	voteLock = false;
}