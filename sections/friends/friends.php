<?php
/************************************************************************
//------------// Main friends page //----------------------------------//
This page lists a user's friends.

There's no real point in caching this page. I doubt users load it that
much.
************************************************************************/

// Number of users per page
define('FRIENDS_PER_PAGE', '20');
include_once(SERVER_ROOT.'/classes/paranoia.class.php');



View::show_header('Friends','comments');


$UserID = $LoggedUser['ID'];


list($Page, $Limit) = Format::page_limit(FRIENDS_PER_PAGE);

// Main query
$DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		f.FriendID,
		f.Comment,
		m.Username,
		m.Uploaded,
		m.Downloaded,
		m.PermissionID,
		m.Paranoia,
		m.LastAccess,
		i.Avatar
	FROM friends AS f
		JOIN users_main AS m ON f.FriendID = m.ID
		JOIN users_info AS i ON f.FriendID = i.UserID
	WHERE f.UserID = '$UserID'
	ORDER BY Username
	LIMIT $Limit");
$Friends = $DB->to_array(false, MYSQLI_BOTH, array(6, 'Paranoia'));

// Number of results (for pagination)
$DB->query('SELECT FOUND_ROWS()');
list($Results) = $DB->next_record();

// Start printing stuff
?>
<div class="thin">
	<div class="header">
		<h2>Friends List</h2>
	</div>
	<div class="linkbox">
<?
// Pagination
$Pages = Format::get_pages($Page, $Results, FRIENDS_PER_PAGE, 9);
echo $Pages;
?>
	</div>
	<div class="box pad">
<?
if ($Results == 0) {
	echo '<p>You have no friends! :(</p>';
}
// Start printing out friends
foreach ($Friends as $Friend) {
	list($FriendID, $Comment, $Username, $Uploaded, $Downloaded, $Class, $Paranoia, $LastAccess, $Avatar) = $Friend;
?>
<form class="manage_form" name="friends" action="friends.php" method="post">
	<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
	<table class="friends_table vertical_margin">
		<tr class="colhead">
			<td colspan="<?=(Users::has_avatars_enabled() ? 3 : 2)?>">
				<span style="float: left;"><?=Users::format_username($FriendID, true, true, true, true)?>
<?	if (check_paranoia('ratio', $Paranoia, $Class, $FriendID)) { ?>
				&nbsp;Ratio: <strong><?=Format::get_ratio_html($Uploaded, $Downloaded)?></strong>
<?
	}
	if (check_paranoia('uploaded', $Paranoia, $Class, $FriendID)) {
?>
				&nbsp;Up: <strong><?=Format::get_size($Uploaded)?></strong>
<?
	}
	if (check_paranoia('downloaded', $Paranoia, $Class, $FriendID)) {
?>
				&nbsp;Down: <strong><?=Format::get_size($Downloaded)?></strong>
<?	} ?>
				</span>
<?	if (check_paranoia('lastseen', $Paranoia, $Class, $FriendID)) { ?>
				<span style="float: right;"><?=time_diff($LastAccess)?></span>
<?	} ?>
			</td>
		</tr>
		<tr>
<?	if (Users::has_avatars_enabled()) { ?>
			<td class="col_avatar avatar" valign="top">
				<?=Users::show_avatar($Avatar, $FriendID, $Username, $HeavyInfo['DisableAvatars'])?>
			</td>
<?	} ?>
			<td valign="top">
				<input type="hidden" name="friendid" value="<?=$FriendID?>" />

				<textarea name="comment" rows="4" cols="65"><?=$Comment?></textarea>
			</td>
			<td class="left" valign="top">
				<input type="submit" name="action" value="Update" /><br />
				<input type="submit" name="action" value="Remove friend" /><br />
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
<? // close <div class="thin"> ?>
</div>
<?
View::show_footer();
?>
