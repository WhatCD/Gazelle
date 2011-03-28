<?
authorize();

include(SERVER_ROOT.'/classes/class_validate.php');
$Val = new VALIDATE;

if(!is_number($_POST['id']) || $_POST['id'] == ''){ error(0); }
$Val->SetFields('title', '1','string','The title must be between 3 and 100 characters',array('maxlength'=>100, 'minlength'=>3));
$Err = $Val->ValidateForm($_POST);
$ArticleID=$_POST['id'];

if($Err) {
	error($Err);
}

$P=array();
$P=db_array($_POST);

$Article = $Alias->article($ArticleID);
list($Revision, $Title, $Body, $CurRead, $CurEdit, $Date, $Author) = array_shift($Article);
if($CurEdit > $LoggedUser['Class']){ error(403); }

if(check_perms('admin_manage_wiki')){
	$Read=$_POST['minclassread'];
	$Edit=$_POST['minclassedit'];
	if(!is_number($Read)) { error(0); } //int?
	if(!is_number($Edit)) { error(0); }
	if($Edit > $LoggedUser['Class']){ error('You can\'t restrict articles above your own level.'); }
	if($Edit < $Read){ $Edit = $Read; } //Human error fix.
}

$MyRevision=$_POST['revision'];
if($MyRevision!=$Revision){ error('This article has already been modified from its original version.'); }

$DB->query("INSERT INTO wiki_revisions (ID, Revision, Title, Body, Date, Author) VALUES ('".db_string($ArticleID)."', '".db_string($Revision)."', '".db_string($Title)."', '".db_string($Body)."', '".db_string($Date)."', '".db_string($Author)."')");
$SQL = "UPDATE wiki_articles SET
			Revision='".db_string($Revision+1)."',
			Title='$P[title]',
			Body='$P[body]',";
if($Read && $Edit) {
	$SQL .= "MinClassRead='$Read',
			MinClassEdit='$Edit',";
}
$SQL .= "Date='".sqltime()."',
			Author='$LoggedUser[ID]'
			WHERE ID='$P[id]'";
$DB->query($SQL);
$Cache->delete_value('wiki_article_'.$ArticleID);
header('Location: wiki.php?action=article&id='.$ArticleID);
?>
