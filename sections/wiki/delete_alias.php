<?
authorize();
$DB->query("DELETE FROM wiki_aliases WHERE Alias='".$Alias->convert($_GET['alias'])."'");
$Cache->delete_value('wiki_article_'.$Alias->to_id($_GET['alias']));
$Alias->flush();
?>
