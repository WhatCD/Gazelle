<?
/************************************************************************
||------------|| Artist wiki history page ||---------------------------||

This page lists previous revisions of the artists page. It gets called
if $_GET['action'] == 'history'.

It also requires $_GET['artistid'].

The wiki class is used here to generate the page history.

************************************************************************/

$ArtistID = $_GET['artistid'];
if (!is_number($ArtistID)) {
	error(0);
}

// Get the artist name and the body of the last revision
$DB->query("
	SELECT Name
	FROM artists_group
	WHERE ArtistID = '$ArtistID'");
list($Name) = $DB->next_record(MYSQLI_NUM, true);

View::show_header("Revision history for $Name"); // Set title

// Start printing form
?>
<div class="thin">
	<div class="header">
		<h2>Revision history for <a href="artist.php?id=<?=$ArtistID?>"><?=$Name?></a></h2>
	</div>
<?
// the Wiki class takes over from here
Wiki::revision_history('wiki_artists', $ArtistID, "artist.php?id=$ArtistID");
?>
</div>
<?
View::show_footer();
?>
