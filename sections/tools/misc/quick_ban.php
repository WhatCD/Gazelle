<?php

if(!check_perms('admin_manage_ipbans')) { error(403); }
	if(isset($_GET['perform'])) {
		if($_GET['perform'] == 'delete') {
                	if(!is_number($_GET['id']) || $_GET['id'] == ''){ error(0); }
                	$DB->query('DELETE FROM ip_bans WHERE ID='.$_GET['id']);
			$Bans = $Cache->delete_value('ip_bans');
		}
		elseif($_GET['perform'] == 'create') {
			$Notes = db_string($_GET['notes']);
        		$IP = ip2unsigned($_GET['ip']); //Sanitized by Validation regex
                        $DB->query("INSERT INTO ip_bans
                                (FromIP, ToIP, Reason) VALUES
                                ('$IP','$IP', '$Notes')");
                        $ID = $DB->inserted_id();
                        $Bans = $Cache->get_value('ip_bans');
                        $Bans[$ID] = array($ID, $Start, $End);
                        $Cache->cache_value('ip_bans', $Bans, 0);
        }
}


?>
