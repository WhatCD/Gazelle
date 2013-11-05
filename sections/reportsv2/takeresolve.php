<?
/*
 * This is the backend of the AJAXy reports resolve (When you press the shiny submit button).
 * This page shouldn't output anything except in error. If you do want output, it will be put
 * straight into the table where the report used to be. Currently output is only given when
 * a collision occurs or a POST attack is detected.
 */

if (!check_perms('admin_reports')) {
	error(403);
}
authorize();


//Don't escape: Log message, Admin message
$Escaped = db_array($_POST, array('log_message', 'admin_message', 'raw_name'));

//If we're here from the delete torrent page instead of the reports page.
if (!isset($Escaped['from_delete'])) {
	$Report = true;
} elseif (!is_number($Escaped['from_delete'])) {
	echo 'Hax occurred in from_delete';
} else {
	$Report = false;
}

$PMMessage = $_POST['uploader_pm'];

if (is_number($Escaped['reportid'])) {
	$ReportID = $Escaped['reportid'];
} else {
	echo 'Hax occurred in the reportid';
	die();
}

if ($Escaped['pm_type'] != 'Uploader') {
	$Escaped['uploader_pm'] = '';
}

$UploaderID = (int)$Escaped['uploaderid'];
if (!is_number($UploaderID)) {
	echo 'Hax occurring on the uploaderid';
	die();
}

$Warning = (int)$Escaped['warning'];
if (!is_number($Warning)) {
	echo 'Hax occurring on the warning';
	die();
}

$CategoryID = $Escaped['categoryid'];
if (!isset($CategoryID)) {
	echo 'Hax occurring on the categoryid';
	die();
}

$TorrentID = $Escaped['torrentid'];
$RawName = $Escaped['raw_name'];

if (isset($Escaped['delete']) && $Cache->get_value("torrent_$TorrentID".'_lock')) {
	echo "You requested to delete the torrent $TorrentID, but this is currently not possible because the upload process is still running. Please try again later.";
	die();
}

if (($Escaped['resolve_type'] == 'manual' || $Escaped['resolve_type'] == 'dismiss') && $Report) {
	if ($Escaped['comment']) {
		$Comment = $Escaped['comment'];
	} else {
		if ($Escaped['resolve_type'] == 'manual') {
			$Comment = 'Report was resolved manually.';
		} elseif ($Escaped['resolve_type'] == 'dismiss') {
			 $Comment = 'Report was dismissed as invalid.';
		}
	}

	$DB->query("
		UPDATE reportsv2
		SET
			Status = 'Resolved',
			LastChangeTime = '".sqltime()."',
			ModComment = '$Comment',
			ResolverID = '".$LoggedUser['ID']."'
		WHERE ID = '$ReportID'
			AND Status != 'Resolved'");

	if ($DB->affected_rows() > 0) {
		$Cache->delete_value('num_torrent_reportsv2');
		$Cache->delete_value("reports_torrent_$TorrentID");
	} else {
	//Someone beat us to it. Inform the staffer.
?>
	<table class="layout" cellpadding="5">
		<tr>
			<td>
				<a href="reportsv2.php?view=report&amp;id=<?=$ReportID?>">Somebody has already resolved this report</a>
				<input type="button" value="Clear" onclick="ClearReport(<?=$ReportID?>);" />
			</td>
		</tr>
	</table>
<?
	}
	die();
}

if (!isset($Escaped['resolve_type'])) {
	echo 'No resolve type';
	die();
} elseif (array_key_exists($_POST['resolve_type'], $Types[$CategoryID])) {
	$ResolveType = $Types[$CategoryID][$_POST['resolve_type']];
} elseif (array_key_exists($_POST['resolve_type'], $Types['master'])) {
	$ResolveType = $Types['master'][$_POST['resolve_type']];
} else {
	//There was a type but it wasn't an option!
	echo 'HAX (Invalid Resolve Type)';
	die();
}

$DB->query("
	SELECT ID
	FROM torrents
	WHERE ID = $TorrentID");
$TorrentExists = ($DB->has_results());
if (!$TorrentExists) {
	$DB->query("
		UPDATE reportsv2
		SET Status = 'Resolved',
			LastChangeTime = '".sqltime()."',
			ResolverID = '".$LoggedUser['ID']."',
			ModComment = 'Report already dealt with (torrent deleted).'
		WHERE ID = $ReportID");

	$Cache->decrement('num_torrent_reportsv2');
}

if ($Report) {
	//Resolve with a parallel check
	$DB->query("
		UPDATE reportsv2
		SET Status = 'Resolved',
			LastChangeTime = '".sqltime()."',
			ResolverID = '".$LoggedUser['ID']."'
		WHERE ID = $ReportID
			AND Status != 'Resolved'");
}

//See if it we managed to resolve
if ($DB->affected_rows() > 0 || !$Report) {
	//We did, lets do all our shit
	if ($Report) {
		$Cache->decrement('num_torrent_reportsv2');
	}


	if (isset($Escaped['upload'])) {
		$Upload = true;
	} else {
		$Upload = false;
	}

	if ($_POST['resolve_type'] == 'tags_lots') {
		$DB->query("
			INSERT IGNORE INTO torrents_bad_tags
				(TorrentID, UserID, TimeAdded)
			VALUES
				($TorrentID, ".$LoggedUser['ID']." , '".sqltime()."')");
		$DB->query("
			SELECT GroupID
			FROM torrents
			WHERE ID = $TorrentID");
		list($GroupID) = $DB->next_record();
		$Cache->delete_value("torrents_details_$GroupID");
		$SendPM = true;
	}

	if ($_POST['resolve_type'] == 'folders_bad') {
		$DB->query("
			INSERT IGNORE INTO torrents_bad_folders
				(TorrentID, UserID, TimeAdded)
			VALUES
				($TorrentID, ".$LoggedUser['ID'].", '".sqltime()."')");
		$DB->query("
			SELECT GroupID
			FROM torrents
			WHERE ID = $TorrentID");
		list($GroupID) = $DB->next_record();
		$Cache->delete_value("torrents_details_$GroupID");
		$SendPM = true;
	}
	if ($_POST['resolve_type'] == 'filename') {
		$DB->query("
			INSERT IGNORE INTO torrents_bad_files
				(TorrentID, UserID, TimeAdded)
			VALUES
				($TorrentID, ".$LoggedUser['ID'].", '".sqltime()."')");
		$DB->query("
			SELECT GroupID
			FROM torrents
			WHERE ID = $TorrentID");
		list($GroupID) = $DB->next_record();
		$Cache->delete_value("torrents_details_$GroupID");
		$SendPM = true;
	}

	//Log and delete
	if (isset($Escaped['delete']) && check_perms('users_mod')) {
		$DB->query("
			SELECT Username
			FROM users_main
			WHERE ID = $UploaderID");
		list($UpUsername) = $DB->next_record();
		$Log = "Torrent $TorrentID ($RawName) uploaded by $UpUsername was deleted by ".$LoggedUser['Username'];
		$Log .= ($Escaped['resolve_type'] == 'custom' ? '' : ' for the reason: '.$ResolveType['title'].".");
		if (isset($Escaped['log_message']) && $Escaped['log_message'] != '') {
			$Log .= ' ( '.$Escaped['log_message'].' )';
		}
		$DB->query("
			SELECT GroupID, hex(info_hash)
			FROM torrents
			WHERE ID = $TorrentID");
		list($GroupID, $InfoHash) = $DB->next_record();
		Torrents::delete_torrent($TorrentID, 0, $ResolveType['reason']);

		//$InfoHash = unpack("H*", $InfoHash);
		$Log .= ' ('.strtoupper($InfoHash).')';
		Misc::write_log($Log);
		$Log = 'deleted torrent for the reason: '.$ResolveType['title'].'. ( '.$Escaped['log_message'].' )';
		Torrents::write_group_log($GroupID, $TorrentID, $LoggedUser['ID'], $Log, 0);
	} else {
		$Log = "No log message (torrent wasn't deleted).";
	}

	//Warnings / remove upload
	if ($Upload) {
		$Cache->begin_transaction("user_info_heavy_$UploaderID");
		$Cache->update_row(false, array('DisableUpload' => '1'));
		$Cache->commit_transaction(0);

		$DB->query("
			UPDATE users_info
			SET DisableUpload = '1'
			WHERE UserID = $UploaderID");
	}

	if ($Warning > 0) {
		$WarnLength = $Warning * (7 * 24 * 60 * 60);
		$Reason = "Uploader of torrent ($TorrentID) $RawName which was resolved with the preset: ".$ResolveType['title'].'.';
		if ($Escaped['admin_message']) {
			$Reason .= ' ('.$Escaped['admin_message'].').';
		}
		if ($Upload) {
			$Reason .= ' (Upload privileges removed).';
		}

		Tools::warn_user($UploaderID, $WarnLength, $Reason);
	} else {
		//This is a bitch for people that don't warn but do other things, it makes me sad.
		$AdminComment = '';
		if ($Upload) {
			//They removed upload
			$AdminComment .= 'Upload privileges removed by '.$LoggedUser['Username'];
			$AdminComment .= "\nReason: Uploader of torrent ($TorrentID) ".db_string($RawName).' which was resolved with the preset: '.$ResolveType['title'].". (Report ID: $ReportID)";
		}
		if ($Escaped['admin_message']) {
			//They did nothing of note, but still want to mark it (Or upload and mark)
			$AdminComment .= ' ('.$Escaped['admin_message'].')';
		}
		if ($AdminComment) {
			$AdminComment = date('Y-m-d') . " - $AdminComment\n\n";

			$DB->query("
				UPDATE users_info
				SET AdminComment = CONCAT('".db_string($AdminComment)."', AdminComment)
				WHERE UserID = '".db_string($UploaderID)."'");
		}
	}

	//PM
	if ($Escaped['uploader_pm'] || $Warning > 0 || isset($Escaped['delete']) || $SendPM) {
		if (isset($Escaped['delete'])) {
			$PM = '[url='.site_url()."torrents.php?torrentid=$TorrentID]Your above torrent[/url] was reported and has been deleted.\n\n";
		} else {
			$PM = '[url='.site_url()."torrents.php?torrentid=$TorrentID]Your above torrent[/url] was reported but not deleted.\n\n";
		}

		$Preset = $ResolveType['resolve_options']['pm'];

		if ($Preset != '') {
			 $PM .= "Reason: $Preset\n\n";
		}

		if ($Warning > 0) {
			$PM .= "This has resulted in a [url=".site_url()."wiki.php?action=article&amp;id=218]$Warning week warning.[/url]\n\n";
		}

		if ($Upload) {
			$PM .= 'This has '.($Warning > 0 ? 'also ' : '')."resulted in the loss of your upload privileges.\n\n";
		}

		if ($Log) {
			$PM .= "Log Message: $Log\n\n";
		}

		if ($Escaped['uploader_pm']) {
			$PM .= "Message from ".$LoggedUser['Username'].": $PMMessage\n\n";
		}

		$PM .= "Report was handled by [user]".$LoggedUser['Username'].'[/user].';

		Misc::send_pm($UploaderID, 0, $Escaped['raw_name'], $PM);
	}

	$Cache->delete_value("reports_torrent_$TorrentID");

	// Now we've done everything, update the DB with values
	if ($Report) {
		$DB->query("
			UPDATE reportsv2
			SET
				Type = '".$Escaped['resolve_type']."',
				LogMessage = '".db_string($Log)."',
				ModComment = '".$Escaped['comment']."'
			WHERE ID = $ReportID");
	}
} else {
	// Someone beat us to it. Inform the staffer.
?>
<a href="reportsv2.php?view=report&amp;id=<?=$ReportID?>">Somebody has already resolved this report</a>
<input type="button" value="Clear" onclick="ClearReport(<?=$ReportID?>);" />
<?
}
