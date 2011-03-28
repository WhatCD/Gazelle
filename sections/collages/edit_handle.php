<?
authorize();

$CollageID = $_POST['collageid'];
if(!is_number($CollageID)) { error(0); }

$DB->query("SELECT UserID, CategoryID FROM collages WHERE ID='$CollageID'");
list($UserID, $CategoryID) = $DB->next_record();
if($CategoryID == 0 && $UserID!=$LoggedUser['ID'] && !check_perms('site_collages_delete')) { error(403); }



$TagList = explode(',',$_POST['tags']);
foreach($TagList as $ID=>$Tag) {
	$TagList[$ID] = sanitize_tag($Tag);
}
$TagList = implode(' ',$TagList);

$DB->query("UPDATE collages SET Description='".db_string($_POST['description'])."', TagList='$TagList' WHERE ID='$CollageID'");

if (check_perms('site_collages_delete')) {
	$DB->query("UPDATE collages SET Name='".db_string($_POST['name'])."' WHERE ID='$CollageID'");
}

if(!empty($_POST['category']) && !empty($CollageCats[$_POST['category']]) && $_POST['category']!=$CategoryID && $_POST['category']!=0) {
	$DB->query("UPDATE collages SET CategoryID='".db_string($_POST['category'])."' WHERE ID='$CollageID'");
}

$Cache->delete_value('collage_'.$CollageID);
header('Location: collages.php?id='.$CollageID);
?>
