<?
enforce_login();

if(!check_perms('users_mod')) {
	error(403);
}

define('ANNOUNCEMENT_FORUM_ID', 19);
show_header('Staff Blog','bbcode');
require(SERVER_ROOT.'/classes/class_text.php');
$Text = new TEXT;

if(check_perms('admin_manage_blog')) {
	if(!empty($_REQUEST['action'])) {
		switch($_REQUEST['action']) {
			case 'takeeditblog':
				authorize();
				if (empty($_POST['title'])) {
					error("Please enter a title.");
				}
				if(is_number($_POST['blogid'])) {
					$DB->query("UPDATE staff_blog SET Title='".db_string($_POST['title'])."', Body='".db_string($_POST['body'])."' WHERE ID='".db_string($_POST['blogid'])."'");
					$Cache->delete_value('staff_blog');
					$Cache->delete_value('staff_feed_blog');
				}
				header('Location: staffblog.php');
				break;
			case 'editblog':
				if(is_number($_GET['id'])){
					$BlogID = $_GET['id'];
					$DB->query("SELECT Title, Body FROM staff_blog WHERE ID=$BlogID");
					list($Title, $Body, $ThreadID) = $DB->next_record();
				}
				break;
			case 'deleteblog':
				if(is_number($_GET['id'])){
					authorize();
					$DB->query("DELETE FROM staff_blog WHERE ID='".db_string($_GET['id'])."'");
					$Cache->delete_value('staff_blog');
					$Cache->delete_value('staff_feed_blog');
				}
				header('Location: staffblog.php');
				break;
		
			case 'takenewblog':
				authorize();
				if (empty($_POST['title'])) {
					error("Please enter a title.");
				}
				$Title = db_string($_POST['title']);
				$Body = db_string($_POST['body']);
				
				$DB->query("INSERT INTO staff_blog (UserID, Title, Body, Time) VALUES ('$LoggedUser[ID]', '".db_string($_POST['title'])."', '".db_string($_POST['body'])."', '".sqltime()."')");
				$Cache->delete_value('staff_blog');
				
				send_irc("PRIVMSG ".ADMIN_CHAN." :!blog " . $_POST['title']);
		
				header('Location: staffblog.php');
				break;
		}
	}
		
	?>
		<div class="box thin">
			<div class="head">
				<?=((empty($_GET['action'])) ? 'Create a staff blog post' : 'Edit staff blog post')?>
				<span style="float:right;">
					<a href="#" onclick="$('#postform').toggle(); this.innerHTML=(this.innerHTML=='(Hide)'?'(Show)':'(Hide)'); return false;"><?=($_REQUEST['action']!='editblog')?'(Show)':'(Hide)'?></a>
				</span>
			</div>
			<form action="staffblog.php" method="post">
				<div id="postform" class="pad<?=($_REQUEST['action']!='editblog')?' hidden':''?>">	
					<input type="hidden" name="action" value="<?=((empty($_GET['action'])) ? 'takenewblog' : 'takeeditblog')?>" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<? if(!empty($_GET['action']) && $_GET['action'] == 'editblog'){?> 
					<input type="hidden" name="blogid" value="<?=$BlogID; ?>" />
	<? }?> 
					<h3>Title</h3>
					<input type="text" name="title" size="95" <? if(!empty($Title)) { echo 'value="'.display_str($Title).'"'; } ?> /><br />
					<h3>Body</h3>
					<textarea name="body" cols="95" rows="15"><? if(!empty($Body)) { echo display_str($Body); } ?></textarea> <br />
					<br /><br />
					<div class="center">
						<input type="submit" value="<?=((!isset($_GET['action'])) ? 'Create blog post' : 'Edit blog post') ?>" />
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
if (!$Blog = $Cache->get_value('staff_blog')) {
	$DB->query("SELECT
		b.ID,
		um.Username,
		b.Title,
		b.Body,
		b.Time
		FROM staff_blog AS b LEFT JOIN users_main AS um ON b.UserID=um.ID
		ORDER BY Time DESC
		LIMIT 20");
	$Blog = $DB->to_array();
	$Cache->cache_value('Blog',$Blog,1209600);
}

$DB->query("INSERT INTO staff_blog_visits (UserID, Time) VALUES (".$LoggedUser['ID'].", NOW()) ON DUPLICATE KEY UPDATE Time=NOW()");
$Cache->delete_value('staff_blog_read_'.$LoggedUser['ID']);

foreach ($Blog as $BlogItem) {
	list($BlogID, $Author, $Title, $Body, $BlogTime) = $BlogItem;
?>
			<div id="blog<?=$BlogID?>" class="box">
				<div class="head">
					<strong><?=$Title?></strong> - posted <?=time_diff($BlogTime);?> by <?=$Author?>
		<? if(check_perms('admin_manage_blog')) { ?> 
					- <a href="staffblog.php?action=editblog&amp;id=<?=$BlogID?>">[Edit]</a>
					<a href="staffblog.php?action=deleteblog&amp;id=<?=$BlogID?>&amp;auth=<?=$LoggedUser['AuthKey']?>" onClick="return confirm('Do you want to delete this?')">[Delete]</a>
		 <? } ?>
				</div>
				<div class="pad">
					<?=$Text->full_format($Body)?>
				</div>
			</div>
		<br />
<? 
}
?>
</div>
<?
show_footer();
?>
