<?
if (!$News = $Cache->get_value('news')) {
	$DB->query("
		SELECT
			ID,
			Title,
			Body,
			Time
		FROM news
		ORDER BY Time DESC
		LIMIT 5");
	$News = $DB->to_array(false, MYSQLI_NUM, false);
	$Cache->cache_value('news', $News, 3600 * 24 * 30);
	$Cache->cache_value('news_latest_id', $News[0][0], 0);
}

if ($LoggedUser['LastReadNews'] != $News[0][0]) {
	$Cache->begin_transaction("user_info_heavy_$UserID");
	$Cache->update_row(false, array('LastReadNews' => $News[0][0]));
	$Cache->commit_transaction(0);
	$DB->query("
		UPDATE users_info
		SET LastReadNews = '".$News[0][0]."'
		WHERE UserID = $UserID");
	$LoggedUser['LastReadNews'] = $News[0][0];
}

if (($Blog = $Cache->get_value('blog')) === false) {
	$DB->query("
		SELECT
			b.ID,
			um.Username,
			b.UserID,
			b.Title,
			b.Body,
			b.Time,
			b.ThreadID
		FROM blog AS b
			LEFT JOIN users_main AS um ON b.UserID = um.ID
		ORDER BY Time DESC
		LIMIT 20");
	$Blog = $DB->to_array();
	$Cache->cache_value('blog', $Blog, 1209600);
}
$JsonBlog = array();
for ($i = 0; $i < 5; $i++) {
	list($BlogID, $Author, $AuthorID, $Title, $Body, $BlogTime, $ThreadID) = $Blog[$i];
	$JsonBlog[] = array(
		'blogId' => (int)$BlogID,
		'author' => $Author,
		'title' => $Title,
		'bbBody' => $Body,
		'body' => Text::full_format($Body),
		'blogTime' => $BlogTime,
		'threadId' => (int)$ThreadID
	);
}

$JsonAnnouncements = array();
$Count = 0;
foreach ($News as $NewsItem) {
	list($NewsID, $Title, $Body, $NewsTime) = $NewsItem;
	if (strtotime($NewsTime) > time()) {
		continue;
	}

	$JsonAnnouncements[] = array(
		'newsId' => (int)$NewsID,
		'title' => $Title,
		'bbBody' => $Body,
		'body' => Text::full_format($Body),
		'newsTime' => $NewsTime
	);

	if (++$Count > 4) {
		break;
	}
}

json_die("success", array(
	'announcements' => $JsonAnnouncements,
	'blogPosts' => $JsonBlog
));
