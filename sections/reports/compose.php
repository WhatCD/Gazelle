<?
if (!check_perms('site_moderate_forums')) {
	error(403);
}

if (empty($Return)) {
	$ToID = $_GET['to'];
	if ($ToID == $LoggedUser['ID']) {
		error("You cannot start a conversation with yourself!");
		header('Location: inbox.php');
	}
}

if (!$ToID || !is_number($ToID)) {
	error(404);
}

$ReportID = $_GET['reportid'];
$Type = $_GET['type'];
$ThingID= $_GET['thingid'];

if (!$ReportID || !is_number($ReportID) || !$ThingID || !is_number($ThingID) || !$Type) {
	error(403);
}

if (!empty($LoggedUser['DisablePM']) && !isset($StaffIDs[$ToID])) {
	error(403);
}

$DB->query("
	SELECT Username
	FROM users_main
	WHERE ID='$ToID'");
list($ComposeToUsername) = $DB->next_record();
if (!$ComposeToUsername) {
	error(404);
}
View::show_header('Compose', 'inbox,bbcode');

// $TypeLink is placed directly in the <textarea> when composing a PM
switch ($Type) {
	case 'user':
		$DB->query("
			SELECT Username
			FROM users_main
			WHERE ID=$ThingID");
		if ($DB->record_count() < 1) {
			$Error = 'No user with the reported ID found';
		} else {
			list($Username) = $DB->next_record();
			$TypeLink = "the user [user]{$Username}[/user]";
			$Subject = 'User Report: '.display_str($Username);
		}
		break;
	case 'request':
	case 'request_update':
		$DB->query("
			SELECT Title
			FROM requests
			WHERE ID=$ThingID");
		if ($DB->record_count() < 1) {
			$Error = 'No request with the reported ID found';
		} else {
			list($Name) = $DB->next_record();
			$TypeLink = 'the request [url=https://'.SSL_SITE_URL."/requests.php?action=view&amp;id=$ThingID]".display_str($Name).'[/url]';
			$Subject = 'Request Report: '.display_str($Name);
		}
		break;
	case 'collage':
		$DB->query("
			SELECT Name
			FROM collages
			WHERE ID=$ThingID");
		if ($DB->record_count() < 1) {
			$Error = 'No collage with the reported ID found';
		} else {
			list($Name) = $DB->next_record();
			$TypeLink = 'the collage [url=https://'.SSL_SITE_URL."/collage.php?id=$ThingID]".display_str($Name).'[/url]';
			$Subject = 'Collage Report: '.display_str($Name);
		}
		break;
	case 'thread':
		$DB->query("
			SELECT Title
			FROM forums_topics
			WHERE ID=$ThingID");
		if ($DB->record_count() < 1) {
			$Error = 'No forum thread with the reported ID found';
		} else {
			list($Title) = $DB->next_record();
			$TypeLink = 'the forum thread [url=https://'.SSL_SITE_URL."/forums.php?action=viewthread&amp;threadid=$ThingID]".display_str($Title).'[/url]';
			$Subject = 'Forum Thread Report: '.display_str($Title);
		}
		break;
	case 'post':
		if (isset($LoggedUser['PostsPerPage'])) {
			$PerPage = $LoggedUser['PostsPerPage'];
		} else {
			$PerPage = POSTS_PER_PAGE;
		}
		$DB->query("
			SELECT
				p.ID,
				p.Body,
				p.TopicID,
				(	SELECT COUNT(ID)
					FROM forums_posts
					WHERE forums_posts.TopicID = p.TopicID
						AND forums_posts.ID<=p.ID
				) AS PostNum
			FROM forums_posts AS p
			WHERE ID=$ThingID");
		if ($DB->record_count() < 1) {
			$Error = 'No forum post with the reported ID found';
		} else {
			list($PostID, $Body, $TopicID, $PostNum) = $DB->next_record();
			$TypeLink = 'this [url=https://'.SSL_SITE_URL."/forums.php?action=viewthread&amp;threadid=$TopicID&amp;post=$PostNum#post$PostID]forum post[/url]";
			$Subject = 'Forum Post Report: Post ID #'.display_str($PostID);
		}
		break;
	case 'requests_comment':
		$DB->query("
			SELECT
				rc.RequestID,
				rc.Body,
				(	SELECT COUNT(ID)
					FROM requests_comments
					WHERE ID <= $ThingID
						AND requests_comments.RequestID = rc.RequestID
				) AS CommentNum
			FROM requests_comments AS rc
			WHERE ID=$ThingID");
		if ($DB->record_count() < 1) {
			$Error = 'No request comment with the reported ID found';
		} else {
			list($RequestID, $Body, $PostNum) = $DB->next_record();
			$PageNum = ceil($PostNum / TORRENT_COMMENTS_PER_PAGE);
			$TypeLink = 'this [url=https://'.SSL_SITE_URL."/requests.php?action=view&amp;id=$RequestID&amp;page=$PageNum#post$ThingID]request comment[/url]";
			$Subject = 'Request Comment Report: ID #'.display_str($ThingID);
		}
		break;
	case 'torrents_comment':
		$DB->query("
			SELECT
				tc.GroupID,
				tc.Body,
				(	SELECT COUNT(ID)
					FROM torrents_comments
					WHERE ID <= $ThingID
						AND torrents_comments.GroupID = tc.GroupID
				) AS CommentNum
			FROM torrents_comments AS tc
			WHERE ID=$ThingID");
		if ($DB->record_count() < 1) {
			$Error = 'No torrent comment with the reported ID found';
		} else {
			list($GroupID, $Body, $PostNum) = $DB->next_record();
			$PageNum = ceil($PostNum / TORRENT_COMMENTS_PER_PAGE);
			$TypeLink = 'this [url=https://'.SSL_SITE_URL."/torrents.php?id=$GroupID&amp;page=$PageNum#post$ThingID]torrent comment[/url]";
			$Subject = 'Torrent Comment Report: ID #'.display_str($ThingID);
		}
		break;
	case 'collages_comment':
		$DB->query("
			SELECT
				cc.CollageID,
				cc.Body,
				(	SELECT COUNT(ID)
					FROM collages_comments
					WHERE ID <= $ThingID
						AND collages_comments.CollageID = cc.CollageID
				) AS CommentNum
			FROM collages_comments AS cc
			WHERE ID=$ThingID");
		if ($DB->record_count() < 1) {
			$Error = 'No collage comment with the reported ID found';
		} else {
			list($CollageID, $Body, $PostNum) = $DB->next_record();
			$PerPage = POSTS_PER_PAGE;
			$PageNum = ceil($PostNum / $PerPage);
			$TypeLink = 'this [url=https://'.SSL_SITE_URL."/collage.php?action=comments&amp;collageid=$CollageID&amp;page=$PageNum#post$ThingID]collage comment[/url]";
			$Subject = 'Collage Comment Report: ID #'.display_str($ThingID);
		}
		break;
	default:
		error('Incorrect type');
		break;
}
if (isset($Error)) {
	error($Error);
}

$DB->query("
	SELECT r.Reason
	FROM reports AS r
	WHERE r.ID = $ReportID");
list($Reason) = $DB->next_record();

$Body = "You reported $TypeLink for the reason:\n[quote]{$Reason}[/quote]";

?>
<div class="thin">
	<div class="header">
		<h2>
			Send a message to <a href="user.php?id=<?=$ToID?>"> <?=$ComposeToUsername?></a>
		</h2>
	</div>
	<form class="send_form" name="message" action="reports.php" method="post" id="messageform">
		<div class="box pad">
			<input type="hidden" name="action" value="takecompose" />
			<input type="hidden" name="toid" value="<?=$ToID?>" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<div id="quickpost">
				<h3>Subject</h3>
				<input type="text" name="subject" size="95" value="<?=(!empty($Subject) ? $Subject : '')?>" />
				<br />
				<h3>Body</h3>
				<textarea id="body" name="body" cols="95" rows="10"><?=(!empty($Body) ? $Body : '')?></textarea>
			</div>
			<div id="preview" class="hidden"></div>
			<div id="buttons" class="center">
				<input type="button" value="Preview" onclick="Quick_Preview();" />
				<input type="submit" value="Send message" />
			</div>
		</div>
	</form>
</div>
<?
View::show_footer();
?>
