function show_peers (TorrentID, Page) {
	if (Page > 0) {
		ajax.get('torrents.php?action=peerlist&page=' + Page + '&torrentid=' + TorrentID, function(response) {
			$('#peers_' + TorrentID).gshow().raw().innerHTML = response;
		});
	} else {
		if ($('#peers_' + TorrentID).raw().innerHTML === '') {
			$('#peers_' + TorrentID).gshow().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=peerlist&torrentid=' + TorrentID, function(response) {
				$('#peers_' + TorrentID).gshow().raw().innerHTML = response;
			});
		} else {
			$('#peers_' + TorrentID).gtoggle();
		}
	}
	$('#snatches_' + TorrentID).ghide();
	$('#downloads_' + TorrentID).ghide();
	$('#files_' + TorrentID).ghide();
	$('#reported_' + TorrentID).ghide();
}

function show_snatches (TorrentID, Page) {
	if (Page > 0) {
		ajax.get('torrents.php?action=snatchlist&page=' + Page + '&torrentid=' + TorrentID, function(response) {
			$('#snatches_' + TorrentID).gshow().raw().innerHTML = response;
		});
	} else {
		if ($('#snatches_' + TorrentID).raw().innerHTML === '') {
			$('#snatches_' + TorrentID).gshow().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=snatchlist&torrentid=' + TorrentID, function(response) {
				$('#snatches_' + TorrentID).gshow().raw().innerHTML = response;
			});
		} else {
			$('#snatches_' + TorrentID).gtoggle();
		}
	}
	$('#peers_' + TorrentID).ghide();
	$('#downloads_' + TorrentID).ghide();
	$('#files_' + TorrentID).ghide();
	$('#reported_' + TorrentID).ghide();
}

function show_downloads (TorrentID, Page) {
	if (Page > 0) {
		ajax.get('torrents.php?action=downloadlist&page=' + Page + '&torrentid=' + TorrentID, function(response) {
			$('#downloads_' + TorrentID).gshow().raw().innerHTML = response;
		});
	} else {
		if ($('#downloads_' + TorrentID).raw().innerHTML === '') {
			$('#downloads_' + TorrentID).gshow().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=downloadlist&torrentid=' + TorrentID, function(response) {
				$('#downloads_' + TorrentID).raw().innerHTML = response;
			});
		} else {
			$('#downloads_' + TorrentID).gtoggle();
		}
	}
	$('#peers_' + TorrentID).ghide();
	$('#snatches_' + TorrentID).ghide();
	$('#files_' + TorrentID).ghide();
	$('#reported_' + TorrentID).ghide();
}

function show_files(TorrentID) {
	$('#files_' + TorrentID).gtoggle();
	$('#peers_' + TorrentID).ghide();
	$('#snatches_' + TorrentID).ghide();
	$('#downloads_' + TorrentID).ghide();
	$('#reported_' + TorrentID).ghide();
}

function show_reported(TorrentID) {
	$('#files_' + TorrentID).ghide();
	$('#peers_' + TorrentID).ghide();
	$('#snatches_' + TorrentID).ghide();
	$('#downloads_' + TorrentID).ghide();
	$('#reported_' + TorrentID).gtoggle();
}

function add_tag(tag) {
	if ($('#tags').raw().value == "") {
		$('#tags').raw().value = tag;
	} else {
		$('#tags').raw().value = $('#tags').raw().value + ", " + tag;
	}
}

function toggle_group(groupid, link, event) {
	var clickedRow = link;
	while (clickedRow.nodeName != 'TR') {
		clickedRow = clickedRow.parentNode;
	}
	var group_rows = clickedRow.parentNode.children;
	var showing = $(clickedRow).nextElementSibling().has_class('hidden');
	var allGroups = event.ctrlKey;

	// for dealing with Mac OS X
	// http://stackoverflow.com/a/3922353
	var allGroupsMac = (
				event.keyCode == 91 // WebKit (left apple)
				|| event.keyCode == 93 // WebKit (right apple)
				|| event.keyCode == 224 // Firefox
				|| event.keyCode == 17 // Opera
				) ? 91 : null;

	for (var i = 0; i < group_rows.length; i++) {
		var row = $(group_rows[i]);
		if (row.has_class('colhead_dark')) {
			continue;
		}
		if (row.has_class('colhead')) {
			continue;
		}
		var relevantRow = row.has_class('group') ? $(group_rows[i + 1]) : row;
		if (allGroups || allGroupsMac || relevantRow.has_class('groupid_' + groupid)) {
			row = $(group_rows[i]); // idk why we need this :S
			if (row.has_class('group')) {
				var section;
				if (location.pathname.search('/artist.php$') !== -1) {
					section = 'in this release type.';
				} else {
					section = 'on this page.';
				}
				var tooltip = showing
					? 'Collapse this group. Hold "Ctrl" while clicking to collapse all groups '+section
					: 'Expand this group. Hold "Ctrl" while clicking to expand all groups '+section;
				$('a.show_torrents_link', row).updateTooltip(tooltip);
				$('a.show_torrents_link', row).raw().parentNode.className = (showing) ? 'hide_torrents' : 'show_torrents';
			} else {
				if (showing) {
					// show the row depending on whether the edition it's in is collapsed or not
					if (row.has_class('edition')) {
						row.gshow();
						showRow = ($('a', row.raw()).raw().innerHTML != '+');
					} else {
						if (showRow) {
							row.gshow();
						} else {
							row.ghide();
						}
					}
				} else {
					row.ghide();
				}
			}
		}
	}
	if (event.preventDefault) {
		event.preventDefault();
	} else {
		// for IE < 9 support
		event.returnValue = false;
	}
}

function toggle_edition(groupid, editionid, lnk, event) {
	var clickedRow = lnk;
	while (clickedRow.nodeName != 'TR') {
		clickedRow = clickedRow.parentNode;
	}
	//var showing = has_class(nextElementSibling(clickedRow), 'hidden');
	var showing = $(clickedRow).nextElementSibling().has_class('hidden');
	var allEditions = event.ctrlKey;
	var group_rows = $('tr.groupid_' + groupid);
	for (var i = 0; i < group_rows.results(); i++) {
		var row = $(group_rows.raw(i));
		if (row.has_class('edition') && (allEditions || row.raw(0) == clickedRow)) {
			var tooltip = showing
				? 'Collapse this edition. Hold "Ctrl" while clicking to collapse all editions in this torrent group.'
				: 'Expand this edition. Hold "Ctrl" while clicking to expand all editions in this torrent group.';
			$('a', row).raw().innerHTML = (showing) ? '&minus;' : '+';
			$('a', row).updateTooltip(tooltip);
			continue;
		}
		if (allEditions || row.has_class('edition_' + editionid)) {
			if (showing && !row.has_class('torrentdetails')) {
				row.gshow();
			} else {
				row.ghide();
			}
		}
	}
	if (event.preventDefault) {
		event.preventDefault();
	} else {
		// for IE < 9 support
		event.returnValue = false;
	}
}

function toggleTorrentSearch(mode) {
	if (mode == 0) {
		var link = $('#ft_toggle').raw();
		$('#ft_container').gtoggle();
		link.innerHTML = link.textContent == 'Hide' ? 'Show' : 'Hide';
	}
	if (mode == 'basic') {
		$('.fti_advanced').disable();
		$('.fti_basic').enable();
		$('.ftr_advanced').ghide(true);
		$('.ftr_basic').gshow();
		$('#ft_advanced_link').gshow();
		$('#ft_advanced_text').ghide();
		$('#ft_basic_link').ghide();
		$('#ft_basic_text').gshow();
		$('#ft_type').raw().value = 'basic';
	} else if (mode == 'advanced') {
		$('.fti_advanced').enable();
		$('.fti_basic').disable();
		$('.ftr_advanced').gshow();
		$('.ftr_basic').ghide();
		$('#ft_advanced_link').ghide();
		$('#ft_advanced_text').gshow();
		$('#ft_basic_link').gshow();
		$('#ft_basic_text').ghide();
		$('#ft_type').raw().value = 'advanced';
	}
	return false;
}

var ArtistFieldCount = 1;

function AddArtistField() {
	if (ArtistFieldCount >= 100) {
		return;
	}
	var x = $('#AddArtists').raw();
	x.appendChild(document.createElement("br"));
	var ArtistField = document.createElement("input");
	ArtistField.type = "text";
	ArtistField.name = "aliasname[]";
	ArtistField.size = "17";
	x.appendChild(ArtistField);
	x.appendChild(document.createTextNode(' '));
	var Importance = document.createElement("select");
	Importance.name = "importance[]";
	Importance.innerHTML = '<option value="1">Main</option><option value="2">Guest</option><option value="4">Composer</option><option value="5">Conductor</option><option value="6">DJ / Compiler</option><option value="3">Remixer</option><option value="7">Producer</option>';
	x.appendChild(Importance);
	if ($("#artist").data("gazelle-autocomplete")) {
		$(ArtistField).live('focus', function() {
			$(ArtistField).autocomplete({
				serviceUrl : 'artist.php?action=autocomplete'
			});
		});
	}
	ArtistFieldCount++;
}

var coverFieldCount = 0;
var hasCoverAddButton = false;

function addCoverField() {
	if (coverFieldCount >= 100) {
		return;
	}
	var x = $('#add_cover').raw();
	x.appendChild(document.createElement("br"));
	var field = document.createElement("input");
	field.type = "text";
	field.name = "image[]";
	field.placeholder = "URL";
	x.appendChild(field);
	x.appendChild(document.createTextNode(' '));
	var summary = document.createElement("input");
	summary.type = "text";
	summary.name = "summary[]";
	summary.placeholder = "Summary";
	x.appendChild(summary);
	coverFieldCount++;

	if (!hasCoverAddButton) {
		x = $('#add_covers_form').raw();
		field = document.createElement("input");
		field.type = "submit";
		field.value = "Add";
		x.appendChild(field);
		hasCoverAddButton = true;
	}
}

function ToggleEditionRows() {
	$('#edition_title').gtoggle();
	$('#edition_label').gtoggle();
	$('#edition_catalogue').gtoggle();
}
