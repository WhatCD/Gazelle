<?
authorize();

if (preg_match('/^'.SITELINK_REGEX.'\/wiki\.php\?action=article\&id=([0-9]+)/i',$_POST['url'],$Match)) {
	$ArticleID = $Match[2];
}
if (preg_match('/^'.SITELINK_REGEX.'\/wiki\.php\?action=article\&name=(.+)/i',$_POST['url'],$Match)) {
	$ArticleID = $Alias->to_id($Match[2]);
}
if (!$ArticleID) {
	error('Unable to link alias to an article.');
}
$NewAlias = $Alias->convert($_POST['alias']);
if ($NewAlias != '') {
	$DB->query("INSERT INTO wiki_aliases (Alias, ArticleID) VALUES ('$NewAlias', '$ArticleID')");
	$Alias->flush();
}

header('Location: wiki.php?action=article&id='.$ArticleID);
?>
