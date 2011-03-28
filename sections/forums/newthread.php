<?
/*
New post page

This is the page that's loaded if someone wants to make a new topic.

Information to be expected in $_GET:
	forumid: The ID of the forum that it's being posted in

*/

$ForumID = $_GET['forumid'];
if(!is_number($ForumID)) {
	error(404);
}
$Forum = get_forum_info($ForumID);
if($Forum === false) {
	error(404);
}


if($LoggedUser['Class'] < $Forum['MinClassCreate']) { error(403); }
show_header('Forums > '.$Forum['Name'].' > New Topic','comments,bbcode');
?>
<div class="thin">
	<h2><a href="forums.php">Forums</a> &gt; <a href="forums.php?action=viewforum&amp;forumid=<?=$ForumID?>"><?=$Forum['Name']?></a> &gt; <span id="newthreadtitle">New Topic</span></h2>
	<div class="hidden" id="newthreadpreview">
		<div class="linkbox">
			<div class="center">
				<a href="#" onclick="return false;">[Report Thread]</a>
				<a href="#" onclick="return false;"><?=!empty($HeavyInfo['AutoSubscribe']) ? '[Unsubscribe]' : '[Subscribe]'?></a>
			</div>
		</div>
<? if (check_perms('forums_polls_create')) { ?>
		<div class="box thin clear hidden" id="pollpreview">
			<div class="head colhead_dark"><strong>Poll</strong> <a href="#" onclick="$('#threadpoll').toggle();return false;">(View)</a></div>
			<div class="pad" id="threadpoll">
				<p><strong id="pollquestion"></strong></p>
				<div id="pollanswers"></div>
				<br /><input type="radio" name="vote" id="answer_0" value="0" /> <label for="answer_0">Blank - Show the results!</label><br /><br />
				<input type="button" style="float: left;" value="Vote" />
			</div>
		</div>
<? } ?>
		<table class="forum_post box vertical_margin" style="text-align:left;">
			<tr class="colhead_dark">
				<td colspan="2">
					<span style="float:left;"><a href='#newthreadpreview'>#XXXXXX</a>
						by <strong><?=format_username($LoggedUser['ID'], $LoggedUser['Username'], $LoggedUser['Donor'], $LoggedUser['Warned'], $LoggedUser['Enabled'] == 2 ? false : true, $LoggedUser['PermissionID'])?></strong> <? if (!empty($LoggedUser['Title'])) { echo '('.$LoggedUser['Title'].')'; }?>
					Just now
					</span>
					<span id="barpreview" style="float:right;">
						<a href="#newthreadpreview">[Report Post]</a>
						&nbsp;
						<a href="#">&uarr;</a>
					</span>
				</td>
			</tr>
			<tr>
				<td class="avatar" valign="top">
			<? if (!empty($LoggedUser['Avatar'])) { ?>
					<img src="<?=$LoggedUser['Avatar']?>" width="150" alt="<?=$LoggedUser['Username']?>'s avatar" />
			<? } else { ?>
					<img src="<?=STATIC_SERVER?>common/avatars/default.png" width="150" alt="Default avatar" />
			<? } ?>
				</td>
				<td class="body" valign="top">
					<div id="contentpreview" style="text-align:left;"></div>
				</td>
			</tr>
		</table>
	</div>
	<div class="box pad">
		<form action="" id="newthreadform" method="post">
			<input type="hidden" name="action" value="new" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="forum" value="<?=$ForumID?>" />
			<table id="newthreadtext">
				<tr>
					<td class="label">Title</td>
					<td><input id="title" type="text" name="title" style="width: 98%;" /></td>
				</tr>
				<tr>
					<td class="label">Body</td>
					<td><textarea id="posttext" style="width: 98%;" onkeyup="resize('posttext');" name="body" cols="90" rows="8"></textarea></td>
				</tr>
				<tr>
					<td></td>
					<td>
						<input id="subscribebox" type="checkbox" name="subscribe"<?=!empty($HeavyInfo['AutoSubscribe'])?' checked="checked"':''?> onchange="$('#subscribeboxpreview').raw().checked=this.checked;" />
						<label for="subscribebox">Subscribe to topic</label>
					</td>
				</tr>
<? if (check_perms('forums_polls_create')) { ?>
				<script type="text/javascript">
				var AnswerCount = 1;

				function AddAnswerField() {
						if (AnswerCount >= 25) { return; }
						var AnswerField = document.createElement("input");
						AnswerField.type = "text";
						AnswerField.id = "answer_"+AnswerCount;
						AnswerField.name = "answers[]";
						AnswerField.style.width = "90%";
		
						var x = $('#answer_block').raw();
						x.appendChild(document.createElement("br"));
						x.appendChild(AnswerField);
						AnswerCount++;
				}

				function RemoveAnswerField() {
						if (AnswerCount == 1) { return; }
						var x = $('#answer_block').raw();
						for (i=0; i<2; i++) { x.removeChild(x.lastChild); }
						AnswerCount--;
				}
				</script>
				<tr>
					<td colspan="2" class="center">
						<strong>Poll Settings</strong> 
						<a href="#" onclick="$('#poll_question, #poll_answers').toggle();return false;">(View)</a> 
					</td>
				</tr>
				<tr id="poll_question" class="hidden">
					<td class="label">Question</td>
					<td><input type="text" name="question" id="pollquestionfield" style="width: 98%;" /></td>
				</tr>
				<tr id="poll_answers" class="hidden">
					<td class="label">Answers</td>
					<td id="answer_block">
						<input type="text" name="answers[]" style="width: 90%;" />
						[<a href="#" onclick="AddAnswerField();return false;">+</a>]
						[<a href="#" onclick="RemoveAnswerField();return false;">-</a>]
					</td>
				</tr>
<? } ?>
			</table>
			<div id="subscribediv" class="hidden">
				<input id="subscribeboxpreview" type="checkbox" name="subscribe"<?=!empty($HeavyInfo['AutoSubscribe'])?' checked="checked"':''?> />
				<label for="subscribebox">Subscribe to topic</label>
			</div>
			<input type="button" value="Preview" onclick="Newthread_Preview(1);" id="newthreadpreviewbutton"/>
			<input type="button" value="Editor" onclick="Newthread_Preview(0);" id="newthreadeditbutton" class="hidden" />
			<input type="submit" value="Create thread" />
		</form>
	</div>
</div>
<? show_footer(); ?>
