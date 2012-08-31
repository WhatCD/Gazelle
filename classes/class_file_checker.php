<?php

$music_extensions = array("mp3","flac","mp4","m4a","m3u","m4b","pls","m3u8","log","txt",
			  "cue","jpg","jpeg","png","gif","dts","ac3","nfo",
		          "sfv","md5","accurip","ffp","pdf", "mobi", "epub", "htm", "html", "lit",
			   "chm", "rtf", "doc", "djv", "djvu");

$comics_extensions = array("cbr", "cbz", "pdf", "jpg","jpeg","png","gif");

$keywords = array("scc.nfo", "torrentday", "demonoid.com", "demonoid.me", "djtunes.com", "mixesdb.com",
		  "housexclusive.net", "plixid.com", "h33t", "reggaeme.com" ,"ThePirateBay.org",
		  "Limetorrents.com", "AhaShare.com", "MixFiend.blogstop", "MixtapeTorrent.blogspot");
function check_file($Type, $Name) {
	check_name(strtolower($Name));
	check_extensions($Type, strtolower($Name));
}

function check_name($Name) {
	global $keywords;
	foreach ($keywords as &$value) { 
		if(preg_match('/'.$value.'/i', $Name)) {
			forbidden_error($Name);
       		}
	}
        if(preg_match('/INCOMPLETE~\*/i', $Name)) {
		forbidden_error($Name);
        }
        if(preg_match('/\?/i', $Name)) {
        	character_error();
	}
        if(preg_match('/\:/i', $Name)) {
        	character_error();
	}

}

function check_extensions($Type, $Name) {

global $music_extensions, $comics_extensions;

if($Type == 'Music' || $Type == 'Audiobooks' || $Type == 'Comedy' || $Type == 'E-Books') {
                if(!in_array(get_file_extension($Name), $music_extensions)) {
			invalid_error($Name);
                }
        }

if($Type == 'Comics') {
                if(!in_array(get_file_extension($Name), $comics_extensions)) {
			invalid_error($Name);
                }
        }

}

function get_file_extension($file_name) {
	return substr(strrchr($file_name,'.'),1);
}

function invalid_error($Name) {
	global $Err;
	$Err = 'The torrent contained one or more invalid files ('.display_str($Name).')';

}

function forbidden_error($Name) {
	global $Err;
	$Err = 'The torrent contained one or more forbidden files ('.display_str($Name).')';
}

function character_error() {
        global $Err;
	$Err = 'The torrent contains one or more files with a ?, which is a forbidden character. Please rename the files as necessary and recreate the torrent';
}



?>
