<?php
/**
 * This version of #quickpostform is used in all subsections
 * Instead of modifying multiple places, just modify this one.
 *
 * To include it in a section use this example.

		View::parse('generic/reply/quickreply.php', array(
			'InputTitle' => 'Post reply',
			'InputName' => 'thread',
			'InputID' => $ThreadID,
			'ForumID' => $ForumID,
			'TextareaCols' => 90
		));

 * Note that InputName and InputID are the only required variables
 * They're used to construct the $_POST.
 *
 * Eg
 * <input name="thread" value="123" />
 * <input name="groupid" value="321" />
 *
 * Globals are required as this template is included within a
 * function scope.
 *
 * To add a "Subscribe" box for non-forum pages (like artist/collage/...
 * comments), add a key 'SubscribeBox' to the array passed to View::parse.
 * Example:

		View::parse('generic/reply/quickreply.php', array(
			'InputTitle' => 'Post comment',
			'InputName' => 'groupid',
			'InputID' => $GroupID,
			'TextareaCols' => 65,
			'SubscribeBox' => true
		));
 */
	global $HeavyInfo, $UserSubscriptions, $ThreadInfo, $ForumsDoublePost, $Document;

	if (G::$LoggedUser['DisablePosting']) {
		return;
	}
	if (!isset($TextareaCols)) {
		$TextareaCols = 70;
	}
	if (!isset($TextareaRows)) {
		$TextareaRows = 8;
	}
	if (!isset($InputAction)) {
		$InputAction = 'reply';
	}
	if (!isset($InputTitle)) {
		$InputTitle = 'Post comment';
	}
	if (!isset($Action)) {
		$Action = '';
	}

	// TODO: Remove inline styles

	// Old to do?
	// TODO: Preview, come up with a standard, make it look like post or just a
	// block of formatted BBcode, but decide and write some proper XHTML


	$ReplyText = new TEXTAREA_PREVIEW('body', 'quickpost', '',
			$TextareaCols, $TextareaRows, false, false, true, array(
				'tabindex="1"',
				'onkeyup="resize(\'quickpost\')"'
			));
?>

			<br />
			<div id="reply_box">
				<h3><?=$InputTitle?></h3>
				<div class="box pad">
					<table class="forum_post box vertical_margin hidden preview_wrap" id="preview_wrap_<?=$ReplyText->getID()?>">
						<colgroup>
<?	if (Users::has_avatars_enabled()) { ?>
							<col class="col_avatar" />
<?	} ?>
							<col class="col_post_body" />
						</colgroup>
						<tr class="colhead_dark">
							<td colspan="<?=(Users::has_avatars_enabled() ? 2 : 1)?>">
								<div style="float: left;"><a href="#quickreplypreview">#XXXXXX</a>
									by <strong><?=Users::format_username(G::$LoggedUser['ID'], true, true, true, true)?></strong> Just now
								</div>
								<div style="float: right;">
									<a href="#quickreplypreview" class="brackets">Report</a>
									&nbsp;
									<a href="#">&uarr;</a>
								</div>
							</td>
						</tr>
						<tr>
<?	if (Users::has_avatars_enabled()) { ?>
							<td class="avatar" valign="top">
								<?=Users::show_avatar(G::$LoggedUser['Avatar'], G::$LoggedUser['ID'], G::$LoggedUser['Username'], $HeavyInfo['DisableAvatars'])?>
							</td>
<?	} ?>
							<td class="body" valign="top">
								<div id="contentpreview" style="text-align: left;">
									<div id="preview_<?=$ReplyText->getID()?>"></div>
								</div>
							</td>
						</tr>
					</table>
					<form class="send_form center" name="reply" id="quickpostform" action="<?=$Action?>" method="post"<? if (!check_perms('users_mod')) { ?> onsubmit="quickpostform.submit_button.disabled = true;"<? } ?>>
						<input type="hidden" name="action" value="<?=$InputAction?>" />
						<input type="hidden" name="auth" value="<?=G::$LoggedUser['AuthKey']?>" />
						<input type="hidden" name="<?=$InputName?>" value="<?=$InputID?>" />
						<div id="quickreplytext">
<?
							echo $ReplyText->getBuffer();
?>
							<br />
						</div>
						<div class="preview_submit">
<?
	if (isset($SubscribeBox) && !isset($ForumID) && Subscriptions::has_subscribed_comments($Document, $InputID) === false) {
?>
							<input id="subscribebox" type="checkbox" name="subscribe"<?=!empty($HeavyInfo['AutoSubscribe']) ? ' checked="checked"' : ''?> tabindex="2" />
							<label for="subscribebox">Subscribe</label>
<?
	}
	// Forum thread logic
	// This might use some more abstraction
	if (isset($ForumID)) {
		if (!Subscriptions::has_subscribed($InputID)) {
?>
							<input id="subscribebox" type="checkbox" name="subscribe"<?=!empty($HeavyInfo['AutoSubscribe']) ? ' checked="checked"' : ''?> tabindex="2" />
							<label for="subscribebox">Subscribe</label>
<?
		}
		if ($ThreadInfo['LastPostAuthorID'] == G::$LoggedUser['ID']
			&& (check_perms('site_forums_double_post')
				|| in_array($ForumID, $ForumsDoublePost))
		) {
?>
							<input id="mergebox" type="checkbox" name="merge" tabindex="2" />
							<label for="mergebox">Merge</label>
<?
		}
		if (!G::$LoggedUser['DisableAutoSave']) {
?>
							<script type="application/javascript">
								var storedTempTextarea = new StoreText('quickpost', 'quickpostform', <?=$InputID?>);
							</script>
<?
		}
	}
?>
							<input type="button" value="Preview" class="hidden button_preview_<?=$ReplyText->getID()?>" tabindex="1" />
							<input type="submit" value="Post reply" id="submit_button" tabindex="1" />
						</div>
					</form>
				</div>
			</div>
