<?php

include(SERVER_ROOT.'/classes/text.class.php');
$Text = new TEXT;

$GroupID=ceil($_GET['id']);

$Results = $Cache->get_value('torrent_comments_'.$GroupID);
if ($Results === false) {
	$DB->query("
		SELECT
			COUNT(c.ID)
		FROM torrents_comments as c
		WHERE c.GroupID = '$GroupID'");
	list($Results) = $DB->next_record();
	$Cache->cache_value('torrent_comments_'.$GroupID, $Results, 0);
}

if (isset($_GET['postid']) && is_number($_GET['postid']) && $Results > TORRENT_COMMENTS_PER_PAGE) {
	$DB->query("
		SELECT COUNT(ID)
		FROM torrents_comments
		WHERE GroupID = $GroupID
			AND ID <= $_GET[postid]");
	list($PostNum) = $DB->next_record();
	list($Page, $Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE, $PostNum);
} else {
	list($Page, $Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE, $Results);
}

//Get the cache catalogue
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE * $Page - TORRENT_COMMENTS_PER_PAGE) / THREAD_CATALOGUE);
$CatalogueLimit = $CatalogueID * THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
$Catalogue = $Cache->get_value('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID);
if ($Catalogue === false) {
	$DB->query("
		SELECT
			c.ID,
			c.AuthorID,
			c.AddedTime,
			c.Body,
			c.EditedUserID,
			c.EditedTime,
			u.Username
		FROM torrents_comments as c
			LEFT JOIN users_main AS u ON u.ID=c.EditedUserID
		WHERE c.GroupID = '$GroupID'
		ORDER BY c.ID
		LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array(false,MYSQLI_ASSOC);
	$Cache->cache_value('torrent_comments_'.$GroupID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
}

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue, ((TORRENT_COMMENTS_PER_PAGE * $Page - TORRENT_COMMENTS_PER_PAGE) % THREAD_CATALOGUE), TORRENT_COMMENTS_PER_PAGE, true);

//---------- Begin printing
$JsonComments = array();
foreach ($Thread as $Key => $Post) {
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(Users::user_info($AuthorID));
	$JsonComments[] = array(
		'postId' => (int) $PostID,
		'addedTime' => $AddedTime,
		'bbBody' => $Body,
		'body' => $Text->full_format($Body),
		'editedUserId' => (int) $EditedUserID,
		'editedTime' => $EditedTime,
		'editedUsername' => $EditedUsername,
		'userinfo' => array(
			'authorId' => (int) $AuthorID,
			'authorName' => $Username,
			'artist' => $Artist == 1,
			'donor' => $Donor == 1,
			'warned' => ($Warned != '0000-00-00 00:00:00'),
			'avatar' => $Avatar,
			'enabled' => ($Enabled == 2 ? false : true),
			'userTitle' => $UserTitle
		)
	);
}

json_die("success", array(
	'page' => (int) $Page,
	'pages' => ceil($Results / TORRENT_COMMENTS_PER_PAGE),
	'comments' => $JsonComments
));
