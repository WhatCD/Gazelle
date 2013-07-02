var ARTIST_AUTOCOMPLETE_URL = 'artist.php?action=autocomplete';
var TAGS_AUTOCOMPLETE_URL = 'torrents.php?action=autocomplete_tags';

$(document).ready(function() {
	var url = new URL();

	$('#artistsearch').autocomplete({
		serviceUrl : ARTIST_AUTOCOMPLETE_URL,
		onSelect : function(suggestion) {
			window.location = 'artist.php?id=' + suggestion['data'];
		},
	});

	if (url.path == 'torrents' || url.path == 'upload' || url.path == 'artist') {
		$("#artist").autocomplete({
			serviceUrl : ARTIST_AUTOCOMPLETE_URL
		});
		$("#artistsimilar").autocomplete({
			serviceUrl : ARTIST_AUTOCOMPLETE_URL
		});
	}
	if (url.path == 'torrents' || url.path == 'upload' || url.path == 'collages' || url.path == 'requests' || url.path == 'top10' || (url.path == 'requests' && url.query['action'] == 'new')) {
		$("#tags").autocomplete({
			serviceUrl : TAGS_AUTOCOMPLETE_URL,
			delimiter: ','
		});
		$("#tagname").autocomplete({
			serviceUrl : TAGS_AUTOCOMPLETE_URL,
		});
	}

});
