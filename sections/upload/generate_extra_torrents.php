<?
$ExtraTorrentsInsert = array();
foreach ($ExtraTorrents as $ExtraTorrent) {
	$Name = $ExtraTorrent['Name'];
	$ExtraTorrentsInsert[$Name] = $ExtraTorrent;
	$ThisInsert =& $ExtraTorrentsInsert[$Name];
	$ExtraTor = new BencodeTorrent($Name, true);
	if (isset($ExtraTor->Dec['encrypted_files'])) {
		$Err = 'At least one of the torrents contain an encrypted file list which is not supported here';
		break;
	}
	if (!$ExtraTor->is_private()) {
		$ExtraTor->make_private(); // The torrent is now private.
		$PublicTorrent = true;
	}

	// File list and size
	list($ExtraTotalSize, $ExtraFileList) = $ExtraTor->file_list();
	$ExtraDirName = isset($ExtraTor->Dec['info']['files']) ? Format::make_utf8($ExtraTor->get_name()) : '';

	$ExtraTmpFileList = array();
	foreach ($ExtraFileList as $ExtraFile) {
		list($ExtraSize, $ExtraName) = $ExtraFile;

		check_file($Type, $ExtraName);

		// Make sure the file name is not too long
		if (mb_strlen($ExtraName, 'UTF-8') + mb_strlen($ExtraDirName, 'UTF-8') + 1 > MAX_FILENAME_LENGTH) {
			$Err = "The torrent contained one or more files with too long of a name: <br />$ExtraDirName/$ExtraName";
			break;
		}
		// Add file and size to array
		$ExtraTmpFileList[] = Torrents::filelist_format_file($ExtraFile);
	}

	// To be stored in the database
	$ThisInsert['FilePath'] = db_string($ExtraDirName);
	$ThisInsert['FileString'] = db_string(implode("\n", $ExtraTmpFileList));
	$ThisInsert['InfoHash'] = pack('H*', $ExtraTor->info_hash());
	$ThisInsert['NumFiles'] = count($ExtraFileList);
	$ThisInsert['TorEnc'] = db_string($ExtraTor->encode());
	$ThisInsert['TotalSize'] = $ExtraTotalSize;

	$Debug->set_flag('upload: torrent decoded');
	$DB->query("
		SELECT ID
		FROM torrents
		WHERE info_hash = '" . db_string($ThisInsert['InfoHash']) . "'");
	if ($DB->has_results()) {
		list($ExtraID) = $DB->next_record();
		$DB->query("
			SELECT TorrentID
			FROM torrents_files
			WHERE TorrentID = $ExtraID");
		if ($DB->has_results()) {
			$Err = "<a href=\"torrents.php?torrentid=$ExtraID\">The exact same torrent file already exists on the site!</a>";
		} else {
			//One of the lost torrents.
			$DB->query("
				INSERT INTO torrents_files (TorrentID, File)
				VALUES ($ExtraID, '$ThisInsert[TorEnc]')");
			$Err = "<a href=\"torrents.php?torrentid=$ExtraID\">Thank you for fixing this torrent.</a>";
		}
	}
}
unset($ThisInsert);
?>
