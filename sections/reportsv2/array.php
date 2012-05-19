<?
/*
 * The $Types array is the backbone of the reports system and is stored here so it can
 * be included on the pages that need it, but not clog up the pages that don't.
 * Important thing to note about the array:
 * 1. When coding for a non music site, you need to ensure that the top level of the
 * array lines up with the $Categories array in your config.php.
 * 2. The first sub array contains resolves that are present on every report type
 * regardless of category.
 * 3. The only part that shouldn't be self explanatory is that for the tracks field in
 * the report_fields arrays, 0 means not shown, 1 means required, 2 means required but
 * you can't tick the 'All' box.
 * 4. The current report_fields that are set up are tracks, sitelink, link and image. If
 * you wanted to add a new one, you'd need to add a field to the reportsv2 table, elements
 * to the relevant report_fields arrays here, add the HTML in ajax_report and add security
 * in takereport.
 */

$Types = array(
		'master' => array(
			'dupe' => array(
				'priority' => '1',
				'title' => 'Dupe',
				'report_messages' => array(
					'Please specify a link to the original torrent.'
				),
				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => '2.2. Your torrent has been deleted because it was a duplicate of another torrent.'
				)
			),
			'banned' => array(
				'priority' => '23',
				'title' => 'Specifically Banned',
				'report_messages' => array(
					'Please specify exactly which entry on the Do Not Upload list this is violating.'
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '4',
					'delete' => '1',
					'pm' => '1.2. You have uploaded material that is currently forbidden. Items on the Do Not Upload list (at the top of the upload page) cannot be uploaded to the site. Do not upload them unless your torrent meets a condition specified in the comments of the DNU list.
Because the torrent you uploaded contained material on the Do Not Upload list, you have been issued a 4-week warning.'
				)
			),
			'urgent' => array(
				'priority' => '28',
				'title' => 'Urgent',
				'report_messages' => array(
					'This report type is only for the very urgent reports, usually for personal information being found within a torrent.',
					'Abusing the Urgent reports could result in a warning or worse',
					'As by default this report type gives the staff absolutely no information about the problem, please be as clear as possible in your comments as to the problem'
				),
				'report_fields' => array(
					'sitelink' => '0',
					'track' => '0',
					'link' => '0',
					'image' => '0',
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => ''
				)
			),
			'other' => array(
				'priority' => '20',
				'title' => 'Other',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => ''
				)
			),
			'trump' => array(
				'priority' => '2',
				'title' => 'Trump',
				'report_messages' => array(
					'Please list the specific reason(s) the newer torrent trumps the older one.',
					'Please make sure you are reporting the torrent <strong>which has been trumped</strong> and should be deleted, not the torrent that you think should remain on site.'
				),

				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => '2.2. Your torrent has been deleted because it was trumped by another torrent.'
				)
			)
		),
		'1' => array( //Music Resolves
			'tag_trump' => array (
				'priority' => '5',
				'title' => 'Tag Trump',
				'report_messages' => array(
					'Please list the specific tag(s) the newer torrent trumps the older one.',
					'Please make sure you are reporting the torrent <strong>which has been trumped</strong> and should be deleted, not the torrent that you think should remain on site.'
				),
				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '2.3.16. Properly tag your music files. Certain meta tags (e.g., ID3, Vorbis) are required on all music uploads. Make sure to use the proper format tags for your files (e.g., no ID3 tags for FLAC - see 2.2.10.8). ID3v2 tags for files are highly recommended over ID3v1. ID3 are recommended for AC3 torrents but are not mandatory because the format does not natively support file metadata tagging (for AC3, the file names become the vehicle for correctly labeling media files). Torrents uploaded with both good ID3v1 tags and blank ID3v2 tags (a dual set of tags) are trumpable by torrents with either just good ID3v1 tags or good ID3v2 tags (a single set of tags). If you upload an album missing one or more of the required tags, then another user may add the tags, re-upload, and report your torrent for deletion.'
				)
			),
			'vinyl_trump' => array(
				'priority' => '6',
				'title' => 'Vinyl Trump',
				'report_messages' => array(
					'Please list the specific reason(s) the newer torrent trumps the older one.',
					'Please make sure you are reporting the torrent <strong>which has been trumped</strong> and should be deleted, not the torrent that you think should remain on site.'
				),

				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => '2.2. Your torrent has been deleted as it was trumped by another torrent.'
				)
			),
			'folder_trump' => array (
				'priority' => '4',
				'title' => 'Bad Folder Name Trump',
				'report_messages' => array(
					'Please list the folder name and what is wrong with it',
					'Please make sure you are reporting the torrent <strong>which has been trumped</strong> and should be deleted, not the torrent that you think should remain on site.'
				),
				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => '2.3.2. Name your directories with meaningful titles, such as "Artist - Album (Year) - Format." The minimum acceptable is "Album" although it is preferable to include more information. If the directory name does not include this minimum then another user can rename the directory, re-upload, and report your torrent for deletion. In addition, torrent folders that are named using the scene convention will be trumpable if the Scene label is absent from the torrent.
2.3.3. Avoid creating unnecessary nested folders (such as an extra folder for the actual album) inside your properly named directory. A torrent with unnecessary nested folders is trumpable by a torrent with such folders removed. For single disc albums, all audio files must be included in the main torrent folder. For multi-disc albums, the main torrent folder may include one sub-folder that holds the audio file contents for each disc in the box set, i.e., the main torrent folder is "Adele - 19 (2008) - FLAC" while appropriate sub-folders may include "19 (Disc 1of2)" or "19" and "Live From The Hotel Cafe (Disc 2of2)" or "Acoustic Set Live From The Hotel Cafe, Los Angeles." Additional folders are unnecessary because they do nothing to improve the organization of the torrent. If you are uncertain about what to do for other cases, PM a staff member for guidance.'
				)
			),
			'file_trump' => array (
				'priority' => '3',
				'title' => 'Bad File Names Trump',
				'report_messages' => array(
					'Please describe what is wrong with the file names.',
					'Please make sure you are reporting the torrent <strong>which has been trumped</strong> and should be deleted, not the torrent that you think should remain on site.'
				),
				'report_fields' => array(
					'sitelink' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => '2.3.11. File names must accurately reflect the song titles. You may not have file names like 01track.mp3, 02track.mp3, etc. Torrents containing files that are named with incorrect song titles can be trumped by properly labeled torrents. Also, torrents that are sourced from the scene but do not have the Scene label must comply with site naming rules (no release group names in the file names, no advertisements in the file names, etc.). If all the letters in the track titles are capitalized, the torrent is trumpable. If you upload an album with improper file names, then another user may fix the file names, re-upload, and report yours for deletion.'
				)
			),
			'tracks_missing' => array(
				'priority' => '24',
				'title' => 'Track(s) Missing',
				'report_messages' => array(
					'Please list the track number and title of the missing track',
					'If possible, please provide a link to Amazon.com or another source showing the proper track listing.'
				),
				'report_fields' => array(
					'track' => '2',
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '2.1.19. All music torrents must represent a complete release, and may not be missing tracks (or discs in the case of a multi-disc release).
2.1.19.2. A single track (e.g., one MP3 file) cannot be uploaded on its own unless it is an officially released single. If a specific track can only be found on an album, the entire album must be uploaded in the torrent.
Because the torrent you uploaded was missing a track (or tracks), you have been issued a 1-week warning.'
				)
			),
			'discs_missing' => array(
				'priority' => '12',
				'title' => 'Disc(s) Missing',
				'report_messages' => array(
					'If possible, please provide a link to Amazon.com or another source showing the proper track listing.'
				),
				'report_fields' => array(
					'track' => '0',
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '2.1.19. All music torrents must represent a complete release, and may not be missing tracks (or discs in the case of a multi-disc release).
2.1.19.1. If an album is released as a multi-disc set (or box set) of CDs or vinyl discs, then it must be uploaded as a single torrent. Preferably, each individual CD rip in a multi-disc set should be organized in its own folder (see 2.3.12).
Because the torrent you uploaded was missing a disc (or discs), you have been issued a 1-week warning.'
				)
			),
			'bonus_tracks' => array(
				'priority' => '9',
				'title' => 'Bonus Tracks Only',
				'report_messages' => array(
					'If possible, please provide a link to Amazon.com or another source showing the proper track listing.'
				),
				'report_fields' => array(
					'track' => '0',
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '2.1.19.3. Bonus discs may be uploaded separately in accordance with 2.4. Please note that individual bonus tracks cannot be uploaded without the rest of the album. Bonus tracks are not bonus discs. Enhanced audio CDs with data or video tracks must be uploaded without the non-audio tracks. If you want to share the videos or data, you may host the files off-site with a file sharing service and include the link to that service in your torrent description.
Because the torrent you uploaded contained only bonus tracks, you have been issued a 1-week warning.'
				)
			),
			'transcode' => array(
				'priority' => '25',
				'title' => 'Transcode',
				'report_messages' => array(
					"Please list the tracks you checked, and the method used to determine the transcode.",
					"If possible, please include at least one screenshot of any spectral analysis done. You may include more than one."
				),
				'report_fields' => array(
					'image' => '0',
					'track' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '2',
					'delete' => '1',
					'pm' => '2.1.2. No transcodes or re-encodes of lossy releases are acceptable here.
Because the torrent you uploaded consisted of transcoded audio files, you have been issued a 2-week warning.'
				)
			),
			'low' => array(
				'priority' => '17',
				'title' => 'Low Bitrate',
				'report_messages' => array(
					"Please tell us the actual bitrate, and the software used to check."
				),
				'report_fields' => array(
					'track' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '2',
					'delete' => '1',
					'pm' => '2.1.3. Music releases must have an average bitrate of at least 192 kbps regardless of the format. Exceptions: The following VBR encodes may go under the 192 kbps limit: LAME V2 (VBR), V1 (VBR), V0 (VBR), APS (VBR), APX (VBR), MP3 192 (VBR), and AAC ~192 (VBR) to AAC ~256 (VBR) releases.
Because the torrent you uploaded contained files that were below the minimum bitrate, you have been issued a 2-week warning.'
				)
			),
			'mutt' => array(
				'priority' => '18',
				'title' => 'Mutt Rip',
				'report_messages' => array(
					"Please list at least two (2) tracks which have different bitrates and/or encoders."
				),
				'report_fields' => array(
					'track' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '2',
					'delete' => '1',
					'pm' => '2.1.6. All music torrents must be encoded with a single encoder using the same settings.
Because the torrent you uploaded contained files that were encoded by multiple encoders, you have been issued a 2-week warning.'
				)
			),
			'single_track' => array(
				'priority' => '27',
				'title' => 'Unsplit Album Rip',
				'report_messages' => array(
					"If possible, please provide a link to Amazon.com or another source showing the proper track listing.",
					"This option is for uploads of CDs ripped as a single track when it should be split as on the CD",
					"This option is not to be confused with uploads of a single track, taken from a CD with multiple tracks (Tracks Missing)"
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '2.1.5. Albums must not be ripped or uploaded as a single track.
2.1.5.1. If the tracks on the original CD were separate, you must rip them to separate files. Any unsplit FLAC rips lacking a cue sheet will be deleted outright. Any unsplit FLAC rip that includes a cue sheet will be trumpable by a properly split FLAC torrent. CDs with single tracks can be uploaded without prior splitting.
Because the torrent you uploaded was ripped as a single track, you have been issued a 1-week warning.'
				)
			),
			'tags_lots' => array(
				'priority' => '31',
				'title' => 'Bad Tags / No Tags at All',
				'report_messages' => array(
					"Please specify which tags are missing, and whether they're missing from all tracks.",
					"Ideally, you will replace this torrent with one with fixed tags and report this with the reason 'Tag Trump' <3"
				),
				'report_fields' => array(
					'track' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => '2.3.16. Properly tag your music files.
The Uploading Rules require that all uploads be properly tagged. Your torrent has been marked as having poor tags. It is now listed on better.php and is eligible for trumping. You are of course free to fix this torrent yourself. Add or fix the required tags and upload the replacement torrent to the site. Then, report (RP) the older torrent using the category "Tag Trump" and indicate in the report comments that you have fixed the tags. Be sure to provide a link (PL) to the new replacement torrent.'
				)
			),
			'folders_bad' => array(
				'priority' => '30',
				'title' => 'Bad Folder Names',
				'report_messages' => array(
					"Please specify the issue with the folder names.",
					"Ideally you will replace this torrent with one with fixed folder names and report this with the reason 'Trumped'."
					),
				'report_fields' => array(),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => '2.3.2. Name your directories with meaningful titles, such as "Artist - Album (Year) - Format."
The Uploading Rules require that all uploads contain torrent directories with meaningful names. Your torrent has been marked as having a poorly named torrent directory. It is now listed on better.php and is eligible for trumping. You are of course free to fix this torrent yourself. Add or fix the folder/directory name(s) and upload the replacement torrent to the site. Then, report (RP) the older torrent using the category "Folder Trump" and indicate in the report comments that you have fixed the directory name(s). Be sure to provide a link (PL) to the new replacement torrent.'
				)
			),
			'wrong_format' => array(
				'priority' => '32',
				'title' => 'Wrong Specified Format',
				'report_messages' => array(
					"Please specify the correct format."
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => '2.1.4. Bitrates must accurately reflect encoder presets or the average bitrate of the audio files. You are responsible for supplying correct format and bitrate information on the upload page.'
				)
			),
			'wrong_media' => array(
				'priority' => '33',
				'title' => 'Wrong Specified Media',
				'report_messages' => array(
					"Please specify the correct media."
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => ''
				)
			),
			'format' => array(
				'priority' => '10',
				'title' => 'Disallowed Format',
				'report_messages' => array(
					"If applicable, list the relevant tracks"
				),
				'report_fields' => array(
					'track' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '2.1.1. The only formats allowed for music are:
Lossy: MP3, AAC, AC3, DTS
Lossless: FLAC.'
				)
			),
			'bitrate' => array(
				'priority' => '15',
				'title' => 'Inaccurate Bitrate',
				'report_messages' => array(
					"Please tell us the actual bitrate, and the software used to check.",
					"If the correct bitrate would make this torrent a duplicate, please report it as a dupe, and include the mislabeling in 'Comments'.",
					"If the correct bitrate would result in this torrent trumping another, please report it as a trump, and include the mislabeling in 'Comments'."
				),
				'report_fields' => array(
					'track' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '2.1.4. Bitrates must accurately reflect encoder presets or the average bitrate of the audio files. You are responsible for supplying correct format and bitrate information on the upload page.'
				)
			),
			'source' => array(
				'priority' => '21',
				'title' => 'Radio/TV/FM/WEB Rip',
				'report_messages' => array(
					"Please include as much information as possible to verify the report"
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "2.1.11. Music ripped from the radio (Satellite or FM), television, the web, or podcasts are not allowed.
Because the torrent you uploaded contained audio files ripped from broadcast sources, you have been issued a 1-week warning."
				)
			),
			'discog' => array(
				'priority' => '13',
				'title' => 'Discography',
				'report_messages' => array(
					"Please include as much information as possible to verify the report"
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "2.1.20. User made discographies may not be uploaded. Multi-album torrents are not allowed on the site under any circumstances. That means no discographies, Pitchfork compilations, etc. If releases (e.g., CD singles) were never released as a bundled set, do not upload them together. Live Soundboard material should be uploaded as one torrent per night, per show, or per venue. Including more than one show in a torrent results in a multi-album torrent.
Because the torrent you uploaded is a discography, you have been issued a 1-week warning."
				)
			),
			'user_discog' => array(
				'priority' => '29',
				'title' => 'User Compilation',
				'report_messages' => array(
					"Please include as much information as possible to verify the report"
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "2.1.16. User-made compilations are not allowed.
2.1.16.1. These are defined as compilations made by the uploader or anyone else who does not officially represent the artist or the label. Compilations must be reasonably official. User-made and unofficial multichannel mixes are also not allowed.
Because the torrent you uploaded is a user compilation, you have been issued a 1-week warning."
				)
			),
			'lineage' => array(
				'priority' => '19',
				'title' => 'No Lineage Info',
				'report_messages' => array(
					"Please list the specific information missing from the torrent (hardware, software, etc.)"
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => "2.3.9. All lossless analog rips should include clear information about source lineage. All lossless SACD digital layer analog rips and vinyl rips must include clear information about recording equipment used (see 2.8). If you used a USB turntable for a vinyl rip, clearly indicate this in your lineage information. Also include all intermediate steps up to lossless encoding, such as the program used for mastering, sound card used, etc. Lossless analog rips missing rip information can be trumped by better documented lossless analog rips of equal or better quality. In order to trump a lossless analog rip without a lineage, this lineage must be included as a .txt or .log file within the new torrent."
				)
			),
			'edited' => array(
				'priority' => '14',
				'title' => 'Edited Log',
				'report_messages' => array(
					"Please explain exactly where you believe the log was edited.",
					"The torrent will not show 'reported' on the group page, but rest assured that the report will be seen by moderators."
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '4',
					'delete' => '1',
					'pm' => "2.2.10.9. No log editing is permitted.
2.2.10.9.1. Forging log data is a serious misrepresentation of quality, and will result in a warning and the loss of your uploading privileges when the edited log is found. We recommend that you do not open the rip log file for any reason. However, if you must open the rip log, do not edit anything in the file for any reason. If you discover that one of your software settings is incorrect in the ripping software preferences, you must rip the CD again with the proper settings. Do not consolidate logs under any circumstances. If you must re-rip specific tracks or an entire disc and the rip results happen to have the new log appended to the original, leave them as is. Do not remove any part of either log, and never copy/paste parts of a new log over an old log.
Because the torrent you uploaded contained an edited log you have been issued a 4-week warning. In addition, your uploading privileges will be suspended for the duration of your warning. To have your privileges restored, you must PM the staff member who handled this log case."
				)
			),
			'audience' => array(
				'priority' => '7',
				'title' => 'Audience Recording',
				'report_messages' => array(
					"Please include as much information as possible to verify the report"
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "2.1.12. No unofficial audience recordings may be uploaded. These include but are not limited to AUD (Audience), IEM (In Ear Monitor), ALD (Assistive Listening Device), Mini-Disc, and Matrix-sourced recordings (see 2.6.3).
Because the torrent you uploaded is sourced from an audience recording, you have been issued a 1-week warning."
				)
			),
			'filename' => array(
				'priority' => '8',
				'title' => 'Bad File Names',
				'report_messages' => array(
				),
				'report_fields' => array(
					'track' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => '2.3.11. File names must accurately reflect the song titles. You may not have file names like 01track.mp3, 02track.mp3, etc. Torrents containing files that are named with incorrect song titles can be trumped by properly labeled torrents. Also, torrents that are sourced from the scene but do not have the Scene label must comply with site naming rules (no release group names in the file names, no advertisements in the file names, etc.). If all the letters in the track titles are capitalized, the torrent is trumpable.
2.3.13. Track numbers are required in file names (e.g., "01 - TrackName.mp3"). If a torrent without track numbers in the file names is uploaded, then a torrent with the track numbers in the file names can take its place. When formatted properly, file names will sort in order by track number or playing order. Also see 2.3.14.
The Uploading Rules require that all uploads contain audio tracks with accurate file names. Your torrent has been marked as having incorrect or incomplete file names. It is now listed on better.php and is eligible for trumping. You are of course free to fix this torrent yourself. Add or fix the file names and upload the replacement torrent to the site. Then, report (RP) the older torrent using the category "Bad File Names Trump" and indicate in the report comments that you have fixed the file names. Be sure to provide a link (PL) to the new replacement torrent.'
				)
			),
			'cassette' => array(
				'priority' => '26',
				'title' => 'Unapproved Cassette',
				'report_messages' => array(
					"If the album was never released other than on cassette, please include a source."
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => "2.10.1 Cassettes are allowed under strict conditions.
2.10.1.1. Releases available only on cassette may be uploaded under special strict conditions (see this wiki for information on cassette ripping). Cassette-sourced uploads must be approved by staff first (see this wiki for details on the approval process). This also applies to soundboard releases where a tape generation can be found in the lineage (either the tape is not in the first generation or there are multiple tape generations). See 2.6.6 for more information.
2.10.1.2. You must contact a moderator privately for approval before uploading. Include proof in the form of discography information from a reputable source as well as the spectrals for 2 songs in your message.
Because the torrent you uploaded is an unapproved rip of a cassette, you have been issued a 1-week warning."
				)
			),
			'skips' => array(
				'priority' => '22',
				'title' => 'Skips / Encode Errors',
				'report_messages' => array(
					"Please tell us which track(s) we should check.",
					"Telling us where precisely in the song the skip/encode error can be heard will help us deal with your torrent."
				),
				'report_fields' => array(
					'track' => '2'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => "2.1.8. Music not sourced from vinyl must not contain pops, clicks, or skips. They will be deleted for rip/encode errors if reported."
				)
			),
			'rescore' => array(
				'priority' => '16',
				'title' => 'Log Rescore Request',
				'report_messages' => array(
					"It could help us if you say exactly why you believe this log requires rescoring.",
					"For example, if it's a foreign log which needs scoring, or if the log wasn't uploaded at all"
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '0',
					'pm' => ""
				)
			)
		),
		'2' => array( //Applications Rules Broken
			'missing_crack' => array(
				'priority' => '7',
				'title' => 'No Crack/Keygen/Patch',
				'report_messages' => array(
					'Please include as much information as possible to verify the report',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '4.1.2. All applications must come with a crack, keygen, or other method of ensuring that downloaders can install them easily. App torrents with keygens, cracks, or patches that do not work or torrents missing clear installation instructions will be deleted if reported. No exceptions.
Because the torrent you uploaded is missing an installation method, you have been issued a 1-week warning.'
				)
			),
			'game' => array(
				'priority' => '5',
				'title' => 'Game',
				'report_messages' => array(
					'Please include as much information as possible to verify the report',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '4',
					'delete' => '1',
					'pm' => '1.2.5. Games of any kind. No games of any kind for PC, Mac, Linux, mobile devices, or any other platform are allowed.
4.1.7. Games of any kind are prohibited (see 1.2.5).
Because the torrent you uploaded contains a game, you have been issued a 4-week warning.'
				)
			),
			'free' => array(
				'priority' => '4',
				'title' => 'Freely Available',
				'report_messages' => array(
					'Please include a link to a source of information or to the freely available app itself.',
				),
				'report_fields' => array(
					'link' => '1'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '4.1.3. App releases must not be freely available tools. Application releases cannot be freely downloaded anywhere from any official source. Nor may you upload open source applications where the source code is available for free.
Because the torrent you uploaded contains a freely available application, you have been issued a 1-week warning.'
				)
			),
			'description' => array(
				'priority' => '8',
				'title' => 'No Description',
				'report_messages' => array(
					'If possible, please provide a link to an accurate description',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '4.1.4. Release descriptions for applications must contain good information about the application. You should either have a small description of the program (either taken from its website or from an NFO file) or a link to the information -- but ideally both. Torrents missing this information will be deleted when reported.
Because the torrent you uploaded had inadequate release information, you have been issued a 1-week warning.'
				)
			),
			'pack' => array(
				'priority' => '2',
				'title' => 'Archived Pack',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '2.1.18. Sound Sample Packs must be uploaded as applications.
4.1.9. Sound sample packs, template collections, and font collections are allowed if they are official releases, not freely available, and unarchived. Sound sample packs, template collections, and font collections must be official compilations and they must not be uploaded as an archive. The files contained inside the torrent must not be archived so that users can see what the pack contains. That means if sound sample packs are in WAV format, they must be uploaded as WAV. If the font collection, template collection, or sound sample pack was originally released as an archive, you must unpack the files before uploading them in a torrent. None of the contents in these packs and collections may be freely available.
Because the torrent you uploaded contains either a freely available application or an archived collection, you have been issued a 1-week warning.'
				)
			),
			'collection' => array(
				'priority' => '3',
				'title' => 'Collection of Cracks',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '4.1.11. Collections of cracks, keygens or serials are not allowed. The crack, keygen, or serial for an application must be in a torrent with its corresponding application. It cannot be uploaded separately from the application.
Because the torrent you uploaded was a collection of serials, keygens, or cracks, you have been issued a 1-week warning.'
				)
			),
			'hack' => array(
				'priority' => '6',
				'title' => 'Hacking Tool',
				'report_messages' => array(
					'Please include as much information as possible to verify the report',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '4.1.12. Torrents containing hacking or cracking tools are prohibited.
Because the torrent you uploaded contained a hacking tool, you have been issued a 1-week warning.'
				)
			),
			'virus' => array(
				'priority' => '6',
				'title' => 'Contains Virus',
				'report_messages' => array(
					'Please include as much information as possible to verify the report.  Please also double check that your virus scanner is not incorrectly identifying a keygen or crack as a virus.',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => 'The torrent was determined to be infected with a virus/trojan. In the future, please scan all potential uploads with an antivirus program such as AVG, Avast, or MS Security Essentials.'
				)
			),
			'notwork' => array(
				'priority' => '6',
				'title' => 'Not Working',
				'report_messages' => array(
					'Please include as much information as possible to verify the report.',
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => 'This program was determined to be not fully functional.'
				)
			)
		),
		'3' => array( //Ebook Rules Broken
			'unrelated' => array(
				'priority' => '27',
				'title' => 'Unrelated Ebooks',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => '6.5. Collections/packs of ebooks are prohibited, even if each title is somehow related to other ebook titles in some way. All ebooks must be uploaded individually and cannot be archived (users must be able to see the ebook format in the torrent).'
				)
			)
		),
		'4' => array( //Audiobook Rules Broken
			'skips' => array(
				'priority' => '21',
				'title' => 'Skips / Encode Errors',
				'report_messages' => array(
					"Please tell us which track(s) we should check.",
					"Telling us where precisely in the song the skip/encode error can be heard will help us deal with your torrent."
				),
				'report_fields' => array(
					'track' => '2'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '0',
					'delete' => '1',
					'pm' => "2.1.8. Music not sourced from vinyl must not contain pops, clicks, or skips. They will be deleted for rip/encode errors if reported."
				)
			)
		),
		'5' => array( //E-Learning vidoes Rules Broken
			'dissallowed' => array(
				'priority' => '2',
				'title' => 'Disallowed Topic',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '7.3. Tutorials on how to use musical instruments, vocal training, producing music, or otherwise learning the theory and practice of music are the only allowed topics. No material outside of these topics is allowed. For example, instruction videos about Kung Fu training, dance lessons, beer brewing, or photography are not permitted here. What is considered allowable under these topics is ultimately at the discretion of the staff.
Because the torrent you uploaded contains a video of a disallowed topic, you have been issued a 1-week warning.'
				)
			)
		),
		'6' => array( //Comedy Rules Broken
			'talkshow' => array(
				'priority' => '27',
				'title' => 'Talkshow/Podcast',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '1',
					'delete' => '1',
					'pm' => '3.3. No radio talk shows or podcasts are allowed. What.CD is primarily a music site, and those recordings do not belong in any torrent category.
Because the torrent you uploaded contains files sourced from a talk show or podcast, you have been issued a 1-week warning.'
				)
			)
		),
		'7' => array( //Comics Rules Broken
			'titles' => array(
				'priority' => '18',
				'title' => 'Multiple Comic Titles',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '',
					'delete' => '1',
					'pm' => '5.2.3. Collections may not span more than one comic title. You may not include multiple, different comic titles in a single collection, e.g., "The Amazing Spider-Man #1" and "The Incredible Hulk #1."'
				)
			),
			'volumes' => array(
				'priority' => '19',
				'title' => 'Multiple Volumes',
				'report_messages' => array(
					'Please include as much information as possible to verify the report'
				),
				'report_fields' => array(
					'link' => '0'
				),
				'resolve_options' => array(
					'upload' => '0',
					'warn' => '',
					'delete' => '1',
					'pm' => "5.2.6. Torrents spanning multiple volumes are too large and must be uploaded as separate volumes."
				)
			)
		)
	);
