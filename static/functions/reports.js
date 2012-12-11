function toggleNotes(id) {
	var style = $('#notes_div_' + id).raw().style.display;
	if (style == "none") {
		$('#notes_div_' + id).raw().style.display = "block";
	}
	else {
		$('#notes_div_' + id).raw().style.display = "none";
	}
}

function saveNotes(id) {
	var notes = $('#notes_' + id).raw().value;
	notes = notes.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '<br />');
	ajax.get('reports.php?action=add_notes&id=' + id + '&notes=' + notes, function (response) {
		if (JSON.parse(response)['status'] != 'success') {
			alert("Error, could not save notes");
		}
	});

}

function claim(id) {
	ajax.get('reports.php?action=claim&id=' + id, function (response) {
		var json = JSON.parse(response);
		if (json['status'] == 'failure') {
			alert("Error, could not claim.");
		}
		if (json['status'] == 'dupe') {
			alert("Oops, this report has already been claimed.");
		}
		if (json['status'] == 'success') {
			var username = json['username'];
			$('#claim_' + id).raw().innerHTML = '<a href="#" onclick="return false;">Claimed by ' + username + '</a>';
		}
	});
}

function resolve(id) {
	if ($('#claimed_' + id).raw()) {
		var answer = confirm("This is a claimed report, are you sure you want to resolve it?");
		if (answer)
			return true;
		else
			return false;
	}
	return true;
}
