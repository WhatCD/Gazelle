<?
//******************************************************************************//
//--------------- Take upload --------------------------------------------------//
// This pages handles the backend of the torrent upload function. It checks		//
// the data, and if it all validates, it builds the torrent file, then writes	//
// the data to the database and the torrent to the disk.						//
//******************************************************************************//

// Maximum allowed size for uploaded files.
// http://php.net/upload-max-filesize
ini_set('upload_max_filesize', 2097152); // 2 Mibibytes

ini_set('max_file_uploads', 100);
define(MAX_FILENAME_LENGTH, 180);
include(SERVER_ROOT.'/classes/validate.class.php');
include(SERVER_ROOT.'/classes/feed.class.php');
include(SERVER_ROOT.'/classes/text.class.php');
include(SERVER_ROOT.'/sections/torrents/functions.php');
include(SERVER_ROOT.'/classes/file_checker.class.php');

enforce_login();
authorize();


$Validate = new VALIDATE;
$Feed = new FEED;
$Text = new TEXT;

define('QUERY_EXCEPTION', true); // Shut up debugging

//******************************************************************************//
//--------------- Set $Properties array ----------------------------------------//
// This is used if the form doesn't validate, and when the time comes to enter	//
// it into the database.														//

$Properties = array();
$Type = $Categories[(int)$_POST['type']];
$TypeID = $_POST['type'] + 1;
$Properties['CategoryName'] = $Type;
$Properties['Title'] = $_POST['title'];
$Properties['Remastered'] = isset($_POST['remaster']) ? 1 : 0;
if ($Properties['Remastered'] || isset($_POST['unknown'])) {
	$Properties['UnknownRelease'] = isset($_POST['unknown']) ? 1 : 0;
	$Properties['RemasterYear'] = $_POST['remaster_year'];
	$Properties['RemasterTitle'] = $_POST['remaster_title'];
	$Properties['RemasterRecordLabel'] = $_POST['remaster_record_label'];
	$Properties['RemasterCatalogueNumber'] = $_POST['remaster_catalogue_number'];
}
if (!$Properties['Remastered'] || $Properties['UnknownRelease']) {
	$Properties['UnknownRelease'] = 1;
	$Properties['RemasterYear'] = '';
	$Properties['RemasterTitle'] = '';
	$Properties['RemasterRecordLabel'] = '';
	$Properties['RemasterCatalogueNumber'] = '';
}
$Properties['Year'] = $_POST['year'];
$Properties['RecordLabel'] = $_POST['record_label'];
$Properties['CatalogueNumber'] = $_POST['catalogue_number'];
$Properties['ReleaseType'] = $_POST['releasetype'];
$Properties['Scene'] = isset($_POST['scene']) ? 1 : 0;
$Properties['Format'] = $_POST['format'];
$Properties['Media'] = $_POST['media'];
$Properties['Bitrate'] = $_POST['bitrate'];
$Properties['Encoding'] = $_POST['bitrate'];
$Properties['LibraryImage'] = $_POST['library_image'];
$Properties['MultiDisc'] = $_POST['multi_disc'];
$Properties['TagList'] = $_POST['tags'];
$Properties['Image'] = $_POST['image'];
$Properties['GroupDescription'] = trim($_POST['album_desc']);
if ($_POST['vanity_house'] && check_perms('torrents_edit_vanityhouse') ) {
	$Properties['VanityHouse'] = 1;
} else {
	$Properties['VanityHouse'] = 0;
}
$Properties['TorrentDescription'] = $_POST['release_desc'];
if ($_POST['album_desc']) {
	$Properties['GroupDescription'] = trim($_POST['album_desc']);
} elseif ($_POST['desc']) {
	$Properties['GroupDescription'] = trim($_POST['desc']);
}
$Properties['GroupID'] = $_POST['groupid'];
if (empty($_POST['artists'])) {
	$Err = "You didn't enter any artists";
} else {
	$Artists = $_POST['artists'];
	$Importance = $_POST['importance'];
}
$RequestID = $_POST['requestid'];
//******************************************************************************//
//--------------- Validate data in upload form ---------------------------------//

$Validate->SetFields('type', '1', 'inarray', 'Please select a valid type.', array('inarray' => array_keys($Categories)));
switch ($Type) {
	case 'Music':
		if (!$_POST['groupid']) {
			$Validate->SetFields('title',
				'1','string','Title must be between 1 and 200 characters.', array('maxlength'=>200, 'minlength'=>1));

			$Validate->SetFields('year',
				'1','number','The year of the original release must be entered.', array('length'=>40));

			$Validate->SetFields('releasetype',
				'1','inarray','Please select a valid release type.', array('inarray'=>array_keys($ReleaseTypes)));

			$Validate->SetFields('tags',
				'1','string','You must enter at least one tag. Maximum length is 200 characters.', array('maxlength'=>200, 'minlength'=>2));

			$Validate->SetFields('record_label',
				'0','string','Record label must be between 2 and 80 characters.', array('maxlength'=>80, 'minlength'=>2));

			$Validate->SetFields('catalogue_number',
				'0','string','Catalogue Number must be between 2 and 80 characters.', array('maxlength'=>80, 'minlength'=>2));

			$Validate->SetFields('album_desc',
				'1','string','The album description has a minimum length of 10 characters.', array('maxlength'=>1000000, 'minlength'=>10));

			if ($Properties['Media'] == 'CD' && !$Properties['Remastered']) {
				$Validate->SetFields('year', '1', 'number', 'You have selected a year for an album that predates the media you say it was created on.', array('minlength'=>1982));
			}
		}

		if ($Properties['Remastered'] && !$Properties['UnknownRelease']) {
			$Validate->SetFields('remaster_year',
				'1','number','Year of remaster/re-issue must be entered.');
		} else {
			$Validate->SetFields('remaster_year',
				'0','number','Invalid remaster year.');
		}

		if ($Properties['Media'] == 'CD' && $Properties['Remastered']) {
			$Validate->SetFields('remaster_year', '1', 'number', 'You have selected a year for an album that predates the media you say it was created on.', array('minlength'=>1982));
		}

		$Validate->SetFields('remaster_title',
			'0','string','Remaster title must be between 2 and 80 characters.', array('maxlength'=>80, 'minlength'=>2));
		if ($Properties['RemasterTitle'] == 'Original Release') {
			$Validate->SetFields('remaster_title', '0', 'string', '"Orginal Release" is not a valid remaster title.');
		}

		$Validate->SetFields('remaster_record_label',
			'0','string','Remaster record label must be between 2 and 80 characters.', array('maxlength'=>80, 'minlength'=>2));

		$Validate->SetFields('remaster_catalogue_number',
			'0','string','Remaster catalogue number must be between 2 and 80 characters.', array('maxlength'=>80, 'minlength'=>2));

		$Validate->SetFields('format',
			'1','inarray','Please select a valid format.', array('inarray'=>$Formats));

		// Handle 'other' bitrates
		if ($Properties['Encoding'] == 'Other') {
			if ($Properties['Format'] == 'FLAC') {
				$Validate->SetFields('bitrate',
					'1','string','FLAC bitrate must be lossless.', array('regex'=>'/Lossless/'));
			}

			$Validate->SetFields('other_bitrate',
				'1','string','You must enter the other bitrate (max length: 9 characters).', array('maxlength'=>9));
			$enc = trim($_POST['other_bitrate']);
			if (isset($_POST['vbr'])) {
				$enc.= ' (VBR)';
			}

			$Properties['Encoding'] = $enc;
			$Properties['Bitrate'] = $enc;
		} else {
			$Validate->SetFields('bitrate',
				'1','inarray','You must choose a bitrate.', array('inarray'=>$Bitrates));
		}

		$Validate->SetFields('media',
			'1','inarray','Please select a valid media.', array('inarray'=>$Media));

		$Validate->SetFields('image',
			'0','link','The image URL you entered was invalid.', array('maxlength'=>255, 'minlength'=>12));

		$Validate->SetFields('release_desc',
			'0','string','The release description has a minimum length of 10 characters.', array('maxlength'=>1000000, 'minlength'=>10));

		$Validate->SetFields('groupid', '0', 'number', 'Group ID was not numeric');

		break;

	case 'Audiobooks':
	case 'Comedy':
		$Validate->SetFields('title',
			'1','string','Title must be between 2 and 200 characters.', array('maxlength'=>200, 'minlength'=>2));

		$Validate->SetFields('year',
			'1','number','The year of the release must be entered.');

		$Validate->SetFields('format',
			'1','inarray','Please select a valid format.', array('inarray'=>$Formats));

		if ($Properties['Encoding'] == 'Other') {
			$Validate->SetFields('other_bitrate',
				'1','string','You must enter the other bitrate (max length: 9 characters).', array('maxlength'=>9));
			$enc = trim($_POST['other_bitrate']);
			if (isset($_POST['vbr'])) {
				$enc.= ' (VBR)';
			}

			$Properties['Encoding'] = $enc;
			$Properties['Bitrate'] = $enc;
		} else {
			$Validate->SetFields('bitrate',
				'1','inarray','You must choose a bitrate.', array('inarray'=>$Bitrates));
		}

		$Validate->SetFields('album_desc',
			'1','string','You must enter a proper audiobook description.', array('maxlength'=>1000000, 'minlength'=>10));

		$Validate->SetFields('tags',
			'1','string','You must enter at least one tag. Maximum length is 200 characters.', array('maxlength'=>200, 'minlength'=>2));

		$Validate->SetFields('release_desc',
			'0','string','The release description has a minimum length of 10 characters.', array('maxlength'=>1000000, 'minlength'=>10));

		$Validate->SetFields('image',
			'0','link','The image URL you entered was invalid.', array('maxlength'=>255, 'minlength'=>12));
		break;

	case 'Applications':
	case 'Comics':
	case 'E-Books':
	case 'E-Learning Videos':
		$Validate->SetFields('title',
			'1','string','Title must be between 2 and 200 characters.', array('maxlength'=>200, 'minlength'=>2));

		$Validate->SetFields('tags',
			'1','string','You must enter at least one tag. Maximum length is 200 characters.', array('maxlength'=>200, 'minlength'=>2));

		$Validate->SetFields('release_desc',
			'0','string','The release description has a minimum length of 10 characters.', array('maxlength'=>1000000, 'minlength'=>10));

		$Validate->SetFields('image',
			'0','link','The image URL you entered was invalid.', array('maxlength'=>255, 'minlength'=>12));
		break;
}

$Validate->SetFields('rules',
	'1','require','Your torrent must abide by the rules.');

$Err = $Validate->ValidateForm($_POST); // Validate the form


$File = $_FILES['file_input']; // This is our torrent file
$TorrentName = $File['tmp_name'];

if (!is_uploaded_file($TorrentName) || !filesize($TorrentName)) {
	$Err = 'No torrent file uploaded, or file is empty.';
} elseif (substr(strtolower($File['name']), strlen($File['name']) - strlen('.torrent')) !== '.torrent') {
	$Err = "You seem to have put something other than a torrent file into the upload field. (".$File['name'].").";
}

if ($Type == 'Music') {
	include(SERVER_ROOT.'/sections/upload/get_extra_torrents.php');
}

$LogScoreAverage = 0;

if (!$Err && $Properties['Format'] == 'FLAC') {
	foreach ($_FILES['logfiles']['name'] as $FileName) {
		if (!empty($FileName) && substr(strtolower($FileName), strlen($FileName) - strlen('.log')) !== '.log') {
			$Err = 'You seem to have put something other than an EAC or XLD log file into an upload field. ('.$FileName.').';
			break;
		}
	}
	//There is absolutely no point in checking the type of the file upload as its interpretation of the type is decided by the client.
	/*	foreach ($_FILES['logfiles']['type'] as $FileType) {
			if (!empty($FileType) && $FileType != "text/plain" && $FileType != "text/x-log" && $FileType != "application/octet-stream" && $FileType != "text/richtext") {
				$Err = "You seem to have put something other than an EAC or XLD log file into an upload field. (".$FileType.")";
				break;
			}
		}
	*/
}



//Multiple artists!

$LogName = '';
if (empty($Properties['GroupID']) && empty($ArtistForm) && $Type == 'Music') {
	$MainArtistCount = 0;
	$ArtistNames = array();
	$ArtistForm = array(
		1 => array(),
		2 => array(),
		3 => array(),
		4 => array(),
		5 => array(),
		6 => array()
	);
	for ($i = 0, $il = count($Artists); $i < $il; $i++) {
		if (trim($Artists[$i]) != '') {
			if (!in_array($Artists[$i], trim($ArtistNames))) {
				$ArtistForm[$Importance[$i]][] = array('name' => Artists::normalise_artist_name($Artists[$i]));
				if ($Importance[$i] == 1) {
					$MainArtistCount++;
				}
				$ArtistNames[] = trim($Artists[$i]);
			}
		}
	}
	if ($MainArtistCount < 1) {
		$Err = 'Please enter at least one main artist';
		$ArtistForm = array();
	}
	$LogName .= Artists::display_artists($ArtistForm, false, true, false);
} elseif ($Type == 'Music' && empty($ArtistForm)) {
	$DB->query("
		SELECT ta.ArtistID, aa.Name, ta.Importance
		FROM torrents_artists AS ta
			JOIN artists_alias AS aa ON ta.AliasID = aa.AliasID
		WHERE ta.GroupID = ".$Properties['GroupID']."
		ORDER BY ta.Importance ASC, aa.Name ASC;");
	while (list($ArtistID, $ArtistName, $ArtistImportance) = $DB->next_record(MYSQLI_BOTH, false)) {
		$ArtistForm[$ArtistImportance][] = array('id' => $ArtistID, 'name' => display_str($ArtistName));
		$ArtistsUnescaped[$ArtistImportance][] = array('name' => $ArtistName);
	}
	$LogName .= Artists::display_artists($ArtistsUnescaped, false, true, false);
}


if ($Err) { // Show the upload form, with the data the user entered
	$UploadForm = $Type;
	include(SERVER_ROOT.'/sections/upload/upload.php');
	die();
}

// Strip out Amazon's padding
$AmazonReg = '/(http:\/\/ecx.images-amazon.com\/images\/.+)(\._.*_\.jpg)/i';
$Matches = array();
if (preg_match($RegX, $Properties['Image'], $Matches)) {
	$Properties['Image'] = $Matches[1].'.jpg';
}
ImageTools::blacklisted($Properties['Image']);

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
//--------------- Generate torrent file ----------------------------------------//

$Tor = new BencodeTorrent($TorrentName, true);
$PublicTorrent = $Tor->make_private(); // The torrent is now private.
$TorEnc = db_string($Tor->encode());
$InfoHash = pack('H*', $Tor->info_hash());

$DB->query("
	SELECT ID
	FROM torrents
	WHERE info_hash = '".db_string($InfoHash)."'");
if ($DB->has_results()) {
	list($ID) = $DB->next_record();
	$DB->query("
		SELECT TorrentID
		FROM torrents_files
		WHERE TorrentID = $ID");
	if ($DB->has_results()) {
		$Err = '<a href="torrents.php?torrentid='.$ID.'">The exact same torrent file already exists on the site!</a>';
	} else {
		// A lost torrent
		$DB->query("
			INSERT INTO torrents_files (TorrentID, File)
			VALUES ($ID, '$TorEnc')");
		$Err = '<a href="torrents.php?torrentid='.$ID.'">Thank you for fixing this torrent</a>';
	}
}

if (isset($Tor->Dec['encrypted_files'])) {
	$Err = 'This torrent contains an encrypted file list which is not supported here.';
}

// File list and size
list($TotalSize, $FileList) = $Tor->file_list();
$NumFiles = count($FileList);
$HasLog = 0;
$HasCue = 0;
$TmpFileList = array();
$TooLongPaths = array();
$DirName = (isset($Tor->Dec['info']['files']) ? Format::make_utf8($Tor->get_name()) : '');
check_name($DirName); // check the folder name against the blacklist
foreach ($FileList as $File) {
	list($Size, $Name) = $File;
	// add +log to encoding
	if ($T['Encoding'] == "'Lossless'" && preg_match('/(?<!audiochecker)\.log$/i', $Name)) {
		$HasLog = 1;
	}
	// add +cue to encoding
	if ($T['Encoding'] == "'Lossless'" && preg_match('/\.cue$/i', $Name)) {
		$HasCue = 1;
	}
	// Check file name and extension against blacklist/whitelist
	check_file($Type, $Name);
	// Make sure the filename is not too long
	if (mb_strlen($Name, 'UTF-8') + mb_strlen($DirName, 'UTF-8') + 1 > MAX_FILENAME_LENGTH) {
		$TooLongPaths[] = "$DirName/$Name";
	}
	// Add file info to array
	$TmpFileList[] = Torrents::filelist_format_file($File);
}
if (count($TooLongPaths) > 0) {
	$Names = implode(' <br />', $TooLongPaths);
	$Err = "The torrent contained one or more files with too long a name:<br /> $Names";
}
$FilePath = db_string($DirName);
$FileString = db_string(implode("\n", $TmpFileList));
$Debug->set_flag('upload: torrent decoded');

if ($Type == 'Music') {
	include(SERVER_ROOT.'/sections/upload/generate_extra_torrents.php');
}

if (!empty($Err)) { // Show the upload form, with the data the user entered
	$UploadForm = $Type;
	include(SERVER_ROOT.'/sections/upload/upload.php');
	die();
}

//******************************************************************************//
//--------------- Start database stuff -----------------------------------------//

$Body = $Properties['GroupDescription'];

// Trickery
if (!preg_match('/^'.IMAGE_REGEX.'$/i', $Properties['Image'])) {
	$Properties['Image'] = '';
	$T['Image'] = "''";
}

if ($Type == 'Music') {
	// Does it belong in a group?
	if ($Properties['GroupID']) {
		$DB->query("
			SELECT
				tg.id,
				tg.WikiImage,
				tg.WikiBody,
				tg.RevisionID,
				tg.Name,
				tg.Year,
				tg.ReleaseType,
				tg.TagList
			FROM torrents_group AS tg
			WHERE tg.id = ".$Properties['GroupID']);
		if ($DB->has_results()) {
			// Don't escape tg.Name. It's written directly to the log table
			list($GroupID, $WikiImage, $WikiBody, $RevisionID, $Properties['Title'], $Properties['Year'], $Properties['ReleaseType'], $Properties['TagList']) = $DB->next_record(MYSQLI_NUM, array(4));
			$Properties['TagList'] = str_replace(array(' ', '.', '_'), array(', ', '.', '.'), $Properties['TagList']);
			if (!$Properties['Image'] && $WikiImage) {
				$Properties['Image'] = $WikiImage;
				$T['Image'] = "'".db_string($WikiImage)."'";
			}
			if (strlen($WikiBody) > strlen($Body)) {
				$Body = $WikiBody;
				if (!$Properties['Image'] || $Properties['Image'] == $WikiImage) {
					$NoRevision = true;
				}
			}
			$Properties['Artist'] = Artists::display_artists(Artists::get_artist($GroupID), false, false);
		}
	}
	if (!$GroupID) {
		foreach ($ArtistForm as $Importance => $Artists) {
			foreach ($Artists as $Num => $Artist) {
				$DB->query("
					SELECT
						tg.id,
						tg.WikiImage,
						tg.WikiBody,
						tg.RevisionID
					FROM torrents_group AS tg
						LEFT JOIN torrents_artists AS ta ON ta.GroupID = tg.ID
						LEFT JOIN artists_group AS ag ON ta.ArtistID = ag.ArtistID
					WHERE ag.Name = '".db_string($Artist['name'])."'
						AND tg.Name = ".$T['Title']."
						AND tg.ReleaseType = ".$T['ReleaseType']."
						AND tg.Year = ".$T['Year']);

				if ($DB->has_results()) {
					list($GroupID, $WikiImage, $WikiBody, $RevisionID) = $DB->next_record();
					if (!$Properties['Image'] && $WikiImage) {
						$Properties['Image'] = $WikiImage;
						$T['Image'] = "'".db_string($WikiImage)."'";
					}
					if (strlen($WikiBody) > strlen($Body)) {
						$Body = $WikiBody;
						if (!$Properties['Image'] || $Properties['Image'] == $WikiImage) {
							$NoRevision = true;
						}
					}
					$ArtistForm = Artists::get_artist($GroupID);
					//This torrent belongs in a group
					break;

				} else {
					// The album hasn't been uploaded. Try to get the artist IDs
					$DB->query("
						SELECT
							aa.ArtistID,
							aa.AliasID,
							aa.Name,
							aa.Redirect
						FROM artists_alias AS aa
						WHERE aa.Name = '".db_string($Artist['name'])."'");
					if ($DB->has_results()) {
						while (list($ArtistID, $AliasID, $AliasName, $Redirect) = $DB->next_record(MYSQLI_NUM, false)) {
							if (!strcasecmp($Artist['name'], $AliasName)) {
								if ($Redirect) {
									$AliasID = $Redirect;
								}
								$ArtistForm[$Importance][$Num] = array('id' => $ArtistID, 'aliasid' => $AliasID, 'name' => $AliasName);
								break;
							}
						}
					}
				}
			}
		}
	}
}

//Needs to be here as it isn't set for add format until now
$LogName .= $Properties['Title'];

//For notifications--take note now whether it's a new group
$IsNewGroup = !$GroupID;

//----- Start inserts
if (!$GroupID && $Type == 'Music') {
	//array to store which artists we have added already, to prevent adding an artist twice
	$ArtistsAdded = array();
	foreach ($ArtistForm as $Importance => $Artists) {
		foreach ($Artists as $Num => $Artist) {
			if (!$Artist['id']) {
				if (isset($ArtistsAdded[strtolower($Artist['name'])])) {
					$ArtistForm[$Importance][$Num] = $ArtistsAdded[strtolower($Artist['name'])];
				} else {
					// Create artist
					$DB->query("
						INSERT INTO artists_group (Name)
						VALUES ('".db_string($Artist['name'])."')");
					$ArtistID = $DB->inserted_id();

					$Cache->increment('stats_artist_count');

					$DB->query("
						INSERT INTO artists_alias (ArtistID, Name)
						VALUES ($ArtistID, '".db_string($Artist['name'])."')");
					$AliasID = $DB->inserted_id();

					$ArtistForm[$Importance][$Num] = array('id' => $ArtistID, 'aliasid' => $AliasID, 'name' => $Artist['name']);
					$ArtistsAdded[strtolower($Artist['name'])] = $ArtistForm[$Importance][$Num];
				}
			}
		}
	}
	unset($ArtistsAdded);
}

if (!$GroupID) {
	// Create torrent group
	$DB->query("
		INSERT INTO torrents_group
			(ArtistID, CategoryID, Name, Year, RecordLabel, CatalogueNumber, Time, WikiBody, WikiImage, ReleaseType, VanityHouse)
		VALUES
			(0, $TypeID, ".$T['Title'].", $T[Year], $T[RecordLabel], $T[CatalogueNumber], '".sqltime()."', '".db_string($Body)."', $T[Image], $T[ReleaseType], $T[VanityHouse])");
	$GroupID = $DB->inserted_id();
	if ($Type == 'Music') {
		foreach ($ArtistForm as $Importance => $Artists) {
			foreach ($Artists as $Num => $Artist) {
				$DB->query("
					INSERT IGNORE INTO torrents_artists (GroupID, ArtistID, AliasID, UserID, Importance)
					VALUES ($GroupID, ".$Artist['id'].', '.$Artist['aliasid'].', '.$LoggedUser['ID'].", '$Importance')");
				$Cache->increment('stats_album_count');
			}
		}
	}
	$Cache->increment('stats_group_count');

} else {
	$DB->query("
		UPDATE torrents_group
		SET Time = '".sqltime()."'
		WHERE ID = $GroupID");
	$Cache->delete_value("torrent_group_$GroupID");
	$Cache->delete_value("torrents_details_$GroupID");
	$Cache->delete_value("detail_files_$GroupID");
	if ($Type == 'Music') {
		$DB->query("
			SELECT ReleaseType
			FROM torrents_group
			WHERE ID = '$GroupID'");
		list($Properties['ReleaseType']) = $DB->next_record();
	}
}

// Description
if (!$NoRevision) {
	$DB->query("
		INSERT INTO wiki_torrents
			(PageID, Body, UserID, Summary, Time, Image)
		VALUES
			($GroupID, $T[GroupDescription], $LoggedUser[ID], 'Uploaded new torrent', '".sqltime()."', $T[Image])");
	$RevisionID = $DB->inserted_id();

	// Revision ID
	$DB->query("
		UPDATE torrents_group
		SET RevisionID = '$RevisionID'
		WHERE ID = $GroupID");
}

// Tags
$Tags = explode(',', $Properties['TagList']);
if (!$Properties['GroupID']) {
	foreach ($Tags as $Tag) {
		$Tag = Misc::sanitize_tag($Tag);
		if (!empty($Tag)) {
		$Tag = Misc::get_alias_tag($Tag);
			$DB->query("
				INSERT INTO tags
					(Name, UserID)
				VALUES
					('$Tag', $LoggedUser[ID])
				ON DUPLICATE KEY UPDATE
					Uses = Uses + 1;
			");
			$TagID = $DB->inserted_id();

			$DB->query("
				INSERT INTO torrents_tags
					(TagID, GroupID, UserID, PositiveVotes)
				VALUES
					($TagID, $GroupID, $LoggedUser[ID], 10)
				ON DUPLICATE KEY UPDATE
					PositiveVotes = PositiveVotes + 1;
			");
		}
	}
}

// Use this section to control freeleeches
$T['FreeLeech'] = 0;
$T['FreeLeechType'] = 0;
$LogScore = ($HasLog == 1 ? $LogScoreAverage : 0);
// Torrent
$DB->query("
	INSERT INTO torrents
		(GroupID, UserID, Media, Format, Encoding,
		Remastered, RemasterYear, RemasterTitle, RemasterRecordLabel, RemasterCatalogueNumber,
		Scene, HasLog, HasCue, info_hash, FileCount, FileList, FilePath,
		Size, Time, Description, LogScore, FreeTorrent, FreeLeechType)
	VALUES
		($GroupID, $LoggedUser[ID], $T[Media], $T[Format], $T[Encoding],
		$T[Remastered], $T[RemasterYear], $T[RemasterTitle], $T[RemasterRecordLabel], $T[RemasterCatalogueNumber],
		$T[Scene], '$HasLog', '$HasCue', '".db_string($InfoHash)."', $NumFiles, '$FileString', '$FilePath',
		$TotalSize, '".sqltime()."', $T[TorrentDescription], $LogScore, '$T[FreeLeech]', '$T[FreeLeechType]')");

$Cache->increment('stats_torrent_count');
$TorrentID = $DB->inserted_id();

Tracker::update_tracker('add_torrent', array('id' => $TorrentID, 'info_hash' => rawurlencode($InfoHash), 'freetorrent' => $T['FreeLeech']));
$Debug->set_flag('upload: ocelot updated');

// Prevent deletion of this torrent until the rest of the upload process is done
// (expire the key after 10 minutes to prevent locking it for too long in case there's a fatal error below)
$Cache->cache_value("torrent_{$TorrentID}_lock", true, 600);

//******************************************************************************//
//--------------- Write torrent file -------------------------------------------//

$DB->query("
	INSERT INTO torrents_files (TorrentID, File)
	VALUES ($TorrentID, '$TorEnc')");
Misc::write_log("Torrent $TorrentID ($LogName) (".number_format($TotalSize / (1024 * 1024), 2).' MB) was uploaded by ' . $LoggedUser['Username']);
Torrents::write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], 'uploaded ('.number_format($TotalSize / (1024 * 1024), 2).' MB)', 0);

Torrents::update_hash($GroupID);
$Debug->set_flag('upload: sphinx updated');

if ($Type == 'Music') {
	include(SERVER_ROOT.'/sections/upload/insert_extra_torrents.php');
}



//******************************************************************************//
//--------------- Add the log scores to the DB ---------------------------------//

if (!empty($LogScores) && $HasLog) {
	$LogQuery = '
		INSERT INTO torrents_logs_new
			(TorrentID, Log, Details, NotEnglish, Score, Revision, Adjusted, AdjustedBy, AdjustmentReason)
		VALUES (';
	foreach ($LogScores as $LogKey => $LogScore) {
		$LogScores[$LogKey] = "$TorrentID, $LogScore, 1, 0, 0, NULL";
	}
	$LogQuery .= implode('),(', $LogScores).')';
	$DB->query($LogQuery);
	$LogInDB = true;
}

//******************************************************************************//
//--------------- Stupid Recent Uploads ----------------------------------------//

if (trim($Properties['Image']) != '') {
	$RecentUploads = $Cache->get_value("recent_uploads_$UserID");
	if (is_array($RecentUploads)) {
		do {
			foreach ($RecentUploads as $Item) {
				if ($Item['ID'] == $GroupID) {
					break 2;
				}
			}

			// Only reached if no matching GroupIDs in the cache already.
			if (count($RecentUploads) === 5) {
				array_pop($RecentUploads);
			}
			array_unshift($RecentUploads, array(
						'ID' => $GroupID,
						'Name' => trim($Properties['Title']),
						'Artist' => Artists::display_artists($ArtistForm, false, true),
						'WikiImage' => trim($Properties['Image'])));
			$Cache->cache_value("recent_uploads_$UserID", $RecentUploads, 0);
		} while (0);
	}
}

//******************************************************************************//
//--------------- Contest ------------------------------------------------------//
if ($Properties['LibraryImage'] != '') {
	$DB->query("
		INSERT INTO reportsv2
			(ReporterID, TorrentID, Type, UserComment, Status, ReportedTime, Track, Image, ExtraID, Link)
		VALUES
			(0, $TorrentID, 'library', '".db_string(($Properties['MultiDisc'] ? 'Multi-disc' : ''))."', 'New', '".sqltime()."', '', '".db_string($Properties['LibraryImage'])."', '', '')");
}

//******************************************************************************//
//--------------- Post-processing ----------------------------------------------//
/* Because tracker updates and notifications can be slow, we're
 * redirecting the user to the destination page and flushing the buffers
 * to make it seem like the PHP process is working in the background.
 */

if ($PublicTorrent) {
	View::show_header('Warning');
?>
	<h1>Warning</h1>
	<p><strong>Your torrent has been uploaded; however, you must download your torrent from <a href="torrents.php?id=<?=$GroupID?>">here</a> because you didn't make your torrent using the "private" option.</strong></p>
<?
	View::show_footer();
} elseif ($RequestID) {
	header("Location: requests.php?action=takefill&requestid=$RequestID&torrentid=$TorrentID&auth=".$LoggedUser['AuthKey']);
} else {
	header("Location: torrents.php?id=$GroupID");
}
if (function_exists('fastcgi_finish_request')) {
	fastcgi_finish_request();
} else {
	ignore_user_abort(true);
	ob_flush();
	flush();
	ob_start(); // So we don't keep sending data to the client
}

//******************************************************************************//
//--------------- IRC announce and feeds ---------------------------------------//
$Announce = '';

if ($Type == 'Music') {
	$Announce .= Artists::display_artists($ArtistForm, false);
}
$Announce .= trim($Properties['Title']).' ';
if ($Type == 'Music') {
	$Announce .= '['.trim($Properties['Year']).']';
	if (($Type == 'Music') && ($Properties['ReleaseType'] > 0)) {
		$Announce .= ' ['.$ReleaseTypes[$Properties['ReleaseType']].']';
	}
	$Announce .= ' - ';
	$Announce .= trim($Properties['Format']).' / '.trim($Properties['Bitrate']);
	if ($HasLog == 1) {
		$Announce .= ' / Log';
	}
	if ($LogInDB) {
		$Announce .= ' / '.$LogScoreAverage.'%';
	}
	if ($HasCue == 1) {
		$Announce .= ' / Cue';
	}
	$Announce .= ' / '.trim($Properties['Media']);
	if ($Properties['Scene'] == '1') {
		$Announce .= ' / Scene';
	}
	if ($T['FreeLeech'] == '1') {
		$Announce .= ' / Freeleech!';
	}
}
$Title = $Announce;

$AnnounceSSL = "$Announce - https://".SSL_SITE_URL."/torrents.php?id=$GroupID / https://".SSL_SITE_URL."/torrents.php?action=download&id=$TorrentID";
$Announce .= " - https://".SSL_SITE_URL."/torrents.php?id=$GroupID / https://".SSL_SITE_URL."/torrents.php?action=download&id=$TorrentID";

$AnnounceSSL .= ' - '.trim($Properties['TagList']);
$Announce .= ' - '.trim($Properties['TagList']);

// ENT_QUOTES is needed to decode single quotes/apostrophes
send_irc('PRIVMSG #'.NONSSL_SITE_URL.'-announce :'.html_entity_decode($Announce, ENT_QUOTES));
send_irc('PRIVMSG #'.NONSSL_SITE_URL.'-announce-ssl :'.html_entity_decode($AnnounceSSL, ENT_QUOTES));
$Debug->set_flag('upload: announced on irc');

// Manage notifications
$UsedFormatBitrates = array();

if (!$IsNewGroup) {
	// maybe there are torrents in the same release as the new torrent. Let's find out (for notifications)
	$GroupInfo = get_group_info($GroupID, true, 0, false);

	$ThisMedia = display_str($Properties['Media']);
	$ThisRemastered = display_str($Properties['Remastered']);
	$ThisRemasterYear = display_str($Properties['RemasterYear']);
	$ThisRemasterTitle = display_str($Properties['RemasterTitle']);
	$ThisRemasterRecordLabel = display_str($Properties['RemasterRecordLabel']);
	$ThisRemasterCatalogueNumber = display_str($Properties['RemasterCatalogueNumber']);

	foreach ($GroupInfo[1] as $TorrentInfo) {
		if (($TorrentInfo['Media'] == $ThisMedia)
			&& ($TorrentInfo['Remastered'] == $ThisRemastered)
			&& ($TorrentInfo['RemasterYear'] == (int)$ThisRemasterYear)
			&& ($TorrentInfo['RemasterTitle'] == $ThisRemasterTitle)
			&& ($TorrentInfo['RemasterRecordLabel'] == $ThisRemasterRecordLabel)
			&& ($TorrentInfo['RemasterCatalogueNumber'] == $ThisRemasterCatalogueNumber)
			&& ($TorrentInfo['ID'] != $TorrentID)) {
			$UsedFormatBitrates[] = array('format' => $TorrentInfo['Format'], 'bitrate' => $TorrentInfo['Encoding']);
		}
	}
}

// For RSS
$Item = $Feed->item($Title, $Text->strip_bbcode($Body), 'torrents.php?action=download&amp;authkey=[[AUTHKEY]]&amp;torrent_pass=[[PASSKEY]]&amp;id='.$TorrentID, $LoggedUser['Username'], 'torrents.php?id='.$GroupID, trim($Properties['TagList']));


//Notifications
$SQL = "
	SELECT unf.ID, unf.UserID, torrent_pass
	FROM users_notify_filters AS unf
		JOIN users_main AS um ON um.ID = unf.UserID
	WHERE um.Enabled = '1'";
if (empty($ArtistsUnescaped)) {
	$ArtistsUnescaped = $ArtistForm;
}
if (!empty($ArtistsUnescaped)) {
	$ArtistNameList = array();
	$GuestArtistNameList = array();
	foreach ($ArtistsUnescaped as $Importance => $Artists) {
		foreach ($Artists as $Artist) {
			if ($Importance == 1 || $Importance == 4 || $Importance == 5 || $Importance == 6) {
				$ArtistNameList[] = "Artists LIKE '%|".db_string(str_replace('\\', '\\\\', $Artist['name']), true)."|%'";
			} else {
				$GuestArtistNameList[] = "Artists LIKE '%|".db_string(str_replace('\\', '\\\\', $Artist['name']), true)."|%'";
			}
		}
	}
	// Don't add notification if >2 main artists or if tracked artist isn't a main artist
	if (count($ArtistNameList) > 2 || $Artist['name'] == 'Various Artists') {
		$SQL .= " AND (ExcludeVA = '0' AND (";
		$SQL .= implode(' OR ', array_merge($ArtistNameList, $GuestArtistNameList));
		$SQL .= " OR Artists = '')) AND (";
	} else {
		$SQL .= " AND (";
		if (!empty($GuestArtistNameList)) {
			$SQL .= "(ExcludeVA = '0' AND (";
			$SQL .= implode(' OR ', $GuestArtistNameList);
			$SQL .= ')) OR ';
		}
		$SQL .= implode(' OR ', $ArtistNameList);
		$SQL .= " OR Artists = '') AND (";
	}
} else {
	$SQL .= "AND (Artists = '') AND (";
}

reset($Tags);
$TagSQL = array();
$NotTagSQL = array();
foreach ($Tags as $Tag) {
	$TagSQL[] = " Tags LIKE '%|".db_string(trim($Tag))."|%' ";
	$NotTagSQL[] = " NotTags LIKE '%|".db_string(trim($Tag))."|%' ";
}
$TagSQL[] = "Tags = ''";
$SQL .= implode(' OR ', $TagSQL);

$SQL .= ") AND !(".implode(' OR ', $NotTagSQL).')';

$SQL .= " AND (Categories LIKE '%|".db_string(trim($Type))."|%' OR Categories = '') ";

if ($Properties['ReleaseType']) {
	$SQL .= " AND (ReleaseTypes LIKE '%|".db_string(trim($ReleaseTypes[$Properties['ReleaseType']]))."|%' OR ReleaseTypes = '') ";
} else {
	$SQL .= " AND (ReleaseTypes = '') ";
}

/*
	Notify based on the following:
		1. The torrent must match the formatbitrate filter on the notification
		2. If they set NewGroupsOnly to 1, it must also be the first torrent in the group to match the formatbitrate filter on the notification
*/


if ($Properties['Format']) {
	$SQL .= " AND (Formats LIKE '%|".db_string(trim($Properties['Format']))."|%' OR Formats = '') ";
} else {
	$SQL .= " AND (Formats = '') ";
}

if ($_POST['bitrate']) {
	$SQL .= " AND (Encodings LIKE '%|".db_string(trim($_POST['bitrate']))."|%' OR Encodings = '') ";
} else {
	$SQL .= " AND (Encodings = '') ";
}

if ($Properties['Media']) {
	$SQL .= " AND (Media LIKE '%|".db_string(trim($Properties['Media']))."|%' OR Media = '') ";
} else {
	$SQL .= " AND (Media = '') ";
}

// Either they aren't using NewGroupsOnly
$SQL .= "AND ((NewGroupsOnly = '0' ";
// Or this is the first torrent in the group to match the formatbitrate filter
$SQL .= ") OR ( NewGroupsOnly = '1' ";
// Test the filter doesn't match any previous formatbitrate in the group
foreach ($UsedFormatBitrates as $UsedFormatBitrate) {
	$FormatReq = "(Formats LIKE '%|".db_string($UsedFormatBitrate['format'])."|%' OR Formats = '') ";
	$BitrateReq = "(Encodings LIKE '%|".db_string($UsedFormatBitrate['bitrate'])."|%' OR Encodings = '') ";
	$SQL .= "AND (NOT($FormatReq AND $BitrateReq)) ";
}

$SQL .= '))';


if ($Properties['Year'] && $Properties['RemasterYear']) {
	$SQL .= " AND (('".db_string(trim($Properties['Year']))."' BETWEEN FromYear AND ToYear)
			OR ('".db_string(trim($Properties['RemasterYear']))."' BETWEEN FromYear AND ToYear)
			OR (FromYear = 0 AND ToYear = 0)) ";
} elseif ($Properties['Year'] || $Properties['RemasterYear']) {
	$SQL .= " AND (('".db_string(trim(Max($Properties['Year'],$Properties['RemasterYear'])))."' BETWEEN FromYear AND ToYear)
			OR (FromYear = 0 AND ToYear = 0)) ";
} else {
	$SQL .= " AND (FromYear = 0 AND ToYear = 0) ";
}
$SQL .= " AND UserID != '".$LoggedUser['ID']."' ";

$DB->query("
	SELECT Paranoia
	FROM users_main
	WHERE ID = $LoggedUser[ID]");
list($Paranoia) = $DB->next_record();
$Paranoia = unserialize($Paranoia);
if (!is_array($Paranoia)) {
	$Paranoia = array();
}
if (!in_array('notifications', $Paranoia)) {
	$SQL .= " AND (Users LIKE '%|".$LoggedUser['ID']."|%' OR Users = '') ";
}

$SQL .= " AND UserID != '".$LoggedUser['ID']."' ";
$DB->query($SQL);
$Debug->set_flag('upload: notification query finished');

if ($DB->has_results()) {
	$UserArray = $DB->to_array('UserID');
	$FilterArray = $DB->to_array('ID');

	$InsertSQL = '
		INSERT IGNORE INTO users_notify_torrents (UserID, GroupID, TorrentID, FilterID)
		VALUES ';
	$Rows = array();
	foreach ($UserArray as $User) {
		list($FilterID, $UserID, $Passkey) = $User;
		$Rows[] = "('$UserID', '$GroupID', '$TorrentID', '$FilterID')";
		$Feed->populate("torrents_notify_$Passkey", $Item);
		$Cache->delete_value("notifications_new_$UserID");
	}
	$InsertSQL .= implode(',', $Rows);
	$DB->query($InsertSQL);
	$Debug->set_flag('upload: notification inserts finished');

	foreach ($FilterArray as $Filter) {
		list($FilterID, $UserID, $Passkey) = $Filter;
		$Feed->populate("torrents_notify_{$FilterID}_$Passkey", $Item);
	}
}

// RSS for bookmarks
$DB->query("
	SELECT u.ID, u.torrent_pass
	FROM users_main AS u
		JOIN bookmarks_torrents AS b ON b.UserID = u.ID
	WHERE b.GroupID = $GroupID");
while (list($UserID, $Passkey) = $DB->next_record()) {
	$Feed->populate("torrents_bookmarks_t_$Passkey", $Item);
}

$Feed->populate('torrents_all', $Item);
$Debug->set_flag('upload: notifications handled');
if ($Type == 'Music') {
	$Feed->populate('torrents_music', $Item);
	if ($Properties['Media'] == 'Vinyl') {
		$Feed->populate('torrents_vinyl', $Item);
	}
	if ($Properties['Bitrate'] == 'Lossless') {
		$Feed->populate('torrents_lossless', $Item);
	}
	if ($Properties['Bitrate'] == '24bit Lossless') {
		$Feed->populate('torrents_lossless24', $Item);
	}
	if ($Properties['Format'] == 'MP3') {
		$Feed->populate('torrents_mp3', $Item);
	}
	if ($Properties['Format'] == 'FLAC') {
		$Feed->populate('torrents_flac', $Item);
	}
}
if ($Type == 'Applications') {
	$Feed->populate('torrents_apps', $Item);
}
if ($Type == 'E-Books') {
	$Feed->populate('torrents_ebooks', $Item);
}
if ($Type == 'Audiobooks') {
	$Feed->populate('torrents_abooks', $Item);
}
if ($Type == 'E-Learning Videos') {
	$Feed->populate('torrents_evids', $Item);
}
if ($Type == 'Comedy') {
	$Feed->populate('torrents_comedy', $Item);
}
if ($Type == 'Comics') {
	$Feed->populate('torrents_comics', $Item);
}

// Clear cache
$Cache->delete_value("torrents_details_$GroupID");

// Allow deletion of this torrent now
$Cache->delete_value("torrent_{$TorrentID}_lock");
