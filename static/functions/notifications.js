function clearItem(torrentId) {
	ajax.get("?action=notify_clear_item&torrentid=" + torrentId + "&auth=" + authkey, function() {
			$("#torrent" + torrentId).remove();
		});
}

function clearSelected(filterId) {
	var checkBoxes, checkedBoxes = [];
	if (filterId) {
		var filterForm = $('#notificationform_'+filterId).raw();
		checkBoxes = $('.notify_box_'+filterId, filterForm).objects;
	} else {
		checkBoxes = $('.notify_box').objects;
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

function toggleBoxes(filterId, value) {
	var filterForm = $('#notificationform_'+filterId).raw();
	var checkBoxes = $('.notify_box_'+filterId, filterForm).objects;
	for (var i = checkBoxes.length - 1; i >= 0; i--) {
		checkBoxes[i].checked = value;
	}
}

/* Remove these */
function GroupClear(form) {
	for (var i = 0; i < form.elements.length; i++ ) {
		if (form.elements[i].type == 'checkbox' && form.elements[i].name != 'toggle') {
			if (form.elements[i].checked == true) {
				Clear(form.elements[i].value);
			}
		}
	}
}

function SuperGroupClear() {
	for (var i = 0; i < document.forms.length; i++ ) {
		GroupClear(document.forms[i]);
	}
}
