<?
enforce_login();

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

if(!empty($_REQUEST['action'])) {
	if($_REQUEST['action'] == 'my_torrents') {
		$MyTorrents = true;
	}
} else {
	$MyTorrents = false;
}

if(isset($_GET['id'])) {
	$UserID = $_GET['id'];
	if(!is_number($UserID)) {
		error(404);
	}
	$UserInfo = user_info($UserID);
	$Username = $UserInfo['Username'];
	if($LoggedUser['ID'] == $UserID) {
		$Self = true;
	} else {
		$Self = false;
	}
	$Perms = get_permissions($UserInfo['PermissionID']);
	$UserClass = $Perms['Class'];
	if (!check_paranoia('torrentcomments', $UserInfo['Paranoia'], $UserClass, $UserID)) { error(403); }
} else {
	$UserID = $LoggedUser['ID'];
	$Username = $LoggedUser['Username'];
	$Self = true;
}

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

list($Page,$Limit) = page_limit($PerPage);
$OtherLink = '';

if($MyTorrents) {
	$Conditions = "WHERE t.UserID = $UserID AND tc.AuthorID != t.UserID AND tc.AddedTime > t.Time";
	$Title = 'Comments left on your torrents';
	$Header = 'Comments left on your uploads';
	if($Self) $OtherLink = '<a href="comments.php">Display comments you\'ve made</a>';
}
else {
	$Conditions = "WHERE tc.AuthorID = $UserID";
	$Title = 'Comments made by '.($Self?'you':$Username);
	$Header = 'Torrent comments left by '.($Self?'you':format_username($UserID, false, false, false)).'';
	if($Self) $OtherLink = '<a href="comments.php?action=my_torrents">Display comments left on your uploads</a>';
}

$Comments = $DB->query("SELECT
	SQL_CALC_FOUND_ROWS
	tc.AuthorID,
	t.ID,
	t.GroupID,
	tg.Name,
	tc.ID,
	tc.Body,
	tc.AddedTime,
	tc.EditedTime,
	tc.EditedUserID as EditorID
	
	FROM torrents as t
	JOIN torrents_comments as tc ON tc.GroupID = t.GroupID
	JOIN torrents_group as tg ON t.GroupID = tg.ID
	
	$Conditions
	
	GROUP BY tc.ID
	
	ORDER BY tc.AddedTime DESC
	
	LIMIT $Limit;
");

$DB->query("SELECT FOUND_ROWS()");
list($Results) = $DB->next_record();
$Pages=get_pages($Page,$Results,$PerPage, 11);

$DB->set_query_id($Comments);
$GroupIDs = $DB->collect('GroupID');

$Artists = get_artists($GroupIDs);

show_header($Title,'bbcode');
$DB->set_query_id($Comments);

?><div class="thin">
	<div class="header">
		<h2><?=$Header?></h2>
<? if ($OtherLink !== '') { ?>
		<div class="linkbox">
			<?=$OtherLink?>
		</div>
<? } ?>
	</div>
	<div class="linkbox">
		<?=$Pages?>
	</div>
<?

while(list($UserID, $TorrentID, $GroupID, $Title, $PostID, $Body, $AddedTime, $EditedTime, $EditorID) = $DB->next_record()) {
	$UserInfo = user_info($UserID);
	?>
	<table class='forum_post box vertical_margin<?=$HeavyInfo['DisableAvatars'] ? ' noavatar' : ''?>' id="post<?=$PostID?>">
		<tr class='colhead_dark'>
			<td  colspan="2">
				<span style="float:left;"><a href='torrents.php?id=<?=$GroupID?>&amp;postid=<?=$PostID?>#post<?=$PostID?>'>#<?=$PostID?></a>
					by <strong><?=format_username($UserID, true, true, true, true, false)?></strong> <?=time_diff($AddedTime) ?>
					on <?=display_artists($Artists[$GroupID])?><a href="torrents.php?id=<?=$GroupID?>"><?=$Title?></a>
				</span>
			</td>
		</tr>
		<tr>
<?
if(empty($HeavyInfo['DisableAvatars'])) {
?>
			<td class='avatar' valign="top">
<?
				if($UserInfo['Avatar']){ 
?>
				<img src='<?=$UserInfo['Avatar']?>' width='150' alt="<?=$UserInfo['Username']?>'s avatar" />
<?
				} else { ?>
				<img src="<?=STATIC_SERVER?>common/avatars/default.png" width="150" alt="Default avatar" />
<?
				} 
?>
			</td>
<?
}
?>
			<td class='body' valign="top">
				<?=$Text->full_format($Body) ?> 
<?
				if($EditorID){ 
?>
				<br /><br />
				Last edited by
				<?=format_username($EditorID, false, false, false) ?> <?=time_diff($EditedTime)?>
<?
				}
?>

			</td>
		</tr>
	</table>
<?
}

?>
	<div class="linkbox">
<?
echo $Pages;
?>
	</div>
</div>
<?

show_footer();

?>
