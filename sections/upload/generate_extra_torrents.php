<?
$ExtraTorrentsInsert = array();
foreach ($ExtraTorrents as $ExtraTorrent) {
	$Name = $ExtraTorrent['Name'];
	$ExtraTorrentsInsert[$Name] = array();
	$ExtraTorrentsInsert[$Name] = $ExtraTorrent;

	$ExtraFile = fopen($Name, 'rb'); // open file for reading
	$ExtraContents = fread($ExtraFile, 10000000);
	$ExtraTor = new TORRENT($ExtraContents); // New TORRENT object

	// Remove uploader's passkey from the torrent.
	// We put the downloader's passkey in on download, so it doesn't matter what's in there now,
	// so long as it's not useful to any leet hax0rs looking in an unprotected /torrents/ directory
	$ExtraTor->set_announce_url('ANNOUNCE_URL'); // We just use the string "ANNOUNCE_URL"

	// $ExtraPrivate is true or false. true means that the uploaded torrent was private, false means that it wasn't.
	$ExtraPrivate = $ExtraTor->make_private();
	// The torrent is now private.

	// File list and size
	list($ExtraTotalSize, $ExtraFileList) = $ExtraTor->file_list();
	$ExtraTorrentsInsert[$Name]['TotalSize'] = $ExtraTotalSize;
	$ExtraDirName = $ExtraTor->get_name();

	$ExtraTmpFileList = array();

	foreach ($ExtraFileList as $ExtraFile) {
		list($ExtraSize, $ExtraName) = $ExtraFile;

		check_file($ExtraType, $ExtraName);

		// Make sure the filename is not too long
		if (mb_strlen($ExtraName, 'UTF-8') + mb_strlen($ExtraDirName, 'UTF-8') + 1 > MAX_FILENAME_LENGTH) {
			$Err = 'The torrent contained one or more files with too long a name (' . $ExtraName . ')';
		}

		// Add file and size to array
		$ExtraTmpFileList[] = $ExtraName . '{{{' . $ExtraSize . '}}}'; // Name {{{Size}}}
	}

	// To be stored in the database
	$ExtraFilePath = isset($ExtraTor->Val['info']->Val['files']) ? db_string(Format::make_utf8($ExtraDirName)) : "";
	$ExtraTorrentsInsert[$Name]['FilePath'] = $ExtraFilePath;
	// Name {{{Size}}}|||Name {{{Size}}}|||Name {{{Size}}}|||Name {{{Size}}}
	$ExtraFileString = "'" . db_string(Format::make_utf8(implode('|||', $ExtraTmpFileList))) . "'";
	$ExtraTorrentsInsert[$Name]['FileString'] = $ExtraFileString;
	// Number of files described in torrent
	$ExtraNumFiles = count($ExtraFileList);
	$ExtraTorrentsInsert[$Name]['NumFiles'] = $ExtraNumFiles;
	// The string that will make up the final torrent file
	$ExtraTorrentText = $ExtraTor->enc();
	

	// Infohash

	$ExtraInfoHash = pack("H*", sha1($ExtraTor->Val['info']->enc()));
	$ExtraTorrentsInsert[$Name]['InfoHash'] = $ExtraInfoHash;
	$DB->query("SELECT ID FROM torrents WHERE info_hash='" . db_string($ExtraInfoHash) . "'");
	if ($DB->record_count() > 0) {
		list($ExtraID) = $DB->next_record();
		$DB->query("SELECT TorrentID FROM torrents_files WHERE TorrentID = " . $ExtraID);
		if ($DB->record_count() > 0) {
			$Err = '<a href="torrents.php?torrentid=' . $ExtraID . '">The exact same torrent file already exists on the site!</a>';
		} else {
			//One of the lost torrents.
			$DB->query("INSERT INTO torrents_files (TorrentID, File) VALUES ($ExtraID, '" . db_string($ExtraTor->dump_data()) . "')");
			$Err = '<a href="torrents.php?torrentid=' . $ExtraID . '">Thankyou for fixing this torrent</a>';
		}
	}
	$ExtraTorrentsInsert[$Name]['Tor'] = $ExtraTor;
}

?>