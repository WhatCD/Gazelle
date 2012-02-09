function Clear(torrentid) {
	ajax.get("?action=notify_clearitem&torrentid=" + torrentid + "&auth=" + authkey, function() {
		$("#torrent" + torrentid).remove();
	});
}

function GroupClear(form) {
	for (var i = 0; i < form.elements.length; i++ ) {
        if (form.elements[i].type == 'checkbox' && form.elements[i].name != 'toggle') {
            if (form.elements[i].checked == true) {
                Clear(form.elements[i].value);
            }
        }
	}
}

function ToggleBoxes(form, newval) {
	for (var i = 0; i < form.elements.length; i++ ) {
        if (form.elements[i].type == 'checkbox' && form.elements[i].name != 'toggle') {
            form.elements[i].checked = newval;
        }
	}
}

function SuperGroupClear() {
	for (var i = 0; i < document.forms.length; i++ ) {
        GroupClear(document.forms[i]);
	}
}