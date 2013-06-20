<?
/************************************************************************
||------------|| Torrent group wiki history page ||--------------------||

This page lists previous revisions of the torrent group page. It gets
called if $_GET['action'] == 'history'.

It also requires $_GET['groupid'].

The Wiki class is used here to generate the page history.

************************************************************************/

$GroupID = $_GET['groupid'];
if (!is_number($GroupID) || !$GroupID) {
	error(0);
}

// Get the torrent group name and the body of the last revision
$DB->query("
	SELECT Name
	FROM torrents_group
	WHERE ID = '$GroupID'");
list($Name) = $DB->next_record();

if (!$Name) {
	error(404);
}

View::show_header("Revision history for $Name"); // Set title

// Start printing form
?>
<div class="thin">
	<div class="header">
		<h2>Revision history for <a href="torrents.php?id=<?=$GroupID?>"><?=$Name?></a></h2>
	</div>
<?
// the Wiki class takes over from here
Wiki::revision_history('wiki_torrents', $GroupID, "/torrents.php?id=$GroupID");
?>
</div>
<?
View::show_footer();
?>
