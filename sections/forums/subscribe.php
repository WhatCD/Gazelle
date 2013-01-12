<?php
$ForumID = (int) ($_GET['forumid']);
if(empty($ForumID)) {
	error(403);
}
$SubscribedForumIDs = $Cache->get("subscribed_forum_ids_".$LoggedUser['ID']);
if(empty($SubscribedForumIDs)) {
	$SubscribedForumIDs = array();
	$DB->query("SELECT ForumID FROM subscribed_forums WHERE UserID = $LoggedUser[ID]");
	if($DB->record_count() > 0) {
		$SubscribedForumIDs = $DB->collect('ForumID');
	}
	$Cache->cache_value("subscribed_forum_ids_".$LoggedUser['ID'], $SubscribedForumIDs, 0);
}

if($_GET['do'] == 'add') {
	if(!in_array($ForumID, $SubscribedForumIDs)) {
		$SubscribedForumIDs[] = $ForumID;
	}

	$DB->query("INSERT INTO subscribed_forums
				(ForumID, UserID)
				VALUES($ForumID, $LoggedUser[ID])");
	$Cache->replace_value("subscribed_forum_ids_".$LoggedUser['ID'], $SubscribedForumIDs, 0);
}
elseif($_GET['do'] == 'remove') {
	$SubscribedForumIDs = array_diff($SubscribedForumIDs, array($ForumID));
	if(count($SubscribedForumIDs) > 0) {
		$DB->query("DELETE FROM subscribed_forums WHERE UserID = $LoggedUser[ID] AND ForumID = $ForumID");
		$Cache->delete_value("subscribed_forum_ids_".$LoggedUser['ID']);
	}
}

header('Location: forums.php?action=viewforum&forumid=' . $ForumID);

