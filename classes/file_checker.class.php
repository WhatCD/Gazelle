<?php
$ComicsExtensions = array_fill_keys(array('cbr', 'cbz', 'gif', 'jpeg', 'jpg', 'pdf', 'png'), true);
$MusicExtensions = array_fill_keys(
	array(
		'ac3', 'accurip', 'azw3', 'chm', 'cue', 'djv', 'djvu', 'doc', 'dts', 'epub', 'ffp',
		'flac', 'gif', 'htm', 'html', 'jpeg', 'jpg', 'lit', 'log', 'm3u', 'm3u8', 'm4a', 'm4b',
		'md5', 'mobi', 'mp3', 'mp4', 'nfo', 'pdf', 'pls', 'png', 'rtf', 'sfv', 'txt'),
	true);
$Keywords = array(
	'ahashare.com', 'demonoid.com', 'demonoid.me', 'djtunes.com', 'h33t', 'housexclusive.net',
	'limetorrents.com', 'mixesdb.com', 'mixfiend.blogstop', 'mixtapetorrent.blogspot',
	'plixid.com', 'reggaeme.com' , 'scc.nfo', 'thepiratebay.org', 'torrentday');

function check_file($Type, $Name) {
	check_name($Name);
	check_extensions($Type, $Name);
}

function check_name($Name) {
	global $Keywords;
	$NameLC = strtolower($Name);
	foreach ($Keywords as &$Value) {
		if (strpos($NameLC, $Value) !== false) {
			forbidden_error($Name);
		}
	}
	if (preg_match('/INCOMPLETE~\*/i', $Name)) {
		forbidden_error($Name);
	}
	if (preg_match('/[:?]/', $Name, $Matches)) {
		character_error($Matches[0]);
	}
}

function check_extensions($Type, $Name) {
	global $MusicExtensions, $ComicsExtensions;
	if ($Type == 'Music' || $Type == 'Audiobooks' || $Type == 'Comedy' || $Type == 'E-Books') {
		if (!isset($MusicExtensions[get_file_extension($Name)])) {
			invalid_error($Name);
		}
	}
	elseif ($Type == 'Comics') {
		if (!isset($ComicsExtensions[get_file_extension($Name)])) {
			invalid_error($Name);
		}
	}
}

function get_file_extension($FileName) {
	return strtolower(substr(strrchr($FileName, '.'), 1));
}

function invalid_error($Name) {
	global $Err;
	$Err = 'The torrent contained one or more invalid files (' . display_str($Name) . ')';
}

function forbidden_error($Name) {
	global $Err;
	$Err = 'The torrent contained one or more forbidden files (' . display_str($Name) . ')';
}

function character_error($Character) {
	global $Err;
	$Err = "One or more of the files in the torrent has a name that contains the forbidden character '$Character'. Please rename the files as necessary and recreate the torrent.";
}
