var voteLock = false;
function DownVoteGroup(groupid, authkey) {
	if (voteLock) {
		return;
	}
	voteLock = true;
	ajax.get('ajax.php?action=votefavorite&do=vote&groupid=' + groupid + '&vote=down' + '&auth=' + authkey, function (response) { return });
	$('.vote_link_' + groupid).ghide();
	$('.vote_clear_' + groupid).gshow();
	$('.voted_down_' + groupid).gshow();
	$('.voted_up_' + groupid).ghide();
	voteLock = false;
}

function UpVoteGroup(groupid, authkey) {
	if (voteLock) {
		return;
	}
	voteLock = true;
	ajax.get('ajax.php?action=votefavorite&do=vote&groupid=' + groupid + '&vote=up' + '&auth=' + authkey, function (response) { return });
	$('.vote_link_' + groupid).ghide();
	$('.vote_clear_' + groupid).gshow();
	$('.voted_down_' + groupid).ghide();
	$('.voted_up_' + groupid).gshow();
	voteLock = false;
}

function UnvoteGroup(groupid, authkey) {
	if (voteLock) {
		return;
	}
	voteLock = true;
	ajax.get('ajax.php?action=votefavorite&do=unvote&groupid=' + groupid + '&auth=' + authkey, function (response) { return });
	$('.vote_link_' + groupid).gshow();
	$('.vote_clear_' + groupid).ghide();
	$('.voted_down_' + groupid).ghide();
	$('.voted_up_' + groupid).ghide();
	voteLock = false;
}
