<?
//extra torrent files
$ExtraTorrents = array();
$DupeNames = array();
$DupeNames[] = $_FILES['file_input']['name'];

if (isset($_POST['extra_format']) && isset($_POST['extra_bitrate'])) {
	for ($i = 1; $i <= 5; $i++) {
		if (isset($_FILES["extra_file_$i"])) {
			$ExtraFile = $_FILES["extra_file_$i"];
			$ExtraTorrentName = $ExtraFile['tmp_name'];
			if (!is_uploaded_file($ExtraTorrentName) || !filesize($ExtraTorrentName)) {
				$Err = 'No extra torrent file uploaded, or file is empty.';
			} elseif (substr(strtolower($ExtraFile['name']), strlen($ExtraFile['name']) - strlen('.torrent')) !== '.torrent') {
				$Err = 'You seem to have put something other than an extra torrent file into the upload field. (' . $ExtraFile['name'] . ').';
			} elseif (in_array($ExtraFile['name'], $DupeNames)) {
				$Err = 'One or more torrents has been entered into the form twice.';
			} else {
				$j = $i - 1;
				$ExtraTorrents[$ExtraTorrentName]['Name'] = $ExtraTorrentName;
				$ExtraFormat = $_POST['extra_format'][$j];
				if (empty($ExtraFormat)) {
					$Err = 'Missing format for extra torrent.';
					break;
				} else {
					$ExtraTorrents[$ExtraTorrentName]['Format'] = db_string(trim($ExtraFormat));
				}
				$ExtraBitrate = $_POST['extra_bitrate'][$j];
				if (empty($ExtraBitrate)) {
					$Err = 'Missing bitrate for extra torrent.';
					break;
				} else {
					$ExtraTorrents[$ExtraTorrentName]['Encoding'] = db_string(trim($ExtraBitrate));
				}
				$ExtraReleaseDescription = $_POST['extra_release_desc'][$j];
				$ExtraTorrents[$ExtraTorrentName]['TorrentDescription'] = db_string(trim($ExtraReleaseDescription));
				$DupeNames[] = $ExtraFile['name'];
			}
		}
	}
}

?>
