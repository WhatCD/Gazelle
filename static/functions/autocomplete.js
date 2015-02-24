var ARTIST_AUTOCOMPLETE_URL = 'artist.php?action=autocomplete';
var TAGS_AUTOCOMPLETE_URL = 'torrents.php?action=autocomplete_tags';
var SELECTOR = '[data-gazelle-autocomplete="true"]';
$(document).ready(function() {
	var url = new gazURL();

	$('#artistsearch' + SELECTOR).autocomplete({
		deferRequestBy: 300,
		onSelect : function(suggestion) {
			window.location = 'artist.php?id=' + suggestion['data'];
		},
		serviceUrl : ARTIST_AUTOCOMPLETE_URL,
	});

	if (url.path == 'torrents' || url.path == 'upload' || url.path == 'artist' || (url.path == 'requests' && url.query['action'] == 'new') || url.path == 'collages') {
		$("#artist" + SELECTOR).autocomplete({
			deferRequestBy: 300,
			serviceUrl : ARTIST_AUTOCOMPLETE_URL
		});
		$("#artistsimilar" + SELECTOR).autocomplete({
			deferRequestBy: 300,
			serviceUrl : ARTIST_AUTOCOMPLETE_URL
		});
	}
	if (url.path == 'torrents' || url.path == 'upload' || url.path == 'collages' || url.path == 'requests' || url.path == 'top10' || (url.path == 'requests' && url.query['action'] == 'new')) {
		$("#tags" + SELECTOR).autocomplete({
			deferRequestBy: 300,
			delimiter: ',',
			serviceUrl : TAGS_AUTOCOMPLETE_URL
		});
		$("#tagname" + SELECTOR).autocomplete({
			deferRequestBy: 300,
			serviceUrl : TAGS_AUTOCOMPLETE_URL,
		});
	}

});
