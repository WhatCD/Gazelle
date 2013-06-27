<?
/**********|| Page to show individual threads || ********************************\

Things to expect in $_GET:
	ThreadID: ID of the forum curently being browsed
	page:	The page the user's on.
	page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

include(SERVER_ROOT.'/classes/text.class.php'); // Text formatting class
$Text = new TEXT;

// Check for lame SQL injection attempts
$CollageID = $_GET['collageid'];
if (!is_number($CollageID)) {
	error(0);
}

// gets the amount of comments for this collage
$NumComments = Collages::get_comment_count($CollageID);

if (isset($_GET['postid']) && is_number($_GET['postid']) && $NumComments > TORRENT_COMMENTS_PER_PAGE) {
	$DB->query("SELECT COUNT(ID) FROM collages_comments WHERE CollageID = $CollageID AND ID <= $_GET[postid]");
	list($PostNum) = $DB->next_record();
	list($Page, $Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE, $PostNum);
} else {
	list($Page, $Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE, $NumComments);
}

//Get the cache catalogue
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $Page - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
$CatalogueLimit = $CatalogueID * THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
$Catalogue = Collages::get_comment_catalogue($CollageID, $CatalogueID);

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue, ((TORRENT_COMMENTS_PER_PAGE * $Page - TORRENT_COMMENTS_PER_PAGE) % THREAD_CATALOGUE), TORRENT_COMMENTS_PER_PAGE, true);

$DB->query("SELECT Name FROM collages WHERE ID='$CollageID'");
list($Name) = $DB->next_record();

// Start printing
View::show_header('Comments for collage '.$Name, 'comments,bbcode');
?>
<div class="thin">
	<div class="header">
		<h2>
			<a href="collages.php">Collages</a> &gt;
			<a href="collages.php?id=<?=$CollageID?>"><?=$Name?></a>
		</h2>
		<div class="linkbox">
<?
$Pages = Format::get_pages($Page, $NumComments, TORRENT_COMMENTS_PER_PAGE, 9);
echo $Pages;
?>
		</div>
	</div>
<?

//---------- Begin printing
foreach ($Thread as $Post) {
	list($PostID, $AuthorID, $AddedTime, $Body) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(Users::user_info($AuthorID));
?>
<table class="forum_post box vertical_margin<?=(!Users::has_avatars_enabled() ? ' noavatar' : '')?>" id="post<?=$PostID?>">
	<colgroup>
<?	if (Users::has_avatars_enabled()) { ?>
		<col class="col_avatar" />
<? 	} ?>
		<col class="col_post_body" />
	</colgroup>
	<tr class="colhead_dark">
		<td colspan="<?=(Users::has_avatars_enabled() ? 2 : 1)?>">
			<span style="float: left;"><a href="#post<?=$PostID?>">#<?=$PostID?></a>
				<?=Users::format_username($AuthorID, true, true, true, true, true)?> <?=time_diff($AddedTime)?>
<? if (!$ThreadInfo['IsLocked']) { ?>				- <a href="#quickpost" onclick="Quote('<?=$PostID?>','<?=$Username?>');" class="brackets">Quote</a><? }
if ($AuthorID == $LoggedUser['ID'] || check_perms('site_moderate_forums')) { ?>				- <a href="#post<?=$PostID?>" onclick="Edit_Form('<?=$PostID?>');" class="brackets">Edit</a><? }
if (check_perms('site_moderate_forums')) { ?>				- <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');" class="brackets">Delete</a> <? } ?>
			</span>
			<span id="bar<?=$PostID?>" style="float: right;">
				<a href="reports.php?action=report&amp;type=collages_comment&amp;id=<?=$PostID?>" class="brackets">Report</a>
				<a href="#">&uarr;</a>
			</span>
		</td>
	</tr>
	<tr>
<?	if (Users::has_avatars_enabled()) { ?>
		<td class="avatar" valign="top">
		<?=Users::show_avatar($Avatar, $Username, $HeavyInfo['DisableAvatars'])?>
		</td>
<?	} ?>
		<td class="body" valign="top">
			<div id="content<?=$PostID?>">
<?=$Text->full_format($Body)?>
			</div>
		</td>
	</tr>
</table>
<?	}
if (!$ThreadInfo['IsLocked'] || check_perms('site_moderate_forums')) {
	if ($ThreadInfo['MinClassWrite'] <= $LoggedUser['Class'] && !$LoggedUser['DisablePosting']) {

	View::parse('generic/reply/quickreply.php', array(
			'InputName' => 'collageid',
			'InputID' => $CollageID,
			'InputAction' => 'add_comment',
			'TextareaCols' => 90));
	}
}
?>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>
<? View::show_footer(); ?>
