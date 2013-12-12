<?
/**********|| Page to show individual threads || ********************************\

Things to expect in $_GET:
	ThreadID: ID of the forum curently being browsed
	page:	The page the user's on.
	page = 1 is the same as no page

********************************************************************************/

//---------- Things to sort out before it can start printing/generating content

// Check for lame SQL injection attempts
if (!is_number($_GET['collageid'])) {
	error(0);
}
$CollageID = (int)$_GET['collageid'];

list($NumComments, $Page, $Thread, $LastRead) = Comments::load('collages', $CollageID);

$DB->query("
	SELECT Name
	FROM collages
	WHERE ID = '$CollageID'");
list($Name) = $DB->next_record();

// Start printing
View::show_header("Comments for collage $Name", 'comments,bbcode,subscriptions');
?>
<div class="thin">
	<div class="header">
		<h2>
			<a href="collages.php">Collages</a> &gt;
			<a href="collages.php?id=<?=$CollageID?>"><?=$Name?></a>
		</h2>
		<div class="linkbox">
			<a href="#" id="subscribelink_collages<?=$CollageID?>" class="brackets" onclick="SubscribeComments('collages', <?=$CollageID?>); return false;"><?=Subscriptions::has_subscribed_comments('collages', $CollageID) !== false ? 'Unsubscribe' : 'Subscribe'?></a>
<?
$Pages = Format::get_pages($Page, $NumComments, TORRENT_COMMENTS_PER_PAGE, 9);
if ($Pages) {
	echo '<br /><br />' . $Pages;
}
?>
		</div>
	</div>
<?

//---------- Begin printing
CommentsView::render_comments($Thread, $LastRead, "collages.php?action=comments&amp;collageid=$CollageID");
if (!$ThreadInfo['IsLocked'] || check_perms('site_moderate_forums')) {
	if ($ThreadInfo['MinClassWrite'] <= $LoggedUser['Class'] && !$LoggedUser['DisablePosting']) {
		View::parse('generic/reply/quickreply.php', array(
			'InputName' => 'pageid',
			'InputID' => $CollageID,
			'Action' => 'comments.php?page=collages',
			'InputAction' => 'take_post',
			'TextareaCols' => 90,
			'SubscribeBox' => true
		));
	}
}
?>
	<div class="linkbox">
		<?=$Pages?>
	</div>
</div>
<? View::show_footer(); ?>
