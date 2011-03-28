<?
if(!check_perms("site_moderate_forums")) {
	error(403);
}

authorize();
$ThreadID = $_GET['threadid'];
$NewVote = $_GET['vote'];

if(is_number($ThreadID) && is_number($NewVote)) {
	$DB->query("UPDATE forums_polls_votes SET Vote = ".$NewVote." WHERE TopicID = ".$ThreadID." AND UserID = ".$LoggedUser['ID']);
	$Cache->delete_value('polls_'.$ThreadID);
	header("Location: forums.php?action=viewthread&threadid=".$ThreadID);
	
} else {
	error(404);
}
