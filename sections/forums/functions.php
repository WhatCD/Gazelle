<?
function get_thread_info($ThreadID, $Return = true, $SelectiveCache = false, $ApiCall = false) {
	global $DB, $Cache;
	if ((!$ThreadInfo = $Cache->get_value('thread_'.$ThreadID.'_info')) || !isset($ThreadInfo['OP'])) {
		$DB->query("
			SELECT
				t.Title,
				t.ForumID,
				t.IsLocked,
				t.IsSticky,
				COUNT(fp.id) AS Posts,
				t.LastPostAuthorID,
				ISNULL(p.TopicID) AS NoPoll,
				t.StickyPostID,
				t.AuthorID as OP,
				t.Ranking
			FROM forums_topics AS t
				JOIN forums_posts AS fp ON fp.TopicID = t.ID
				LEFT JOIN forums_polls AS p ON p.TopicID=t.ID
			WHERE t.ID = '$ThreadID'
			GROUP BY fp.TopicID");
		if ($DB->record_count() == 0) {
			if (!$ApiCall) {
				error(404);
			} else {
				return NULL;
			}
		}
		$ThreadInfo = $DB->next_record(MYSQLI_ASSOC, false);
		if ($ThreadInfo['StickyPostID']) {
			$ThreadInfo['Posts']--;
			$DB->query("
				SELECT
					p.ID,
					p.AuthorID,
					p.AddedTime,
					p.Body,
					p.EditedUserID,
					p.EditedTime,
					ed.Username
				FROM forums_posts as p
					LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
				WHERE p.TopicID = '$ThreadID'
					AND p.ID = '".$ThreadInfo['StickyPostID']."'");
			list($ThreadInfo['StickyPost']) = $DB->to_array(false, MYSQLI_ASSOC);
		}
		if (!$SelectiveCache || !$ThreadInfo['IsLocked'] || $ThreadInfo['IsSticky']) {
			$Cache->cache_value('thread_'.$ThreadID.'_info', $ThreadInfo, 0);
		}
	}
	if ($Return) {
		return $ThreadInfo;
	}
}

function check_forumperm($ForumID, $Perm = 'Read') {
	global $LoggedUser, $Forums;
	if ($LoggedUser['CustomForums'][$ForumID] == 1) {
		return true;
	}
	if ($Forums[$ForumID]['MinClass'.$Perm] > $LoggedUser['Class'] && (!isset($LoggedUser['CustomForums'][$ForumID]) || $LoggedUser['CustomForums'][$ForumID] == 0)) {
		return false;
	}
	if (isset($LoggedUser['CustomForums'][$ForumID]) && $LoggedUser['CustomForums'][$ForumID] == 0) {
		return false;
	}
	return true;
}

// Function to get basic information on a forum
// Uses class CACHE
function get_forum_info($ForumID) {
	global $DB, $Cache;
	$Forum = $Cache->get_value('ForumInfo_'.$ForumID);
	if (!$Forum) {
		$DB->query("
			SELECT
				Name,
				MinClassRead,
				MinClassWrite,
				MinClassCreate,
				COUNT(forums_topics.ID) AS Topics
			FROM forums
				LEFT JOIN forums_topics ON forums_topics.ForumID=forums.ID
			WHERE forums.ID='$ForumID'
			GROUP BY ForumID");
		if ($DB->record_count() == 0) {
			return false;
		}
		// Makes an array, with $Forum['Name'], etc.
		$Forum = $DB->next_record(MYSQLI_ASSOC);

		$Cache->cache_value('ForumInfo_'.$ForumID, $Forum, 86400); // Cache for a day
	}
	return $Forum;
}
