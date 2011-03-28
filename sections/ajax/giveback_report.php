<?
if(!check_perms('admin_reports')) {
	error(403);
}

if(!is_number($_GET['id'])) {
	error(0);
}

$DB->query("SELECT Status FROM reportsv2 WHERE ID=".$_GET['id']);
list($Status) = $DB->next_record();
if(isset($Status)) {
		$DB->query("UPDATE reportsv2 SET Status='New', ResolverID = 0 WHERE ID=".$_GET['id']);
}
?>
