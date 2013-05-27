function show_peers (TorrentID, Page) {
	if (Page > 0) {
		ajax.get('torrents.php?action=peerlist&page='+Page+'&torrentid=' + TorrentID,function(response) {
			$('#peers_' + TorrentID).show().raw().innerHTML=response;
		});
	} else {
		if ($('#peers_' + TorrentID).raw().innerHTML === '') {
			$('#peers_' + TorrentID).show().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=peerlist&torrentid=' + TorrentID,function(response) {
				$('#peers_' + TorrentID).show().raw().innerHTML=response;
			});
		} else {
			$('#peers_' + TorrentID).toggle();
		}
	}
	$('#snatches_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#files_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_snatches (TorrentID, Page) {
	if (Page > 0) {
		ajax.get('torrents.php?action=snatchlist&page='+Page+'&torrentid=' + TorrentID,function(response) {
			$('#snatches_' + TorrentID).show().raw().innerHTML=response;
		});
	} else {
		if ($('#snatches_' + TorrentID).raw().innerHTML === '') {
			$('#snatches_' + TorrentID).show().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=snatchlist&torrentid=' + TorrentID,function(response) {
				$('#snatches_' + TorrentID).show().raw().innerHTML=response;
			});
		} else {
			$('#snatches_' + TorrentID).toggle();
		}
	}
	$('#peers_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#files_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_downloads (TorrentID, Page) {
	if (Page > 0) {
		ajax.get('torrents.php?action=downloadlist&page='+Page+'&torrentid=' + TorrentID,function(response) {
			$('#downloads_' + TorrentID).show().raw().innerHTML=response;
		});
	} else {
		if ($('#downloads_' + TorrentID).raw().innerHTML === '') {
			$('#downloads_' + TorrentID).show().raw().innerHTML = '<h4>Loading...</h4>';
			ajax.get('torrents.php?action=downloadlist&torrentid=' + TorrentID,function(response) {
				$('#downloads_' + TorrentID).raw().innerHTML=response;
			});
		} else {
			$('#downloads_' + TorrentID).toggle();
		}
	}
	$('#peers_' + TorrentID).hide();
	$('#snatches_' + TorrentID).hide();
	$('#files_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_files(TorrentID) {
	$('#files_' + TorrentID).toggle();
	$('#peers_' + TorrentID).hide();
	$('#snatches_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#reported_' + TorrentID).hide();
}

function show_reported(TorrentID) {
	$('#files_' + TorrentID).hide();
	$('#peers_' + TorrentID).hide();
	$('#snatches_' + TorrentID).hide();
	$('#downloads_' + TorrentID).hide();
	$('#reported_' + TorrentID).toggle();
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
	for (var i = 0; i < group_rows.length; i++) {
		var row = $(group_rows[i]);
		if (row.has_class('colhead_dark')) {
			continue;
		}
		if (row.has_class('colhead')) {
			continue;
		}
		var relevantRow = row.has_class('group') ? $(group_rows[i+1]) : row;
		if (allGroups || relevantRow.has_class('groupid_' + groupid)) {
			row = $(group_rows[i]); // idk why we need this :S
			if (row.has_class('group')) {
				$('a.show_torrents_link', row.raw()).raw().title = (showing) ? 'Collapse this group. Hold "Ctrl" while clicking to collapse all groups/editions in this section.' : 'Expand this group. Hold "Ctrl" while clicking to expand all groups/editions in this section.';
				$('a.show_torrents_link', row.raw()).raw().parentNode.className = (showing) ? 'hide_torrents' : 'show_torrents';
			} else {
				if (showing) {
					// show the row depending on whether the edition it's in is collapsed or not
					if (row.has_class('edition')) {
						row.show();
						showRow = ($('a', row.raw()).raw().innerHTML != '+');
					} else {
						if (showRow) {
							row.show();
						} else {
							row.hide();
						}
					}
				} else {
					row.hide();
				}
			}
		}
	}
	if (event.preventDefault) {
		event.preventDefault();
	} else {
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
			$('a', row.raw()).raw().innerHTML = (showing) ? '&minus;' : '+';
			$('a', row.raw()).raw().title = (showing) ? 'Collapse this edition. Hold "Ctrl" to collapse all editions in this torrent group.' : 'Expand this edition. Hold "Ctrl" to expand all editions in this torrent group.';
			continue;
		}
		if (allEditions || row.has_class('edition_' + editionid)) {
			if (showing && !row.has_class('torrentdetails')) {
				row.show();
			} else {
				row.hide();
			}
		}
	}
	if (event.preventDefault) {
		event.preventDefault();
	} else {
		event.returnValue = false;
	}
}

function toggleTorrentSearch(mode) {
	if (mode == 0) {
		var link = $('#ft_toggle').raw();
		$('#ft_container').toggle();
		link.innerHTML = link.textContent == 'Hide' ? 'Show' : 'Hide';
	} if (mode == 'basic') {
		$('.fti_advanced').disable();
		$('.fti_basic').enable();
		$('.ftr_advanced').hide(true);
		$('.ftr_basic').show();
		$('#ft_advanced_link').show();
		$('#ft_advanced_text').hide();
		$('#ft_basic_link').hide();
		$('#ft_basic_text').show();
		$('#ft_type').raw().value = 'basic';
	} else if (mode == 'advanced') {
		$('.fti_advanced').enable();
		$('.fti_basic').disable();
		$('.ftr_advanced').show();
		$('.ftr_basic').hide();
		$('#ft_advanced_link').hide();
		$('#ft_advanced_text').show();
		$('#ft_basic_link').show();
		$('#ft_basic_text').hide();
		$('#ft_type').raw().value = 'advanced';
	}
	return false;
}

// For /sections/torrents/browse.php (not browse2.php)
function Bitrate() {
	$('#other_bitrate').raw().value = '';
	if ($('#bitrate').raw().options[$('#bitrate').raw().selectedIndex].value == 'Other') {
		$('#other_bitrate_span').show();
	} else {
		$('#other_bitrate_span').hide();
	}
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

	if(!hasCoverAddButton) {
		x = $('#add_covers_form').raw();
		field = document.createElement("input");
		field.type = "submit";
		field.value = "Add";
		x.appendChild(field);
		hasCoverAddButton = true;
	}
}


function ToggleEditionRows() {
	$('#edition_title').toggle();
	$('#edition_label').toggle();
	$('#edition_catalogue').toggle();
}

function check_private(TorrentID) {
	$('#checkprivate-'+TorrentID).raw().innerHTML = "Checking...";
	ajax.get('ajax.php?action=checkprivate&torrentid=' + TorrentID,function(response) {
		$('#checkprivate-'+TorrentID).raw().innerHTML = response;
	});
}
