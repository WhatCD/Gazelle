(function() {

var LIMIT = 10;
var artistId, artistName;
var artistTags;
$(document).ready(function() {
	initArtistCloud();
});
function initArtistCloud() {
	$("#currentArtist").text();

	artistTags = $("#artistTags").find('ul');
	artistName = $("#content").find("h2:first").text();
	artistId = window.location.search.split("?id=")[1];
	addArtistMain(artistName);
	loadArtists();
}


function loadArtists() {
	$.getJSON('ajax.php?action=similar_artists&id='+artistId+'&limit='+LIMIT, function(data) {
		var first = true;
		var ratio;
		$.each(data, function(key, val) {
			if (first) {
				ratio = val['score'] / 300;
				first = false;
			}
			var score = val['score'] / ratio;
			score = score <= 150 ? 150 : score;
			addArtist(val['id'], val['name'], score);
		});

	createCloud();
	});

}

function addArtist(id, name, score) {
	var item = $('<li><a style="color:#007DC6;" data-weight="' + score + '">' + name + '</a></li>');

	$(item).click(function(e) {
		e.preventDefault();
		reinit(id, name);
	});

	artistTags.append(item);
}

function addArtistMain(name) {
	var item = $('<li><a style="color: #007DC6;" data-weight="350">' + name + '</a></li>');

	$("#currentArtist").attr('href', 'artist.php?id=' + artistId);
	$("#currentArtist").text(artistName);


	$(item).click(function(e) {
		e.preventDefault();
		reinit(artistId, name);
	});

	artistTags.append(item);
}

function reinit(id, name) {
	artistId = id;
	artistName = name;
	artistTags.empty();
	addArtistMain(artistName);
	loadArtists();
}

function createCloud() {
	if (!$('#similarArtistsCanvas').tagcanvas({

		// textFont: 'Impact,"Arial Black",sans-serif',
		wheelZoom: false,
		freezeActive: true,
		weightSize: 0.15,
		interval: 20,
		textFont: null,
		textColour: null,
		textHeight: 25,
		outlineColour: '#f96',
		outlineThickness: 4,
		maxSpeed: 0.04,
		minBrightness: 0.1,
		depth: 0.92,
		pulsateTo: 0.2,
		pulsateTime: 0.75,
		initial: [0.1,-0.1],
		decel: 0.98,
		reverse: true,
		shadow: '#ccf',
		shadowBlur: 3,
		weight : true,
		weightFrom: 'data-weight'
	},'artistTags')) {
		// something went wrong, hide the canvas container
		$('#flip_view_2').hide();
	}
}

})();
