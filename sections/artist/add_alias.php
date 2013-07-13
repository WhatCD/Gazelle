<?
authorize();

if (!check_perms('torrents_edit')) {
	error(403);
}
$ArtistID = $_POST['artistid'];
$Redirect = $_POST['redirect'];
$AliasName = Artists::normalise_artist_name($_POST['name']);
$DBAliasName = db_string($AliasName);
if (!$Redirect) {
	$Redirect = 0;
}

if (!is_number($ArtistID) || !($Redirect === 0 || is_number($Redirect)) || !$ArtistID) {
	error(0);
}

if ($AliasName == '') {
	error('Blank artist name.');
}

/*
 * In the case of foo, who released an album before changing his name to bar and releasing another
 * the field shared to make them appear on the same artist page is the ArtistID
 * 1. For a normal artist, there'll be one entry, with the ArtistID, the same name as the artist and a 0 redirect
 * 2. For Celine Dion (C�line Dion), there's two, same ArtistID, diff Names, one has a redirect to the alias of the first
 * 3. For foo, there's two, same ArtistID, diff names, no redirect
 */

$DB->query("
	SELECT AliasID, ArtistID, Name, Redirect
	FROM artists_alias
	WHERE Name = '$DBAliasName'");
if ($DB->has_results()) {
	while (list($CloneAliasID, $CloneArtistID, $CloneAliasName, $CloneRedirect) = $DB->next_record(MYSQLI_NUM, false)) {
		if (!strcasecmp($CloneAliasName, $AliasName)) {
			break;
		}
	}
	if ($CloneAliasID) {
		if ($ArtistID == $CloneArtistID && $Redirect == 0) {
			if ($CloneRedirect != 0) {
				$DB->query("
					UPDATE artists_alias
					SET ArtistID = '$ArtistID', Redirect = 0
					WHERE AliasID = '$CloneAliasID'");
				Misc::write_log("Redirection for the alias $CloneAliasID ($DBAliasName) for the artist $ArtistID was removed by user $LoggedUser[ID] ($LoggedUser[Username])");
			} else {
				error('No changes were made as the target alias did not redirect anywhere.');
			}
		} else {
			error('An alias by that name already exists <a href="artist.php?id='.$CloneArtistID.'">here</a>. You can try renaming that artist to this one.');
		}
	}
}
if (!$CloneAliasID) {
	if ($Redirect) {
		$DB->query("
			SELECT ArtistID, Redirect
			FROM artists_alias
			WHERE AliasID = $Redirect");
		if (!$DB->has_results()) {
			error('Cannot redirect to a nonexistent artist alias.');
		}
		list($FoundArtistID, $FoundRedirect) = $DB->next_record();
		if ($ArtistID != $FoundArtistID) {
			error('Redirection must target an alias for the current artist.');
		}
		if ($FoundRedirect != 0) {
			$Redirect = $FoundRedirect;
		}
	}
	$DB->query("
		INSERT INTO artists_alias
			(ArtistID, Name, Redirect, UserID)
		VALUES
			($ArtistID, '$DBAliasName', $Redirect, ".$LoggedUser['ID'].')');
	$AliasID = $DB->inserted_id();

	$DB->query("
		SELECT Name
		FROM artists_group
		WHERE ArtistID = $ArtistID");
	list($ArtistName) = $DB->next_record(MYSQLI_NUM, false);

	Misc::write_log("The alias $AliasID ($DBAliasName) was added to the artist $ArtistID (".db_string($ArtistName).') by user '.$LoggedUser['ID'].' ('.$LoggedUser['Username'].')');
}
header('Location: '.$_SERVER['HTTP_REFERER']);
?>
