<?
authorize();

if(!check_perms('admin_whitelist')) {
	error(403);
}

if($_POST['submit'] == 'Delete'){
	if(!is_number($_POST['id']) || $_POST['id'] == ''){
		error("1");
	}
	
	$DB->query("SELECT peer_id FROM xbt_client_whitelist WHERE id = ".$_POST['id']);
	list($PeerID) = $DB->next_record();
	$DB->query('DELETE FROM xbt_client_whitelist WHERE id='.$_POST['id']);
	update_tracker('remove_whitelist', array('peer_id' => $PeerID));
} else { //Edit & Create, Shared Validation
	
	if(empty($_POST['client']) || empty($_POST['peer_id'])) {
		print_r($_POST);
		die();
	}
	
	$Client = db_string($_POST['client']);
	$PeerID = db_string($_POST['peer_id']);

	if($_POST['submit'] == 'Edit'){ //Edit
		if(empty($_POST['id']) || !is_number($_POST['id'])) {
			error("3");
		} else {
			$DB->query("SELECT peer_id FROM xbt_client_whitelist WHERE id = ".$_POST['id']);
			list($OldPeerID) = $DB->next_record();
			$DB->query("UPDATE xbt_client_whitelist SET
				vstring='".$Client."',
				peer_id='".$PeerID."'
				WHERE ID=".$_POST['id']);
			update_tracker('edit_whitelist', array('old_peer_id' => $OldPeerID, 'new_peer_id' => $PeerID));
		}
	} else { //Create
		$DB->query("INSERT INTO xbt_client_whitelist
			(vstring, peer_id) 
		VALUES
			('".$Client."','".$PeerID."')");
		update_tracker('add_whitelist', array('peer_id' => $PeerID));
	}
}

$Cache->delete('whitelisted_clients');

// Go back
header('Location: tools.php?action=whitelist')
?>
