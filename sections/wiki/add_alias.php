<?
authorize();

if (!isset($_POST['article']) || !is_number($_POST['article'])) {
	error(0);
}

$ArticleID = (int)$_POST['article'];

$DB->query("SELECT MinClassEdit FROM wiki_articles WHERE ID = $ArticleID");
list($MinClassEdit) = $DB->next_record();
if ($MinClassEdit > $LoggedUser['EffectiveClass']) {
	error(403);
}

$NewAlias = Wiki::normalize_alias($_POST['alias']);
$Dupe = Wiki::alias_to_id($_POST['alias']);

if ($NewAlias != '' && $NewAlias!='addalias' && $Dupe === false) { //Not null, and not dupe
	$DB->query("INSERT INTO wiki_aliases (Alias, UserID, ArticleID) VALUES ('$NewAlias', '$LoggedUser[ID]', '$ArticleID')");
} else {
	error('The alias you attempted to add was either null or already in the database.');
}

Wiki::flush_aliases();
Wiki::flush_article($ArticleID);
header('Location: wiki.php?action=article&id='.$ArticleID);
