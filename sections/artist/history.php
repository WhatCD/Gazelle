<?
if (!is_number($_GET['artistid'])) {
	error(0);
}
$ArtistID = (int)$_GET['artistid'];

$DB->query("
	SELECT Name
	FROM artists_group
	WHERE ArtistID = $ArtistID");
if (!$DB->has_results()) {
	error(404);
}
list($Name) = $DB->next_record();

View::show_header("Revision history for $Name");
?>
<div class="thin">
	<div class="header">
		<h2>Revision history for <a href="artist.php?id=<?=$ArtistID?>"><?=$Name?></a></h2>
	</div>
<?
RevisionHistoryView::render_revision_history(RevisionHistory::get_revision_history('artists', $ArtistID), "artist.php?id=$ArtistID");
?>
</div>
<?
View::show_footer();
