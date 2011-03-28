<?
/**********|| Page to show individual threads || ********************************\

Things to expect in $_GET:
	ThreadID: ID of the forum curently being browsed
	page:	The page the user's on. 
	page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

// Check for lame SQL injection attempts
$CollageID = $_GET['collageid'];
if(!is_number($CollageID)) { 
	error(0);
}

list($Page,$Limit) = page_limit(POSTS_PER_PAGE);

//Get the cache catalogue
$CatalogueID = floor((POSTS_PER_PAGE*$Page-POSTS_PER_PAGE)/THREAD_CATALOGUE);
$CatalogueLimit=$CatalogueID*THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
if(!list($Catalogue,$Posts) = $Cache->get_value('collage_'.$CollageID.'_catalogue_'.$CatalogueID)) {
	$DB->query("SELECT SQL_CALC_FOUND_ROWS
		ID,
		UserID,
		Time,
		Body
		FROM collages_comments
		WHERE CollageID = '$CollageID'
		LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array();
	$DB->query("SELECT FOUND_ROWS()");
	list($Posts) = $DB->next_record();
	$Cache->cache_value('collage_'.$CollageID.'_catalogue_'.$CatalogueID, array($Catalogue,$Posts), 0);
}

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue,((POSTS_PER_PAGE*$Page-POSTS_PER_PAGE)%THREAD_CATALOGUE),POSTS_PER_PAGE,true);

$DB->query("SELECT Name FROM collages WHERE ID='$CollageID'");
list($Name) = $DB->next_record();

// Start printing
show_header('Comments for collage '.$Name, 'comments,bbcode');
?>
<div class="thin">
	<h2>
		<a href="collages.php">Collages</a> &gt;
		<a href="collages.php?id=<?=$CollageID?>"><?=$Name?></a>
	</h2>
	<div class="linkbox">
<?
$Pages=get_pages($Page,$Posts,POSTS_PER_PAGE,9);
echo $Pages;
?>
	</div>
<?

//---------- Begin printing
foreach($Thread as $Post){
	list($PostID, $AuthorID, $AddedTime, $Body) = $Post;
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(user_info($AuthorID));
?>
<table class="forum_post box vertical_margin<?=$HeavyInfo['DisableAvatars'] ? ' noavatar' : ''?>" id="post<?=$PostID?>">
	<tr class="colhead_dark">
		<td colspan="2">
			<span style="float:left;"><a href='#post<?=$PostID?>'>#<?=$PostID?></a>
				by <strong><?=format_username($AuthorID, $Username, $Donor, $Warned, $Enabled, $PermissionID)?></strong> <? if ($UserTitle) { echo '('.$UserTitle.')'; }?> <?=time_diff($AddedTime)?> <a href="reports.php?action=report&amp;type=collages_comment&amp;id=<?=$PostID?>">[Report Comment]</a>
<? if (!$ThreadInfo['IsLocked']){ ?>				- <a href="#quickpost" onclick="Quote('<?=$PostID?>','<?=$Username?>');">[Quote]</a><? }
if ($AuthorID == $LoggedUser['ID'] || check_perms('site_moderate_forums')){ ?>				- <a href="#post<?=$PostID?>" onclick="Edit_Form('<?=$PostID?>');">[Edit]</a><? }
if (check_perms('site_moderate_forums')){ ?>				- <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');">[Delete]</a> <? } ?>
			</span>
			<span id="bar<?=$PostID?>" style="float:right;">
				<a href="#">&uarr;</a>
			</span>
		</td>
	</tr>
	<tr>
<? if(empty($HeavyInfo['DisableAvatars'])) { ?>
		<td class="avatar" valign="top">
<? if ($Avatar) { ?>
			<img src="<?=$Avatar?>" width="150" alt="<?=$Username ?>'s avatar" />
<? } else { ?>
			<img src="<?=STATIC_SERVER?>common/avatars/default.png" width="150" alt="Default avatar" />
<? } ?>
		</td>
<? } ?>
		<td class="body" valign="top">
			<div id="content<?=$PostID?>">
<?=$Text->full_format($Body)?>
			</div>
		</td>
	</tr>
</table>
<?	} 
if(!$ThreadInfo['IsLocked'] || check_perms('site_moderate_forums')) {
	if($ThreadInfo['MinClassWrite'] <= $LoggedUser['Class'] && !$LoggedUser['DisablePosting']) {
?>
		<br />
		<h3>Post reply</h3>
		<div class="box pad" style="padding:20px 10px 10px 10px;">
			<form id="quickpostform" action="" method="post" style="display: block; text-align: center;">
				<input type="hidden" name="action" value="add_comment" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="collageid" value="<?=$CollageID?>" />
				<div id="quickreplypreview" class="box" style="text-align: left; display: none; padding: 10px;"></div>
				<div id="quickreplytext">
					<textarea id="quickpost" name="body"  cols="90"  rows="8"></textarea> <br />
				</div>
				<input type="submit" value="Post reply" />
				<input id="post_preview" type="button" value="Preview" onclick="if(this.preview){Quick_Edit();}else{Quick_Preview();}" />
			</form>
		</div>
<?
	}
}
?>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>
<? show_footer(); ?>
