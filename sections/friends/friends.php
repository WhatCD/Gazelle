 <?
/************************************************************************
//------------// Main friends page //----------------------------------//
This page lists a user's friends. 

There's no real point in caching this page. I doubt users load it that 
much.
************************************************************************/

// Number of users per page 
define('FRIENDS_PER_PAGE', '20');



show_header('Friends');
 

$UserID = $LoggedUser['ID'];


list($Page,$Limit) = page_limit(FRIENDS_PER_PAGE);

// Main query
$DB->query("SELECT 
	SQL_CALC_FOUND_ROWS
	f.FriendID,
	f.Comment,
	m.Username,
	m.Uploaded,
	m.Downloaded,
	m.PermissionID,
	m.Enabled,
	m.Paranoia,
	i.Donor,
	i.Warned,
	m.Title,
	m.LastAccess,
	i.Avatar
	FROM friends AS f
	JOIN users_main AS m ON f.FriendID=m.ID
	JOIN users_info AS i ON f.FriendID=i.UserID
	WHERE f.UserID='$UserID'
	ORDER BY Username LIMIT $Limit");
$Friends = $DB->to_array(false, MYSQLI_BOTH, array(7));

// Number of results (for pagination)
$DB->query('SELECT FOUND_ROWS()');
list($Results) = $DB->next_record();

// Start printing stuff
?>
<div class="thin">
	<h2>Friends list</h2>
	<div class="linkbox">
<?
// Pagination
$Pages=get_pages($Page,$Results,FRIENDS_PER_PAGE,9);
echo $Pages;
?>
	</div>
	<div class="box pad">
<?
if($Results == 0) {
	echo '<p>You have no friends! :(</p>';
}
// Start printing out friends
foreach($Friends as $Friend) {
	list($FriendID, $Comment, $Username, $Uploaded, $Downloaded, $Class, $Enabled, $Paranoia, $Donor, $Warned, $Title, $LastAccess, $Avatar) = $Friend;
?>
<form action="friends.php" method="post">
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<table class="friends_table vertical_margin">
		<tr>
			<td class="colhead" colspan="3">
				<span style="float:left;"><?=format_username($FriendID, $Username, $Donor, $Warned, $Enabled == 2 ? false : true, $Class)?>
<?	if(check_paranoia('ratio', $Paranoia, $Class, $FriendID)) { ?>
				&nbsp;Ratio: <strong><?=ratio($Uploaded, $Downloaded)?></strong>
<?	} ?>
<?	if(check_paranoia('uploaded', $Paranoia, $Class, $FriendID)) { ?>
				&nbsp;Up: <strong><?=get_size($Uploaded)?></strong>
<?	} ?>
<?	if(check_paranoia('downloaded', $Paranoia, $Class, $FriendID)) { ?>
				&nbsp;Down: <strong><?=get_size($Downloaded)?></strong>
<?	} ?>
				</span>
<?	if(check_paranoia('lastseen', $Paranoia, $Class, $FriendID)) { ?>
				<span style="float:right;"><?=time_diff($LastAccess)?></span>
<?	} ?>
			</td>
		</tr>
		<tr>
			<td width="50px" valign="top">
<?
	if(empty($HeavyInfo['DisableAvatars'])) {
		if(!empty($Avatar)) {
			if(check_perms('site_proxy_images')) {
				$Avatar = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?c=1&i='.urlencode($Avatar);
			}
	?> 
					<img src="<?=$Avatar?>" alt="<?=$Username?>'s avatar" width="50px" />
	<?	} else { ?> 
					<img src="<?=STATIC_SERVER?>common/avatars/default.png" width="50px" alt="Default avatar" />
	<?	} 
	}?> 
			</td>
			<td valign="top">
					<input type="hidden" name="friendid" value="<?=$FriendID?>" />

					<textarea name="comment" rows="4" cols="80"><?=$Comment?></textarea>
				</td>
				<td class="left" valign="top">
					<input type="submit" name="action" value="Update" /><br />
					<input type="submit" name="action" value="Defriend" /><br />
					<input type="submit" name="action" value="Contact" /><br />

			</td>
		</tr>
	</table>
</form>
<?
} // while

// close <div class="box pad">
?>
	</div>
	<div class="linkbox">
		<?=$Pages?>
	</div>
<? // close <div class="thin">  ?>
</div>
<?
show_footer();
?>
