(function() {
	var username;
	// How many entries to show per category before expanding
	var initialCount = 3;
	var tasteometer = "";
	var lastPlayedTrack = "";
	var sharedArtists = "";
	var topArtists = "";
	var topAlbums = "";
	var topTracks = "";
	var expanded = false;
	// Failed request flag.
	var flag = 0;
	$(document).ready(function () {
		// Avoid conflicting with other jQuery instances (userscripts et al).
//		$.noConflict(); // Why is this needed?
		// Fetch the username (appended from php) to base all get requests on.
		username = $('#lastfm_username').text();
		var div = $('#lastfm_stats');
		// Fetch the required data.
		// If data isn't cached, delays are issued in the class to avoid too many parallel requests to Last.fm
		getTasteometer(div);
		getLastPlayedTrack(div);
		getTopArtists(div);
		getTopAlbums(div);
		getTopTracks(div);
		// Allow expanding the show information to more than three entries.
		// Attach to document as lastfm_expand links are added dynamically when fetching the data.
		$(document).on('click', "#lastfm_expand", function () {
			// Make hidden entries visible and remove the expand button.
			if ($(this).attr("href") == "#sharedartists") {
				sharedArtists = sharedArtists.replace(/\ class="hidden"/g,"");
				sharedArtists = sharedArtists.replace(/<li>\[<a\ href=\"#sharedartists.*\]<\/li>/,"");
			} else if ($(this).attr("href") == "#topartists") {
				topArtists = topArtists.replace(/\ class="hidden"/g,"");
				topArtists = topArtists.replace(/<li>\[<a\ href=\"#topartists.*\]<\/li>/,"");
			} else if ($(this).attr("href") == "#topalbums") {
				topAlbums = topAlbums.replace(/\ class="hidden"/g,"");
				topAlbums = topAlbums.replace(/<li>\[<a\ href=\"#topalbums.*\]<\/li>/,"");
			} else if ($(this).attr("href") == "#toptracks") {
				topTracks = topTracks.replace(/\ class="hidden"/g,"");
				topTracks = topTracks.replace(/<li>\[<a\ href=\"#toptracks.*\]<\/li>/,"");
			}
			updateDivContents(div);
		});
		// Allow expanding or collapsing the Last.fm data.
		$("#lastfm_expand").on('click', function () {
			if (expanded == false) {
				expanded = true;
				$(this).html("Show less info");
			} else {
				expanded = false;
				$(this).html("Show more info");
			}
			updateDivContents(div);
		});
		// Hide the reload button until data is expanded.
		$("#lastfm_reload_container").addClass("hidden");
		// Allow reloading the data manually.
		$.urlParam = function(name) {
			var results = new RegExp('[\\?&amp;]' + name + '=([^&amp;#]*)').exec(window.location.href);
			return results[1] || 0;
		}
		$("#lastfm_reload").on('click', function () {
			// Clear the cache and the necessary variables.
			$.get('user.php?action=lastfm_clear_cache&username=' + username + '&uid=' + $.urlParam('id'), function (response) {
			});
			tasteometer = "";
			lastPlayedTrack = "";
			sharedArtists = "";
			topArtists = "";
			topAlbums = "";
			topTracks = "";
			// Revert the sidebar box to its initial state.
			$("#lastfm_stats").html("");
			//$(".box_lastfm").children("ul").append('<li id="lastfm_loading">Loading...</li>');
			$("#lastfm_stats").append('<li id="lastfm_loading">Loading...</li>');
			// Remove the stats reload button.
			$("#lastfm_reload_container").remove();
			getTasteometer(div);
			getLastPlayedTrack(div);
			getTopArtists(div);
			getTopAlbums(div);
			getTopTracks(div);
		});
	});

	// Allow updating the sidebar element contents as get requests are completed.
	function updateDivContents(div) {
		var html = "";
		// Pass all data vars, gets that haven't completed yet append empty strings.
		html += tasteometer;
		html += lastPlayedTrack;
		html += sharedArtists;
		html += topArtists;
		html += topAlbums;
		html += topTracks;
		html += '<li id="lastfm_loading">Loading...</li>';
		div.html(html);
		// If the data isn't expanded hide most of the info.
		if (expanded == false) {
			$("#lastfm_stats").children(":not(.lastfm_essential)").addClass("hidden");
			$("#lastfm_reload_container").addClass("hidden");
		} else {
			$("#lastfm_reload_container").removeClass("hidden");
		}
		// Once all requests are completed, remove the loading message.
		if (tasteometer && lastPlayedTrack && sharedArtists && topArtists && topAlbums && topTracks) {
			$('#lastfm_loading').remove();
		}
	}

	// Escape ampersands with url code to avoid breaking the search links
	function escapeAmpUrl(input) {
		return input.replace(/&/g,"%26");
	}

	// Escape ampersands with html code to avoid breaking the search links
	function escapeHtml(input) {
		return input.replace(/&/g,"&#38;").replace(/</g,"&#60;");
	}


	// Functions for fetching the required data are as follows.
	// Also gets the data for shared artists as they're bundled.
	function getTasteometer(div) {
		if ($("#lastfm_stats").attr("data-uid")) {
			//Own profile, don't show tasteometer and shared artists.
			tasteometer = " ";
			sharedArtists = " ";
		} else if (username) {
			$.get('user.php?action=lastfm_compare&username=' + username, function (response) {
				// Two separate elements are received from one Last.fm API call.
				var tasteometerHtml = "";
				var sharedArtistsHtml = "";
				if (response && response != "false") {
					json = JSON.parse(response);
					if (json != null && json['error']) {
						console.log("Tasteometer: " + json['message']);
						// Specified non-existant username for Last.fm, remove Last.fm box from page.
						if (json['error'] == "7" ) {
							tasteometer = " ";
							sharedArtists = " ";
						}
					} else if (json == null) {
						// No Last.fm compare possible.
						tasteometer = " ";
						sharedArtists = " ";
					} else {
						var j = json['comparison']['result'];
						var a = j['artists']['artist'];
						tasteometerHtml += '<li class="lastfm_essential">Compatibility: ';
						var compatibility = Math.round(j['score'] * 100);
						var background;
						if (compatibility < 0 || compatibility > 100) {
							compatibility = "Unknown";
							tasteometerHtml += compatibility;
						} else {
							if (compatibility < 50) {
								background = 'rgb(255, '+Math.floor(255*compatibility/50)+', 0)'
							} else {
								background = 'rgb('+Math.floor((1-(compatibility-50)/50)*255)+', 255, 0)'
							}
							tasteometerHtml += compatibility + '%\r\
						<li class="lastfm_essential">\r\
							<div id="lastfm_compatibilitybar_container">\n\
								<div id="lastfm_compatibilitybar" style="width: '+compatibility+'%; background: '+background+';">\n\
								</div>\r\
							</div>\r\
						</li>';
						}
						// Only print shared artists if there are any
						if (j['artists']['matches'] != 0) {
							sharedArtistsHtml += '<li>Shared artists:</li><li><ul class="nobullet">';
							var k = initialCount;
							if (a.length < 3) {
								k = a.length;
							}
							for (var i = 0; i < k; i++) {
								sharedArtistsHtml += '<li><a href="artist.php?artistname=' + escapeAmpUrl(a[i]['name']) + '">' + escapeHtml(a[i]['name']) + '</a></li>'
							}
							if (a.length > 3) {
								for (i = 3; i < a.length; i++) {
									sharedArtistsHtml += '<li class="hidden"><a href="artist.php?artistname=' + escapeAmpUrl(a[i]['name']) + '">' + escapeHtml(a[i]['name']) + '</a></li>'
								}
								sharedArtistsHtml += '<li><a href="#sharedartists" id="lastfm_expand" onclick="return false" class="brackets">Expand</a></li>'
							}
							sharedArtistsHtml += '</ul></li>';
							sharedArtists = sharedArtistsHtml;
						} else {
							// Allow removing loading message regardless.
							sharedArtists = " ";
							sharedArtistsHtml += '<li class="lastfm_expand"><a href="#sharedartists" id="lastfm_expand" onclick="return false" class="brackets">Expand</a></li>'
						}
						tasteometerHtml += "</li>";
						tasteometer = tasteometerHtml;
					}
				} else {
					sharedArtists = " ";
					tasteometer = " ";
				}
				updateDivContents(div);
			});
		}
	}

	function getLastPlayedTrack(div) {
		if (!username) {
			return;
		}
		$.get('user.php?action=lastfm_last_played_track&username=' + username, function (response) {
			var html = "";
			if (response && response != "false") {
				var json = JSON.parse(response);
				if (json != null && json['error']) {
					console.log("Last played track: " + json['message']);
					lastPlayedTrack = " ";
				} else if (json == null) {
					// No last played track available.
					// Allow removing the loading message regardless.
					lastPlayedTrack = " ";
				} else {
					// Fix Last.fm API returning more than one entry despite limit on certain conditions.
					if (typeof(json[0]) === "object") {
						json = json[0];
					}
					html += '<li class="lastfm_essential">Last played: ';
					html += '<a href="artist.php?artistname=' + escapeAmpUrl(json['artist']['#text']) + '">' + escapeHtml(json['artist']['#text']) + '</a> - <a href="torrents.php?artistname=' + escapeAmpUrl(json['artist']['#text']) +'&filelist=' + escapeAmpUrl(json['name']) + '">' + escapeHtml(json['name']) + '</a>';
					html += "</li>";
					lastPlayedTrack = html;
				}
			} else {
				lastPlayedTrack = " ";
			}
			updateDivContents(div);
		});
	}

	function getTopArtists(div) {
		if (!username) {
			return;
		}
		$.get('user.php?action=lastfm_top_artists&username=' + username, function (response) {
			var html;
			if (response && response != "false") {
				var json = JSON.parse(response);
				if (json != null && json['error']) {
					console.log("Top artists: " + json['message']);
					topArtists = " ";
				} else if (json == null) {
					console.log("Error: json == null");
					topArtists = " ";
				} else if (json['topartists']['total'] == 0) {
					// No top artists for the specified user, possibly a new Last.fm account.
					// Allow removing the loading message regardless.
					topArtists = " ";
				} else {
					html = "<li>Top Artists:</li>";
					html += "<li>";
					var j = json['topartists']['artist'];
					html += '<ul class="nobullet">';
					var k = initialCount;
					if (j.length < 3) {
						k = j.length;
					}
					for (var i = 0; i < k; i++) {
						html += '<li><a href="artist.php?artistname=' + escapeAmpUrl(j[i]['name']) + '">' + escapeHtml(j[i]['name']) + '</a></li>'
					}
					if (j.length > 3) {
						for (i = 3; i < j.length; i++) {
							html += '<li class="hidden"><a href="artist.php?artistname=' + escapeAmpUrl(j[i]['name']) + '">' + escapeHtml(j[i]['name']) + '</a></li>'
						}
						html += '<li><a href="#topartists" id="lastfm_expand" onclick="return false" class="brackets">Expand</a></li>'
					}
					html += '</ul>';
					html += "</li>";
					topArtists = html;
				}
			} else {
				topArtists = " ";
			}
			updateDivContents(div);
		});
	}

	function getTopAlbums(div) {
		if (!username) {
			return;
		}
		$.get('user.php?action=lastfm_top_albums&username=' + username, function (response) {
			var html;
			if (response && response != "false") {
				var json = JSON.parse(response);
				if (json != null && json['error']) {
					console.log("Top albums: " + json['message']);
					topAlbums = " ";
				} else if (json == null) {
					console.log("Error: json == null");
					topAlbums = " ";
				} else if (json['topalbums']['total'] == 0) {
					// No top artists for the specified user, possibly a new Last.fm account.
					// Allow removing the loading message regardless.
					topAlbums = " ";
				} else {
					var j = json['topalbums']['album'];
					html = "<li>Top Albums:</li>";
					html += "<li>";
					html += '<ul class="nobullet">';
					var k = initialCount;
					if (j.length < 3) {
						k = j.length;
					}
					for (var i = 0; i < k; i++) {
						html += '<li><a href="artist.php?artistname=' + escapeAmpUrl(j[i]['artist']['name']) + '">' + escapeHtml(j[i]['artist']['name']) + '</a> - <a href="torrents.php?searchstr=' + escapeAmpUrl(j[i]['name']) + '">' + escapeHtml(j[i]['name']) + '</a></li>'
					}
					if (j.length > 3) {
						for (i = 3; i < j.length; i++) {
							html += '<li class="hidden"><a href="artist.php?artistname=' + escapeAmpUrl(j[i]['artist']['name']) + '">' + escapeHtml(j[i]['artist']['name']) + '</a> - <a href="torrents.php?searchstr=' + escapeAmpUrl(j[i]['name']) + '">' + escapeHtml(j[i]['name']) + '</a></li>'
						}
						html+= '<li><a href="#topalbums" id="lastfm_expand" onclick="return false" class="brackets">Expand</a></li>'
					}
					html += '</ul>';
					html += "</li>";
					topAlbums = html;
				}
			} else {
				topAlbums = " ";
			}
			updateDivContents(div);
		});
	}

	function getTopTracks(div) {
		if (!username) {
			return;
		}
		$.get('user.php?action=lastfm_top_tracks&username=' + username, function (response) {
			var html;
			if (response && response != "false") {
				var json = JSON.parse(response);
				if (json != null && json['error']) {
					console.log("Toptracks: " + json['message']);
					topTracks = " ";
				} else if (json == null) {
					console.log("Error: json == null");
					topTracks = " ";
				} else if (json['toptracks']['total'] == 0) {
					// No top artists for the specified user, possibly a new Last.fm account.
					// Allow removing the loading message regardless.
					topTracks = " ";
				} else {
					html = "<li>Top Tracks:</li>";
					html += "<li>";
					var j = json['toptracks']['track'];
					html += '<ul class="nobullet">';
					var k = initialCount;
					if (j.length < 3) {
						k = j.length;
					}
					for (var i = 0; i < k; i++) {
						html += '<li><a href="artist.php?artistname=' + escapeAmpUrl(j[i]['artist']['name']) + '">' + escapeHtml(j[i]['artist']['name']) + '</a> - <a href="torrents.php?artistname=' + escapeAmpUrl(j[i]['artist']['name']) + '&filelist=' + escapeAmpUrl(j[i]['name']) + '">' + escapeHtml(j[i]['name']) + '</a></li>'
					}
					if (j.length > 3) {
						for (i = 3; i < j.length; i++) {
							html += '<li class="hidden"><a href="artist.php?artistname=' + escapeAmpUrl(j[i]['artist']['name']) + '">' + escapeHtml(j[i]['artist']['name']) + '</a> - <a href="torrents.php?artistname=' + escapeAmpUrl(j[i]['artist']['name']) + '&filelist=' + escapeAmpUrl(j[i]['name']) + '">' + escapeHtml(j[i]['name']) + '</a></li>'
						}
						html+= '<li><a href="#toptracks" id="lastfm_expand" onclick="return false" class="brackets">Expand</a></li>'
					}
					html += '</ul>';
					html += "</li>";
					topTracks = html;
				}
			} else {
				topTracks = " ";
			}
			updateDivContents(div);
		});
	}

})();
