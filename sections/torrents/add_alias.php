<?
authorize();

$UserID = $LoggedUser['ID'];
$GroupID = db_string($_POST['groupid']);
$Importances = $_POST['importance'];
$AliasNames = $_POST['aliasname'];

if(!is_number($GroupID) || !$GroupID) {
	error(0);
}

$Changed = false;

for($i = 0; $i < count($AliasNames); $i++) {
	$AliasName = normalise_artist_name($AliasNames[$i]);
	$Importance = $Importances[$i];
	
	if($Importance!='1' && $Importance!='2' && $Importance!='3' && $Importance!='4' && $Importance!='5' && $Importance!='6' && $Importance!='7') {
		break;
	}
	
	if(strlen($AliasName) > 0) {
		$DB->query("SELECT AliasID, ArtistID, Redirect, Name FROM artists_alias WHERE Name LIKE '".db_string($AliasName,true)."'");
		if($DB->record_count() == 0) {
			$AliasName = db_string($AliasName);
			$DB->query("INSERT INTO artists_group (Name) VALUES ('$AliasName')");
			$ArtistID = $DB->inserted_id();
			$DB->query("INSERT INTO artists_alias (ArtistID, Name) VALUES ('$ArtistID', '$AliasName')");
			$AliasID = $DB->inserted_id();
		} else {
			list($AliasID, $ArtistID, $Redirect, $AliasName) = $DB->next_record();
			if($Redirect) {
				$AliasID = $Redirect;
			}
		}
		
		$DB->query("SELECT Name FROM torrents_group WHERE ID=".$GroupID);
		list($GroupName) = $DB->next_record();

		$DB->query("SELECT Name FROM artists_group WHERE ArtistID=".$ArtistID);
		list($ArtistName) = $DB->next_record();
		
		
		$DB->query("INSERT IGNORE INTO torrents_artists 
		(GroupID, ArtistID, AliasID, Importance, UserID) VALUES 
		('$GroupID', '$ArtistID', '$AliasID', '$Importance', '$UserID')");
		
		if ($DB->affected_rows()) {
			$Changed = true;
			$DB->query("INSERT INTO torrents_group (ID, NumArtists) 
					SELECT ta.GroupID, COUNT(ta.ArtistID) 
					FROM torrents_artists AS ta 
					WHERE ta.GroupID='$GroupID' 
					AND ta.Importance='1'
					GROUP BY ta.GroupID 
				ON DUPLICATE KEY UPDATE 
				NumArtists=VALUES(NumArtists);");
			
			write_log("Artist ".$ArtistID." (".$ArtistName.") was added to the group ".$GroupID." (".$GroupName.") as ".$ArtistTypes[$Importance]." by user ".$LoggedUser['ID']." (".$LoggedUser['Username'].")");
			write_group_log($GroupID, 0, $LoggedUser['ID'], "added artist ".$ArtistName." as ".$ArtistTypes[$Importance], 0);
		}
	}
}

if($Changed) {
	$Cache->delete_value('torrents_details_'.$GroupID);
	$Cache->delete_value('groups_artists_'.$GroupID); // Delete group artist cache
	$Cache->delete_value('artist_'.$ArtistID);
	update_hash($GroupID);
}

header('Location: '.$_SERVER['HTTP_REFERER']);
?>
