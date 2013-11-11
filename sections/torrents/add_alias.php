<?
authorize();

$UserID = $LoggedUser['ID'];
$GroupID = db_string($_POST['groupid']);
$Importances = $_POST['importance'];
$AliasNames = $_POST['aliasname'];

if (!is_number($GroupID) || !$GroupID) {
	error(0);
}

$DB->query("
	SELECT Name
	FROM torrents_group
	WHERE ID = $GroupID");
if (!$DB->has_results()) {
	error(404);
}
list($GroupName) = $DB->next_record(MYSQLI_NUM, false);

$Changed = false;

for ($i = 0; $i < count($AliasNames); $i++) {
	$AliasName = Artists::normalise_artist_name($AliasNames[$i]);
	$Importance = $Importances[$i];

	if ($Importance != '1' && $Importance != '2' && $Importance != '3' && $Importance != '4' && $Importance != '5' && $Importance != '6' && $Importance != '7') {
		break;
	}

	if (strlen($AliasName) > 0) {
		$DB->query("
			SELECT AliasID, ArtistID, Redirect, Name
			FROM artists_alias
			WHERE Name = '".db_string($AliasName)."'");
		while (list($AliasID, $ArtistID, $Redirect, $FoundAliasName) = $DB->next_record(MYSQLI_NUM, false)) {
			if (!strcasecmp($AliasName, $FoundAliasName)) {
				if ($Redirect) {
					$AliasID = $Redirect;
				}
				break;
			}
		}
		if (!$AliasID) {
			$AliasName = db_string($AliasName);
			$DB->query("
				INSERT INTO artists_group (Name)
				VALUES ('$AliasName')");
			$ArtistID = $DB->inserted_id();
			$DB->query("
				INSERT INTO artists_alias (ArtistID, Name)
				VALUES ('$ArtistID', '$AliasName')");
			$AliasID = $DB->inserted_id();
		}

		$DB->query("
			SELECT Name
			FROM artists_group
			WHERE ArtistID = $ArtistID");
		list($ArtistName) = $DB->next_record(MYSQLI_NUM, false);


		$DB->query("
			INSERT IGNORE INTO torrents_artists
				(GroupID, ArtistID, AliasID, Importance, UserID)
			VALUES
				('$GroupID', '$ArtistID', '$AliasID', '$Importance', '$UserID')");

		if ($DB->affected_rows()) {
			$Changed = true;
			Misc::write_log("Artist $ArtistID ($ArtistName) was added to the group $GroupID ($GroupName) as ".$ArtistTypes[$Importance].' by user '.$LoggedUser['ID'].' ('.$LoggedUser['Username'].')');
			Torrents::write_group_log($GroupID, 0, $LoggedUser['ID'], "added artist $ArtistName as ".$ArtistTypes[$Importance], 0);
		}
	}
}

if ($Changed) {
	$Cache->delete_value("torrents_details_$GroupID");
	$Cache->delete_value("groups_artists_$GroupID"); // Delete group artist cache
	Torrents::update_hash($GroupID);
}

header('Location: '.$_SERVER['HTTP_REFERER']);
?>
