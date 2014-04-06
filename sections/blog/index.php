<?
enforce_login();

define('ANNOUNCEMENT_FORUM_ID', 19);
View::show_header('Blog','bbcode');

if (check_perms('admin_manage_blog')) {
	if (!empty($_REQUEST['action'])) {
		switch ($_REQUEST['action']) {
			case 'deadthread':
				if (is_number($_GET['id'])) {
					$DB->query("
						UPDATE blog
						SET ThreadID = NULL
						WHERE ID = ".$_GET['id']);
					$Cache->delete_value('blog');
					$Cache->delete_value('feed_blog');
				}
				header('Location: blog.php');
				break;

			case 'takeeditblog':
				authorize();
				if (is_number($_POST['blogid']) && is_number($_POST['thread'])) {
					$DB->query("
						UPDATE blog
						SET
							Title = '".db_string($_POST['title'])."',
							Body = '".db_string($_POST['body'])."',
							ThreadID = ".$_POST['thread']."
						WHERE ID = '".db_string($_POST['blogid'])."'");
					$Cache->delete_value('blog');
					$Cache->delete_value('feed_blog');
				}
				header('Location: blog.php');
				break;

			case 'editblog':
				if (is_number($_GET['id'])) {
					$BlogID = $_GET['id'];
					$DB->query("
						SELECT Title, Body, ThreadID
						FROM blog
						WHERE ID = $BlogID");
					list($Title, $Body, $ThreadID) = $DB->next_record();
				}
				break;

			case 'deleteblog':
				if (is_number($_GET['id'])) {
					authorize();
					$DB->query("
						DELETE FROM blog
						WHERE ID = '".db_string($_GET['id'])."'");
					$Cache->delete_value('blog');
					$Cache->delete_value('feed_blog');
				}
				header('Location: blog.php');
				break;

			case 'takenewblog':
				authorize();
				$Title = db_string($_POST['title']);
				$Body = db_string($_POST['body']);
				$ThreadID = $_POST['thread'];
				if ($ThreadID && is_number($ThreadID)) {
					$DB->query("
						SELECT ForumID
						FROM forums_topics
						WHERE ID = $ThreadID");
					if (!$DB->has_results()) {
						error('No such thread exists!');
						header('Location: blog.php');
					}
				} else {
					$ThreadID = Misc::create_thread(ANNOUNCEMENT_FORUM_ID, $LoggedUser[ID], $Title, $Body);
					if ($ThreadID < 1) {
						error(0);
					}
				}

				$DB->query("
					INSERT INTO blog
						(UserID, Title, Body, Time, ThreadID, Important)
					VALUES
						('".$LoggedUser['ID']."',
						'".db_string($_POST['title'])."',
						'".db_string($_POST['body'])."',
						'".sqltime()."',
						$ThreadID,
						'".($_POST['important'] == '1' ? '1' : '0')."')");
				$Cache->delete_value('blog');
				if ($_POST['important'] == '1') {
					$Cache->delete_value('blog_latest_id');
				}
				if (isset($_POST['subscribe'])) {
					$DB->query("
						INSERT IGNORE INTO users_subscriptions
						VALUES ('$LoggedUser[ID]', $ThreadID)");
					$Cache->delete_value('subscriptions_user_'.$LoggedUser['ID']);
				}
				NotificationsManager::send_push(NotificationsManager::get_push_enabled_users(), $_POST['title'], $_POST['body'], site_url() . 'index.php', NotificationsManager::BLOG);

				header('Location: blog.php');
				break;
		}
	}

	?>
		<div class="box thin">
			<div class="head">
				<?=empty($_GET['action']) ? 'Create a blog post' : 'Edit blog post'?>
			</div>
			<form class="<?=empty($_GET['action']) ? 'create_form' : 'edit_form'?>" name="blog_post" action="blog.php" method="post">
				<div class="pad">
					<input type="hidden" name="action" value="<?=empty($_GET['action']) ? 'takenewblog' : 'takeeditblog'?>" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
<?	if (!empty($_GET['action']) && $_GET['action'] == 'editblog') { ?>
					<input type="hidden" name="blogid" value="<?=$BlogID; ?>" />
<?	} ?>
					<h3>Title</h3>
					<input type="text" name="title" size="95"<?=!empty($Title) ? ' value="'.display_str($Title).'"' : '';?> /><br />
					<h3>Body</h3>
					<textarea name="body" cols="95" rows="15"><?=!empty($Body) ? display_str($Body) : '';?></textarea> <br />
					<input type="checkbox" value="1" name="important" id="important" checked="checked" /><label for="important">Important</label><br />
					<h3>Thread ID</h3>
					<input type="text" name="thread" size="8"<?=!empty($ThreadID) ? ' value="'.display_str($ThreadID).'"' : '';?> />
					(Leave blank to create thread automatically)
					<br /><br />
					<input id="subscribebox" type="checkbox" name="subscribe"<?=!empty($HeavyInfo['AutoSubscribe']) ? ' checked="checked"' : '';?> tabindex="2" />
					<label for="subscribebox">Subscribe</label>

					<div class="center">
						<input type="submit" value="<?=!isset($_GET['action']) ? 'Create blog post' : 'Edit blog post';?>" />
					</div>
				</div>
			</form>
		</div>
		<br />
<?
}
?>
<div class="thin">
<?
if (!$Blog = $Cache->get_value('blog')) {
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

if ($LoggedUser['LastReadBlog'] < $Blog[0][0]) {
	$Cache->begin_transaction('user_info_heavy_'.$LoggedUser['ID']);
	$Cache->update_row(false, array('LastReadBlog' => $Blog[0][0]));
	$Cache->commit_transaction(0);
	$DB->query("
		UPDATE users_info
		SET LastReadBlog = '".$Blog[0][0]."'
		WHERE UserID = ".$LoggedUser['ID']);
	$LoggedUser['LastReadBlog'] = $Blog[0][0];
}

foreach ($Blog as $BlogItem) {
	list($BlogID, $Author, $AuthorID, $Title, $Body, $BlogTime, $ThreadID) = $BlogItem;
?>
	<div id="blog<?=$BlogID?>" class="box blog_post">
		<div class="head">
			<strong><?=$Title?></strong> - posted <?=time_diff($BlogTime);?> by <a href="user.php?id=<?=$AuthorID?>"><?=$Author?></a>
<?	if (check_perms('admin_manage_blog')) { ?>
			- <a href="blog.php?action=editblog&amp;id=<?=$BlogID?>" class="brackets">Edit</a>
			<a href="blog.php?action=deleteblog&amp;id=<?=$BlogID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Delete</a>
<?	} ?>
		</div>
		<div class="pad">
					<?=Text::full_format($Body)?>
<?	if ($ThreadID) { ?>
			<br /><br />
			<em><a href="forums.php?action=viewthread&amp;threadid=<?=$ThreadID?>">Discuss this post here</a></em>
<?		if (check_perms('admin_manage_blog')) { ?>
			<a href="blog.php?action=deadthread&amp;id=<?=$BlogID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" class="brackets">Remove link</a>
<?
		}
	}
?>
		</div>
	</div>
	<br />
<?
}
?>
</div>
<?
View::show_footer();
?>
