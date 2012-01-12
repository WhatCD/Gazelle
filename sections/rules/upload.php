<?
//Include the header
show_header('Uploading Rules');
?>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.0/jquery.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		var original_value = $('#search_string').val();
		$('#search_string').keyup(function() {
			var query_string = $('#search_string').val();
			var q = query_string.replace(/\s+/gm, '').split('+');
			var regex = new Array();
			for (var i = 0; i < q.length; i++) {
				regex[i] = new RegExp(q[i], 'mi');
			}
			$('#actual_rules li').each(function() {
				var show = true;
				for (var i = 0; i < regex.length; i++) {
					if (!regex[i].test($(this).html())) {
						show = false;
						break;
					}
				}
				$(this).toggle(show);
			});
			$('.before_rules').toggle(query_string.length == 0);
		});
		$('#search_string').focus(function() {
			if ($(this).val() == original_value) {
				$(this).val('');
			}
		});
		$('#search_string').blur(function() {
			if ($(this).val() == '') {
				$(this).val(original_value);
				$('.before_rules').show();
			}
		})
	});
</script>
<!-- Uploading Rules -->
<div class="thin">
<!-- Uploading Rules Index Links -->
	<input type="text" id="search_string" value="Filter (empty to reset)" />
	<span id="Index">Example: The search term <strong>FLAC</strong> returns all rules containing <strong>FLAC</strong>. The search term <strong>FLAC+trump</strong> returns all rules containing both <strong>FLAC</strong> and <strong>trump</strong>.</span>
	<br />
	<div class="before_rules">
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul> 
				<li id="Introk"><a href="#Index"><strong>&uarr;</strong></a> <a href="#Intro"><strong>Introduction</strong></a></li>

				<li id="h1k"><strong><a href="#Index"><strong>&uarr;</strong></a></strong> <a href="#h1">1. <strong>Uploading Rules</strong></a>
					<ul>
						<li id="h1.1k"><strong><a href="#h1k"><strong>&uarr;</strong></a></strong> <a href="#h1.1">1.1. <strong>General</strong></a>						
						</li>	
						<li id="h1.2k"><a href="#h1k"><strong>&uarr;</strong></a> <a href="#h1.2">1.2. <strong>Specifically Banned</strong></a>						
						</li>

						<li id="h1.3k"><a href="#h1k"><strong>&uarr;</strong></a> <a href="#h1.3">1.3. <strong>Scene Uploads</strong></a>
						</li>
					</ul>
				</li>
				<li id="h2k"><a href="#Index"><strong>&uarr;</strong></a> <a href="#h2">2. <strong>Music</strong></a>
					<ul>

						<li id="h2.1k"><a href="#h2k"><strong>&uarr;</strong></a> <a href="#h2.1">2.1. <strong>General</strong></a>
						</li>					
						<li id="h2.2k"><a href="#h2k"><strong>&uarr;</strong></a> <a href="#h2.2">2.2. <strong>Duplicates &amp; Trumping</strong></a>
							<ul>
								<li id="r2.2.11k"><a href="#h2.2k"><strong>&uarr;</strong></a> <a href="#r2.2.11">2.2.11. <strong>Vinyl Specific Dupe Rules</strong></a>

								</li>
								<li id="r2.2.12k"><a href="#h2.2k"><strong>&uarr;</strong></a> <a href="#r2.2.12">2.2.12. <strong>MP3 Specific Dupe Rules</strong></a>
								</li>
								<li id="r2.2.13k"><a href="#h2.2k"><strong>&uarr;</strong></a> <a href="#r2.2.13">2.2.13. <strong>Ogg Vorbis Specific Dupe Rules</strong></a>
								</li>
								<li id="r2.2.14k"><a href="#h2.2k"><strong>&uarr;</strong></a> <a href="#r2.2.14">2.2.14. <strong>AAC Specific Dupe Rules</strong></a>

								</li>
								<li id="r2.2.15k"><a href="#h2.2k"><strong>&uarr;</strong></a> <a href="#r2.2.15">2.2.15. <strong>FLAC Specific Dupe Rules</strong></a>
								</li>
							</ul>
						</li>
						<li id="h2.3k"><a href="#h2k"><strong>&uarr;</strong></a> <a href="#h2.3">2.3. <strong>Formatting</strong></a>	
						</li>

					</ul>
				</li>
				<li id="h3k"><a href="#Index"><strong>&uarr;</strong></a> <a href="#h3">3. <strong>Applications</strong></a>
					<ul>
						<li id="h3.1k"><a href="#h3k"><strong>&uarr;</strong></a> <a href="#h3.1">3.1. <strong>General</strong></a>
						</li>

						<li id="h3.2k"><a href="#h3k"><strong>&uarr;</strong></a> <a href="#h3.2">3.2. <strong>Duplicates &amp; Trumping</strong></a>
						</li>
					</ul>
				</li>
				<li id="h4k"><a href="#Index"><strong>&uarr;</strong></a> <a href="#h4">4. <strong>Comic Books</strong></a>

					<ul>
						<li id="h4.1k"><a href="#h4k"><strong>&uarr;</strong></a> <a href="#h4.1">4.1. <strong>General</strong></a>
						</li>
						<li id="h4.2k"><a href="#h4k"><strong>&uarr;</strong></a> <a href="#h4.2">4.2. <strong>Multi-comic</strong></a>
						</li>
						<li id="h4.3k"><a href="#h4k"><strong>&uarr;</strong></a> <a href="#h4.3">4.3. <strong>Duplicates &amp; Trumping</strong></a>

						</li>
						<li id="h4.4k"><a href="#h4k"><strong>&uarr;</strong></a> <a href="#h4.4">4.4. <strong>Formatting</strong></a>
						</li>
					</ul>
				</li>
				<li id="h5k"><a href="#Index"><strong>&uarr;</strong></a> <a href="#h5">5. <strong>eBooks, eLearning Books &amp; Sheet Music</strong></a>

				</li>
				<li id="h6k"><a href="#Index"><strong>&uarr;</strong></a> <a href="#h6">6. <strong>Comedy (Audio) &amp; Audio Books</strong></a>
				</li>
				<li id="h7k"><a href="#Index"><strong>&uarr;</strong></a> <a href="#h7">7. <strong>eLearning Videos</strong></a>
				</li>

			</ul>
		</div>
	</div>
<!-- Actual Uploading Rules -->	
	<div id="actual_rules">
		<div class="before_rules">
			<h4 id="Intro"><a href="#Introk"><strong>&uarr;</strong></a> Introduction</h4>
			<div class="box pad" style="padding:10px 10px 10px 20px;">
				<p>The upload rules below appear overwhelmingly long and detailed at first glance. However, the length is for explaining the rules clearly and thoroughly. A summary of each rule is in <strong>bold text</strong> before the actual rule for easier reading. You may also find the corresponding rule sections in the <a href="#Index">Index</a>. The corresponding <a href="#"><strong>&uarr;</strong></a> (move one level up) and <a href="#Index">rule section links</a> (move down into the document) help provide quick navigation.</p>		
				<p>If you are still unsure of what a rule means before uploading something, PLEASE ask at any of the following forms of site user support: <a href="staff.php">First-Line Support</a>, <a href="forums.php?action=viewforum&amp;forumid=8">The Help Forum</a>, <a href="wiki.php?action=article&amp;name=IRC">#what.cd-help on IRC</a>. Privately message a <a href="staff.php">moderator</a> on the site if other support has directed you to a moderator for resolution or support has been unhelpful in your case. If you find any dead links in the upload rules, let a <a href="staff.php">staff member</a> know so it can be promptly fixed.</p>

			</div>
		</div>
		
		<h4 id="h1"><a href="#h1k"><strong>&uarr;</strong></a> <a href="#h1">1.</a> Uploading Rules</h4>
		
		<h5 id="h1.1"><a href="#h1.1k"><strong>&uarr;</strong></a> <a href="#h1.1">1.1.</a> General</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r1.1.1"><a href="#h1.1"><strong>&uarr;_</strong></a> <a href="#r1.1.1">1.1.1.</a> <strong>Only music, applications, comic books, eBooks, comedy (audio), audiobooks, and eLearning videos are allowed here.</strong>

				</li>
				<li id="r1.1.2"><a href="#h1.1"><strong>&uarr;_</strong></a> <a href="#r1.1.2">1.1.2.</a> <strong>Duplicate torrents in any category are not allowed.</strong> There are some exceptions to this rule, which are outlined in their relevant sections below.
				</li>
				<li id="r1.1.3"><a href="#h1.1"><strong>&uarr;_</strong></a> <a href="#r1.1.3">1.1.3.</a> <strong>No freely available content in non-music sections.</strong> If you could just download from the web, so can everyone else. Each main section explains in greater detail what "freely available" means in context. <a href="#r2.1.9">See 2.1.9</a> in regards to freely available music.
				</li>

				<li id="r1.1.4"><a href="#h1.1"><strong>&uarr;_</strong></a> <a href="#r1.1.4">1.1.4.</a> <strong>Seed complete copies.</strong> Do not upload a torrent unless you intend to seed until there are at least 1.0 distributed copies. Seeding past this minimum is strongly encouraged.
				</li>
				<li id="r1.1.5"><a href="#h1.1"><strong>&uarr;_</strong></a> <a href="#r1.1.5">1.1.5.</a> <strong>No advertisements.</strong> Do not advertise other sites in your torrent descriptions, torrent directories, or the contents of your torrent. We have no advertising and neither should you. <u>Exceptions</u>: Additional information about an artist, album, or band is acceptable, and does not count as advertising.
				</li>

				<li id="r1.1.6"><a href="#h1.1"><strong>&uarr;_</strong></a> <a href="#r1.1.6">1.1.6.</a> <strong>Archived files in uploads are not allowed.</strong> <u>Exceptions</u>: The sections that allow archived files (e.g. zip, rar, iso, etc.) are the following:
					<ul>
						<li>Comic Books (.cbr and .cbz).</li>
						<li>Scene released torrents in non-music categories.</li>
						<li>eBooks and sheet music may be individually archived.</li>

					</ul> 
				</li>
			</ul>
		</div>
		
		<h5 id="h1.2"><a href="#h1.2k"><strong>&uarr;</strong></a> <a href="#h1.2">1.2.</a> Specifically Banned</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r1.2.1"><a href="#h1.2"><strong>&uarr;_</strong></a> <a href="#r1.2.1">1.2.1.</a> <strong>Anything not specifically allowed below.</strong> If you have any doubts, ask before uploading.
				</li>

				<li id="r1.2.2"><a href="#h1.2"><strong>&uarr;_</strong></a> <a href="#r1.2.2">1.2.2.</a> <strong>Any car parts and car data programs.</strong> This ban includes programs like AllData and vendor-specific diagnostic programs such as Carsoft.
				</li>
				<li id="r1.2.3"><a href="#h1.2"><strong>&uarr;_</strong></a> <a href="#r1.2.3">1.2.3.</a> <strong>Videos of any kind (other than eLearning Videos).</strong> No movies, no TV shows, no concerts, and no data/video tracks from enhanced CDs.
				</li>
				<li id="r1.2.4"><a href="#h1.2"><strong>&uarr;_</strong></a> <a href="#r1.2.4">1.2.4.</a> <strong>Pornography or nudity of any kind.</strong> This ban includes pictures, erotic comic books or hentai, sex manuals, erotic magazines, etc.
				</li>

				<li id="r1.2.5"><a href="#h1.2"><strong>&uarr;_</strong></a> <a href="#r1.2.5">1.2.5.</a> <strong>Games of any kind.</strong> No games of any kind are allowed: whether PC, Mac, phone or any other platform.
				</li>
				<li id="r1.2.6"><a href="#h1.2"><strong>&uarr;_</strong></a> <a href="#r1.2.6">1.2.6.</a> <strong>Collections of pictures or wallpapers are not applications.</strong> You may not upload them to any category.
				</li>
				<li id="r1.2.7"><a href="#h1.2"><strong>&uarr;_</strong></a> <a href="#r1.2.7">1.2.7.</a> <strong>User compilations in any category.</strong> All "packs" must be reasonably official as specified in each category.
				</li>

				<li id="r1.2.8"><a href="#h1.2"><strong>&uarr;_</strong></a> <a href="#r1.2.8">1.2.8.</a> <strong>DRM-restricted files:</strong> files must not be encrypted or in a restricted format that impedes sharing. It is also highly encouraged that you remove personal information from non-DRM protected files (such as iTunes Plus releases).
				</li>
			</ul>
		</div>
		
		<h5 id="h1.3"><a href="#h1.3k"><strong>&uarr;</strong></a> <a href="#h1.3">1.3.</a> Scene Uploads</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">

			<ul>
				<li id="r1.3.1"><a href="#h1.3"><strong>&uarr;_</strong></a> <a href="#r1.3.1">1.3.1.</a> <strong>Do not add irrelevant credits to your torrent.</strong> Your name is credited to the upload, and there's no need to plaster it all over the place!
				</li>
				<li id="r1.3.2"><a href="#h1.3"><strong>&uarr;_</strong></a> <a href="#r1.3.2">1.3.2.</a> <strong>You may give credit to the release group (optional).</strong> If you really want to give credit to the release group, mention the full release name, including group name in the <em>Release description</em> section.
				</li>

				<li id="r1.3.3"><a href="#h1.3"><strong>&uarr;_</strong></a> <a href="#r1.3.3">1.3.3.</a> <strong>No NFO art pasted in the album description.</strong> Unedited NFOs are allowed within the specific <em>Release description</em>&ndash;not the <em>Album description</em>. If you must include some information from the NFO in the <em>Album description</em> or torrent description, include only the tracklist, album notes, and other essential information. Specific encoding settings belong in the <em>Release description</em>.
				</li>

				<li id="r1.3.4"><a href="#h1.3"><strong>&uarr;_</strong></a> <a href="#r1.3.4">1.3.4.</a> <strong>Use the correct actual album title on the <a href="upload.php">upload page</a>; do not use the scene-given title.</strong> Naming your <em>albums_titles_like_this</em> or <em>your.albums.like.this</em> is not allowed. Use the actual release title and artist. Do not use the title from the folder or NFO of the scene release.
				</li>
				<li id="r1.3.5"><a href="#h1.3"><strong>&uarr;_</strong></a> <a href="#r1.3.5">1.3.5.</a> <strong>Scene releases must be complete (as released) to use the scene label.</strong> If you've changed the tags, unpacked the archive, removed any files, split the tracks, or altered the naming, then it is no longer a scene release. It should not be labeled as such.
				</li>

				<li id="r1.3.6"><a href="#h1.3"><strong>&uarr;_</strong></a> <a href="#r1.3.6">1.3.6.</a> <strong>No protected archives.</strong> Archived releases must not be password protected.
				</li>
				<li id="r1.3.7"><a href="#h1.3"><strong>&uarr;_</strong></a> <a href="#r1.3.7">1.3.7.</a> <strong>Scene releases must conform to rules specified for their respective section.</strong> For example, music scene releases must adhere to the music quality and formatting rules no matter how the original files were released. If the scene archives were password protected, you cannot upload them to this site unmodified. <u>Exceptions:</u> You may upload scene releases that originally do not fit in the rules if you can make the necessary changes within the rules. However, these modified uploads must not be labeled as "scene."
				</li>

			</ul>
		</div>
		
		<h4 id="h2"><a href="#h2k"><strong>&uarr;</strong></a> <a href="#h2">2.</a> Music</h4>
		
		<h5 id="h2.1"><a href="#h2.1k"><strong>&uarr;</strong></a> <a href="#h2.1">2.1.</a> General</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r2.1.1"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.1">2.1.1.</a> <strong>The only formats allowed for music consist of the following lossy and lossless formats:</strong>
					<ul>
						<li><strong>Lossy:</strong> MP3, Ogg Vorbis, AAC, AC3, DTS</li>
						<li><strong>Lossless:</strong> FLAC</li>
					</ul>
					<p><em>Only standard versions of each format are allowed. Hybrid formats that combine both lossless and lossy audio data such as DTS-HD and mp3HD are not allowed. AC3 and DTS are reserved for commercial media sources only if they contain such tracks; transcoding from any other source, including lossless (e.g. PCM and MLP formats), is not allowed.</em></p>
				</li>
				<li id="r2.1.2"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.2">2.1.2.</a> <strong>No transcodes or re-encodes of lossy releases are acceptable here.</strong> There are no exceptions regardless of how rare you claim the release is. The only acceptable transcodes are releases that were transcoded from a lossless source (e.g. CD, SBD, DAT, Vinyl, SACD, LPCM). <u>Exceptions</u>: Official lossy-mastered releases are not considered to be transcodes. They are allowed on the site. It is highly recommended you provide proof in at least one of the following forms: a) digital photo proving ownership of the CD or hi-res scan of the CD top b) a rip log c) confidence of 2 or more verification with AccurateRip in the rip log or after ripping (with <a href="http://www.hydrogenaudio.org/forums/index.php?showtopic=53583">arcue.pl</a> / <a href="http://www.hydrogenaudio.org/forums/index.php?showtopic=60440">arcue.exe</a>, <a href="http://wiki.hydrogenaudio.org/index.php?title=CueTools">CUE Tools</a>, or equivalent) in your <em>Release description</em>. You must provide this proof if requested by staff.
				</li>

				<li id="r2.1.3"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.3">2.1.3.</a> <strong>Music releases must have an average bitrate of at least 192kbps regardless of the format.</strong> <u>Exceptions</u>: The following VBR encodes may go under the 192kbps limit: LAME -V2, -V1, -V0, APS, APX, MP3 192 (VBR), Ogg Vorbis -q8 (~256 VBR), and AAC ~192 (VBR) to AAC ~256 (VBR) releases.
					<ul> 
						<li id="r2.1.3.1"><strong>Recommendations for MP3 encoding:</strong> We strongly encourage the latest Hydrogenaudio recommended stable release of LAME (currently 3.98.4) for MP3 encoding. You can find out more information about LAME and its options at the <a href="http://wiki.hydrogenaudio.org/index.php?title=LAME">Hydrogenaudio Wiki</a>.
						</li>
						<li id="r2.1.3.2"><strong>Recommendations for Ogg Vorbis encoding:</strong> We strongly encourage the use of newer and well-maintained Ogg Vorbis encoders such as Aoyumi's aoTuV series of encoders. However, as long as the encoder satiates the requirements for Ogg Vorbis presets and average bitrates on this site, you may use any encoder you prefer. For more information on aoTuV and other Ogg Vorbis encoders, <a href="http://wiki.hydrogenaudio.org/index.php?title=Recommended_Ogg_Vorbis#Recommended_Vorbis_Encoders">read the wiki page at Hydrogenaudio</a>.
						</li>

						<li id="r2.1.3.3"><strong>Recommendations for AAC encoding:</strong> Encoding object types LC/Low Complexity and HE/High Efficiency are strongly encouraged. LC/Low Complexity is recommended for music uploads. HE/High Efficiency is optimized for lower bitrates, which makes it ideal for comedy or audio books. You may use other encoding profiles and object types, but they will not be compatible on most portable players, media players, and systems. Find out more information <a href="http://wiki.hydrogenaudio.org/index.php?title=AAC">here</a>.
						</li>
					</ul>
				</li>
				<li id="r2.1.4"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.4">2.1.4.</a> <strong>Bitrates must accurately reflect encoder presets or average bitrate of the audio files.</strong> You are responsible for supplying correct format and bitrate information on the upload page. All audio torrents must have accurate labeling for the encoder settings used or the average bitrate. This means that lossy formats such as MP3, AAC, DTS, and Ogg Vorbis cannot have a bitrate of "lossless." Conversely, FLAC cannot have fixed bitrates or xxx(VBR) bitrates. Non-LAME formats and MP3 encoders cannot use LAME's VBR switches (-V0, -V1, -V2, etc). If you are uploading a non-LAME MP3, determine the average bitrate of the files. Then select "Other" for bitrate and type in xxx (VBR) where "xxx" is the bitrate you found. Some tools to determine the bitrates of audio files are <a href="http://www.free-codecs.com/download/Audio_Identifier.htm">Audio Identifier</a>, <a href="http://www.burrrn.net/?page_id=6">Mr. Questionman</a> (<a href="http://www.softpedia.com/get/Multimedia/Audio/Other-AUDIO-Tools/Mr-QuestionMan.shtml">mirror</a>), or <a href="http://bitheap.org/dnuos/">Dnuos</a>. If the bitrate is labeled incorrectly on your torrent, your torrent may be deleted if reported.
				</li>

				<li id="r2.1.5"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.5">2.1.5.</a> <strong>Albums must not be ripped or uploaded as a single track.</strong> If the tracks on the original CD were separate, you must rip them to separate files. Clearly, CDs with single tracks are permitted to be uploaded without prior splitting. You are encouraged to use <a href="http://musicutter.szm.sk/">MusiCutter</a> or <a href="http://mpesch3.de1.cc/mp3dc.html">MP3DirectCut</a> for MP3 and <a href="http://wiki.hydrogenaudio.org/index.php?title=CueTools">CUE Tools</a>, <a href="http://tmkk.pv.land.to/xld/index_e.html">XLD</a>, or <a href="http://www.exactaudiocopy.de/">EAC</a> for lossless if you need to split up an album image into individual tracks. <u>Exceptions</u>: Gapless DJ or professional mixes released as MP3+CUE images are allowed as unseparated album images on the site. This includes scene DJ mixes. No other format is allowed in this unsplit form. Unsplit MP3 albums containing separate tracks are not allowed if missing a cue sheet.
				</li>

				<li id="r2.1.6"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.6">2.1.6.</a> <strong>All music torrents must be encoded with a single encoder using the same settings.</strong> This means you cannot create a torrent which contains both CBR and VBR files, nor can you upload torrents containing a mix of APS (VBR)/-V2 (VBR) and APX (VBR)/-V0 (VBR). Including other kinds of audio quality, such as duplicate lossy files in a lossless torrent is also prohibited. This kind of release is referred to as a "mutt rip."
				</li>
				<li id="r2.1.7"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.7">2.1.7.</a> <strong>Use only the allowed container formats for audio files.</strong> Use .m4a and .mp4 for AAC, and .ogg for Vorbis only. All other formats should not be encapsulated in containers. (e.g. FLAC must not be in an Ogg container, MP3 must not be in an .m4a container, and so forth.) <u>Exceptions</u>: DTS CD-sourced audio rips, although contained in WAV, should have the .dts extension.
				</li>

				<li id="r2.1.8"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.8">2.1.8.</a> <strong>Music not sourced from vinyl must not contain pops, clicks, or skips.</strong> They will be deleted for rip/encode errors if reported. Music that is sourced from vinyl must not have excessive problems.
				</li>
				<li id="r2.1.9"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.9">2.1.9.</a> <strong>Freely available music is allowed.</strong> Music uploaded may be freely available on the web (come from official sources such as record label, band websites, or the <a href="http://www.archive.org/index.php">Internet Archive</a>). Uploads can come from other torrent sites, but you take responsibility for determining the music is not transcoded. You are highly encouraged to include a link to the original host site or a note about the source for freely available files. However, this is not required and not grounds for reporting a torrent if missing such a link. All freely available music must conform to quality rules and formatting rules. This means it must be tagged correctly, not be a transcode, be separate tracks, and so forth. Freely available music uploads should have the "WEB" media format if no other source media (e.g. CD, DVD, etc.) can be established for the files.
				</li>

				<li id="r2.1.10"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.10">2.1.10.</a> <strong>Label water-marked or voice-over releases clearly.</strong> Watermarks or voice-overs must be clearly indicated in the torrent description. The torrent will be deleted for quality misrepresentation if not noted.
				</li>
				<li id="r2.1.11"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.11">2.1.11.</a> <strong>Radio, television, web rips and podcasts are not allowed.</strong> It does not matter whether it's FM, direct satellite, internet, or even if it's a pre-broadcast tape. It's too difficult for staff to moderate such things. Radio does not have enough bandwidth to meet our 192kbps rule. Anything on the radio has already gone through several conversions or re-encodes. <em>Note: Do not confuse web rips for WEB. The "WEB" media label on the upload page is for digital downloads, and has nothing to do with web rips.</em>

				</li>
				<li id="r2.1.12"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.12">2.1.12.</a> <strong>No unofficial audience recordings.</strong> Unofficially-mastered audience recordings (AUD) are not allowed here regardless of how rare you think they are. These include recordings on bootleg labels and those mastered by fans or traders. Unofficial IEM (In Ear Monitor), ALD (Assistive Listening Device), Mini-Disc, and Matrix-sourced recordings are also not allowed. None of those are pure soundboard quality either. <u>Exceptions</u>: Officially-remastered AUD/IEM/ALD/Mini-Disc/Matrix recordings are allowed. These may be re-appropriated recordings released with the artist's or their label's consent. Bonus tracks from such recording sources are also exempt since the tracks are released officially with the album.
				</li>
				<li id="r2.1.13"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.13">2.1.13.</a> <strong>Tape (VHS, Video-8, etc.) music sources are not suitable for this site.</strong> The frequency range is not good enough to meet the high quality we strive to maintain here. Hi-fi formats of tapes, such as Hi-Fi VHS, have good frequency range above regular standards (near CD quality at times). However, none of these sources are allowed on the site for moderation reasons.
				</li>

				<li id="r2.1.14"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.14">2.1.14.</a> <strong>Cassettes are allowed under strict conditions.</strong> Rare releases <em>available only on cassette</em> may be uploaded under strict special conditions. Cassette-sourced uploads must be approved by staff first. You must contact a <a href="staff.php">moderator</a> privately for approval before uploading. Include proof in the form of discography information from a reputable source as well as <a href="forums.php?action=viewthread&threadid=1200">spectrals</a> of 2 songs in your message. Staff reserves the right to refuse any badly-done cassette rips. Staff will immediately reject any cassette sources with poor lineage such as Audience recordings, bootlegs, or multi-generational rips. Unapproved cassette torrents may be reported and will be deleted if no note exists of prior staff approval. Anyone found falsifying staff notes will have their uploading privileges removed.
				</li>
				<li id="r2.1.15"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.15">2.1.15.</a> <strong>The only lossy multichannel audio formats allowed are AC3 and DTS.</strong> If the source is DTS or AC3, do not transcode to other formats. <u>Exceptions</u>: Transcoding is allowed if the source is lossless (LPCM or MLP). Those should be compressed with multichannel FLAC. <em>Note: DTS-CD rips cannot be re-compressed to a lossless codec and they must be kept as WAV files with the .dts extension.</em>

				</li>
				<li id="r2.1.16"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.16">2.1.16.</a> <strong>SACD hybrid rip sources must be properly labeled.</strong> SACD hybrid discs ripped from the CD layer should be labeled as CD and not SACD. If you used your CD/DVD drive and a CD ripper (such as <a href="http://www.exactaudiocopy.de/">EAC</a>) to extract the audio, then label it as "CD" source (i.e. it's not true 24-bit). If the rip came from the genuine digital SACD layer through a SACD player mod or line out, it should be 24-bit quality SACD. You may include "SACD Hybrid" in the <em>Edition Information</em> box on the <a href="upload.php">upload page</a>.
				</li>

				<li id="r2.1.17"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.17">2.1.17.</a> <strong>WEB media is for digital downloads only.</strong> Digital downloads released only on the internet from internet sources cannot be given the CD media label on the upload page. This includes downloads from the iTunes Store, LiveDownloads, Beatport, Amazon.com, Rhapsody, and other webstores. Scene releases with no information of sources must also be labeled as WEB. Freely available music with no source information must also be labeled WEB. If possible, indicate the source of your files (e.g. webstore) in the torrent description. You are responsible that the downloaded files conform to What.CD's rules for music quality. <em>Note: Do not confuse WEB for web rips. WEB media torrents are not always web rips (<a href="wiki.php?action=article&amp;id=14">transcodes</a>). Please check the spectrals before assuming they are web rips.</em>
				</li>
				<li id="r2.1.18"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.18">2.1.18.</a> <strong>User-made compilations are not allowed.</strong> Compilations must be reasonably official. For example, "34 of my favourite Grateful Dead songs" is not a reasonably official collection. Compiling a release from a list, such as a Top 100 Billboard Chart, is not allowed. User-made and unofficial multichannel mixes are also not allowed. <u>Exceptions</u>: Bootlegs are allowed if they meet quality standards for music and are proven to be retail releases in digital or physical form. Bootlegs/mixtapes assembled and available from internet-only sources (e.g. music blogs, message boards, etc.) are not considered official enough for this site.
				</li>

				<li id="r2.1.19"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.19">2.1.19.</a> <strong>No comedy, audio books, or spoken word releases may be uploaded in the music category.</strong> Spoken word releases are governed by a different set of standards than music. Thus they do not belong in the music category.
				</li>
				<li id="r2.1.20"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.20">2.1.20.</a> <strong>Sound sample packs must be uploaded to the Applications category.</strong> These releases are allowed only under the apps category; they are forbidden in music and other categories. Sound sample packs may use formats other than those allowed for music, and must be official collections. <a href="#r3.1.8">See 3.1.8.</a> for more information.
				</li>

				<li id="r2.1.21"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.21">2.1.21.</a> <strong>All music torrents must represent a complete album (a complete collection release).</strong> Albums must not be missing tracks, or discs in the case of a multi-disc release. All music torrents must be one release. If an album is released as a multiple set of CDs, then it must be uploaded as a single torrent. Uploading each disc separately is not allowed. Digital downloads of albums must consist of entire albums. If tracks are available separately, but not released as singles, you may not upload them individually. <u>Exceptions</u>: Bonus discs may be uploaded separately in accordance with <a href="rules.php?p=upload#r2.2.15">this rule</a>. Please note that individual bonus tracks are not allowed to be uploaded without the rest of the album. Bonus tracks are not bonus discs. Enhanced audio CDs with data or video tracks must be uploaded without the non-audio tracks. If you want to share the videos or data, you may host the files off-site with a file sharing service and include the link in your torrent description.
				</li>
				<li id="r2.1.22"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.22">2.1.22.</a> <strong>Multi-album torrents are not allowed on the site under any circumstances.</strong> That means no discographies, Pitchfork compilations, etc. Discographies make it a hassle for users to download individual albums. They result in a lot of duplicate albums being uploaded and are generally very poor quality. If releases (e.g. CD singles) were never released bundled together, do not upload them together. Live SBD material should be one torrent per night, per show, or per venue. Including more than one show in a torrent results in a multi-album torrent. <u>Exceptions</u>: Only official boxsets and official compilation collections can contain multiple albums.
				</li>
				<li id="r2.1.23"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.23">2.1.23.</a> <strong>Edition Information must be provided for digitally-sourced torrents.</strong> Digitally-sourced (including CD-sourced) rips of material first released before the availability of their source medium must have accurate edition information. For example, if a CD rip of an album whose original release date was 1957, predating the creation and distribution of CDs, then the uploader must make note of the correct year the CD was pressed, and preferably catalog identification as well. Rips for which Edition Information cannot be provided must be marked as an 'Unknown Release'. Under no circumstances must you guess or feign Edition Information.
				</li>		
				<li id="r2.1.24"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.24">2.1.24.</a> <strong>Downsampling of analog rips is allowed.</strong> Analog rips that have been downsampled may be uploaded (e.g. a 24/96 vinyl rip downsampled to 16/44). Any downsampled torrents must include the specific programs and methods used to downsample in addition to the lineage for the original rip or it will be deleted. <i>NOTE: downsampling of CDs is expressly forbidden.</i>
				</li>
				<li id="r2.1.25"><a href="#h2.1"><strong>&uarr;_</strong></a> <a href="#r2.1.25">2.1.25.</a> <strong><a href="wiki.php?action=article&amp;id=386">Pre-emphasis</a> is allowed in lossless torrents only.</strong> Lossless FLAC torrents with pre-emphasis are allowed on the site. They are allowed to co-exist with lossless de-emphasised torrents. On the other hand, lossy formats may not have pre-emphasis and will be deleted if uploaded.
				</li>
			</ul>

		</div>
		
		<h5 id="h2.2"><a href="#h2.2k"><strong>&uarr;</strong></a> <a href="#h2.2">2.2.</a> Duplicates &amp; Trumping</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r2.2.0"> <a href="#r2.2.0">2.2.0.</a> <strong>Overview</strong>

				<p style="text-align:center">
						<img src="static/common/trumpchart.png" alt="Audio Dupe and Trump Chart" />					<br /><em>This chart is an overview of how the dupe and trump rules work.</em>
					</p>
				</li>
				<li id="r2.2.1"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.1">2.2.1.</a> <strong>Upload an allowed format if it doesn't exist on the site.</strong> If there is no existing torrent of the album in the format you've chosen, you can upload it in any bitrate that at least averages 192kbps.
				</li>

				<li id="r2.2.2"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.2">2.2.2.</a> <strong>Same bitrates and formats of the same releases are duplicates.</strong> If a torrent is already up in the format and bitrate you want to upload it in, you are not allowed to upload it&ndash;it's a duplicate (dupe). So if there's a 192kbps CBR MP3 version on the site, you are not allowed to upload another 192kbps CBR MP3. You cannot trump an existing torrent with a version that includes album art. <u>Exceptions:</u> Different editions and source media do not count as dupes. Refer to <a href="#r2.2.6">rule 2.2.6.</a> for more information.
				</li>
				<li id="r2.2.3"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.3">2.2.3.</a> <strong>Report all trumped and duplicated torrents.</strong> If you trump a torrent or notice a duplicate torrent, please use the report link (RP) to notify staff for removal of the old or duplicate torrent. This especially applies to lossless rips. If you are uploading a superior rip to the current one in the same format on the site, report the older torrent and include a link to your torrent in the report. Your torrent will be deleted as a dupe if the older torrent is not reported. <em>Note: Trump = You can trump a torrent that exists on the site by uploading a torrent in a preferred bitrate or quality as specified in the rules below. A torrent that is trumped by another torrent is considered a duplicate ("dupe") of the superior torrent, since the two torrents are not allowed to coexist on the site.</em>

				</li>
				<li id="r2.2.4"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.4">2.2.4.</a> <strong>Dupe rules also apply against previous uploads.</strong> All of the following dupe rules concerning music formats in this section may apply to torrents uploaded before. For example, if you upload an album in 192kbps MP3, someone can upload the same album in 320kbps at a later date and trump your 192kbps MP3.
				</li>
				<li id="r2.2.5"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.5">2.2.5.</a> <strong>Scene and non-scene releases of the same release, bitrate, and format are dupes.</strong> The "scene" label does not make torrents unique from each other. If a scene -V2 (VBR) of an album from CD is already uploaded, you may not upload another -V2 (VBR) of the same album from CD.
				</li>

				<li id="r2.2.6"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.6">2.2.6.</a> <strong>Different editions and source media count as separate releases.</strong> A rip from a different medium (e.g. vinyl) or release (e.g. a remaster) of an already existing torrent counts as a different release. The dupe rules do not apply between the two different edition torrents, nor two differently sourced torrents. So if a FLAC ripped from a CD is already up, you are still allowed to upload a FLAC ripped from vinyl. And if a 320kbps CBR MP3 release of an original mastering of an album was uploaded, you are still allowed to upload a 320kbps CBR MP3 remaster. <u>Exceptions</u>: Only one edition of each unofficial live recording is allowed. Such bootlegs can be unofficially remastered several times, and such constant remastering is of little consequence on a site where bootlegs are not the primary focus. Only one unofficial soundboard recording of each show is allowed, and it should be uploaded on the torrent page with no edition information selected.
				</li>			
				<li id="r2.2.7"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.7">2.2.7.</a> <strong>Complete or untouched releases replace incomplete or watermarked versions.</strong> Watermarked promos containing voice-overs and similar can be trumped by a non-watermarked release. Releases missing hidden or pre-gap tracks can be replaced by proper rips that include the range. These hidden or pre-gap tracks should be in their own file, not appended to the beginning of track one, and releases where the hidden track or pre-gap is appended can be trumped by one where the track is a separate file.
				</li>
				<li id="r2.2.8"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.8">2.2.8.</a> <strong>Bonus disc-only uploads can co-exist with the complete set in accordance with the trumping rules.</strong> A bonus disc-only release can be trumped by an upload containing the "full" original album + bonus discs, in the same format, in accordance with the <a href="rules.php?p=upload#r2.2.15">usual trump rules</a>.
				</li>

				<li id="r2.2.9"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.9">2.2.9.</a> <strong>Higher bitrate CBR (Constant Bitrate) and ABR (Average Bitrate) torrents replace lower ones.</strong> Once a CBR (Constant Bitrate) rip has been uploaded, no CBR rips of that bitrate or lower can be uploaded. In the same manner, once an ABR (Average Bitrate) rip has been uploaded, no ABR rips of that bitrate or lower can be uploaded. For example, if a 320kbps CBR rip is already on the site, you are not allowed to upload a 256kbps CBR. ABR and CBR may be interchangeably trumped. A CBR can trump a lower bitrate ABR and an ABR may trump a lower bitrate CBR. You may not upload a 192kbps CBR if a 256kbps ABR of the same release is already up. 
				</li>
				<li id="r2.2.10"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.10">2.2.10.</a> <strong>Lossy format torrents with .log files do not replace equivalent existing torrents.</strong> If you want to upload a lossy format (e.g. MP3, Ogg Vorbis, AAC, etc.) with a log that already exists in the same medium and edition, you may not replace the current lossy torrent. For example, a -V2 (VBR) MP3 torrent with a log cannot replace a -V2 (VBR) without a log. Same applies for lossy audio torrents with .m3u, .cue, and other additions such as artwork. <u>Exceptions</u>: If an existing torrent contains encode/rip errors, you may upload another copy without errors. The bad torrent needs to be reported or yours will be deleted as a dupe. Also, <a href="#r2.3.8">see rules 2.3.8 through 2.3.16</a> for replacing badly named and tagged torrents.
				</li>

				<li id="r2.2.11"><a href="#r2.2.11k"><strong>&uarr;_</strong></a> <a href="#r2.2.11">2.2.11.</a> <strong>Vinyl Specific Dupe Rules</strong>
					<ul>
						<li id="r2.2.11.1"><a href="#r2.2.11"><strong>&uarr;_</strong></a> <a href="#r2.2.11.1">2.2.11.1.</a> <strong>Only one lossy vinyl rip in a specific bitrate is allowed per release.</strong> Once someone has uploaded a lossy-format vinyl rip (in MP3, Ogg Vorbis or AAC), you may not upload another copy in the same bitrate. It does not matter whether if the lossy files are of a different sample rate found on the site. For example, if a 48 kHz -V2 (VBR) copy is already up, you may not upload the same album in -V2 (VBR) at 44.1 kHz.
						</li>
						<li id="r2.2.11.2"><a href="#r2.2.11"><strong>&uarr;_</strong></a> <a href="#r2.2.11.2">2.2.11.2.</a> <strong>Only one lossless and one 24bit lossless vinyl rip is allowed per edition.</strong> A poor sounding lossless rip may be trumped by a better sounding lossless rip, regardless of lineage information. The same quality trump can occur for 24bit lossless rips. To trump a rip for a better sounding version, you need to report it with clear informations about how your rip sounds better than the other one. Rips of extremely poor quality may be deleted outright if reported. All quality trumps/deletions of this nature are at the discretion of the moderator involved.
						</li>
						<li id="r2.2.11.3"><a href="#r2.2.11"><strong>&uarr;_</strong></a> <a href="#r2.2.11.3">2.2.11.3.</a> <strong>All vinyl torrents must be ripped at the correct speed.</strong> You must rip all vinyl at the speed they were intended to be played at. For example, you may not rip a 45rpm vinyl at 33rpm and upload it to the site.
						</li>
				</ul>
				<li id="r2.2.12"><a href="#r2.2.12"><strong>&uarr;_</strong></a> <a href="#r2.2.12">2.2.12.</a> <strong>MP3 Specific Dupe Rules</strong>
					<ul>
						<li id="r2.2.12.1"><a href="#r2.2.12"><strong>&uarr;_</strong></a> <a href="#r2.2.12.1">2.2.12.1.</a> <strong>-V0 (VBR), -V2 (VBR), and 320 CBR MP3 are allowed at any time.</strong> You may upload a -V0 (VBR), -V2 (VBR) or 320 CBR MP3 as long as another rip with the same bitrate and format doesn't already exist. So if a -V0 (VBR) is on the site, you may still upload a -V2 (VBR) or 320 CBR MP3 of the same release.
						</li>

						<li id="r2.2.12.2"><a href="#r2.2.12"><strong>&uarr;_</strong></a> <a href="#r2.2.12.2">2.2.12.2.</a> <strong>-V2 (VBR) and APS (VBR) replace CBR rips under 256kbps, 192 (ABR), and rips averaging 192 (VBR) to 210 (VBR).</strong> Once a rip with either -V2 (VBR) or APS (VBR) LAME encoding preset has been uploaded, you are not allowed to upload any CBR torrents under 256kbps bitrate. Furthermore, a -V2 (VBR) or APS (VBR) rip will trump all indiscriminate VBR rips of similar bitrate. A -V2 (VBR) rip will replace a 192 (VBR) rip or 210 (VBR) rip for example. Also, a -V2 (VBR) will replace a 192 (ABR) torrent.
						</li>
						<li id="r2.2.12.3"><a href="#r2.2.12"><strong>&uarr;_</strong></a> <a href="#r2.2.12.3">2.2.12.3.</a> <strong>-V0 (VBR) and APX (VBR) replace CBR rips under 320kbps, V1 (VBR), all (ABR) rips, and all other non-LAME preset (VBR) rips.</strong> Once a rip with either -V0 (VBR) or APX (VBR) LAME encoding preset has been uploaded, you are not allowed to upload any CBR torrents under 320kbps bitrate. Furthermore, a -V0 (VBR) or APX (VBR) rip will trump all indiscriminate VBR rips (of 192kbps average bitrate or higher). A -V0 (VBR) rip will replace a 256 (VBR) rip for example. You may not upload a -V1 (VBR) LAME encoding preset if either -V0 (VBR) or APX (VBR) are already present.
						</li>
						<li id="r2.2.12.4"><a href="#r2.2.12"><strong>&uarr;_</strong></a> <a href="#r2.2.12.4">2.2.12.4.</a> <strong>Non-LAME preset (VBR) rips do not replace each other.</strong> Similar bitrate (VBR) encodes are considered dupes. Thus, a 192 (VBR) is allowed to co-exist with a significantly higher (VBR) encode. You may not upload a 224 (VBR) if a 256 (VBR) is already present, nor vice versa.
						</li>

						<li id="r2.2.12.5"><a href="#r2.2.12"><strong>&uarr;_</strong></a> <a href="#r2.2.12.5">2.2.12.5.</a> <strong>-V2 (VBR) and -V0 (VBR) LAME encodes trump APS (VBR) and APX (VBR) respectively.</strong> -V2 (VBR) trumps an APS (VBR) encode of the same release. Once a -V2 (VBR) is uploaded, you may not upload an APS (VBR) encode. -V0 (VBR) trumps an APX (VBR) encode of the same release. Once a -V0 (VBR) is uploaded, you may not upload an APX (VBR) encode.
						</li>
					</ul>
				</li>
				<li id="r2.2.13"><a href="#r2.2.13k"><strong>&uarr;_</strong></a> <a href="#r2.2.13">2.2.13.</a> <strong>Ogg Vorbis Specific Dupe Rules</strong>

					<ul>
						<li id="r2.2.13.1"><a href="#r2.2.13"><strong>&uarr;_</strong></a> <a href="#r2.2.13.1">2.2.13.1.</a> <strong>Only -q8.x (VBR) and equivalent presets (-q0.8) are allowed.</strong> Only -q8.x (~256 (VBR)) is allowed on the site for Ogg Vorbis. Torrents encoded with presets other than -q8.x will be deleted. You may use fraction presets where x is any number from 0 - 9. Fraction presets do not make your upload unique from all similar presets. You may not upload a -q8.1 (VBR) if a -q8.7 (VBR) is already present.
						</li>
					</ul>
				</li>
				<li id="r2.2.14"><a href="#r2.2.14k"><strong>&uarr;_</strong></a> <a href="#r2.2.14">2.2.14.</a> <strong>AAC Specific Dupe Rules</strong>

					<ul>
						<li id="r2.2.14.1"><a href="#r2.2.14"><strong>&uarr;_</strong></a> <a href="#r2.2.14.1">2.2.14.1.</a> <strong>No constant bitrate encodes higher than 320 CBR are allowed on the site.</strong> You may upload AAC files from 192kbps to 320kbps if no torrent exists in that format for a release. Any CBR bitrates under 192kbps and bitrates higher than 320kbps will be deleted. CBR trumping rules apply to AAC as well. <a href="#r2.2.9">See 2.2.9</a> for more information.
						</li>
						<li id="r2.2.14.2"><a href="#r2.2.14"><strong>&uarr;_</strong></a> <a href="#r2.2.14.2">2.2.14.2.</a> <strong>Encoding profiles/object types at the same bitrate are not unique uploads.</strong> A 256 (VBR) in LC profile is a dupe if an 256 (VBR) HE object type encode already exists on the site. Similarly, different extensions (.m4a and .mp4) count as dupes if available in the same bitrate.
						</li>

						<li id="r2.2.14.3"><a href="#r2.2.14"><strong>&uarr;_</strong></a> <a href="#r2.2.14.3">2.2.14.3.</a> <strong>VBR AAC encodes can be trumped by 320 CBR encodes.</strong> Any (VBR) encode regardless of encoder used may be deleted in favor of 320 CBR encodes. This includes 320 (VBR) encodes or encodes made with Nero preset of approximately -q0.80.
						</li>
					</ul>
				</li>
				<li id="r2.2.15"><a href="#r2.2.15k"><strong>&uarr;_</strong></a> <a href="#r2.2.15">2.2.15.</a> <strong>Lossless Specific Dupe Rules</strong>

					<ul>
						<li id="r2.2.15.1"><a href="#r2.2.15"><strong>&uarr;_</strong></a> <a href="#r2.2.15.1">2.2.15.1.</a> <strong>All FLAC CD rips must come from official CD sources.</strong> Rips must be taken from commercially pressed or official-pressed CD sources. They may not come from CD-R copies of the same pressed CDs. Undetected errors may be introduced from the original CD rip and subsequent burning process to CD-R. Even though the CD-R is ripped with proper settings in <a href="http://tmkk.pv.land.to/xld/index_e.html">XLD</a> or <a href="http://www.exactaudiocopy.de/">EAC</a>, it may still be a sub-par rip with a good log. <u>Exceptions</u>: If the release is only distributed on CD-R, then that is acceptable. Promotions and small distributions of albums tend to be in CD-R format.
						</li>

						<li id="r2.2.15.2"><a href="#r2.2.15"><strong>&uarr;_</strong></a> <a href="#r2.2.15.2">2.2.15.2.</a> <strong>A FLAC torrent without a rip log can be trumped by a FLAC torrent of the same release containing a rip log.</strong> The FLAC torrent without a log will be trumped in favor of one containing a log. Please make sure your rip log has the extension .log in order to be displayed properly on the torrents page. <u>Exceptions</u>: If the FLAC torrent with a log contains excessive rip errors (e.g. suspicious positions, skips, or pops), it cannot replace the existing torrent. Audiochecker, auCDtect, and other MPEG analyzer logs do not count as rip logs, even if renamed with the .log extension. Soundboards and other exclusive lossless digitally-distributed files cannot be unofficially burned to CD-R and re-ripped in order to trump existing digital copies of the same quality and source.
						</li>
						<li id="r2.2.15.3"><a href="#r2.2.15"><strong>&uarr;_</strong></a> <a href="#r2.2.15.3">2.2.15.3.</a> <strong>A FLAC upload with an <a href="http://www.exactaudiocopy.de/">EAC</a> or <a href="http://tmkk.pv.land.to/xld/index_e.html">XLD</a> rip log that scores 100 on the logchecker replaces one with a lesser score.</strong> Proper <a href="http://www.exactaudiocopy.de/">EAC</a> or <a href="http://tmkk.pv.land.to/xld/index_e.html">XLD</a> rips can replace rips made by other rippers or with sub-par settings. No log scoring less than 100 can trump an already existing one under 100. For example, a FLAC+log rip that scores a 50 in the logchecker can not be trumped by a FLAC+log that scores an 80. What.CD recommends <a href="user.php?id=59886">caaok</a>'s <a href="wiki.php?action=article&id=693">guide for EAC</a>, and <a href="wiki.php?action=article&amp;id=146">this guide for XLD</a>. What.CD strongly encourages enabling AccurateRip if available for your CD extraction app of choice. <em>Note: If a log scores 95 due to not defeating audio cache, and the torrent description contains accepted proof of the drive for ripping caches less than 64 kB of audio data, then that torrent can not be trumped by a torrent with a 100 score. The 95 torrent may be upgraded to 100 once a staff member has been alerted to the case, and has manually corrected the score. See <a href="#r2.2.15.7">2.2.15.7.</a></em>

						</li>
						<li id="r2.2.15.4"><a href="#r2.2.15"><strong>&uarr;_</strong></a> <a href="#r2.2.15.4">2.2.15.4.</a> <strong><a href="http://tmkk.pv.land.to/xld/index_e.html">XLD</a> and <a href="http://www.exactaudiocopy.de/">EAC</a> log in languages other than English require manual logchecker score adjustment.</strong> The current logchecker cannot parse non-English logs made by <a href="http://www.exactaudiocopy.de/">EAC</a> and <a href="http://tmkk.pv.land.to/xld/index_e.html">XLD</a>. However, a special exception is made for foreign-language logs since the rip quality is equivalent to English logs. Please report your torrent with the <u>RP</u> link so staff can approve your torrent manually.
						</li>

						<li id="r2.2.15.5"><a href="#r2.2.15"><strong>&uarr;_</strong></a> <a href="#r2.2.15.5">2.2.15.5.</a> <strong>Range rips of hidden tracks or range rips under strict conditions require manual score adjustment.</strong> The new logchecker cannot accurately score range-ripped hidden tracks appended to proper rip logs. If you have created a 100 rip with a hidden track, but the logchecker decreased your score for the hidden track, report the torrent. That way your score can be adjusted to reflect the proper settings. What.CD does not encourage ripping entire CDs as range rips. Instead, please rip CDs <a href="http://blowfish.be/eac/">to separate tracks according to the EAC Guide</a>. For those rare cases where you have created a CD image rip with matching CRCs for test and copy, and the tracks have a AccurateRip verification of 2 or more, then you may submit your torrent for manual adjustment. <em>Note: The CD Image rip must be split with <a href="http://wiki.hydrogenaudio.org/index.php?title=CueTools">CUE Tools</a>, <a href="http://tmkk.pv.land.to/xld/index_e.html">XLD</a>, or <a href="http://www.exactaudiocopy.de/">EAC</a>. No other splitter is acceptable for a score adjustment. You will not receive score adjustment for copy-only range rips approved with AccurateRip nor range rips done with test and copy only.</em>

						</li>
						<li id="r2.2.15.6"><a href="#r2.2.15"><strong>&uarr;_</strong></a> <a href="#r2.2.15.6">2.2.15.6.</a> <strong>A 100% log rip lacking a cue sheet can be replaced by another 100% log rip with a noncompliant cue sheet ONLY when the included cue sheet is materially different than 'a cue generated from the ripping log'.</strong> Examples of a material difference include additional or correct indices, and pre-emphasis flags. If you upload a torrent with a cue sheet that provides nothing additional beyond what is contained in the ripping log of the preexistent torrent, it will be deleted as a dupe. Exception: An EACv0.95 rip with a 100% log and no cue file uploaded before September 14, 2010 may be trumped by a torrent that scores 100% under the current logchecker.
						</li>
						<li id="r2.2.15.7"><a href="#r2.2.15"><strong>&uarr;_</strong></a> <a href="#r2.2.15.7">2.2.15.7.</a> <strong>Drives that cache less than 64 kB of audio data may leave the audio cache enabled. Proof is required. They can be trumped by a 100 rip if no proof is included.</strong> Under certain conditions, rips made without the audio cache disabled may trump other rips if all other settings are correct. A pasted log (hosted at <a href="http://www.pastebin.com">Pastebin</a> or similar site is OK) or screenshot of proof from <a href="http://www.feurio.com/">Feurio!</a> and a screenshot from <a href="http://club.cdfreaks.com/f52/cache-explorer-184487/">CacheX</a> should be posted in the torrent description field. See <a href="wiki.php?action=article&amp;id=219">the Wiki page</a> for examples of allowed proof. You will need to present proof from both CacheX and Feurio!. <a href="http://www.exactaudiocopy.de/">EAC</a>'s own cache detection may give erroneous results sometimes, so a screenshot of <a href="http://www.exactaudiocopy.de/">EAC</a>'s drive features test cannot serve as proof. Also, <a href="http://tmkk.pv.land.to/xld/index_e.html">XLD</a> logs before CDParanoia III 10.2 engine require AccurateRip verification because the cache is not properly defeated on earlier CDParanoia versions. Please report your torrent with the <u>RP</u> link so staff can approve your torrent manually to 100 score.
						</li>				
						<li id="r2.2.15.8"><a href="#r2.2.15"><strong>&uarr;_</strong></a> <a href="#r2.2.15.8">2.2.15.8.</a> <strong>FLAC rips that contain ID3 tags or other non-compliant tags for FLAC may be trumped by same score rips that have faulty tags removed and replaced with the standard for each format.</strong> Enabling ID3 tags in <a href="http://www.exactaudiocopy.de/">EAC</a> when ripping to FLAC may prevent some players from playing the files due to the inclusion of ID3 headers. If you wish to trump a FLAC rip that was ripped with ID3 tags enabled, upload the corrected torrent with the proper Vorbis comments, and report the old torrent. Add information about your clean-up in the <em>Release description</em>, or your torrent may be deleted for a dupe. Do not edit the log and change the ID3 tag setting to "No." <em>Note: A simple way of getting rid of the ID3 header is to decompress the files to WAV. Then compress the files to FLAC, and again add the proper Vorbis comments.</em>

						</li>
						<li id="r2.2.15.9"><a href="#r2.2.15"><strong>&uarr;_</strong></a> <a href="#r2.2.15.9">2.2.15.9.</a> <strong>No log editing is permitted.</strong> Forging log data is a serious misrepresentation of quality, and will result in a warning and permanent loss of upload privileges if found. <strong>Do not consolidate logs under any circumstances.</strong> If you must re-rip a disc and happen to have the new log appended to the original, leave them as-is. Do not remove any part of either log, and never copy/paste parts of a new log over an old log. If you find that an appended log has not been scored properly, please PM a moderator to get the score manually adjusted.
						</li>

					</ul>
				</li>
				<li id="r2.2.16"><a href="#h2.2"><strong>&uarr;_</strong></a> <a href="#r2.2.16">2.2.16.</a> <strong>Unknown Release torrents may be trumped by seemingly identical torrents whose Edition Information can be verified.</strong> Torrents marked as 'Unknown Release' are eligible to be trumped by rips sourced from the same medium, of the same track listing and running order, whose source Edition Information is provided and can be verified.
				</li>
			</ul>
		</div>
		
		<h5 id="h2.3"><a href="#h2.3k"><strong>&uarr;</strong></a> <a href="#h2.3">2.3.</a> Formatting</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r2.3.1"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.1">2.3.1.</a> <strong>Music releases must be in a directory containing the music.</strong> No music contained in an archive (e.g. .rar, .zip, .tar, .iso). Scene archives of music must be unpacked and not labeled as "scene." <u>Exceptions</u>: There is no need for a directory for a torrent that consists of a single file (and is not an archive!).
				</li>

				<li id="r2.3.2"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.2">2.3.2.</a> <strong>Name your directories with meaningful titles, such as "Artist - Album (Year) - Format."</strong> We advise that directory names in your uploads should at least be "Artist - Album (Year) - Format". The minimum acceptable is "Album", although it is preferable to include more information. If the directory name does not include this minimum then another user can rename the directory, re-upload and report yours for deletion. Avoid creating unnecessary nested folders (such as an extra folder for the actual album) inside your properly named directory. Nested folders make it less likely that downloaders leave the torrent unchanged in order to stay seeding. 
				</li>
				<li id="r2.3.3"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.3">2.3.3.</a> <strong>Label your torrents according to standards.</strong> Follow <a href="wiki.php?action=article&amp;id=159">the torrent naming guide</a> for help on how to name your uploaded torrents properly. Note that soundboards, EPs, LPs. singles, special characters, and releases with various artists have special naming standards here. Use the <em>Edition Information</em> box on the <a href="upload.php">upload page</a> to denote different editions or versions of an album (e.g. censored version versus an uncensored version). Do not add genre tags like [Australia] or [K-Pop] to your album title. Those belong as <a href="rules.php?p=tag">Gazelle tags</a>. If you need help merging or editing your upload, <a href="forums.php?action=viewthread&amp;threadid=12006&amp;page=1">request it at this thread</a>. For the album category/release type, follow the <a href="wiki.php?action=article&amp;id=202">guidelines here</a>.
				</li>

				<li id="r2.3.4"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.4">2.3.4.</a> <strong>Torrents should never have [REQ] or [REQUEST] in the title or artist name.</strong> If you fill a request using the proper <a href="requests.php">Requests</a> system, then everyone who voted for it will be automatically notified.
				</li>
				<li id="r2.3.5"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.5">2.3.5.</a> <strong>Torrent album titles must accurately reflect the actual album titles.</strong> Use proper capitalization when naming your albums. Typing the album titles in all lowercase letters or all capital letters is unacceptable. For detailed information on naming practices see <a href="wiki.php?action=article&id=317">this</a>. <u>Exceptions</u>: If the album has special capitalization, then you may follow that convention.
				</li>

				<li id="r2.3.6"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.6">2.3.6.</a> <strong>The Artist field in the torrent name should contain only the artist name.</strong> Any descriptions like [Advance] or [CDM] (if you must use them) should be entered in the <em>Edition Information</em> box on the <a href="upload.php">upload page</a>, not the title. Do not add additional information about the artist in the artist field unless the album credits the artist in that manner. For example, "Artist X (of Band Y)" or "Band X (feat. Artist Y)." It is recommended that you search existing torrents for the artist name so that you can be sure that you name the artist the exact same way. A torrent with a proper artist name will be grouped with the existing torrents on the artist page, and thus easy to find. Labeling the artist incorrectly prevents your torrent from being grouped with the other torrents of the same artist.
				</li>
				<li id="r2.3.7"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.7">2.3.7.</a> <strong>The year of the original recording should be used for the upload page "Year" box.</strong> Use the recording year for "Year of the original release" (if you can establish it), and use the option to add the release year for the album or edition you are uploading in the <em>Edition information</em> on the uploads page. For example, all editions of <em>The Beatles (White Album)</em> would have 1968 in the main Year box. However, each of the various mono pressings, remasters, re-releases, expanded editions, reconstructions, etc. would have its respective release year in the <em>Edition Information</em> box. 
				</li>
				<li id="r2.3.8"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.8">2.3.8.</a> <strong>All lossless analog rips should include clear information about source lineage.</strong> All lossless SACD digital layer analog rips and vinyl rips must include clear information about recording equipment used. If you used a USB turntable for vinyl, clearly indicate that you have. Also include all intermediate steps up to lossless encoding, such as program used for mastering, sound card used, etc. Lossless analog rips missing rip information can be trumped by better documented lossless analog rips of comparable or better quality. In order to trump a lossless analog rip without a lineage, this lineage must be included as a .txt or .log file within the torrent.
				</li>
				<li id="r2.3.9"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.9">2.3.9.</a> <strong>All lossless soundboard recordings must include clear information about source lineage.</strong> This information should be displayed in the torrent description. Optionally, the uploader may include the information in a .txt or .log file within the torrent. Lossless soundboard recordings missing lineage information will be deleted if reported.
				</li>
				<li id="r2.3.10"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.10">2.3.10.</a> <strong>File names must accurately reflect the song titles</strong>. You may not have file names like 01track.mp3, 02track.mp3, etc. File names with incorrect song titles can be trumped by properly labeled torrents. Note that these must be substantial improvements such as the removal of garbage characters. Small changes such as diacritical marks are insufficient grounds for trumping. English translations of song titles in file names are encouraged, but not necessary for foreign language song titles. <u>Exceptions</u>: Rare albums featuring no track listing or untitled tracks may have file names like 01track.mp3, 02track.mp3, and so forth. Please note this tracklist in the <em>Album description</em>. If foreign language characters create playback problems for some systems or cannot be coherently translated, file names such as "01track" is acceptable for those few cases.
				</li>
				<li id="r2.3.11"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.11">2.3.11.</a> <strong>Including track numbers of each song in the file names (e.g. "01 - TrackName.mp3") is highly recommended.</strong> If a torrent without track numbers in the file names is uploaded, then a torrent with the track numbers can take its place. <strong>Exception: Track numbers are not required for single-track torrents.</strong>
				</li>
				<li id="r2.3.12"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.12">2.3.12.</a> <strong>Multiple-disc torrents cannot have tracks with the same numbers in one directory.</strong> Tracks cannot contain duplicate track numbers in the same directory. You may either place the tracks for each disc in a separate directory or number the tracks successively. You may place all the tracks for Disc One in one directory and all the tracks for Disc Two in another directory. If you prefer to use one directory for all the audio files, you must use successive numbering. Successive numbering may consist of the following (as illustrated with examples). Disc One has 15 tracks and Disc Two has 20. You may either number tracks in Disc One as #01-#15, and those of Disc Two as #16-#35 in the same directory. Or you may add a Disc number before the track numbers, such that the numbers are #1 06 for Disc One Track 06, and #2 03 for Disc 2 Track 03, and so forth. That way the track numbers will not be duplicated across multiple discs.
				</li>
				<li id="r2.3.13"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.13">2.3.13.</a> <strong>Properly tag your music files.</strong> Certain meta tags (e.g. ID3, Vorbis) are required on all music uploads. Make sure to use the proper format tags for your files (e.g. no ID3 tags for FLAC &ndash; see rule <a href="#r2.2.15.8">2.2.15.8</a>). ID3v2 tags for files are highly recommended over ID3v1. Torrents uploaded with both good ID3v1 tags and blank ID3v2 tags are trumpable by torrents with either just good ID3v1 tags or good ID3v2 tags. If you upload an album missing one or more of these tags, then another user may add the tags, re-upload, and report yours for deletion. The required tags are:
					<ul>

						<li>Artist</li>
						<li>Album</li>
						<li>Title</li>
						<li>Track Number.</li>
					</ul>
					<p><em>Note: The "Year" tag is optional, but strongly encouraged. However, if missing or incorrect, is not grounds for trumping a torrent.</em></p>
					<p><em>Note: Classical music has a different tagging standard.</em> In particular, classical music uploads must have both the composer and artist tagged properly.  See <a href="wiki.php?action=article&id=691">this article</a> for more information.
				</li>
				<li id="r2.3.14"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.14">2.3.14.</a> <strong>The torrent artist for classical works should use the full composer name.</strong> Please consult <a href="wiki.php?action=article&id=700">this article</a> for a full explanation of the classical music system.
				</li>
				<li id="r2.3.15"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.15">2.3.15.</a> <strong>Newly re-tagged torrents trumping badly tagged torrents must reflect a substantial improvement over the previous tags.</strong> Small changes that include replacing ASCII characters with proper foreign language characters with diacritical marks, fixing slight misspellings, or missing an alternate spelling of an artist (e.g. excluding "The" before a band name) are insufficient grounds for replacing other torrents. You may trump a release if the tags do not follow the data from a reputable music cataloging service such as <a href="http://musicbrainz.org/">Musicbrainz</a> or <a href="http://www.discogs.com/">Discogs</a>. In case of conflict between reputable listings, either tagged version is equally preferred to the site and cannot trump the other. For example, an album is tagged differently in Musicbrainz and in Discogs. Either style of tagging is permitted; neither is "better" than the other. In that case, any newly tagged torrents replacing an already properly tagged torrent, which follows good tagging convention, will result in a dupe.  <em>Note: For classical music, please follow <a href="wiki.php?action=article&id=691">our tagging guidelines</a>.</em>

				</li>
				<li id="r2.3.16"><a href="#h2.3"><strong>&uarr;_</strong></a> <a href="#r2.3.16">2.3.16.</a> <strong>Avoid embedding large images if including cover art in meta tags.</strong> Do not embed large images (in excess of ~256 KB) in file meta tags. It adds unnecessary bloat to the files. Include the artwork in a separate directory if too big or hi-res. If someone reports your torrent for large embedded artwork, it will be deleted.
				</li>
			</ul>
		</div>
		
		<h4 id="h3"><a href="#h3k"><strong>&uarr;</strong></a> <a href="#h3">3.</a> Applications</h4>

		
		<h5 id="h3.1"><a href="#h3.1k"><strong>&uarr;</strong></a> <a href="#h3.1">3.1.</a> General</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r3.1.1"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.1">3.1.1.</a> <strong>App releases can be either a torrent of a directory or an archive.</strong> <u>Exceptions:</u> Only scene released applications may be archived and must not be password protected. If archives were originally password protected and had the protection removed, they cannot be represented as official scene releases. The file hashes between protected and unprotected archives are different, so it counts as a modification.
				</li>

				<li id="r3.1.2"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.2">3.1.2.</a> <strong>All applications must come with a crack, keygen, or other method of ensuring that downloaders can install them easily.</strong> App torrents with keygens, cracks, or patches that do not work and torrents missing clear installation instructions are deleted if reported. No exceptions.
				</li>
				<li id="r3.1.3"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.3">3.1.3.</a> <strong>App releases must not be freely available tools.</strong> Application releases cannot be freely downloaded anywhere from any official source. Nor may you upload open source apps where the source code is available for free. Closed or shareware installers like Crossover Office are allowed. <em>Note: If all official sources stop hosting and remove a freely available app and its source code from their site(s) due to varying reasons (legal, dead development, etc.), the app ceases to be freely available. You may upload it in that case.</em>

				</li>
				<li id="r3.1.4"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.4">3.1.4.</a> <strong>Release descriptions for apps must contain good information about the application.</strong> You should either have a small description of the program (either taken from its website or from a NFO) or a link to information&ndash;ideally both.
				</li>
				<li id="r3.1.5"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.5">3.1.5.</a> <strong>The torrent title must have a descriptive name.</strong> The torrent title should at least include the app name and release version. Optionally, you may include additional labels for OS and kind of circumvention (i.e. crack, patch, keygen, or serial). For example, AcrylicApps Wallet v3.0.1.493 MacOSX Cracked.
				</li>

				<li id="r3.1.6"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.6">3.1.6.</a> <strong>Use relevant tags for your torrent.</strong> Add all applicable default <a href="rules.php?p=tag">Gazelle tags</a> to help downloaders find your torrent. The default tags are apps.mac for Mac apps, apps.windows for Windows apps, and apps.sound for audio apps. You may add additional tags if the default ones do not apply (such as apps.linux).
				</li>
				<li id="r3.1.7"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.7">3.1.7.</a> <strong>Application "packs" are not allowed.</strong> That means no 0-day packs or "video utilities" compilations. <u>Exceptions</u>: The applications are from the same company and an official release. For example, Adobe CS and Macromedia Studio.
				</li>

				<li id="r3.1.8"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.8">3.1.8.</a> <strong>Sound sample packs, template collections, and font collections are allowed if they are official releases, not freely available, and unarchived.</strong> Sound sample packs, template collections, and font collections must be official compilations. The files contained inside the torrent must not be archived. That means if sound sample packs are in WAV format, they must be uploaded as WAV. If the font collection, template collection, or sound sample pack was originally released as an archive, you must unpack the files before uploading them in a torrent. None of the content in these packs and collections may be freely available.
				</li>
				<li id="r3.1.9"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.9">3.1.9.</a> <strong>Application components such as plug-ins, add-ons, expansions, filters, and so forth may be uploaded in a collection if they correspond to a particular application.</strong> You may upload plug-ins, expansions, add-ons, filters, and other application components as collections provided they are compatible to a particular application and version. For example, you may not upload a megapack of all filters for Adobe Photoshop CS2, CS3, and CS4. But you may upload a pack of Adobe Photoshop CS4 filters.
				</li>

				<li id="r3.1.10"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.10">3.1.10.</a> <strong>Collections of cracks, keygens or serials are not allowed.</strong> The crack, keygen or serial for an application must be in a torrent with its corresponding application. It cannot be uploaded separately from the application.
				</li>
				<li id="r3.1.11"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.11">3.1.11.</a> <strong>Torrents containing hacking or cracking tools are not allowed.</strong>
				</li>
				<li id="r3.1.12"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.12">3.1.12.</a> <strong>Never post serial numbers in torrent descriptions.</strong> Serial numbers should be in a text file (or similar) contained within the torrent. No exceptions.
				</li>
				<li id="r3.1.13"><a href="#h3.1"><strong>&uarr;_</strong></a> <a href="#r3.1.13">3.1.13.</a> <strong>All applications must be complete.</strong> If an application consists of multiple CDs or DVDs, these should all be uploaded as one torrent, and not as separate torrents. This also applies to scene uploads where multiple CDs or DVDs were released separately.
				</li>
			</ul>
		</div>
		
		<h5 id="h3.2"><a href="#h3.2k"><strong>&uarr;</strong></a> <a href="#h3.2">3.2.</a> Duplicates &amp; Trumping</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r3.2.1"><a href="#h3.2"><strong>&uarr;_</strong></a> <a href="#r3.2.1">3.2.1.</a> <strong>Applications of the same version numbers are dupes.</strong> An application may have older versions than those already uploaded. Those are not dupes. Only identical versions are duplicates.
				</li>

				<li id="r3.2.2"><a href="#h3.2"><strong>&uarr;_</strong></a> <a href="#r3.2.2">3.2.2.</a> <strong>A scene archived torrent may coexist with an unarchived torrent of the same app version.</strong> If both an scene archive and an unarchived copy are uploaded of the same app and version, both may stay on the site. Any subsequent uploads of the same in either format or install method are dupes.
				</li>
				<li id="r3.2.3"><a href="#h3.2"><strong>&uarr;_</strong></a> <a href="#r3.2.3">3.2.3.</a> <strong>Different language editions of the same app and version are unique.</strong> Multi-language versions and single language versions of different languages are not considered dupes.
				</li>
				<li id="r3.2.4"><a href="#h3.2"><strong>&uarr;_</strong></a> <a href="#r3.2.4">3.2.4.</a> <strong>Apps can be trumped by other apps with better install methods.</strong> Apps with serial keys may be trumped by crack/patch versions or torrents with keygens. Once an app with either a crack/patch or keygen is uploaded to the site, no other identical app with a different method of install is allowed. Report the old torrent if trumping it with a torrent of the same app and improved method of installation.
				</li>

			</ul>
		</div>
		
		<h4 id="h4"><a href="#h4k"><strong>&uarr;</strong></a> <a href="#h4">4.</a> Comic Books</h4>

		<h5 id="h4.1"><a href="#h4.1k"><strong>&uarr;</strong></a> <a href="#h4.1">4.1.</a> General</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>

				<li id="r4.1.1"><a href="#h4.1"><strong>&uarr;_</strong></a> <a href="#r4.1.1">4.1.1.</a> <strong>Comic books must be in the specified formats.</strong> These formats are the following according to descending preference:
					<ul>
						<li>A rar archive (Preferably with the .cbr extension)</li>
						<li>A zip archive (Preferably with the .cbz extension)</li>
						<li>A PDF file</li>

						<li>A directory containing only the images themselves</li>
					</ul>
				</li>
				<li id="r4.1.2"><a href="#h4.1"><strong>&uarr;_</strong></a> <a href="#r4.1.2">4.1.2.</a> <strong>Pages must be scanned cleanly and be of good quality.</strong> Scans of poor quality will be deleted, especially if the quality is so poor as to render difficulty in reading. Poorer quality scans may be acceptable for very old or rare comics with staff discretion.
				</li>
				<li id="r4.1.3"><a href="#h4.1"><strong>&uarr;_</strong></a> <a href="#r4.1.3">4.1.3.</a> <strong>Comic books must not be freely available.</strong> Comics must be official publications, and these cannot be taken from official sources. You may upload comics from other torrent and unofficial distribution sites, but it is your responsibility that they conform to our quality and formatting rules for comic books. <em>Note: If all official sources stop hosting and remove an official freely available release from their site(s), the release ceases to be freely available. You may upload it in that case.</em>

				</li>
				<li id="r4.1.4"><a href="#h4.1"><strong>&uarr;_</strong></a> <a href="#r4.1.4">4.1.4.</a> <strong>0-Day comic uploads are allowed and encouraged.</strong>
				</li>
			</ul>
		</div>
		
		<h5 id="h4.2"><a href="#h4.2k"><strong>&uarr;</strong></a> <a href="#h4.2">4.2.</a> Multi-comic</h5>

		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r4.2.1"><a href="#h4.2"><strong>&uarr;_</strong></a> 4.2.1 <strong>Multi-comic and series packs must follow formatting requirements.</strong> Multi-comic and series packs are both accepted and encouraged but care must be taken to make a valid compilation. The rules below outline the requirements for multi-comic torrents.
				</li>
				<li id="r4.2.2"><a href="#h4.2"><strong>&uarr;_</strong></a> <a href="#r4.2.2">4.2.2.</a> <strong>0-Day comic packs are allowed.</strong> Make sure such uploads are synchronized with previous packs. 0-Day comic uploads must not be missing any of their corresponding DCP or Minutemen scans for that sync time.
				</li>

				<li id="r4.2.3"><a href="#h4.2"><strong>&uarr;_</strong></a> <a href="#r4.2.3">4.2.3.</a> <strong>Collections may not span more than one comic title.</strong> You may not collect multiple different comic titles. e.g. "The Amazing Spider-Man #1 and The Incredible Hulk #1" <u>Exceptions:</u> Titles may contain more than one comic title if either: it's a recognized comic crossover/event or it's a DCP weekly release.
				</li>
				<li id="r4.2.4"><a href="#h4.2"><strong>&uarr;_</strong></a> <a href="#r4.2.4">4.2.4.</a> <strong>Any "multi-part" comic enveloping the whole event is allowed as a single torrent.</strong> Whole events may be uploaded together. For example, the comics "Buffy the Vampire Slayer Season Eight - 2007 - part 1.cbr" and "Buffy the Vampire Slayer Season Eight - 2007 - The Long Way Home Part 2.cbr" can be uploaded as a single torrent providing there are only 2 parts to "The Long Way Home."
				</li>

				<li id="r4.2.5"><a href="#h4.2"><strong>&uarr;_</strong></a> <a href="#r4.2.5">4.2.5.</a> <strong>Torrents containing complete volumes of comics may be uploaded.</strong> For example, "The Amazing Spider-Man Vol. 1 #1-#441" can be uploaded.
				</li>
				<li id="r4.2.6"><a href="#h4.2"><strong>&uarr;_</strong></a> <a href="#r4.2.6">4.2.6.</a> <strong>Torrents spanning multiple volumes are too large and must be uploaded as separate volumes.</strong>
				</li>
				<li id="r4.2.7"><a href="#h4.2"><strong>&uarr;_</strong></a> <a href="#r4.2.7">4.2.7.</a> <strong>Torrents containing #Number-#CurrentDay are allowed only if the comics appear in no other pack.</strong> Take for instance, if #1-#35 are already on site and the current issue is #50, #1-#50 is NOT allowed to be uploaded, but #36-#50 is allowed.
				</li>

			</ul>
		</div>
		
		<h5 id="h4.3"><a href="#h4.3k"><strong>&uarr;</strong></a> <a href="#h4.3">4.3.</a> Duplicates &amp; Trumping</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r4.3.1"><a href="#h4.3"><strong>&uarr;_</strong></a> <a href="#r4.3.1">4.3.1.</a> <strong>A dupe of a single comic is defined as two scans of the same book by the same scanner, where the same pages have been scanned.</strong> <u>Exceptions:</u> The following examples are NOT dupes: 
					<ul>

						<li>Two copies of the same book by the same scanner, but one is a c2c copy and the other is a "no ads" copy</li>
						<li>Two scans of the same book by different scanners</li>
						<li>Two scans of the same book by the same scanner, when the copy you're uploading contains fixes</li>
					</ul>
				</li>
				<li id="r4.3.2"><a href="#h4.3"><strong>&uarr;_</strong></a> <a href="#r4.3.2">4.3.2.</a> <strong>Releases in .cbz and .cbr are always allowed, with preference given to the earliest upload.</strong> In the event of a dupe occurring between a .cbr and a .cbz, the earliest upload remains. In the event of any other dupe, the order listed in 4.1.1 determines which torrent is kept regardless of uploaded time. e.g. A PDF is uploaded followed by a .cbr, the .cbr remains on site and the PDF is deleted as a dupe.
				</li>

				<li id="r4.3.3"><a href="#h4.3"><strong>&uarr;_</strong></a> <a href="#r4.3.3">4.3.3.</a> <strong>Multi-comic collections and packs must follow either of these collection types in order of preference.</strong> For example, there is the torrent #1-50 on site, the only pack containing any of the comics before #50 that are allowed to be uploaded are a #1-100 pack or a complete volume pack.
					<ul>
						<li id="r4.3.3.1"><strong>#1-#10, #11-#20, #21- #40, etc.</strong> are allowed only if comics appear in no other pack. They must contain at least 10 new comics.
						</li>
						<li id="r4.3.3.2"><strong>#1-#100, #101-#200, etc.</strong> are allowed only if they are not a complete volume pack.
						</li>

						<li id="r4.3.3.3"><strong>#1-#EndOfVolume</strong> is allowed at any time.
						</li> 
					</ul>
				</li>	
			</ul>
		</div>
		
		<h5 id="h4.4"><a href="#h4.4k"><strong>&uarr;</strong></a> <a href="#h4.4">4.4.</a> Formatting</h5>
		<div class="box pad" style="padding:10px 10px 10px 20px;">

			<ul>
				<li id="r4.4.1"><a href="#h4.4"><strong>&uarr;_</strong></a> <a href="#r4.4.1">4.4.1.</a> <strong>All comic page scans must be zero-padded numbered and may be archived properly in .pdf, .rar (.cbr) or .zip (.cbz).</strong> The contents of the archive or directory must be image files (either JPEG or PNG) named sequentially for display in the correct order by <a href="http://en.wikipedia.org/wiki/Comparison_of_image_viewers">comic reading software</a> such as <a href="http://www.cdisplay.me/">CDisplay</a> and <a href="http://www.feedface.com/software/ffview.html">FFView</a>. The page numbers and books must be zero-padded for this same reason. <em>For example, Good numbering: file01.jpg, file02.jpg .. file30.jpg and Bad numbering: file1.jpg, file2.jpg, file3.jpg...file30.jpg.</em>

				</li>
				<li id="r4.4.2"><a href="#h4.4"><strong>&uarr;_</strong></a> <a href="#r4.4.2">4.4.2.</a> <strong>Comic book archive file names must be informative.</strong> The archive names should include at least the Book's name (i.e. Uncanny X-Men), the volume (if there's more than one volume of that book) and the issue number. It is recommended to also include the cover year and the scanner information (to differentiate between different scans of the same book), as well as the issue's title (i.e. Days of Future Past). For example: <em>Buffy the Vampire Slayer Season Eight - #01 - 2007 - The Long Way Home Part 1.cbr</em> and <em>Amazing Spiderman - Volume 1 - #10 - 1964.cbz</em>
				</li>
				<li id="r4.4.3"><a href="#h4.4"><strong>&uarr;_</strong></a> <a href="#r4.4.3">4.4.3.</a> <strong>The directory name should uniquely identify its contents.</strong> You should include the title, as well as the issue numbers included (if applicable). The title, volume, cover year, and story name can often be found in small type at the bottom of the page opposite the inside cover. Directories should be named with the title of the series and the issue numbers. For example: <em>../Buffy the Vampire Slayer Season Eight - #01-#08/</em> and <em>../Amazing Spiderman - Volume 1 - #10-#20/</em>

				</li>
			</ul>
		</div>

		<h4 id="h5"><a href="#h5k"><strong>&uarr;</strong></a> <a href="#h5">5.</a> eBooks, eLearning Books &amp; Sheet Music</h4>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>

				<li id="r5.1"><a href="#h5"><strong>&uarr;_</strong></a> <a href="#r5.1">5.1.</a> <strong>Individual releases can be either a torrent of a directory, an archive or original format (e.g. .chm, .pdf, .txt, etc.).</strong> Neither the individual release or archive can be password protected.
				</li>			
				<li id="r5.2"><a href="#h5"><strong>&uarr;_</strong></a> <a href="#r5.2">5.2.</a> <strong>Do not archive collections into a single archive (.zip, .tar, .rar, etc.).</strong> You may individually archive each release separately if you want to compress the files. Uploading a pack of eBooks in one archive (e.g. .tar, .rar, .zip) is prohibited.
				</li>
				<li id="r5.3"><a href="#h5"><strong>&uarr;_</strong></a> <a href="#r5.3">5.3.</a> <strong>Only published eBooks are allowed.</strong> Freely available eBooks are not allowed. This rule also covers recipes and cookbooks: only official publications are allowed.
				</li>

				<li id="r5.4"><a href="#h5"><strong>&uarr;_</strong></a> <a href="#r5.4">5.4.</a> <strong>Collections of eBooks are allowed if each title is related to each other in a meaningful way.</strong> Releases of similar topic can be uploaded in a collection. You may not upload a collection of eBooks with nothing in common other than made by a single publisher (e.g. O'Reilly). Nor does it mean you can group eBooks by a broad area of topic. For example, "50 books in English," "Psychology books," or "Electrical engineering pack." Your collection must focus on a specific topic, series, or a body of work by an author.
				</li>
				<li id="r5.5"><a href="#h5"><strong>&uarr;_</strong></a> <a href="#r5.5">5.5.</a> <strong>Identical eBooks and identical sheet music uploads are dupes respectively.</strong> The same eBook and sheet music titles in the same format (e.g. .pdf, .chm, .txt, etc.) are dupes. <u>Exceptions</u>: eBooks and sheet music uploaded individually are not dupes if the same eBooks or sheet music releases are part of a torrent that consists of a collection.
				</li>

				<li id="r5.6"><a href="#h5"><strong>&uarr;_</strong></a> <a href="#r5.6">5.6.</a> <strong>Include proper <a href="rules.php?p=tag">Gazelle tags</a> for your eBook and sheet music uploads.</strong> You are strongly encouraged to use the appropriate default tags with your uploads. That way other users can find your uploads easily through the tag search system. Sheet music should use the sheet.music tag. eBooks should at least contain the tag ebooks.fiction or ebooks.non.fiction depending on the contents. You may add additional tags if the defaults do not apply or are not enough to describe the torrent contents.
				</li>
			</ul>
		</div>
		
		<h4 id="h6"><a href="#h6k"><strong>&uarr;</strong></a> <a href="#h6">6.</a> Comedy (Audio) &amp; Audio Books</h4>

		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r6.1"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.1">6.1.</a> <strong>The only formats allowed for comedy and audio books are those listed below:</strong>
					<ul>
						<li>MP3, FLAC, Ogg Vorbis, AAC, AC3, DTS</li>
					</ul>
					<p><em>Monkey's Audio (APE), Apple Lossless (ALAC/.M4A lossless), and Wavpack (WV) are deprecated. No more new uploads in either of these 3 formats are allowed after April 20, 2009. Only unique releases with no alternative upload in FLAC are permitted to remain on the site. However, you are encouraged to convert them to FLAC. </em>
					</p>
				</li>
				<li id="r6.2"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.2">6.2.</a> <strong>No music is permitted in these two categories.</strong> They are for spoken word only.
				</li>
				<li id="r6.3"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.3">6.3.</a> <strong>No radio talk shows and podcasts are allowed.</strong> What.CD is primarily a music site, and those do not belong in any torrent categories.
				</li>

				<li id="r6.4"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.4">6.4.</a> <strong>Comedy and audio books must not be freely available.</strong> Free audio books and comedy releases from official sources may not be uploaded. <u>Exceptions</u>: Uploads that are at a different bitrate from those freely available are allowed. If a comedy album in 96kbps CBR MP3 is freely available, you may still upload a -V8 (VBR) or higher bitrate torrent of the freely available torrent if it does not exist on the site already.
				</li>
				<li id="r6.5"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.5">6.5.</a> <strong>Releases must be unarchived and of a single release.</strong> Comedy and audio book releases should not be archived in a file, such as .zip or .rar. Releases must be a torrent of a directory containing the audio files. Only one torrent per release. You may not bundle multiple audio books or comedy releases in one torrent. <a href="#r1.2.7">See 1.2.7.</a>

				</li>
				<li id="r6.6"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.6">6.6.</a> <strong>All comedy and audio book releases must at least have an average bitrate of 16kbps mono or 32kbps stereo.</strong> Higher bitrates are preferred over this minimum, however.
				</li>
				<li id="r6.7"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.7">6.7.</a> <strong>Lossy sources, including lossy transcodes, are allowed in this section only.</strong> Lossy-sourced audio is permitted only in the comedy and audio book categories. This means the source may come from cassette, VHS (audio), radio, or a higher bitrate lossy file. While the sharing of transcoded material is strongly discouraged for music, the audio quality is less important for spoken word material. You may not transcode a lower bitrate file to a higher bitrate file and upload here. For example, you find a 32kbps CBR WMA and transcode it to 64kbps CBR MP3.
				</li>

				<li id="r6.8"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.8">6.8.</a> <strong>Duplicate torrents of the same bitrate and format are not allowed.</strong> No uploads of the same bitrate and format are allowed to coexist on the site. For example, if a torrent exists on the site in 256kbps MP3, no further torrents in 256kbps MP3 are allowed. Significantly higher or lower bitrate rips or different file formats of the same content are allowed. <u>Exceptions</u>: One LAME -V8 (or -V8 --vbr-new [with or without -mm]) rip is allowed on the site at any time. Even if a 85kbps CBR rip is already on the site, you may still upload a -V8 (VBR). The -V8 (VBR) may be sourced from a higher bitrate lossy encoded file.
				</li>
				<li id="r6.9"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.9">6.9.</a> <strong>Scene and non-scene releases of the same release, bitrate, and format are dupes.</strong> The "scene" label does not make torrents unique from each other. If a -V2 (VBR) of an album in CD is already uploaded, you may not upload a -V2 (VBR) scene version of the same album in CD.
				</li>

				<li id="r6.10"><a href="#h6"><strong>&uarr;_</strong></a> <a href="#r6.10">6.10.</a> <strong>Releases must follow formatting guidelines for file names and tags.</strong> Audiobooks and comedy must follow formatting rules outlined in section 2.3. This means no file names without track numbers, no files without metatags, and so forth. For comedy, at least use <a href="rules.php?p=tag">the official Gazelle tag</a> of "comedy". And for audio books, at least use the <a href="rules.php?p=tag">Gazelle tag</a> of "audio.books".
				</li>
			</ul>
		</div>

		
		<h4 id="h7"><a href="#h7k"><strong>&uarr;</strong></a> <a href="#h7">7.</a> eLearning Videos</h4>
		<div class="box pad" style="padding:10px 10px 10px 20px;">
			<ul>
				<li id="r7.1"><a href="#h7"><strong>&uarr;_</strong></a> <a href="#r7.1">7.1.</a> <strong>The eLearning Videos category is for tutorial videos of specific topics only.</strong> Any video clips mentioned in <a href="#h1.2">Section 1.2.</a> cannot be uploaded to the eLearning Videos category. 
				</li>

				<li id="r7.2"><a href="#h7"><strong>&uarr;_</strong></a> <a href="#r7.2">7.2.</a> <strong>No freely available eLearning videos</strong> You may not upload videos hosted officially from university sites, the author's site, the <a href="http://www.archive.org/index.php">Internet Archive</a> or publisher's site. You may upload videos from other torrent sites provided they conform to the rules in this section.
				</li>
				<li id="r7.3"><a href="#h7"><strong>&uarr;_</strong></a> <a href="#r7.3">7.3.</a> <strong>Tutorials on how to use musical instruments, vocal training, producing music or otherwise learning the theory and practice of music are the only allowed topics.</strong> No material outside of these topics is allowed. For example, instruction videos about Kung Fu training, dance lessons, beer brewing or photography are not permitted here. What is considered allowed under these topics is ultimately under staff discretion.
				</li>

				<li id="r7.4"><a href="#h7"><strong>&uarr;_</strong></a> <a href="#r7.4">7.4.</a> <strong>eLearning Videos must be either in a video file format (.mkv, .avi, .mov, .mp4 etc.) or a disk image (.iso, .bin/.cue, etc.).</strong> Only one video file format and one disk image format are allowed per video, anything else is a dupe. Torrents should not be uploaded in compressed archives.
				</li>
				<li id="r7.5"><a href="#h7"><strong>&uarr;_</strong></a> <a href="#r7.5">7.5.</a> <strong>eLearning video uploads must contain an informative description and use proper <a href="rules.php?p=tag">Gazelle tags</a>.</strong> Uploads should include a proper description and/or a link to further information. It is strongly encouraged they at least have the elearning.videos <a href="rules.php?p=tag">Gazelle tag</a> as well.
				</li>

			</ul>
		</div>
	</div>

<!-- END Other Sections -->
<? include('jump.php'); ?>
</div>	
<?
show_footer();
?>
