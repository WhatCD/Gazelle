<?
authorize();
$UserSubscriptions = Subscriptions::get_subscriptions();
if (!empty($UserSubscriptions)) {
	$DB->query("
		INSERT INTO forums_last_read_topics (UserID, TopicID, PostID)
			SELECT '$LoggedUser[ID]', ID, LastPostID
			FROM forums_topics
			WHERE ID IN (".implode(',', $UserSubscriptions).')
		ON DUPLICATE KEY UPDATE
			PostID = LastPostID');
}
$DB->query("
	INSERT INTO users_comments_last_read (UserID, Page, PageID, PostID)
	SELECT $LoggedUser[ID], t.Page, t.PageID, t.LastPostID
	FROM (
		SELECT
			s.Page,
			s.PageID,
			IFNULL(c.ID, 0) AS LastPostID
		FROM users_subscriptions_comments AS s
			LEFT JOIN comments AS c ON c.Page = s.Page
				AND c.ID = (
						SELECT MAX(ID)
						FROM comments
						WHERE Page = s.Page
							AND PageID = s.PageID
						)
	) AS t
	ON DUPLICATE KEY UPDATE
		PostID = LastPostID");
$Cache->delete_value('subscriptions_user_new_'.$LoggedUser['ID']);
header('Location: userhistory.php?action=subscriptions');
?>
