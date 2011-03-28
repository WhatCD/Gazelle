<?
$ID = $_GET['id'];
if (!check_perms('admin_manage_wiki') || !is_number($ID) || ($ID == '136')) {
	error(404);
}

$DB->query("SELECT Title FROM wiki_articles WHERE ID = $ID");

if($DB->record_count() < 1) {
	error(404);
}

list($Title) = $DB->next_record();
//Log
write_log("Wiki article ".$ID." (".$Title.") was deleted by ".$LoggedUser['Username']);
//Delete
$DB->query("DELETE FROM wiki_articles WHERE ID = $ID");
$DB->query("DELETE FROM wiki_aliases WHERE ArticleID = $ID");
$DB->query("DELETE FROM wiki_revisions WHERE ID = $ID");

$Cache->delete_value('wiki_article_'.$ID);
header("location: wiki.php");

?>
