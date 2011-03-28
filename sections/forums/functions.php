<?
function get_thread_info($ThreadID, $Return = true, $SelectiveCache = false) {
	global $DB, $Cache;
	if(!$ThreadInfo = $Cache->get_value('thread_'.$ThreadID.'_info')) {
		$DB->query("SELECT
			t.Title,
			t.ForumID,
			t.IsLocked,
			t.IsSticky,
			COUNT(fp.id) AS Posts,
			t.LastPostAuthorID,
			ISNULL(p.TopicID) AS NoPoll,
			t.StickyPostID
			FROM forums_topics AS t
			JOIN forums_posts AS fp ON fp.TopicID = t.ID
			LEFT JOIN forums_polls AS p ON p.TopicID=t.ID
			WHERE t.ID = '$ThreadID'
			GROUP BY fp.TopicID");
		if($DB->record_count()==0) { error(404); }
		$ThreadInfo = $DB->next_record(MYSQLI_ASSOC);
		if($ThreadInfo['StickyPostID']) {
			$DB->query("SELECT
				p.ID,
				p.AuthorID,
				p.AddedTime,
				p.Body,
				p.EditedUserID,
				p.EditedTime,
				ed.Username
				FROM forums_posts as p
				LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
				WHERE p.TopicID = '$ThreadID' AND p.ID = '".$ThreadInfo['StickyPostID']."'");
			list($ThreadInfo['StickyPost']) = $DB->to_array(false, MYSQLI_ASSOC);
		}
		if(!$SelectiveCache || (!$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky'])) {
			$Cache->cache_value('thread_'.$TheadID.'_info', $ThreadInfo, 0);
		}
	}
	if($Return) {
		return $ThreadInfo;
	}
}
