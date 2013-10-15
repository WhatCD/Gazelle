<?
/*
New post page

This is the page that's loaded if someone wants to make a new topic.

Information to be expected in $_GET:
	forumid: The ID of the forum that it's being posted in

*/

$ForumID = $_GET['forumid'];
if (!is_number($ForumID)) {
	error(404);
}
$Forum = Forums::get_forum_info($ForumID);
if ($Forum === false) {
	error(404);
}


if (!Forums::check_forumperm($ForumID, 'Write') || !Forums::check_forumperm($ForumID, 'Create')) {
	error(403);
}
View::show_header('Forums &gt; '.$Forum['Name'].' &gt; New Topic', 'comments,bbcode,jquery.validate,form_validate');
?>
<div class="thin">
	<h2><a href="forums.php">Forums</a> &gt; <a href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>"><?=$Forum['Name']?></a> &gt; <span id="newthreadtitle">New Topic</span></h2>
	<div class="hidden" id="newthreadpreview">
		<div class="linkbox">
			<div class="center">
				<a href="#" onclick="return false;" class="brackets">Report thread</a>
				<a href="#" onclick="return false;" class="brackets"><?=!empty($HeavyInfo['AutoSubscribe']) ? 'Unsubscribe' : 'Subscribe'?></a>
			</div>
		</div>
<?	if (check_perms('forums_polls_create')) { ?>
		<div class="box thin clear hidden" id="pollpreview">
			<div class="head colhead_dark"><strong>Poll</strong> <a href="#" onclick="$('#threadpoll').gtoggle(); return false;" class="brackets">View</a></div>
			<div class="pad" id="threadpoll">
				<p><strong id="pollquestion"></strong></p>
				<div id="pollanswers"></div>
				<br /><input type="radio" name="vote" id="answer_0" value="0" /> <label for="answer_0">Blank&#8202;&mdash;&#8202;Show the results!</label><br /><br />
				<input type="button" style="float: left;" value="Vote" />
			</div>
		</div>
<?	} ?>
		<table class="forum_post box vertical_margin" style="text-align: left;">
			<colgroup>
<?	if (Users::has_avatars_enabled()) { ?>
				<col class="col_avatar" />
<?	} ?>
				<col class="col_post_body" />
			</colgroup>
			<tr class="colhead_dark">
				<td colspan="<?=Users::has_avatars_enabled() ? 2 : 1 ?>">
					<span style="float: left;"><a href="#newthreadpreview">#XXXXXX</a>
						by <strong><?=Users::format_username($LoggedUser['ID'], true, true, true, true, true)?></strong>
					Just now
					</span>
					<span id="barpreview" style="float: right;">
						<a href="#newthreadpreview" class="brackets">Report</a>
						&nbsp;
						<a href="#">&uarr;</a>
					</span>
				</td>
			</tr>
			<tr>
<?	if (Users::has_avatars_enabled()) { ?>
				<td class="avatar" valign="top">
					<?=Users::show_avatar($LoggedUser['Avatar'], $LoggedUser['ID'], $LoggedUser['Username'], $HeavyInfo['DisableAvatars'])?>
				</td>
<?	} ?>
				<td class="body" valign="top">
					<div id="contentpreview" style="text-align: left;"></div>
				</td>
			</tr>
		</table>
	</div>
	<div class="box pad">
		<form class="create_form" name="forum_thread" action="" id="newthreadform" method="post">
			<input type="hidden" name="action" value="new" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="forum" value="<?=$ForumID?>" />
			<table id="newthreadtext" class="layout">
				<tr>
					<td class="label">Title:</td>
					<td><input id="title" class="required" type="text" name="title" style="width: 98%;" /></td>
				</tr>
				<tr>
					<td class="label">Body:</td>
					<td><textarea id="posttext" class="required" style="width: 98%;" onkeyup="resize('posttext');" name="body" cols="90" rows="8"></textarea></td>
				</tr>
				<tr>
					<td></td>
					<td>
						<input id="subscribebox" type="checkbox" name="subscribe"<?=!empty($HeavyInfo['AutoSubscribe']) ? ' checked="checked"' : ''?> onchange="$('#subscribeboxpreview').raw().checked=this.checked;" />
						<label for="subscribebox">Subscribe to topic</label>
					</td>
				</tr>
<? 
if (check_perms('forums_polls_create')) {
?>
				<script type="text/javascript">//<![CDATA[
				var AnswerCount = 1;

				function AddAnswerField() {
						if (AnswerCount >= 25) {
							return;
						}
						var AnswerField = document.createElement("input");
						AnswerField.type = "text";
						AnswerField.id = "answer_"+AnswerCount;
						AnswerField.className = "required";
						AnswerField.name = "answers[]";
						AnswerField.style.width = "90%";

						var x = $('#answer_block').raw();
						x.appendChild(document.createElement("br"));
						x.appendChild(AnswerField);
						AnswerCount++;
				}

				function RemoveAnswerField() {
						if (AnswerCount == 1) {
							return;
						}
						var x = $('#answer_block').raw();
						for (i = 0; i < 2; i++) {
							x.removeChild(x.lastChild);
						}
						AnswerCount--;
				}
				//]]>
				</script>
				<tr>
					<td colspan="2" class="center">
						<strong>Poll Settings</strong>
						<a href="#" onclick="$('#poll_question, #poll_answers').gtoggle(); return false;" class="brackets">View</a>
					</td>
				</tr>
				<tr id="poll_question" class="hidden">
					<td class="label">Question:</td>
					<td><input type="text" name="question" id="pollquestionfield" class="required" style="width: 98%;" /></td>
				</tr>
				<tr id="poll_answers" class="hidden">
					<td class="label">Answers:</td>
					<td id="answer_block">
						<input type="text" name="answers[]" class="required" style="width: 90%;" />
						<a href="#" onclick="AddAnswerField();return false;" class="brackets">+</a>
						<a href="#" onclick="RemoveAnswerField();return false;" class="brackets">&minus;</a>
					</td>
				</tr>
<? } ?>
			</table>
			<div id="subscribediv" class="hidden">
				<input id="subscribeboxpreview" type="checkbox" name="subscribe"<?=!empty($HeavyInfo['AutoSubscribe']) ? ' checked="checked"' : '' ?> />
				<label for="subscribebox">Subscribe to topic</label>
			</div>
			<div id="buttons" class="center">
				<input type="button" value="Preview" onclick="Newthread_Preview(1);" id="newthreadpreviewbutton" />
				<input type="button" value="Editor" onclick="Newthread_Preview(0);" id="newthreadeditbutton" class="hidden" />
				<input type="submit" class="submit" id="submit_button" value="Create thread" />
			</div>
		</form>
	</div>
</div>
<? View::show_footer(); ?>
