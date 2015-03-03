<?
//******************************************************************************//
//--------------- Take edit ----------------------------------------------------//
// This pages handles the backend of the 'edit torrent' function. It checks		//
// the data, and if it all validates, it edits the values in the database		//
// that correspond to the torrent in question.									//
//******************************************************************************//

enforce_login();
authorize();

require(SERVER_ROOT.'/classes/validate.class.php');

$Validate = new VALIDATE;

//******************************************************************************//
//--------------- Set $Properties array ----------------------------------------//
// This is used if the form doesn't validate, and when the time comes to enter	//
// it into the database.														//
//******************************************************************************//

$Properties=array();
$TypeID = (int)$_POST['type'];
$Type = $Categories[$TypeID-1];
$TorrentID = (int)$_POST['torrentid'];
$Properties['Remastered'] = (isset($_POST['remaster']))? 1 : 0;
if ($Properties['Remastered']) {
	$Properties['UnknownRelease'] = (isset($_POST['unknown'])) ? 1 : 0;
	$Properties['RemasterYear'] = $_POST['remaster_year'];
	$Properties['RemasterTitle'] = $_POST['remaster_title'];
	$Properties['RemasterRecordLabel'] = $_POST['remaster_record_label'];
	$Properties['RemasterCatalogueNumber'] = $_POST['remaster_catalogue_number'];
}
if (!$Properties['Remastered']) {
	$Properties['UnknownRelease'] = 0;
	$Properties['RemasterYear'] = '';
	$Properties['RemasterTitle'] = '';
	$Properties['RemasterRecordLabel'] = '';
	$Properties['RemasterCatalogueNumber'] = '';
}
$Properties['Scene'] = (isset($_POST['scene']))? 1 : 0;
$Properties['HasLog'] = (isset($_POST['flac_log']))? 1 : 0;
$Properties['HasCue'] = (isset($_POST['flac_cue']))? 1 : 0;
$Properties['BadTags'] = (isset($_POST['bad_tags']))? 1 : 0;
$Properties['BadFolders'] = (isset($_POST['bad_folders']))? 1 : 0;
$Properties['BadFiles'] = (isset($_POST['bad_files'])) ? 1 : 0;
$Properties['CassetteApproved'] = (isset($_POST['cassette_approved']))? 1 : 0;
$Properties['LossymasterApproved'] = (isset($_POST['lossymaster_approved']))? 1 : 0;
$Properties['LossywebApproved'] = (isset($_POST['lossyweb_approved'])) ? 1 : 0;
$Properties['LibraryUpload'] = (isset($_POST['library_upload']))? 1 : 0;
$Properties['LibraryPoints'] = (isset($_POST['library_points']))? $_POST['library_points'] : 0;
$Properties['Format'] = $_POST['format'];
$Properties['Media'] = $_POST['media'];
$Properties['Bitrate'] = $_POST['bitrate'];
$Properties['Encoding'] = $_POST['bitrate'];
$Properties['Trumpable'] = (isset($_POST['make_trumpable'])) ? 1 : 0;
$Properties['TorrentDescription'] = $_POST['release_desc'];
$Properties['Name'] = $_POST['title'];
if ($_POST['album_desc']) {
	$Properties['GroupDescription'] = $_POST['album_desc'];
}
if (check_perms('torrents_freeleech')) {
	$Free = (int)$_POST['freeleech'];
	if (!in_array($Free, array(0, 1, 2))) {
		error(404);
	}
	$Properties['FreeLeech'] = $Free;

	if ($Free == 0) {
		$FreeType = 0;
	} else {
		$FreeType = (int)$_POST['freeleechtype'];
		if (!in_array($Free, array(0, 1, 2, 3))) {
			error(404);
		}
	}
	$Properties['FreeLeechType'] = $FreeType;
}

//******************************************************************************//
//--------------- Validate data in edit form -----------------------------------//

$DB->query("
	SELECT UserID, Remastered, RemasterYear, FreeTorrent
	FROM torrents
	WHERE ID = $TorrentID");
if (!$DB->has_results()) {
	error(404);
}
list($UserID, $Remastered, $RemasterYear, $CurFreeLeech) = $DB->next_record(MYSQLI_BOTH, false);

if ($LoggedUser['ID'] != $UserID && !check_perms('torrents_edit')) {
	error(403);
}

if ($Remastered == '1' && !$RemasterYear && !check_perms('edit_unknowns')) {
	error(403);
}

if ($Properties['UnknownRelease'] && !($Remastered == '1' && !$RemasterYear) && !check_perms('edit_unknowns')) {
	//It's Unknown now, and it wasn't before
	if ($LoggedUser['ID'] != $UserID) {
		//Hax
		die();
	}
}

$Validate->SetFields('type', '1', 'number', 'Not a valid type.', array('maxlength' => count($Categories), 'minlength' => 1));
switch ($Type) {
	case 'Music':
		if (!empty($Properties['Remastered']) && !$Properties['UnknownRelease']) {
			$Validate->SetFields('remaster_year', '1', 'number', 'Year of remaster/re-issue must be entered.');
		} else {
			$Validate->SetFields('remaster_year', '0','number', 'Invalid remaster year.');
		}

		if (!empty($Properties['Remastered']) && !$Properties['UnknownRelease'] && $Properties['RemasterYear'] < 1982 && $Properties['Media'] == 'CD') {
			error('You have selected a year for an album that predates the medium you say it was created on.');
			header("Location: torrents.php?action=edit&id=$TorrentID");
			die();
		}

		$Validate->SetFields('remaster_title', '0', 'string', 'Remaster title must be between 2 and 80 characters.', array('maxlength' => 80, 'minlength' => 2));

		if ($Properties['RemasterTitle'] == 'Original Release') {
			error('"Original Release" is not a valid remaster title.');
			header("Location: torrents.php?action=edit&id=$TorrentID");
			die();
		}

		$Validate->SetFields('remaster_record_label', '0', 'string', 'Remaster record label must be between 2 and 80 characters.', array('maxlength' => 80, 'minlength' => 2));

		$Validate->SetFields('remaster_catalogue_number', '0', 'string', 'Remaster catalogue number must be between 2 and 80 characters.', array('maxlength' => 80, 'minlength' => 2));


		$Validate->SetFields('format', '1', 'inarray', 'Not a valid format.', array('inarray' => $Formats));

		$Validate->SetFields('bitrate', '1', 'inarray', 'You must choose a bitrate.', array('inarray' => $Bitrates));


		// Handle 'other' bitrates
		if ($Properties['Encoding'] == 'Other') {
			$Validate->SetFields('other_bitrate', '1', 'text', 'You must enter the other bitrate (max length: 9 characters).', array('maxlength' => 9));
			$enc = trim($_POST['other_bitrate']);
			if (isset($_POST['vbr'])) {
				$enc .= ' (VBR)';
			}

			$Properties['Encoding'] = $enc;
			$Properties['Bitrate'] = $enc;
		} else {
			$Validate->SetFields('bitrate', '1', 'inarray', 'You must choose a bitrate.', array('inarray' => $Bitrates));
		}

		$Validate->SetFields('media', '1', 'inarray', 'Not a valid media.', array('inarray' => $Media));

		$Validate->SetFields('release_desc', '0', 'string', 'Invalid release description.', array('maxlength' => 1000000, 'minlength' => 0));

		break;

	case 'Audiobooks':
	case 'Comedy':
		/*$Validate->SetFields('title', '1', 'string', 'Title must be between 2 and 300 characters.', array('maxlength' => 300, 'minlength' => 2));
		^ this is commented out because there is no title field on these pages*/
		$Validate->SetFields('year', '1', 'number', 'The year of the release must be entered.');

		$Validate->SetFields('format', '1', 'inarray', 'Not a valid format.', array('inarray' => $Formats));

		$Validate->SetFields('bitrate', '1', 'inarray', 'You must choose a bitrate.', array('inarray' => $Bitrates));


		// Handle 'other' bitrates
		if ($Properties['Encoding'] == 'Other') {
			$Validate->SetFields('other_bitrate', '1', 'text', 'You must enter the other bitrate (max length: 9 characters).', array('maxlength' => 9));
			$enc = trim($_POST['other_bitrate']);
			if (isset($_POST['vbr'])) {
				$enc .= ' (VBR)';
			}

			$Properties['Encoding'] = $enc;
			$Properties['Bitrate'] = $enc;
		} else {
			$Validate->SetFields('bitrate', '1', 'inarray', 'You must choose a bitrate.', array('inarray' => $Bitrates));
		}

		$Validate->SetFields('release_desc', '0', 'string', 'The release description has a minimum length of 10 characters.', array('maxlength' => 1000000, 'minlength' => 10));

		break;

	case 'Applications':
	case 'Comics':
	case 'E-Books':
	case 'E-Learning Videos':
		/*$Validate->SetFields('title', '1', 'string', 'Title must be between 2 and 300 characters.', array('maxlength' => 300, 'minlength' => 2));
			^ this is commented out because there is no title field on these pages*/
		break;
}

$Err = $Validate->ValidateForm($_POST); // Validate the form

if ($Properties['Remastered'] && !$Properties['RemasterYear']) {
	//Unknown Edit!
	if ($LoggedUser['ID'] == $UserID || check_perms('edit_unknowns')) {
		//Fine!
	} else {
		$Err = "You may not edit someone else's upload to unknown release.";
	}
}

// Strip out Amazon's padding
$AmazonReg = '/(http:\/\/ecx.images-amazon.com\/images\/.+)(\._.*_\.jpg)/i';
$Matches = array();
if (preg_match($RegX, $Properties['Image'], $Matches)) {
	$Properties['Image'] = $Matches[1].'.jpg';
}
ImageTools::blacklisted($Properties['Image']);

if ($Err) { // Show the upload form, with the data the user entered
	if (check_perms('site_debug')) {
		die($Err);
	}
	error($Err);
}


//******************************************************************************//
//--------------- Make variables ready for database input ----------------------//

// Shorten and escape $Properties for database input
$T = array();
foreach ($Properties as $Key => $Value) {
	$T[$Key] = "'".db_string(trim($Value))."'";
	if (!$T[$Key]) {
		$T[$Key] = null;
	}
}


//******************************************************************************//
//--------------- Start database stuff -----------------------------------------//

$DBTorVals = array();
$DB->query("
	SELECT Media, Format, Encoding, RemasterYear, Remastered, RemasterTitle, RemasterRecordLabel, RemasterCatalogueNumber, Scene, Description
	FROM torrents
	WHERE ID = $TorrentID");
$DBTorVals = $DB->to_array(false, MYSQLI_ASSOC);
$DBTorVals = $DBTorVals[0];
$LogDetails = '';
foreach ($DBTorVals as $Key => $Value) {
	$Value = "'$Value'";
	if ($Value != $T[$Key]) {
		if (!isset($T[$Key])) {
			continue;
		}
		if ((empty($Value) && empty($T[$Key])) || ($Value == "'0'" && $T[$Key] == "''")) {
			continue;
		}
		if ($LogDetails == '') {
			$LogDetails = "$Key: $Value -> ".$T[$Key];
		} else {
			$LogDetails = "$LogDetails, $Key: $Value -> ".$T[$Key];
		}
	}
}

// Update info for the torrent
$SQL = "
	UPDATE torrents
	SET
		Media = $T[Media],
		Format = $T[Format],
		Encoding = $T[Encoding],
		RemasterYear = $T[RemasterYear],
		Remastered = $T[Remastered],
		RemasterTitle = $T[RemasterTitle],
		RemasterRecordLabel = $T[RemasterRecordLabel],
		RemasterCatalogueNumber = $T[RemasterCatalogueNumber],
		Scene = $T[Scene],";

if (check_perms('torrents_freeleech')) {
	$SQL .= "FreeTorrent = $T[FreeLeech],";
	$SQL .= "FreeLeechType = $T[FreeLeechType],";
}

if (check_perms('users_mod')) {
	if ($T[Format] != "'FLAC'") {
		$SQL .= "
			HasLog = '0',
			HasCue = '0',";
	} else {
		$SQL .= "
			HasLog = $T[HasLog],
			HasCue = $T[HasCue],";
	}

	$DB->query("
		SELECT TorrentID
		FROM torrents_bad_tags
		WHERE TorrentID = '$TorrentID'");
	list($btID) = $DB->next_record();

	if (!$btID && $Properties['BadTags']) {
		$DB->query("
			INSERT INTO torrents_bad_tags
			VALUES ($TorrentID, $LoggedUser[ID], '".sqltime()."')");
	}
	if ($btID && !$Properties['BadTags']) {
		$DB->query("
			DELETE FROM torrents_bad_tags
			WHERE TorrentID = '$TorrentID'");
	}

	$DB->query("
		SELECT TorrentID
		FROM torrents_bad_folders
		WHERE TorrentID = '$TorrentID'");
	list($bfID) = $DB->next_record();

	if (!$bfID && $Properties['BadFolders']) {
		$DB->query("
			INSERT INTO torrents_bad_folders
			VALUES ($TorrentID, $LoggedUser[ID], '".sqltime()."')");
	}
	if ($bfID && !$Properties['BadFolders']) {
		$DB->query("
			DELETE FROM torrents_bad_folders
			WHERE TorrentID = '$TorrentID'");
	}

	$DB->query("
		SELECT TorrentID
		FROM torrents_bad_files
		WHERE TorrentID = '$TorrentID'");
	list($bfiID) = $DB->next_record();

	if (!$bfiID && $Properties['BadFiles']) {
		$DB->query("
			INSERT INTO torrents_bad_files
			VALUES ($TorrentID, $LoggedUser[ID], '".sqltime()."')");
	}
	if ($bfiID && !$Properties['BadFiles']) {
		$DB->query("
			DELETE FROM torrents_bad_files
			WHERE TorrentID = '$TorrentID'");
	}

	$DB->query("
		SELECT TorrentID
		FROM library_contest
		WHERE TorrentID = '$TorrentID'");
	list($lbID) = $DB->next_record();
	if (!$lbID && $Properties['LibraryUpload'] && $Properties['LibraryPoints'] > 0) {
		$DB->query("
			SELECT UserID
			FROM torrents
			WHERE ID = $TorrentID");
		list($UploaderID) = $DB->next_record();
		$DB->query("
			INSERT INTO library_contest
			VALUES ($UploaderID, $TorrentID, $Properties[LibraryPoints])");
	}
	if ($lbID && !$Properties['LibraryUpload']) {
		$DB->query("
			DELETE FROM library_contest
			WHERE TorrentID = '$TorrentID'");
	}

	$DB->query("
		SELECT TorrentID
		FROM torrents_cassette_approved
		WHERE TorrentID = '$TorrentID'");
	list($caID) = $DB->next_record();

	if (!$caID && $Properties['CassetteApproved']) {
		$DB->query("
			INSERT INTO torrents_cassette_approved
			VALUES ($TorrentID, $LoggedUser[ID], '".sqltime()."')");
	}
	if ($caID && !$Properties['CassetteApproved']) {
		$DB->query("
			DELETE FROM torrents_cassette_approved
			WHERE TorrentID = '$TorrentID'");
	}

	$DB->query("
		SELECT TorrentID
		FROM torrents_lossymaster_approved
		WHERE TorrentID = '$TorrentID'");
	list($lmaID) = $DB->next_record();

	if (!$lmaID && $Properties['LossymasterApproved']) {
		$DB->query("
			INSERT INTO torrents_lossymaster_approved
			VALUES ($TorrentID, $LoggedUser[ID], '".sqltime()."')");
	}
	if ($lmaID && !$Properties['LossymasterApproved']) {
		$DB->query("
			DELETE FROM torrents_lossymaster_approved
			WHERE TorrentID = '$TorrentID'");
	}

	$DB->query("
		SELECT TorrentID
		FROM torrents_lossyweb_approved
		WHERE TorrentID = '$TorrentID'");
	list($lwID) = $DB->next_record();
	if (!$lwID && $Properties['LossywebApproved']) {
		$DB->query("
			INSERT INTO torrents_lossyweb_approved
			VALUES ($TorrentID, $LoggedUser[ID], '".sqltime()."')");
	}
	if ($lwID && !$Properties['LossywebApproved']) {
		$DB->query("
			DELETE FROM torrents_lossyweb_approved
			WHERE TorrentID = '$TorrentID'");
	}
}

$SQL .= "
		Description = $T[TorrentDescription]
	WHERE ID = $TorrentID";
$DB->query($SQL);

if (check_perms('torrents_freeleech') && $Properties['FreeLeech'] != $CurFreeLeech) {
	Torrents::freeleech_torrents($TorrentID, $Properties['FreeLeech'], $Properties['FreeLeechType']);
}

$DB->query("
	SELECT GroupID, Time
	FROM torrents
	WHERE ID = '$TorrentID'");
list($GroupID, $Time) = $DB->next_record();

// Competition
if (strtotime($Time) > 1241352173) {
	if ($_POST['log_score'] == '100') {
		$DB->query("
			INSERT IGNORE into users_points (GroupID, UserID, Points)
			VALUES ('$GroupID', '$UserID', '1')");
	}
}
// End competiton

$DB->query("
	SELECT LogScore
	FROM torrents
	WHERE ID = $TorrentID");
list($LogScore) = $DB->next_record();
if ($Properties['Trumpable'] == 1 && $LogScore == 100) {
	$DB->query("
		UPDATE torrents
		SET LogScore = 99
		WHERE ID = $TorrentID");
	$Results = array();
	$Results[] = 'The original uploader has chosen to allow this log to be deducted one point for using EAC v0.95., -1 point [1]';
	$Details = db_string(serialize($Results));
	$DB->query("
		UPDATE torrents_logs_new
		SET Score = 99, Details = '$Details'
		WHERE TorrentID = $TorrentID");
}

$DB->query("
	SELECT Enabled
	FROM users_main
	WHERE ID = $UserID");
list($Enabled) = $DB->next_record();
if ($Properties['Trumpable'] == 0 && $LogScore == 99 && $Enabled == 1 && strtotime($Time) < 1284422400) {
	$DB->query("
		SELECT Log
		FROM torrents_logs_new
		WHERE TorrentID = $TorrentID");
	list($Log) = $DB->next_record();
	if (strpos($Log, 'EAC extraction') === 0) {
		$DB->query("
			UPDATE torrents
			SET LogScore = 100
			WHERE ID = $TorrentID");
		$DB->query("
			UPDATE torrents_logs_new
			SET Score = 100, Details = ''
			WHERE TorrentID = $TorrentID");
	}
}

$DB->query("
	SELECT Name
	FROM torrents_group
	WHERE ID = $GroupID");
list($Name) = $DB->next_record(MYSQLI_NUM, false);

Misc::write_log("Torrent $TorrentID ($Name) in group $GroupID was edited by ".$LoggedUser['Username']." ($LogDetails)"); // TODO: this is probably broken
Torrents::write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], $LogDetails, 0);
$Cache->delete_value("torrents_details_$GroupID");
$Cache->delete_value("torrent_download_$TorrentID");

Torrents::update_hash($GroupID);
// All done!

header("Location: torrents.php?id=$GroupID");
?>
