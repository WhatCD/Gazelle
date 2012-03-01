<?

include_once(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class


function link_users($UserID, $TargetID) {
	global $DB, $LoggedUser;

	authorize();
	if (!check_perms('users_mod')) {
		error(403);
	}

	if (!is_number($UserID) || !is_number($TargetID)) {
		error(403);
	}
	if ($UserID == $TargetID) {
		return;
	}

	$DB->query("SELECT 1 FROM users_main WHERE ID IN ($UserID, $TargetID)");
	if ($DB->record_count() != 2) {
		error(403);
	}

	$DB->query("SELECT GroupID FROM users_dupes WHERE UserID = $TargetID");
	list($TargetGroupID) = $DB->next_record();
	$DB->query("SELECT u.GroupID, d.Comments FROM users_dupes AS u JOIN dupe_groups AS d ON d.ID = u.GroupID WHERE UserID = $UserID");
	list($UserGroupID, $Comments) = $DB->next_record();

	$UserInfo = user_info($UserID);
	$TargetInfo = user_info($TargetID);
	if (!$UserInfo || !$TargetInfo) {
		return;
	}

	if ($TargetGroupID) {
		if ($TargetGroupID == $UserGroupID) {
			return;
		}
		if ($UserGroupID) {
			$DB->query("UPDATE users_dupes SET GroupID = $TargetGroupID WHERE GroupID = $UserGroupID");
			$DB->query("UPDATE dupe_groups SET Comments = CONCAT('".db_string($Comments)."\n\n',Comments) WHERE ID = $TargetGroupID");
			$DB->query("DELETE FROM dupe_groups WHERE ID = $UserGroupID");
			$GroupID = $UserGroupID;
		} else {
			$DB->query("INSERT INTO users_dupes (UserID, GroupID) VALUES ($UserID, $TargetGroupID)");
			$GroupID = $TargetGroupID;
		}
	} elseif ($UserGroupID) {
		$DB->query("INSERT INTO users_dupes (UserID, GroupID) VALUES ($TargetID, $UserGroupID)");
		$GroupID = $UserGroupID;
	} else {
		$DB->query("INSERT INTO dupe_groups () VALUES ()");
		$GroupID = $DB->inserted_id();
		$DB->query("INSERT INTO users_dupes (UserID, GroupID) VALUES ($TargetID, $GroupID)");
		$DB->query("INSERT INTO users_dupes (UserID, GroupID) VALUES ($UserID, $GroupID)");
	}

	$AdminComment = sqltime()." - Linked accounts updated: [user]".$UserInfo['Username']."[/user] and [user]".$TargetInfo['Username']."[/user] linked by ".$LoggedUser['Username'];
	$DB->query("UPDATE users_info  AS i
				JOIN   users_dupes AS d ON d.UserID = i.UserID
				SET i.AdminComment = CONCAT('".db_string($AdminComment)."\n\n', i.AdminComment)
				WHERE d.GroupID = $GroupID");
}

function unlink_user($UserID) {
	global $DB, $LoggedUser;

	authorize();
	if (!check_perms('users_mod')) {
		error(403);
	}

	if (!is_number($UserID)) {
		error(403);
	}
	$UserInfo = user_info($UserID);
	if ($UserInfo === FALSE) {
		return;
	}
	$AdminComment = sqltime()." - Linked accounts updated: [user]".$UserInfo['Username']."[/user] unlinked by ".$LoggedUser['Username'];
	$DB->query("UPDATE users_info  AS i
				JOIN   users_dupes AS d1 ON d1.UserID = i.UserID
				JOIN   users_dupes AS d2 ON d2.GroupID = d1.GroupID
				SET i.AdminComment = CONCAT('".db_string($AdminComment)."\n\n', i.AdminComment)
				WHERE d2.UserID = $UserID");
	$DB->query("DELETE FROM users_dupes WHERE UserID='$UserID'");
	$DB->query("DELETE g.* FROM dupe_groups AS g LEFT JOIN users_dupes AS u ON u.GroupID = g.ID WHERE u.GroupID IS NULL");
}

function delete_dupegroup($GroupID) {
	global $DB;

	authorize();
	if (!check_perms('users_mod')) {
		error(403);
	}

	if (!is_number($GroupID)) {
		error(403);
	}

	$DB->query("DELETE FROM dupe_groups WHERE ID = '$GroupID'");
}

function dupe_comments($GroupID, $Comments) {
	global $DB, $Text, $LoggedUser;

	authorize();
	if (!check_perms('users_mod')) {
		error(403);
	}

	if (!is_number($GroupID)) {
		error(403);
	}

	$DB->query("SELECT SHA1(Comments) AS CommentHash FROM dupe_groups WHERE ID = $GroupID");
	list($OldCommentHash) = $DB->next_record();
	if ($OldCommentHash != sha1($Comments)) {
		$AdminComment = sqltime()." - Linked accounts updated: Comments updated by ".$LoggedUser['Username'];
		if ($_POST['form_comment_hash'] == $OldCommentHash) {
			$DB->query("UPDATE dupe_groups SET Comments = '".db_string($Comments)."' WHERE ID = '$GroupID'");
		} else {
			$DB->query("UPDATE dupe_groups SET Comments = CONCAT('".db_string($Comments)."\n\n',Comments) WHERE ID = '$GroupID'");
		}

		$DB->query("UPDATE users_info  AS i
					JOIN   users_dupes AS d ON d.UserID = i.UserID
					SET i.AdminComment = CONCAT('".db_string($AdminComment)."\n\n', i.AdminComment)
					WHERE d.GroupID = $GroupID");
	}
}

function user_dupes_table($UserID) {
	global $DB, $LoggedUser;
	$Text = new TEXT;

	if (!check_perms('users_mod')) {
		error(403);
	}
	if (!is_number($UserID)) {
		error(403);
	}
	$DB->query("SELECT d.ID, d.Comments, SHA1(d.Comments) AS CommentHash
				FROM dupe_groups AS d
				JOIN users_dupes AS u ON u.GroupID = d.ID
				WHERE u.UserID = $UserID");
	if (list($GroupID, $Comments, $CommentHash) = $DB->next_record()) {
		$DB->query("SELECT m.ID
					FROM users_main AS m
					JOIN users_dupes AS d ON m.ID = d.UserID
					WHERE d.GroupID = $GroupID
					ORDER BY m.ID ASC");
		$DupeCount = $DB->record_count();
		$Dupes = $DB->to_array();
	} else {
		$DupeCount = 0;
		$Dupes = array();
	}
?>
		<form method="POST" id="linkedform">
			<input type="hidden" name="action" value="dupes">
			<input type="hidden" name="dupeaction" value="update">
			<input type="hidden" name="userid" value="<?=$UserID?>">
			<input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>">
			<input type="hidden" id="form_comment_hash" name="form_comment_hash" value="<?=$CommentHash?>">
			<div class="box">
				<div class="head"><?=max($DupeCount - 1, 0)?> Linked Account<?=(($DupeCount == 2)?'':'s')?> <a href="#" onclick="$('.linkedaccounts').toggle(); return false;">(View)</a></div>
				<table width="100%" class="hidden linkedaccounts">
					<?=$DupeCount?'<tr>':''?>
<?
	$i = 0;
	foreach ($Dupes as $Dupe) {
		$i++;
		list($DupeID) = $Dupe;
		$DupeInfo = user_info($DupeID);
?>
					<td align="left"><?=format_username($DupeID, $DupeInfo['Username'], $DupeInfo['Donor'], $DupeInfo['Warned'], ($DupeInfo['Enabled']==2)?false:true)?>
						(<a href="user.php?action=dupes&dupeaction=remove&auth=<?=$LoggedUser['AuthKey']?>&userid=<?=$UserID?>&removeid=<?=$DupeID?>" onClick="return confirm('Are you sure you wish to remove <?=$DupeInfo['Username']?> from this group?');">x</a>)</td>
<?
		if ($i == 5) {
			$i = 0;
			echo "</tr><tr>";
		}
	}
	if ($DupeCount) {
		for ($j = $i; $j < 5; $j++) {
			echo '<td>&nbsp;</td>';
		}
?>
					</tr>
<?	}	?>
					<tr>
						<td colspan="5" align="left" style="border-top: thin solid"><strong>Comments:</strong></td>
					</tr>
					<tr>
						<td colspan="5" align="left">
							<div id="dupecomments" class="<?=$DupeCount?'':'hidden'?>"><?=$Text->full_format($Comments);?></div>
							<div id="editdupecomments" class="<?=$DupeCount?'hidden':''?>">
								<textarea name="dupecomments" onkeyup="resize('dupecommentsbox');" id="dupecommentsbox" cols="65" rows="5" style="width:98%;"><?=display_str($Comments)?></textarea>
							</div>
							<span style="float:right; font-style: italic;"><a href="#" onClick="$('#dupecomments').toggle(); $('#editdupecomments').toggle(); resize('dupecommentsbox'); return false;">(Edit linked account comments)</a>
						</td>
					</tr>
				</table>
				<div class="pad hidden linkedaccounts">
					<label for="target">Link this user with: </label><input type="text" name="target" id="target"><input type="submit" value="Link" id="submitlink" />
				</div>
			</div>
		</form>
<?
}
?>
