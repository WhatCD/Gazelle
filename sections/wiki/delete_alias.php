<?
authorize();

$ArticleID = Wiki::alias_to_id($_GET['alias']);

$DB->query("SELECT MinClassEdit FROM wiki_articles WHERE ID = $ArticleID");
list($MinClassEdit) = $DB->next_record();
if ($MinClassEdit > $LoggedUser['EffectiveClass']) {
	error(403);
}

$DB->query("DELETE FROM wiki_aliases WHERE Alias='".Wiki::normalize_alias($_GET['alias'])."'");
Wiki::flush_article($ArticleID);
Wiki::flush_aliases();
