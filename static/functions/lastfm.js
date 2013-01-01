//skipfile
(function ($) {
	var TAB_COUNT = 0;
	var topArtistsLoaded = false;
	var topAlbumsLoaded = false;
	var topTracksLoaded = false;
	var tasteometerLoaded = false;
	var username;
	$(document).ready(function () {
		init();
	});

	function init() {
		username = $('#lastfm_username').text();
		$('#tabs').children('a').each(function () {
			var i = TAB_COUNT;
			$(this).click(function () {
				switchTo(i);
				return false;
			});
			TAB_COUNT++;
		});
	}

	function getTopArtists(div) {
		if (!topArtistsLoaded) {
			div.html('Loading...');
			ajax.get('user.php?action=lastfm_top_artists&username=' + username, function (response) {
				var json = JSON.parse(response);
				if (json != null && json['error']) {
					div.html(json['message']);
				}
				else if (json == null) {
					div.html("Error");
				}
				else {
					var j = json['topartists']['artist'];
					var html = '<strong>Top Artists</strong><ul class="nobullet">';
					for (var i = 0; i < j.length; i++) {
						html += '<li><a href="torrents.php?searchstr=' + j[i]['name'] + '">' + j[i]['name'] + '</a></li>'
					}
					html += '</ul>';
					div.html(html);
					topArtistsLoaded = true;
				}
			});
		}
	}

	function getTopAlbums(div) {
		if (!topAlbumsLoaded) {
			div.html('Loading...');
			ajax.get('user.php?action=lastfm_top_albums&username=' + username, function (response) {
				var json = JSON.parse(response);
				if (json != null && json['error']) {
					div.html(json['message']);
				}
				else if (json == null) {
					div.html("Error");
				}
				else {
					var j = json['topalbums']['album'];
					var html = '<strong>Top Albums</strong><ul class="nobullet">';
					for (var i = 0; i < j.length; i++) {
						html += '<li><a href="torrents.php?searchstr=' + j[i]['name'] + '">' + j[i]['name'] + '</a> - <a href="torrents.php?searchstr=' + j[i]['artist']['name'] + '">' + j[i]['artist']['name'] + '</a></li>'
					}
					html += '</ul>';
					div.html(html);
					topAlbumsLoaded = true;
				}
			});
		}
	}

	function getTopTracks(div) {
		if (!topTracksLoaded) {
			div.html('Loading...');
			if (json != null && json['error']) {
				div.html(json['message']);
			}
			else if (json == null) {
				div.html("Error");
			}
			else {
				ajax.get('user.php?action=lastfm_top_tracks&username=' + username, function (response) {
					var json = JSON.parse(response);
					var j = json['toptracks']['track'];
					if (j != null) {
						var html = '<strong>Top Tracks</strong><ul class="nobullet">';
						for (var i = 0; i < j.length; i++) {
							html += '<li><a href="torrents.php?searchstr=' + j[i]['name'] + '">' + j[i]['name'] + '</a> - <a href="torrents.php?searchstr=' + j[i]['artist']['name'] + '">' + j[i]['artist']['name'] + '</a></li>'
						}
						html += '</ul>';
						div.html(html);
					}
					else {
						div.html('Error');
					}
					topTracksLoaded = true;
				});
			}
		}
	}

	function getTasteometer(div) {
		if (!tasteometerLoaded) {
			div.html('Loading...');
			ajax.get('user.php?action=lastfm_compare_users&username=' + username, function (response) {
				var json = JSON.parse(response);
				if (json != null && json['error']) {
					div.html(json['message']);
				}
				else if (json == null) {
					div.html("Error, do you have your Last.FM username set in settings?");
				}
				else {
					var j = json['comparison']['result'];
					var a = j['artists']['artist'];
					console.log(a);
					var compatibility = Math.round(j['score'] * 100);
					var html = '<strong>Tasteometer</strong><br/>Compatibility: ' + compatibility + '% <ul class="nobullet">';
					for (var i = 0; i < a.length; i++) {
						html += '<li><a href="torrents.php?searchstr=' + a[i]['name'] + '">' + a[i]['name'] + '</li>'
					}
					html += '</ul>';
					div.html(html);
					tasteometerLoaded = true;
				}
			});
		}
	}

	function switchTo(tab) {
		var i = 0;
		$('#tabs').children('a').each(function () {
			if (i != tab) {
				$(this).css('font-weight', '');
			} else {
				$(this).css('font-weight', 'bold');
			}
			i++;
		});
		i = 0;
		$('#contents_div').children('div').each(function () {
			if (i != tab) {
				$(this).hide();
			} else {
				$(this).show();
				switch (tab) {
					case 1:
						getTopArtists($(this));
						break;
					case 2:
						getTopAlbums($(this));
						break;
					case 3:
						getTopTracks($(this));
						break;
					case 4:
						getTasteometer($(this));
						break;
					default:
						break;

				}
			}
			i++;
		});
	}

})(jQuery);
