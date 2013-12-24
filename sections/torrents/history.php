<?
if (!isset($_GET['groupid']) || !is_number($_GET['groupid'])) {
	error(0);
}
$GroupID = (int)$_GET['groupid'];

$DB->query("
	SELECT Name
	FROM torrents_group
	WHERE ID = $GroupID");
if (!$DB->has_results()) {
	error(404);
}
list($Name) = $DB->next_record();

View::show_header("Revision history for $Name");
?>
<div class="thin">
	<div class="header">
		<h2>Revision history for <a href="torrents.php?id=<?=$GroupID?>"><?=$Name?></a></h2>
	</div>
<?
RevisionHistoryView::render_revision_history(RevisionHistory::get_revision_history('torrents', $GroupID), "torrents.php?id=$GroupID");
?>
</div>
<?
View::show_footer();
