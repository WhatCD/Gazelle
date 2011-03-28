<?
authorize();
if(($UserSubscriptions = $Cache->get_value('subscriptions_user_'.$LoggedUser['ID'])) === false) {
	$DB->query('SELECT TopicID FROM users_subscriptions WHERE UserID = '.db_string($LoggedUser['ID']));
	if($UserSubscriptions = $DB->collect(0)) {
		$Cache->cache_value('subscriptions_user_'.$LoggedUser['ID'],$UserSubscriptions,0);
	}
}
if(!empty($UserSubscriptions)) {
	$DB->query("INSERT INTO forums_last_read_topics (UserID, TopicID, PostID)
		SELECT '$LoggedUser[ID]', ID, LastPostID FROM
		forums_topics
		WHERE ID IN (".implode(',',$UserSubscriptions).")
		ON DUPLICATE KEY UPDATE PostID=LastPostID");
}
$Cache->delete_value('subscriptions_user_new_'.$LoggedUser['ID']);
header('Location: userhistory.php?action=subscriptions');
?>
