<?
if(!check_perms('admin_reports')) {
	error(403);
}

if(!is_number($_GET['id'])) {
	error(0);
}

$DB->query("UPDATE reportsv2 SET Status='New' WHERE ID=".$_GET['id']." AND Status <> 'Resolved'");
if($DB->affected_rows() > 0) {
		//Win
} else {
		echo 'You just tried to grab a resolved or non existent report!';
}
?>
