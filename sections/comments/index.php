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
	$Header = 'Torrent comments left by '.($Self?'you':format_username($UserID, $Username)).'';
	if($Self) $OtherLink = '<a href="comments.php?action=my_torrents">Display comments left on your uploads</a>';
}

$Comments = $DB->query("SELECT
	SQL_CALC_FOUND_ROWS
	m.ID,
	m.Username,
	m.PermissionID,
	m.Enabled,
	
	i.Avatar,
	i.Donor,
	i.Warned,
	
	t.ID,
	t.GroupID,
	
	tg.Name,
	
	tc.ID,
	tc.Body,
	tc.AddedTime,
	tc.EditedTime,
	
	em.ID as EditorID,
	em.Username as EditorUsername
	
	FROM torrents as t
	JOIN torrents_comments as tc ON tc.GroupID = t.GroupID
	JOIN users_main as m ON tc.AuthorID = m.ID
	JOIN users_info as i ON i.UserID = m.ID
	JOIN torrents_group as tg ON t.GroupID = tg.ID
	LEFT JOIN users_main as em ON em.ID = tc.EditedUserID
	
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

$DB->set_query_id($Comments);
show_header($Title,'bbcode');

?><div class="thin">
	<h2>
		<?=$Header?>
	</h2>
	
	<div class="linkbox">
	<?=$OtherLink?>
	<br /><br />
	<?=$Pages?>
	</div>
<?php

while(list($UserID, $Username, $Class, $Enabled, $Avatar, $Donor, $Warned, $TorrentID, $GroupID, $Title, $PostID, $Body, $AddedTime, $EditedTime, $EditorID, $EditorUsername) = $DB->next_record()) {
	?>
	<table class='forum_post box vertical_margin<?=$HeavyInfo['DisableAvatars'] ? ' noavatar' : ''?>' id="post<?=$PostID?>">
		<tr class='colhead_dark'>
			<td  colspan="2">
				<span style="float:left;"><a href='torrents.php?id=<?=$GroupID?>&amp;postid=<?=$PostID?>#post<?=$PostID?>'>#<?=$PostID?></a>
					by <strong><?=format_username($UserID, $Username, $Donor, $Warned, $Enabled, $Class)?></strong> <?=time_diff($AddedTime) ?>
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
				if($Avatar){ 
?>
				<img src='<?=$Avatar?>' width='150' alt="<?=$Username ?>'s avatar" />
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
				<?=format_username($EditorID, $EditorUsername) ?> <?=time_diff($EditedTime)?>
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
