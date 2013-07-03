var ARTIST_AUTOCOMPLETE_URL = 'artist.php?action=autocomplete';
var TAGS_AUTOCOMPLETE_URL = 'torrents.php?action=autocomplete_tags';
var SELECTOR = '[data-gazelle-autocomplete="true"]';
$(document).ready(function() {
	var url = new URL();

	$('#artistsearch' + SELECTOR).autocomplete({
		serviceUrl : ARTIST_AUTOCOMPLETE_URL,
		onSelect : function(suggestion) {
			window.location = 'artist.php?id=' + suggestion['data'];
		},
	});

	if (url.path == 'torrents' || url.path == 'upload' || url.path == 'artist') {
		$("#artist" + SELECTOR).autocomplete({
			serviceUrl : ARTIST_AUTOCOMPLETE_URL
		});
		$("#artistsimilar" + SELECTOR).autocomplete({
			serviceUrl : ARTIST_AUTOCOMPLETE_URL
		});
	}
	if (url.path == 'torrents' || url.path == 'upload' || url.path == 'collages' || url.path == 'requests' || url.path == 'top10' || (url.path == 'requests' && url.query['action'] == 'new')) {
		$("#tags" + SELECTOR).autocomplete({
			serviceUrl : TAGS_AUTOCOMPLETE_URL,
			delimiter: ','
		});
		$("#tagname" + SELECTOR).autocomplete({
			serviceUrl : TAGS_AUTOCOMPLETE_URL,
		});
	}

});
