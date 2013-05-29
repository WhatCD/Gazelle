<?
/************************************************************************
 ************************************************************************/
if (!check_perms('admin_reports') && !check_perms('project_team') && !check_perms('site_moderate_forums')) {
	error(404);
}

// Number of reports per page
define('REPORTS_PER_PAGE', '10');
include(SERVER_ROOT . '/classes/text.class.php');
$Text = NEW TEXT;

list($Page, $Limit) = Format::page_limit(REPORTS_PER_PAGE);

include(SERVER_ROOT . '/sections/reports/array.php');

// Header
View::show_header('Reports', 'bbcode,reports');

if ($_GET['id'] && is_number($_GET['id'])) {
	$View = "Single report";
	$Where = "r.ID = " . $_GET['id'];
} else if (empty($_GET['view'])) {
	$View = "New";
	$Where = "Status='New'";
} else {
	$View = $_GET['view'];
	switch ($_GET['view']) {
		case 'old' :
			$Where = "Status='Resolved'";
			break;
		default :
			error(404);
			break;
	}
}

if (!check_perms('admin_reports')) {
	if (check_perms('project_team')) {
		$Where .= " AND Type = 'request_update'";
	}
	if (check_perms('site_moderate_forums')) {
		$Where .= " AND Type IN('collages_comment', 'post', 'requests_comment', 'thread', 'torrents_comment', 'torrent_comments', 'artist_comment')";
	}

}

$Reports = $DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		r.ID,
		r.UserID,
		um.Username,
		r.ThingID,
		r.Type,
		r.ReportedTime,
		r.Reason,
		r.Status,
		r.ClaimerID,
		r.Notes,
		r.ResolverID
	FROM reports AS r
		JOIN users_main AS um ON r.UserID=um.ID
	WHERE $Where
	ORDER BY ReportedTime DESC
	LIMIT $Limit");

// Number of results (for pagination)
$DB->query('SELECT FOUND_ROWS()');
list($Results) = $DB->next_record();

// Done with the number of results. Move $DB back to the result set for the reports
$DB->set_query_id($Reports);

// Start printing stuff
?>
<div class="thin">
	<div class="header">
		<h2>Active Reports</h2>
		<div class="linkbox">
			<a href="reports.php">New</a> |
			<a href="reports.php?view=old">Old</a> |
			<a href="reports.php?action=stats">Stats</a>
		</div>
	</div>
	<div class="linkbox">
<?
// pagination
		$Pages = Format::get_pages($Page, $Results, REPORTS_PER_PAGE, 11);
		echo $Pages;
		?>
	</div>
<?
	while (list($ReportID, $SnitchID, $SnitchName, $ThingID, $Short, $ReportedTime, $Reason, $Status, $ClaimerID, $Notes, $ResolverID) = $DB->next_record()) {
		$Type = $Types[$Short];
		$Reference = "reports.php?id=" . $ReportID . "#report" . $ReportID;
		?>
		<div id="report_<?=$ReportID?>">
			<table cellpadding="5" id="report_<?=$ReportID?>">
				<tr>
					<td><strong><a href="<?=$Reference?>">Report #<?=$ReportID?></a></strong></td>
					<td>
						<strong><?=$Type['title']?></strong> was reported by <a href="user.php?id=<?=$SnitchID?>"><?=$SnitchName?></a> <?=time_diff($ReportedTime)?>
						<a href="reports.php?action=compose&amp;to=<?=$SnitchID?>&amp;reportid=<?=$ReportID?>&amp;type=<?=$Short?>&amp;thingid=<?=$ThingID?>" class="brackets">Contact</a>
					</td>
				</tr>
				<tr>
					<td class="center" colspan="2">
						<strong>
<?							switch ($Short) {
								case "user" :
									$DB->query("SELECT Username FROM users_main WHERE ID=" . $ThingID);
									if ($DB->record_count() < 1) {
										echo 'No user with the reported ID found';
									} else {
										list($Username) = $DB->next_record();
										echo "<a href=\"user.php?id=$ThingID\">" . display_str($Username) . '</a>';
									}
									break;
								case "request" :
								case "request_update" :
									$DB->query("SELECT Title FROM requests WHERE ID=" . $ThingID);
									if ($DB->record_count() < 1) {
										echo 'No request with the reported ID found';
									} else {
										list($Name) = $DB->next_record();
										echo "<a href=\"requests.php?action=view&amp;id=$ThingID\">" . display_str($Name) . '</a>';
									}
									break;
								case "collage" :
									$DB->query("SELECT Name FROM collages WHERE ID=" . $ThingID);
									if ($DB->record_count() < 1) {
										echo 'No collage with the reported ID found';
									} else {
										list($Name) = $DB->next_record();
										echo "<a href=\"collages.php?id=$ThingID\">" . display_str($Name) . '</a>';
									}
									break;
								case "thread" :
									$DB->query("SELECT Title FROM forums_topics WHERE ID=" . $ThingID);
									if ($DB->record_count() < 1) {
										echo 'No forum thread with the reported ID found';
									} else {
										list($Title) = $DB->next_record();
										echo "<a href=\"forums.php?action=viewthread&amp;threadid=$ThingID\">" . display_str($Title) . '</a>';
									}
									break;
								case "post" :
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
										echo 'No forum post with the reported ID found';
									} else {
										list($PostID, $Body, $TopicID, $PostNum) = $DB->next_record();
										echo "<a href=\"forums.php?action=viewthread&amp;threadid=" . $TopicID . "&amp;post=" . $PostNum . "#post" . $PostID . "\">FORUM POST</a>";
									}
									break;
								case "requests_comment" :
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
										echo 'No request comment with the reported ID found';
									} else {
										list($RequestID, $Body, $PostNum) = $DB->next_record();
										$PageNum = ceil($PostNum / TORRENT_COMMENTS_PER_PAGE);
										echo "<a href=\"requests.php?action=view&amp;id=" . $RequestID . "&amp;page=" . $PageNum . "#post" . $ThingID . "\">REQUEST COMMENT</a>";
									}
									break;
								case "torrents_comment" :
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
										echo 'No torrent comment with the reported ID found';
									} else {
										list($GroupID, $Body, $PostNum) = $DB->next_record();
										$PageNum = ceil($PostNum / TORRENT_COMMENTS_PER_PAGE);
										echo "<a href=\"torrents.php?id=" . $GroupID . "&amp;page=" . $PageNum . "#post" . $ThingID . "\">TORRENT COMMENT</a>";
									}
									break;
								case "artist_comment" :
									$DB->query("
										SELECT
											ac.ArtistID,
											ac.Body,
											(	SELECT COUNT(ID)
												FROM artist_comments
												WHERE ID <= $ThingID
													AND artist_comments.ArtistID = ac.ArtistID
											) AS CommentNum
										FROM artist_comments AS ac
										WHERE ID=$ThingID");
									if ($DB->record_count() < 1) {
										echo 'No comment with the reported ID found';
									} else {
										list($ArtistID, $Body, $PostNum) = $DB->next_record();
										$PageNum = ceil($PostNum / TORRENT_COMMENTS_PER_PAGE);
										echo "<a href=\"artist.php?id=" . $ArtistID . "&amp;page=" . $PageNum . "#post" . $ThingID . "\">ARTIST COMMENT</a>";
									}
									break;

								case "collages_comment" :
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
										echo 'No collage comment with the reported ID found';
									} else {
										list($CollageID, $Body, $PostNum) = $DB->next_record();
										$PerPage = POSTS_PER_PAGE;
										$PageNum = ceil($PostNum / $PerPage);
										echo "<a href=\"collage.php?action=comments&amp;collageid=" . $CollageID . "&amp;page=" . $PageNum . "#post" . $ThingID . "\">COLLAGE COMMENT</a>";
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
				<tr>
					<td colspan="2">
<?					if ($ClaimerID == $LoggedUser['ID']) { ?>
						<span id="claimed_<?=$ReportID?>">Claimed by <?=Users::format_username($ClaimerID, false, false, false, false)?> <a href="#" onclick="unClaim(<?=$ReportID?>); return false;" class="brackets">Unclaim</a></span>
<?					} else if ($ClaimerID) { ?>
						<span id="claimed_<?=$ReportID?>">Claimed by <?=Users::format_username($ClaimerID, false, false, false, false)?></span>
<?					} else { ?>
						<a href="#" id="claim_<?=$ReportID?>" onclick="claim(<?=$ReportID?>); return false;" class="brackets">Claim</a>
<?					} ?>
						&nbsp;&nbsp;
						<a href="#" onclick="toggleNotes(<?=$ReportID?>); return false;" class="brackets">Toggle notes</a>

						<div id="notes_div_<?=$ReportID?>" style="display: <?=empty($Notes) ? "none" : "block"?>;">
							<textarea cols="50" rows="3" id="notes_<?=$ReportID?>"><?=$Notes?></textarea>
							<br />
							<input type="submit" onclick="saveNotes(<?=$ReportID?>)" value="Save" />
						</div>
					</td>
				</tr>
<?			if ($Status != 'Resolved') { ?>
				<tr>
					<td class="center" colspan="2">
						<form id="report_form_<?=$ReportID?>" action="">
							<input type="hidden" name="reportid" value="<?=$ReportID?>" />
							<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
							<input type="submit" onclick="return resolve(<?=$ReportID?>, <?=(($ClaimerID == $LoggedUser['ID'] || !$ClaimerID) ? 'true' : 'false')?>)" name="submit" value="Resolve" />
						</form>
					</td>
				</tr>
<?			} else {
				$ResolverInfo = Users::user_info($ResolverID);
			?>
				<tr>
					<td colspan="2">
						Resolved by <a href="users.php?id=<?=$ResolverID?>"><?=$ResolverInfo['Username']?></a>
					</td>
				</tr>
<?			} ?>
			</table>
		</div>
		<br />
<?
		$DB->set_query_id($Reports);
	}
	?>
	<div class="linkbox">
<?
		echo $Pages;
		?>
	</div>
</div>
<?
View::show_footer();
?>
