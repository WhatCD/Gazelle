<?
enforce_login();

if (!$_GET['postid'] || !is_number($_GET['postid'])) {
	error(0);
}

$PostID = (int)$_GET['postid'];
$DB->query("
	SELECT Body
	FROM comments
	WHERE ID = $PostID");
list($Body) = $DB->next_record(MYSQLI_NUM);

echo trim($Body);