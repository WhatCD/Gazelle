<?php

/**
 * Prints a table that contains a comment on something
 *
 * @param $UserID UserID of the guy/gal who posted the comment
 * @param $PostID The post number
 * @param $postheader the header used in the post.
 * @param $permalink the link to the post elsewhere on the site (torrents.php)
 * @param $Body the post body
 * @param $EditorID the guy who last edited the post
 * @param $EditedTime time last edited
 * @returns void, prints output
 */
function comment_body($UserID, $PostID, $postheader, $permalink, $Body, $EditorID, $AddedTime, $EditedTime) {
	global $Text,$HeavyInfo;
	$UserInfo = Users::user_info($UserID);
	$postheader = 'by <strong>' . Users::format_username($UserID, true, true, true, true, false) . '</strong> '
	. time_diff($AddedTime) . $postheader;

?>
	<table class="forum_post box vertical_margin<?=$noavatar ? ' noavatar' : '' ?>" id="post<?=$PostID?>">
		<colgroup>
<?	if (empty($UserInfo['DisableAvatars'])) { ?>
			<col class="col_avatar" />
<? 	} ?>
			<col class="col_post_body" />
		</colgroup>
		<tr class="colhead_dark">
			<td colspan="<?=empty($UserInfo['DisableAvatars']) ? 2 : 1 ?>">
				<span style="float: left;"><a href="<?=$permalink ?>">#<?=$PostID?></a>
					<?=$postheader ?>
				</span>
			</td>
		</tr>
		<tr>
<?	if (empty($HeavyInfo['DisableAvatars'])) { ?>
			<td class="avatar" valign="top">
<?		if ($UserInfo['Avatar']) { ?>
				<img src="<?=$UserInfo['Avatar']?>" width="150" alt="<?=$UserInfo['Username']?>'s avatar" />
<?		} else { ?>
				<img src="<?=STATIC_SERVER?>common/avatars/default.png" width="150" alt="Default avatar" />
<?		} ?>
			</td>
<?	} ?>
			<td class="body" valign="top">
				<?=$Text->full_format($Body) ?>
<?	if ($EditorID) { ?>
				<br /><br />
				Last edited by
				<?=Users::format_username($EditorID, false, false, false) ?> <?=time_diff($EditedTime)?>
<?	} ?>
			</td>
		</tr>
	</table>
<? }
