<?
include(SERVER_ROOT . '/classes/class_text.php');
include(SERVER_ROOT . '/classes/class_alias.php');
$Text = new TEXT(true);
$Alias = new ALIAS;


if (!empty($_GET['id']) && is_number($_GET['id'])) { //Visiting article via ID
	$ArticleID = $_GET['id'];
} elseif ($_GET['name'] != '') { //Retrieve article ID via alias.
	$ArticleID = $Alias->to_id($_GET['name']);
} else {
	print json_encode(
		array(
			'status' => 'error',
		)
	);
	die();
}

if (!$ArticleID) { //No article found
	print json_encode(
		array(
			'status' => 'not found',
		)
	);
	die();
}
$Article = $Alias->article($ArticleID, false);

if (!$Article) {
	print json_encode(
		array(
			'status' => 'not found',
		)
	);
	die();
}
list($Revision, $Title, $Body, $Read, $Edit, $Date, $AuthorID, $AuthorName, $Aliases, $UserIDs) = array_shift($Article);
if ($Read > $LoggedUser['EffectiveClass']) {
	print json_encode(
		array(
			'status' => 'You must be a higher user class to view this wiki article',
		)
	);
	die();
}

$TextBody = $Text->full_format($Body, false);

print json_encode(
	array(
		'status' => 'success',
		'response' => array(
			'title' => $Title,
			'bbBody' => $Body,
			'body' => $TextBody,
			'aliases' => $Aliases,
			'authorID' => (int)$AuthorID,
			'authorName' => $AuthorName,
			'date' => $Date,
			'revision' => (int)$Revision
		)
	));
?>