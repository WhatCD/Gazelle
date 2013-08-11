function clearItem(torrentId) {
	ajax.get("?action=notify_clear_item&torrentid=" + torrentId + "&auth=" + authkey, function() {
			$("#torrent" + torrentId).remove();
		});
}

function clearSelected(filterId) {
	var checkBoxes, checkedBoxes = [];
	if (filterId) {
		var filterForm = $('#notificationform_' + filterId);
		checkBoxes = $('.notify_box_' + filterId, filterForm);
	} else {
		checkBoxes = $('.notify_box');
	}
	for (var i = checkBoxes.length - 1; i >= 0; i--) {
		if (checkBoxes[i].checked) {
			checkedBoxes.push(checkBoxes[i].value);
		}
	}
	ajax.get("?action=notify_clear_items&torrentids=" + checkedBoxes.join(',') + "&auth=" + authkey, function() {
			for (var i = checkedBoxes.length - 1; i >= 0; i--) {
				$('#torrent' + checkedBoxes[i]).remove();
			}
		});
}
