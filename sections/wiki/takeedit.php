<?
authorize();

if (!isset($_POST['id']) || !is_number($_POST['id'])) {
	error(0);
}
$ArticleID = (int)$_POST['id'];

include(SERVER_ROOT.'/classes/validate.class.php');
$Val = new VALIDATE;
$Val->SetFields('title', '1', 'string', 'The title must be between 3 and 100 characters', array('maxlength' => 100, 'minlength' => 3));
$Err = $Val->ValidateForm($_POST);
if ($Err) {
	error($Err);
}

$P = array();
$P = db_array($_POST);

$Article = Wiki::get_article($ArticleID);
list($OldRevision, $OldTitle, $OldBody, $CurRead, $CurEdit, $OldDate, $OldAuthor) = array_shift($Article);
if ($CurEdit > $LoggedUser['EffectiveClass']) {
	error(403);
}

if (check_perms('admin_manage_wiki')) {
	$Read=$_POST['minclassread'];
	$Edit=$_POST['minclassedit'];
	if (!is_number($Read)) {
		error(0); //int?
	}
	if (!is_number($Edit)) {
		error(0);
	}
	if ($Edit > $LoggedUser['EffectiveClass']) {
		error('You can\'t restrict articles above your own level.');
	}
	if ($Edit < $Read) {
		$Edit = $Read; //Human error fix.
	}
}

$MyRevision = $_POST['revision'];
if ($MyRevision != $OldRevision) {
	error('This article has already been modified from its original version.');
}

// Store previous revision
$DB->query("
	INSERT INTO wiki_revisions
		(ID, Revision, Title, Body, Date, Author)
	VALUES
		('".db_string($ArticleID)."', '".db_string($OldRevision)."', '".db_string($OldTitle)."', '".db_string($OldBody)."', '".db_string($OldDate)."', '".db_string($OldAuthor)."')");

// Update wiki entry
$SQL = "
	UPDATE wiki_articles
	SET
		Revision = '".db_string($OldRevision + 1)."',
		Title = '$P[title]',
		Body = '$P[body]',";
if ($Read && $Edit) {
	$SQL .= "
		MinClassRead = '$Read',
		MinClassEdit = '$Edit',";
}
$SQL .= "
		Date = '".sqltime()."',
		Author = '$LoggedUser[ID]'
	WHERE ID = '$P[id]'";
$DB->query($SQL);
Wiki::flush_article($ArticleID);

header("Location: wiki.php?action=article&id=$ArticleID");
