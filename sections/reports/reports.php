<?
/************************************************************************

 ************************************************************************/
if(!check_perms('admin_reports') && !check_perms('project_team')) {
	error(404);
}

// Number of reports per page
define('REPORTS_PER_PAGE', '10');
include(SERVER_ROOT.'/classes/class_text.php');
$Text = NEW TEXT;

list($Page,$Limit) = page_limit(REPORTS_PER_PAGE);

include(SERVER_ROOT.'/sections/reports/array.php');

// Header
show_header('Reports','bbcode');

if($_GET['id'] && is_number($_GET['id'])) {
	$View = "Single report";
	$Where = "r.ID = ".$_GET['id'];
} else if(empty($_GET['view'])) {
	$View = "New";
	$Where = "Status='New'";
} else {
	$View = $_GET['view'];
	switch($_GET['view']) {
		case 'old' :
			$Where = "Status='Resolved'";
			break;
		default : 
			error(404);
			break;
	}
}

if(!check_perms('admin_reports')) {
	$Where .= " AND Type = 'request_update'";
}

$Reports = $DB->query("SELECT SQL_CALC_FOUND_ROWS 
		r.ID, 
		r.UserID,
		um.Username, 
		r.ThingID, 
		r.Type, 
		r.ReportedTime, 
		r.Reason, 
		r.Status
	FROM reports AS r 
		JOIN users_main AS um ON r.UserID=um.ID 
	WHERE ".$Where." 
	ORDER BY ReportedTime 
	DESC LIMIT ".$Limit);

// Number of results (for pagination)
$DB->query('SELECT FOUND_ROWS()');
list($Results) = $DB->next_record();

// Done with the number of results. Move $DB back to the result set for the reports
$DB->set_query_id($Reports);

// Start printing stuff
?>
<div class="thin">
<h2>Active Reports</h2>
<div class="linkbox">
	<a href="reports.php">New</a> |
	<a href="reports.php?view=old">Old</a> |
	<a href="reports.php?action=stats">Stats</a>
</div>
<div class="linkbox">
<?
	// pagination
	$Pages = get_pages($Page,$Results,REPORTS_PER_PAGE,11);
	echo $Pages;
?>
</div>
<?
while(list($ReportID, $SnitchID, $SnitchName, $ThingID, $Short, $ReportedTime, $Reason, $Status) = $DB->next_record()) {
	$Type = $Types[$Short];
	$Reference = "reports.php?id=".$ReportID."#report".$ReportID;
?>
<div id="report<?=$ReportID?>">
<form action="reports.php" method="post">
	<div>
		<input type="hidden" name="reportid" value="<?=$ReportID?>" />
		<input type="hidden" name="action" value="takeresolve" />
		<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	</div>
	<table cellpadding="5" id="report_<?=$ReportID?>">
		<tr>
			<td><strong><a href="<?=$Reference?>">Report</a></strong></td>
			<td><strong><?=$Type['title']?></strong> was reported by <a href="user.php?id=<?=$SnitchID?>"><?=$SnitchName?></a> <?=time_diff($ReportedTime)?></td>
		</tr>
		<tr>
			
			<td class="center" colspan="2">
				<strong>
<?
	switch($Short) {
		case "user" :
			$DB->query("SELECT Username FROM users_main WHERE ID=".$ThingID);
			if($DB->record_count() < 1) {
				echo "No user with the reported ID found";
			} else {
				list($Username) = $DB->next_record();
				echo "<a href='user.php?id=".$ThingID."'>".display_str($Username)."</a>";
			}
			break;
		case "request" :
		case "request_update" :
			$DB->query("SELECT Title FROM requests WHERE ID=".$ThingID);
			if($DB->record_count() < 1) {
				echo "No request with the reported ID found";
			} else {
				list($Name) = $DB->next_record();
				echo "<a href='requests.php?action=view&amp;id=".$ThingID."'>".display_str($Name)."</a>";
			}
			break;
		case "collage" :
			$DB->query("SELECT Name FROM collages WHERE ID=".$ThingID);
			if($DB->record_count() < 1) {
				echo "No collage with the reported ID found";
			} else {
				list($Name) = $DB->next_record();
				echo "<a href='collages.php?id=".$ThingID."'>".display_str($Name)."</a>";
			}
			break;
		case "thread" :
			$DB->query("SELECT Title FROM forums_topics WHERE ID=".$ThingID);
			if($DB->record_count() < 1) {
				echo "No thread with the reported ID found";
			} else {
				list($Title) = $DB->next_record();
				echo "<a href='forums.php?action=viewthread&amp;threadid=".$ThingID."'>".display_str($Title)."</a>";
			}
			break;
		case "post" :
			if (isset($LoggedUser['PostsPerPage'])) {
				$PerPage = $LoggedUser['PostsPerPage'];
			} else {
				$PerPage = POSTS_PER_PAGE;
			}
			$DB->query("SELECT p.ID, p.Body, p.TopicID, (SELECT COUNT(ID) FROM forums_posts WHERE forums_posts.TopicID = p.TopicID AND forums_posts.ID<=p.ID) AS PostNum FROM forums_posts AS p WHERE ID=".$ThingID);
			if($DB->record_count() < 1) {
				echo "No post with the reported ID found";
			} else {
				list($PostID,$Body,$TopicID,$PostNum) = $DB->next_record();
				echo "<a href='forums.php?action=viewthread&amp;threadid=".$TopicID."&post=".$PostNum."#post".$PostID."'>POST</a>";
			}
			break;
		case "requests_comment" :
			$DB->query("SELECT rc.RequestID, rc.Body, (SELECT COUNT(ID) FROM requests_comments WHERE ID <= ".$ThingID." AND requests_comments.RequestID = rc.RequestID) AS CommentNum FROM requests_comments AS rc WHERE ID=".$ThingID);
			if($DB->record_count() < 1) {
				echo "No comment with the reported ID found";
			} else {
				list($RequestID, $Body, $PostNum) = $DB->next_record();
				$PageNum = ceil($PostNum / TORRENT_COMMENTS_PER_PAGE);
				echo "<a href='requests.php?action=view&amp;id=".$RequestID."&page=".$PageNum."#post".$ThingID."'>COMMENT</a>";
			}
			break;
		case "torrents_comment" :
			$DB->query("SELECT tc.GroupID, tc.Body, (SELECT COUNT(ID) FROM torrents_comments WHERE ID <= ".$ThingID." AND torrents_comments.GroupID = tc.GroupID) AS CommentNum FROM torrents_comments AS tc WHERE ID=".$ThingID);
			if($DB->record_count() < 1) {
				echo "No comment with the reported ID found";
			} else {
				list($GroupID, $Body, $PostNum) = $DB->next_record();
				$PageNum = ceil($PostNum / TORRENT_COMMENTS_PER_PAGE);
				echo "<a href='torrents.php?id=".$GroupID."&page=".$PageNum."#post".$ThingID."'>COMMENT</a>";
			}
			break;
		case "collages_comment" :
			$DB->query("SELECT cc.CollageID, cc.Body, (SELECT COUNT(ID) FROM collages_comments WHERE ID <= ".$ThingID." AND collages_comments.CollageID = cc.CollageID) AS CommentNum FROM collages_comments AS cc WHERE ID=".$ThingID);
			if($DB->record_count() < 1) {
				echo "No comment with the reported ID found";
			} else {
				list($CollageID, $Body, $PostNum) = $DB->next_record();
				$PerPage = POSTS_PER_PAGE;
				$PageNum = ceil($PostNum / $PerPage);
				echo "<a href='collage.php?action=comments&amp;collageid=".$CollageID."&page=".$PageNum."#post".$ThingID."'>COMMENT</a>";
			}
			break;
	}
?>
				</strong>
			</td>
		</tr>
		<tr>
			<td colspan="2"><?=$Text->full_format($Reason)?></td>
		</tr>
<? if($Status != "Resolved") { ?>
		<tr>
			<td class="center" colspan="2">
				<input type="submit" name="submit" value="Resolved" />
			</td>
		</tr>
<? } ?>
	</table>
</form>
</div>
<br />
<?
	$DB->set_query_id($Reports);
}
?>
</div>
<div class="linkbox">
<?
	echo $Pages;
?>
</div>
<?
show_footer();
?>
