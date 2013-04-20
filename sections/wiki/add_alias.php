<?
authorize();

//TODO, check that loggeduser > edit
if (!is_number($_POST['article']) || $_POST['article'] == '') {
	error(0);
}

$ArticleID = $_POST['article'];
$NewAlias = $Alias->convert($_POST['alias']);
$Dupe = $Alias->to_id($_POST['alias']);

if ($NewAlias != '' && $NewAlias!='addalias' && !$Dupe) { //Not null, and not dupe
	$DB->query("INSERT INTO wiki_aliases (Alias, UserID, ArticleID) VALUES ('$NewAlias', '$LoggedUser[ID]', '$ArticleID')");
	$Alias->flush();
} else {
	error('The alias you attempted to add was either null or already in the database.');
}

$Cache->delete_value('wiki_article_'.$ArticleID);
header('Location: wiki.php?action=article&id='.$ArticleID);
