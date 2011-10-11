<?
//TODO: replace 24-43 with user_info()
/*
User post history page
*/

if(!empty($LoggedUser['DisableForums'])) {
	error(403);
}


include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;


$UserID = empty($_GET['userid']) ? $LoggedUser['ID'] : $_GET['userid'];
if(!is_number($UserID)){
	error(0);
}

if (isset($LoggedUser['PostsPerPage'])) {
	$PerPage = $LoggedUser['PostsPerPage'];
} else {
	$PerPage = POSTS_PER_PAGE;
}

list($Page,$Limit) = page_limit($PerPage);

if(($UserInfo = $Cache->get_value('user_info_'.$UserID)) === FALSE) {
	$DB->query("SELECT
		m.Username,
		m.Enabled,
		m.Title,
		i.Avatar,
		i.Donor,
		i.Warned
		FROM users_main AS m
		JOIN users_info AS i ON i.UserID = m.ID
		WHERE m.ID = $UserID");
	
	if($DB->record_count() == 0){ // If user doesn't exist
		error(404);
	}
	list($Username, $Enabled, $Title, $Avatar, $Donor, $Warned) = $DB->next_record();
} else {
	extract(array_intersect_key($UserInfo, array_flip(array('Username', 'Enabled', 'Title', 'Avatar', 'Donor', 'Warned'))));
}

if(check_perms('site_proxy_images') && !empty($Avatar)) {
	$Avatar = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?c=1&i='.urlencode($Avatar);
}

show_header('Post history for '.$Username,'subscriptions,comments,bbcode');

if($LoggedUser['CustomForums']) {
	unset($LoggedUser['CustomForums']['']);
	$RestrictedForums = implode("','", array_keys($LoggedUser['CustomForums'], 0));
}
$ViewingOwn = ($UserID == $LoggedUser['ID']);
$ShowUnread = ($ViewingOwn && (!isset($_GET['showunread']) || !!$_GET['showunread']));
$ShowGrouped = ($ViewingOwn && (!isset($_GET['group']) || !!$_GET['group']));
if($ShowGrouped) {
	$sql = 'SELECT
		SQL_CALC_FOUND_ROWS
		MAX(p.ID) AS ID
		FROM forums_posts AS p
		LEFT JOIN forums_topics AS t ON t.ID = p.TopicID';
	if($ShowUnread) {
		$sql.='
		LEFT JOIN forums_last_read_topics AS l ON l.TopicID = t.ID AND l.UserID = '.$LoggedUser['ID'];
	}
	$sql .= '
		LEFT JOIN forums AS f ON f.ID = t.ForumID
		WHERE p.AuthorID = '.$UserID.'
		AND ((f.MinClassRead <= '.$LoggedUser['Class'];
	if(!empty($RestrictedForums)) {
		$sql.='
		AND f.ID NOT IN (\''.$RestrictedForums.'\')';
	}
	$sql .= ')';
	if(!empty($PermittedForums)) {
		$sql.='
		OR f.ID IN (\''.$PermittedForums.'\')';
	}
	$sql .= ')';
	if($ShowUnread) {
		$sql .= '
		AND ((t.IsLocked=\'0\' OR t.IsSticky=\'1\')
		AND (l.PostID<t.LastPostID OR l.PostID IS NULL))';
	}
	$sql .= '
		GROUP BY t.ID
		ORDER BY p.ID DESC LIMIT '.$Limit;
	$PostIDs = $DB->query($sql);
	$DB->query("SELECT FOUND_ROWS()");
	list($Results) = $DB->next_record();

	if($Results > $PerPage*($Page-1)) {
		$DB->set_query_id($PostIDs);
		$PostIDs = $DB->collect('ID');
		$sql = 'SELECT
			p.ID,
			p.AddedTime,
			p.Body,
			p.EditedUserID,
			p.EditedTime,
			ed.Username,
			p.TopicID,
			t.Title,
			t.LastPostID,
			l.PostID AS LastRead,
			t.IsLocked,
			t.IsSticky
			FROM forums_posts as p
			LEFT JOIN users_main AS um ON um.ID = p.AuthorID
			LEFT JOIN users_info AS ui ON ui.UserID = p.AuthorID
			LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
			JOIN forums_topics AS t ON t.ID = p.TopicID
			JOIN forums AS f ON f.ID = t.ForumID
			LEFT JOIN forums_last_read_topics AS l ON l.UserID = '.$UserID.' AND l.TopicID = t.ID
			WHERE p.ID IN ('.implode(',',$PostIDs).')
			ORDER BY p.ID DESC';
		$Posts = $DB->query($sql);
	}
} else {
	$sql = 'SELECT
		SQL_CALC_FOUND_ROWS';
	if($ShowGrouped) {
		$sql.=' * FROM (SELECT';
	}
	$sql .= '
		p.ID,
		p.AddedTime,
		p.Body,
		p.EditedUserID,
		p.EditedTime,
		ed.Username,
		p.TopicID,
		t.Title,
		t.LastPostID,';
	if($UserID == $LoggedUser['ID']) {
		$sql .= '
		l.PostID AS LastRead,';
	}
	$sql .= '
		t.IsLocked,
		t.IsSticky
		FROM forums_posts as p
		LEFT JOIN users_main AS um ON um.ID = p.AuthorID
		LEFT JOIN users_info AS ui ON ui.UserID = p.AuthorID
		LEFT JOIN users_main AS ed ON ed.ID = p.EditedUserID
		JOIN forums_topics AS t ON t.ID = p.TopicID
		JOIN forums AS f ON f.ID = t.ForumID
		LEFT JOIN forums_last_read_topics AS l ON l.UserID = '.$UserID.' AND l.TopicID = t.ID
		WHERE p.AuthorID = '.$UserID.'
		AND f.MinClassRead <= '.$LoggedUser['Class'];

	if(!empty($RestrictedForums)) {
		$sql.='
		AND f.ID NOT IN (\''.$RestrictedForums.'\')';
	}

	if($ShowUnread) {
		$sql.='
		AND ((t.IsLocked=\'0\' OR t.IsSticky=\'1\') AND (l.PostID<t.LastPostID OR l.PostID IS NULL)) ';
	}
	
	$sql .= '
		ORDER BY p.ID DESC';
	
	if($ShowGrouped) {
		$sql.='
		) AS sub
		GROUP BY TopicID ORDER BY ID DESC';
	}
	
	$sql.=' LIMIT '.$Limit;
	$Posts = $DB->query($sql);
	
	$DB->query("SELECT FOUND_ROWS()");
	list($Results) = $DB->next_record();
	
	$DB->set_query_id($Posts);
}

?>
<div class="thin">
	<h2>
<?
	if($ShowGrouped) {
		echo "Grouped ".($ShowUnread?"unread ":"")."post history for <a href=\"user.php?id=$UserID\">$Username</a>";
	}
	elseif($ShowUnread) {
		echo "Unread post history for <a href=\"user.php?id=$UserID\">$Username</a>";
	}
	else {
		echo "Post history for <a href=\"user.php?id=$UserID\">$Username</a>";
	}
?>
	</h2>
	
	<div class="linkbox">
<?
if($ViewingOwn) {
	if(($UserSubscriptions = $Cache->get_value('subscriptions_user_'.$LoggedUser['ID'])) === FALSE) {
		$DB->query("SELECT TopicID FROM users_subscriptions WHERE UserID = '$LoggedUser[ID]'");
		$UserSubscriptions = $DB->collect(0);
		$Cache->cache_value('subscriptions_user_'.$LoggedUser['ID'],$UserSubscriptions,0);
		$DB->set_query_id($Posts);
	}
	
	if(!$ShowUnread){ ?>
		<br /><br />
		<? if($ShowGrouped) { ?>
			<a href="userhistory.php?action=posts&amp;userid=<?=$UserID?>&amp;showunread=0&amp;group=0">Show all posts</a>&nbsp;&nbsp;&nbsp;
		<? } else { ?>
			<a href="userhistory.php?action=posts&amp;userid=<?=$UserID?>&amp;showunread=0&amp;group=1">Show all posts (grouped)</a>&nbsp;&nbsp;&nbsp;
		<? } ?>
		<a href="userhistory.php?action=posts&amp;userid=<?=$UserID?>&amp;showunread=1&amp;group=1">Only display posts with unread replies (grouped)</a>&nbsp;&nbsp;&nbsp;
<?	} else { ?>
		<br /><br />
		<a href="userhistory.php?action=posts&amp;userid=<?=$UserID?>&amp;showunread=0&amp;group=0">Show all posts</a>&nbsp;&nbsp;&nbsp;
<?	
		if(!$ShowGrouped) {
			?><a href="userhistory.php?action=posts&amp;userid=<?=$UserID?>&amp;showunread=1&amp;group=1">Only display posts with unread replies (grouped)</a>&nbsp;&nbsp;&nbsp;<?
		}
		else {
			?><a href="userhistory.php?action=posts&amp;userid=<?=$UserID?>&amp;showunread=1&amp;group=0">Only display posts with unread replies</a>&nbsp;&nbsp;&nbsp;<?
		}
	}
?>
			<a href="userhistory.php?action=subscriptions">Go to subscriptions</a>
<?
}

?>
	</div>
<?
if(empty($Results)) {
?>
	<div class="center">
		No topics<?=$ShowUnread?' with unread posts':''?>
	</div>
<?
} else {
?>
	<div class="linkbox">
<?
	$Pages=get_pages($Page,$Results,$PerPage, 11);
	echo $Pages;
?>
	</div>
<?
	while(list($PostID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername, $TopicID, $ThreadTitle, $LastPostID, $LastRead, $Locked, $Sticky) = $DB->next_record()){
?>
	<table class='forum_post vertical_margin<?=$HeavyInfo['DisableAvatars'] ? ' noavatar' : ''?>' id='post<?=$PostID ?>'>
		<tr class='colhead_dark'>
			<td  colspan="2">
				<span style="float:left;">
					<?=time_diff($AddedTime) ?>
					in <a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>&amp;postid=<?=$PostID?>#post<?=$PostID?>" title="<?=display_str($ThreadTitle)?>"><?=cut_string($ThreadTitle, 75)?></a>
<?
		if($ViewingOwn) {
			if ((!$Locked  || $Sticky) && (!$LastRead || $LastRead < $LastPostID)) { ?> 
					<span style="color: red;">(New!)</span>
<?
			}
?>
				</span>
<?			if(!empty($LastRead)) { ?>
				<span style="float:left;" class="last_read" title="Jump to last read">
					<a href="forums.php?action=viewthread&amp;threadid=<?=$TopicID?>&amp;postid=<?=$LastRead?>#post<?=$LastRead?>"></a>
				</span>
<?			}
		} else {
?>
				</span>
<?		}
?>
				<span id="bar<?=$PostID ?>" style="float:right;">
<? 		if($ViewingOwn && !in_array($TopicID, $UserSubscriptions)) { ?>
					<a href="#" onclick="Subscribe(<?=$TopicID?>);$('.subscribelink<?=$TopicID?>').remove();return false;" class="subscribelink<?=$TopicID?>">[Subscribe]</a>
					&nbsp;
<? 		} ?>
					<a href="#">&uarr;</a>
				</span>
			</td>
		</tr>
<?
		if(!$ShowGrouped) {
?>
		<tr>
<?
			if(empty($HeavyInfo['DisableAvatars'])) {
?>
			<td class='avatar' valign="top">
<?
				if($Avatar) {
?>
				<img src='<?=$Avatar?>' width='150' style="max-height:400px;" alt="<?=$Username?>'s avatar" />
<?
				} 
?>
			</td>
<?
			}
?>
			<td class='body' valign="top">
				<div id="content<?=$PostID?>">
					<?=$Text->full_format($Body)?>
<?			if($EditedUserID) { ?>       
					<br />
					<br />
<?				if(check_perms('site_moderate_forums')) { ?>
					<a href="#content<?=$PostID?>" onclick="LoadEdit(<?=$PostID?>, 1)">&laquo;</a>
<? 				} ?>		   
					Last edited by
					<?=format_username($EditedUserID, $EditedUsername) ?> <?=time_diff($EditedTime,2,true,true)?>
<?			} ?>		
				</div>
			</td>
		</tr>
<?
		}
?>
	</table>
<? 	} ?>
	<div class="linkbox">
<?=$Pages?>
	</div>
<? } ?>
</div>
<?

show_footer();

?>
