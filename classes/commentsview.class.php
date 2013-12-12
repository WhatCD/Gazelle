<?
class CommentsView {
	/**
	 * Render a thread of comments
	 * @param array $Thread An array as returned by Comments::load
	 * @param int $LastRead PostID of the last read post
	 * @param string $Baselink Link to the site these comments are on
	 */
	public static function render_comments($Thread, $LastRead, $Baselink) {
		foreach ($Thread as $Post) {
			list($PostID, $AuthorID, $AddedTime, $CommentBody, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
			self::render_comment($AuthorID, $PostID, $CommentBody, $AddedTime, $EditedUserID, $EditedTime, $Baselink . "&amp;postid=$PostID#post$PostID", ($PostID > $LastRead));
		}
	}

	/**
	 * Render one comment
	 * @param int $AuthorID
	 * @param int $PostID
	 * @param string $Body
	 * @param string $AddedTime
	 * @param int $EditedUserID
	 * @param string $EditedTime
	 * @param string $Link The link to the post elsewhere on the site
	 * @param string $Header The header used in the post
	 * @param bool $Tools Whether or not to show [Edit], [Report] etc.
	 * @todo Find a better way to pass the page (artist, collages, requests, torrents) to this function than extracting it from $Link
	 */
	function render_comment($AuthorID, $PostID, $Body, $AddedTime, $EditedUserID, $EditedTime, $Link, $Unread = false, $Header = '', $Tools = true) {
		$UserInfo = Users::user_info($AuthorID);
		$Header = '<strong>' . Users::format_username($AuthorID, true, true, true, true, false) . '</strong> ' . time_diff($AddedTime) . $Header;
?>
		<table class="forum_post box vertical_margin<?=(!Users::has_avatars_enabled() ? ' noavatar' : '') . ($Unread ? ' forum_unread' : '')?>" id="post<?=$PostID?>">
			<colgroup>
<?		if (Users::has_avatars_enabled()) { ?>
				<col class="col_avatar" />
<?		} ?>
				<col class="col_post_body" />
			</colgroup>
			<tr class="colhead_dark">
				<td colspan="<?=(Users::has_avatars_enabled() ? 2 : 1)?>">
					<div style="float: left;"><a class="post_id" href="<?=$Link?>">#<?=$PostID?></a>
						<?=$Header?>
<?		if ($Tools) { ?>
						- <a href="#quickpost" onclick="Quote('<?=$PostID?>','<?=$UserInfo['Username']?>', true);" class="brackets">Quote</a>
<?			if ($AuthorID == G::$LoggedUser['ID'] || check_perms('site_moderate_forums')) { ?>
						- <a href="#post<?=$PostID?>" onclick="Edit_Form('<?=$PostID?>','<?=$Key?>');" class="brackets">Edit</a>
<?			}
			if (check_perms('site_moderate_forums')) { ?>
						- <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');" class="brackets">Delete</a>
<?			} ?>
					</div>
					<div id="bar<?=$PostID?>" style="float: right;">
						<a href="reports.php?action=report&amp;type=comment&amp;id=<?=$PostID?>" class="brackets">Report</a>
<?
			if (check_perms('users_warn') && $AuthorID != G::$LoggedUser['ID'] && G::$LoggedUser['Class'] >= $UserInfo['Class']) {
?>
						<form class="manage_form hidden" name="user" id="warn<?=$PostID?>" action="comments.php" method="post">
							<input type="hidden" name="action" value="warn" />
							<input type="hidden" name="postid" value="<?=$PostID?>" />
						</form>
						- <a href="#" onclick="$('#warn<?=$PostID?>').raw().submit(); return false;" class="brackets">Warn</a>
<?			} ?>
						&nbsp;
						<a href="#">&uarr;</a>
<?		} ?>
					</div>
				</td>
			</tr>
			<tr>
<?		if (Users::has_avatars_enabled()) { ?>
				<td class="avatar" valign="top">
				<?=Users::show_avatar($UserInfo['Avatar'], $AuthorID, $UserInfo['Username'], G::$LoggedUser['DisableAvatars'])?>
				</td>
<?		} ?>
				<td class="body" valign="top">
					<div id="content<?=$PostID?>">
						<?=Text::full_format($Body)?>
<?		if ($EditedUserID) { ?>
						<br />
						<br />
<?			if (check_perms('site_admin_forums')) { ?>
						<a href="#content<?=$PostID?>" onclick="LoadEdit('<?=substr($Link, 0, strcspn($Link, '.'))?>', <?=$PostID?>, 1); return false;">&laquo;</a>
<?			} ?>
						Last edited by
						<?=Users::format_username($EditedUserID, false, false, false) ?> <?=time_diff($EditedTime, 2, true, true)?>
<?		} ?>
					</div>
				</td>
			</tr>
		</table>
<?	}
}
